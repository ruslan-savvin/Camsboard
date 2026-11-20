<?php

//Savvin Ruslan 2024
namespace App;

use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;

class Authorization
{
    private string $cookieName = "";

    public function __construct()
    {
        $this->cookieName = "guestCookie"; //userCookie
    }

    public function checkSession(bool $waitingForAuthorizationStatusReturn)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $idCookies = filter_input(INPUT_COOKIE, $this->cookieName);
        if (empty($idCookies)) {
            if ($waitingForAuthorizationStatusReturn === true) {
                ReturnData::getInstance()->set(["authorized" => false], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
                return;
            }
            ReturnData::getInstance()->set("Authorization error Cookie is empty", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
            return;
        }
        if (isset($_SESSION['auth_cookie']) && $_SESSION['auth_cookie'] === $idCookies) {
            if ($waitingForAuthorizationStatusReturn === true) {
                ReturnData::getInstance()->set(["authorized" => true], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
            }
            return;
        }
        $data["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "cookies", "columns" => "userId"],
                1 => ["action" => "+", "prefix" => "users", "columns" => "id"]
            ],
            "tables" => ["cookies", "users"],
            "join" => "LEFT JOIN users ON cookies.userId = users.id",
            "where" => "WHERE cookies.idCookies IN ('" . $idCookies . "') AND users.role = 2",
        ];
        $records = DataProvider::getInstance()->read($data);
        if (!is_array($records) || count($records) === 0) {
            unset($_SESSION['auth_cookie']);
            if ($waitingForAuthorizationStatusReturn === true) {
                ReturnData::getInstance()->set(["authorized" => false], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
                return;
            }
            ReturnData::getInstance()->set("Authorization error User not found", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
            return;
        }
        $_SESSION['auth_cookie'] = $idCookies;
        if ($waitingForAuthorizationStatusReturn === true) {
            ReturnData::getInstance()->set(["authorized" => true], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        }
    }

    public function getPassword(array $data)
    {
        $this->captcha($data);
        $token = $data["email"] ?? "";
        if ($token === "") {
            ReturnData::getInstance()->set("message.authorization.invalidLogin", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }
        $data["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "users", "columns" => "chatCod"]
            ],
            "tables" => ["users"],
            "where" => "WHERE users.token IN ('" . $token . "') AND users.role = 2",
        ];
        $records = DataProvider::getInstance()->read($data);
        if (!is_array($records) || count($records) === 0) {
            ReturnData::getInstance()->set("message.wayForPay.invalidLogin", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }

        // If one-time code delivery is disabled in .env (AUTH_2FA_ENABLED=false), set static code 1111
        if (!filter_var($_ENV["AUTH_2FA_ENABLED"] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $dataTemp = [
                "table" => "users",
                "index" => "idx_users_user",
                "queryData" => ["0" => ["changed" => 5, "token" => $token, "pass" => 1111]],
            ];
            DataProvider::getInstance()->save($dataTemp);
            setcookie($this->cookieName, "", -1, "/");
            return;
        }


        $chatCod = json_decode($records[0]["chatCod"], true);
        $isSent = false;
        $email = $chatCod["email"] ?? "";
        $pass = mt_rand(1000, 9999);
        $gf = new GeneralFunctions();
        if ($email !== "") {
            $dataTemp = ["email" => $email, "emailSubject" => "Your temporary password", "message" => $pass, "emailSuccessfullySent" => "message.system.EmailSuccessfullySent"];
            $gf->sendEmailMessage($dataTemp);
        }
        $dataTemp = [
            "table" => "users",
            "index" => "idx_users_user",
            "queryData" => ["0" => ["changed" => 5, "token" => $token, "pass" => $pass]],
        ];
        DataProvider::getInstance()->save($dataTemp);
        $telegramCod = $chatCod["telegram"] ?? "";
        if ($telegramCod !== "") {
            $dataTemp = ["message" => $pass, "telegramCod" => $telegramCod];
            $gf->sendTelegramMessage($dataTemp);
        }
        $viberCod = $chatCod["viber"] ?? "";
        if ($viberCod !== "") {
            $dataTemp = ["message" => $pass, "viberCod" => $viberCod];
            $gf->sendViberMessage($dataTemp);
        }
        setcookie($this->cookieName, "", -1, "/");
    }

    public function setAuthorization(array $data)
    {
        $email = $data["email"] ?? "";
        $code = (int) ($data["code"] ?? 0);
        if ($code === 0 || $email === "") {
            ReturnData::getInstance()->set("message.authorization.dynamicPasswordNotSpecified", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }
        // Check if login and password exist in the database
        $data["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "users", "columns" => "id,token"]
            ],
            "tables" => ["users"],
            "where" => "WHERE users.token IN ('" . $email . "') AND users.pass IN (" . $code . ")",
        ];
        $records = DataProvider::getInstance()->read($data);
        if (!is_array($records) || count($records) === 0) {
            ReturnData::getInstance()->set("message.authorization.invalidPassword", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }
        $userId = $records[0]["id"];
        // Reset the authorization code
        $saveUser = [
            "table" => "users",
            "index" => "idx_users_user",
            "queryData" => ["0" => ["changed" => 5, "token" => $email, "pass" => 0]],
        ];
        DataProvider::getInstance()->save($saveUser);
        $idCookies = bin2hex(random_bytes(32));
        $dt = time() + (10 * 24 * 60 * 60);
        $cookieSettings = [
            "expires" => $dt,
            "path" => "/",
            "domain" => "",
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ];
        setcookie($this->cookieName, $idCookies, $cookieSettings);
        $db_expires = date("Y-m-d H:i:s", $dt);
        $saveCookie = [
            "table" => "cookies",
            "index" => "idx_cookies_idCookies",
            "queryData" => ["0" => ["changed" => 5, "userId" => $userId, "idCookies" => $idCookies, "expires" => $db_expires]]
        ];
        DataProvider::getInstance()->save($saveCookie);
    }

    public function userOutput()
    {
        $idCookies = filter_input(INPUT_COOKIE, "id");
        if (!empty($idCookies)) {
            $dataTemp = [];
            $dataTemp = [
                "table" => "cookies",
                "queryData" => [0 => ["changed" => 4]],
                "where" => "WHERE cookies.idCookies IN ('" . $idCookies . "')"
            ];
            DataProvider::getInstance()->save($dataTemp);
        }
        setcookie($this->cookieName, "", [
            "expires" => time() - 3600,
            "path" => "/",
            "samesite" => "Strict"
        ]);
        ReturnData::getInstance()->set(["authorized" => false], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function captcha(array $data)
    {
        if (!filter_var($_ENV["RECAPTCHA_ENABLED"] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $recaptchaKey = $_ENV["RECAPTCHA_SITE_KEY"];
        $token = $data["captcha"];
        $GOOGLE_APPLICATION_CREDENTIALS = $_ENV["GOOGLE_APPLICATION_CREDENTIALS"];
        $project = $_ENV["GOOGLE_PROJECT_ID"];
        $action = "LOGIN";
        $client = new RecaptchaEnterpriseServiceClient([
            'credentials' => $GOOGLE_APPLICATION_CREDENTIALS
        ]);
        $projectName = $client->projectName($project);
        // Set the properties of the event to be tracked.
        $event = (new Event())
            ->setSiteKey($recaptchaKey)
            ->setToken($token);

        // Build the assessment request.
        $assessment = (new Assessment())
            ->setEvent($event);

        $request = (new CreateAssessmentRequest())
            ->setParent($projectName)
            ->setAssessment($assessment);


        // FIXME: Temporary workaround for a bug in Google Cloud PHP SDK (Recaptcha Enterprise).
        // The library triggers a "hashed_account_id is deprecated" error internally during the 
        // createAssessment() call (see line 372 in Event.php), even if the field is not used.
        // This causes a crash in environments with strict error handling (like OpenServer).
        // We temporarily disable the custom error handler to bypass this internal deprecation notice.
        $old_handler = set_error_handler(null);
        $response = $client->createAssessment($request);
        set_error_handler($old_handler);


        // Check if the token is valid.
        if ($response->getTokenProperties()->getValid() == false) {
            //printf("The CreateAssessment() call failed because the token was invalid for the following reason: ");
            //printf(InvalidReason::name($response->getTokenProperties()->getInvalidReason()));
            $mes = "The CreateAssessment() call failed because the token was invalid for the following reason: " .
                InvalidReason::name($response->getTokenProperties()->getInvalidReason());
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }

        // Check if the expected action was executed.
        if ($response->getTokenProperties()->getAction() == $action) {
            // Get the risk score and the reason(s).
            // For more information on interpreting the assessment, see:
            // https://cloud.google.com/recaptcha-enterprise/docs/interpret-assessment
            //printf("The score for the protection action is:");
            //printf($response->getRiskAnalysis()->getScore());            

            $score = $response->getRiskAnalysis()->getScore();
            if ($score < 0.5) {
                ReturnData::getInstance()->set("reCAPTCHA check failed: low score ($score)", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
            }
            return;
        } else {
            //printf("The action attribute in your reCAPTCHA tag does not match the action you are expecting to score");
            $mes = "The action attribute in your reCAPTCHA tag does not match the action you are expecting to score";
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
    }
}

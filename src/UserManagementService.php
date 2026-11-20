<?php

//Savvin Ruslan 2024

namespace App;


use App\Templates\TemplatesContactConfirmation;

class UserManagementService
{
    private string $cookieName = "";

    public function __construct()
    {
        $this->cookieName = "guestCookie";
    }

    /**
     * Renders and serves the input and subscription confirmation page to the client.
     * Displays the interface where users can enter their contact details, 
     * manage their subscription preferences, and trigger the confirmation process.
     */
    public function getAuthorizationPage(array $data)
    {
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "generalSettings", "columns" => "testDomain,domain"],
            ],
            "tables" => ["generalSettings"]
        ];
        $recordsGeneralSettings = DataProvider::getInstance()->read($dataTemp);
        if (DataProvider::getInstance()->environment == "production") {
            $domain = $recordsGeneralSettings[0]["domain"];
        } else {
            $domain = $recordsGeneralSettings[0]["testDomain"];
        }
        $languageId = $data["language"] ?? 1;
        $localization = new Localization($languageId);
        $mes = $localization->translate(call_user_func([TemplatesContactConfirmation::class, "formaVerification"], []));
        ReturnData::getInstance()->setHeader("HTTP/1.1 200 OK", "text/html");
        ReturnData::getInstance()->set($mes, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function getUserInviteLink(array $data)
    {
        $userId = isset($data["queryData"][0]["id"]) ? (int)$data["queryData"][0]["id"] : null;
        $languageId = 1;
        if ($userId !== null) {
            $dataTemp = [];
            $dataTemp["queryParams"] = [
                "select" => [
                    "1" => ["action" => "+", "prefix" => "users", "columns" => "languageId"],
                ],
                "tables" => ["users"],
                "where" => "WHERE users.id IN ({$userId})"
            ];
            $records = DataProvider::getInstance()->read($dataTemp);
            if (!empty($records)) {
                $languageId = $records[0]["languageId"] ?? 1;
            }
        }
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "generalSettings", "columns" => "testDomain,domain"],
            ],
            "tables" => ["generalSettings"]
        ];
        $recordsGeneralSettings = DataProvider::getInstance()->read($dataTemp);
        if (DataProvider::getInstance()->environment == "production") {
            $domain = $recordsGeneralSettings[0]["domain"];
        } else {
            $domain = $recordsGeneralSettings[0]["testDomain"];
        }
        $url = $domain . "/src/GatewayPublicData.php?action=guest/getAuthorizationPage&language=" . urlencode((string)$languageId);
        ReturnData::getInstance()->set($url, ReturnType::MESSAGE, "", ReturnDirection::USER);
    }

    /**
     * Retrieves the list of contact types already verified by the user.
     * Called by the client application to determine which communication channels 
     * (e.g., email, messenger tokens) have been successfully confirmed. 
     */
    public function checkVerificationStatusChatCod(array $data)
    {
        $idCookies = $_COOKIE[$this->cookieName] ?? "";
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "users", "columns" => "chatCod"],
            ],
            "tables" => ["cookies", "users"],
            "join" => "LEFT JOIN users ON cookies.userId = users.id",
            "where" => "WHERE cookies.idCookies IN ('" . $idCookies . "') ",
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        if (empty($records)) {
            ReturnData::getInstance()->set(["chatCod" => ""], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        } else {
            $chatCod = $records[0]["chatCod"];
            ReturnData::getInstance()->set(["chatCod" => $chatCod], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        }
    }

    public function checkVerificationStatusRole(array $data)
    {
        $idCookies = $_COOKIE[$this->cookieName] ?? "";
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "users", "columns" => "role"],
            ],
            "tables" => ["cookies", "users"],
            "join" => "LEFT JOIN users ON cookies.userId = users.id",
            "where" => "WHERE cookies.idCookies IN ('" . $idCookies . "') AND users.role = 3",
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        if (empty($records)) {
            ReturnData::getInstance()->set(["is_confirmed" => true], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        } else {
            ReturnData::getInstance()->set(["is_confirmed" => false], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        }
    }

    public function getCaptchaSitekey(array $data)
    {
        $dataTemp = [
            "0" => ["captchaSitekey" => $_ENV["RECAPTCHA_SITE_KEY"]]
        ];
        ReturnData::getInstance()->set($dataTemp, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    /**
     *Retrieves the required initial session data and user token needed for the first-time loading. 
     */
    public function createNewUser(array $data)
    {
        $token = random_int(1000000000, 9999999999);
        $languageId = $data["queryParams"]["languageId"] ?? 1;
        DataProvider::getInstance()->save([
            "table" => "users",
            "queryData" => [0 => ["changed" => 1, "role" => 3, "languageId" => $languageId, "token" => $token]]
        ]);
        $userId = DataProvider::getInstance()->execResult["insertId"];
        $idCookies = "";
        if (empty($data["queryParams"]["iSchannelCallback"])) {
            $idCookies = bin2hex(random_bytes(32));
            $dt = time() + 31536000;
            setcookie($this->cookieName, $idCookies, [
                "expires" => $dt,
                "domain" => "",
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Lax"
            ]);
            DataProvider::getInstance()->save([
                "table" => "cookies",
                "index" => "idx_cookies_idCookies",
                "queryData" => [0 => ["changed" => 5, "userId" => $userId, "idCookies" => $idCookies, "expires" => date("Y-m-d H:i:s", $dt)]]
            ]);
        }
        return ["idCookies" => $idCookies, "userId" => $userId, "token" => $token];
    }

    /**
     * Sends a verification email with a unique confirmation link to the user.
     * The setGuestEmail(array $data) procedure for the email verification process will be final
     *
     */
    public function sendEmailVerification(array $data)
    {
        (new Authorization())->captcha($data);
        $email = $data["email"];
        $languageId = $data["languageId"] ?? "1";
        if (empty($email)) {
            $localization = new Localization($languageId);
            $keyMes = "userManagementService.message.emailNotProvided";
            $mesTemp = $localization->translate("<data-translate='" . $keyMes . "'></>", "true");
            $mes = $mesTemp[$keyMes]["textValue"] ?? "Email is not provided.";
            ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "", ReturnDirection::USER);
            return;
        }
        $token = $this->ensureUserSession($data);
        $dataTemp = [];
        $dataTemp = ["contact" => $email, "type" => "email", "token" => $token];
        $this->setContactValue($dataTemp);
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "generalSettings", "columns" => "testDomain, domain"],
            ],
            "tables" => ["generalSettings"],
        ];
        $recordsGeneralSettings = DataProvider::getInstance()->read($dataTemp);
        if (DataProvider::getInstance()->environment == "production") {
            $domain = $recordsGeneralSettings[0]["domain"];
        } else {
            $domain = $recordsGeneralSettings[0]["testDomain"];
        }
        $url = $domain . "/src/GatewayPublicData.php?action=guest/setUserAuthenticated&token=" . urlencode($token);
        $dataTemp = [];
        $dataTemp = ["url" => $url, "domain" => $domain];
        $localization = new Localization($languageId);
        $message = $localization->translate(call_user_func([TemplatesContactConfirmation::class, "emailConfirmationRequest"], $dataTemp));
        $emailSuccessfullySent = $localization->translate("<data-translate='message.system.EmailSuccessfullySent'></>", "true");
        $cleanEmailSentText = str_replace("%s", "", $emailSuccessfullySent["message.system.EmailSuccessfullySent"]["textValue"]);
        $gf = new GeneralFunctions();
        $dataTemp = [
            "email" => $email,
            "emailSubject" => "Email confirmation",
            "message" => $message,
            "emailSuccessfullySent" => $cleanEmailSentText,
            "languageId" => $languageId
        ];
        $gf->sendEmailMessage($dataTemp);
    }

    /**
     * Validates the user's cookie or registers a new user if the cookie is invalid.
     */
    public function ensureUserSession(array $data)
    {
        $languageId = $data["languageId"] ?? "1";
        $idCookies = $_COOKIE[$this->cookieName] ?? "";
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "users", "columns" => "id AS userId, token, languageId"]
            ],
            "tables" => ["cookies", "users"],
            "join" => "LEFT JOIN users ON cookies.userId = users.id",
            "where" => "WHERE cookies.idCookies IN ('" . $idCookies . "')"
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        if (empty($records)) {
            ["idCookies" => $idCookies, "userId" => $userId, "token" => $token] = $this->createNewUser($dataTemp);
            $_COOKIE[$this->cookieName] = $idCookies;
            ReturnData::getInstance()->set($token, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
            return $token;
        } else {
            ReturnData::getInstance()->set($records[0]["token"], ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
            return $records[0]["token"];
        }
    }

    public function setContactValue(array $data)
    {
        $token = $data["token"];
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "users", "columns" => "chatCod"],
            ],
            "tables" => ["users"],
            "where" => "WHERE users.token IN ('" . $token . "') ",
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        $chatCod = $records[0]["chatCod"];
        $contact = $data["contact"];
        $type = $data["type"];
        $chatCodArray = json_decode($chatCod, true) ?? [];
        $chatCodArray["temp." . $type] = $contact;
        $dataTemp = [];
        $dataTemp = [
            "table" => "users",
            "index" => "idx_users_user",
            "queryData" => ["0" => ["changed" => 5, "token" => $token, "chatCod" => json_encode($chatCodArray)]]
        ];
        DataProvider::getInstance()->save($dataTemp);
    }

    /**
     * Handles confirmation from an email or messenger action. 
     * Sets the user's status to permanent and upgrades the respective 
     * email address or messenger token to permanent mode.
     *
     */
    public function setUserAuthenticated(array $data, string $type)
    {
        $languageId = 1;
        if ($type === "email") {
            $token = $data["token"];
            $dataTemp = [];
            $dataTemp["queryParams"] = [
                "select" => [
                    0 => ["action" => "+", "prefix" => "users", "columns" => "chatCod,role,languageId,token"],
                ],
                "tables" => ["users"],
                "where" => "WHERE users.token IN ('" . $token . "') ",
            ];
            $records = DataProvider::getInstance()->read($dataTemp);
        } else {
            $key = "temp." . $type;
            $dataTemp = [];
            $dataTemp["value"] = $data["contact"];
            $dataTemp["sql"] = "SELECT `users`.`languageId`,`users`.`token`,`users`.`role`,`users`.`chatCod` FROM `users` WHERE (`chatCod`->>'$.\"{$key}\"' COLLATE utf8mb4_unicode_ci) = ?";
            $records = DataProvider::getInstance()->findRowByJsonPair($dataTemp);
        }
        if (empty($records)) {
            return;
        }
        $languageId = $records[0]["languageId"];
        $token = $records[0]["token"];
        $chatCod = $records[0]["chatCod"];
        $chatCodArray = json_decode($chatCod, true);
        if (array_key_exists('channelManager', $data)) {
            $chatCodArray["temp.email"] = $data["contact"];
        }
        $chatCodArray[$type] = $chatCodArray["temp." . $type];
        unset($chatCodArray['temp.' . $type]);
        $dataTemp = [];
        $dataTemp = [
            "table" => "users",
            "index" => "idx_users_user",
            "queryData" => ["0" => ["changed" => 5, "token" => $token, "chatCod" => json_encode($chatCodArray)]]
        ];
        if ((int)$records[0]["role"] === 3) {
            $dataTemp["queryData"][0]["role"] = 1;
        }
        DataProvider::getInstance()->save($dataTemp);

        if ($type === "email" && ! array_key_exists('channelManager', $data)) {
            $dataTemp = [];
            $dataTemp["queryParams"] = [
                "select" => [
                    "0" => ["action" => "+", "prefix" => "languages", "columns" => "languageCode"],
                ],
                "tables" => ["languages"],
                "where" => "WHERE languages.id IN ($languageId)"
            ];
            $records = DataProvider::getInstance()->read($dataTemp);
            $dataTemp = [];
            $dataTemp = ["language" => $records[0]["languageCode"]];
            $localization = new Localization($languageId);
            $mes = $localization->translate(call_user_func([TemplatesContactConfirmation::class, "successConfirmationPage"], $dataTemp));
            ReturnData::getInstance()->setHeader("HTTP/1.1 200 OK", "text/html");
            ReturnData::getInstance()->set($mes, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
        }
    }
}

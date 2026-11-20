<?php
//Savvin Ruslan 2024

namespace App;

use App\Templates\TemplatesTelegram;
use App\Templates\TemplatesViber;

class MessengerBotHandler
{
    private string $telegramBotIp = "";
    private string $viberBotIp = "";
    private string $domain = "";

    public function __construct()
    {
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "generalSettings", "columns" => "domain,testDomain,telegramBotIp,viberBotIp"],
            ],
            "tables" => ["generalSettings"],
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        $this->telegramBotIp = $records[0]["telegramBotIp"]  ?? "";
        $this->viberBotIp = $records[0]["viberBotIp"] ?? "";
        if (DataProvider::getInstance()->environment === "development") {
            $this->domain = $records[0]["testDomain"] ?? "";
        } else {
            $this->domain = $records[0]["domain"] ?? "";
        }
    }

    public function getViberBotIp()
    {
        return $this->viberBotIp;
    }

    public function telegramCallback(array $data)
    {
        if (isset($data["message"])) {
            $messengerCode = $data["message"]["chat"]["id"];
            $text = $data["message"]["text"];
            $languageMapping = $this->getLanguageMapping();
            if (strpos($text, "/start") === 0) {
                $textNew = trim(str_replace("/start", "", $text));
                $parts = explode("_", $textNew);
                $token = $parts[0];
                if (empty($token)) {
                    return;
                }
                $dataTemp = ["token" => "$token", "contact" => $messengerCode, "type" => "telegram"];
                (new UserManagementService())->setContactValue($dataTemp);
                $templateData = call_user_func([TemplatesTelegram::class, "sendLanguageKeyboard"], $dataTemp);
                $this->callTelegramApi("sendMessage", $templateData, "Message sent successfully");
            } else if (isset($languageMapping[$text])) {
                $languageId = $languageMapping[$text];
                $dataTemp = ["languageId" => $languageId, "contact" => $messengerCode, "type" => "telegram"];
                $this->setLanguage($dataTemp);
                if ($this->shouldRenderButton($messengerCode, "telegram")) {
                    $templateData = call_user_func([TemplatesTelegram::class, "sendSubscribeKeyboard"], $dataTemp);
                    $this->callTelegramApi("sendMessage", $templateData, "Connect button sent");
                } else {
                    $this->messageSuccessConnected($dataTemp);
                }
            } else {
                $dataTemp = ["contact" => $messengerCode, "type" => "telegram"];
                $templateData = call_user_func([TemplatesTelegram::class, "sendLanguageKeyboard"], $dataTemp);
                $this->callTelegramApi("sendMessage", $templateData, "Message sent successfully");
            }
        } else if (isset($data["callback_query"])) {
            $callbackData = $data["callback_query"]["data"];
            $messengerCode = (string)$data["callback_query"]["message"]["chat"]["id"];
            $callbackId = $data["callback_query"]["id"];
            if (strpos($callbackData, "sub_connect") === 0) {
                $this->callTelegramApi("answerCallbackQuery", ["callback_query_id" => $callbackId], "Callback answered");
                $dataTemp = ["contact" => $messengerCode, "type" => "telegram"];
                (new UserManagementService())->setUserAuthenticated($dataTemp, "telegram");
                $this->messageSuccessConnected($dataTemp);
            }
        } else if (isset($data["my_chat_member"])) {
            $status = $data["my_chat_member"]["new_chat_member"]["status"] ?? "";
            if ($status === "kicked") {
                $messengerCode = (string)$data["my_chat_member"]["chat"]["id"];
                $this->unsubscribeUser($messengerCode, "telegram");
            }
        }
    }

    public function viberCallback(array $data)
    {
        $event = $data["event"] ?? "";
        if ($event === "message") {
            $messengerCode = $data["sender"]["id"];
            $text = $data["message"]["text"];
            $languageMapping = $this->getLanguageMapping();
            if (isset($languageMapping[$text])) {
                $languageId = $languageMapping[$text];
                $dataTemp = ["languageId" => $languageId, "contact" => $messengerCode, "type" => "viber"];
                $this->setLanguage($dataTemp);
                if ($this->shouldRenderButton($messengerCode, "viber")) {
                    $templateData = call_user_func([TemplatesViber::class, "sendSubscribeKeyboard"], $dataTemp);
                    $this->callViberApi("send_message", $templateData, "Connect button sent");
                } else {
                    $this->messageSuccessConnected($dataTemp);
                }
            } else if (strpos($text, "sub_connect") === 0) {
                $dataTemp = ["contact" => $messengerCode, "type" => "viber"];
                (new UserManagementService())->setUserAuthenticated($dataTemp, "viber");
                $this->messageSuccessConnected($dataTemp);
            } else {
                $dataTemp = ["contact" => $messengerCode, "type" => "viber", "notDisplayMessageSelectLanguage" => true];
                $langKeyboardData = call_user_func([TemplatesViber::class, "sendLanguageKeyboard"], $dataTemp);
                $this->callViberApi("send_message", $langKeyboardData, "Language keyboard returned");
            }
        } else if ($event === "conversation_started") {
            $messengerCode = $data["user"]["id"];
            $context = $data["context"];
            $parts = explode("_", $context);
            $token = $parts[0];
            $dataTemp = ["token" => "$token", "contact" => $messengerCode, "type" => "viber", "notDisplayMessageSelectLanguage" => true];
            (new UserManagementService())->setContactValue($dataTemp);
            $templateData = call_user_func([TemplatesViber::class, "sendLanguageKeyboard"], $dataTemp);
            $this->callViberApi("send_message", $templateData, "Connect button sent");
        } else if ($event === "unsubscribed") {
            $messengerCode = (string)$data["user_id"];
            $this->unsubscribeUser($messengerCode, "viber");
        }
    }

    private function messageSuccessConnected(array $data)
    {
        $languageId = $this->getLanguage($data);
        $localization = new Localization($languageId);
        $localTextArr = $localization->translate("<data-translate='message.messengerBot.successConnected'></>", true);
        $mes = $localTextArr['message.messengerBot.successConnected']['textValue'] ?? "Thank you, you are connected!";
        $messengerCode = $data["contact"];
        $type = $data["type"];
        if ($type === "telegram") {
            $postData = [
                "chat_id" => $messengerCode,
                "text" => $mes
            ];
            $this->callTelegramApi("sendMessage", $postData, "Success message sent");
        } else if ($type === "viber") {
            $postData = [
                "receiver" => $messengerCode,
                "type"     => "text",
                "text"     => $mes
            ];
            $this->callViberApi("send_message", $postData, "Success message sent");
            $data["notDisplayMessageSelectLanguage"] = true;
            $langKeyboardData = call_user_func([TemplatesViber::class, "sendLanguageKeyboard"], $data);
            $this->callViberApi("send_message", $langKeyboardData, "Language keyboard returned");
        }
    }

    private function getLanguage(array $data)
    {
        $key = $data["type"];
        $dataTemp = [];
        $dataTemp["value"] = $data["contact"];
        $dataTemp["sql"] = "SELECT `users`.`languageId` FROM `users` WHERE (`chatCod`->>'$.{$key}' COLLATE utf8mb4_unicode_ci) = ?";
        $records = DataProvider::getInstance()->findRowByJsonPair($dataTemp);
        if (empty($records)) {
            return 1;
        }
        return $records[0]["languageId"];
    }

    private function getLanguageMapping()
    {
        $dataTemp["queryParams"]["tables"][0] = "languages";
        (new DataProcessor)->referenceNotAuthorizationFetch($dataTemp);
        $recordsLanguages = ReturnData::getInstance()->get();
        $languageMapping = [];
        $flags = ["3" => "🇺🇦", "1" => "🇬🇧"];
        foreach ($recordsLanguages["data"] as $id => $languageInfo) {
            $name = $languageInfo["languageName"];
            $displayText = isset($flags[$id]) ? $flags[$id] . " " . $name : $name;
            $languageMapping[$displayText] = (int)$id;
        }
        return $languageMapping;
    }

    private function setLanguage(array $data)
    {
        $languageId = $data["languageId"];
        $dataTemp = [];
        $key = $data["type"];
        $dataTemp["value"] = $data["contact"];
        $dataTemp["sql"] = "SELECT `users`.`id`, 3 AS changed, {$languageId} AS languageId  FROM `users` WHERE (`chatCod`->>'$.{$key}' COLLATE utf8mb4_unicode_ci) = ?";
        $records = DataProvider::getInstance()->findRowByJsonPair($dataTemp);
        if (empty($records)) {
            return;
        }
        $dataTemp = [];
        $dataTemp = [
            "table" => "users",
            "queryData" => $records
        ];
        DataProvider::getInstance()->save($dataTemp);
    }

    private function shouldRenderButton(string $messengerCode, string $type)
    {
        $dataTemp = [];
        $dataTemp["value"] = $messengerCode;
        $key = "temp." . $type;
        $dataTemp["sql"] = "SELECT `users`.`id` FROM `users` WHERE (`chatCod`->>'$.\"{$key}\"') = ? COLLATE utf8mb4_unicode_ci";
        $records = DataProvider::getInstance()->findRowByJsonPair($dataTemp);
        if (!empty($records)) {
            return true;
        }
        return false;
    }

    private function unsubscribeUser(string $messengerCode, string $type)
    {
        $dataTemp = [];
        $dataTemp["value"] = $messengerCode;
        $key = $type;
        $dataTemp["sql"] = "SELECT `users`.`id`,`users`.`chatCod` FROM `users` WHERE (`chatCod`->>'$.{$key}' COLLATE utf8mb4_unicode_ci) = ?";
        $records = DataProvider::getInstance()->findRowByJsonPair($dataTemp);
        if (!empty($records)) {
            foreach ($records as $key => $row) {
                $chatData = json_decode($row["chatCod"], true);
                $chatData[$type] = "";
                $records[$key]["changed"] = 3;
                $records[$key]["chatCod"] = json_encode($chatData);
            }
            $dataTemp = [];
            $dataTemp = [
                "table" => "users",
                "queryData" => $records
            ];
            DataProvider::getInstance()->save($dataTemp);
        }
    }

    public function telegramSetCallbackUrl(array $data)
    {
        $postData = [
            "url"          => "{$this->domain}/src/GatewayPublicData.php",
            "secret_token" => "7fcf93e8da22c548bc9d2b1a13b40fd6"
        ];
        $this->callTelegramApi("setWebhook", $postData, "message.system.webhookSetSuccessfully");
    }

    public function callTelegramApi(string $method, array $postData, string $successMessage)
    {
        $tgApiUrl = "https://api.telegram.org/bot{$this->telegramBotIp}/{$method}";
        $ch = curl_init($tgApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        if (empty($response)) {
            ReturnData::getInstance()->set(["message.system.CurlError", curl_error($ch)], ReturnType::MESSAGE, "❗ ", ReturnDirection::USER);
            return;
        }
        $result = json_decode($response, true);
        if (isset($result["ok"]) && $result["ok"] === true) {
            ReturnData::getInstance()->set($successMessage, ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
        } else {
            $mes = $result["description"] ?? "Unknown Telegram Error";
            ReturnData::getInstance()->set(["message.messengerBot.unknownTelegramError", $mes], ReturnType::MESSAGE, "❗ ", ReturnDirection::USER);
        }
        return $result;
    }

    public function viberSetCallbackUrl(array $data)
    {
        $ch = curl_init("https://chatapi.viber.com/pa/get_account_info");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Viber-Auth-Token: " . $this->viberBotIp, "Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        if ($response["status"] === 0) {
            $mes = "";
            foreach ($response as $k => $v) {
                if (!is_array($v)) {
                    $mes = $mes . $k . " " . $v . "\n";
                }
            }
            ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "✅ ", ReturnDirection::LOG);
        } else {
            $mes = "Invalid Viber Bot ID (Auth Token)";
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
        $webhookUrl = "{$this->domain}/src/GatewayPublicData.php";
        $webhookData = [
            "url" => $webhookUrl,
            "event_types" => ["delivered", "seen", "failed", "subscribed", "unsubscribed", "conversation_started", "message"]
        ];
        $ch2 = curl_init("https://chatapi.viber.com/pa/set_webhook");
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($webhookData));
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ["X-Viber-Auth-Token: " . $this->viberBotIp, "Content-Type: application/json"]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch2), true);
        if (isset($result["status"]) && $result["status"] === 0) {
            ReturnData::getInstance()->set("message.system.webhookSetSuccessfully", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
        } else {
            $mes = $result["status_message"] ?? "Unknown Viber Error";
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
    }

    public function callViberApi(string $method, array $postData, string $successMessage): void
    {
        $url = "https://chatapi.viber.com/pa/" . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Viber-Auth-Token: ' . $this->viberBotIp
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = json_decode($response, true);
        if (isset($result["status"]) && $result["status"] === 0) {
            ReturnData::getInstance()->set($successMessage, ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
        } else {
            $mes = $result["status_message"] ?? "Unknown Viber Error";
            ReturnData::getInstance()->set(["message.messengerBot.unknownMessengerError", $mes], ReturnType::MESSAGE, "❗ ", ReturnDirection::USER);
        }
    }
}

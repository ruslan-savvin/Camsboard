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

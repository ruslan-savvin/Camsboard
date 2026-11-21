<?php

//Savvin Ruslan 2024
namespace App;

use PHPMailer\PHPMailer\PHPMailer;

class GeneralFunctions
{
    private function initMailer()
    {
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "generalSettings", "columns" => "mailHost,mailUser,mailPass,domain"],
            ],
            "tables" => ["generalSettings"],
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = $records[0]["mailHost"];
        $mail->SMTPAuth = true;
        $mail->Username = $records[0]["mailUser"];
        $mail->Password = $records[0]["mailPass"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 465;
        $mail->CharSet = "UTF-8";
        $mail->setFrom($records[0]["mailUser"], $records[0]["domain"]);
        $mail->isHTML(true);
        return $mail;
    }

    public function sendEmailMessage(array $data)
    {
        $languageId = $data["languageId"] ?? "1";
        $email = $data["email"];
        $attachment = $data["attachment"] ?? null;
        $mail = $this->initMailer();
        $mail->addAddress($email);
        $mail->Subject = $data["emailSubject"];
        $mail->Body = $data["message"];
        if (!empty($attachment)) {
            $mail->addStringAttachment($attachment["content"], $attachment["name"], $attachment["encoding"], $attachment["type"]);
        }
        if (!empty($data["image"])) {
            $mail->addEmbeddedImage($data["image"]["imagePath"], $data["image"]["cid"], $data["image"]["name"]);
        }
        if ($mail->send()) {
            $mes = $data["emailSuccessfullySent"];
            ReturnData::getInstance()->set([$mes, "\n", $email], ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
            $isSent = true;
        } else {
            if ($mail->ErrorInfo === "You must provide at least one recipient email address.") {
                $localization = new Localization($languageId);
                $keyMes = "generalFunctions.message.emailNotProvided";
                $mesTemp = $localization->translate("<data-translate='" . $keyMes . "'></>", "true");
                $mes = $mesTemp[$keyMes]["textValue"] ?? "You must provide at least one recipient email address.";
                ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "⚠️ ", ReturnDirection::USER);
            } else {
                ReturnData::getInstance()->set($mail->ErrorInfo, ReturnType::MESSAGE, "⚠️ ", ReturnDirection::LOG);
            }
        }
    }

    public function sendTelegramMessage(array $data)
    {
        $telegramCod = $data["telegramCod"];
        $attachment = $data["attachment"] ?? null;
        $photoPath = $data["photo"] ?? null;
        $mb = new MessengerBotHandler();
        $body = $data["message"];

        if (!empty($photoPath)) {
            $postData = [
                "chat_id" => $telegramCod,
                "photo" => new \CURLFile($photoPath),
                "caption" => $body,
                "parse_mode" => "HTML"
            ];
            $mb->callTelegramApi("sendPhoto", $postData, "message.system.TelegramFileSuccessfullySent");
        } elseif (!empty($attachment)) {

            $document = new \CURLStringFile($attachment["content"], $attachment["name"], $attachment["type"]);
            $postData = [
                "chat_id" => $telegramCod,
                "document" => $document,
                "caption" => $body,
                "parse_mode" => "HTML"
            ];
            $mb->callTelegramApi("sendDocument", $postData, "message.system.TelegramFileSuccessfullySent");
        } else {
            $postData = [
                "chat_id" => $telegramCod,
                "text" => $body,
                "parse_mode" => "HTML"
            ];
            $mb->callTelegramApi("sendMessage", $postData, "message.system.TelegramSuccessfullySent");
        }
    }

    public function sendViberMessage(array $data)
    {
        $viberCod = $data["viberCod"];
        $attachment = $data["attachment"] ?? null;
        $mb = new MessengerBotHandler();
        $body = $data["message"];
        $basePostData = [
            "receiver" => $viberCod,
            "sender" => ["name" => "GreatApart"]
        ];
        $textData = array_merge($basePostData, [
            "type" => "text",
            "text" => $body
        ]);
        $mb->callViberApi("send_message", $textData, "message.system.ViberSuccessfullySent");
        $isSent = true;
        if (!empty($attachment)) {
            $dataTemp = [];
            $dataTemp["queryParams"] = [
                "select" => [
                    "0" => ["action" => "+", "prefix" => "generalSettings", "columns" => "domain,testDomain"],
                ],
                "tables" => ["generalSettings"],
            ];
            $recordsGeneralSettings = DataProvider::getInstance()->read($dataTemp);
            $uniqueName = uniqid() . "_" . (string)$attachment["name"];
            $publicPath = dirname(__DIR__, 1) . "/public/tempViber/" . $uniqueName;
            if (DataProvider::getInstance()->environment == "production") {
                $domain = $recordsGeneralSettings[0]["domain"];
            } else {
                $domain = $recordsGeneralSettings[0]["testDomain"];
            }
            $fileUrl = $domain . "/public/tempViber/" . $uniqueName;
            file_put_contents($publicPath, $attachment["content"]);
            $fileData = array_merge($basePostData, [
                "type" => "file",
                "media" => $fileUrl,
                "size" => strlen((string)$attachment["content"]),
                "file_name" => (string)$attachment["name"]
            ]);
            $mb->callViberApi("send_message", $fileData, "message.system.ViberFileSuccessfullySent");
        }
    }

    public function sendMessage(array $data, array $attachment = []): void
    {
        $userId = $data["userId"];
        $dataTemp = [];
        $dataTemp["queryParams"] = [
            "select" => [
                "1" => ["action" => "+", "prefix" => "users", "columns" => "chatCod"],
                "2" => ["action" => "+", "prefix" => "generalSettings", "columns" => "telegramBotIp,viberBotIp,viberSendName"],
            ],
            "tables" => ["users", "generalSettings"],
            "join" => "LEFT JOIN generalSettings ON  1 = 1",
            "where" => "WHERE users.id IN ({$userId})"
        ];
        $records = DataProvider::getInstance()->read($dataTemp);
        $chatCod = json_decode($records[0]["chatCod"], true);
        $isSent = false;
        $email = $chatCod["email"] ?? "";
        if ($email !== "") {
            $dataTemp = [
                "email" => $email,
                "emailSubject" => $data["emailSubject"],
                "message" => $data["messageEmail"],
                "attachment" => $attachment,
                "image" => $data["image"] ?? "",
                "emailSuccessfullySent" => "message.system.EmailSuccessfullySent"
            ];
            $this->sendEmailMessage($dataTemp);
            $isSent = true;
        }
        $telegramBotIp = $records[0]["telegramBotIp"];
        $viberBotIp = $records[0]["viberBotIp"];
        if (!$telegramBotIp && !$viberBotIp) {
            ReturnData::getInstance()->set("message.system.NoMessengersAvailableToSendMessage", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }
        $telegramCod = $chatCod["telegram"] ?? "";
        if ($telegramCod !== "") {
            $dataTemp = ["telegramBotIp" => $telegramBotIp, "message" => $data["messageTelegram"], "telegramCod" => $telegramCod, "attachment" => $attachment];
            $this->sendTelegramMessage($dataTemp);
            $isSent = true;
        }
        $viberCod = $chatCod["viber"] ?? "";
        if ($viberCod !== "") {
            $dataTemp = ["message" => $data["messageViber"],  "viberCod" => $viberCod, "attachment" => $attachment];
            $this->sendViberMessage($dataTemp);
            $isSent = true;
        }
        if ($isSent !== true) {
            ReturnData::getInstance()->set("message.system.MessageWasNotSentThroughAnyCommunicationChannel", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
        }
    }
}

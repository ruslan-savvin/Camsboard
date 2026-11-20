<?php

namespace App\Templates;

use App\DataProcessor;
use App\ReturnData;
use App\Localization;

class TemplatesTelegram
{

    public static function sendLanguageKeyboard(array $data): array
    {
        $messengerCode = $data["contact"];
        $dataTemp["queryParams"]["tables"][0] = "languages";
        (new DataProcessor)->referenceNotAuthorizationFetch($dataTemp);
        $recordsLanguages = ReturnData::getInstance()->get();
        $flags = ["3" => "🇺🇦", "1" => "🇬🇧"];
        $buttonsRow = [];
        foreach ($recordsLanguages["data"] as $id => $languageInfo) {
            $name = $languageInfo["languageName"];
            $displayText = isset($flags[$id]) ? $flags[$id] . " " . $name : $name;
            $buttonsRow[] = ["text" => $displayText];
        }
        $keyboard = [
            "keyboard" => [
                $buttonsRow
            ],
            "resize_keyboard" => true,
            "one_time_keyboard" => false
        ];
        return [
            "chat_id" => $messengerCode,
            "text" => "Будь ласка, оберіть вашу мову / Please choose your language:",
            "reply_markup" => json_encode($keyboard)
        ];
    }

    public static function sendSubscribeKeyboard(array $data): array
    {
        $messengerCode = $data["contact"];
        $languageId = $data["languageId"];
        $localization = new Localization($languageId);
        $tl = <<<HTML
            <span data-translate='["templates.templatesTelegram.message.connect_request"]'></span>
        HTML;
        $localTextArr1 = $localization->translate($tl, true);
        $t2 = <<<HTML
            <span data-translate='["templates.templatesTelegram.button.connect"]'></span>
        HTML;
        $localTextArr2 = $localization->translate($t2, true);
        $keyboard = [
            "inline_keyboard" => [[
                ["text" => $localTextArr1[0], "callback_data" => "sub_connect"]
            ]]
        ];
        return [
            "chat_id" => $messengerCode,
            "text" => $localTextArr2[0],
            "reply_markup" => json_encode($keyboard)
        ];
    }
}

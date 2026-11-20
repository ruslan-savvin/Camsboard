<?php

namespace App\Templates;

use App\DataProcessor;
use App\ReturnData;
use App\Localization;

class TemplatesViber
{

    public static function sendLanguageKeyboard(array $data): array
    {
        $messengerCode = $data["contact"];
        $textSelectLanguage = "Будь ласка, оберіть вашу мову / Please choose your language:";
        if ($data["notDisplayMessageSelectLanguage"]) {
            $textSelectLanguage = "\u{200B}";
        }

        $dataTemp["queryParams"]["tables"][0] = "languages";
        (new DataProcessor)->referenceNotAuthorizationFetch($dataTemp);
        $recordsLanguages = ReturnData::getInstance()->get();
        $flags = ["3" => "🇺🇦", "1" => "🇬🇧"];
        $viberButtons = [];
        foreach ($recordsLanguages["data"] as $id => $languageInfo) {
            $name = $languageInfo["languageName"];
            $displayText = isset($flags[$id]) ? $flags[$id] . " " . $name : $name;
            $viberButtons[] = [
                "Columns" => 3,
                "Rows" => 1,
                "ActionType" => "reply",
                "ActionBody" => $displayText,
                "Text" => $displayText
            ];
        }
        return [
            "receiver" => $messengerCode,
            "type" => "text",
            "text" => $textSelectLanguage,
            "keyboard" => [
                "Type" => "keyboard",
                "DefaultHeight" => false,
                "Buttons" => $viberButtons
            ]
        ];
    }

    public static function sendSubscribeKeyboard(array $data): array
    {
        $messengerCode = $data["contact"];
        $languageId = $data["languageId"];
        $localization = new Localization($languageId);
        $tl = <<<HTML
            <span data-translate='["templates.templatesViber.message.connect_request"]'></span>
        HTML;
        $localTextArr1 = $localization->translate($tl, true);
        $t2 = <<<HTML
            <span data-translate='["templates.templatesViber.button.connect"]'></span>
        HTML;
        $localTextArr2 = $localization->translate($t2, true);
        return [
            "receiver" => $messengerCode,
            "type" => "text",
            "text" => $localTextArr1[0],
            "keyboard" => [
                "Type" => "keyboard",
                "DefaultHeight" => false,
                "Buttons" => [[
                    "Columns" => 6,
                    "Rows" => 1,
                    "ActionType" => "reply",
                    "ActionBody" => "sub_connect",
                    "Text" => $localTextArr2[0]
                ]]
            ]
        ];
    }
}

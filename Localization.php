<?php

namespace App;

/**
 *     $pluralMap = [
 *       3 => [
 *           "night" => ["ніч", "ночі", "ночей"],
 *           "guest" => ["гість", "гості", "гостей"],
 *       ],
 *       1 => [
 *           "night" => ["night", "nights"],
 *           "guest" => ["guest", "guests"],
 *       ]
 *   ];
 * 
 * Localization strings (templates with %s and {plural} markers) are stored in the database.
 * Number of persons %s × cost %s %s per night × %s {plural:night}
 * Кількість осіб %s × вартість %s %s за ніч × %s {plural:night}
 * 
 * $localization = new Localization($languageId);
 *$tl = <<<HTML
 *  <span data-translate='["templates.booking.confirmation.aboveBasePrice", {$p1}, {$p2}, "{$p3}", {$p4}]'></span>
 *HTML;
 *$trl = $localization->translate($tl, true);
 *$title = $localization->processAllPluralMarkers($trl[0]);
 */

class Localization
{
    private string $currentLanguage;
    private array $requestData = [];

    protected array $pluralMap = [
        1 => [],
        3 => []
    ];

    public function __construct(string $currentLanguage)
    {
        $this->currentLanguage = $currentLanguage;
    }

    public function translate(string $input, $strippedText = false)
    {
        $keysForQuery = [];
        preg_match_all('/data-translate=\s*([\'"])(.*?)\1/is', $input, $matches);
        if (!empty($matches[2])) {
            foreach ($matches[2] as $rawInput) {
                $trimmed = trim($rawInput);
                if (str_starts_with($trimmed, '[')) {
                    $parsed = json_decode($trimmed, true);
                    if (is_array($parsed)) {
                        foreach ($parsed as $item) {
                            $item = trim((string)$item);
                            if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*\.[a-zA-Z0-9_.]+$/', $item)) {
                                $keysForQuery[] = $item;
                            }
                        }
                    }
                } else {
                    if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*\.[a-zA-Z0-9_.]+$/', $trimmed)) {
                        $keysForQuery[] = $trimmed;
                    }
                }
            }
        }
        $textKeys = implode(",", array_unique($keysForQuery));
        $data["queryParams"] = [
            "select" => [
                0 => ["action" => "+", "prefix" => "localization", "columns" => "textKey,textValue"]
            ],
            "tables" => ["localization"],
            "where" => "WHERE localization.textKey IN ($textKeys) AND localization.languageId IN ($this->currentLanguage)",
            "buildMap" => [["textKey"], ["textValue"]]
        ];
        if (!empty($textKeys)) {
            $records = DataProvider::getInstance()->read($data);

            if (!empty($records)) {
                self::logUsedKeys(array_keys($records));
            }
        } else {
            $records = [];
        }
        $translatedHtml = $this->replaceHtmlTranslations($input, $records);
        if ($strippedText) {
            $cleanLines = [];
            preg_match_all('/>(.*?)</is', $translatedHtml, $textMatches);
            if (!empty($textMatches[1])) {
                foreach ($textMatches[1] as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $cleanLines[] = $line;
                    }
                }
            }
            return $cleanLines;
        }
        return $translatedHtml;
    }

    private function replaceHtmlTranslations(string $input, array $records): string
    {
        return preg_replace_callback(
            '/(<[^>]*data-translate=\s*([\'"])(.*?)\2[^>]*>)(.*?)(<\/([a-zA-Z0-9]+)>)/is',
            function ($matches) use ($records) {
                $tagStart = $matches[1];
                $rawInput = trim($matches[3]);
                $tagEnd = $matches[5];
                $textValue = '';
                if (str_starts_with($rawInput, '[')) {
                    $parsed = json_decode($rawInput, true);
                    if (is_array($parsed)) {
                        $key = trim((string)$parsed[0]);
                        $textTemplate = $records[$key]['textValue'] ?? $key;
                        $placeholderCount = substr_count($textTemplate, '%s');
                        if ($placeholderCount > 0) {
                            $params = array_slice($parsed, 1, $placeholderCount);
                            foreach ($params as $param) {
                                $textTemplate = preg_replace('/%s/', $param, $textTemplate, 1);
                            }
                        }
                        $textValue = $textTemplate;
                    }
                } else {
                    $textValue = $records[$rawInput]['textValue'] ?? $rawInput;
                }
                return $tagStart . $textValue . $tagEnd;
            },
            $input
        );
    }

    public function processAllPluralMarkers(string $text): string
    {
        $languageId = (int)$this->currentLanguage;
        if (preg_match_all('/(\d+)\s*\{plural:([a-zA-Z0-9_]+)\}/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullMatch = $match[0];
                $number = (int)$match[1];
                $mapKey = $match[2];
                if (isset($this->pluralMap[$languageId][$mapKey])) {
                    $forms = $this->pluralMap[$languageId][$mapKey];
                    $count = count($forms);
                    $word = "";
                    if ($languageId === 3 && $count === 3) {
                        $mod10 = $number % 10;
                        $mod100 = $number % 100;
                        if ($mod100 >= 11 && $mod100 <= 14) {
                            $word = $forms[2];
                        } elseif ($mod10 === 1) {
                            $word = $forms[0];
                        } elseif ($mod10 >= 2 && $mod10 <= 4) {
                            $word = $forms[1];
                        } else {
                            $word = $forms[2];
                        }
                    } elseif ($count === 2) {
                        $word = ($number === 1) ? $forms[0] : $forms[1];
                    } else {
                        $word = !empty($forms) ? $forms[0] : '';
                    }
                    if ($word !== '') {
                        $text = str_replace($fullMatch, $number . ' ' . $word, $text);
                    }
                }
            }
        }
        return $text;
    }


    public static function logUsedKeys(array $records): void
    {
        if (empty($records)) {
            return;
        }

        $logFile = dirname(__DIR__) . '/used_localization_keys.json';
        $fp = fopen($logFile, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $existing = $content ? json_decode($content, true) : [];
            if (!is_array($existing)) {
                $existing = [];
            }

            foreach ($records as $key => $translations) {
                if (is_array($translations)) {
                    // Беремо перший доступний текст перекладу з масиву мов
                    $firstTranslation = reset($translations);
                    if ($firstTranslation !== false && $firstTranslation !== '') {
                        $existing[$key] = $firstTranslation;
                    }
                }
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        if ($fp) {
            fclose($fp);
        }
    }
}

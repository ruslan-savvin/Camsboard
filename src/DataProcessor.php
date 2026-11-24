<?php

//Savvin Ruslan 2026

namespace App;

class DataProcessor
{
    public function __construct() {}

    public function referenceNotAuthorizationFetch(array $data)
    {
        $alias = $data["queryParams"]["tables"][0] ?? "";
        switch ($alias) {
            case "localization":
                $data["queryParams"] = [
                    "select" => [
                        0 => ["action" => "+", "prefix" => "localization", "columns" => "textKey,textValue,languageId"]
                    ],
                    "tables" => ["localization"],
                    "where" => $data["queryParams"]["where"],
                    "buildMap" => [["textKey", "languageId"], ["textValue"]]
                ];
                break;
            case "languages":
                $data["queryParams"] = [
                    "select" => [
                        0 => ["action" => "+", "prefix" => "languages", "columns" => "id,languageName, languageCode, flag, adminPanel, isDefault"]
                    ],
                    "tables" => ["languages"],
                    "where" => "WHERE languages.isDelete <> 1",
                    "orderBy" => "ORDER BY languages.isDefault DESC",
                    "buildMap" => [["id"], ["flag", "languageName", "adminPanel", "languageCode", "isDefault"]]
                ];
                break;
            case "generalSettings":
                $records = [
                    0 => [
                        "captchaSitekey" => $_ENV["RECAPTCHA_SITE_KEY"]
                    ]
                ];
                ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
                return;
                break;
            default:
                ReturnData::getInstance()->set("Table `$alias` is not available for public access", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
        $records = DataProvider::getInstance()->read($data);
        unset($data["queryParams"]); // security: do not return the query structure                
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function getEnvironment()
    {
        $records = ["0" => ["environment" => DataProvider::getInstance()->environment]];
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }


    public function getTestResult(array $data)
    {
        $data["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "testers", "columns" => "*"],
            ],
            "tables" => ["testers"],
        ];
        $records = DataProvider::getInstance()->read($data);
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function referenceAuthorizationFetch(array $data)
    {
        $records = DataProvider::getInstance()->read($data);
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function logFetch(array $data): void
    {
        $limit = $data["queryParams"]["limit"];
        $filePath = dirname(__DIR__) . "/log/log.txt";
        $content = @file_get_contents($filePath);
        $logs = json_decode((string) $content, true) ?: [];
        $records = array_slice($logs, 0, str_ireplace("LIMIT", "", $limit));
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    public function referenceAuthorizationSave(array $data)
    {
        DataProvider::getInstance()->save($data);
        ReturnData::getInstance()->set("message.system.save", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
    }

    function loadNameFotos(array $data)
    {
        $patch = dirname(__DIR__) . $data["queryParams"]["patch"];
        $extraParameter = $data["queryParams"]["extraParameter"];
        $records = [];
        if (is_dir($patch)) {
            $files = scandir($patch);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if ($extraParameter === '' || $ext === $extraParameter) {
                        $records[] = [
                            'name' => $file,
                            'path' => $patch . $file
                        ];
                    }
                }
            }
        }
        ReturnData::getInstance()->set($records, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    function loadFoto(array $data)
    {
        $photoPath = $data["queryParams"]["photoPath"];
        if (!file_exists($photoPath)) {
            return;
        }
        $binaryData = file_get_contents($photoPath);
        $mimeType = mime_content_type($photoPath);
        $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($binaryData);
        $dataTemp = [$base64Image];
        ReturnData::getInstance()->set($dataTemp, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }

    function referenceGeneralSettingsSave(array $data)
    {
        DataProvider::getInstance()->save($data);
        if (($_ENV['APP_ENV'] ?? '') === 'development') {
            ReturnData::getInstance()->set("message.system.save", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
            return;
        }

        $data["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "generalSettings", "columns" => "*"],
            ],
            "tables" => ["generalSettings"],
        ];
        $recordsGeneralSettings = DataProvider::getInstance()->read($data);
        $ffmpegService = $recordsGeneralSettings[0]["ffmpegService"];
        $detectService = $recordsGeneralSettings[0]["detectService"];
        $timerService = $recordsGeneralSettings[0]["timerService"];

        $timerCleaner = $recordsGeneralSettings[0]["timerCleaner"];
        $timerCopier = $recordsGeneralSettings[0]["timerCopier"];
        $timerDetector = $recordsGeneralSettings[0]["timerDetector"];
        $timerTester = $recordsGeneralSettings[0]["timerTester"];

        $this->syncffmpegService($ffmpegService);
        $this->syncDetectService($detectService);
        $this->syncTaskService($timerService);
        $this->syncTimer('cleaner.timer', $timerCleaner);
        $this->syncTimer('copier.timer', $timerCopier);
        $this->syncTimer('detector.timer', $timerDetector);
        $this->syncTimer('tester.timer', $timerTester);

        $mes = "If issues occur, check the status of the following services:
                sudo systemctl status camsboard_timer@Tester.service
                sudo systemctl status detector.timer
                sudo systemctl status copier.timer
                sudo systemctl status cleaner.timer
                sudo systemctl status tester.timer

                sudo systemctl status camsboard_detect.service";
        ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "⚠️ ", ReturnDirection::USER);
        ReturnData::getInstance()->set("message.system.save", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
    }

    function referenceDevicesSave(array $data)
    {
        DataProvider::getInstance()->save($data);
        if (($_ENV['APP_ENV'] ?? '') === 'development') {
            ReturnData::getInstance()->set("message.system.save", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
            return;
        }


        $execResult = DataProvider::getInstance()->execResult;
        $recordsNew = [];
        $ids = $execResult["insertId"] ?? [];
        foreach ($data["queryData"] ?? [] as $device) {
            if ((int)($device["changed"] ?? 0) !== 2 && !empty($device["id"])) {
                $ids[] = (int)$device["id"];
            }
        }
        $idsString = implode(',', array_unique(array_filter(array_map('intval', $ids))));
        if ($idsString) {
            $data["queryParams"] = [
                "select" => [
                    "0" => ["action" => "+", "prefix" => "devices", "columns" => "*"],
                ],
                "tables" => ["devices"],
                "where" => "WHERE devices.id IN (" . $idsString . ")",
            ];
            $recordsNew = DataProvider::getInstance()->read($data);
        }

        //isDelete 1 або 0  Зміна статусу існуючего дівайса Зупиняемо існуючу службу / Стартуємо існуючу службу        
        foreach ($recordsNew as $device) {
            $this->syncDeviceService($device);
        }

        //changed = 2  Видален дівайс з бази данних. Видаляемо кофіг / Зупиняемо службу / Деактивуємо службу
        foreach ($data["queryData"] as $device) {
            if ($device["changed"] === 2) {
                $this->deleteDeviceService((int)$device["id"]);
            }
        }

        $this->updateMtxPaths();

        ReturnData::getInstance()->set("message.system.save", ReturnType::MESSAGE, "✅ ", ReturnDirection::USER);
    }

    private function syncDeviceService(array $device): void
    {
        $deviceId = (int)$device['id'];
        $isDelete = (int)$device['isDelete'];

        if ($isDelete === 1) {
            $this->runCmd("sudo /usr/bin/systemctl stop camsboard_device-@{$deviceId}.service");
            $this->runCmd("sudo /usr/bin/systemctl disable camsboard_device-@{$deviceId}.service");
        } else {
            $streamKey = trim((string)$device['streamKey']);
            $onlyKeyFrames = (int)$device['onlyKeyFrames'];
            $fps = (int)$device['fps'];
            $sceneThreshold = (float)$device['sceneThreshold'];

            $inputOpts = $onlyKeyFrames === 1 ? '-skip_frame nokey' : '';

            $lines = [
                "STREAM_KEY={$streamKey}",
                "ARCHIVE_DIR=" . Config::get('STORAGE_PATH') . "temp-{$deviceId}",
                "FFMPEG_INPUT_OPTS=\"{$inputOpts}\"",
                "FPS={$fps}",
                "SCENE_THRESHOLD={$sceneThreshold}",
            ];
            $newContent = implode("\n", $lines) . "\n";

            $configPath = "/etc/default/camsboard_device-{$deviceId}";
            $cmd = "printf '%s' " . escapeshellarg($newContent) . ' | sudo /usr/bin/tee ' . escapeshellarg($configPath) . ' > /dev/null';
            $this->runCmd($cmd);

            $this->runCmd("sudo /usr/bin/systemctl enable camsboard_device-@{$deviceId}.service");
            $this->runCmd("sudo /usr/bin/systemctl restart camsboard_device-@{$deviceId}.service");

            $mes = "If issues occur, check the service status
                sudo systemctl status camsboard_device-@{$deviceId}.service";
            ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "⚠️ ", ReturnDirection::USER);
        }
    }

    private function deleteDeviceService(int $deviceId): void
    {
        $this->runCmd("sudo /usr/bin/systemctl stop camsboard_device-@{$deviceId}.service");
        $this->runCmd("sudo /usr/bin/systemctl disable camsboard_device-@{$deviceId}.service");

        $configFile = "/etc/default/camsboard_device-{$deviceId}";
        if (file_exists($configFile)) {
            $this->runCmd('sudo /usr/bin/rm -f ' . escapeshellarg($configFile));
        }
    }

    private function runCmd(string $command): void
    {
        $output = [];
        $code = 0;
        exec($command . ' 2>&1', $output, $code);

        if ($code !== 0) {
            $errorText = implode(' ', $output);
            ReturnData::getInstance()->set(
                "Command execution error ({$code}) [{$command}]: {$errorText}",
                ReturnType::THROW_EXCEPTION,
                "",
                ReturnDirection::USER
            );
        }
    }

    private function syncDetectService(?string $detectService): void
    {
        if (empty($detectService)) {
            return;
        }

        $servicePath = '/etc/systemd/system/camsboard_detect.service';
        $newContent = trim(str_replace("\r\n", "\n", $detectService)) . "\n";
        $currentContent = file_exists($servicePath) ? file_get_contents($servicePath) : '';

        if ($currentContent !== $newContent) {
            $cmd = "printf '%s' " . escapeshellarg($newContent) . ' | sudo /usr/bin/tee ' . escapeshellarg($servicePath) . ' > /dev/null';
            $this->runCmd($cmd);
            $this->runCmd('sudo /usr/bin/systemctl daemon-reload');
            $this->runCmd('sudo /usr/bin/systemctl enable camsboard_detect.service');
            $this->runCmd('sudo /usr/bin/systemctl restart camsboard_detect.service');
        }
    }

    private function syncffmpegService(?string $ffmpegService): void
    {
        if (empty($ffmpegService)) {
            return;
        }

        $servicePath = '/etc/systemd/system/camsboard_device-@.service';
        $newContent = trim(str_replace("\r\n", "\n", $ffmpegService)) . "\n";
        $currentContent = file_exists($servicePath) ? file_get_contents($servicePath) : '';

        if ($currentContent !== $newContent) {
            $cmd = "printf '%s' " . escapeshellarg($newContent) . ' | sudo /usr/bin/tee ' . escapeshellarg($servicePath) . ' > /dev/null';
            $this->runCmd($cmd);
            $this->runCmd('sudo /usr/bin/systemctl daemon-reload');
            $this->runCmd('sudo /usr/bin/systemctl restart "camsboard_device-@*.service"');
        }
    }

    private function syncTaskService(?string $timerService): void
    {
        if (empty($timerService)) {
            return;
        }

        $servicePath = '/etc/systemd/system/camsboard_timer@.service';
        $newContent = trim(str_replace("\r\n", "\n", $timerService)) . "\n";
        $currentContent = file_exists($servicePath) ? file_get_contents($servicePath) : '';

        if ($currentContent !== $newContent) {
            $cmd = "printf '%s' " . escapeshellarg($newContent) . ' | sudo /usr/bin/tee ' . escapeshellarg($servicePath) . ' > /dev/null';
            $this->runCmd($cmd);
            $this->runCmd('sudo /usr/bin/systemctl daemon-reload');
        }
    }

    private function syncTimer(string $timerName, ?string $timerContent): void
    {
        if (empty($timerContent)) {
            return;
        }

        $servicePath = '/etc/systemd/system/' . $timerName;
        $newContent = trim(str_replace("\r\n", "\n", $timerContent)) . "\n";
        $currentContent = file_exists($servicePath) ? file_get_contents($servicePath) : '';

        if ($currentContent !== $newContent) {
            $cmd = "printf '%s' " . escapeshellarg($newContent) . ' | sudo /usr/bin/tee ' . escapeshellarg($servicePath) . ' > /dev/null';
            $this->runCmd($cmd);
            $this->runCmd('sudo /usr/bin/systemctl daemon-reload');
            $this->runCmd('sudo /usr/bin/systemctl enable ' . escapeshellarg($timerName));
            $this->runCmd('sudo /usr/bin/systemctl restart ' . escapeshellarg($timerName));
        }
    }




    private function updateMtxPaths(): void
    {
        $data["queryParams"] = [
            "select" => [
                "0" => ["action" => "+", "prefix" => "devices", "columns" => "id,streamKey"],
            ],
            "tables" => ["devices"],
            "where" => "WHERE devices.isDelete = 0",
        ];
        $records = DataProvider::getInstance()->read($data);

        $streamNames = [];
        foreach ($records as $device) {
            if (!empty($device["streamKey"])) {
                //$streamNames[] = "live/" . $device["streamKey"] . ":";

                $deviceId = (int)$device["id"];
                $streamKey = trim((string)$device["streamKey"]);

                $streamNames[] = "  {$streamKey}:\n"
                    . "    record: yes\n"
                    . "    recordPath: /var/www/camsboard/storage/records-{$deviceId}/%path/%Y%m%d-%H%M%S\n"
                    . "    recordFormat: fmp4\n"
                    . "    recordSegmentDuration: 1m";
            }
        }

        $configFile = '/opt/mediamtx/mediamtx.yml';

        $content = @file_get_contents($configFile);
        if ($content === false) {
            ReturnData::getInstance()->set("Failed to read mediamtx.yml", ReturnType::THROW_EXCEPTION, "", ReturnDirection::USER);
        }

        $parts = preg_split('/^paths:\s*.*$/m', $content, 2);
        $baseConfig = rtrim(current($parts));

        if (empty($streamNames)) {
            $newPaths = "paths: {}\n";
        } else {
            $newPaths = "paths:\n";
            foreach ($streamNames as $stream) {
                $newPaths .= "{$stream}\n";
            }
        }


        if (@file_put_contents($configFile, $baseConfig . "\n\n" . $newPaths) === false) {
            ReturnData::getInstance()->set("Failed to write mediamtx.yml", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::USER);
        }

        $mes = "If issues occur, check the 
                the paths: section in mediamtx.yml
                sudo nano /opt/mediamtx/mediamtx.yml";
        ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "⚠️ ", ReturnDirection::USER);
    }
}

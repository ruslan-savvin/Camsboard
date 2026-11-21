<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';


try {
    testTake();
} catch (\Throwable $exception) {
    ReturnData::getInstance()->prepare($exception);
}

foreach (ReturnData::getInstance()->get()["message"] ?? [] as $message) {
    $data = is_array($message) ? $message[1] : $message;
    $type = is_array($message) ? $message[0] : "";

    if ($type !== "exeption") {
        (new Log())->set($data);
    }
    echo $data . "<br>";
}

function testTake()
{
    $now = date('Y-m-d H:i:s');
    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "testers", "columns" => "id,errCount,errTarget,testJsonResult"],
        ],
        "tables" => ["testers"],
        "where" => "WHERE DATE_ADD(testers.testDateTime, INTERVAL testers.inter_val SECOND) <= '$now'",
        "orderBy" => "ORDER BY testers.testDateTime ASC",
        "limit" => "LIMIT 1",
    ];
    $records = DataProvider::getInstance()->read($data);
    foreach ($records as $row) {
        $id = $row['id'];

        $data = [
            "table" => "testers",
            "queryData" => []
        ];

        $data["queryData"][] = [
            "id" => $id,
            "changed" => 3,
            "testDateTime" => date('Y-m-d H:i:s'),
            "testResult" => "notDefined",
            "testJsonResult" => json_encode([], JSON_FORCE_OBJECT)
        ];
        DataProvider::getInstance()->save($data);
        if ($id === 1) {
            detection($row);
        } else if ($id === 2) {
            deviceOnline($row);
        } else if ($id === 3) {
            cleaner($row);
        }
    }
}

function detection(array $row)
{
    $yoloSock = new YoloSock;
    $rawResult = $yoloSock->hasPerson(dirname(__DIR__) . "/images/test/man.jpg");
    $dataTemp = json_decode($rawResult, true);
    $isPerson = (!empty($dataTemp['has_person'])) ? "TRUE" : "FALSE";
    $testResult = $isPerson === "TRUE" ? "OK" : "ERR";

    [$errCount, $errTarget] = checkAlert($row, $testResult, "Detection service error");
    $data = [
        "table" => "testers",
        "queryData" => []
    ];
    $data["queryData"][] = [
        "id" => 1,
        "changed" => 3,
        "testDateTime" => date('Y-m-d H:i:s'),
        "testResult" => $testResult,
        "testJsonResult" => json_encode([], JSON_FORCE_OBJECT),
        "errCount" => $errCount,
        "errTarget" => $errTarget,
    ];
    DataProvider::getInstance()->save($data);
    ReturnData::getInstance()->set("[test detection] " . $testResult, ReturnType::MESSAGE, "", ReturnDirection::USER);
}

function deviceOnline(array $row)
{
    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "devices", "columns" => "id,deviceName"],
        ],
        "tables" => ["devices"],
        "where" => "WHERE devices.isDelete = 0",
    ];
    $records = DataProvider::getInstance()->read($data);

    $testJsonResult = [];
    $errors = [];
    $prevJson = json_decode($row['testJsonResult'] ?? '{}', true) ?: [];
    $hasNewOfflineDevice = false;
    $now = new \DateTime();
    $deviceOfflineTimeout  = Config::get('DEVICE_OFFLINE_TIMEOUT');
    foreach ($records as $device) {
        $deviceId = $device['id'];
        $deviceName = $device['deviceName'];
        $aliveFile = "/tmp/alive-{$deviceId}";

        if (!file_exists($aliveFile)) {
            $timeOfLine = -1;
        } else {
            $timeOfLine = time() - filemtime($aliveFile);
        }

        $testJsonResult[$deviceId] = ["time" => $timeOfLine];
        if ($timeOfLine === -1 || $timeOfLine > $deviceOfflineTimeout) {
            $errors[] = "Device " . $deviceName . " is offline for more than " . $deviceOfflineTimeout . " seconds.";

            $prevTime = $prevJson[$deviceId]['time'] ?? 0;
            $wasOfflineBefore = ($prevTime === -1 || $prevTime > $deviceOfflineTimeout);
            if (!$wasOfflineBefore) {
                $hasNewOfflineDevice = true;
            }
        }
    }

    $testResult = empty($errors) ? "OK" : "ERR";
    if ($hasNewOfflineDevice) {
        $row["errCount"] = 0;
        $row["errTarget"] = 1;
    }
    [$errCount, $errTarget] = checkAlert($row, $testResult, implode("\n", $errors));

    $data = [
        "table" => "testers",
        "queryData" => []
    ];
    $testJson = json_encode($testJsonResult, JSON_FORCE_OBJECT);
    $data["queryData"][] = [
        "id" => 2,
        "changed" => 3,
        "testDateTime" => date('Y-m-d H:i:s'),
        "testResult" => $testResult,
        "testJsonResult" => $testJson,
        "errCount" => $errCount,
        "errTarget" => $errTarget,
    ];
    DataProvider::getInstance()->save($data);
    ReturnData::getInstance()->set("[test device Online] " . $testJson, ReturnType::MESSAGE, "", ReturnDirection::USER);
}

function cleaner(array $row)
{
    $storagePath = rtrim(Config::get('STORAGE_PATH'), '/');
    $eventDirs = glob("{$storagePath}/event-*/*", GLOB_ONLYDIR) ?: [];

    $now = time();
    $eventMaxAge = (int) Config::get('DELETE_EVENTS_OLDER_THAN') * 3600 + 3600;
    $archiveMaxAge = (int) Config::get('DELETE_EVENTS_OLDER_THAN_DAY') * 86400 + 86400;

    $hasErrors = false;

    foreach ($eventDirs as $dir) {
        $age = $now - filemtime($dir);
        $hasMarker = file_exists("{$dir}/.found");

        if (!$hasMarker && $age > $eventMaxAge) {
            $hasErrors = true;
            (new Log())->set("[CHECK] Expired event without object: " . $dir);
        } elseif ($hasMarker && $age > $archiveMaxAge) {
            $hasErrors = true;
            (new Log())->set("[CHECK] Expired event with object: " . $dir);
        }
    }

    $testResult = $hasErrors ? "ERR" : "OK";
    $shortMsg = $hasErrors ? "Garbage collector alert: expired folders found" : "";

    [$errCount, $errTarget] = checkAlert($row, $testResult, $shortMsg);

    $data = [
        "table" => "testers",
        "queryData" => [
            [
                "id" => 3,
                "changed" => 3,
                "testDateTime" => date('Y-m-d H:i:s'),
                "testResult" => $testResult,
                "testJsonResult" => "[]",
                "errCount" => $errCount,
                "errTarget" => $errTarget,
            ]
        ]
    ];
    DataProvider::getInstance()->save($data);

    ReturnData::getInstance()->set("[CHECK] " . $testResult, ReturnType::MESSAGE, "", ReturnDirection::USER);
}


function checkAlert(array $row, string $testResult, string $message): array
{
    $errCount = (int)($row["errCount"] ?? 0);
    $errTarget = (int)($row["errTarget"] ?? 1);
    if ($errTarget < 1) {
        $errTarget = 1;
    }

    if ($testResult === "ERR") {
        $errCount++;
        if ($errCount >= $errTarget) {
            sendTelegramMessage($message, 2);
            $errCount = 0;
            $errTarget *= 2;
        }
    } else {
        $errCount = 0;
        $errTarget = 1;
    }

    return [$errCount, $errTarget];
}


function sendTelegramMessage(string $mes, int $task)
{
    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "users", "columns" => "chatCod"],
        ],
        "tables" => ["tasks", "users"],
        "join" => "LEFT JOIN users ON users.id = tasks.userId",
        "where" => "WHERE tasks.task = " . $task . " AND tasks.isDelete = 0 AND users.isDelete = 0",
    ];
    $records = DataProvider::getInstance()->read($data);

    $chatCod = json_decode($records[0]["chatCod"], true);
    $gf = new GeneralFunctions();
    $telegramCod = $chatCod["telegram"] ?? "";
    if ($telegramCod !== "") {
        $dataTemp = ["message" => $mes, "telegramCod" => $telegramCod];
        $gf->sendTelegramMessage($dataTemp);
    }
}

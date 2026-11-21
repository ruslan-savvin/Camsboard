<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';


try {
    detection();
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

function detection(): void
{
    $lock = fopen('/tmp/detection.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return;
    }

    $yoloSock = new YoloSock;

    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "deviceEvents", "columns" => "id"],
            "1" => ["action" => "+", "prefix" => "devices", "columns" => "id AS deviceId, deviceName, address"],
        ],
        "tables" => ["deviceEvents", "devices"],
        "join" => "LEFT JOIN devices ON devices.id = deviceEvents.device",
        "where" => "WHERE deviceEvents.isHumanChecked = 0 AND deviceEvents.isPhotoReady > 0",
        "orderBy" => "ORDER BY deviceEvents.eventTime",
    ];
    $records = DataProvider::getInstance()->read($data);
    $mes = "";
    $storagePath = Config::get('STORAGE_PATH');
    $data = [
        "table" => "deviceEvents",
        "queryData" => []
    ];
    $totalAllFiles = 0;
    $totalAllTime = 0.0;

    foreach ($records as $row) {
        $deviceId = $row['deviceId'];
        $deviceName = $row['deviceName'];
        $address = $row['address'];
        $eventId = $row['id'];
        $sourceDir = $storagePath . "event-" . $deviceId . "/" . $eventId;

        if (!is_dir($sourceDir)) {
            continue;
        }

        $totalDetectionTime = 0.0;
        $filesChecked = 0;
        $isPerson = "FALSE";
        $humanCheckedFrame = "";
        $confidence = "";
        $serviceOffline = false;

        foreach (scandir($sourceDir) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'jpg') {
                continue;
            }

            $detectStart = microtime(true);
            $rawResult = $yoloSock->hasPerson($sourceDir . "/" . $file);

            if ($rawResult === null) {
                $serviceOffline = true;
                break;
            }
            if ($rawResult === false) {
                continue;
            }
            $filesChecked++;
            $totalDetectionTime += (microtime(true) - $detectStart);
            $dataTemp = json_decode($rawResult, true);

            $isPerson = (!empty($dataTemp['has_person'])) ? "TRUE" : "FALSE";

            if ($isPerson === "TRUE") {
                $humanCheckedFrame = $file;
                $coords = $dataTemp['detections'][0]['box'] ?? [];
                $confidence = $dataTemp['detections'][0]['confidence'] ?? 0;
                $mes = $mes . $file . " / isPerson: " . $isPerson . " / coords: [" . implode(",", $coords) . "] / confidence: " . $confidence . "% ";
                drawDetections($sourceDir . "/" . $file, $dataTemp['detections'] ?? []);
                sendTelegramMessage($sourceDir . "/" . $file, $eventId, $deviceName, $address, $file);
                break;
            } else {
                $mes = $mes . $file . " / isPerson: " . $isPerson . " ";
            }
        }

        if ($serviceOffline) {
            break;
        }
        if ($filesChecked === 0) {
            continue;
        }

        $totalAllFiles += $filesChecked;
        $totalAllTime += $totalDetectionTime;

        $data["queryData"][] = [
            "id" => $eventId,
            "changed" => 3,
            "isHumanChecked" => ($isPerson === "TRUE") ? 2 : 1,
            "humanCheckedFrame" => $humanCheckedFrame,
            "confidence" => $confidence,
            "filesChecked" => $filesChecked,
            "elapsed" => round($totalDetectionTime / $filesChecked, 2),
            "totalDetectionTime" => round($totalDetectionTime, 2)
        ];

        if ($isPerson === "TRUE") {
            touch(rtrim($sourceDir, '/') . '/.found');
        }
    }

    if (!empty($data["queryData"])) {
        DataProvider::getInstance()->save($data);
        ReturnData::getInstance()->set("[detection] " . $mes . "| Files checked: " . $totalAllFiles . " | Time: " . round($totalAllTime, 2) . " sec", ReturnType::MESSAGE, "", ReturnDirection::USER);
    } else {
        ReturnData::getInstance()->set("[detection] No data available for person recognition", ReturnType::MESSAGE, "", ReturnDirection::USER);
    }
}


function sendTelegramMessage(string $imagePath, int $eventId, string $deviceName, string $address, string $file)
{
    if (!file_exists($imagePath)) {
        return false;
    }

    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "users", "columns" => "chatCod"],
        ],
        "tables" => ["tasks", "users"],
        "join" => "LEFT JOIN users ON users.id = tasks.userId",
        "where" => "WHERE tasks.task = 1 AND tasks.isDelete = 0 AND users.isDelete = 0",
    ];
    $records = DataProvider::getInstance()->read($data);

    $chatCod = json_decode($records[0]["chatCod"], true);
    $gf = new GeneralFunctions();
    $telegramCod = $chatCod["telegram"] ?? "";
    if ($telegramCod !== "") {
        $dataTemp = ["message" => $file . " Event: " . $eventId . " Device: " . $deviceName . " " . $address, "telegramCod" => $telegramCod, "photo" => $imagePath];
        $gf->sendTelegramMessage($dataTemp);
    }

    $viberCod = $chatCod["viber"] ?? "";
    if ($viberCod !== "") {
        $dataTemp = [
            "message"  => "Event: " . $eventId . " Device: " . $deviceName . " " . $address,
            "viberCod" => $viberCod
        ];
        $gf->sendViberMessage($dataTemp);
    }
}

function drawDetections(string $imagePath, array $detections): bool
{
    if (empty($detections) || !file_exists($imagePath)) {
        return false;
    }

    $im = imagecreatefromjpeg($imagePath);
    if ($im === false) {
        return false;
    }

    $red = imagecolorallocate($im, 255, 0, 0);
    imagesetthickness($im, 3);

    foreach ($detections as $det) {
        $coords = $det['box'] ?? [];
        if (count($coords) === 4) {
            imagerectangle(
                $im,
                (int)$coords[0],
                (int)$coords[1],
                (int)$coords[2],
                (int)$coords[3],
                $red
            );
        }
    }

    $saved = imagejpeg($im, $imagePath, 90);
    imagedestroy($im);

    return $saved;
}

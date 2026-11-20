<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

//date_default_timezone_set("Europe/Kyiv");
try {
    copyingVideos();
} catch (\Throwable $exception) {
    ReturnData::getInstance()->prepare($exception);
}

foreach (ReturnData::getInstance()->get()["message"] as $message) {
    $data = is_array($message) ? $message[1] : $message; //THROW_EXCEPTION
    $type = is_array($message) ? $message[0] : "";

    if ($type !== "exeption") {
        (new Log())->set($data);
    }
    echo $data . "<br>";
}

function copyingVideos(): void
{
    $records = DataProvider::getInstance()->read([
        "queryParams" => [
            "select" => [
                ["action" => "+", "prefix" => "deviceEvents", "columns" => "id,eventTime"],
                ["action" => "+", "prefix" => "devices", "columns" => "id AS deviceId,streamKey"],
            ],
            "tables" => ["deviceEvents", "devices"],
            "join" => "LEFT JOIN devices ON devices.id = deviceEvents.device",
            "where" => "WHERE deviceEvents.eventTime <= NOW() - INTERVAL " . Config::get('COPY_VIDEO_AFTER_EVENT') . " SECOND AND deviceEvents.isVideoReady = 0",
        ]
    ]);

    $storage = Config::get('STORAGE_PATH');
    $save = ["table" => "deviceEvents", "queryData" => []];
    $mes = "";

    foreach ($records as $row) {
        $eventTs = strtotime($row['eventTime']);
        $targetDir = "{$storage}event-{$row['deviceId']}/{$row['id']}";
        @mkdir($targetDir, 0755, true);

        $files = glob("{$storage}records-{$row['deviceId']}/{$row['streamKey']}/*.mp4") ?: [];
        sort($files);

        $copiedCount = 0;
        foreach ($files as $i => $file) {
            $start = \DateTime::createFromFormat('Ymd-His', basename($file, '.mp4'))?->getTimestamp();
            $end = filemtime($file);

            if ($start !== false && $end !== false && $start <= $eventTs && $end >= $eventTs) {
                foreach (array_slice($files, $i, 2) as $src) {
                    if (@copy($src, "{$targetDir}/" . basename($src))) {
                        $copiedCount++;
                    }
                }
                break;
            }
        }

        $mes .= "Event ID {$row['id']}: copied {$copiedCount} videos. ";
        $save["queryData"][] = ["id" => $row['id'], "changed" => 3, "isVideoReady" => $copiedCount];
    }

    if ($save["queryData"]) {
        DataProvider::getInstance()->save($save);
        ReturnData::getInstance()->set("[copy video] " . $mes, ReturnType::MESSAGE, "", ReturnDirection::USER);
    } else {
        ReturnData::getInstance()->set("[copy video] No videos to copy.", ReturnType::MESSAGE, "", ReturnDirection::USER);
    }
}

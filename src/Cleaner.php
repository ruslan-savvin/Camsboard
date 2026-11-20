<?php

namespace App;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

require_once __DIR__ . '/../vendor/autoload.php';

//date_default_timezone_set("Europe/Kyiv");
try {
    cleanRecordsTemp();
    cleanEventIsHumanCheckedNo();
    cleanEventIsHumanCheckedYes();
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

function cleanRecordsTemp(): void
{
    $storagePath = rtrim(Config::get('STORAGE_PATH'), '/');
    if (!is_dir($storagePath)) {
        ReturnData::getInstance()->set("Directory not found: {$storagePath}", ReturnType::THROW_EXCEPTION, "exeption", ReturnDirection::USER);
    }

    $now = time();
    $retention = (int)Config::get('RECORDS_RETENTION');
    $deleted_count = 0;

    $files = glob($storagePath . '/records-*/*/*.mp4', GLOB_NOSORT) ?: [];

    foreach ($files as $file_path) {
        if (!is_file($file_path)) {
            continue;
        }

        if (($now - filemtime($file_path)) > $retention) {
            if (@unlink($file_path)) {
                $deleted_count++;
            }
        }
    }

    $mes = "[clean video] Deleted {$deleted_count} files from records directories.";
    ReturnData::getInstance()->set($mes, ReturnType::MESSAGE, "", ReturnDirection::USER);
}


function cleanEventIsHumanCheckedNo()
{

    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "deviceEvents", "columns" => "id,eventTime"],
            "1" => ["action" => "+", "prefix" => "devices", "columns" => "id AS deviceId"],
        ],
        "tables" => ["deviceEvents", "devices"],
        "join" => "LEFT JOIN devices ON devices.id = deviceEvents.device",
        "where" => "WHERE deviceEvents.eventTime <= NOW() - INTERVAL " . Config::get('DELETE_EVENTS_OLDER_THAN') . " HOUR AND deviceEvents.isHumanChecked < 2",
        "orderBy" => "ORDER BY deviceEvents.eventTime",

    ];
    $records = DataProvider::getInstance()->read($data);

    $storagePath = Config::get('STORAGE_PATH');
    $data = [
        "table" => "deviceEvents",
        "queryData" => []
    ];
    $mes = "";
    foreach ($records as $row) {
        $deviceId = $row['deviceId'];
        $eventId = $row['id'];
        $sourceDir = $storagePath . "event-" . $deviceId . "/" . $eventId;
        if (!empty($deviceId) && !empty($eventId) && is_dir($sourceDir)) {
            exec('rm -rf ' . escapeshellarg($sourceDir));
        }
        clearstatcache(true, $sourceDir);
        if (is_dir($sourceDir)) {
            continue;
        }


        $data["queryData"][] = [
            "id" => $eventId,
            "changed" => 2
        ];
        $mes = $mes . $sourceDir . " ";
    }
    if (!empty($data["queryData"])) {
        DataProvider::getInstance()->save($data);
    }

    ReturnData::getInstance()->set("[clean] Deleted events " . $mes, ReturnType::MESSAGE, "", ReturnDirection::USER);
}

function cleanEventIsHumanCheckedYes()
{

    $data["queryParams"] = [
        "select" => [
            "0" => ["action" => "+", "prefix" => "deviceEvents", "columns" => "id,eventTime"],
            "1" => ["action" => "+", "prefix" => "devices", "columns" => "id AS deviceId"],
        ],
        "tables" => ["deviceEvents", "devices"],
        "join" => "LEFT JOIN devices ON devices.id = deviceEvents.device",
        "where" => "WHERE deviceEvents.eventTime <= NOW() - INTERVAL " . Config::get('DELETE_EVENTS_OLDER_THAN_DAY') . " DAY AND deviceEvents.isHumanChecked = 2",
        "orderBy" => "ORDER BY deviceEvents.eventTime",

    ];
    $records = DataProvider::getInstance()->read($data);

    $storagePath = Config::get('STORAGE_PATH');
    $data = [
        "table" => "deviceEvents",
        "queryData" => []
    ];
    $mes = "";
    foreach ($records as $row) {
        $deviceId = $row['deviceId'];
        $eventId = $row['id'];
        $sourceDir = $storagePath . "event-" . $deviceId . "/" . $eventId;
        if (!empty($deviceId) && !empty($eventId) && is_dir($sourceDir)) {
            exec('rm -rf ' . escapeshellarg($sourceDir));
        }
        clearstatcache(true, $sourceDir);
        if (is_dir($sourceDir)) {
            continue;
        }


        $data["queryData"][] = [
            "id" => $eventId,
            "changed" => 2
        ];
        $mes = $mes . $sourceDir . " ";
    }
    if (!empty($data["queryData"])) {
        DataProvider::getInstance()->save($data);
    }
    ReturnData::getInstance()->set("[clean] Deleted events " . $mes, ReturnType::MESSAGE, "", ReturnDirection::USER);
}

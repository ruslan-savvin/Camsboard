<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(0);

$deviceId = (int) trim($argv[1]);
$fifoPath = trim($argv[2]);
$fifo = fopen($fifoPath, 'r+');

$storagePath = Config::get('STORAGE_PATH');
$uploadPhotoDelayAfterEvent = (int) Config::get('UPLOAD_FOTO_DELAY_AFTER_EVENT');
$copyStartTime = 0;

if ($fifo) {
    while (($line = fgets($fifo)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        (new Log())->set("[FFMPEG]  Device: " . $deviceId . " " . $line);
        try {
            if (preg_match('/lavfi\.scene_score=([0-9.]+)/i', $line, $matches)) {
                $sceneScore = (float) $matches[1];
                saveEvent($deviceId, $sceneScore, $copyStartTime, $uploadPhotoDelayAfterEvent, $storagePath);
            }
        } catch (\Throwable $e) {
            (new Log())->set("ERROR saveEvent Cam {$deviceId}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
    fclose($fifo);
}

function saveEvent(int $deviceId, float $sceneScore, &$copyStartTime, int $uploadPhotoDelayAfterEvent, string $storagePath)
{
    if ((time() - $copyStartTime) > $uploadPhotoDelayAfterEvent) {
        $now = time();
        $copyStartTime = time();
        $data = [
            "table" => "deviceEvents",
            "queryData" => [
                "0" => ["changed" => 1, "device" => $deviceId, "eventTime" => date('Y-m-d H:i:s', $now), "sceneScore" => $sceneScore],
            ]
        ];
        DataProvider::getInstance()->save($data);
        $execResult = DataProvider::getInstance()->execResult;
        $eventId = $execResult['insertId'][0];

        $signalFile = "/tmp/event-{$deviceId}.json";
        $dir = $storagePath . "event-" . $deviceId . "/" . $eventId . "/";
        file_put_contents($signalFile, json_encode([
            'dir' => $dir,
            'eventId' => $eventId,
            'until' => $copyStartTime + $uploadPhotoDelayAfterEvent,
        ]));
    }
}

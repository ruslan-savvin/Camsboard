<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(0);

$deviceId = (int) trim($argv[1]);
$signalFile = "/tmp/event-{$deviceId}.json";

$buf = '';
$isRecording = false;
$fotoCount = 0;

$signal = null;
$eventId = null;
$dir = '';
$until = 0;

$lastBeat = 0;
while (!feof(STDIN)) {
    $chunk = fread(STDIN, 16384);
    if ($chunk === false || $chunk === '') {
        continue;
    }
    $buf .= $chunk;

    while (($s = strpos($buf, "\xff\xd8")) !== false && ($e = strpos($buf, "\xff\xd9", $s)) !== false) {
        $jpg = substr($buf, $s, $e - $s + 2);
        $buf = substr($buf, $e + 2);

        if (time() - $lastBeat >= 10) {
            touch("/tmp/alive-{$deviceId}");
            $lastBeat = time();
        }

        try {
            if (!$isRecording) {
                $signal = json_decode(@file_get_contents($signalFile), true);
                if ($signal) {
                    $eventId = $signal['eventId'];
                    $dir = $signal['dir'];
                    $until = $signal['until'];
                }
            }
            if ($signal && time() <= $until) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $name = (new \DateTime())->format('Ymd His-v');
                (new Log())->set("[FFMPEG] Збереження файла " . $dir . "/" . $name);
                file_put_contents("{$dir}/{$name}.jpg", $jpg);
                ++$fotoCount;
                $isRecording = true;
            } elseif ($isRecording) {
                $data = [
                    "table" => "deviceEvents",
                    "queryData" => [
                        "0" => ["changed" => 3, "id" => $eventId, "isPhotoReady" => $fotoCount]
                    ]
                ];
                DataProvider::getInstance()->save($data);
                exec("php /var/www/camsboard/src/Detector.php > /dev/null 2>&1 &");

                $fotoCount = 0;
                $isRecording = false;
            }
        } catch (\Throwable $e) {
            (new Log())->set("[FFMPEG] Device: {$deviceId}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
}

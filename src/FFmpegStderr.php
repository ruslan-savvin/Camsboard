<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(0);

$deviceId = (int) trim($argv[1]);
$stdin = fopen('php://stdin', 'r');

if ($stdin) {
    while (($line = fgets($stdin)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        try {
            (new Log())->set("[FFMPEG] Devise: {$deviceId} | ERROR: {$line}");
        } catch (\Throwable $e) {
            (new Log())->set("[FFMPEG] Device: {$deviceId}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
    fclose($stdin);
}

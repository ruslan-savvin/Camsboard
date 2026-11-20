<?php

namespace App;

class Config
{
    private static array $settings = [
        'PYTHON_BIN' => "/var/www/camsboard/venv/bin/python3",
        'PYTHON_SCRIPT' => "/var/www/camsboard/src/detect.py",
        'STORAGE_PATH'            => '/var/www/camsboard/storage/',
        'COPY_VIDEO_AFTER_EVENT' => 130,
        'RECORDS_RETENTION'       => 300, // Deletion interval for temporary video files storage/records-N
        'UPLOAD_FOTO_DELAY_AFTER_EVENT' => 10, // Delay interval in seconds before copying files after a motion change event triggers        
        'POST_MOTION_HUMAN_DETECTION_FRAMES' => 10, // Number of photos to copy after the motion event for human detection
        'DELETE_EVENTS_OLDER_THAN' => 24, // Delete events older than X hours
        'DELETE_EVENTS_OLDER_THAN_DAY' => 30, // Delete events older than X DAY
        'DEVICE_OFFLINE_TIMEOUT' => 60 // Device downtime in seconds after which it is considered an error/offline        
    ];

    public static function get(string $key)
    {
        return self::$settings[$key] ?? null;
    }
}

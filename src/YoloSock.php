<?php

namespace App;

class YoloSock
{
    public function hasPerson(string $imagePath)
    {
        if (!file_exists($imagePath)) {
            return false;
        }
        $sock = @stream_socket_client('unix:///tmp/yolo.sock', $errno, $errstr, 2);
        if (!$sock) {
            return null;
        }
        fwrite($sock, $imagePath . "\n");
        $output = stream_get_contents($sock);
        fclose($sock);

        if ($output === false || trim($output) === '') {
            return null;
        }
        return trim($output);
    }
}

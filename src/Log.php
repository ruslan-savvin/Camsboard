<?php

//Savvin Ruslan 2024

namespace App;

class Log
{

    public function set(mixed $contents)
    {
        $filename = dirname(__DIR__) . "/log/log.txt";
        $file_content = @file_get_contents($filename);
        if ($file_content === false || $file_content === "") {
            $my_array = [];
        } else {
            $my_array = json_decode($file_content, true);
            if ($my_array === null && $file_content !== "null") {
                $my_array = [];
            }
        }
        $export_string = var_export($contents, true);
        $export_string = str_replace('\\\\', '/', $export_string);
        $export_string = str_replace('\\', '/', $export_string);
        $new_item = array("d" => date("d-m-Y H:i:s"), "i" => $export_string);
        array_unshift($my_array, $new_item);
        $my_array = array_slice($my_array, 0, 50);
        $new_content = json_encode($my_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($filename, $new_content);
    }
}

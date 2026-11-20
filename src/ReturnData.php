<?php

namespace App;

enum ReturnType: string
{
    case MESSAGE = "message";
    case DATA = "data";
    case THROW_EXCEPTION = "throwException";
}
enum ReturnDirection: string
{
    case LOG = "log";
    case USER = "user";
    case RETURNTOCLIENT = "client";
}
class ReturnData
{
    private static ?ReturnData $instance = null;
    private array $returnArray = [];
    private function __construct() {}
    public string $typeData = "application/json";

    public static function getInstance(): ReturnData
    {
        return self::$instance ??= new self();
    }

    public function setHeader(string $header, string $typeData)
    {
        $this->returnArray["header"][] = $header;
        $this->typeData = $typeData;
    }

    public function getHeader()
    {
        $headers = $this->returnArray["header"] ?? ["Content-Type: application/json; charset=utf-8"];
        if (is_array($headers)) {
            foreach ($headers as $value) {
                header($value);
            }
        }
        return $this->typeData;
    }

    public function set(mixed $data, ReturnType $type, string $sign, ReturnDirection $direction)
    {
        if ($type === ReturnType::DATA) {
            $this->returnArray[$type->value] = $data;
        } else if ($type === ReturnType::MESSAGE or $type === ReturnType::THROW_EXCEPTION) {
            if ($direction !== ReturnDirection::LOG) {
                if (is_string($data)) {
                    $this->returnArray["message"][] = [$sign, $data];
                } elseif (is_array($data)) {
                    array_unshift($data, $sign);
                    $this->returnArray["message"][] = $data;
                }
            } else {
                $this->returnArray["message"][] = $data;
            }
            $this->returnArray["direction"][] = $direction;
        }
        if ($type === ReturnType::THROW_EXCEPTION) {
            throw new \Exception(is_string($data) ? $data : "CustomException");
        }
    }

    public function get(): array
    {
        return $this->returnArray;
    }

    public function clear(ReturnType $type)
    {
        if ($type === ReturnType::DATA) {
            $this->returnArray[$type->value] = [];
        } else if ($type === ReturnType::MESSAGE or $type === ReturnType::THROW_EXCEPTION) {
            $this->returnArray["message"] = [];
        }
    }

    public function prepare(mixed $ex)
    {
        if (isset($this->returnArray["message"])) {
            foreach ($this->returnArray["direction"] as $i => $dir) {
                if ($dir === ReturnDirection::LOG) {
                    (new Log())->set($this->returnArray["message"][$i]);
                    unset($this->returnArray["message"][$i], $this->returnArray["direction"][$i]);
                }
            }
            $this->returnArray["message"] = array_values($this->returnArray["message"]);
        }
        unset($this->returnArray["direction"]);
        $message = "";
        if ($ex instanceof \Throwable) {
            $trace = $ex->getTrace();
            $src = !empty($trace) ? $trace[0] : ['file' => $ex->getFile(), 'line' => $ex->getLine()];
            $message = "Error: " . $ex->getMessage() .
                " | Source: " . ($src['file'] ?? 'unknown') .
                " line: " . ($src['line'] ?? 'unknown');
            $mesClient = $ex->getMessage();
            (new Log())->set($message);
            http_response_code(500);
        }
        if (empty($this->returnArray["message"]) and empty($this->returnArray["data"])) {
            if ($message) {
                $this->returnArray["message"][] = ["❗ ", "There are errors. Contact the administrator."];
            }
        }
    }
}

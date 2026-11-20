<?php
// Savvin Ruslan 2024

namespace App;

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

class SqlLink
{
    public \mysqli $link;
    public string $host;
    public string $user;
    public string $pass;
    public string $base;
    public string $environment;

    public function __construct()
    {

        $this->host        = $_ENV['DB_HOST'] ?? 'localhost';
        $this->user        = $_ENV['DB_USER'] ?? '';
        $this->pass        = $_ENV['DB_PASS'] ?? '';
        $this->base        = $_ENV['DB_NAME'] ?? '';
        $this->environment = $_ENV['APP_ENV'] ?? 'production';

        $this->link = new \mysqli($this->host, $this->user, $this->pass, $this->base);
        $this->link->set_charset("utf8mb4");
        $this->link->query("SET time_zone = '" . date('P') . "'");
        $this->link->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
}

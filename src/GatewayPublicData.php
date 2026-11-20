<?php

// Savvin Ruslan 2024-2025
namespace App;

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});
//date_default_timezone_set("Europe/Kyiv");

try {
    DataProvider::getInstance()->begin();
    $data = json_decode(file_get_contents('php://input'), true);
    // Check if the script is running via CLI and an argument is provided
    if (php_sapi_name() === 'cli' && isset($argv[1])) {
        $_GET['action'] = $argv[1];
    }

    $action = "";
    $botHandler = null;
    if (isset($_SERVER['HTTP_X_VIBER_CONTENT_SIGNATURE'])) {
        $botHandler = new MessengerBotHandler();
        $viberBot = $botHandler->getViberBotIp();
        $inputData = file_get_contents('php://input');
        $expectedSignature = hash_hmac("sha256", $inputData, $viberBot);
        if (hash_equals($expectedSignature, $_SERVER["HTTP_X_VIBER_CONTENT_SIGNATURE"])) {
            $action = "viber/callback";
        } else {
            $mes = "(Viber) Verification failed: cryptographic signature mismatch ";
            ReturnData::getInstance()->set($mes, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
        }
    } else if (isset($_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"]) && $_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"] === "7fcf93e8da22c548bc9d2b1a13b40fd6") {
        $action = "telegram/callback";
    } else if (isset($_SERVER["HTTP_X_BEDS24_SECRET_TOKEN"]) && $_SERVER["HTTP_X_BEDS24_SECRET_TOKEN"] === "4f7e2a9b3c1d8e5f6a0b2c4d6e8f9a1b3c5d7e9f0a2b4c6d8e0f1a3b5c7d9e1f") {
        $action = "channel/callback";
    }
    if (!$action) {
        $action = $data["action"] ?? $_GET["action"];
    }
    $routes = [
        "reference/notAuthorization/fetch" => fn() => (new DataProcessor())->referenceNotAuthorizationFetch($data),
        "templates/modalWindow" => fn() => (new \App\Templates\TemplateModalWindow())->getTemplateModalWindow(),
        "user/getPassword" => fn() => (new Authorization())->getPassword($data),
        "user/checkSession" => fn() => (new Authorization())->checkSession(true),
        "user/setAuthorization" => fn() => (new Authorization())->setAuthorization($data),
    ];
    $handler = $routes[$action] ?? ReturnData::getInstance()->set("Unknown action: " . $action, ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
    $handler();
    DataProvider::getInstance()->commit();
    ReturnData::getInstance()->prepare(null);
} catch (\Throwable $exception) {
    DataProvider::getInstance()->rollback();
    ReturnData::getInstance()->prepare($exception);
    if (isset($_SERVER["HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN"])) {
        http_response_code(200);
    }
}
ob_clean();
$typeHeader = ReturnData::getInstance()->getHeader();
if ($typeHeader === "application/json") {
    echo json_encode(ReturnData::getInstance()->get(), JSON_UNESCAPED_UNICODE);
} else if ($typeHeader === "text/html" || $typeHeader === "application/pdf") {
    echo ReturnData::getInstance()->get()["data"];
}
exit;

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


try {
    DataProvider::getInstance()->begin();
    $data = json_decode(file_get_contents('php://input'), true);
    // Check if the script is running via CLI and an argument is provided
    if (php_sapi_name() === 'cli' && isset($argv[1])) {
        $_GET['action'] = $argv[1];
    }

    $action = $data["action"] ?? $_GET["action"] ?? null;
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
}
ob_clean();
$typeHeader = ReturnData::getInstance()->getHeader();
if ($typeHeader === "application/json") {
    echo json_encode(ReturnData::getInstance()->get(), JSON_UNESCAPED_UNICODE);
} else if ($typeHeader === "text/html" || $typeHeader === "application/pdf") {
    echo ReturnData::getInstance()->get()["data"];
}
exit;

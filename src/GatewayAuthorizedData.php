<?php

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
    (new Authorization())->checkSession(false);
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $data["action"] ?? null;
    $routes = [
        "reference/authorization/fetch"   => fn() => (new DataProcessor())->referenceAuthorizationFetch($data),
        "reference/devices/authorization/save" => fn() => (new DataProcessor())->referenceDevicesSave($data),
        "reference/GeneralSettings/authorization/save" => fn() => (new DataProcessor())->referenceGeneralSettingsSave($data),
        "loadFoto/authorization/fetch"   => fn() => (new DataProcessor())->loadFoto($data),
        "nameFotos/authorization/fetch"  => fn() => (new DataProcessor())->loadNameFotos($data),
        "log/fetch"                      => fn() => (new DataProcessor())->logFetch($data),
        "getTestResult/authorization/fetch"            => fn() => (new DataProcessor())->getTestResult($data),
        "environment/authorization/fetch" => fn() => (new DataProcessor())->getEnvironment(),
        "reference/authorization/save"    => fn() => (new DataProcessor())->referenceAuthorizationSave($data),
        "user/outputSession"              => fn() => (new Authorization())->userOutput(),
    ];
    $handler = $routes[$action] ?? ReturnData::getInstance()->set("Unknown action: $action", ReturnType::THROW_EXCEPTION, "❗ ", ReturnDirection::LOG);
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

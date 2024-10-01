<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\ErrorLog;
use App\Config\ResponseHttp;

ErrorLog::activateErrorLog();

// ------ DOTENV ------
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// ------ ROUTER ------
$requestUri = trim($_SERVER['REQUEST_URI'], '/');
$basePath = 'turnero';

if (strpos($requestUri, $basePath) === 0) {
  $requestUri = substr($requestUri, strlen($basePath));
  $requestUri = trim($requestUri, '/');
}

$params = explode('/', $requestUri);

$validRoutes = ['appointments', 'auth', 'professionals', 'users'];
$route = $params[0] ?? '';

if (in_array($route, $validRoutes)) {
  $file = dirname(__DIR__) . '/src/Routes/' . $params[0] . '.php';

  if (file_exists($file)) {
    array_shift($params);
    $_SERVER['params'] = $params;
    require($file);
    exit;
  }
}

echo json_encode(ResponseHttp::status404());

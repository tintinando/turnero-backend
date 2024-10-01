<?php

use App\Config\ResponseHttp;
use App\Controllers\UserController;

$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$params = $_SERVER['params'] ?? [];
$data = json_decode(file_get_contents('php://input'), true);

$controller = new UserController($method, $headers, $params, $data);

$controller->login("login");
$controller->getCurrentUser("profile");

$controller->dispatch();

echo json_encode(ResponseHttp::status404());

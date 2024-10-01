<?php

use App\Config\ResponseHttp;
use App\Controllers\ProfessionalController;

$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$params = $_SERVER['params'] ?? [];
$data = json_decode(file_get_contents('php://input'), true);

$controller = new ProfessionalController($method, $headers, $params, $data);


$controller->getAllProfessionals(); // professionals [GET]
$controller->postProfessional(); // professionals [POST]
$controller->getProfessional(); // professionals/{id} [GET]
$controller->modifyProfessional(); // professionals/{id} [PUT]
$controller->deleteprofessional(); // method delete

$controller->dispatch();

echo json_encode(ResponseHttp::status404());

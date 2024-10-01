<?php

use App\Config\ResponseHttp;
use App\Controllers\AppointmentController;

$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$params = $_SERVER['params'] ?? [];
$data = json_decode(file_get_contents('php://input'), true);

$controller = new AppointmentController($method, $headers, $params, $data);

$controller->getAllAppointments();
$controller->getAppointmentsOfCurrentUser("my");
$controller->post();
$controller->modify();

$controller->dispatch();

echo json_encode(ResponseHttp::status404());

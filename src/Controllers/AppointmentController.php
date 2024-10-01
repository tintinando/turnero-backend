<?php

namespace App\Controllers;

use App\Config\ResponseHttp;
use App\Config\Security;
use App\Models\AppointmentModel;
use App\Models\AppointmentRequestModel;
use App\Models\UserModel;
use Exception;

class AppointmentController {
  private $response = null;
  private $model = null;
  private $userModel = null;

  public function __construct(
    private string $method,
    private array $headers,
    private array $params,
    private $data
  ) {
    $this->method = strtolower($this->method);
    $this->model = new AppointmentModel();
    $this->model->mapFromRequest($this->data);
    $this->userModel = new UserModel();
  }

  private function verifyAdmin() {
    $jwt = Security::validateJwt($this->headers);
    if (!$this->userModel->isAdmin($jwt->data->userId)) {
      die(json_encode(ResponseHttp::status401("Se requiere token de admin")));
    };
    return;
  }

  /**
   * Verify if params[0] is a digit
   */
  private function verifyId() {
    if (!ctype_digit($this->params[0])) {
      die(json_encode(ResponseHttp::status400("Id de usuario inválido")));
      return;
    }
  }

  // ------ GET ALL ------
  public function getAllAppointments() {
    if ($this->method !== 'get' || isset($this->params[0])) return;
    $this->verifyAdmin();
    $this->response = $this->model->getAllAppointments();
    return;
  }

  // ------ GET BY USER ------
  public function getAppointmentsOfCurrentUser($endpoint) {
    if ($this->method !== 'get' || empty($this->params) || $this->params[0] !== $endpoint) return;
    $jwt = Security::validateJwt($this->headers);

    $this->response = $this->model->getAppointmentsByUser($jwt->data->userId);
  }

  // ------ POST ------
  public function post() {
    if ($this->method !== 'post' || isset($this->params[0])) return;

    $jwt = Security::validateJwt($this->headers);

    // set user id if the body has not
    if (!isset($this->data[AppointmentRequestModel::USER_ID])) {
      $this->model->setUserId($jwt->data->userId);
    } else {
      // if body has user id, verify owner or admin
      $user = $this->userModel->getUserById($jwt->data->userId);
      if ($this->data[AppointmentRequestModel::USER_ID] !== $jwt->data->userId) {
        if ($user['user_group_id'] > 2) {
          $this->response = ResponseHttp::status401();
          return;
        }
      }
    }

    try {
      $this->response = $this->model->post();
    } catch (Exception $e) {
      $this->response = ResponseHttp::status400($e->getMessage());
    }
    return;
  }

  public function modify() {
    if ($this->method !== 'put' || !isset($this->params[0])) return;
    $this->verifyId();
    $jwt = Security::validateJwt($this->headers);

    // obterner la cita por ID
    $appointment = $this->model->getAppointmentById($this->params[0]);

    // Verificar si el usuario actual es propietario de la cita
    if ($jwt->data->userId !== $appointment['user_id']) {
      // si no es propietario, obtener detalles del usuario actual
      $currentUser = $this->userModel->getUserById($jwt->data->userId);
      if ($currentUser['user_group_id'] > 2) {
        $this->response = ResponseHttp::status403("Solo admin o profesional o propietario modifican turnos ");
        return;
      }
    }

    if (isset($this->data[AppointmentRequestModel::ESTADO]) && $jwt->data->userId === $appointment['user_id']) {
      $this->response = ResponseHttp::status403("El propietario no puede modificar el estado");
      return;
    }

    $this->response = $this->model->modify($appointment['id']);
    return;
  }

  // ------ DISPATCH RESPONSE ------
  public function dispatch() {
    if ($this->response !== null) {
      echo json_encode($this->response);
      exit;
    }
  }
}

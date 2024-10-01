<?php

namespace App\Controllers;

use App\Config\ResponseHttp;
use App\Config\Security;
use App\Models\ProfessionalModel;
use App\Models\ProfessionalRequestModel;
use App\Models\UserModel;

class ProfessionalController {
  private $response = null;
  private $professionalModel = null;
  private $userModel = null;

  public function __construct(
    private string $method,
    private array $headers,
    private array $params,
    private $data
  ) {
    $this->method = strtolower($this->method);
    $this->professionalModel = new ProfessionalModel();
    $this->professionalModel->mapFromRequest($this->data);

    $this->userModel = new UserModel();
  }

  /**
   * Get jwt from headers and die if user is not in group 1
   */
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

  /**
   * Return true if the user is owner or admin
   * @param int $targetProfessionalId The Id of the user who will be modificated
   * @param array $userWhoRequest The UserModel response of get user id who request
   * @param ['modify'|string] $action only for correct error message
   * @return boolean 
   */
  private function validateUserPermissions($targetProfessionalId, $userWhoRequest, $action = 'modify') {

    $professional = $this->professionalModel->getProfessionalById((int)$targetProfessionalId);
    if ($professional['user_id'] == $userWhoRequest['id']) return true;

    // User is not owner, then check if it's admin
    if ($userWhoRequest['user_group_id'] !== 1) {
      $message = $action === 'modify'
        ? "Solo se puede modificar el usuario si es administrador"
        : "Solo se puede borrar el usuario si es administrador";
      $this->response = ResponseHttp::status403($message);
      return false;
    }
    return true;
  }

  /**
   * Return the user logged in jwt
   * @return object User
   */
  private function getUserWhoRequest() {
    $jwtPayload = Security::validateJwt($this->headers);
    return $this->userModel->getUserById($jwtPayload->data->userId);
  }

  public function getAllProfessionals() {
    if ($this->method !== 'get' || isset($this->params[0])) return;
    $this->verifyAdmin();
    $this->response = $this->professionalModel->getAllProfessionals();
    return;
  }

  public function getProfessional() {
    if ($this->method !== 'get' || !isset($this->params[0])) return;
    $this->verifyId();
    $this->verifyAdmin();

    $this->response = $this->professionalModel->getProfessionalById($this->params[0]);
    return;
  }

  public function postProfessional() {
    if ($this->method !== 'post' || isset($this->params[0])) return;
    $this->verifyAdmin();

    $this->response = $this->professionalModel->post();
    return;
  }

  public function modifyProfessional() {
    if ($this->method !== 'put') return;
    if (!isset($this->params[0])) {
      $this->response = ResponseHttp::status400("Falta endpoint id de profesional");
      return;
    }

    $this->verifyId();
    $userWhoRequest = $this->getUserWhoRequest();

    // validate permissions
    if (!$this->validateUserPermissions($this->params[0], $userWhoRequest, 'modify')) return;

    $this->response = $this->professionalModel->modifyProfessional($this->params[0]);
    return;
  }

  public function deleteprofessional() {
    if ($this->method !== 'delete') return;
    $userWhoRequest = $this->getUserWhoRequest();

    // use ID from params, or from JWT is it's not
    if (isset($this->params[0])) {
      $this->verifyId();
      $targetProfessionalId = $this->params[0];
    } else {
      $targetProfessionalId = $userWhoRequest['id'];
    }

    // validate permissions
    if (!$this->validateUserPermissions($targetProfessionalId, $userWhoRequest, 'delete')) return;

    $this->response = $this->professionalModel->deleteProfessional($targetProfessionalId);
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

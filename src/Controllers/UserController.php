<?php

namespace App\Controllers;

use App\Config\ResponseHttp;
use App\Config\Security;
use App\Models\UserModel;
use App\Models\UserRequestModel;

class UserController {
  private $response = null;
  private $userModel = null;
  private UserRequestModel $userRequest;

  public function __construct(
    private string $method,
    private array $headers,
    private array $params,
    private $data
  ) {
    $this->method = strtolower($this->method);
    if ($this->data) $this->userRequest = new UserRequestModel($this->data);
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
   * @param int $targetUserId The Id of the user who will be modificated
   * @param array $userWhoRequest The UserModel response of get user id who request
   * @param ['modify'|string] $action only for correct error message
   * @return boolean 
   */
  private function validateUserPermissions($targetUserId, $userWhoRequest, $action = 'modify') {
    if ($targetUserId == $userWhoRequest['id']) return true;

    // User is not owner, then check if it's admin
    if ($userWhoRequest['user_group_id'] !== 1) {
      $message = $action === 'modify'
        ? "Solo se puede modificar el usuario si es administrador"
        : "Solo se puede borrar el usuario si es administrador";
      $this->response = ResponseHttp::status401($message);
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

  // ------ GET ALL USERS ------
  public function getAllUsers() {
    if ($this->method !== 'get' || isset($this->params[0])) return;
    $this->verifyAdmin();
    $this->response = $this->userModel->getAllUsers();
    return;
  }

  // ------ GET USER ------
  public function getUser() {
    if ($this->method !== 'get') return;
    $this->verifyId();
    $this->verifyAdmin();

    $this->response = $this->userModel->getUserById($this->params[0]);
    return;
  }

  // ------ POST NEW USER ------
  public function postUser() {
    if ($this->method !== 'post' || isset($this->params[0])) return;

    $userGroup = $this->userRequest->userGroup ?? 3;

    // require jwt with privilegies
    if ($userGroup < 3) {
      $this->verifyAdmin();
    }

    $model = new UserModel(
      $this->userRequest->userGroup,
      $this->userRequest->username,
      $this->userRequest->password,
      $this->userRequest->email,
    );

    $this->response = $model->post();
    return;
  }

  // ------ MODIFY USER ------
  public function modifyUser() {
    if ($this->method !== 'put') return;

    // get user who request
    $userWhoRequest = $this->getUserWhoRequest();

    // use ID from params, or from JWT is it's not
    if (isset($this->params[0])) {
      $this->verifyId();
      $targetUserId = $this->params[0];
    } else {
      $targetUserId = $userWhoRequest['id'];
    }

    // validate permissions
    if (!$this->validateUserPermissions($targetUserId, $userWhoRequest, 'modify')) return;

    // check if user is admin for scale privileges
    if (isset($this->data['userGroup']) && $this->data['userGroup'] < 3) {
      $this->verifyAdmin();
    }


    $userModelToModify = new UserModel(
      $this->userRequest->userGroup,
      $this->userRequest->username,
      $this->userRequest->password,
      $this->userRequest->email
    );

    $this->response = $userModelToModify->modifyUser($targetUserId);
    return;
  }

  // ------ DELETE USER ------
  public function deleteUser() {
    if ($this->method !== 'delete') return;

    $userWhoRequest = $this->getUserWhoRequest();

    // check if body have id
    if (!$this->data['id']) {
      $this->response = ResponseHttp::status400("Falta ID");
      return;
    }

    if (!is_numeric($this->data['id'])) {
      $this->response = ResponseHttp::status400("El ID debe ser numérico");
      return;
    }

    // check if user to delete exists
    $userToDelete = $this->userModel->getUserById($this->data['id']);
    if (!$userToDelete) {
      $this->response = ResponseHttp::status404("Id de usuario incorrecto");
      return;
    }

    // validate permissions
    if (!$this->validateUserPermissions($userToDelete['id'], $userWhoRequest, 'delete')) return;

    // require jwt with privilegies
    if ($userToDelete['user_group_id'] < 3) {
      $this->verifyAdmin();
    }

    $this->response = $this->userModel->deleteUser($this->data['id']);
    return;
  }

  // ------ LOGIN USER ------
  public function login(string $endpoint) {
    if (!$this->params) return;
    if ($this->method !== 'post' || $this->params[0] !== $endpoint) return;

    $userModel = new UserModel(
      username: $this->userRequest->username,
      password: $this->userRequest->password
    );

    $this->response = $userModel->login();
  }

  public function getCurrentUser(string $endpoint) {
    if (!$this->params) return;
    if ($this->method !== 'get' || $this->params[0] !== $endpoint) return;
    $this->response = ResponseHttp::status200($this->getUserWhoRequest());
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

<?php

namespace App\Models;

use App\Config\ResponseHttp;
use App\Config\Security;

class UserModel extends Database {
  public function __construct(
    protected ?int $userGroup = null,
    protected ?string $username = null,
    protected ?string $password = null,
    protected ?string $email = null,
  ) {
    parent::__construct();
  }

  public function getUserById($id) {
    $sql = "SELECT * FROM user WHERE id=?";
    $stmt = $this->executeQuery($sql, "s", [$id]);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  public function getUserByUsername() {
    $this->validateFields(['username']);
    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $this->executeQuery($sql, "s", [$this->username]);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  public function getAllUsers() {
    $sql = "SELECT * FROM user";
    $stmt = $this->executeQuery($sql);
    $result = $stmt->get_result();
    $users = [];

    while ($row = $result->fetch_assoc()) {
      $users[] = $row;
    }
    return $users;
  }

  public function isAdmin($userId) {
    $user = $this->getUserById($userId);
    return $user ? $user['user_group_id'] === 1 : false;
  }

  public function post() {
    $this->validateFields(['userGroup', 'username', 'password']);

    // find ig username exists
    $user = $this->getUserByUsername($this->username);
    if ($user)  return ResponseHttp::status409("El username ya existe");

    // post new user
    $sql = "INSERT INTO user (user_group_id, username, password, email) VALUES (?,?,?,?)";
    $hashedPassword = Security::hashPassword($this->password);

    $stmt = $this->executeQuery($sql, "ssss", [$this->userGroup, $this->username, $hashedPassword, $this->email]);
    $newUserId = $this->conn->insert_id;

    $stmt->close();

    return ResponseHttp::status201(['user_id' => $newUserId]);
  }

  // ------ MODIFY USER ------
  public function modifyUser($userId) {
    $fieldsToUpdate = [];
    $params = [];
    $types = '';

    if (!empty($this->userGroup)) {
      $fieldsToUpdate[] = 'user_group_id = ?';
      $params[] = $this->userGroup;
      $types .= 's';
    }

    if (!empty($this->username)) {
      $user = $this->getUserByUsername($this->username);
      if ($user && $user['id'] != $userId) {
        return ResponseHttp::status409("El username ya existe");
      }

      $fieldsToUpdate[] = 'username = ?';
      $params[] = $this->username;
      $types .= 's';
    }

    if (!empty($this->password)) {
      $hashedPassword = Security::hashPassword($this->password);
      $fieldsToUpdate[] = 'password = ?';
      $params[] = $hashedPassword;
      $types .= 's';
    }

    if (!empty($this->email)) {
      $fieldsToUpdate[] = 'email = ?';
      $params[] = $this->email;
      $types .= 's';
    }

    if (empty($fieldsToUpdate)) {
      return ResponseHttp::status400("No se proporcionaron campos para actualizar");
    }

    $sql = "UPDATE user SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
    $params[] = $userId;
    $types .= 'i';

    $stmt = $this->executeQuery($sql, $types, $params);

    if ($stmt->affected_rows > 0) {
      return ResponseHttp::status200(("Usuario actualizado correctamente"));
    } else {
      return ResponseHttp::status400(("No se pudo actualizar el usuario"));
    }

    $stmt->close();
  }

  // ------ LOGIN ------
  public function login() {
    $this->validateFields(['username', 'password']);
    $user = $this->getUserByUsername();
    if ($user === null || !Security::verifyPassword($this->password, $user['password'])) {
      return ResponseHttp::status400("Username o Password incorrecto");
    }

    $data = ['userId' => $user['id']];
    $jwt = Security::createJwt($data);

    $response = [
      'username' => $user['username'],
      'token' => $jwt
    ];

    return ResponseHttp::status200($response);
  }

  public function deleteUser($id) {
    $sql = "DELETE FROM user WHERE id=?";
    $stmt = $this->executeQuery($sql, "s", [$id]);

    if ($stmt->affected_rows > 0) {
      $stmt->close();
      return ResponseHttp::status200("Usuario eliminado");
    } else {
      $stmt->close();
      return ResponseHttp::status404("Usuario no encontrado");
    }
  }
}

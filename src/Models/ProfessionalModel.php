<?php

namespace App\Models;

use App\Config\ResponseHttp;

class ProfessionalModel extends Database {
  private $userModel;

  public function __construct(
    protected ?int $user_id = null,
    protected ?string $nombre = null,
    protected ?string $apellido = null,
    protected ?string $especialidad = null
  ) {
    parent::__construct();
    $this->userModel = new UserModel();
  }

  public function mapFromRequest(?array $data) {
    if (!$data) return;
    $this->user_id = $data[ProfessionalRequestModel::USER_ID] ?? null;
    $this->nombre = $data[ProfessionalRequestModel::NOMBRE] ?? null;
    $this->apellido = $data[ProfessionalRequestModel::APELLIDO] ?? null;
    $this->especialidad = $data[ProfessionalRequestModel::ESPECIALIDAD] ?? null;
  }

  public function getProfessionalById($id) {
    $sql = "SELECT * FROM profesionales WHERE id=?";
    $stmt = $this->executeQuery($sql, "i", [$id]);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  public function getProfessionalByUserId($userId) {
    $sql = "SELECT * FROM profesionales WHERE user_id=?";
    $stmt = $this->executeQuery($sql, "i", [$userId]);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  public function getProfessionalsByLastname() {
    $this->validateFields(['$apellido']);
    $sql = "SELECT * FROM profesionales WHERE apellido = ?";
    $stmt = $this->executeQuery($sql, "s", [$this->apellido]);
    $result = $stmt->get_result();

    $professionals = [];
    while ($row = $result->fetch_assoc()) {
      $professionals[] = $row;
    }

    return $professionals;
  }

  public function getAllProfessionals() {
    $sql = "SELECT * FROM profesionales";
    $stmt = $this->executeQuery($sql);
    $result = $stmt->get_result();
    $professionals = [];

    while ($row = $result->fetch_assoc()) {
      $professionals[] = $row;
    }
    return $professionals;
  }

  public function post() {
    $this->validateFields(['user_id', 'nombre', 'apellido', 'especialidad']);

    // find if user_id exists and it's in user group 2
    $user = $this->userModel->getUserById($this->user_id);
    if (!$user)  return ResponseHttp::status409("El user no existe");
    if ($user['user_group_id'] !== 2) {
      return ResponseHttp::status409("El user debe pertenecer al grupo 2");
    }

    if ($this->getProfessionalByUserId($this->user_id)) {
      return ResponseHttp::status409("El user id ya está en uso");
    }

    // post new user
    $sql = "INSERT INTO profesionales (user_id, nombre, apellido, especialidad) VALUES (?,?,?,?)";
    $stmt = $this->executeQuery($sql, "isss", [$this->user_id, $this->nombre, $this->apellido, $this->especialidad]);
    $newProfessionalId = $this->conn->insert_id;
    $stmt->close();

    return ResponseHttp::status201(['id' => $newProfessionalId]);
  }

  // ------ MODIFY USER ------
  public function modifyProfessional($id) {
    $fieldsToUpdate = [];
    $params = [];
    $types = '';

    if (!empty($this->user_id)) {
      if ($this->getProfessionalByUserId($this->user_id)) {
        return ResponseHttp::status409("user ya existe");
      }
      $fieldsToUpdate[] = 'user_id = ?';
      $params[] = $this->user_id;
      $types .= 'i';
    }

    if (!empty($this->nombre)) {
      $fieldsToUpdate[] = 'nombre = ?';
      $params[] = $this->nombre;
      $types .= 's';
    }

    if (!empty($this->apellido)) {
      $fieldsToUpdate[] = 'apellido = ?';
      $params[] = $this->apellido;
      $types .= 's';
    }

    if (!empty($this->especialidad)) {
      $fieldsToUpdate[] = 'especialidad = ?';
      $params[] = $this->especialidad;
      $types .= 's';
    }

    if (empty($fieldsToUpdate)) {
      return ResponseHttp::status400("No se proporcionaron campos para actualizar");
    }

    $sql = "UPDATE profesionales SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';

    $stmt = $this->executeQuery($sql, $types, $params);

    if ($stmt->affected_rows > 0) {
      $stmt->close();
      return ResponseHttp::status200(("Profesional actualizado correctamente"));
    } else {
      $stmt->close();
      return ResponseHttp::status400(("No se pudo actualizar el profesional"));
    }
  }

  public function deleteProfessional($id) {
    $sql = "DELETE FROM profesionales WHERE id=?";
    $stmt = $this->executeQuery($sql, "s", [$id]);

    if ($stmt->affected_rows > 0) {
      $stmt->close();
      return ResponseHttp::status200("Profesional eliminado");
    } else {
      $stmt->close();
      return ResponseHttp::status404("Profesional no encontrado");
    }
  }
}

<?php

namespace App\Models;

use App\Config\ResponseHttp;
use Exception;

class AppointmentModel extends Database {
  private $userModel = null;
  private $professionalModel = null;

  public function __construct(
    protected ?int $profesional_id = null,
    protected ?int $user_id = null,
    protected ?string $fecha = null,
    protected ?string $hora = null,
    protected ?string $estado = null
  ) {
    parent::__construct();
    $this->userModel = new UserModel();
    $this->professionalModel = new ProfessionalModel();
  }

  /**
   * Map request keys using AppointmentRequestModel consts
   */
  public function mapFromRequest(?array $data) {
    if (!$data) return;
    $this->profesional_id = $data[AppointmentRequestModel::PROFESSIONAL_ID] ?? null;
    $this->user_id = $data[AppointmentRequestModel::USER_ID] ?? null;
    $this->fecha = $data[AppointmentRequestModel::FECHA] ?? null;
    $this->hora = $data[AppointmentRequestModel::HORA] ?? null;
    $this->estado = $data[AppointmentRequestModel::ESTADO] ?? null;
  }

  private function validateDateTime() {
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $this->fecha)) {
      throw new Exception("Formato de fecha inválido, debe ser YYYY-MM-DD.");
    }

    if (!preg_match("/^\d{2}:\d{2}:\d{2}$/", $this->hora)) {
      throw new Exception("Formato de hora inválido, debe ser HH:MM:SS.");
    }

    $dateTime = strtotime($this->fecha . ' ' . $this->hora);
    if ($dateTime === false || $dateTime < time()) {
      throw new Exception("El turno no puede estar en el pasado.");
    }

    $timeParts = explode(':', $this->hora);
    $minutes = (int) $timeParts[1];

    if ($minutes % 30 !== 0) {
      throw new Exception("Los turnos deben ser en bloques de media hora (Ej.: 9:00 ó 9:30)");
    }
  }

  private function checkProfessionalAvailability() {
    $sql = "SELECT * FROM turnos WHERE profesional_id = ? AND fecha = ? AND hora = ?";
    $stmt = $this->executeQuery($sql, "iss", [$this->profesional_id, $this->fecha, $this->hora]);
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      throw new Exception("El profesional ya tiene turno en ese horario");
    }
  }

  private function validateStatus() {
    $validStates = ['pendiente', 'confirmado', 'cancelado'];

    if (!in_array($this->estado, $validStates)) {
      throw new Exception("Estado inválido, debe ser uno de: " . implode(" ,", $validStates));
    }
  }

  public function setUserId($userId) {
    $this->user_id = $userId;
    return;
  }

  public function getAllAppointments() {
    $sql = "SELECT * FROM turnos";
    $stmt = $this->executeQuery($sql);
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    return $data;
  }

  public function getAppointmentsByUser($userId) {
    $sql = "SELECT * FROM turnos WHERE user_id = ?";
    $stmt = $this->executeQuery($sql, "i", [$userId]);
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    return $data;
  }

  public function getAppointmentById($id) {
    $sql = "SELECT * FROM turnos WHERE id=?";
    $stmt = $this->executeQuery($sql, "i", [$id]);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  public function getAppointmentsByProfessional($professionalId) {
    $sql = "SELECT * FROM turnos WHERE profesional_id = ?";
    $stmt = $this->executeQuery($sql, "i", $professionalId);
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    return $data;
  }

  public function post() {
    $this->validateFields(['profesional_id', 'fecha', 'hora', 'estado']);
    $this->validateDateTime();
    $this->validateStatus();

    $user = $this->userModel->getUserById($this->user_id);
    if (!$user)  return ResponseHttp::status409("El user no existe");
    $professional = $this->professionalModel->getProfessionalById($this->profesional_id);
    if (!$professional)  return ResponseHttp::status409("El profesional no existe");

    $this->checkProfessionalAvailability();

    $sql = "INSERT INTO turnos (profesional_id, user_id, fecha, hora, estado) VALUES (?,?,?,?,?)";

    $stmt = $this->executeQuery($sql, "iisss", [
      $this->profesional_id,
      $this->user_id,
      $this->fecha,
      $this->hora,
      $this->estado
    ]);

    $newAppointmentId = $this->conn->insert_id;
    $stmt->close();

    return ResponseHttp::status201(['id' => $newAppointmentId]);
  }

  public function modify($id) {
    $fieldsToUpdate = [];
    $params = [];
    $types = '';

    $fieldsMap = [
      'profesional_id' => $this->profesional_id,
      'user_id' => $this->user_id,
      'fecha' => $this->fecha,
      'hora' => $this->hora,
      'estado' => $this->estado
    ];

    foreach ($fieldsMap as $field => $value) {
      if (!empty($value)) {
        $fieldsToUpdate[] = "$field = ?";
        $params[] = $value;

        $types .= is_int($value) ? 'i' : 's';
      }
    }

    if (empty($fieldsToUpdate)) {
      return ResponseHttp::status400("No se proporcionaron campos para actualizar");
    }

    $sql = "UPDATE turnos SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';

    $stmt = $this->executeQuery($sql, $types, $params);

    if ($stmt->affected_rows > 0) {
      return ResponseHttp::status200("Turno actualizado correctamente");
    } else {
      return ResponseHttp::status400("No se pudo actualizar el turno");
    }
  }

  public function delete($id) {
    $sql = "DELETE * FROM turnos WHERE id=?";
    $stmt = $this->executeQuery($sql, "i", $id);

    if ($stmt->affected_rows > 0) {
      return ResponseHttp::status200("Eliminado correctamente");
    } else {
      return ResponseHttp::status400("No se pudo eliminar. ¿Existe el id " . $id . "?");
    }
  }
}

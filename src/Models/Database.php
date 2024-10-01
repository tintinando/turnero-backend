<?php

namespace App\Models;

use App\Config\ResponseHttp;
use mysqli;

class Database {
  protected $conn = null;

  public function __construct() {
    if ($this->conn === null) {
      $host = $_ENV['DB_HOST'];
      $username = $_ENV['DB_USER'];
      $password = $_ENV['DB_PASSWORD'];
      $database = $_ENV['DB_NAME'];
      $this->conn = new mysqli($host, $username, $password, $database);

      if (mysqli_connect_errno() !== 0) {
        error_log("Database::getConnection => " . mysqli_connect_error());
        die(json_encode(ResponseHttp::status500("Error al conectar DB")));
      }
    }
  }

  protected function executeQuery(string $sql, string $types = "", array $params = []) {
    $stmt = $this->conn->prepare($sql);

    if ($stmt === false) {
      die(json_encode(ResponseHttp::status500("Error en la preparación " . $this->conn->error)));
    }

    if ($types && $params) {
      $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
      die(json_encode(ResponseHttp::status500("Error en la ejecución " . $stmt->error)));
    }

    return $stmt;
  }

  protected function validateFields(array $fields) {
    foreach ($fields as $field) {
      if ($this->{$field} === null) {
        echo json_encode(ResponseHttp::status400("Falta " . $field));
        exit;
      }
    }
  }
}

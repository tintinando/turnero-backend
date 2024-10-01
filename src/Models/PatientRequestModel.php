<?php

namespace App\Models;

/**
 * This class is for typing the API contract
 */
class ProfessionalRequestModel {
  // JSON keys
  public const USER_ID = 'userId';
  public const NOMBRE = 'nombre';
  public const APELLIDO = 'apellido';
  public const ESPECIALIDAD = 'especialidad';

  public ?int $userId;
  public ?string $nombre;
  public ?string $apellido;
  public ?string $especialidad;

  public function __construct(array $data) {
    $this->userId = $data[self::USER_ID] ?? null;
    $this->nombre = $data[self::NOMBRE] ?? null;
    $this->apellido = $data[self::APELLIDO] ?? null;
    $this->especialidad = $data[self::ESPECIALIDAD] ?? null;
  }
}

<?php

namespace App\Models;

/**
 * This class is for typing the API contract
 */
class AppointmentRequestModel {
  // JSON keys
  public const PROFESSIONAL_ID = 'profesionalId';
  public const USER_ID = 'userId';
  public const FECHA = 'fecha';
  public const HORA = 'hora';
  public const ESTADO = 'estado';
}

<?php

namespace App\Models;

/**
 * This class is for typing the API contract
 */
class UserRequestModel {
  // JSON keys
  public const USER_GROUP = 'userGroup';
  public const USERNAME = 'username';
  public const PASSWORD = 'password';
  public const EMAIL = 'email';

  public ?int $userGroup;
  public ?string $username;
  public ?string $password;
  public ?string $email;

  public function __construct(array $data) {
    $this->userGroup = $data[self::USER_GROUP] ?? null;
    $this->username = $data[self::USERNAME] ?? null;
    $this->password = $data[self::PASSWORD] ?? null;
    $this->email = $data[self::EMAIL] ?? null;
  }
}

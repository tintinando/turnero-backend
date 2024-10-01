<?php

namespace App\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Security {
  public static function hashPassword(string $password) {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  public static function verifyPassword(string $password, string $hash) {
    $passwordCorrect = password_verify($password, $hash);
    return $passwordCorrect;
  }

  public static function createJwt($data, $expirationInSeconds = 3600) {
    $payload = [
      'iat' => time(),
      'exp' => time() + $expirationInSeconds,
      'data' => $data
    ];

    $jwt = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');
    return $jwt;
  }

  public static function validateJwt(array $headers) {
    if (isset($headers['authorization'])) {
      $authorizationHeader = $headers['authorization'];
    } else if (isset($headers['Authorization'])) {
      $authorizationHeader = $headers['Authorization'];
    } else {
      die(json_encode(ResponseHttp::status401("Falta token")));
    }

    try {
      $jwt = explode(" ", $authorizationHeader)[1];
      $payload = JWT::decode($jwt, new Key($_ENV['JWT_KEY'], 'HS256'));
      return $payload;
    } catch (\Exception $e) {
      error_log("Security::validateJwt => Error en la validación de JWT");
      echo json_encode(ResponseHttp::status401("Token inválido o expirado"));
      exit();
    }
  }
}

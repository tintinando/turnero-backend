<?php

namespace App\Config;

class ResponseHttp {

  private static $message = [
    "status" => "",
    "data" => ""
  ];

  public static function status200($res = 'ok') {
    http_response_code(200);
    self::$message['status'] = 'ok';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status201($res = 'Created ok') {
    http_response_code(201);
    self::$message['status'] = 'ok';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status400($res = 'Bad query') {
    http_response_code(400);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status401($res = 'Unauthorized') {
    http_response_code(401);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status403($res = 'Forbidden') {
    http_response_code(403);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status404($res = 'Not found') {
    http_response_code(404);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status409($res = 'Conflict') {
    http_response_code(409);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;
    return self::$message;
  }

  public static function status500($res = 'Internal server error') {
    http_response_code(500);
    self::$message['status'] = 'error';
    self::$message['data'] = $res;

    $backtrace = debug_backtrace();
    $caller = isset($backtrace[1]) ? $backtrace[1] : null;

    if ($caller) {
      error_log("Error 500 en " . $caller['file'] . " línea " . $caller['line'] . " función " . $caller['function']);
    } else {
      error_log("Error 500 sin información adicional");
    }

    return self::$message;
  }
}

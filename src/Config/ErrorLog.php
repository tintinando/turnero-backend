<?php

namespace App\Config;

date_default_timezone_set('America/Argentina/Buenos_Aires');

class ErrorLog {
  public static function activateErrorLog() {

    error_reporting(E_ALL);
    ini_set("ignore_repeated_errors", TRUE);
    ini_set("display_errors", FALSE);
    ini_set("log_errors", 1);
    ini_set("error_log", dirname(__DIR__, 2) . "/Logs/php-error.log");
  }
}

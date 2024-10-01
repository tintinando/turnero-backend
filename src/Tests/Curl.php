<?php

namespace App\Tests;

class Curl {

  public static function buildHeaders($token = null) {
    $headers = ['Content-Type:application/json'];
    if ($token) {
      $headers[] = 'Authorization: Bearer ' . $token;
    }
    return $headers;
  }

  /**
   * Make Get query using cURL
   * 
   * @param string $url The url
   * @param string|null $token JWT if exists
   * @return array [string|null, int] Array with response and http code
   */
  public static function makeGetRequest($url, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, self::buildHeaders($token));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
  }

  /**
   * Make Post query using cURL
   * 
   * @param string $url The url
   * @param array $postData Associative array with body to post
   * @param string|null $token JWT if exists
   * @return array [string|null, int] Array with response and http code
   */
  public static function makePostRequest($url, $postData = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, self::buildHeaders($token));
    if ($postData) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
  }

  /**
   * Make Put query using cURL
   * 
   * @param string $url The url
   * @param array $postData Associative array with body to post
   * @param string|null $token JWT if exists
   * @return array [string|null, int] Array with response and http code
   */
  public static function makePutRequest($url, $postData, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, self::buildHeaders($token));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
  }

  /**
   * Make Delete query using cURL
   * 
   * @param string $url The url
   * @param array $postData Associative array with body to post
   * @param string|null $token JWT if exists
   * @return array [string|null, int] Array with response and http code
   */
  public static function makeDeleteRequest($url, $postData, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, self::buildHeaders($token));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
  }
}

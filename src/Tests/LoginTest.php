<?php

use App\Models\UserRequestModel;
use App\Tests\Curl;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase {
  protected function setUp(): void {
  }

  private function loginAndGetToken($username, $password) {
    $url = "http://localhost/turnero/auth/login";
    $postData = [
      UserRequestModel::USERNAME => $username,
      UserRequestModel::PASSWORD => $password
    ];

    $response = Curl::makePostRequest($url, $postData)[0];
    $data = json_decode($response, true);

    return $data['data']['token'] ?? null;
  }

  public function testLogin() {
    $url = "http://localhost/turnero/auth/login";
    $postData = [
      'username' => 'admin',
      'password' => '123456'
    ];

    $response = Curl::makePostRequest($url, $postData)[0];

    $data = json_decode($response, true);

    // verify that response has keys
    $this->assertArrayHasKey('username', $data['data'], $response);
    $this->assertArrayHasKey('token', $data['data']);

    // verify that responsee has content
    $this->assertEquals('admin', $data['data']['username']);
    $this->assertNotEmpty($data['data']['token']);
  }

  public function testLoginWithInvalidCredentials() {
    $url = "http://localhost/turnero/auth/login";
    $postData = [
      'username' => 'admin',
      'password' => '123457' //bad key
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData);

    $this->assertEquals(400, $httpCode, $response);
  }

  public function testGetProfile() {
    $token = $this->loginAndGetToken('admin', '123456');
    $url = "http://localhost/turnero/auth/profile";

    [$response, $httpCode] = Curl::makeGetRequest($url, $token);
    $data = json_decode($response, true);

    $this->assertArrayHasKey('id', $data['data'], $response);
    $this->assertArrayHasKey('username', $data['data'], $response);
  }

  public function testGuestCannotGetProfile() {
    $url = "http://localhost/turnero/auth/profile";

    [$response, $httpCode] = Curl::makeGetRequest($url);

    $this->assertEquals(401, $httpCode, $response);
  }
}

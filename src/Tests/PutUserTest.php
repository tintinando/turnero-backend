<?php

use App\Models\UserModel;
use App\Models\UserRequestModel;
use App\Tests\Curl;
use PHPUnit\Framework\TestCase;

class PutUserTest extends TestCase {
  private $adminToken;
  private $createdUserId;

  protected function setUp(): void {
    $this->adminToken = $this->loginAndGetToken('admin', '123456');

    // create user
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '3',
      UserRequestModel::USERNAME => 'userForModify',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => ''
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData);
    $data = json_decode($response, true);

    $this->createdUserId = $data['data']['user_id'];
  }

  protected function tearDown(): void {
    $userModel = new UserModel();
    $userModel->deleteUser($this->createdUserId);
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

  public function testGuestCannotModifyUser() {
    // http://localhost/turnero/users/{userForModify id}
    $url = 'http://localhost/turnero/users/' . $this->createdUserId;

    $putData = [
      UserRequestModel::PASSWORD => '123457'
    ];

    [$response, $httpCode] = Curl::makePutRequest($url, $putData);

    $this->assertEquals(401, $httpCode, $response);
  }

  public function testOwnerCanModifyUser() {
    $token = $this->loginAndGetToken('userForModify', '123456');
    $this->assertNotNull($token, "Falla al querer iniciar sesión en userForModify");

    $url = 'http://localhost/turnero/users/' . $this->createdUserId;
    $putData = [UserRequestModel::EMAIL => 'a@b.com'];
    [$response, $httpCode] = Curl::makePutRequest($url, $putData, $token);

    $this->assertEquals(200, $httpCode, $response);
  }

  public function testAdminCanModifyUser() {
    $url = 'http://localhost/turnero/users/' . $this->createdUserId;
    $putData = [UserRequestModel::EMAIL => 'a@d.com'];
    [$response, $httpCode] = Curl::makePutRequest($url, $putData, $this->adminToken);

    $this->assertEquals(200, $httpCode, $url);
  }

  public function testOwnerCannotElevatePermission() {
    $token = $this->loginAndGetToken('userForModify', '123456');
    $url = 'http://localhost/turnero/users/' . $this->createdUserId;
    $putData = [UserRequestModel::USER_GROUP => 1];
    [$response, $httpCode] = Curl::makePutRequest($url, $putData, $token);

    $this->assertEquals(401, $httpCode, $response);
  }
}

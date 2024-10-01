<?php

use App\Models\UserModel;
use App\Models\UserRequestModel;
use App\Tests\Curl;
use PHPUnit\Framework\TestCase;

class PostUserTest extends TestCase {
  private $adminToken;
  private $userToken;
  private $userModel;
  private $createdUserId;
  private $createdExistingUser;

  protected function setUp(): void {
    $this->adminToken = $this->loginAndGetToken('admin', '123456');
    $this->userToken = $this->loginAndGetToken('paciente1', '123456');
    $this->userModel = new UserModel();

    // create existingUser
    $createUserModel = new UserModel(3, 'existingUser', '123456', 'a@b.com');
    $createdUser = $createUserModel->post();
    $this->createdExistingUser = $createdUser['data']['user_id'];
  }

  protected function tearDown(): void {
    $this->userModel->deleteUser($this->createdExistingUser);
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

  public function testGetToken() {
    $this->assertNotNull($this->adminToken);
    $this->assertNotNull($this->userToken);
  }

  public function testGuestCanPostUser() {
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '3',
      UserRequestModel::USERNAME => 'newCommonUser',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => ''
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData);

    $this->assertEquals(201, $httpCode, $response);

    // delete created user
    $data = json_decode($response, true);
    $this->createdUserId = $data['data']['user_id'] ?? null;

    $this->assertNotNull($this->createdUserId);

    if ($this->createdUserId) {
      $deleteResult = $this->userModel->deleteUser($this->createdUserId);
      $this->assertEquals("Usuario eliminado", $deleteResult['data']);
    }
  }

  public function testAdminCanPostPrivilegiedUser() {
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '1',
      UserRequestModel::USERNAME => 'newUser',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => 'a@b.com'
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData, $this->adminToken);

    // check if response is 201
    $this->assertEquals(201, $httpCode, $response);

    // delete created user
    $data = json_decode($response, true);
    $this->createdUserId = $data['data']['user_id'] ?? null;

    $this->assertNotNull($this->createdUserId, json_encode($data));

    if ($this->createdUserId) {
      $deleteResult = $this->userModel->deleteUser($this->createdUserId);
      $this->assertEquals("Usuario eliminado", $deleteResult['data']);
    }
  }

  public function testUserCannotPostPrivilegiedUser() {
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '1',
      UserRequestModel::USERNAME => 'newUser2',
      UserRequestModel::PASSWORD => '123456'
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData, $this->userToken);

    // check if response is 401
    $this->assertEquals(401, $httpCode, $response);
  }

  public function testCannotCreateExistentingUser() {
    $url = 'http:/localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '3',
      UserRequestModel::USERNAME => 'existingUser',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => 'existing@user.com'
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData);

    $this->assertEquals(409, $httpCode, "No se tiene que poder crear existingUser ¿Existe en la DB?", $response);
  }

  public function testGuestCannotDeleteUser() {
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '3',
      UserRequestModel::USERNAME => 'userForDelete',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => ''
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData);
    $data = json_decode($response, true);

    $deletePostData = [
      'id' => $data['data']['user_id']
    ];

    [$response, $httpCode] = Curl::makeDeleteRequest($url, $deletePostData);

    $this->assertEquals(401, $httpCode, $response);
  }

  public function testOwnerCanDeleteUser() {
    $url = 'http://localhost/turnero/users';
    $token = $this->loginAndGetToken('userForDelete', '123456');

    $userModel = new UserModel(username: 'userForDelete');
    $user = $userModel->getUserByUsername();

    $deletePostData = [
      'id' => $user['id']
    ];

    [$response, $httpCode] = Curl::makeDeleteRequest($url, $deletePostData, $token);

    $this->assertEquals(200, $httpCode, $response);
  }

  public function testUserCannotDeleteAdmin() {
    $url = 'http://localhost/turnero/users';
    $postData = [
      UserRequestModel::USER_GROUP => '1',
      UserRequestModel::USERNAME => 'adminForDelete',
      UserRequestModel::PASSWORD => '123456',
      UserRequestModel::EMAIL => ''
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData, $this->adminToken);
    $data = json_decode($response, true);

    $deletePostData = [
      'id' => $data['data']['user_id']
    ];

    [$response, $httpCode] = Curl::makeDeleteRequest($url, $deletePostData, $this->userToken);

    $this->assertEquals(401, $httpCode, $response);
  }

  public function testAdminCanDeleteAdmin() {
    $url = 'http://localhost/turnero/users';
    $userModel = new UserModel(username: 'adminForDelete');
    $user = $userModel->getUserByUsername();

    $deletePostData = [
      'id' => $user['id']
    ];

    [$response, $httpCode] = Curl::makeDeleteRequest($url, $deletePostData, $this->adminToken);

    $this->assertEquals(200, $httpCode, $response);
  }

  public function testCannotDeleteNonExistentUser() {
    $url = 'http://localhost/turnero/users';

    // ID de usuario que no existe
    $deletePostData = [
      'id' => 999999 // Un ID que seguramente no existe en la DB
    ];

    // Hacer la petición DELETE con el token de administrador
    [$response, $httpCode] = Curl::makeDeleteRequest($url, $deletePostData, $this->adminToken);

    // Verificar que el código de estado es 404
    $this->assertEquals(404, $httpCode, $response);

    // Verificar que el mensaje de error es el esperado
    $data = json_decode($response, true);
    $this->assertEquals('error', $data['status']);
    $this->assertEquals('Id de usuario incorrecto', $data['data']);
  }
}

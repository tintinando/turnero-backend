<?php

namespace App\Tests;

use App\Models\ProfessionalModel;
use App\Models\ProfessionalRequestModel;
use App\Models\UserModel;
use App\Models\UserRequestModel;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ProfessionalTest extends TestCase {
  private static $adminToken;
  private static $userToken;
  private static $userModel;
  private static $createdExistingUser;

  public static function setUpBeforeClass(): void {
    self::$adminToken = self::loginAndGetToken('admin', '123456');
    self::$userToken = self::loginAndGetToken('paciente1', '123456');

    self::$userModel = new UserModel();
    // Crear un usuario en grupo 2
    $createUserModel = new UserModel(2, 'testProfessionalUser', '123456', '');
    $createdUser = $createUserModel->post();
    self::$createdExistingUser = $createdUser['data']['user_id'];
  }

  public static function tearDownAfterClass(): void {
    if (self::$createdExistingUser) {
      self::$userModel->deleteUser(self::$createdExistingUser);
    }
  }

  private static function loginAndGetToken($username, $password) {
    $url = "http://localhost/turnero/auth/login";
    $postData = [
      UserRequestModel::USERNAME => $username,
      UserRequestModel::PASSWORD => $password
    ];

    $response = Curl::makePostRequest($url, $postData)[0];
    $data = json_decode($response, true);

    return $data['data']['token'] ?? null;
  }

  public function testUserCannotPostProfessional() {
    $url = "http://localhost/turnero/professionals";
    $postData = [
      ProfessionalRequestModel::USER_ID => self::$createdExistingUser,
      ProfessionalRequestModel::NOMBRE => "Ramon",
      ProfessionalRequestModel::APELLIDO => "Gonzatest",
      ProfessionalRequestModel::ESPECIALIDAD => "Clinica"
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData, self::$userToken);
    $this->assertEquals(401, $httpCode, $response);
  }

  public function testAdminCanPostProfessional(): int {
    $url = "http://localhost/turnero/professionals";
    $postData = [
      ProfessionalRequestModel::USER_ID => self::$createdExistingUser,
      ProfessionalRequestModel::NOMBRE => "Ramon",
      ProfessionalRequestModel::APELLIDO => "Gonzatest",
      ProfessionalRequestModel::ESPECIALIDAD => "Clinica"
    ];

    [$response, $httpCode] = Curl::makePostRequest($url, $postData, self::$adminToken);

    $data = json_decode($response, true);
    $createdProfessionalId = $data['data']['id'];

    $this->assertEquals(201, $httpCode, $response);
    return $createdProfessionalId;
  }

  #[Depends('testAdminCanPostProfessional')]
  public function testAdminCanModifyProfessional(int $professionalId) {
    $url = "http://localhost/turnero/professionals/" . $professionalId;
    $postData = [
      ProfessionalRequestModel::ESPECIALIDAD => 'Cirujano'
    ];

    [$response, $httpCode] = Curl::makePutRequest($url, $postData, self::$adminToken);
    $data = json_decode($response, true);

    $this->assertEquals(200, $httpCode, $response);
    $this->assertEquals("Profesional actualizado correctamente", $data['data'], $response);
  }

  #[Depends('testAdminCanPostProfessional')]
  public function testOwnerCanModify($professionalId) {
    $token = self::loginAndGetToken('testProfessionalUser', '123456');
    $url = "http://localhost/turnero/professionals/" . $professionalId;
    $postData = [
      ProfessionalRequestModel::ESPECIALIDAD => 'Psicologo'
    ];

    [$response, $httpCode] = Curl::makePutRequest($url, $postData, $token);
    $data = json_decode($response, true);

    $this->assertEquals(200, $httpCode, $response);
    $this->assertEquals("Profesional actualizado correctamente", $data['data'], $response);
  }

  #[Depends('testAdminCanPostProfessional')]
  public function testProfessionalCannotModifyOther($professionalId) {
    $model = new UserModel(2, "professionalCannotModify", "123456", "");
    $otherProfessional = $model->post();

    $token = self::loginAndGetToken('professionalCannotModify', '123456');
    $url = "http://localhost/turnero/professionals/" . $professionalId;
    $postData = [
      ProfessionalRequestModel::ESPECIALIDAD => 'Psicologo'
    ];

    [$response, $httpCode] = Curl::makePutRequest($url, $postData, $token);

    $this->assertEquals(403, $httpCode, $response);
    $model->deleteUser($otherProfessional['data']['user_id']);
  }
}

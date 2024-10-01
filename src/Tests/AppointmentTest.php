<?php

use App\Models\AppointmentRequestModel;
use App\Models\ProfessionalModel;
use App\Models\UserModel;
use App\Models\UserRequestModel;
use App\Tests\Curl;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase {
  private static $adminToken;
  private static $userToken;
  private static $userId;
  private static $adminId;
  private static $professionalUserId;
  private static $professionalId;
  private const BASE_URL = "http://localhost/turnero/appointments";

  public static function setUpBeforeClass(): void {
    $userModel = new UserModel(3, 'userForAppointmentTest', '123456', '');
    self::$userId = $userModel->post()['data']['user_id'];
    $userModel = new UserModel(1, 'adminForAppointmentTest', '123456', '');
    self::$adminId = $userModel->post()['data']['user_id'];
    $userModel = new UserModel(2, 'professinalUserForAppointmentTest', '123456', '');
    self::$professionalUserId = $userModel->post()['data']['user_id'];
    self::$adminToken = self::loginAndGetToken('adminForAppointmentTest', '123456');
    self::$userToken = self::loginAndGetToken('userForAppointmentTest', '123456');
    $professionalModel = new ProfessionalModel(self::$professionalUserId, 'ProfessionalForAppointmentTest', 'Gomez', 'clinico');
    self::$professionalId = $professionalModel->post()['data']['id'];
  }

  public static function tearDownAfterClass(): void {
    $userModel = new UserModel();
    $userModel->deleteUser(self::$adminId);
    $userModel->deleteUser(self::$userId);
    $userModel->deleteUser(self::$professionalUserId);
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

  /** ------ GET ------ */
  public function testGuestCannotGetAllAppointments() {
    [$response, $httpcode] = Curl::makeGetRequest(self::BASE_URL);

    $this->assertEquals(401, $httpcode, $response);
  }

  public function testUserCannotGetAllAppointments() {
    [$response, $httpcode] = Curl::makeGetRequest(self::BASE_URL, self::$userToken);

    $this->assertEquals(401, $httpcode, $response);
  }

  public function testAdminCanGetAllAppointments() {
    [$response, $httpcode] = Curl::makeGetRequest(self::BASE_URL, self::$adminToken);

    $data = json_decode($response, true);

    $this->assertEquals(200, $httpcode, $response);
    $this->assertIsArray($data, "Response no es un array " . PHP_EOL . $response);
    $this->assertArrayHasKey('id', $data[0], "La clave 'id' no está presente en el array");
  }

  /** ------ POST ------ */
  public function testOwnerCanPostAppointment() {
    $postData = [
      AppointmentRequestModel::PROFESSIONAL_ID => self::$professionalId,
      AppointmentRequestModel::USER_ID => self::$userId,
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('+1 day')),
      AppointmentRequestModel::HORA => "13:30:00",
      AppointmentRequestModel::ESTADO => "pendiente"
    ];

    [$response, $httpCode] = Curl::makePostRequest(self::BASE_URL, $postData, self::$userToken);
    $data = json_decode($response, true);

    $this->assertEquals(201, $httpCode, $response);
    return $data['data']['id'];
  }

  public function testCannotPostAppointmentInPast() {
    $postData = [
      AppointmentRequestModel::PROFESSIONAL_ID => self::$professionalId,
      AppointmentRequestModel::USER_ID => self::$userId,
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('-1 day')),
      AppointmentRequestModel::HORA => "13:30:00",
      AppointmentRequestModel::ESTADO => "pendiente"
    ];

    [$response, $httpCode] = Curl::makePostRequest(self::BASE_URL, $postData, self::$userToken);

    $this->assertEquals(400, $httpCode, $response);
    $data = json_decode($response, true);
    $this->assertIsArray($data, $response);
    $this->assertEquals("El turno no puede estar en el pasado.", $data['data'], $response);
  }

  public function testCannotPostAnotherUser() {
    $token = self::loginAndGetToken('paciente1', '123456');

    $postData = [
      AppointmentRequestModel::PROFESSIONAL_ID => self::$professionalId,
      AppointmentRequestModel::USER_ID => self::$userId,
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('+1 day')),
      AppointmentRequestModel::HORA => "13:30:00",
      AppointmentRequestModel::ESTADO => "pendiente"
    ];

    [$response, $httpCode] = Curl::makePostRequest(self::BASE_URL, $postData, $token);
    $this->assertEquals(401, $httpCode, $response);
  }

  public function testAdminCanPostAppointment() {
    $postData = [
      AppointmentRequestModel::PROFESSIONAL_ID => self::$professionalId,
      AppointmentRequestModel::USER_ID => self::$userId,
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('+2 day')),
      AppointmentRequestModel::HORA => "13:30:00",
      AppointmentRequestModel::ESTADO => "pendiente"
    ];

    [$response, $httpCode] = Curl::makePostRequest(self::BASE_URL, $postData, self::$adminToken);
    $this->assertEquals(201, $httpCode, $response);
  }

  #[Depends('testOwnerCanPostAppointment')]
  public function testOwnerCanModifyAppointment(int $appointmentId) {
    $putData = [
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('+3 day'))
    ];
    $modifyUrl = self::BASE_URL . "/$appointmentId";
    [$response, $httpCode] = Curl::makePutRequest($modifyUrl, $putData, self::$userToken);

    $this->assertEquals(200, $httpCode, $response);
    $data = json_decode($response, true);
    $this->assertEquals("Turno actualizado correctamente", $data['data'], $response);
  }

  #[Depends('testOwnerCanPostAppointment')]
  public function testOwnerCanotModifyStatus(int $appointmentId) {
    $putData = [
      AppointmentRequestModel::ESTADO => "confirmado"
    ];
    $modifyUrl = self::BASE_URL . "/$appointmentId";
    [$response, $httpCode] = Curl::makePutRequest($modifyUrl, $putData, self::$userToken);

    $this->assertEquals(403, $httpCode, $response);
  }

  #[Depends('testOwnerCanPostAppointment')]
  public function testAdminCanModifyStatus(int $appointmentId) {
    $putData = [
      AppointmentRequestModel::ESTADO => "confirmado"
    ];
    $modifyUrl = self::BASE_URL . "/$appointmentId";
    [$response, $httpCode] = Curl::makePutRequest($modifyUrl, $putData, self::$adminToken);

    $this->assertEquals(200, $httpCode, $response);
    $data = json_decode($response, true);
    $this->assertEquals("Turno actualizado correctamente", $data['data'], $response);
  }

  #[Depends('testOwnerCanPostAppointment')]
  public function testAnotherUserCannotModifyAppointment(int $appointmentId) {
    $token = self::loginAndGetToken('paciente1', '123456');
    $putData = [
      AppointmentRequestModel::FECHA => date('Y-m-d', strtotime('+3 day'))
    ];
    $modifyUrl = self::BASE_URL . "/$appointmentId";
    [$response, $httpCode] = Curl::makePutRequest($modifyUrl, $putData, $token);

    $this->assertEquals(403, $httpCode, $response);
  }
}

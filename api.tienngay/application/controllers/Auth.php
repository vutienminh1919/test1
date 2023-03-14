<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Auth extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
		$this->load->model('time_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());

	}

	private $createdAt;

	public function index_get()
	{
		$response = array(
			'status' => true,
			'message' => 'Connected'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	//generate random password
	private function generateRandomPassword($length = 10)
	{
		$alphabets = range('A', 'Z');
		$numbers = range('0', '9');
		$final_array = array_merge($alphabets, $numbers);
		$password = '';
		while ($length--) {
			$key = array_rand($final_array);
			$password .= $final_array[$key];
		}
		return $password;
	}

	public function signup_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => "Email không đúng định dạng"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		if (empty($data['fullname'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 2,
				'message' => "Họ và tên là trường không được để rỗng"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}

		if (strlen($data['password']) < 8) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 5,
				'message' => "Mật khẩu phải lớn hơn 8 ký tự"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}

		// Kiểm tra captchar
//        $this->form_validation->set_rules('googleTokenCaptcha', 'G-TokenCaptcha', 'required|max_length[700]|xss_clean|regex_match[/^[a-zA-Z0-9\s _-]+$/]|callback_verifyGoogleCaptcha');
//        if ($this->form_validation->run() == true) { // Chú ý cái này sau này live thì chỉnh lại
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                "code"=>127,
//                'message' => $this->message_model->msg(127, $data['lang'])
//            );
//            $this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
//            return false;
//        }
		// Sinh mã referal của id đó (lấy 8 số đầu của id)
		$signup = $this->user_model->signup($data['email']);
		if ($signup == true) {
			//Start insert user
			$email = $this->input->post('email');
			$username = $this->input->post('username');
			$password = $this->input->post('password');
			$singup_token = $this->generateRandomPassword(8);
			$hash_password = password_hash($password, PASSWORD_BCRYPT);
			$data['time'] = $this->time_model->convertDatetimeToTimestamp(new DateTime());
			$result = $this->user_model->insertReturnId(array(
				'email' => trim(strtolower($email)),
				'username' => trim(strtolower($username)),
				'password' => $hash_password,
				'full_name' => trim($data['fullname']),
				'created_at' => $data['time'],
				'status' => 'new',
				'status_login' => false,
				'token_active' => $singup_token,
				'link_active' => false,
				'created_by' => "user"
			));
			$url = $this->config->item('cpanel_url') . 'auth/activeAccount?token=' . $singup_token;
			$this->user_model->update(
				array('_id' => $result),
				array('link_active' => $url)
			);
			//Send email
			$data_post = array(
				'code' => "vfc_register",
				'email' => $email,
				'url' => $url,
				'full_name' => trim($data['fullname']),
				'API_KEY' => $this->config->item('API_KEY')
			);
			$this->user_model->send_Email($data_post);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				"code" => 7,
				'message' => "đăng ký thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 6,
				'message' => "Tài khoản đã tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}

	public function active_account_post()
	{
		$data = $this->input->post();
		$token = !empty($data['token']) ? $data['token'] : "";
		if (empty($token)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 8,
				'message' => "token không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}

		$result = $this->user_model->activate($token);
		if ($result) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				"code" => 9,
				'message' => "kích hoạt thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 10,
				'message' => "kích hoạt không thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}

	}

	public function signin_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => "Email không đươc để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
//        $this->form_validation->set_rules('googleTokenCaptcha', 'G-TokenCaptcha', 'required|max_length[700]|xss_clean|regex_match[/^[a-zA-Z0-9\s _-]+$/]|callback_verifyGoogleCaptcha');
//        if ($this->form_validation->run() == true) { // Chú ý cái này sau này live thì chỉnh lại
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                "code"=>127,
//                'message' => $this->message_model->msg(127, $data['lang'])
//            );
//            $this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
//            return false;
//        }
		$input_email = $data["email"];
		if (filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
			$input_email = strtolower($input_email);
		}
		$type_login = !empty($data['type']) ? $data['type'] : 1;
		$signin = $this->user_model->signin($input_email, $data['password']);
		if ($signin == true) {
			$user = $this->user_model->getAppUserInfo();
			if (!empty($user[0]->status_login) && $user[0]->status_login != true) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 11,
					'message' => "Tài khoản chưa được kích hoạt"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return;
			}
			if (!empty($user[0]->status) && $user[0]->status != 'active') {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 14,
					'message' => "Tài khoản đang tạm bị khóa"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return;
			}
			$full_name = !empty($user[0]->full_name) ? $user[0]->full_name : "";
			$email = !empty($user[0]->email) ? $user[0]->email : "";
			$username = !empty($user[0]->username) ? $user[0]->username : "";

			$token = array();
			$token['id'] = (string)$user[0]->_id;
			$token['email'] = $email;
			$token['username'] = $username;
			$token['full_name'] = $full_name;
			$token['time'] = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
			$response['token'] = Authorization::generateToken($token);
			if ($type_login == 1) {
				$this->user_model->update(array('_id' => $user[0]->_id), array('token_web' => $response['token']));
			}
			if ($type_login == 2) {
				$this->user_model->update(array('_id' => $user[0]->_id), array('token_app' => $response['token']));
			}
			// $$user[0]['id'] = (string)$user[0]->_id;
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'token' => $response['token'],
				'data' => $user[0],
				"code" => 15,
				'message' => "đăng nhập thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 16,
				'message' => "Tài khoản không chính xác"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}

	public function login_by_google_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => "Email không đúng định dạng"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		$user = $this->user_model->findOne(array('email' => $data['email']));
		//chưa tồn tại thì tạo tài khoản => login
		if (!empty($user)) {
			$email = $data['email'];
			$fullname = !empty($data['fullname']) ? trim($data['fullname']) : "";
			$password = $this->generateRandomPassword(8);
			$hash_password = password_hash($password, PASSWORD_BCRYPT);
			$data['time'] = $this->time_model->convertDatetimeToTimestamp(new DateTime());
			$result = $this->user_model->insertReturnId(array(
				'email' => trim(strtolower($email)),
				'password' => $hash_password,
				'full_name' => $fullname,
				'created_at' => $data['time'],
				'status' => 'active',
				'status_login' => true,
				'token_active' => "",
				'link_active' => false,
				'created_by' => "google.com"
			));
			$userData = $this->user_model->findOne(array('email' => $data['email']));
			//Send email
			$data_post = array(
				'code' => "ticki_create_account",
				'email' => $email,
				'url' => "",
				'API_KEY' => $this->config->item('API_KEY')
			);
			$this->user_model->send_Email($data_post);
			//login
			$token = array();
			$token['id'] = (string)$userData['_id'];
			$token['email'] = $userData['email'];
			$token['full_name'] = (string)$userData['full_name'];
			$token['time'] = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
			$response['token'] = Authorization::generateToken($token);
			$this->user_model->update(array('_id' => new MongoDB\BSON\ObjectId($userData['_id'])), array('token' => $response['token']));
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'token' => $response['token'],
				"code" => 15,
				'message' => "đăng nhập thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			//check tài khoản tồn tại  => login luôn
			if ($user['status'] == 'block') {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 17,
					'message' => "Tài khoản đang bị khóa"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			} else {
				$token = array();
				$token['id'] = (string)$user['_id'];
				$token['email'] = $user['email'];
				$token['full_name'] = (string)$user['full_name'];
				$token['time'] = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
				$response['token'] = Authorization::generateToken($token);
				$this->user_model->update(array('_id' => new MongoDB\BSON\ObjectId($user['_id'])), array('token' => $response['token'], 'status_login' => true));
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'token' => $response['token'],
					"code" => 15,
					'message' => "đăng nhập thành công"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			}

		}
	}

	public function login_by_facebook_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => "Email không đúng định dạng"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		$id_fblogin = !empty($data['id_fblogin']) ? trim($data['id_fblogin']) : "";
		if (empty($id_fblogin)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 18,
				'message' => "id fblogin không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		$dataWhere = array(
			"email" => $data['email'],
			"id_fblogin" => $id_fblogin
		);
		$user = $this->user_model->where_or($dataWhere);
		if (!empty($user)) {
			$email = $data['email'];
			$fullname = !empty($data['fullname']) ? trim($data['fullname']) : "";
			$password = $this->generateRandomPassword(8);
			$hash_password = password_hash($password, PASSWORD_BCRYPT);
			$data['time'] = $this->time_model->convertDatetimeToTimestamp(new DateTime());
			$result = $this->user_model->insertReturnId(array(
				'email' => trim(strtolower($email)),
				'password' => $hash_password,
				'full_name' => $fullname,
				'created_at' => $data['time'],
				'status' => 'active',
				'status_login' => true,
				'token_active' => "",
				'link_active' => false,
				'id_fblogin' => $id_fblogin,
				'created_by' => "facebook.com"
			));
			$userData = $this->user_model->findOne(array('email' => $data['email']));
			//Send email
			$data_post = array(
				'code' => "ticki_create_account",
				'email' => $email,
				'url' => "",
				'API_KEY' => $this->config->item('API_KEY')
			);
			$this->user_model->send_Email($data_post);
			//login
			$token = array();
			$token['id'] = (string)$userData['_id'];
			$token['email'] = $userData['email'];
			$token['full_name'] = (string)$userData['full_name'];
			$token['time'] = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
			$response['token'] = Authorization::generateToken($token);
			$this->user_model->update(array('_id' => new MongoDB\BSON\ObjectId($userData['_id'])), array('token' => $response['token']));
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'token' => $response['token'],
				"code" => 15,
				'message' => "đăng nhập thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			//check tài khoản tồn tại  => login luôn
			if ($user['status'] == 'block') {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 17,
					'message' => "Tài khoản đang bị khóa"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			} else {
				$token = array();
				$token['id'] = (string)$user['_id'];
				$token['email'] = $user['email'];
				$token['full_name'] = (string)$user['full_name'];
				$token['time'] = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
				$response['token'] = Authorization::generateToken($token);
				$this->user_model->update(array('_id' => new MongoDB\BSON\ObjectId($user['_id'])), array('token' => $response['token'], 'status_login' => true));
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'token' => $response['token'],
					"code" => 15,
					'message' => "đăng nhập thành công"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			}

		}

	}

	public function reset_password_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => "Email không đúng định dạng"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		$user = $this->user_model->findOne(array('email' => $data['email']));
		if (empty($user)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 18,
				'message' => 'Không tồn tại tài khoản này'
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return;
		}
		//Send email
		$now = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		// Time expired is one hour
		$time_expired_token = $now + 360000;
		$hash_resettoken = $this->generateRandomPassword(8);
		$this->user_model->update(
			array('_id' => new MongoDB\BSON\ObjectId($user['_id'])),
			array(
				'token_reset_password' => $hash_resettoken,
				'time_token_exprired_reset_password' => $time_expired_token
			)
		);
		$url = $this->config->item('root_url') . 'user/new_pasword/' . $hash_resettoken . '/' . $user['_id'];
		//Send email
		$data_post = array(
			'code' => "ticki_create_account",
			'email' => $data['email'],
			'url' => $url,
			'API_KEY' => $this->config->item('API_KEY')
		);
		$this->user_model->send_Email($data_post);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			"code" => 19,
			'message' => "Email để thiết lập lại mật khẩu gửi thành công! Vui lòng kiểm tra email của bạn để đặt lại mật khẩu"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function new_password_post()
	{
		$data = $this->input->post();
		$user = $this->user_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($data['id'])));
		$timeExpried = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		if ($user != false && $user['time_token_exprired_reset_password'] > $timeExpried) {
			if (strlen($data['password']) < 8) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 5,
					'message' => "Mật khẩu phải lớn hơn 8 ký tự"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return false;
			}
			if ($data['password'] !== $data['re_password']) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 20,
					'message' => "Nhập lại mật khẩu không trùng với mật khẩu mới"
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return false;
			}
			$hash_password = password_hash($data['password'], PASSWORD_BCRYPT);
			$this->user_model->update(
				array('_id' => new MongoDB\BSON\ObjectId($data['id'])),
				array('token_reset_password' => '', 'password' => $hash_password)
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				"code" => 21,
				'message' => "Đổi mật khẩu thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 22,
				'message' => "Thời gian đổi mật khẩu đã hết hạn"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}


	public function google_authenticator_post()
	{
		$data = $this->input->post();
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		if ($this->form_validation->run() == FALSE) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 1,
				'message' => $this->message_model->msg(1, $data['lang'])
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		}
		$user = $this->user_model->findOne(array('email' => $data['email']));
		if ($user != null) {
			if ($user['status_login'] != true) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 3,
					'message' => $this->message_model->msg(3, $data['lang'])
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return;
			}
			if ($user['status'] != 'active') {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					"code" => 4,
					'message' => $this->message_model->msg(4, $data['lang'])
				);
				$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
				return;
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'enable_authenticator' => $user['enable_authenticator'],
				"code" => 131,
				'message' => $this->message_model->msg(131, $data['lang'])
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 132,
				'message' => $this->message_model->msg(132, $data['lang'])
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}

	public function verifyGoogleCaptcha($googleTokenCaptcha = '')
	{
		$flagCaptChar = json_decode(reCaptChar($googleTokenCaptcha));
		if (!$flagCaptChar->success || $flagCaptChar->success != 1) {
			$this->form_validation->set_message(__FUNCTION__, 'Invalid re captchar. Please check again');
			return false;
		} else {
			return true;
		}
	}

	public function active_get($token = '', $lang = 'EN')
	{
		$arr = array(
			'token' => $token,
			'status_login' => false
		);
		$update = array('status_login' => true, 'token' => sha1($this->time_model->convertDatetimeToTimestamp(new DateTime())));
		$user = $this->user_model->findOneAndUpdate($arr, $update);
		if ($user == null) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				"code" => 129,
				'message' => $this->message_model->msg(129, $lang)
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				"code" => 128,
				'message' => $this->message_model->msg(128, $lang)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function resetPassword_post()
	{
		$data = $this->input->post();
		$email = !empty($data['email']) ? $data['email'] : '';
		$pass = '12345678';
		$this->user_model->update(['email' => $email], ['password' => password_hash($pass, PASSWORD_BCRYPT)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
}

?>

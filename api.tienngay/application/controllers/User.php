<?php

use Restserver\Libraries\REST_Controller;

include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

//var_dump(APPPATH); die;
class User extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->dataPost = $this->input->post();
		$this->flag_login = 1;
		$this->load->model('log_user_model');
		$this->load->model('role_model');
		$this->load->model('user_model');
		$this->load->model('group_role_model');
		$this->load->model('notification_model');
		$this->load->model('menu_model');
		$this->load->model('store_model');
		$this->load->model('borrowed_model');
		$this->load->model('log_borrowed_model');
		$this->load->model('borrowed_noti_model');
		 $this->load->model('email_history_model');
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id' => new \MongoDB\BSON\ObjectId($token->id),
					'email' => $token->email,
					"status" => "active"
				);
				//Web
				$type = !empty($this->dataPost['type']) ? $this->dataPost['type'] : 1;
				if ($type == 1) $this->app_login['token_web'] = $headers_item;
				if ($type == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}

	private $api, $dataPost;

	public function find_where_not_in_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$users = $this->user_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $users
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function find_where_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);

		$users = $this->user_model->find_where_select($data, array("_id", "email"));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $users
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function search_autocomplete_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$dataRes = $this->user_model->find_where_select(array($data['name'] => new \MongoDB\BSON\Regex($data['value'])), array("_id", "email", "phone_number", "identify"));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataRes
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$news = $this->user_model->find_where_in('status', ['active']);
		if (!empty($news)) {
			foreach ($news as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id user already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$news = $this->user_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function list_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$input = $this->input->post();
		$per_page = !empty($input['per_page']) ? $input['per_page'] : 30;
		$uriSegment = !empty($input['uriSegment']) ? $input['uriSegment'] : 0;
		$condition = array();
		if (!empty($input['name'])) {
			$condition['full_name'] = $input['name'];
		}
		if (!empty($input['email'])) {
			$condition['email'] = $input['email'];
		}
		if (!empty($input['number_phone'])) {
			$condition['phone_number'] = $input['number_phone'];
		}
		if (!empty($input['type_user'])) {
			$condition['type_user'] = !empty($input['type_user']) ? $input['type_user'] : "";
		}
		$dataRes = $this->user_model->getUserPagination($per_page, $uriSegment, $condition);
		$total = $this->user_model->getUserTotal($condition);
		if (!empty($dataRes)) {
			foreach ($dataRes as $data) {
				$data['id'] = (string)$data['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataRes,
			'count' => $total,
			'per_page' => $per_page,
			'uriSegment' => $input
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function detail_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$users = $this->user_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $users
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function change_language_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['language'] = $this->security->xss_clean($data['language']);
		unset($data['type']);
		$data_update = array(
			'lang' => $data['language']
		);
		$this->user_model->findOneAndUpdate(array("_id" => $this->id), $data_update);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_create_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$this->dataPost['email'] = $this->security->xss_clean($this->dataPost['email']);
		$this->dataPost['username'] = $this->security->xss_clean($this->dataPost['username']);
		$this->dataPost['phone_number'] = $this->security->xss_clean($this->dataPost['phone_number']);
		$this->dataPost['identify'] = $this->security->xss_clean($this->dataPost['identify']);
		$this->dataPost['full_name'] = $this->security->xss_clean($this->dataPost['full_name']);
		$this->dataPost['password'] = $this->security->xss_clean($this->dataPost['password']);
		$this->dataPost['group_role'] = $this->security->xss_clean($this->dataPost['group_role']);
		$this->dataPost['lang'] = $this->security->xss_clean($this->dataPost['lang']);
		$this->dataPost['created_at'] = $this->security->xss_clean($this->dataPost['created_at']);
		$this->dataPost['created_by'] = $this->security->xss_clean($this->dataPost['created_by']);

		if (!$this->isValidEmail($this->dataPost['email'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Invalid email'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//If new customer then create account for customer
		$sendEmail = $this->checkSendEmailForNewUser($this->dataPost);
		if ($sendEmail['status'] != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $sendEmail['message']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert log
		$insertLog = array(
			"type" => "create",
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new user success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['email'] = $this->security->xss_clean($this->dataPost['email']);
		$this->dataPost['username'] = $this->security->xss_clean($this->dataPost['username']);
		$this->dataPost['phone_number'] = $this->security->xss_clean($this->dataPost['phone_number']);
		$this->dataPost['identify'] = $this->security->xss_clean($this->dataPost['identify']);
		$this->dataPost['full_name'] = $this->security->xss_clean($this->dataPost['full_name']);
		
		if (!empty($this->dataPost['status'])) {
			$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		}
		$inforDB = $this->user_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (empty($inforDB)) return;
		//Update user model
		$existedTndentify = $this->user_model->findOne(array("identify" => $this->dataPost['identify']));
		if (!empty($existedTndentify) && ((string)$existedTndentify['_id'] !== $this->dataPost['id'])) {
			$response = array(
				"status" => 500,
				"message" => "Existed identify"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
//		unset($this->dataPost['id']);
		$this->user_model->update(
			array("_id" => $inforDB['_id']),
			$this->dataPost
		);
		
		
		//Insert log
		$insertLog = array(
			"type" => "update",
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail,
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update user success",
			'user_id' => $this->dataPost['id'],
			'email' => $this->dataPost['email'],
			'username' => $this->dataPost['username'],
			
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_profile_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$this->dataPost['phone_number'] = $this->security->xss_clean($this->dataPost['phone_number']);
		$this->dataPost['identify'] = $this->security->xss_clean($this->dataPost['identify']);
		$this->dataPost['full_name'] = $this->security->xss_clean($this->dataPost['full_name']);
		$inforDB = $this->user_model->findOne(array("_id" => $this->id));
		if (empty($inforDB)) return;
		//Update user model
		$existedTndentify = $this->user_model->findOne(array("identify" => $this->dataPost['identify']));
		if (!empty($existedTndentify) && ((string)$existedTndentify['_id'] !== (string)$this->id)) {
			$response = array(
				"status" => 500,
				"message" => "Existed identify"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$url_avatar = '';
		if (!empty($this->dataPost['file'])) {
			$cfile = new CURLFile($this->dataPost['file']["tmp_name"], $this->dataPost['file']["type"], $this->dataPost['file']["name"]);
			$push_upload = $this->pushUpload($cfile);

			if ($push_upload->code == 200) {
				$url_avatar = $push_upload->path;
			} else {
				$response = array(
					'status' => 400,
					'message' => "Upload avatar user fail",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$insert = array(
			'full_name' => $this->dataPost['full_name'],
			'identify' => $this->dataPost['identify'],
			'phone_number' => $this->dataPost['phone_number'],
		);
		if (!empty($url_avatar)) {
			$insert['avatar'] = $push_upload->path;
		}
		$this->user_model->update(
			array("_id" => $inforDB['_id']),
			$insert
		);
		//Insert log
		$insertLog = array(
			"type" => "update",
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update user success",
			'url_avatar' => $url_avatar,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function pushUpload($cfile)
	{
		$serviceUpload = $this->config->item("url_service_upload");
		// $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
		$post = array('avatar' => $cfile);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $serviceUpload);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	public function change_password_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$this->dataPost['current_password'] = $this->security->xss_clean($this->dataPost['current_password']);
		$this->dataPost['password'] = $this->security->xss_clean($this->dataPost['password']);
		$this->dataPost['re_password'] = $this->security->xss_clean($this->dataPost['re_password']);
		$this->dataPost['current_password'] = trim($this->dataPost['current_password']);
		$this->dataPost['password'] = trim($this->dataPost['password']);
		$this->dataPost['current_password'] = trim($this->dataPost['current_password']);
		if (empty($this->dataPost['current_password'])) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu hiện tại không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['password'])) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu mới không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (strlen($this->dataPost['password']) < 8) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu mới phải từ 8 kí tự trở lên!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['re_password'])) {
			$response = array(
				'status' => 400,
				'message' => 'Xác nhận mật khẩu mới không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (strlen($this->dataPost['re_password']) < 8) {
			$response = array(
				'status' => 400,
				'message' => 'Xác nhận mật khẩu mới phải từ 8 kí tự trở lên!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['password'] !== $this->dataPost['re_password']) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu xác nhận không trùng mật khẩu mới!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$inforDB = $this->user_model->findOne(array("_id" => $this->id));
		if (empty($inforDB)) return;
		// check current password
		$check = $this->user_model->checkPass($this->dataPost['current_password'], $this->id);
		if (!$check) {
			$response = array(
				"status" => 400,
				"message" => "Mật khẩu hiện tại không đúng!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update user model
		$data = array(
			'password' => password_hash($this->dataPost['password'], PASSWORD_BCRYPT),
			"updated_at" => $this->createdAt,
			"updated_by" => $this->uemail
		);

		$response = $this->user_model->findOneAndUpdate(array('_id' => $this->id), $data);
		//Insert log
		$insertLog = array(
			"type" => "update",
			"old" => $inforDB,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update user success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function active_user_post()
	{
		$this->dataPost = $this->input->post();
		$this->dataPost['token'] = $this->security->xss_clean($this->dataPost['token']);
		$inforDB = $this->user_model->findOne(array("token_active" => $this->dataPost['token']));
		if (empty($inforDB)) return;
		if ($inforDB['status'] !== 'new') {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "User activated",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update user model
		$this->user_model->update(
			array("_id" => $inforDB['_id']),
			array('status' => 'active', 'token_active' => '')
		);
		//Insert log
		$insertLog = array(
			"type" => "update",
			"old" => $inforDB,
			"new" => array('status' => 'active'),
			"created_at" => $this->createdAt,
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Active user success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function forgot_user_post()
	{
		$this->dataPost = $this->input->post();
		$this->dataPost['email_forgot'] = $this->security->xss_clean($this->dataPost['email_forgot']);
		$inforDB = $this->user_model->findOne(array("email" => $this->dataPost['email_forgot']));
		if (empty($inforDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tài khoản không tồn tại trong hệ thống!",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		};
		if ($inforDB['status'] !== 'active') {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tài khoản đã bị vô hiệu hóa!",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update user model
		$code = uniqid();
		$uniq = md5($code);
		$this->user_model->findOneAndUpdate(
			array("_id" => $inforDB['_id']),
			array('token_forgot' => $uniq)
		);
		$name = !empty($inforDB['full_name']) ? $inforDB['full_name'] : '';
		$this->sendEmailForgot($name, $this->dataPost['email_forgot'], $uniq);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Forgot user success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function token_forgot_post()
	{
		$this->dataPost = $this->input->post();
		$this->dataPost['token'] = $this->security->xss_clean($this->dataPost['token']);
		$inforDB = $this->user_model->findOne(array("token_forgot" => $this->dataPost['token']));
		if (empty($inforDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tài khoản không tồn tại trong hệ thống!",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		};

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function new_password_post()
	{
		$this->dataPost = $this->input->post();
		$this->dataPost['token_pass'] = $this->security->xss_clean($this->dataPost['token_pass']);
		$this->dataPost['new_password'] = $this->security->xss_clean($this->dataPost['new_password']);
		$this->dataPost['re_password'] = $this->security->xss_clean($this->dataPost['re_password']);
		$this->dataPost['token_pass'] = trim($this->dataPost['token_pass']);
		$this->dataPost['new_password'] = trim($this->dataPost['new_password']);
		$this->dataPost['re_password'] = trim($this->dataPost['re_password']);

		if (empty($this->dataPost['token_pass'])) {
			$response = array(
				'status' => 400,
				'message' => 'Token đổi mật khẩu không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['new_password'])) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu mới không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (strlen($this->dataPost['new_password']) < 8) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu mới phải từ 8 kí tự trở lên!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['re_password'])) {
			$response = array(
				'status' => 400,
				'message' => 'Xác nhận mật khẩu mới không được để trống!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (strlen($this->dataPost['re_password']) < 8) {
			$response = array(
				'status' => 400,
				'message' => 'Xác nhận mật khẩu mới phải từ 8 kí tự trở lên!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['new_password'] !== $this->dataPost['re_password']) {
			$response = array(
				'status' => 400,
				'message' => 'Mật khẩu xác nhận không trùng mật khẩu mới!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$inforDB = $this->user_model->findOne(array("token_forgot" => $this->dataPost['token_pass']));
		if (empty($inforDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tài khoản không tồn tại trong hệ thống!",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		};

		//Update user model
		$data = array(
			'password' => password_hash($this->dataPost['new_password'], PASSWORD_BCRYPT),
			'token_forgot' => '',
			"updated_at" => $this->createdAt,
		);

		$this->user_model->findOneAndUpdate(array('_id' => $inforDB['_id']), $data);

		//Insert log
		$insertLog = array(
			"type" => "update",
			"old" => $inforDB,
			"new" => $data,
			"created_at" => $this->createdAt,
		);
		$this->log_user_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function sendEmailForgot($name, $email, $uniq)
	{
		//Send email
		$urlForgot = $this->config->item("cpanel_url") . 'auth/reset_password?token=' . $uniq;
		$sendEmail = array(
			"name" => $name,
			"email" => $email,
			"code" => "vfc_reset_password",
			"url" => $urlForgot,
			'API_KEY' => $this->config->item('API_KEY')
		);
		$this->user_model->send_Email_Forgot($sendEmail);
		   $data = array(
            "from_name" => $name,
            "to" => $email,
            "subject" =>'Lấy lại mật khẩu đăng nhập '.$email,
            "status" => 'deactive',
            "message"=> $urlForgot,
            "created_at" => (int)$this->createdAt
        );
      
         $this->email_history_model->insert($data);

		return;
	}

	private function checkSendEmailForNewUser($data)
	{
		$res = array(
			"status" => 1,
			"message" => ""
		);
		//check username
		$existedUsername = $this->user_model->findOne(array("username" => $data['username']));
		if (!empty($existedUsername)) {
			$res = array(
				"status" => 500,
				"message" => "Existed Username"
			);
			return $res;
		}
		//Create account for customer
		$data['email'] = strtolower($data['email']);
		$existedEmail = $this->user_model->findOne(array("email" => $data['email']));
		if (!empty($existedEmail)) {
			$res = array(
				"status" => 500,
				"message" => "Existed Email"
			);
			return $res;
		}
		$existedTndentify = $this->user_model->findOne(array("identify" => $data['identify']));
		if (!empty($existedTndentify)) {
			$res = array(
				"status" => 500,
				"message" => "Existed identify"
			);
			return $res;
		}
		$hash_password = password_hash($data['password'], PASSWORD_BCRYPT);
		$tokenActive = password_hash($hash_password, PASSWORD_BCRYPT);
		$urlActive = $this->config->item("cpanel_url") . 'user/activeAccount?token=' . $tokenActive;
		$newAccount = array(
			"email" => $data['email'],
			"username" => $data['username'],
			"password" => $hash_password,
			"phone_number" => $data['phone_number'],
			"full_name" => $data['full_name'],
			"identify" => $data['identify'],
			"url_active" => $urlActive,
			"token_active" => $tokenActive,
			"status" => "new",
			"lang" => "vietnamese",
			"created_at" => $this->createdAt,
			"created_by" => $data['created_by'],
		);

		$userId = $this->user_model->insertReturnId($newAccount);
		//Update to role customer
		$roleCustomer = $this->role_model->findOne(array("slug" => "customer"));
		if (!empty($roleCustomer)) {
			$users = $roleCustomer['users'];
			$data1 = array();
			$data1['email'] = $data['email'];
			$dataCustomer = array();
			$dataCustomer[(string)$userId] = $data1;
			$users[(string)$userId] = $dataCustomer;
			//Update role customer
			$this->role_model->update(
				array("_id" => $roleCustomer['_id']),
				array('users' => $users)
			);
		}
		//Update group role user
		$groupRoleUser = $this->group_role_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['group_role'])));
		if (!empty($groupRoleUser)) {
			$group_role_users = $groupRoleUser['users'];
			$dataGroupRole = array();
			$dataGroupRole['email'] = $data['email'];
			$dataGroup = array();
			$dataGroup[(string)$userId] = $dataGroupRole;
			$group_role_users[(string)$userId] = $dataGroup;
			//Update role group
			$this->group_role_model->update(
				array("_id" => $groupRoleUser['_id']),
				array('users' => $group_role_users)
			);
		}
		//Send email
		$sendEmail = array(
			'code' => "vfc_register",
			'email' => $data['email'],
			'url' => $urlActive,
			'full_name' => trim($data['full_name']),
			'API_KEY' => $this->config->item('API_KEY')
		);
		$this->user_model->send_Email($sendEmail);
		return $res;
	}


	public function getNotification_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$limit = !empty($this->dataPost['limit']) ? (int)$this->dataPost['limit'] : '';
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
		if (!empty($limit)) {
			$condition['limit'] = $limit;
		}
		$condition['count'] = true;
		$count = $this->notification_model->getNotification($condition);
		unset($condition['count']);

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 25;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$res = $this->notification_model->getNotification_all($condition, $per_page, $uriSegment);

		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res,
				'total' => $count,
				'message' => 'Get notifications success!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => 500,
				'data' => array(),
				'total' => 0,
				'message' => 'Error server!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function updateNotification_post()
	{
		$data = $this->input->post();
		$data['noti_id'] = $this->security->xss_clean($data['noti_id']);
		$no_id = $data['noti_id'];
		$data = array(
			'status' => 2,
		);
		if ($no_id === 'all') {
			$this->notification_model->findManyAndUpdate(array('user_id' => (string)$this->id), $data);
		} else {
			$this->notification_model->findOneAndUpdate(array('_id' => new MongoDB\BSON\ObjectId($no_id)), $data);
		}
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
		$condition['count'] = true;
		$count = $this->notification_model->getNotification($condition);
		unset($condition['count']);
		$res = $this->notification_model->getNotification($condition);
		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res,
				'count' => $count,
				'message' => 'Get notifications success!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => 500,
				'data' => array(),
				'count' => 0,
				'message' => 'Error server!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function profile_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$res = $this->user_model->findOne(array('_id' => $this->id));
		if ($res) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res,
				'message' => 'Get profile success!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => 500,
				'data' => array(),
				'message' => 'Error server!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	private function isValidEmail($email)
	{
		$email = strtolower($email);
		return filter_var($email, FILTER_VALIDATE_EMAIL)
			&& preg_match('/@.+\./', $email);
	}

	public function get_init_data_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		//userRoles
		$userId = $this->id;
		$userRoles = $this->role_model->getRoleByUserId($userId);

		//groupRoles
		$arrGroupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$groupRoles = array();
		foreach ($arrGroupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($groupRoles, (string)$groupRole['_id']);
					continue;
				}
			}
		}

		//paramMenus
		$param = array(
			'where' => array(
				'status' => 'active'
			),
			'fields' => '_id',
			'in' => $userRoles['role_menus']
		);
		$paramMenus = $this->menu_model->find_where_in($param['where'], $param['fields'], convertToMongoObject($param['in']));
		$arrayParamMenus = [];
		if (isset($paramMenus)) {
			foreach ($paramMenus as $menu) {
				$url_array = explode('/', $menu['url']);
				$url = strtolower($url_array[0]);
				if (empty($url_array[0])) {
					if (!empty($url_array[1])) {
						$url = strtolower($url_array[1]);
					}
				}
				$arrayParamMenus[] = $url;
			}
		}
		//Notifications
		$notifications = array();
		$notifications['data'] = array();
		$notifications['count'] = 0;
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
//        $condition['limit'] = array('limit' => 6);
		$condition['limit'] = 6;
		$condition['count'] = true;
		$count = $this->notification_model->getNotification($condition);
		unset($condition['count']);
		$res = $this->notification_model->getNotification($condition);
		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$notifications['data'] = $res;
			$notifications['count'] = $count;
		}


		//Return
		$response = array(
			'status' => 200,
			'userRoles' => $userRoles,
			'groupRoles' => $groupRoles,
			'paramMenus' => $paramMenus,
			'paramMenus' => $arrayParamMenus,
			'notifications' => $notifications
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function get_notification_for_lead_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$notifications = array();
		$notifications['data'] = array();
		$condition['status'] = [1];
		$condition['action'] = "lead";
		$condition['user_id'] = (string)$data["user_id"];
		$notifyData = $this->notification_model->getNotification_all($condition);
		$notifyDivideFive = array();
		if (!empty($notifyData)) {
			foreach ($notifyData as $key => $value) {
				$timeMod = $this->elapsed_time($value['created_at']) % 5;
				if ($timeMod === 0) {
					array_push($notifyDivideFive, $value);
				}
			}
		}

		$response = array(
			'status' => 200,
			'notifications' => $notifyDivideFive
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	function elapsed_time($timestamp)
	{
		$time = time() - $timestamp;
		$minute = ($time / 60 % 60);
		return $minute ? $minute : '';
	}

	public function delete_user_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$this->user_model->delete(['_id' => new  MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => 200,
			'msg' => 'success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	
	public function update_read_all_notification_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = [];
		$condition['user_id'] = (string)$this->id;
		$condition['status'] = 1;
		$data = array(
			'status' => 2,
		);
		$this->notification_model->findManyAndUpdate($condition, $data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'messege' => 'thanh cong',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_init_dataBorrowed_note_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		//userRoles
		$userId = $this->id;
		$userRoles = $this->role_model->getRoleByUserId($userId);

		//groupRoles
		$arrGroupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$groupRoles = array();
		foreach ($arrGroupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($groupRoles, (string)$groupRole['_id']);
					continue;
				}
			}
		}

		//paramMenus
		$param = array(
			'where' => array(
				'status' => 'active'
			),
			'fields' => '_id',
			'in' => $userRoles['role_menus']
		);
		$paramMenus = $this->menu_model->find_where_in($param['where'], $param['fields'], convertToMongoObject($param['in']));
		$arrayParamMenus = [];
		if (isset($paramMenus)) {
			foreach ($paramMenus as $menu) {
				$url_array = explode('/', $menu['url']);
				$url = strtolower($url_array[0]);
				if (empty($url_array[0])) {
					if (!empty($url_array[1])) {
						$url = strtolower($url_array[1]);
					}
				}
				$arrayParamMenus[] = $url;
			}
		}
		//Notifications
		$notifications = array();
		$notifications['data'] = array();
		$notifications['count'] = 0;
		$condition['status'] = [1];
		$condition['user_id'] = (string)$this->id;
		$condition['limit'] = 6;
//		$condition['limit'] = 12;
		$condition['count'] = true;
		$count = $this->borrowed_noti_model->getNotification_borrowed($condition);
		unset($condition['count']);
		$res = $this->borrowed_noti_model->getNotification_borrowed($condition);
		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$notifications['data'] = $res;
			$notifications['count'] = $count;
		}


		//Return
		$response = array(
			'status' => 200,

			'notifications' => $notifications
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_init_dataBorrowed_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		//userRoles
		$userId = $this->id;
		$userRoles = $this->role_model->getRoleByUserId($userId);

		//groupRoles
		$arrGroupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$groupRoles = array();
		foreach ($arrGroupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($groupRoles, (string)$groupRole['_id']);
					continue;
				}
			}
		}

		//paramMenus
		$param = array(
			'where' => array(
				'status' => 'active'
			),
			'fields' => '_id',
			'in' => $userRoles['role_menus']
		);
		$paramMenus = $this->menu_model->find_where_in($param['where'], $param['fields'], convertToMongoObject($param['in']));
		$arrayParamMenus = [];
		if (isset($paramMenus)) {
			foreach ($paramMenus as $menu) {
				$url_array = explode('/', $menu['url']);
				$url = strtolower($url_array[0]);
				if (empty($url_array[0])) {
					if (!empty($url_array[1])) {
						$url = strtolower($url_array[1]);
					}
				}
				$arrayParamMenus[] = $url;
			}
		}
		//Notifications
		$notifications = array();
		$notifications['data'] = array();
		$notifications['count'] = 0;
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
        $condition['limit'] = 6;
//		$condition['limit'] = 12;
		$condition['count'] = true;
		$count = $this->borrowed_noti_model->getNotification_borrowed($condition);
		unset($condition['count']);
		$res = $this->borrowed_noti_model->getNotification_borrowed($condition);
		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$notifications['data'] = $res;
			$notifications['count'] = $count;
		}


		//Return
		$response = array(
			'status' => 200,

			'notifications' => $notifications
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function updateNotification_borrowed_post()
	{
		$data = $this->input->post();
		$data['noti_id'] = $this->security->xss_clean($data['noti_id']);
		$no_id = $data['noti_id'];
		$data = array(
			'status' => 2,
		);
		if ($no_id === 'all') {
			$this->borrowed_noti_model->findManyAndUpdate(array('user_id' => (string)$this->id), $data);
		} else {
			$this->borrowed_noti_model->findOneAndUpdate(array('_id' => new MongoDB\BSON\ObjectId($no_id)), $data);
		}
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
		$condition['count'] = true;
		$condition['limit'] = array('limit' => 6);

		$count = $this->borrowed_noti_model->getNotification_borrowed($condition);
		unset($condition['count']);
		$res = $this->borrowed_noti_model->getNotification_borrowed($condition);
		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res,
				'count' => $count,
				'message' => 'Get notifications success!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => 500,
				'data' => array(),
				'count' => 0,
				'message' => 'Error server!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function getNotificationBorrow_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$limit = !empty($this->dataPost['limit']) ? (int)$this->dataPost['limit'] : '';
		$condition['status'] = [1, 2];
		$condition['user_id'] = (string)$this->id;
		if (!empty($limit)) {
			$condition['limit'] = $limit;
		}
		$condition['count'] = true;
		$count = $this->borrowed_noti_model->getNotification($condition);
		unset($condition['count']);

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 25;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$res = $this->borrowed_noti_model->getNotification_all($condition, $per_page, $uriSegment);

		if ($res) {
			foreach ($res as $r) {
				$r['id'] = (string)$r['_id'];
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res,
				'total' => $count,
				'message' => 'Get notifications success!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => 500,
				'data' => array(),
				'total' => 0,
				'message' => 'Error server!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}



}

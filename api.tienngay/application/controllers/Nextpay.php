<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Nextpay extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model("role_model");
		$this->load->model("log_model");
		$this->load->model("group_role_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->dataPost = $this->input->post();
		$this->flag_login = 1;
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id' => new \MongoDB\BSON\ObjectId($token->id),
					'email' => $token->email,
					"status" => "active",
					//"is_superadmin" => 1
				);
				//Web
				if ($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				unset($this->dataPost['type']);
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
				}
			}
		}
	}

	public function check_group_next_pay_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$userId = $this->dataPost['user_id'];
		$groupRoles = $this->getGroupRole($this->id);
		$response = $this->role_model->getRoleByUserId($userId);
		$store_user = [];
		if (count($response['role_stores']) > 0) {
			foreach ($response['role_stores'] as $store) {
				array_push($store_user, $store['store_id']);
			}
		}

		$role = $this->role_model->findOne(['slug' => 'doi-tac-nextpay']);
		$store_np = [];
		foreach ($role['stores'] as $st) {
			foreach ($st as $k => $v) {
				array_push($store_np, $k);
			}
		}
		$i = 0;
		foreach ($store_user as $value) {
			if (in_array($value, $store_np)) {
				$i += 1;
			}
		}
		$response1 = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $i > 0 ? 1 : 0
		);
		if (in_array("van-hanh", $groupRoles) || in_array("hoi-so", $groupRoles) || in_array("ke-toan", $groupRoles) || in_array("quan-ly-khu-vuc", $groupRoles)) {
			$response1['data'] = 0;
		}
		$this->set_response($response1, REST_Controller::HTTP_OK);
	}

	public function check_store_next_pay_post()
	{
		$storeId = $this->dataPost['store_id'];
		$role = $this->role_model->findOne(['slug' => 'doi-tac-nextpay']);
		$store_np = [];
		foreach ($role['stores'] as $st) {
			foreach ($st as $k => $v) {
				array_push($store_np, $k);
			}
		}
		$i = 0;
		if (in_array($storeId, $store_np)) {
			$i = 1;
		}
		$response1 = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $i
		);
		$this->set_response($response1, REST_Controller::HTTP_OK);
	}

	private function getGroupRole($userId)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
		return $arr;
	}

	public function check_store_create_contract_digital_post()
	{
		$storeId = $this->dataPost['store_id'];
		$role = $this->role_model->findOne(['slug' => 'hop-dong-dien-tu']);
		$store_digital = [];
		foreach ($role['stores'] as $st) {
			foreach ($st as $k => $v) {
				array_push($store_digital, $k);
			}
		}
		$i = 0;
		if (in_array($storeId, $store_digital)) {
			$i = 1;
		}
		$response1 = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $i
		);
		$this->set_response($response1, REST_Controller::HTTP_OK);
		return;
	}
}

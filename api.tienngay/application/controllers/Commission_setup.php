<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Commission_setup extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('main_commission_model');
		$this->load->model('property_log_model');
		$this->load->model('commission_setup_model');
		$this->load->model('commission_log_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$dataPost = $this->input->post();
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
					// "is_superadmin" => 1
				);
				//Web
				if ($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
				}
			}
		}
	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function createCommission_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$getOne = $this->commission_setup_model->findOne(array("product_type.id" => $data['product_type']['id'], "status" => "active"));
		$arr_update = [
			'status' => 'deactivate',
			'updated_at' => $this->createdAt,
			'updated_by' => $this->uemail
		];
		if (!empty($getOne)) {
			$this->commission_setup_model->update(
				["_id" => $getOne['_id']],
				$arr_update
			);
			$insertLog = array(
				"type" => "commission",
				"action" => "update",
				"commissionID" => (string)$getOne["_id"],
				"old" => $data,
				"new" => $arr_update,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->commission_log_model->insert($insertLog);
		}

		$commissionID = $this->commission_setup_model->insertReturnId($data);
		$insertLog = array(
			"type" => "commission",
			"action" => "create",
			"commissionID" => (string)$commissionID,
			"old" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->commission_log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cài đặt hoa hồng sản phẩm thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function list_commission_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$list_commission = $this->commission_setup_model->find_where_in('status', ['active', 'deactivate']);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $list_commission
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function update_status_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		if (empty($data['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$getOne = $this->commission_setup_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($data['id'])]);
		$product_name = $getOne['product_type']['text'];
		if (!empty($getOne)) {
			$check_product = $this->commission_setup_model->findOne(['product_type.code' => $getOne['product_type']['code'], 'status' => 'active']);
			if (!empty($check_product)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Bạn phải tạo mới hoa hồng $product_name thay thế, trước khi block $product_name cũ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$commissionID = $this->commission_setup_model->findOneAndUpdate(
			array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
			array("status" => $data['status'])
		);
		$insertLog = array(
			"type" => "commission",
			"action" => "update",
			"commissionID" => (string)$commissionID,
			"old" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->commission_log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhật thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function test_post()
	{
		var_dump(1112);
		die();
	}
}

<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Customer_manager extends REST_Controller
{
	public function __construct()
	{

		parent::__construct();
		$this->load->model("lead_model");
		$this->load->model("dashboard_model");
		$this->load->model('contract_model');
		$this->load->model('log_lead_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model("store_model");
		$this->load->model("group_role_model");
		$this->load->model('district_model');
		$this->load->model('ward_model');
		$this->load->helper('lead_helper');
		$this->load->model('province_model');
		$this->load->model('recording_model');
		$this->load->model("landing_page_model");
		$this->load->model("lead_extra_model");
		$this->load->model('cskh_del_model');
		$this->load->model('cskh_insert_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_accesstrade_model');
		$this->load->model("notification_model");
		$this->load->model("order_model");
		$this->load->model("area_model");
		$this->load->model("createkpi_telesale_model");
		$this->load->model("customer_code_model");
		$this->load->model("transaction_model");
		$this->load->model("log_customercode_model");
		$this->load->model("mic_tnds_model");
		$this->load->model("contract_tnds_model");
		$this->load->model("vbi_sxh_model");
		$this->load->model("vbi_tnds_model");
		$this->load->model("vbi_utv_model");
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
					// "is_superadmin" => 1
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
					$this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}


	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;


	public function cron_insert_contract_post()
	{
		$contractData = $this->contract_model->getContractPaginationByRole_limit();

		foreach ($contractData as $value) {
			$data = [];

			$check_customer_code = $this->customer_code_model->findOne(['customer_infor.customer_identify' => $value['customer_infor']['customer_identify']]);

			$check_phone = $this->customer_code_model->findOne(['customer_infor.customer_phone_number' => $value['customer_infor']['customer_phone_number']]);

			if (empty($check_customer_code) && empty($check_phone)){

				$resNumberCodeContract = $this->initNumberContractCode();

				$data['customer_code'] = "KH00000" . $resNumberCodeContract['max_number_contract'];
				$data['number_contract'] = $resNumberCodeContract['max_number_contract'];

				//
				if ($value['customer_infor']['customer_name'] == ""){
					continue;
				}
				$data['customer_infor']['customer_name'] = $value['customer_infor']['customer_name'];

				$data['customer_infor']['customer_identify'] = $value['customer_infor']['customer_identify'];
				if (strlen($value['customer_infor']['customer_identify']) > 10){
					$data['customer_infor']['customer_identify_name'] = "CCCD";
				} else {
					$data['customer_infor']['customer_identify_name'] = "CMTND";
				}
				$data['status'] = $value['status'];
				$data['customer_infor']['customer_phone_number'] = $value['customer_infor']['customer_phone_number'];
				$check_status = $this->contract_model->find_where_cmt(['customer_infor.customer_identify' => $value['customer_infor']['customer_identify']]);
				if (count($check_status) > 1){
					$data['status'] = [];
					foreach ($check_status as $item){
						array_push($data['status'], $item['status']);
					}
				}

				$data["id_contract"] = $value['_id'];
				$data["created_at"] = $this->createdAt;
				$this->customer_code_model->insert($data);

//				$this->contract_model->update(array("_id" => new \MongoDB\BSON\ObjectId((string)$value['_id'])), ["is_customer_code" => 1]);

			} else {

				if (!empty($check_customer_code['_id'])){
					$data['status'] = $value['status'];

					$check_status = $this->contract_model->find_where_cmt(['customer_infor.customer_identify' => $value['customer_infor']['customer_identify']]);

					if (count($check_status) > 1){
						$data['status'] = [];
						foreach ($check_status as $item){
							array_push($data['status'], $item['status']);
						}
					}
					$data["update_at"] = $this->createdAt;
					$this->customer_code_model->update(array("_id" => new \MongoDB\BSON\ObjectId((string)$check_customer_code['_id'])), ["status" => $data['status']]);
				}


			}

		}

		$user_app = $this->user_model->getContractPaginationByRole_limit();

		foreach ($user_app as $value) {
			$data = [];

			$check_phone = $this->customer_code_model->findOne(['customer_infor.customer_phone_number' => $value['phone_number']]);

			if (empty($check_phone)){

				$resNumberCodeContract = $this->initNumberContractCode();

				$data['customer_code'] = "KH00000" . $resNumberCodeContract['max_number_contract'];
				$data['number_contract'] = $resNumberCodeContract['max_number_contract'];

				//
				$data['customer_infor']['customer_name'] = $value['full_name'];
				$data['customer_infor']['customer_phone_number'] = $value['phone_number'];
				$data["status"] = -1;
				$data['source'] = "app";
				$data["created_at"] = $this->createdAt;
				$this->customer_code_model->insert($data);

				$this->user_model->update(array("_id" => new \MongoDB\BSON\ObjectId((string)$value['_id'])), ["is_customer_code" => 1]);

			}
		}

		echo "oke";

	}


	private function initNumberContractCode()
	{
		$maxNumber = $this->customer_code_model->getMaxNumberContract();
		$maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract'] + 1 : 1;
		$res = array(
			"max_number_contract" => $maxNumberContract
		);
		return $res;
	}

	public function get_count_all_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$customer_code = !empty($this->dataPost['customer_code']) ? $this->dataPost['customer_code'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";

		if (!empty($customer_code)) {
			$condition['customer_code'] = $customer_code;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$customer = $this->customer_code_model->getCountByRole($condition, $per_page, $uriSegment);
		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function get_all_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$customer_code = !empty($this->dataPost['customer_code']) ? $this->dataPost['customer_code'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";

		if (!empty($customer_code)) {
			$condition['customer_code'] = $customer_code;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$customer = $this->customer_code_model->getDataByRole($condition, $per_page, $uriSegment);
		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_transaction_post(){

		$this->dataPost = $this->input->post();
		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$condition = [];
		if (!empty($phone)) {
			$condition['phone'] = $phone;
		}

		$customer = $this->transaction_model->getDataByRole($condition);
		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_order_post(){

		$this->dataPost = $this->input->post();
		$transaction_code = !empty($this->dataPost['transaction_code']) ? $this->dataPost['transaction_code'] : "";

		$condition = [];
		if (!empty($transaction_code)) {
			$condition['transaction_code'] = $transaction_code;
		}

		$customer = $this->order_model->getDataByRole($condition);
		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_post(){


		$this->dataPost['customer_code'] = $this->security->xss_clean($this->dataPost['customer_code']);
		$this->dataPost['img_id_front'] = $this->security->xss_clean($this->dataPost['img_id_front']);
		$this->dataPost['img_id_back'] = $this->security->xss_clean($this->dataPost['img_id_back']);


		//Validate
		if (($this->dataPost['img_id_front']) == "https://service.egate.global/uploads/avatar/1625124067-8da7f095442375c0470ffe268c725116.png") {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "CCCD mặt trước không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['img_id_back'] == "https://service.egate.global/uploads/avatar/1625124067-8da7f095442375c0470ffe268c725116.png") {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "CCCD mặt sau không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		$check_fileReturn = $this->customer_code_model->findOne(array("customer_code" => $this->dataPost['customer_code']));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "Customer_code",
			"action" => "Lưu cccd",
			"customer_code" => $this->dataPost['customer_code'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_customercode_model->insert($log);

		unset($this->dataPost['id']);

		$this->customer_code_model->update(array("customer_code" => $this->dataPost['customer_code']), ["img_id_front" => $this->dataPost['img_id_front'], "img_id_back" => $this->dataPost['img_id_back']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function log_get_one_post(){

		$this->dataPost = $this->input->post();
		$customer_code = !empty($this->dataPost['customer_code']) ? $this->dataPost['customer_code'] : "";


		$customer = $this->log_customercode_model->find_where(["customer_code"=> $customer_code]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_mic_tnds_post(){
		$this->dataPost = $this->input->post();
		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$customer = $this->mic_tnds_model->find_where(["customer_info.customer_phone"=> $phone]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_contract_tnds_post(){

		$this->dataPost = $this->input->post();

		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$customer = $this->contract_tnds_model->find_where(["contract_info.customer_infor.customer_phone_number"=> $phone]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_vbi_sxh_post(){

		$this->dataPost = $this->input->post();
		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$customer = $this->vbi_sxh_model->find_where(["customer_info.customer_phone"=> $phone]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_vbi_tnds_post(){

		$this->dataPost = $this->input->post();

		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$customer = $this->vbi_tnds_model->find_where(["customer_info.customer_phone"=> $phone]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_vbi_utv_post(){

		$this->dataPost = $this->input->post();

		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";

		$customer = $this->vbi_utv_model->find_where(["customer_info.customer_phone"=> $phone]);

		if (empty($customer)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $customer
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}

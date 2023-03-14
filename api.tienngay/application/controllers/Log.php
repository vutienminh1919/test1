<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Log extends REST_Controller {
	public function __construct($config = 'rest') {
		parent::__construct($config);
		$this->load->model("log_model");
		$this->load->model("log_contract_model");
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
					'_id'=>new \MongoDB\BSON\ObjectId($token->id),
					'email'=>$token->email,
					"status" => "active",
					//"is_superadmin" => 1
				);
				//Web
				if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				unset($this->dataPost['type']);
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1){
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
				}
			}
		}
	}

	private $dataPost, $createdAt, $id;
	public function get_log_transaction_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$log = $this->log_model->getLogs(array("type" =>"transaction","action" =>"update","transaction_id" => $this->dataPost['code_contract']));
		if(empty($log)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Log is not exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $log
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_log_contract_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
//        $log = $this->log_model->getLogs(array("contract_id" => $this->dataPost['contract_id']));
		$log = $this->log_contract_model->getLogs(array("contract_id" => $this->dataPost['contract_id']));
		if(empty($log)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Log is not exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$dataFileJson = $this->readFileJson($this->dataPost['contract_id']);
		foreach ($log as $value){
			if (empty($value['old'])){
				foreach ($dataFileJson as $item){
					if ((string)$value['_id'] == $item['log_id']['$oid']){
						$value['old'] = $item['old'];
						$value['new'] = $item['new'];
					}
				}
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $log
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_log_gh_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$log = $this->log_contract_model->findOne_ghcc(array("contract_id" => $this->dataPost['contract_id'],"action"=>'cvkd_send_gh'));
		if(empty($log)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Log is not exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$dataFileJson = $this->readFileJson($this->dataPost['contract_id']);
		$arr = [];
		foreach ($log as $value){
			if ($value['action'] != 'cvkd_send_gh'){
				continue;
			}
			if (empty($value['old'])){
				foreach ($dataFileJson as $item){
					if ((string)$value['_id'] == $item['log_id']['$oid']){
						$value['old'] = $item['old'];
						$value['new'] = $item['new'];
					}
				}
			}
			array_push($arr, $value);
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr[count($arr)-1]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_log_cc_post() {
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$log = $this->log_contract_model->findOne_ghcc(array("contract_id" => $this->dataPost['contract_id'],"action"=>'cvkd_send_cc'));
		if(empty($log)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Log is not exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$dataFileJson = $this->readFileJson($this->dataPost['contract_id']);
		$arr = [];
		foreach ($log as $value){
			if ($value['action'] != 'cvkd_send_cc'){
				continue;
			}
			if (empty($value['old'])){
				foreach ($dataFileJson as $item){
					if ((string)$value['_id'] == $item['log_id']['$oid']){
						$value['old'] = $item['old'];
						$value['new'] = $item['new'];
					}
				}
			}
			array_push($arr, $value);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr[count($arr)-1]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function create_log_comment_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$data['created_at'] = (int)$data['created_at'];

		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['comment_id']);

		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['add_comment']);

		$log = array(
			"type" => "contract",
			"action" => "Comment",
			"contract_id" => $this->dataPost['contract_id'],
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		/**
		 * Save log to json file
		 */

		$insertLogNew = [
			"type" => "contract",
			"action" => "Comment",
			"contract_id" => $this->dataPost['contract_id'],
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail,
		];
		$log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
		$this->log_model->insert($log);
		$log['log_id'] = $log_id;

		$this->insert_log_file($log, $this->dataPost['contract_id']);

		/**
		 * ----------------------
		 */

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Tạo mới thành công",
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_interest_fee_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$start = !empty($data['fdate']) ? $this->security->xss_clean($data['fdate']) : '';
		$end = !empty($data['tdate']) ? $this->security->xss_clean($data['tdate']) : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$dataInterestFee = $this->log_model->get_type_fee_loan_action_update($condition);
		$data = array();
		if (!empty($dataInterestFee)) {
			foreach ($dataInterestFee as $k => $interest_fee) {
				$data[$k]['created_at'] = $interest_fee->created_at;
				foreach ($interest_fee['new']['infor'] as $key => $item) {
					if ($key == 30) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "CC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}

						}
					}
					if ($key == 90) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
					if ($key == 180) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
					if ($key == 270) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
					if ($key == 360) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
					if ($key == 540) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
					if ($key == 720) {
						foreach ($item as $key1 => $value) {
							if ($key1 == "DKX") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXM") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "DKXOTO") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "TC") {
								$data[$k][$key1][$key] = $value;
							}
							if ($key1 == "KDOL") {
								$data[$k][$key1][$key] = $value;
							}
						}
					}
				}
			}
		}

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	function readFileJson($contract_id){
		$data = file_get_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json');
		return json_decode($data,true);
	}

	public function insert_log_file($value, $contract_id){

		$fp = fopen($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json', "a");

		if(!empty($fp)){

			$arrayData = $this->readFileJson($contract_id);

			if(empty($arrayData)){
				$arrayData = [];
			}
			array_push($arrayData, $value);

			$this->saveFileJson($arrayData, $contract_id);

		}
	}

	function saveFileJson($arrayData , $contract_id){
		$dataJson = json_encode($arrayData);
		file_put_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json',$dataJson);
	}


}

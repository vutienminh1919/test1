<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Ksnb_report extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model("access_right_model");
		$this->load->model("contract_model");
		$this->load->model("contract_tempo_model");
		$this->load->model("tempo_contract_accounting_model");
		$this->load->model("log_model");
		$this->load->model("transaction_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("transaction_extend_model");
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
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
				}
			}
		}
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost;

	

	public function lich_su_hop_dong_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$format = $start; // 2020-01
		$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}
		// var_dump($condition['end']); die;
		$contract = $this->contract_model->get_lich_su_hop_dong($condition);
   // var_dump($contract); die;
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}



	public function follow_current_month_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$format = $start; // 2020-01
		$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}

		$contract = $this->contract_model->getFollowCurrentMonth($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function revoke_loan_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

//		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

//		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		}

		$transaction = $this->transaction_model->getRevokeLoan($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['full_name'] = '';
				if (isset($tran['code_contract'])) {
					$contract = $this->contract_model->find_where(array('code_contract' => $tran['code_contract']));

					$tran['full_name'] = (isset($contract[0]['customer_infor']['customer_name'])) ? (string)$contract[0]['customer_infor']['customer_name'] : '';
					$tran['type_gh'] = (isset($contract[0]['type_gh'])) ? $contract[0]['type_gh'] : '';
					$tran['code_contract_parent_gh'] = (isset($contract[0]['code_contract_parent_gh'])) ? $contract[0]['code_contract_parent_gh'] : '';
					$tran['code_contract_disbursement'] = (isset($contract[0]['code_contract_disbursement'])) ? $contract[0]['code_contract_disbursement'] : '';
				}
			}
		}
		$transaction_extent = $this->transaction_extend_model->find_where_extend($condition);
		if (!empty($transaction_extent)){
			foreach ($transaction_extent as $extend){
				$contract1 = $this->contract_model->find_where(array('code_contract' => $extend['code_contract_parent_gh']));
				$extend['full_name'] = (isset($contract1[0]['customer_infor']['customer_name'])) ? (string)$contract1[0]['customer_infor']['customer_name'] : '';


				$info = $this->transaction_model->findOne(array("code_contract" => $extend['code_contract']));
				$extend['bank'] = $info['bank'];
				$extend['code_transaction_bank'] = $info['code_transaction_bank'];
				$extend['store']['name'] = $info['store']['name'];
				$extend['type'] = $info['type'];
				unset($extend['tien_thua_thanh_toan']);
				unset($extend['tien_thua_tat_toan']);
			}

			$result = array_merge($transaction, $transaction_extent);
		}
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => !empty($result) ? $result : $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function revoke_loan_new_post(){
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');
		}

		$contract = $this->contract_model->getFollowCurrentMonth($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function follow_investor_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}
		$contract = $this->contract_model->getFollowInvestor($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function interest_real_investor_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}
		$contract = $this->contract_model->getInterestRealInvestor($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_pay_investor_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}
		$contract = $this->transaction_model->getPayInvestor($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function get_count_all_post()
	{
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

//		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

//		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		}

		$transaction = $this->transaction_model->getRevokeLoan_count($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['full_name'] = '';
				if (isset($tran['code_contract'])) {
					$contract = $this->contract_model->find_where(array('code_contract' => $tran['code_contract']));

					$tran['full_name'] = (isset($contract[0]['customer_infor']['customer_name'])) ? (string)$contract[0]['customer_infor']['customer_name'] : '';
				}
			}
		}
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function revoke_loan_view_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$start = !empty($this->dataPost['start']) ? date('Y-m-d',strtotime($this->dataPost['start'])) : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

//		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

//		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$transaction = $this->transaction_model->getRevokeLoan_view($condition, $per_page, $uriSegment);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['full_name'] = '';
				if (isset($tran['code_contract'])) {
					$contract = $this->contract_model->find_where(array('code_contract' => $tran['code_contract']));

					$tran['full_name'] = (isset($contract[0]['customer_infor']['customer_name'])) ? (string)$contract[0]['customer_infor']['customer_name'] : '';
					$tran['type_gh'] = (isset($contract[0]['type_gh'])) ? $contract[0]['type_gh'] : '';
					$tran['code_contract_parent_gh'] = (isset($contract[0]['code_contract_parent_gh'])) ? $contract[0]['code_contract_parent_gh'] : '';
					$tran['code_contract_disbursement'] = (isset($contract[0]['code_contract_disbursement'])) ? $contract[0]['code_contract_disbursement'] : '';
				}
			}
		}
		$transaction_extent = $this->transaction_extend_model->find_where_extend($condition);
		if (!empty($transaction_extent)){
			foreach ($transaction_extent as $extend){
				$contract1 = $this->contract_model->find_where(array('code_contract' => $extend['code_contract_parent_gh']));
				$extend['full_name'] = (isset($contract1[0]['customer_infor']['customer_name'])) ? (string)$contract1[0]['customer_infor']['customer_name'] : '';

				$info = $this->transaction_model->findOne(array("code_contract" => $extend['code_contract']));

				$extend['bank'] = $info['bank'];
				$extend['code_transaction_bank'] = $info['code_transaction_bank'];
				$extend['store']['name'] = $info['store']['name'];
				$extend['type'] = $info['type'];
				unset($extend['tien_thua_thanh_toan']);
				unset($extend['tien_thua_tat_toan']);
			}

			$result = array_merge($transaction, $transaction_extent);
		}

		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => !empty($result) ? $result : $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function follow_current_month_count_post(){

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$format = $start; // 2020-01
		$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}

		$contract = $this->contract_model->getFollowCurrentMonth_count($condition);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function follow_current_month_view_post(){

		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		$format = $start; // 2020-01
		$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$contract = $this->contract_model->getFollowCurrentMonth_view($condition, $per_page, $uriSegment);
		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}


}

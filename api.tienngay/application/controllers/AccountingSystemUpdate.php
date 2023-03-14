<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class AccountingSystemUpdate extends REST_Controller
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

	public function disburse_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['start']) || empty($this->dataPost['end'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($end)); // 2020-01-31

		$condition = array();
		if (!empty($start)) $condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
		if (!empty($end)) $condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');

		$contract = $this->contract_model->getDisburse($condition);

		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_phai_tra_bc_thuhoi_post()
	{
//        $flag = notify_token($this->flag_login);
//        if ($flag == false) return;

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";


		$total = $this->contract_model->get_phai_tra_bc_thu_hoi($code_contract, $start);

		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'tien_phai_tra' => $total['tien_phai_tra'],
			'tien_thua' => $total['tien_thua'],
			'thu_hoi_luy_ke' => $total['thu_hoi_luy_ke']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_phai_tra_bc_thuhoi_1_post()
	{
//        $flag = notify_token($this->flag_login);
//        if ($flag == false) return;

		$start = !empty($this->dataPost['start']) ? ($this->dataPost['start']) : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$start_date = strtotime(trim($startMonth) . ' 00:00:00');
		$end_date = strtotime(trim($endMonth) . ' 23:59:59');


		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";

		$condition['code_contract'] = $code_contract;
		$condition['end'] = $end_date;

		$tong_tien_phai_tra = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
		$tong_thu_hoi_luy_ke = $this->transaction_model->find_where(['code_contract' => $code_contract]);
		$thu_hoi_luy_ke_extend = $this->transaction_extend_model->find_where_date($condition);
		$thu_hoi_luy_ke_fn = $this->transaction_model->find_where_date($condition);



		$tong_thu_hoi_luy_ke_fn = 0;
		$tong_thu_hoi_luy_ke_extend = 0;
		$tien_goc_da_thu_hoi_ky = 0;
		$tien_goc_da_thu_hoi = 0;
		$tien_phai_tra = [];
		$tien_tra = 0;
		$total = [];
		$total_tien_phai_tra = [];
		foreach ($thu_hoi_luy_ke_extend as $item1){
			$tong_thu_hoi_luy_ke_extend += $item1->total;
		}
		foreach ($thu_hoi_luy_ke_fn as $item2){
			$tong_thu_hoi_luy_ke_fn += $item2->total;
		}

		if (!empty($tong_thu_hoi_luy_ke)) {
			$count = 0;
		}
		foreach ($tong_tien_phai_tra as $value) {

			$fee_delay_pay = 0;
			if (!empty($value->fee_delay_pay)) {
				$fee_delay_pay = $value->fee_delay_pay;
			}
			$fee_finish_contract = 0;
			if (!empty($value->fee_finish_contract)) {
				$fee_finish_contract = $value->fee_delay_pay;
			}

			if (!empty($value->round_tien_tra_1_ky)){
				$tien_tra += $value->round_tien_tra_1_ky + $fee_delay_pay + $fee_finish_contract;
			} else {
				$tien_tra += $value->tien_tra_1_ky + $fee_delay_pay + $fee_finish_contract;
			}

			$tien_goc_da_thu_hoi += round($value->tien_goc_1ky);

			array_push($tien_phai_tra, $tien_tra);
		}
		$tat_toan = [];
		$thu_hoi_ly_ke = [];
		foreach ($tong_thu_hoi_luy_ke as $item) {

			if ($item->date_pay > $end_date) {
				continue;
			}
//			if ($item->type == 5 && $item->total == 200000) {
//				continue;
//			}
			if (!empty($item->total)) {
				$total_thu_hoi_luy_ke += (int)$item->total;
			}

			if (!empty($item->so_tien_goc_da_tra)) {
				$tien_goc_da_thu_hoi_ky += round($item->so_tien_goc_da_tra);
			}
			if ($item->type == 3) {
				array_push($tat_toan, 2);
			}

			array_push($tat_toan, 1);
			array_push($thu_hoi_ly_ke, $total_thu_hoi_luy_ke);
			array_push($total, $item->total);
			$count++;
		}


		//Get contract
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'tien_phai_tra' => $tien_phai_tra,
			'ky_thu' => $count,
			"tong_thu_hoi_luy_ke" => !empty($thu_hoi_ly_ke) ? $thu_hoi_ly_ke : "",
			"tien_goc_da_thu_hoi_ky" => $tien_goc_da_thu_hoi_ky,
			"tien_goc_da_thu_hoi" => $tien_goc_da_thu_hoi,
			"total" => $total,
			"tat_toan" => $tat_toan,
			"tong_thu_hoi_luy_ke_extend" => $tong_thu_hoi_luy_ke_extend,
			"tong_thu_hoi_luy_ke_fn" => $tong_thu_hoi_luy_ke_fn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function getamount_money_post(){

		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";

		$check = $this->contract_model->findOne(array('code_contract' => $code_contract));

		if (!empty($check)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $check['loan_infor']['amount_money']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function interest_real_post()
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
		$contract = $this->contract_model->getInterestReal($condition);
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
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
//		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');

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
//		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');

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

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
//		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (empty($this->dataPost['start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Dữ liệu post không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01

		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31

		$condition = array();
		if (!empty($start)) {
			$condition['start'] = strtotime(trim($startMonth) . ' 00:00:00');
			$condition['end'] = strtotime(trim($endMonth) . ' 23:59:59');

		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$transaction = $this->transaction_model->getRevokeLoan_view($condition, $per_page, $uriSegment);
	
		

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

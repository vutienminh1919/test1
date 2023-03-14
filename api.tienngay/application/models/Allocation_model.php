<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Allocation_model extends CI_Model
{

	private $collection = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
				$this->load->model("order_model");
		$this->load->model("dashboard_model");
		$this->load->model("transaction_model");
		$this->load->model("service_vimo_model");
		$this->load->model("customer_billing_model");
		$this->load->model("user_model");
		$this->load->model("hotline_model");
		$this->load->model("role_model");
		$this->load->model("storage_card_model");
		$this->load->model("group_role_model");
		$this->load->model("transaction_contract_model");
		$this->load->model("contract_tempo_model");
		$this->load->model("log_model");
		$this->load->model("sms_model");
		$this->load->model("contract_model");
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('temporary_plan_contract_model');
		$this->load->helper('lead_helper');
		$this->load->model('store_model');
		$this->load->model('area_model');
		$this->load->model('log_transaction_model');
		$this->load->model("transaction_extend_model");
		$this->load->model("generate_model");
        $this->load->model('payment_model');
        $this->load->model('coupon_model');
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());

		if(empty($this->uemail)) {
			$this->uemail = 'system';
		}
	}
	private  $dataPost, $isTriple, $libraries, $flag_login, $id, $uemail, $ulang, $app_login, $superadmin, $so_tien_goc_da_tra_tat_toan, $so_tien_lai_da_tra_tat_toan, $so_tien_phi_da_tra_tat_toan, $so_tien_phi_tat_toan_da_tra, $tien_thua_tat_toan, $so_tien_phi_phat_sinh_da_tra, $so_tien_phi_cham_tra_da_tra, $so_tien_phi_gia_han_da_tra;

   private  $so_tien_goc_phai_tra_tat_toan,
		$so_tien_lai_phai_tra_tat_toan,
		$so_tien_phi_phai_tra_tat_toan,
		$so_tien_phi_tat_toan_phai_tra_tat_toan,
		$so_tien_phi_cham_tra_phai_tra_tat_toan,
		$so_tien_phi_gia_han_phai_tra_tat_toan,
		$so_tien_phi_phat_sinh_phai_tra_tat_toan,
		$lai_con_no_thuc_te,
		$phi_con_no_thuc_te,
		$so_tien_goc_phai_tra_hop_dong,
		$so_tien_lai_phai_tra_hop_dong,
		$so_tien_phi_phai_tra_hop_dong,
		$phi_gia_han_phai_tra_hop_dong,
		$phi_cham_tra_phai_tra_hop_dong,
		$phi_tat_toan_phai_tra_hop_dong,
		$phi_phat_sinh_phai_tra_hop_dong;

    //chia tất toán  
  	private function finishContract($code, $amount, $fee_finish_contract, $date_pay)
	{
		$transaction = $this->contract_tempo_model->find_where(array('code_contract' => $code, 'status' => 1));
		foreach ($transaction as $tran) {
			$insert = array();
			//$insert['da_thanh_toan'] = $tran['tien_tra_1_ky'];
			$insert['status'] = 2; // da dong tien
			$insert['current_plan'] = 2; // da dong tien
			$this->contract_tempo_model->findOneAndUpdate(array("_id" => $tran['_id']), $insert);
		}


		// update total debt pay
		$contract = $this->contract_model->findOne(array('code_contract' => $code));
		if (!in_array($contract['status'], [17, 40])) {
			$response = array(
				'status' =>401,
				'message' => "Hợp đồng không ở trạng thái đang vay",
				'transaction_id' => '',
			);
			
			return $response;
		}
		$update_contract = array(
			"status" => 19, // da tat toan
			"updated_by" => $this->uemail,
			"updated_at" => $this->createdAt,
		);
		$this->contract_model->findOneAndUpdate(array("code_contract" => $code), $update_contract);
		$total_debt_pay = !empty($contract['total_debt_pay']) ? (int)$contract['total_debt_pay'] : 0;
		$total_debt_pay = $total_debt_pay + $amount;
		$this->contract_model->findOneAndUpdate(array("_id" => $contract['_id']), array('total_debt_pay' => $total_debt_pay));


	}



	
	private function getTotalPaid($code_contract, $date_pay)
	{
		$dataDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		if (empty($dataDB)) {
			return 0;
		}
		// hop dong
		$fee = array();
		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		$percent_prepay_phase_1 = 0;
		$percent_prepay_phase_2 = 0;
		$percent_prepay_phase_3 = 0;
		if (!empty($dataDB['fee'])) {
			$fee = $dataDB['fee'];
			if (empty($fee['percent_advisory'])) {
				$fee['percent_advisory'] = 0;
			}
			if (empty($fee['percent_expertise'])) {
				$fee['percent_expertise'] = 0;
			}
			if (empty($fee['percent_interest_customer'])) {
				$fee['percent_interest_customer'] = 0;
			}
			if (empty($fee['percent_prepay_phase_1'])) {
				$fee['percent_prepay_phase_1'] = 0;
			}
			if (empty($fee['percent_prepay_phase_2'])) {
				$fee['percent_prepay_phase_2'] = 0;
			}
			if (empty($fee['percent_prepay_phase_3'])) {
				$fee['percent_prepay_phase_3'] = 0;
			}
			if (empty($fee['penalty_percent'])) {
				$fee['penalty_percent'] = 0;
			}
			if (empty($fee['penalty_amount'])) {
				$fee['penalty_amount'] = 0;
			}
			$pham_tram_phi_tu_van = floatval($fee['percent_advisory']) / 100;
			$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise']) / 100;
			$lai_suat_ndt = floatval($fee['percent_interest_customer']) / 100;
			$percent_prepay_phase_1 = floatval($fee['percent_prepay_phase_1']) / 100;
			$percent_prepay_phase_2 = floatval($fee['percent_prepay_phase_2']) / 100;
			$percent_prepay_phase_3 = floatval($fee['percent_prepay_phase_3']) / 100;
			$phan_tram_phi_phat_tra_cham = floatval($fee['penalty_percent']) / 100;
			$phi_phat_tra_cham = floatval($fee['penalty_amount']);
		}
		$tien_gian_ngan = (int)$dataDB['loan_infor']['amount_money'];
		// tinh toan
		$total_money_remaining = 0;
		$kiem_tra_tat_toan = false;
		//$date_now = date('d-m-Y');
		$now = (empty($date_pay)) ? $date_pay : strtotime(date('Y-m-d') . ' 23:59:59');
		$all_contract_tempo = $this->contract_tempo_model->find_where(array('code_contract' => $dataDB['code_contract']));
		if (empty($all_contract_tempo)) {
			return array();
		}
		$so_ngay_vay = (isset($this->contract_model->get_phi_phat_cham_tra((string)$dataDB['_id'], $now)['so_ngay_vay'])) ? (int)$this->contract_model->get_phi_phat_cham_tra((string)$dataDB['_id'], $now)['so_ngay_vay'] : (int)$dataDB['loan_infor']['number_day_loan'];
		$period_pay_interest = (int)$dataDB['loan_infor']['period_pay_interest'];
		$so_ky_vay = $so_ngay_vay / $period_pay_interest;
		$type_interest = (int)$dataDB['loan_infor']['type_interest'];
		// ngay giai ngan
		$ngay_giai_ngan = date('d-m-Y', $dataDB['disbursement_date']);
		$timestamp_ngay_giai_ngan = strtotime($ngay_giai_ngan);
		$timestamp_ngay_tat_toan = $timestamp_ngay_giai_ngan + $so_ngay_vay * 24 * 3600 - 24 * 60 * 60;
		$datediff = $now - $timestamp_ngay_tat_toan;
		$tong_tien_lai_phi_tat_toan = 0;
		$tien_phi_phat_tra_cham = 0;
		if ($so_ngay_vay == 30) {
			$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_chenh_lech = $so_ngay_vay_thuc_te - $so_ngay_vay;
			$tien_goc_con = $tien_gian_ngan;
			if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt * $tien_gian_ngan;
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan;
				$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu + $tien_gian_ngan;
				$phi_thanh_toan_truoc_han = 0;
			} else if (($so_ngay_vay_thuc_te >= (2 * $so_ngay_vay / 3)) && $so_ngay_chenh_lech <= 0) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan * $phi_thanh_toan_truoc_han;
			} else if (($so_ngay_vay_thuc_te >= ($so_ngay_vay / 3)) && ($so_ngay_vay_thuc_te <= (2 * $so_ngay_vay / 3 - 1))) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan * $phi_thanh_toan_truoc_han;
			} else if (($so_ngay_vay_thuc_te >= 0) && ($so_ngay_vay_thuc_te <= ($so_ngay_vay / 3 - 1))) {
				$tien_lai_nha_dau_tu = $lai_suat_ndt * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan * $phi_thanh_toan_truoc_han;
			} else {
				$tien_phi_phat_tra_cham = $tien_gian_ngan * $phan_tram_phi_phat_tra_cham * $so_ngay_vay_thuc_te / 30;
				$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
				$tien_lai_nha_dau_tu = $lai_suat_ndt * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan / 30 * $so_ngay_vay_thuc_te;
				$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $tien_lai_nha_dau_tu;
				$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
				$tong_tien_thanh_toan = $tong_tien_lai_phi_tat_toan * $phi_thanh_toan_truoc_han;
				$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
			}

		} else {
			$tien_goc_con = 0;
			$phi_tu_van = $pham_tram_phi_tu_van * $tien_gian_ngan;
			$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_gian_ngan;
			$condition_success = array(
				'status' => 2,
				'code_contract' => $dataDB['code_contract'],
			);
			$contract_success = $this->contract_tempo_model->find_where_success($condition_success);
			if (!empty($contract_success)) { // hop dong da thanh toan ky lai
				$ngay_tra_lai_ky = $contract_success[count($contract_success) - 1]['ngay_ky_tra'];
				$tien_goc_con = $contract_success[count($contract_success) - 1]['tien_goc_con'];
				// ngay giai ngan
				$ngay_tra_lai_ky_gan_nhat = date('d-m-Y', $ngay_tra_lai_ky);
				$timestamp_tra_lai_ky_gan_nhat = strtotime($ngay_tra_lai_ky_gan_nhat);
				$datediff = $now - $timestamp_tra_lai_ky_gan_nhat;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			} else {
				$tien_goc_con = $tien_gian_ngan;
				$datediff = $now - $timestamp_ngay_giai_ngan;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			}
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_da_vay = round($datediff / (60 * 60 * 24));
			$so_ngay_da_vay = $so_ngay_da_vay + 1;
			$so_ngay_chenh_lech = $so_ngay_da_vay - $so_ngay_vay;
			if ($type_interest == 1) { // du no giam dan
				$goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_gian_ngan);
				$lai_ky = $lai_suat_ndt * $tien_goc_con;
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$phi_thanh_toan_truoc_han = 0;
				} else if (($so_ngay_da_vay >= (2 * $so_ngay_vay / 3)) && $so_ngay_chenh_lech <= 0) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con * $phan_tram_phi_phat_tra_cham * $so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			} else { // lai hang thang goc cuoi ky
				$lai_ky = round($lai_suat_ndt * $tien_gian_ngan);
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky + $tien_gian_ngan;
				} else if (($so_ngay_da_vay >= (2 * $so_ngay_vay / 3)) && $so_ngay_chenh_lech <= 0) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con * $phan_tram_phi_phat_tra_cham * $so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			}

		}
		return array(
			'tong_tien_thanh_toan' => $tong_tien_thanh_toan,
			'phi_thanh_toan_truoc_han' => $phi_thanh_toan_truoc_han
		);
	}



	public function approve_contract($data)
	{
		
		$data['transaction_id'] = $this->security->xss_clean($data['transaction_id']);
		$data['code_transaction_bank'] = $this->security->xss_clean($data['code_transaction_bank']);
		$data['bank'] = $this->security->xss_clean($data['bank']);
		$data['approve_note'] = $this->security->xss_clean($data['approve_note']);
		$data['status'] = $this->security->xss_clean($data['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($data['transaction_id'])));
		if (empty($transaction) ) {
			$response = array(
				'status' => '401',
				'message' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			
			return $response;
		}
       
		if (!empty($data['code_transaction_bank']) ) {
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $data['code_transaction_bank'], "status" => array('$ne' => 3), "code" => array('$ne' => $transaction['code'])));


			if (!empty($transaction_ck_pt)) {
				foreach ($transaction_ck_pt as $key => $value) {
					if (date("Ymd", $value['date_pay']) != date("Ymd", $transaction['date_pay'])) {
						$response = array(
							'status' => '401',
							'message' => "Phiếu thu đã tồn tại (khác ngày):"
						);
						
						return $response;
					}

					if (date("Ymd", $value['date_pay']) == date("Ymd", $transaction['date_pay']) && $value['code_contract'] == $transaction['code_contract']) {
						$response = array(
							'status' => '401',
							'message' => "Phiếu thu đã tồn tại (trùng ngày):"
						);
						
						return $response;
					}
				}
			}
		}
		$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($transaction['code_contract']);
				$phi_gia_han = 0;
			$type_payment=isset($transaction['type_payment']) ? $transaction['type_payment'] : 1;
                if($type_payment==2)
                {
                	$phi_gia_han_origin= 200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }
                 if($type_payment==0)
                {
                	$type_payment=1;

                }
           $transaction['type_payment']=$type_payment;
	    $date_pay = (isset($transaction['date_pay'])) ? strtotime(date("Y-m-d", $transaction['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');

		$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));
		
		$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
		$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['phi_phat_sinh'];
		$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['so_ngay_phat_sinh'];
		$so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['so_tien_phat_sinh'];



		if ((int)$data['status'] == 1 || (int)$data['status'] == 5) {
			if ($transaction['type'] == 3) {
				$total = $this->getTotalPaid($transaction['code_contract'], $date_pay);
				if (empty($total)) {
					$response = array(
						'status' => '401',
						'message' => "Hợp đồng không tồn tại kỳ thanh toán",
						'transaction_id' => '',
					);
					
					return $response;
				}
				
               
				
			} else if ($transaction['type'] == 4) {
			
				
			}
			$url = $data['transaction_id'];
			$message = 'Duyệt thanh toán thành công';

		} else {
			if($type_payment==2 && $contractDB['status'] !=33)
                {
                 $this->contract_model->update(
					array("code_contract" => $contractDB['code_contract']),
					array("status"=>17)
				   );	
                }
             if($type_payment==3 && $contractDB['status'] !=34)
                {
                 $this->contract_model->update(
					array("code_contract" => $contractDB['code_contract']),
					array("status"=>17)
				   );	
                }
			$url = 'transaction';
			$message = 'Hủy thanh toán thành công';
			
		}
		
		//Update
		$update_tran=array(
				"code_transaction_bank" => $data['code_transaction_bank'],
				"bank" => $data['bank'],
				"approve_note" => $data['approve_note'],
				"status" => (int)$data['status'],
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
				"fee_delay_pay" => $phi_phat_tra_cham,
				"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
				"so_tien_phat_sinh"=>$so_tien_phat_sinh,
				"type_payment" => $type_payment,
			
			);
		if($type_payment==2)
		{
		$code_contract_parent_gh=(isset($contractDB['code_contract_parent_gh'])) ? $contractDB['code_contract_parent_gh'] : $contractDB['code_contract_disbursement'];
           $update_tran['code_contract_parent_gh']=$code_contract_parent_gh;
		}
		if($type_payment==3)
		{
          $code_contract_parent_cc=(isset($contractDB['code_contract_parent_cc'])) ? $contractDB['code_contract_parent_cc'] : $contractDB['code_contract_disbursement'];
           $update_tran['code_contract_parent_cc']=$code_contract_parent_cc;
		}
		if ((int)$data['status'] == 1 || (int)$data['status'] == 5) {
			$update_tran['approved_at']= $this->createdAt;
			$update_tran['approved_by']= $this->uemail;
		}
		$transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			$update_tran
			
		);
		$this->generate_model->debt_recovery_one($transaction['code_contract']);
		$log = array(
			"type" => "transaction",
			"action" => 'approve_transaction',
			"data_post" => $data,
			"transaction_id" => $transaction['code_contract'],
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$log_ksnb = array(
			"type" => "contract_ksnb",
			"action" => "Approve",
			"transaction_ksnb_id" => (string)$data['transaction_id'],
			"old" => $transaction,
			"new" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log_ksnb);
		$this->log_model->insert($log);
		$response = array(
			'status' => '200',
			'msg' => $message,
			'url' => $url,
			'data' => $transaction,
			'data_contract'=>$contractDB
		);
		
		return $response;

	}
	public function payment_all_contract($data)
	{
		
		if (empty($data['code_contract'])) {
			$response = array(
				'status' => '401',
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			
			return $response;
		}
		 $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $data['code_contract']));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	$response = array(
				'status' => '401',
				'message' => "Hợp đồng đã khóa"
			);
			
			return $response;
        }
		$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $data['code_contract'], "status" => ['$in'=>[1,5]],'type'=>array('$in'=>[3,4])));
		if (empty($data_transaction)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			
			return $response;
		}
		if (!empty($data_transaction))
			foreach ($data_transaction as $key => $value) {
				 
				//if (!empty($value['temporary_plan_contract_id'])) continue;
				$transaction = $value;

				$date_pay = (isset($value['date_pay'])) ? strtotime(date("Y-m-d", $value['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $value['created_at']) . '  23:59:59');
				$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));
				$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
				$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['phi_phat_sinh'];
				$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_ngay_phat_sinh'];
		       $so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_tien_phat_sinh'];
				
				$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($transaction['code_contract']);
				$phi_gia_han = 0;
				$type_payment=isset($transaction['type_payment']) ? $transaction['type_payment'] : 1;

                if($type_payment==2)
                {
                	$phi_gia_han_origin= isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }

                if($type_payment==0)
                {
                	$type_payment=1;
                }
				if ($transaction['type'] == 3 || $type_payment==3) {

					
                   $tien_thua_thanh_toan=0;
			       $transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
				$transaction['total'] = $transaction['total']+$tien_thua_thanh_toan ;
				  if($type_payment==2)
		           {
				 //$transaction['total'] =$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   }
					$phi_thanh_toan_truoc_han = $this->contract_model->get_phi_tat_toan_truoc_han($contractDB, $date_pay);

					//Get lãi NĐT đã trừ vào kỳ cuối (với các HĐ có coupon giảm lãi kỳ đầu trừ vào kỳ cuối)
					$tien_lai_da_tru_vao_ky_cuoi = 0;
					if ( date('Y-m-d', $date_pay) == date('Y-m-d', $contractDB['expire_date']) && $contractDB['interest_reduction']['isset_date_late'] == false) {
						$tien_lai_da_tru_vao_ky_cuoi = !empty($contractDB['interest_reduction']['amount_interest_reduction']) ? $contractDB['interest_reduction']['amount_interest_reduction'] : 0;
					}
					//Update lại kì hiện tại
					$this->cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, (int)$transaction['total'], $phi_thanh_toan_truoc_han, $phi_phat_tra_cham, $date_pay, $phi_phat_sinh, $phi_gia_han,$type_payment,(int)$transaction['total_deductible'], (string)$transaction['_id'], $tien_lai_da_tru_vao_ky_cuoi);
					//Update các kì trước kì tất toán
					$this->cap_nhat_tat_toan_cac_ki_truoc_do($contractDB, $phi_phat_tra_cham, $date_pay);
					//Update các kì tiếp theo
					// $this->cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB, $phi_phat_tra_cham, $date_pay);
					if($type_payment!=3)
					{
					$this->finishContract($transaction['code_contract'], (int)$transaction['total'], $phi_thanh_toan_truoc_han, $date_pay);
				    }
					
					
					$this->tinh_goc_lai_phi_transaction_tat_toan($transaction['_id'], $phi_phat_sinh);
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);

				} else if ($transaction['type'] == 4  && $type_payment!=3) {
				
                   $tien_thua_thanh_toan = 0;
                   //tiền thừa
		           if($type_payment==2)
		           {
					$transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
				}
				
					$transaction['total'] = $transaction['total']+ $tien_thua_thanh_toan ;
				//tiền thiếu
		          if($type_payment==2)
		           {
				 $transaction['total'] =$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   }
					$this->finishTempoPlan($transaction['code_contract'], (int)$transaction['total'], $date_pay,(int)$transaction['total_deductible']);
					$this->tinhtoanBangLaiKy($transaction['code_contract'], (int)$transaction['total'], (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$transaction, (int)$transaction['total_deductible']);
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);
				}
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;

				$update_tran=array(
						"updated_at" => $this->createdAt,
						"updated_by" => $this->uemail,
						"fee_delay_pay" => $phi_phat_tra_cham,
						"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
						"so_tien_phat_sinh"=>$so_tien_phat_sinh,
						"type_payment" => $type_payment,
						'ky_da_tt_gan_nhat'=>$ky_da_tt_gan_nhat
						
					);
				if($type_payment==2)
				{
				if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59'))
	            {	
				 $update_tran['so_ngay_phat_sinh']=0;
                 $update_tran['so_tien_phat_sinh']=0;
                 }	
				$code_contract_parent_gh=(isset($contractDB['code_contract_parent_gh'])) ? $contractDB['code_contract_parent_gh'] : $contractDB['code_contract'];
		           $update_tran['code_contract_parent_gh']=$code_contract_parent_gh;
				}
				if($type_payment==3)
				{
		          $code_contract_parent_cc=(isset($contractDB['code_contract_parent_cc'])) ? $contractDB['code_contract_parent_cc'] : $contractDB['code_contract'];
		           $update_tran['code_contract_parent_cc']=$code_contract_parent_cc;
				}
				$update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $transaction['_id']),
					 $update_tran
					
				);
				

			}
			$this->generate_model->debt_recovery_one($data['code_contract']);
			$response = array(
					'status' => '200',
					'msg' => 'OK',
					'url' => '',
					'data'=>''
				);
				
				return $response;


	}
		public function get_con_lai_sau_thanh_toan($code_contract,$date_pay)
	{
		$ptData=$this->temporary_plan_contract_model->get_tien_phai_tra_hop_dong($code_contract,$date_pay);
		$dtData=$this->temporary_plan_contract_model->get_tien_da_tra_sau_thanh_toan($code_contract,$date_pay);
		$goc_phai_tra= !empty($ptData['tien_goc_phai_tra']) ? $ptData['tien_goc_phai_tra'] : 0;
		$lai_phai_tra=!empty($ptData['tien_lai_phai_tra']) ? $ptData['tien_lai_phai_tra'] : 0;
		$phi_phai_tra=!empty($ptData['tien_phi_phai_tra']) ? $ptData['tien_phi_phai_tra'] : 0;
		$cham_tra_phai_tra=!empty($ptData['tien_cham_tra_phai_tra']) ? $ptData['tien_cham_tra_phai_tra'] : 0;
		$goc_da_tra=!empty($dtData['tien_goc_da_tra']) ? $dtData['tien_goc_da_tra'] : 0;
		$lai_da_tra=!empty($dtData['tien_lai_da_tra']) ? $dtData['tien_lai_da_tra'] : 0;
		$phi_da_tra=!empty($dtData['tien_phi_da_tra']) ? $dtData['tien_phi_da_tra'] : 0;
		$cham_tra_da_tra=!empty($dtData['tien_cham_tra_da_tra']) ? $dtData['tien_cham_tra_da_tra'] : 0;

		$goc_con_lai=$goc_phai_tra-$goc_da_tra;
		$lai_con_lai=$lai_phai_tra-$lai_da_tra;
		$phi_con_lai=$phi_phai_tra-$phi_da_tra;
		$cham_tra_con_lai=$cham_tra_phai_tra-$cham_tra_da_tra;
		return ['goc_con_lai'=>$goc_con_lai,
		        'lai_con_lai'=>$lai_con_lai,
		        'phi_con_lai'=>$phi_con_lai,
		        'cham_tra_con_lai'=>$cham_tra_con_lai,
		       ];


	}
	public function payment_all_contract_gh_cc($data)
	{
		
		 $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $data['code_contract']));
		 //hợp đồng khóa thì không chạy lại
       if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	    $response = array(
					'status' => '200',
					'msg' => 'OK',
				);
				return $response;
        }
		$last=(isset($data['last'])) ? $data['last'] : 0;
		//loại gia hạn / cơ cấu
		$type_gh_cc=(isset($data['type_gh_cc'])) ? $data['type_gh_cc'] : '';
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$data['code_contract']));
		
		$KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($contractDB['code_contract']);
		
		//lấy phiếu thu thanh toán hoặc tất toán theo gia hạn hoặc cơ cấu
		 if($type_gh_cc=="GH"  )
				{

			$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $data['code_contract'], "status" => 1,'type'=>array('$in'=>[3,4])));

	   }else{
         $data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $data['code_contract'], "status" => 1,'type'=>array('$in'=>[3,4])));

	   }
	
		if (empty($data_transaction)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			
			return $response;
		}
		if (!empty($data_transaction))
		{
			foreach ($data_transaction as $key => $value) {

				$transaction = $value;

				$date_pay = (isset($transaction['date_pay'])) ? strtotime(date("Y-m-d", $transaction['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');
				$arr_data=[
					'date_pay'=>$date_pay,
					'id_contract'=>(string)$contractDB['_id'],
				];
				$payment=$this->payment_model->get_payment($arr_data)['contract'];
				$so_tien_phi_cham_tra_phai_tra=(isset($payment['penalty_pay'])) ? $payment['penalty_pay'] : 0;
				$tat_toan_part_2=$this->payment_model->get_infor_tat_toan_part_2(['code_contract'=>$contractDB['code_contract'],'date_pay'=>$date_pay]);
				$so_tien_lai_phai_tra=(isset($tat_toan_part_2['lai_chua_tra_qua_han'])) ? $tat_toan_part_2['lai_chua_tra_qua_han'] : 0;
				$so_tien_phi_phai_tra=(isset($tat_toan_part_2['phi_chua_tra_qua_han'])) ? $tat_toan_part_2['phi_chua_tra_qua_han'] : 0;

				$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
				$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['phi_phat_sinh'];
				$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_ngay_phat_sinh'];
				$so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_tien_phat_sinh'];
				$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($contractDB['code_contract']);
				$phi_gia_han = 0;
				$type_payment= 1;
                //phiếu thu cuối gắn là phiếu thu gia hạn
                if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {
                   $phi_phat_tra_cham=[];
	            }	
                if((count($data_transaction)-1)==$key && $last!=1 && $type_gh_cc=="GH")
                {
                	$type_payment=2;
                	$phi_gia_han_origin= isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }
                //phiếu thu cuối gắn là phiếu thu cơ cấu
                  if((count($data_transaction)-1)==$key && $last!=1 && $type_gh_cc=="CC")
                {
                	$type_payment=3;
                	
                }
                //tất toán và cơ cấu
				if ($transaction['type'] == 3 || $type_payment==3) {

                   $tien_thua_thanh_toan=0;
			       $transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1));
			       //lấy tiền thừa
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
					//cộng tiền thừa
				$money_tt = $transaction['total']+$tien_thua_thanh_toan;
                    //trừ tiền thiếu
				 $money_tt=$this->chia_tien_thieu($money_tt,$contractDB['code_contract_parent_gh']);
				   
			
					$phi_thanh_toan_truoc_han = $this->contract_model->get_phi_tat_toan_truoc_han($contractDB, $date_pay);
					$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
						"code_contract" => $codeContract
					));
                  
					
					//Update lại kì hiện tại
					$this->cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, (int)$money_tt, $phi_thanh_toan_truoc_han, $phi_phat_tra_cham, $date_pay, $phi_phat_sinh, $phi_gia_han,$type_payment,(int)$transaction['total_deductible'], (string)$transaction['_id']);
					//Update các kì trước kì tất toán
					$this->cap_nhat_tat_toan_cac_ki_truoc_do($contractDB, $phi_phat_tra_cham, $date_pay);
					//Update các kì tiếp theo
					//$this->cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB, $phi_phat_tra_cham, $date_pay);
					if($type_payment!=3)
					{
					$this->finishContract($contractDB['code_contract'], (int)$money_tt, $phi_thanh_toan_truoc_han, $date_pay);
				    }
					
					//$this->tinhtoanBangLaiKy($contractDB['code_contract'],(int)$transaction['total'], $transDB['_id']);
					$this->tinh_goc_lai_phi_transaction_tat_toan($transaction['_id'], $phi_phat_sinh);
					//gen lãi tháng
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);

				} else if ($transaction['type'] == 4 && $type_payment!=3) {
					 //thanh toán
					 $tien_thua_thanh_toan = 0;
					 //lấy tiền thừa gia hạn 
	                 if($type_payment==2)
	                 {
	                 	$transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
										          )
										
										);

									}
								}
	                 }
                    
					$transaction['total'] = $transaction['total']+$tien_thua_thanh_toan;
					//chia tiền thiếu
			     	$transaction['total']=$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   //gán trạng thái hoàn thành 
					$this->finishTempoPlan($contractDB['code_contract'], (int)$transaction['total'],$date_pay,(int)$transaction['total_deductible']);
					//chia bảng lãi kỳ
					$this->tinhtoanBangLaiKy($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$transaction, (int)$transaction['total_deductible']);
					//tính lại bảng lãi tháng
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);
				}
				//lấy kỳ thanh toán gần nhất
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                 $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;
				$arr_update_tran=	array(
						"updated_at" => $this->createdAt,
						"updated_by" => $this->uemail,
						"fee_delay_pay" => $phi_phat_tra_cham,
						"code_contract" => $contractDB['code_contract'],
						"code_contract_disbursement" =>  $contractDB['code_contract_disbursement'],
						"type_payment" => $type_payment,
						"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
						"so_tien_phat_sinh"=>$so_tien_phat_sinh,
						"ky_da_tt_gan_nhat"=>$ky_da_tt_gan_nhat,
					);
				 
				if($type_gh_cc=="CC" )
				{
			    $arr_update_tran['code_contract_parent_cc']= $data['code_contract_origin'];
			    $arr_update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
			    //trước 30/9 không tính phí phát sinh, chậm trả
			      if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {	
				 $arr_update_tran['so_ngay_phat_sinh']=0;
                 $arr_update_tran['so_tien_phat_sinh']=0;
                 $arr_update_tran['so_tien_phi_cham_tra_phai_tra']=0; 
                 }
			     }
			     if($type_gh_cc=="GH")
				{
					
			    $arr_update_tran['code_contract_parent_gh']= $data['code_contract_origin'];
			    $arr_update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
			    $arr_update_tran['so_tien_lai_phai_tra']= (float)$so_tien_lai_phai_tra;
			    $arr_update_tran['so_tien_phi_phai_tra']= (float)$so_tien_phi_phai_tra;
			    //trước 30/9 không tính phí phát sinh, chậm trả
			    if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {	
				 $arr_update_tran['so_ngay_phat_sinh']=0;
                 $arr_update_tran['so_tien_phat_sinh']=0;
                 $arr_update_tran['so_tien_phi_cham_tra_phai_tra']=0; 
                 }
			     }
			     $arr_update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $transaction['_id']),
					$arr_update_tran
				
				);
				
			}
		}
		//cập nhật trường debt contract
			$this->generate_model->debt_recovery_one($data['code_contract']);
			   $response = array(
					'status' => '200',
					'msg' => 'OK',
					'url' => '',
					'data'=>$phi_gia_han
				);
				
				return  $response;


	}
	public function chia_tien_thieu($money,$code_contract_parent_gh)
	{
		$amountRemain=$money;
		$transactionData = $this->transaction_model->find_where(array('code_contract_parent_gh' =>$code_contract_parent_gh,'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
						$so_thieu_da_tra_transaction=0;
						foreach ($transactionData as $key => $value) {
                    $so_tien_thieu_con_lai=!empty($value['so_tien_thieu_con_lai']) ? $value['so_tien_thieu_con_lai'] : 0;
                     $so_tien_thieu_da_chuyen=!empty($value['so_tien_thieu_da_chuyen']) ? $value['so_tien_thieu_da_chuyen'] : 0;
						

		if ($amountRemain > 0) {
					//tiền thiếu
					if ($so_tien_thieu_con_lai > 0) {
						if ($amountRemain >= $so_tien_thieu_con_lai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['so_tien_thieu_da_chuyen'] = $so_tien_thieu_da_chuyen + $so_tien_thieu_con_lai;

							$so_thieu_da_tra_transaction = $so_thieu_da_tra_transaction + $so_tien_thieu_con_lai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['so_tien_thieu_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $so_tien_thieu_con_lai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['so_tien_thieu_da_chuyen'] = $so_tien_thieu_da_chuyen + $amountRemain;

							$so_thieu_da_tra_transaction = $so_thieu_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['so_tien_thieu_con_lai'] = $so_tien_thieu_con_lai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->transaction_model->update(
							array("_id" => $value['_id']),
							$dataUpdate
						);
					}

				
				}

						}
					}
		return $amountRemain;
	}
	public function generate_money_gh($data)
	{
		
		if (empty($data['code_contract'])) {
			$response = array(
				'status' => '401',
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			
			return $response;
		}
		$date_pay=$data['disbursement_date'];
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$data['code_contract']));
		$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract_parent_gh' => $data['code_contract_origin'], "status" => 1,'type'=>4,'type_payment'=>2,'tien_thua_thanh_toan_con_lai'=>array('$gt'=>0)));
		if (empty($data_transaction)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			
			return $response;
		}
		foreach ($data_transaction as $key => $value) {
			$code=isset($value['code']) ? $value['code'] : '';
			$data_transaction_extend = $this->transaction_extend_model->findOne(array('code_parent' => $code,'code_contract'=>$value['code_contract']));
			if(empty($data_transaction_extend))
			{
				$insert_extend=	array(
                        "code_contract" => $value['code_contract'],
                        "code_contract_disbursement" => $value['code_contract_disbursement'],
                        "code_contract_parent_gh" => $value['code_contract_parent_gh'],
                        "code_parent" =>$code,
                        "total"=>$value['tien_thua_thanh_toan_con_lai'],
						"so_tien_lai_da_tra" => 0,
						"so_tien_phi_da_tra" => 0,
						"so_tien_goc_da_tra" => 0,
						"tien_phi_phat_sinh_da_tra" => 0,
						"fee_delay_pay" => array(),
						"tien_thua_tat_toan" => 0,
						"so_ngay_phat_sinh" => 0,
						"so_tien_phat_sinh" => 0,
						"tien_thua_thanh_toan" => $value['tien_thua_thanh_toan_con_lai'],
						"tien_thua_thanh_toan_da_tra" => 0,
						"tien_thua_thanh_toan_con_lai" => $value['tien_thua_thanh_toan_con_lai'],
						"fee_finish_contract" => 0,
						"so_tien_phi_cham_tra_da_tra" => 0,
						"so_tien_phi_gia_han_da_tra" => 0,
						"created_at" => $this->createdAt
					);

				$this->transaction_extend_model->insert($insert_extend);
				$log = array(
						"type" => "transaction",
						"action" => 'generate_money_extend_insert',
						"data_post" => $data,
						"transaction_code" => (string)$value['code'],
						"email" => $this->uemail,
						"created_at" => $this->createdAt
					);
					$this->log_transaction_model->insert($log);
			}
			
		}
		        $response = array(
					'status' => '200',
					'msg' => 'OK'
				);
				return $response;
      
	}
	//tạo tiền thiếu gia hạn 
	public function generate_money_thieu_gh($data)
	{
		
		if (empty($data['code_contract'])) {
			
			   $response = array(
					'status' => '401',
					'msg' => 'Mã phiếu ghi không thể trống'
				);
				return $response;
		}
		$date_pay=$data['disbursement_date'];
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$data['code_contract']));
		if (empty($contractDB)) {
			
			$response = array(
					'status' => '401',
					'msg' => 'Không tồn tại hợp đồng'
				);
				return $response;
		}
		 $tranDT = $this->transaction_model->findOne(array('code_contract' => $data['code_contract'], "status" => 1,'type'=>4,"type_payment"=>2));
		 $cond = ['code_contract' => $contractDB['code_contract'] ];
		if ( empty($tranDT)) {
			$response = array(
					'status' => '401',
					'msg' => 'Không tồn tại phiếu thu'
				);
				return $response;
		}
		$tong_tien_thua_thanh_toan = $this->transaction_model->sum_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1,'type_payment'=>1),'$tien_thua_thanh_toan');
		
		$so_tien_da_tra=!empty($tranDT['total']) ? $tranDT['total'] : 0;
		$so_tien_lai_phai_tra=!empty($tranDT['so_tien_lai_phai_tra']) ? $tranDT['so_tien_lai_phai_tra'] : 0;
		$so_tien_phi_phai_tra=!empty($tranDT['so_tien_phi_phai_tra']) ? $tranDT['so_tien_phi_phai_tra'] : 0;
		$so_tien_phi_phat_sinh_phai_tra=!empty($tranDT['phi_phat_sinh']) ? $tranDT['phi_phat_sinh'] : 0;
		$phi_gia_han=isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
		$so_tien_phi_cham_tra_phai_tra=!empty($tranDT['so_tien_phi_cham_tra_phai_tra']) ? $tranDT['so_tien_phi_cham_tra_phai_tra'] : 0;
	    $so_tien_thieu=($so_tien_lai_phai_tra+$so_tien_phi_phai_tra+$so_tien_phi_phat_sinh_phai_tra+$so_tien_phi_cham_tra_phai_tra+$phi_gia_han)-$so_tien_da_tra-$tong_tien_thua_thanh_toan;
	    //trước 30/9
	    if($tranDT['date_pay'] <= strtotime('2021-09-30  23:59:59'))
	    {
           $so_tien_thieu=($so_tien_lai_phai_tra+$so_tien_phi_phai_tra+$so_tien_phi_cham_tra_phai_tra+$phi_gia_han)-$so_tien_da_tra;
	    }
	    if($so_tien_thieu < 0)
	    {
	    	$so_tien_thieu=0;
	    }
	    	
	    $transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $tranDT['_id']),
			[
				"so_tien_thieu"=>$so_tien_thieu,
				"so_tien_thieu_da_chuyen"=>0,
				"so_tien_thieu_con_lai"=>$so_tien_thieu
			]
			
		);
		       $response = array(
					'status' => '200',
					'msg' => 'OK',
				
				);
				
				return $response;
      
	}
	public function payment_tien_thua_gh_cc($data)
	{
		
		if (empty($data['code_contract'])) {
			$response = array(
				'status' => '401',
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			
			return $response;
		}
		$last=(isset($data['last'])) ? $data['last'] : 0;
		$type_gh_cc=(isset($data['type_gh_cc'])) ? $data['type_gh_cc'] : '';
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$data['code_contract']));
		
        $data_transaction = $this->transaction_extend_model->find_where_pay_all(array('code_contract_parent_gh' => $data['code_contract_origin'],'tien_thua_thanh_toan_con_lai'=>array('$gt'=>0)));
		if (empty($data_transaction)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			
			return $response;
		}
		if (!empty($data_transaction))
			foreach ($data_transaction as $key => $value) {
				//if (!empty($value['temporary_plan_contract_id'])) continue;
				$transaction = $value;
                  $tien_thua=isset($transaction['total']) ? $transaction['total'] : 0;
				$date_pay = (isset($contractDB['disbursement_date'])) ? strtotime(date("Y-m-d", $contractDB['disbursement_date']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');
			    
				$phi_phat_tra_cham =0;
				$phi_phat_sinh = 0;
				$so_ngay_phat_sinh = 0;
				$so_tien_phat_sinh = 0;
				$phi_gia_han_da_tra = 0;
				$phi_gia_han = 0;
				$type_payment=22;
                
               
					$money_total = $tien_thua;
				
					$this->finishTempoPlan($contractDB['code_contract'], $money_total,$date_pay);
					$this->tinhtoanBangLaiKy($contractDB['code_contract'], $money_total, (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
					//$this->tinhtoanBangLaiThang($contractDB['code_contract'], $money_total, '');
					$arr_update_tran=array();
				$arr_update_tran['code_contract']=$contractDB['code_contract'];
				$arr_update_tran['code_contract_disbursement']=$contractDB['code_contract_disbursement'];
				//Update
				$transDB = $this->transaction_extend_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					$arr_update_tran
				
				);
				}
				
			$this->generate_model->debt_recovery_one($data['code_contract']);
			$response = array(
					'status' => '200',
					'msg' => 'OK',
					'url' => '',
					'data'=>$phi_gia_han
				);
				
				return $response;


	}
	public function tinhtoanBangLaiKy($codeContract, $amount, $transId, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$tranData,$amount_giam_tru)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi kỳ
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		if($amount>0)
		{
		if($type_payment==1 )
		{
		$this->bangLaiKy_tinhtoan_tien_datra_conlai($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		$this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount_giam_tru, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
	    }
	   
	    if($type_payment==2 )
		{
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_gh($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount_giam_tru, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		}
		 if($type_payment==22)
		{
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_thua($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		}
	}
	}
     private function bangLaiKy_tinhtoan_tien_datra_conlai($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$date_pay_tt=0)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		foreach ($temps as $key=>$temp) {
			if($date_pay_tt>0 && strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00')>$date_pay_tt)
			{
				continue;
			}

			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}
			

			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}
            //trước 1/5
			if (strtotime(date('Y-m-d',$date_pay). ' 00:00:00') < strtotime('2021-05-01 00:00:00') && $amountRemain > 0) {
  
					if($amountRemain < ($goc_con_lai_ky_hien_tai + $lai_con_lai_ky_hien_tai+ $phi_con_lai_ky_hien_tai ) && $amountRemain !=$amount && $temp['status']==1 && strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') )
					{
                      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
					}
			}else{
	 
			    if ( strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') && $amountRemain > 0 ) {
				      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
			   }
			     
	     	}


			$date_ngay_t = strtotime($this->config->item("date_t_apply"));
			if ($contractDB['disbursement_date'] > $date_ngay_t || $type_interest==2 ) {


				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_goc_da_tra" => $so_goc_da_tra_transaction
						)
					);
				}
			
			


			} else {
				if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_goc_da_tra" => $so_goc_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền phí đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}


			}
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$phi_con_lai = (!empty($temp_1ky['tien_phi_1ky_con_lai'])) ? $temp_1ky['tien_phi_1ky_con_lai'] : 0;
			$goc_con_lai = (!empty($temp_1ky['tien_goc_1ky_con_lai'])) ? $temp_1ky['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai = (!empty($temp_1ky['tien_lai_1ky_con_lai'])) ? $temp_1ky['tien_lai_1ky_con_lai'] : 0;
			$tong_lai_phi_goc_con=$phi_con_lai+$goc_con_lai+$lai_con_lai;
			if($tong_lai_phi_goc_con <= $this->get_amout_limit_debt($date_pay,$temp_1ky['round_tien_tra_1_ky']))
			{
               $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>2   ) );
			}else{
                $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>1   )
			    );
			}
			


		}

		if ($amountRemain > 0) {
			$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
		}
		if ($amountRemain > 0 ) {
			$update = array();

			$update['tien_thua_thanh_toan'] = $amountRemain;
             $update['tien_thua_thanh_toan_da_tra'] = 0;
             $update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		}

	}
	private function bangLaiKy_tinhtoan_tien_datra_conlai_gh($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ( $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}



			


				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				 if($type_payment==2 && $amountRemain > 0 && $key == (count($temps)-1))
                {
                	$pghg=isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
				//phi gia han
				if ($amountRemain > 0 && $phi_gia_han >0 && $so_phi_gia_han_da_tra_transaction < $pghg ) {
                    if ($amountRemain >= $phi_gia_han) {
                      $so_phi_gia_han_da_tra_transaction=$phi_gia_han;
                      $amountRemain=$amountRemain-$phi_gia_han;
                    }else{
                      $so_phi_gia_han_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    $this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_gia_han_da_tra" => $so_phi_gia_han_da_tra_transaction
						)
					  );
				}
				
		        //phi cham tra type_payment >1
				if( $amountRemain > 0)
				{
				$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
				}
				 //phi phát sinh type_payment>1
				if( $amountRemain > 0 )
				{
					 if($date_pay > strtotime('2021-09-30  23:59:59'))
	               {
					if ($amountRemain >= $phi_phat_sinh) {
                      $so_phi_phat_sinh_da_tra_transaction=$phi_phat_sinh;
                      $amountRemain=$amountRemain-$phi_phat_sinh;
                    }else{
                      $so_phi_phat_sinh_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    $this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"phi_phat_sinh" => $phi_phat_sinh,
							"tien_phi_phat_sinh_da_tra" => $so_phi_phat_sinh_da_tra_transaction,
							"tien_phi_phat_sinh_con_lai" =>$phi_phat_sinh- $so_phi_phat_sinh_da_tra_transaction
						)
					  );
                   }
				}
			  }
			
			
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra)
			);


		}

		if ($amountRemain > 0) {
			$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
		}
		if ($amountRemain > 0 ) {
			$update = array();

			$update['tien_thua_thanh_toan'] = $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = 0;
            $update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		}

	}
	private function bangLaiKy_tinhtoan_tien_datra_conlai_tien_thua($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}

             //trước 1/5
			if (strtotime(date('Y-m-d',$date_pay). ' 00:00:00') < strtotime('2021-05-01 00:00:00') && $amountRemain > 0) {
  
					if($amountRemain < ($goc_con_lai_ky_hien_tai + $lai_con_lai_ky_hien_tai+ $phi_con_lai_ky_hien_tai ) && $amountRemain !=$amount && $temp['status']==1 && strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') )
					{
                      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
					}
			}else{
	 
			    if ( strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') && $amountRemain > 0 ) {
				      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
			   }
			     
	     	}

				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_extend_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_extend_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				

			 
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra)
			);


		}

		
		if ($amountRemain > 0 ) {
			$update = array();
			$update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = $amount- $amountRemain;
             $update["date_pay"] =$contractDB['disbursement_date'];
			$this->transaction_extend_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
			$tranDB = $this->transaction_extend_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
			$tranDB_origin = $this->transaction_model->findOne(array('code' =>$tranDB['code_parent']));
			$update_tran_origin = array();
			
			$update_tran_origin['tien_thua_thanh_toan_da_tra'] =$tranDB_origin['tien_thua_thanh_toan_da_tra']+($amount- $amountRemain);
            $tien_thua_thanh_toan_con_lai=$tranDB_origin['tien_thua_thanh_toan']-$update_tran_origin['tien_thua_thanh_toan_da_tra']-($amount- $amountRemain);
            if($tien_thua_thanh_toan_con_lai<0)
            	$tien_thua_thanh_toan_con_lai=0;
			$update_tran_origin['tien_thua_thanh_toan_con_lai'] =$tien_thua_thanh_toan_con_lai ;
			
			$this->transaction_model->update(
				array("_id" => $tranDB_origin['_id']),
				$update_tran_origin
			);
		}else if($amountRemain == 0 ) {
			$update = array();
			$update['tien_thua_thanh_toan_con_lai'] = 0;
			$update['tien_thua_thanh_toan_da_tra'] = $amount;
            $update["date_pay"] =$contractDB['disbursement_date'];
			$this->transaction_extend_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
			$tranDB = $this->transaction_extend_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
			$tranDB_origin = $this->transaction_model->findOne(array('code' =>$tranDB['code_parent']));
			$update_tran_origin = array();
			
			$update_tran_origin['tien_thua_thanh_toan_da_tra'] =$tranDB_origin['tien_thua_thanh_toan_da_tra']+$amount;
			$update_tran_origin['tien_thua_thanh_toan_con_lai'] = 0;
			 
			$this->transaction_model->update(
				array("_id" => $tranDB_origin['_id']),
				$update_tran_origin
			);
		}

	}
	private function bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		if(empty($contractDB))
			return;
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		$tien_phi_tat_toan_da_tra_transaction = 0;
		$tranData=$this->transaction_model->findOne(['_id'=>new MongoDB\BSON\ObjectId($transId)]);
        if(empty($tranData))
			return;
		 if($type_payment==2 )
		{
		$phi_gia_han =!empty($tranData['so_tien_phi_gia_han_con_lai']) ? $tranData['so_tien_phi_gia_han_con_lai'] : 0;
	    }

			$amountRemain = !empty($tranData['total_deductible']) ? (int)$tranData['total_deductible'] : 0;
		if ($amountRemain == 0)
			return ;

			if($type_payment==2)
				{
               //phi gia han
				if ($amountRemain > 0 && $phi_gia_han >0 && $so_phi_gia_han_da_tra_transaction < 200000 ) {
                    if ($amountRemain >= $phi_gia_han) {
                      $so_phi_gia_han_da_tra_transaction=$phi_gia_han;
                      $amountRemain=$amountRemain-$phi_gia_han;
                    }else{
                      $so_phi_gia_han_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    
				}
			   }
				if($tranData['type']==3 || $type_payment>1)
				{
				if($tranData['type']==3)
				{
			        $tong_phi_tat_toan = !empty($tranData['so_tien_phi_tat_toan_phai_tra_tat_toan']) ? $tranData['so_tien_phi_tat_toan_phai_tra_tat_toan'] : 0;
					$phi_tat_toan_da_tra = !empty($tranData['fee_finish_contract']) ? $tranData['fee_finish_contract'] : 0;
					$phi_tat_toan_phai_tra = $tong_phi_tat_toan - $phi_tat_toan_da_tra;
					
					if ($amountRemain > 0  && $phi_tat_toan_phai_tra > 0) {
						if ($amountRemain >= $phi_tat_toan_phai_tra) {
							$tien_phi_tat_toan_da_tra_transaction = $phi_tat_toan_phai_tra;
							$amountRemain = $amountRemain - $phi_tat_toan_phai_tra;
						} else {
							$tien_phi_tat_toan_da_tra_transaction = $amountRemain;
							$amountRemain = 0;
						}
					

					}
				}


               //phi phát sinh type_payment>1
				if( $amountRemain > 0 )
				{
					
                    $tong_phi_phat_sinh = $this->transaction_model->get_tong_phi_phat_sinh($code_contract);
					$phi_phat_sinh_da_tra = $this->transaction_model->get_phi_phat_sinh_da_tra($code_contract);
					$phi_phat_sinh_phai_tra = $tong_phi_phat_sinh - $phi_phat_sinh_da_tra;
					$tien_phi_phat_sinh_da_tra = 0;
					if ($amountRemain > 0 && $phi_phat_sinh_phai_tra > 0) {
						if ($amountRemain >= $phi_phat_sinh_phai_tra) {
							$so_phi_phat_sinh_da_tra_transaction = $phi_phat_sinh_phai_tra;
							$amountRemain = $amountRemain - $phi_phat_sinh_phai_tra;
						} else {
							$so_phi_phat_sinh_da_tra_transaction = $amountRemain;
							$amountRemain = 0;
						}
						

					}
				}
			   }
			   $so_tien_phi_cham_tra_da_tra_transaction=0;
		        //phi cham tra type_payment >1
				if( $amountRemain > 0)
				{
				$truoc_phan_bo_cham_tra =$amountRemain;
				$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId,"GT", $amountRemain, 0, $date_pay);
				 $so_tien_phi_cham_tra_da_tra_transaction=$truoc_phan_bo_cham_tra -$amountRemain;
				}
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}


				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					
				}
					if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					
				}
                if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
				
				}
			 
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$phi_con_lai = (!empty($temp_1ky['tien_phi_1ky_con_lai'])) ? $temp_1ky['tien_phi_1ky_con_lai'] : 0;
			$goc_con_lai = (!empty($temp_1ky['tien_goc_1ky_con_lai'])) ? $temp_1ky['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai = (!empty($temp_1ky['tien_lai_1ky_con_lai'])) ? $temp_1ky['tien_lai_1ky_con_lai'] : 0;
			
			$tong_lai_phi_goc_con=$phi_con_lai+$goc_con_lai+$lai_con_lai;
			if($tong_lai_phi_goc_con <= ($this->get_amout_limit_debt($date_pay,$temp_1ky['round_tien_tra_1_ky'])+(int)$tranData['total_deductible']) )
			{
               $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>2   ) );
			}else{
                $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>1   )
			    );
			}


		}
        $tien_thua_mien_giam=0;
		
		if ($amountRemain > 0 ) {
			$tien_thua_mien_giam=$amountRemain ;
			  $tien_thua_thanh_toan = !empty($tranData['tien_thua_thanh_toan']) ? $tranData['tien_thua_thanh_toan'] : 0;
			$update = array();

			$update['tien_thua_thanh_toan'] =$tien_thua_thanh_toan+ $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = 0;
            $update['tien_thua_thanh_toan_con_lai'] = $tien_thua_thanh_toan+ $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		
		}
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				'chia_mien_giam'=>[
				"so_tien_phi_gia_han_da_tra" => $so_phi_gia_han_da_tra_transaction,
				"so_tien_phi_tat_toan_da_tra" =>$tien_phi_tat_toan_da_tra_transaction,
				"so_tien_phi_phat_sinh_da_tra" =>$so_phi_phat_sinh_da_tra_transaction,
				"so_tien_phi_cham_tra_da_tra"=>$so_tien_phi_cham_tra_da_tra_transaction,
				"so_tien_phi_da_tra"=>$so_phi_da_tra_transaction,
				"so_tien_lai_da_tra"=>$so_lai_da_tra_transaction,
				"so_tien_goc_da_tra"=>$so_goc_da_tra_transaction,
				"tien_thua_mien_giam"=>$tien_thua_mien_giam
			    ]
			)
		);


	}
	public function tinhtoanBangLaiThang($codeContract, $amount,$id_transaction)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		$tranDB = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($id_transaction)));
		$this->tinhtoan_tien_datra_conlai($temps, $amount,$tranDB);
		$this->tinhtoan_duno_thangtruoc($codeContract, $amount,$tranDB);
	}


	private function tinhtoan_tien_datra_conlai($temps, $amount,$tranDB)
	{
		$amountRemain = 0;
        $ck=0;
		foreach ($temps as $key =>$temp) {
			$dataUpdate = array();
	
			

			//Tiền đã đóng tháng hiện tại
			$goc_da_dong_thang_hien_tai = !empty($temp['tien_goc_1thang_da_tra']) ? $temp['tien_goc_1thang_da_tra'] : 0;
			$lai_da_dong_thang_hien_tai = !empty($temp['tien_lai_1thang_da_tra']) ? $temp['tien_lai_1thang_da_tra'] : 0;
			$phi_da_dong_thang_hien_tai = !empty($temp['tien_phi_1thang_da_tra']) ? $temp['tien_phi_1thang_da_tra'] : 0;

			$phi_phat_sinh_1thang_da_tra = !empty($temp['phi_phat_sinh_1thang_da_tra']) ? $temp['tien_goc_1thang_da_tra'] : 0;
			$phi_tat_toan_1thang_da_tra = !empty($temp['phi_tat_toan_1thang_da_tra']) ? $temp['phi_tat_toan_1thang_da_tra'] : 0;
			$phi_gia_han_1thang_da_tra = !empty($temp['phi_gia_han_1thang_da_tra']) ? $temp['phi_gia_han_1thang_da_tra'] : 0;
			$phi_cham_tra_1thang_da_tra = !empty($temp['phi_cham_tra_1thang_da_tra']) ? $temp['phi_cham_tra_1thang_da_tra'] : 0;

			
            $ck=0;
			$dataUpdate = array();
           if( $temp['time'] ==(string)date('m/Y',$tranDB['date_pay']) || (strtotime(date($temp['year'].'-'.$temp['month'].'-t').' 23:59:59') < $tranDB['date_pay'] && $key==(count($temps)-1) ))
            {
			$ck=1;
			if($ck==1)
			{
				$ck++;
			$dataUpdate['tien_goc_1thang_da_tra'] = $goc_da_dong_thang_hien_tai +$tranDB['so_tien_goc_da_tra'];
			$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $tranDB['so_tien_lai_da_tra'];	
			$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai +$tranDB['so_tien_phi_da_tra'];	

			$dataUpdate['phi_phat_sinh_1thang_da_tra'] = $phi_phat_sinh_1thang_da_tra +$tranDB['tien_phi_phat_sinh_da_tra'];	
			$dataUpdate['phi_tat_toan_1thang_da_tra'] = $phi_tat_toan_1thang_da_tra +$tranDB['fee_finish_contract'];	
			$dataUpdate['phi_gia_han_1thang_da_tra'] = $phi_gia_han_1thang_da_tra +$tranDB['so_tien_phi_gia_han_da_tra'];	
			$dataUpdate['phi_cham_tra_1thang_da_tra'] = $phi_cham_tra_1thang_da_tra +$tranDB['so_tien_phi_cham_tra_da_tra'];
			$dataUpdate['tien_thua_tat_toan'] = $tranDB['tien_thua_tat_toan'];	
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
		  }
		  }
			
		
		  }
			
		  
		}
	

	private function tinhtoan_duno_thangtruoc($code_contract, $amount)
	{
		//Step 1: tìm lại các bảng lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$du_no_goc_thang_truoc_da_tra = 0;
		$du_no_lai_thang_truoc_da_tra = 0;
		$du_no_phi_thang_truoc_da_tra = 0;
        
		foreach ($temps as $key=>$temp) {
			$ck=0;
			if( $temp['time'] ==(string)date('m/Y',$tranDB['date_pay']) || (strtotime(date($temp['year'].'-'.$temp['month'].'-t').' 23:59:59') < $tranDB['date_pay'] && $key==(count($temps)-1) ))
            {
              $ck=$key+1;
            }
            if($ck==($key+1) )
            {
			   
			    $du_no_goc_thang_truoc_da_tra =  $temp['tien_goc_1thang_da_tra'];

				$dataUpdate['du_no_goc_thang_truoc_da_tra'] = $du_no_goc_thang_truoc_da_tra;
			
		       $du_no_lai_thang_truoc_da_tra =  $temp['tien_lai_1thang_da_tra'];
				$dataUpdate['du_no_lai_thang_truoc_da_tra'] = $du_no_lai_thang_truoc_da_tra;
			
			
               $du_no_phi_thang_truoc_da_tra =  $temp['tien_phi_1thang_da_tra'];
			
				$dataUpdate['du_no_phi_thang_truoc_da_tra'] = $du_no_phi_thang_truoc_da_tra;
			
			

			//Update DB
			if (empty($dataUpdate)) continue;
			$this->tempo_contract_accounting_model->update(
				array("_id" => $temp['_id']),
				$dataUpdate
			);
          }
		}
	}



	

	private function phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay)
	{
		$temps_cham_tra = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));
		$ck_gtpp="";
		if($phi_phat_tra_cham=="GT")
		{
		
			$tranData=$this->transaction_model->findOne(['_id'=>new MongoDB\BSON\ObjectId($transId)]);	
			$so_tien_phi_cham_tra_da_tra=(isset($tranData['so_tien_phi_cham_tra_da_tra'])) ? $tranData['so_tien_phi_cham_tra_da_tra'] : 0;
			$phi_phat_tra_cham=0;
			//biến check : chia giảm trừ sẽ vào đây
			$ck_gtpp="GT";
		}
		foreach ($temps_cham_tra as $chamtra) {
			$dataUpdate_pp = array();
			$dataUpdate_pp_t = array();
			$tinh_phi_phat_sinh = false;
			$so_ky_da_tra = 0;
			foreach ($temps as $glp) {
				if ($glp['ky_tra'] == $chamtra['ky_tra']) {
					//Tiền còn lại phải đóng kỳ hiện tại

					$goc_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_goc_1ky_con_lai']) ? $glp['tien_goc_1ky_con_lai'] : 0;
					$lai_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_lai_1ky_con_lai']) ? $glp['tien_lai_1ky_con_lai'] : 0;
					$phi_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_phi_1ky_con_lai']) ? $glp['tien_phi_1ky_con_lai'] : 0;
				}
				if ($glp['status'] == 2)
					$so_ky_da_tra++;

			}
			if (count($temps) == $so_ky_da_tra) $tinh_phi_phat_sinh = true;

			$phi_phat_cham_tra_con_lai_ky_hien_tai = !empty($chamtra['tien_phi_cham_tra_1ky_con_lai']) ? $chamtra['tien_phi_cham_tra_1ky_con_lai'] : 0;
			$fee_delay_pay = !empty($chamtra['fee_delay_pay']) ? $chamtra['fee_delay_pay'] : 0;
			$phi_phat_cham_tra_da_dong_ky_hien_tai = !empty($chamtra['tien_phi_cham_tra_1ky_da_tra']) ? $chamtra['tien_phi_cham_tra_1ky_da_tra'] : 0;
			$phi_phat_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$chamtra['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$chamtra['ky_tra']]['so_tien'] : 0;
			$so_ngay_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$chamtra['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$chamtra['ky_tra']]['so_ngay'] : 0;

			if ($phi_phat_tra_cham_ky_hien_tai == 0)
				$phi_phat_tra_cham_ky_hien_tai = $phi_phat_cham_tra_con_lai_ky_hien_tai;
            //lưu tiền chậm trả cho kỳ đã thanh toán
			if ($chamtra['status'] == 2 && $phi_phat_tra_cham_ky_hien_tai > 0 && $phi_phat_cham_tra_con_lai_ky_hien_tai == 0 && $phi_phat_cham_tra_da_dong_ky_hien_tai == 0) {
				$dataUpdate_pp_t['fee_delay_pay'] = $phi_phat_tra_cham_ky_hien_tai;
				$dataUpdate_pp_t['tien_phi_cham_tra_1ky_con_lai'] = $phi_phat_tra_cham_ky_hien_tai;
				$dataUpdate_pp_t['so_ngay_cham_tra'] = $so_ngay_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_pp_t)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $chamtra['_id']),
						$dataUpdate_pp_t
					);
				}
			}
			if ($fee_delay_pay > 0 && $fee_delay_pay == $phi_phat_cham_tra_da_dong_ky_hien_tai) {
				$phi_phat_tra_cham_ky_hien_tai = 0;
			}
           $amountRemain_origin=$amountRemain;
			//phí phạt
			
			if ($amountRemain > 0 && $phi_phat_tra_cham_ky_hien_tai > 0 && $chamtra['fee_delay_pay'] > 0) {
				if ($amountRemain >= $phi_phat_tra_cham_ky_hien_tai) {
					//Update $phi_phat_cham_tra_da_dong_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_da_tra'] = $phi_phat_cham_tra_da_dong_ky_hien_tai + $phi_phat_tra_cham_ky_hien_tai;

					$so_phi_phat_cham_tra_da_tra_transaction = $so_phi_phat_cham_tra_da_tra_transaction + $phi_phat_tra_cham_ky_hien_tai;

					//Update $phi_phat_tra_cham_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_phat_tra_cham_ky_hien_tai;
				} else {
					//Update $phi_phat_da_dong_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_da_tra'] = $phi_phat_cham_tra_da_dong_ky_hien_tai + $amountRemain;

					$so_phi_phat_cham_tra_da_tra_transaction = $so_phi_phat_cham_tra_da_tra_transaction + $amountRemain;

					//Update $phi_phat_tra_cham_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_con_lai'] = $phi_phat_tra_cham_ky_hien_tai - $amountRemain;
					$amountRemain = 0;
				}
                 
				//lưu lại để check số tiền trong vòng lặp khi lưu tiền chậm trả
				if (!empty($dataUpdate_pp)) {
					$dataUpdate_pp['phi_phat_tra_cham_ky_hien_tai']=$phi_phat_tra_cham_ky_hien_tai;
					$dataUpdate_pp['amountRemain']=$amountRemain;
					$dataUpdate_pp['amountRemain_origin']=$amountRemain_origin;
					$this->temporary_plan_contract_model->update(
						array("_id" => $chamtra['_id']),
						$dataUpdate_pp
					);
				}


				//Update số tiền cham tra đã đóng, đã đóng ở bảng transaction
				if($so_phi_phat_cham_tra_da_tra_transaction>0 && $ck_gtpp=="")
				{

					$this->transaction_model->update(
					array("_id" => new MongoDB\BSON\ObjectId($transId)),
					array(
						"temporary_plan_contract_id" => $chamtra['_id'],
						"so_tien_phi_cham_tra_da_tra" =>$so_phi_phat_cham_tra_da_tra_transaction,
						"amountRemain" =>$amountRemain
					)
				);
			  }
			}
		}
	
		return $amountRemain;
	}
   private function tinhtoan_tien_datra_conlai_giahan($temps, $amount)
	{
		$amountRemain = 0;

		foreach ($temps as $temp) {
			if ($amountRemain == 0) $amountRemain = $amount;
            
			//Tiền phải đóng tháng hiện tại
			$lai_phai_dong_thang_hien_tai = !empty($temp['tien_lai_1thang']) ? $temp['tien_lai_1thang'] : 0;
			$phi_phai_dong_thang_hien_tai = !empty($temp['tien_phi_1thang']) ? $temp['tien_phi_1thang'] : 0;

			//Tiền đã đóng tháng hiện tại
			$lai_da_dong_thang_hien_tai = !empty($temp['tien_lai_1thang_da_tra']) ? $temp['tien_lai_1thang_da_tra'] : 0;
			$phi_da_dong_thang_hien_tai = !empty($temp['tien_phi_1thang_da_tra']) ? $temp['tien_phi_1thang_da_tra'] : 0;

			//Tiền còn lại phải đóng tháng hiện tại
			$lai_con_lai_thang_hien_tai = !empty($temp['tien_lai_1thang_con_lai']) ? $temp['tien_lai_1thang_con_lai'] : 0;
			$phi_con_lai_thang_hien_tai = !empty($temp['tien_phi_1thang_con_lai']) ? $temp['tien_phi_1thang_con_lai'] : 0;

			$dataUpdate = array();
			if ($amountRemain <= 0) continue;
			//Lãi
			if ($lai_con_lai_thang_hien_tai > 0) {
				if ($amountRemain >= $lai_con_lai_thang_hien_tai) {
					//Update $lai_da_dong_thang_hien_tai
					$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $lai_con_lai_thang_hien_tai;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_lai_1thang_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_thang_hien_tai;
				} else {
					//Update $lai_da_dong_thang_hien_tai
					$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $amountRemain;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_lai_1thang_con_lai'] = $lai_con_lai_thang_hien_tai - $amountRemain;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_thang_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
			if ($amountRemain <= 0) continue;
			//Phí
			if ($phi_con_lai_thang_hien_tai > 0) {
				if ($amountRemain >= $phi_con_lai_thang_hien_tai) {
					$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai + $phi_con_lai_thang_hien_tai;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_phi_1thang_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_con_lai_thang_hien_tai;
				} else {
					//Update $phi_da_dong_thang_hien_tai
					$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai + $amountRemain;
					//Update $phi_con_lai_thang_hien_tai
					$dataUpdate['tien_phi_1thang_con_lai'] = $phi_con_lai_thang_hien_tai - $amountRemain;
					$amountRemain = $amountRemain - $phi_con_lai_thang_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
		}
	}

	private function bangLaiKy_tinhtoan_tien_datra_conlai_giahan($temps, $amount, $transId)
	{
		$amountRemain = 0;

		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;

		foreach ($temps as $temp) {
			if ($amountRemain == 0) $amountRemain = $amount;

			//Tiền đã đóng kỳ hiện tại
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;

			//Tiền còn lại phải đóng kỳ hiện tại
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;

			$dataUpdate = array();

			if ($amountRemain <= 0) break;
			//Lãi
			if ($lai_con_lai_ky_hien_tai > 0) {
				if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
					//Update $lai_da_dong_ky_hien_tai
					$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

					$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_lai_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
				} else {
					//Update $lai_da_dong_ky_hien_tai
					$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

					$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->temporary_plan_contract_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}

			//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				array(
					"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
				)
			);

			if ($amountRemain <= 0) break;
			//Phí
			if ($phi_con_lai_ky_hien_tai > 0) {
				if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
					$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

					$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_phi_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
				} else {
					//Update $phi_da_dong_ky_hien_tai
					$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

					$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

					//Update $phi_con_lai_ky_hien_tai
					$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
					$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->temporary_plan_contract_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
			//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				array(
					"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
				)
			);
		}
	}

	private function bangLaiKy_tinhtoan_duno_thangtruoc($code_contract, $amount)
	{
		//Step 1: tìm lại các bảng lãi tháng
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$du_no_goc_ky_truoc_da_tra = 0;
		$du_no_lai_ky_truoc_da_tra = 0;
		$du_no_phi_ky_truoc_da_tra = 0;

		$temp_du_no_goc_ky_truoc = !empty($temp['du_no_goc_ky_truoc']) ? $temp['du_no_goc_ky_truoc'] : 0;
		$temp_du_no_goc_ky_truoc_da_tra = !empty($temp['du_no_goc_ky_truoc_da_tra']) ? $temp['du_no_goc_ky_truoc_da_tra'] : 0;
		$temp_tien_goc_1ky_da_tra = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;

		$temp_du_no_lai_ky_truoc = !empty($temp['du_no_lai_ky_truoc']) ? $temp['du_no_lai_ky_truoc'] : 0;
		$temp_du_no_lai_ky_truoc_da_tra = !empty($temp['du_no_lai_ky_truoc_da_tra']) ? $temp['du_no_lai_ky_truoc_da_tra'] : 0;
		$temp_tien_lai_1ky_da_tra = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;

		$temp_du_no_phi_ky_truoc = !empty($temp['du_no_phi_ky_truoc']) ? $temp['du_no_phi_ky_truoc'] : 0;
		$temp_du_no_phi_ky_truoc_da_tra = !empty($temp['du_no_phi_ky_truoc_da_tra']) ? $temp['du_no_phi_ky_truoc_da_tra'] : 0;
		$temp_tien_phi_1ky_da_tra = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;

		foreach ($temps as $temp) {
			if ($temp_du_no_goc_ky_truoc > 0 && $du_no_goc_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_goc_ky_truoc'] = $temp_du_no_goc_ky_truoc + $temp_du_no_goc_ky_truoc_da_tra - $du_no_goc_ky_truoc_da_tra;
				$dataUpdate['du_no_goc_ky_truoc_da_tra'] = $du_no_goc_ky_truoc_da_tra;
			}
			$du_no_goc_ky_truoc_da_tra = $du_no_goc_ky_truoc_da_tra + $temp_tien_goc_1ky_da_tra;

			if ($temp_du_no_lai_ky_truoc > 0 && $du_no_lai_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_lai_ky_truoc'] = $temp_du_no_lai_ky_truoc + $temp_du_no_lai_ky_truoc_da_tra - $du_no_lai_ky_truoc_da_tra;
				$dataUpdate['du_no_lai_ky_truoc_da_tra'] = $du_no_lai_ky_truoc_da_tra;
			}
			$du_no_lai_ky_truoc_da_tra = $du_no_lai_ky_truoc_da_tra + $temp_tien_lai_1ky_da_tra;

			if ($temp_du_no_phi_ky_truoc > 0 && $du_no_phi_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_phi_ky_truoc'] = $temp_du_no_phi_ky_truoc + $temp_du_no_phi_ky_truoc_da_tra - $du_no_phi_ky_truoc_da_tra;
				$dataUpdate['du_no_phi_ky_truoc_da_tra'] = $du_no_phi_ky_truoc_da_tra;
			}
			$du_no_phi_ky_truoc_da_tra = $du_no_phi_ky_truoc_da_tra + $temp_tien_phi_1ky_da_tra;

			//Update DB
			if (empty($dataUpdate)) continue;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				$dataUpdate
			);
		}
	}



	private function tinhFeeFinishContract($codeContract, $feeFinishContract, $time, $date_pay)
	{
		//Bảng lãi tháng
		$this->tempo_contract_accounting_model->findOneAndUpdate(
			array("code_contract" => $codeContract,
				'time' => $time),
			array('fee_finish_contract' => $feeFinishContract)
		);
		//Bảng lãi kỳ
		$currentLaiKy = $this->contract_tempo_model->findOneOrderBy(
			array("code_contract" => $codeContract,
				"ngay_ky_tra" => array('gte' => $date_pay)),
			array("ngay_ky_tra" => "ESC")
		);
		if (!empty($currentLaiKy)) {
			$this->contract_tempo_model->update(
				array("_id" => $currentLaiKy['_id']),
				array("fee_finish_contract" => (float)$feeFinishContract)
			);
		}
	}

	private function get_goc_lai_phi_tat_toan_bang_ky($contractDB, $date_pay)
	{

		$code_contract = $contractDB['code_contract'];
		// $goc_lai_phi_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($code_contract, $date_pay);
		$goc_lai_phi_da_tra = $this->temporary_plan_contract_model->goc_lai_phi_da_tra($code_contract, $date_pay);
		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($code_contract, $date_pay);
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($code_contract, $date_pay);
		//Tiền gốc còn lại phải đóng
		
		$goc_phai_dong = $get_infor_tat_toan_part_1['goc_chua_tra_den_thoi_diem_dao_han'];
		$lai_phai_dong = $get_infor_tat_toan_part_1['lai_chua_tra_den_thoi_diem_hien_tai'] ;
		$phi_phai_dong = $get_infor_tat_toan_part_1['phi_chua_tra_den_thoi_diem_hien_tai'];
	     $lai_con_no_thuc_te =$get_infor_tat_toan_part_2['lai_con_no_thuc_te'];
         $phi_con_no_thuc_te =$get_infor_tat_toan_part_2['phi_con_no_thuc_te'];
		$data = array();
		$data['goc_phai_dong'] = $goc_phai_dong;
		$data['lai_phai_dong'] = $lai_phai_dong;
		$data['phi_phai_dong'] = $phi_phai_dong;
		$data['lai_con_no_thuc_te'] = $lai_con_no_thuc_te;
		$data['phi_con_no_thuc_te'] = $phi_con_no_thuc_te;

		return $data;
	}
private function tinh_goc_lai_phi_transaction_tat_toan($transId, $phi_phat_sinh)
	{
		//Update trans id
		$update = array();
		$update['so_tien_goc_da_tra'] = $this->so_tien_goc_da_tra_tat_toan;
		$update['so_tien_lai_da_tra'] = $this->so_tien_lai_da_tra_tat_toan;
		$update['so_tien_phi_da_tra'] = $this->so_tien_phi_da_tra_tat_toan;
		$update['tien_thua_tat_toan'] = $this->tien_thua_tat_toan;
		$update['fee_finish_contract'] = $this->so_tien_phi_tat_toan_da_tra;
		$update['phi_phat_sinh'] = $phi_phat_sinh;
		$update['tien_phi_phat_sinh_da_tra'] = $this->so_tien_phi_phat_sinh_da_tra;
		$update['so_tien_phi_cham_tra_da_tra'] = $this->so_tien_phi_cham_tra_da_tra;
		$update['so_tien_phi_gia_han_da_tra'] = $this->so_tien_phi_gia_han_da_tra;
		$update['tat_toan_phai_tra']=[
				'so_tien_goc_phai_tra_tat_toan' => $this->so_tien_goc_phai_tra_tat_toan,
				'so_tien_lai_phai_tra_tat_toan' => $this->so_tien_lai_phai_tra_tat_toan,
				'so_tien_phi_phai_tra_tat_toan' => $this->so_tien_phi_phai_tra_tat_toan,
				'so_tien_phi_cham_tra_phai_tra_tat_toan' => $this->so_tien_phi_cham_tra_phai_tra_tat_toan,
				'so_tien_phi_gia_han_phai_tra_tat_toan' => $this->so_tien_phi_gia_han_phai_tra_tat_toan,
				'so_tien_phi_phat_sinh_phai_tra_tat_toan' => $this->so_tien_phi_phat_sinh_phai_tra_tat_toan
	     ];
		$update['lai_con_no_thuc_te'] = $this->lai_con_no_thuc_te;
		$update['phi_con_no_thuc_te'] = $this->phi_con_no_thuc_te;
		$update['phai_tra_hop_dong']=[
				"so_tien_goc_phai_tra_hop_dong"=>$this->so_tien_goc_phai_tra_hop_dong,
			    "so_tien_lai_phai_tra_hop_dong"=>$this->so_tien_lai_phai_tra_hop_dong,
			    "so_tien_phi_phai_tra_hop_dong"=>$this->so_tien_phi_phai_tra_hop_dong,
			    "phi_gia_han_phai_tra_hop_dong"=>$this->phi_gia_han_phai_tra_hop_dong,
			    "phi_cham_tra_phai_tra_hop_dong"=>$this->phi_cham_tra_phai_tra_hop_dong,
			    "phi_tat_toan_phai_tra_hop_dong"=>$this->phi_tat_toan_phai_tra_hop_dong,
			    "phi_phat_sinh_phai_tra_hop_dong"=>$this->phi_phat_sinh_phai_tra_hop_dong
		];
		$this->transaction_model->update(
			array("_id" => $transId),
			$update
		);
	}
	//chia lại các phiếu thanh toán
	// date_pay_tt ngày tất toán
	// type_payment loại phiếu thu 1 thanh toán 2 gia hạn 3 cơ cấu
     public function update_tran_thanhtoan($contractDB,$date_pay_tt,$type_payment)
   {
   	        $data_delete = array(
				"code_contract" =>  $contractDB['code_contract'],
			);
			//xóa data cũ
			$this->payment_model->delete_lai_ky_lai_thang($data_delete);
				$data_generate = array(
					"code_contract" => $contractDB['code_contract'],
					"investor_code" =>$contractDB['investor_code'],
					"disbursement_date" => $contractDB['disbursement_date'],
					"date_pay" => $date_pay_tt
				);
			//khởi tạo bảng kỳ mới	
			$this->generate_model->processGenerate($data_generate);
			
   	       $data_transaction =$this->transaction_model->find_where(array('code_contract'=>$contractDB['code_contract'],'status'=>1,'type'=>4,'type_payment'=>1));
   	      
		    if (!empty($data_transaction)) {
		    //chia từng phiếu thu	
			foreach ($data_transaction as $key => $value) {
				 
			
			    $value['total']=$this->chia_tien_thieu($value['total'],$contractDB['code_contract_parent_gh']);
			    $date_pay = (isset($value['date_pay'])) ? strtotime(date("Y-m-d", $value['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $value['created_at']) . '  23:59:59');
			    $date_pay_tt = (isset($date_pay_tt)) ? strtotime(date("Y-m-d", $date_pay_tt) . '  00:00:00') : 0;
			    $phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
			   
			    if($value['date_pay'] <= strtotime('2021-09-30  23:59:59') && ( !empty($value['code_contract_parent_cc']) || !empty($value['code_contract_parent_gh'])))
	           {
				  $phi_phat_tra_cham=[];
			    }
				 $this->finishTempoPlan($value['code_contract'], (int)$value['total'], $date_pay,(int)$value['total_deductible']);
			     $temps_ = $this->temporary_plan_contract_model->find_where_order_by(array(
					"code_contract" => $contractDB['code_contract']
				));
               

				$this->bangLaiKy_tinhtoan_tien_datra_conlai($temps_, (float)$value['total'], (string)$value['_id'],$contractDB['code_contract'],$phi_phat_tra_cham, 0,$date_pay,0,(int)$value['type_payment'],$date_pay_tt);
				$this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($value['total_deductible'],(string)$value['_id'], $contractDB['code_contract'], $phi_phat_tra_cham, 0,(int)$date_pay,0,$value['type_payment']);
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;
                $update_tran=array(
					'fee_delay_pay'=>$phi_phat_tra_cham,
					'date_pay_tt'=>$date_pay_tt,
					'ky_da_tt_gan_nhat'=>$ky_da_tt_gan_nhat,
					'stt'=> $key
						
					);
				
				$update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					 $update_tran
					
				);
				
				
			}
		}
   }
	private function cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, $amount, $feeFinishContract,$phi_phat_tra_cham,$date_pay,$phi_phat_sinh,$phi_gia_han,$type_payment,$total_deductible,$transId, $amount_lai_tru_ky_cuoi = 0) {

	$currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
         if($type_payment==3)
				{
					if($contractDB['type_cc']=='origin')
					{
                     $this->transaction_model->findOneAndUpdate(
					       array("_id" => new MongoDB\BSON\ObjectId($transId)),
					 ['type_payment'=>(int)$type_payment,'amount_cc'=>(float)$contractDB['structure_all'][1]['amount_money']]
					
				       );
					}else{
						$contractDB_origin = $this->contract_model->findOne(array('code_contract' =>$contractDB['code_contract_parent_cc']));
						
						$this->transaction_model->findOneAndUpdate(
					       array("_id" => new MongoDB\BSON\ObjectId($transId)),
					 ['type_payment'=>(int)$type_payment,'amount_cc'=>(float)$contractDB_origin['structure_all'][(int)$contractDB['type_cc']+1]['amount_money']]
					
				       );
					}
                }
         $arr_data=[
		'date_pay'=>$date_pay,
		'id_contract'=>(string)$contractDB['_id'],
		'code_contract'=>$contractDB['code_contract']

	    ];
	    
	$tranData =$this->transaction_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
	$this->update_tran_thanhtoan($contractDB,$tranData['date_pay'],$type_payment);

	  $amount=$this->chia_tien_thieu( $amount,$contractDB['code_contract_parent_gh']);
	
    $goc_lai_phi_phai_tra = $this->get_goc_lai_phi_tat_toan_bang_ky($contractDB,$date_pay); 
    $ttptData=$this->transaction_model->get_tong_tien_phieu_thu($contractDB['code_contract']);
    $truoc_tat_toan_ky_tt=$this->temporary_plan_contract_model->get_tien_da_tra_truoc_tat_toan_ki_tt($contractDB['code_contract'],$date_pay);
    $truoc_tat_toan_all=$this->temporary_plan_contract_model->get_tien_da_tra_truoc_tat_toan($contractDB['code_contract'],$date_pay);
	$contract_pay=$this->payment_model->get_payment($arr_data)['contract'];
	// Lấy lãi, phí, gốc chưa trả
	$goc_lai_phi_con_lai_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($contractDB['code_contract']);

	$tien_thua_thanh_toan=0;
	$transactionData = $this->transaction_model->find_where_pay_all(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {

				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
				}
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					array("tien_thua_thanh_toan_con_lai" =>0,
						  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
				          )
				);

			}
		}
		$amount=$tranData['total']+$tien_thua_thanh_toan;
		//Cập nhật gốc + lãi + phí vào kì hiện tại
		//Gốc, lãi, phí của kì
		
		$tong_phi_cham_tra=0;

		$tong_phi_cham_tra = !empty($contract_pay['penalty_pay']) ? $contract_pay['penalty_pay'] : 0;

		
		
		$lai_con_no_thuc_te = $goc_lai_phi_phai_tra['lai_con_no_thuc_te'];
		$phi_con_no_thuc_te = $goc_lai_phi_phai_tra['phi_con_no_thuc_te'];

		$so_tien_goc_da_tra = 0;
		$so_tien_lai_da_tra = 0;
		$so_tien_phi_da_tra = 0;
		
		$phi_gia_han_da_tra = 0;
		$phi_cham_tra_da_tra = 0;
		$phi_tat_toan_da_tra = 0;
		$phi_phat_sinh_da_tra = 0;
		
		$tien_goc_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_goc_da_tra_truoc_tat_toan'];
		$tien_lai_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_lai_da_tra_truoc_tat_toan'];
		$tien_phi_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_phi_da_tra_truoc_tat_toan'];

		$tien_cham_tra_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_cham_tra_da_tra_truoc_tat_toan'];
	if($tranData['date_pay'] <= strtotime('2021-09-30  23:59:59') && $type_payment==3)
    {
     $feeFinishContract=0;
    }
		$this->so_tien_goc_phai_tra_hop_dong = (int)$contractDB['loan_infor']['amount_money'];
		// nếu có lãi NĐT trừ vào kỳ cuối theo coupon => Tổng lãi phải trả của hợp đồng - lãi đã trừ vào kỳ cuối
		if ($amount_lai_tru_ky_cuoi > 0) {
			$this->so_tien_lai_phai_tra_hop_dong = $tien_lai_da_tra_truoc_tat_toan + $goc_lai_phi_phai_tra['lai_phai_dong'] - $amount_lai_tru_ky_cuoi;
		} else {
			$this->so_tien_lai_phai_tra_hop_dong = $tien_lai_da_tra_truoc_tat_toan+$goc_lai_phi_phai_tra['lai_phai_dong'];
		}

		$this->so_tien_phi_phai_tra_hop_dong = $tien_phi_da_tra_truoc_tat_toan+$goc_lai_phi_phai_tra['phi_phai_dong'];
		$this->phi_gia_han_phai_tra_hop_dong = $phi_gia_han;
		$this->phi_cham_tra_phai_tra_hop_dong =$tien_cham_tra_da_tra_truoc_tat_toan+ $tong_phi_cham_tra;
		$this->phi_tat_toan_phai_tra_hop_dong = $feeFinishContract;
		$this->phi_phat_sinh_phai_tra_hop_dong = $phi_phat_sinh;
		// nếu có lãi NĐT trừ vào kỳ cuối theo coupon => Lãi phải trả = lãi phải trả kỳ cuối (kỳ tất toán)
		if ($amount_lai_tru_ky_cuoi > 0) {
			$so_tien_lai_phai_tra = $goc_lai_phi_con_lai_chua_tra[0]['lai_chua_tra'];
		} else {
			$so_tien_lai_phai_tra =$this->so_tien_lai_phai_tra_hop_dong-$truoc_tat_toan_all['tien_lai_da_tra_truoc_tat_toan'];
		}

		$so_tien_phi_phai_tra = $this->so_tien_phi_phai_tra_hop_dong-$truoc_tat_toan_all['tien_phi_da_tra_truoc_tat_toan'];
        $so_tien_goc_phai_tra = $contractDB['loan_infor']['amount_money']-$truoc_tat_toan_all['tien_goc_da_tra_truoc_tat_toan'];
       $truoc_tat_toan_phieu_thu=$this->transaction_model->get_tien_da_tra_truoc_tat_toan($contractDB['code_contract'],$date_pay);
		// không chia lại amount va số liền lãi phải trả để khớp với số tiền lãi thực tế khách được giảm ở coupon giảm lãi
       if ($amount_lai_tru_ky_cuoi == 0) {
		   if(isset($truoc_tat_toan_phieu_thu['so_tien_lai_da_tra']) && $truoc_tat_toan_phieu_thu['so_tien_lai_da_tra'] >$this->so_tien_lai_phai_tra_hop_dong)
		   {
			   $so_tien_lai_phai_tra=0;
			   $amount=$amount+($truoc_tat_toan_phieu_thu['so_tien_lai_da_tra']-$this->so_tien_lai_phai_tra_hop_dong);
		   }
	   }

        if(isset($truoc_tat_toan_phieu_thu['so_tien_phi_da_tra']) && $truoc_tat_toan_phieu_thu['so_tien_phi_da_tra'] >$this->so_tien_phi_phai_tra_hop_dong)
       {
           $so_tien_phi_phai_tra=0;
           $amount=$amount+($truoc_tat_toan_phieu_thu['so_tien_phi_da_tra']-$this->so_tien_phi_phai_tra_hop_dong);
       }
        if($type_payment==3)
        {
        	 $so_tien_goc_phai_tra =$so_tien_goc_phai_tra-$tranData['amount_cc'];
        	  if($so_tien_goc_phai_tra<0)
        	 	$so_tien_goc_phai_tra =0;
        }
		$date_ngay_t= strtotime($this->config->item("date_t_apply"));
		//phải trả
        $this->so_tien_goc_phai_tra_tat_toan = $so_tien_goc_phai_tra;
		$this->so_tien_lai_phai_tra_tat_toan = $so_tien_lai_phai_tra;
		$this->so_tien_phi_phai_tra_tat_toan= $so_tien_phi_phai_tra;
		$this->so_tien_phi_tat_toan_phai_tra_tat_toan = $feeFinishContract;
		$this->so_tien_phi_cham_tra_phai_tra_tat_toan = $tong_phi_cham_tra;
		$this->so_tien_phi_gia_han_phai_tra_tat_toan = $phi_gia_han;
		$this->so_tien_phi_phat_sinh_phai_tra_tat_toan = $phi_phat_sinh;
		$amount_miengiam=$total_deductible;

		if($amount_miengiam>0)
		{
		//==============chia miễn giảm===================
		//----------gia hạn-----------------------
		  $phi_gia_han_da_tra_miengiam=0;
           if($amount_miengiam >= $phi_gia_han) {
			$phi_gia_han_da_tra_miengiam = $phi_gia_han;
			$amount_miengiam = $amount_miengiam - $phi_gia_han;
		   }else 
		    if($amount_miengiam >0 && $phi_gia_han>0 )
		    {
		    	$phi_gia_han_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $phi_gia_han=$phi_gia_han-$phi_gia_han_da_tra_miengiam;
		//-------------------------------------------
        //----------tất toán----------------------- 
		     $phi_tat_toan_da_tra_miengiam=0;
		   if($amount_miengiam >= $feeFinishContract) {
			$phi_tat_toan_da_tra_miengiam = $feeFinishContract;
			$amount_miengiam = $amount_miengiam - $feeFinishContract;
		     }else 
		    if($amount_miengiam >0 && $feeFinishContract>0 )
		    {
		    	$phi_tat_toan_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $feeFinishContract=$feeFinishContract-$phi_tat_toan_da_tra_miengiam;
		//-------------------------------------------
		 //----------phát sinh----------------------- 
		     $phi_phat_sinh_da_tra_miengiam=0;    
		    if($amount_miengiam >= $phi_phat_sinh) {
			$phi_phat_sinh_da_tra_miengiam = $phi_phat_sinh;
			$amount_miengiam = $amount_miengiam - $phi_phat_sinh;
		   }else 
		    if($amount_miengiam >0 && $phi_phat_sinh>0 )
		    {
		    	$phi_phat_sinh_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $phi_phat_sinh=$phi_phat_sinh-$phi_phat_sinh_da_tra_miengiam;
		//-------------------------------------------
		  //----------chậm trả----------------------- 
		     $phi_cham_tra_da_tra_miengiam=0;    
		   if($amount_miengiam >= $tong_phi_cham_tra) {
			$phi_cham_tra_da_tra_miengiam = $tong_phi_cham_tra;
			$amount_miengiam = $amount_miengiam - $tong_phi_cham_tra;
		   }else 
		    if($amount_miengiam >0 && $tong_phi_cham_tra>0 )
		    {
		    	$phi_cham_tra_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $tong_phi_cham_tra=$tong_phi_cham_tra-$phi_cham_tra_da_tra_miengiam;
		//-------------------------------------------
		//----------phí----------------------- 
		     $so_tien_phi_da_tra_miengiam=0;    
		   if($amount_miengiam >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra_miengiam = $so_tien_phi_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_phi_phai_tra;
		  }else 
		    if($amount_miengiam >0 && $so_tien_phi_phai_tra>0 )
		    {
		    	$so_tien_phi_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_phi_phai_tra=$so_tien_phi_phai_tra-$so_tien_phi_da_tra_miengiam;
		//-------------------------------------------
		//----------lai----------------------- 
		     $so_tien_lai_da_tra_miengiam=0;    
			if($amount_miengiam >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra_miengiam = $so_tien_lai_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_lai_phai_tra;
		 }else 
		    if($amount_miengiam >0 && $so_tien_lai_phai_tra>0 )
		    {
		    	$so_tien_lai_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_lai_phai_tra=$so_tien_lai_phai_tra-$so_tien_lai_da_tra_miengiam;
		//-------------------------------------------
		 //----------gốc----------------------- 
		     $so_tien_goc_da_tra_miengiam=0;    
	    if($amount_miengiam >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra_miengiam = $so_tien_goc_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_goc_phai_tra;
		 }else 
		    if($amount_miengiam >0 && $so_tien_goc_phai_tra>0 )
		    {
		    	$so_tien_goc_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_goc_phai_tra=$so_tien_goc_phai_tra-$so_tien_goc_da_tra_miengiam;
		//-------------------------------------------

		    $this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				'chia_mien_giam'=>[
				"so_tien_phi_gia_han_da_tra" => $phi_gia_han_da_tra_miengiam,
				"so_tien_phi_tat_toan_da_tra" =>$phi_tat_toan_da_tra_miengiam,
				"so_tien_phi_phat_sinh_da_tra" =>$phi_phat_sinh_da_tra_miengiam,
				"so_tien_phi_cham_tra_da_tra"=>$phi_cham_tra_da_tra_miengiam,
				"so_tien_phi_da_tra"=>$so_tien_phi_da_tra_miengiam,
				"so_tien_lai_da_tra"=>$so_tien_lai_da_tra_miengiam,
				"so_tien_goc_da_tra"=>$so_tien_goc_da_tra_miengiam
			    ],
			    'goc_lai_phi_phai_tra'=>$goc_lai_phi_phai_tra

			    )
		    );
		//===============================================
		}
		// Chia tiền lãi các kỳ đầu đã trừ vào kỳ cuối
		if ($amount_lai_tru_ky_cuoi > 0) {
			$so_tien_lai_da_tra_tru_ky_cuoi = 0;
			if ($amount_lai_tru_ky_cuoi >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra_tru_ky_cuoi = $amount_lai_tru_ky_cuoi;
			} elseif ($amount_lai_tru_ky_cuoi > 0 && $so_tien_lai_phai_tra > 0) {
				$so_tien_lai_da_tra_tru_ky_cuoi = $amount_lai_tru_ky_cuoi;
			}
			$so_tien_lai_phai_tra = $so_tien_lai_phai_tra - $so_tien_lai_da_tra_tru_ky_cuoi;
		}
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				
			    'goc_lai_phi_phai_tra'=>$goc_lai_phi_phai_tra,
			    'tong_tien_tat_toan'=>$amount

			    )
		    );
		if($contractDB['disbursement_date'] > $date_ngay_t )
		{
			if($amount >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $so_tien_lai_phai_tra;
				$amount = $amount - $so_tien_lai_da_tra;
			}
			if($amount >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $so_tien_phi_phai_tra;
				$amount = $amount - $so_tien_phi_da_tra;
			}
			if($amount >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $so_tien_goc_phai_tra;
				$amount = $amount - $so_tien_goc_da_tra;
			}
			if($amount > 0)
		    {
		    if($so_tien_lai_da_tra < floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $amount;
				$amount = $amount-$so_tien_lai_da_tra;
			}
			if($so_tien_phi_da_tra <   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $amount;
				$amount = $amount-$so_tien_phi_da_tra;
			}
			if($so_tien_goc_da_tra <  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $amount;
				$amount = $amount-$so_tien_goc_da_tra;
			}
		    }
		}else{
			if($amount >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $so_tien_goc_phai_tra;
				$amount = $amount - $so_tien_goc_da_tra;
			}
			if($amount >=  floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $so_tien_lai_phai_tra;
				$amount = $amount - $so_tien_lai_da_tra;
			}
			if($amount >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $so_tien_phi_phai_tra;
				$amount = $amount - $so_tien_phi_da_tra;
			}
			if($amount > 0)
		    {
		    if($so_tien_goc_da_tra <  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $amount;
				$amount = $amount-$so_tien_goc_da_tra;
			}
		    if($so_tien_lai_da_tra < floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $amount;
				$amount = $amount-$so_tien_lai_da_tra;
			}
			if($so_tien_phi_da_tra <   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $amount;
				$amount = $amount-$so_tien_phi_da_tra;
			}
			
		    }

		}
        if($amount >= $phi_gia_han) {
			$phi_gia_han_da_tra = $phi_gia_han;
			$amount = $amount - $phi_gia_han;
		}
		if($amount >= $tong_phi_cham_tra) {
			$phi_cham_tra_da_tra = $tong_phi_cham_tra;
			$amount = $amount - $tong_phi_cham_tra;
		}
		if($amount >= $phi_phat_sinh) {
			$phi_phat_sinh_da_tra = $phi_phat_sinh;
			$amount = $amount - $phi_phat_sinh;
		}
		if($amount >= $feeFinishContract) {
			$phi_tat_toan_da_tra = $feeFinishContract;
			$amount = $amount - $feeFinishContract;
		}
		if($amount > 0)
		{
			if($phi_gia_han_da_tra ==0 && $phi_gia_han>0)
			{
			$phi_gia_han_da_tra = $amount;
           $amount = $amount - $phi_gia_han_da_tra;
			}
			if($phi_cham_tra_da_tra ==0  && $tong_phi_cham_tra>0)
			{
			$phi_cham_tra_da_tra = $amount;
             $amount = $amount - $phi_cham_tra_da_tra;
			}
			if($phi_phat_sinh_da_tra ==0 && $phi_phat_sinh>0)
			{
			$phi_phat_sinh_da_tra = $amount;
           $amount = $amount - $phi_phat_sinh_da_tra;
			}
			if($phi_tat_toan_da_tra ==0 && $feeFinishContract>0 )
			{
			$phi_tat_toan_da_tra = $amount;
             $amount = $amount - $phi_tat_toan_da_tra;
			}
		}

		//Update bảng kì
		$update = array();
		$update['so_tien_goc_da_tra_tat_toan'] = $so_tien_goc_da_tra;
		$update['so_tien_lai_da_tra_tat_toan'] = $so_tien_lai_da_tra;
		$update['so_tien_phi_da_tra_tat_toan'] = $so_tien_phi_da_tra;

		$update['so_tien_phi_gia_han_da_tra_tat_toan'] = $phi_gia_han_da_tra;
		$update['so_tien_phi_cham_tra_da_tra_tat_toan'] = $phi_cham_tra_da_tra;
		$update['so_tien_phi_phat_sinh_da_tra_tat_toan'] = $phi_phat_sinh_da_tra;
		$update['fee_finish_contract'] = $phi_tat_toan_da_tra;
		$update['tien_thua_tat_toan'] = $amount;

		if($currentPlan[0]['status']==1)
		{
            
		    $update['fee_delay_pay']=$phi_phat_tra_cham_ky_hien_tai;
		    $update['so_ngay_cham_tra']=$so_ngay_tra_cham_ky_hien_tai;
		    $update['tien_phi_cham_tra_1ky_con_lai'] = 0;
            $update['tien_phi_cham_tra_1ky_da_tra'] =$phi_phat_tra_cham_ky_hien_tai;
		}
		

		$update['tien_goc_1ky_da_tra'] = $currentPlan[0]['tien_goc_1ky_phai_tra'];
		$update['tien_goc_1ky_con_lai'] = 0;
		$update['tien_lai_1ky_da_tra'] = $currentPlan[0]['tien_lai_1ky_phai_tra'];
		$update['tien_lai_1ky_con_lai'] = 0;
		$update['tien_phi_1ky_da_tra'] = $currentPlan[0]['tien_phi_1ky_phai_tra'];
		$update['tien_phi_1ky_con_lai'] = 0;

        
		$this->so_tien_goc_da_tra_tat_toan = $so_tien_goc_da_tra;
		$this->so_tien_lai_da_tra_tat_toan = $so_tien_lai_da_tra;
		$this->so_tien_phi_da_tra_tat_toan = $so_tien_phi_da_tra;

		$this->tien_thua_tat_toan = $amount;

		$this->so_tien_phi_tat_toan_da_tra = $phi_tat_toan_da_tra;
		$this->so_tien_phi_da_tra = $so_tien_phi_da_tra;
		$this->so_tien_phi_cham_tra_da_tra = $phi_cham_tra_da_tra;
		$this->so_tien_phi_gia_han_da_tra = $phi_gia_han_da_tra;
		$this->so_tien_phi_phat_sinh_da_tra = $phi_phat_sinh_da_tra;
      

		$this->lai_con_no_thuc_te = $lai_con_no_thuc_te;
		$this->phi_con_no_thuc_te = $phi_con_no_thuc_te;
		$this->temporary_plan_contract_model->update(
			array("_id" => $currentPlan[0]['_id']),
			$update
		);
	}

	private function getTongTienTatToan($codeContract, $date_pay)
	{
		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($codeContract, $date_pay);
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($codeContract, $date_pay);

		$du_no_con_lai = !empty($get_infor_tat_toan_part_1['du_no_con_lai']) ? $get_infor_tat_toan_part_1['du_no_con_lai'] : 0;
		$lai_con_no_thuc_te = !empty($get_infor_tat_toan_part_2['lai_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['lai_con_no_thuc_te'] : 0;
		$phi_con_no_thuc_te = !empty($get_infor_tat_toan_part_2['phi_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['phi_con_no_thuc_te'] : 0;

		return $du_no_con_lai + $lai_con_no_thuc_te + $phi_con_no_thuc_te;

	}
   	private function get_amout_limit_debt($date_pay,$amount_ky)
	{
		$amount_limit_debt=0;
         if($date_pay<strtotime('2021-04-18 00:00:00') )
         {
         $amount_limit_debt = $amount_ky * (2 / 100);
			if ($amount_limit_debt > 200000) {
				$amount_limit_debt = 200000;
			}
         }else{
                $amount_limit_debt=12000;
         }
		return $amount_limit_debt;
	}
	private function finishTempoPlan($code, $amount,$date_pay,$total_deductible)
	{
		$transaction = $this->contract_tempo_model->find_where(array('code_contract' => $code, 'status' => 1));
		$contract = $this->contract_model->findOne(array('code_contract' => $code));
		$type_interest = !empty($contract['loan_infor']['type_interest']) ? $contract['loan_infor']['type_interest'] : "1";
		$remaining_amount = $amount;
		$check = false;
		foreach ($transaction as  $key =>$tran) {
			if($key==count($transaction)-1 && $type_interest ==2)
			{
				break;
			}
			$insert = array();
			if ((int)($tran['tien_tra_1_ky'] - $tran['da_thanh_toan']) > $remaining_amount) {
				$insert['da_thanh_toan'] = $remaining_amount + $tran['da_thanh_toan'];
				if ($tran['type'] == 1) {
					$insert['tien_goc_con'] = $tran['tien_goc_con'] - $remaining_amount;
				}
				if ((int)($tran['tien_tra_1_ky'] - $this->get_amout_limit_debt($date_pay,$tran['round_tien_tra_1_ky'])-$total_deductible - $tran['da_thanh_toan']) <= $remaining_amount) {
					$insert['da_thanh_toan'] = $remaining_amount;
					$insert['status'] = 2; // da dong tien
					$insert['current_plan'] = 2; // da dong tien
					$check = true;
				}

			} else {
				$insert['da_thanh_toan'] = $tran['tien_tra_1_ky'];
				$insert['status'] = 2; // da dong tien
				$insert['current_plan'] = 2; // da dong tien
				$check = true;
			}
			$this->contract_tempo_model->findOneAndUpdate(array("_id" => $tran['_id']), $insert);
			$remaining_amount = $remaining_amount - $tran['tien_tra_1_ky'] + $tran['da_thanh_toan'];
			if ($remaining_amount <= 0) {
				break;
			}
		}
		if ($check) {
			$next_plan = $this->contract_tempo_model->findOne(array("status" => 1, 'current_plan' => 2));
			if (!empty($next_plan)) {
				$this->contract_tempo_model->findOneAndUpdate(array("_id" => $next_plan['_id']), array('current_plan' => 1));
			}
		}
		// update total debt pay
		$contract = $this->contract_model->findOne(array('code_contract' => $code));
		$total_debt_pay = !empty($contract['total_debt_pay']) ? (int)$contract['total_debt_pay'] : 0;
		$total_debt_pay = $total_debt_pay + $amount;
		$this->contract_model->findOneAndUpdate(array("_id" => $contract['_id']), array('total_debt_pay' => $total_debt_pay));
	}


	private function cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB,$phi_phat_tra_cham,$date_pay) {
		//Lấy thông tin kì hiện tại
		$currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
		if(!empty($currentPlan)) {

			//Lấy thông tin các kì tiếp theo
			$plansAfter = $this->temporary_plan_contract_model->getCurrentPlanAfter($contractDB['code_contract'], $currentPlan[0]['_id'],$date_pay);
			foreach($plansAfter as $plan) {
				//Gốc đã trả = Gốc phải trả
				//Gốc còn lại = 0
				//Lãi đã trả = Gốc phải trả
				//Lãi còn lại = 0
				//Phí đã trả = Gốc phải trả
				//Phí còn lại = 0
				
				$arr_update=array('tien_goc_1ky_da_tra' => $plan['tien_goc_1ky_phai_tra'],
						'tien_goc_1ky_con_lai' => 0,
						'tien_lai_1ky_da_tra' => $plan['tien_lai_1ky_phai_tra'],
						'tien_lai_1ky_con_lai' => 0,
						'tien_phi_1ky_da_tra' => $plan['tien_phi_1ky_phai_tra'],
						'tien_phi_1ky_con_lai' => 0,
					);

				//Update
				$this->temporary_plan_contract_model->update(
					array('_id' => $plan['_id']),
					$arr_update
				);
			}
		}

	}
    	private function get_current_plan_tat_toan($code_contract, $date_pay = 0)
	{
		$date_pay = (isset($date_pay)) ? $date_pay : strtotime(date('Y-m-d') . ' 23:59:59');
		$currentPlan = array();
		//Get kì phải thanh toán xa nhất
		$ki_phai_thanh_toan_xa_nhat = $this->temporary_plan_contract_model->getKiPhaiThanhToanXaNhat($code_contract);
		//Khách đã quá hạn tất toán của HĐ
		if (!empty($ki_phai_thanh_toan_xa_nhat[0]['ngay_ky_tra']) && $date_pay > $ki_phai_thanh_toan_xa_nhat[0]['ngay_ky_tra']) {
			$currentPlan = $ki_phai_thanh_toan_xa_nhat;
		} //Lấy thông tin kì hiện tại
		else {
			$currentPlan = $this->temporary_plan_contract_model->getCurrentPlan_top($code_contract, $date_pay);
		}
		return $currentPlan;
	}
	private function cap_nhat_tat_toan_cac_ki_truoc_do($contractDB,$phi_phat_tra_cham,$date_pay) {
		//Lấy thông tin kì hiện tại
		$currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
		if(!empty($currentPlan)) {

			//Lấy thông tin các kì tiếp theo
			$plansAfter = $this->temporary_plan_contract_model->getCurrentPlanBefore($contractDB['code_contract'], $currentPlan[0]['_id'],$date_pay);
			foreach($plansAfter as $plan) {
				//Gốc đã trả = Gốc phải trả
				//Gốc còn lại = 0
				//Lãi đã trả = Gốc phải trả
				//Lãi còn lại = 0
				//Phí đã trả = Gốc phải trả
				//Phí còn lại = 0
			   $arr_update=	array();
				 if($plan['status']==1)
				{
					$phi_phat_tra_cham_ky_hien_tai=(isset($phi_phat_tra_cham[$plan['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$plan['ky_tra']]['so_tien'] : 0;
					$so_ngay_tra_cham_ky_hien_tai=(isset($phi_phat_tra_cham[$plan['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$plan['ky_tra']]['so_ngay'] : 0;

					$arr_update['fee_delay_pay']=$phi_phat_tra_cham_ky_hien_tai;
					$arr_update['tien_phi_cham_tra_1ky_con_lai'] = 0;
                    $arr_update['tien_phi_cham_tra_1ky_da_tra'] =$phi_phat_tra_cham_ky_hien_tai;
                    $arr_update['so_ngay_cham_tra'] =$so_ngay_tra_cham_ky_hien_tai;
				}
				
				//Update
				if(!empty($arr_update))
				$this->temporary_plan_contract_model->update(
					array('_id' => $plan['_id']),
					$arr_update
				);
			}
		}

	}
    public function check_approve_gia_han($data)
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$data['code_contract'] = $this->security->xss_clean($data['code_contract']);
		$number_day_loan = $this->security->xss_clean($data['number_day_loan']);
		$so_lan = $this->security->xss_clean($data['so_lan']);
		//$extend_date = $this->security->xss_clean($data['extend_date']);
		$inforDB = $this->contract_model->findOne(array("code_contract" => $data['code_contract']));
		if (empty($inforDB)) return;
		 $tranDT=$this->transaction_model->findOne(array('code_contract'=>$inforDB['code_contract'],'status'=>1,'type_payment'=>2));
        
		$KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
	
		  $ngay_gia_han=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
		$count_extension = !empty($inforDB['count_extension']) ? $inforDB['count_extension'] : 0;
		if(!empty($so_lan))
		{
			$count_extension=(int)$so_lan;
		}
		$tien_goc_con_lai = $this->temporary_plan_contract_model->tien_goc_con_lai($inforDB['code_contract']);
		$investorData = $this->investor_model->findOne(array("_id" => $inforDB['investor_infor']['_id']));

		$origin_code_contract = !empty($inforDB['code_contract_parent_gh']) ? $inforDB['code_contract_parent_gh'] : $inforDB['code_contract'];
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['count_extension'] = $count_extension ;
		$inforDB['code_contract_parent_gh'] = $origin_code_contract;
		$inforDB['loan_infor']['number_day_loan'] =  !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['amount_money'] = !empty($tien_goc_con_lai) ? (string)$tien_goc_con_lai : 0;
		
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['type_gh']=empty($inforDB['code_contract_parent_gh']) ? 'origin' : $inforDB['count_extension'];
		
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
		$inforDB['created_at'] =  $ngay_gia_han;
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
		$inforDB['extend_all'] = array();

		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['extend_date'] = $ngay_gia_han;
		$inforDB['disbursement_date'] = $ngay_gia_han;

         $this->contract_model->update(
				array("_id" => $inforDB['_id']),
				array(
					'status'=>33
				)
			);
		unset($inforDB['_id']);
        
		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
			$this->contract_model->update(
				array("code_contract" => $origin_code_contract),
				array(

					"code_contract_child_gh.".$inforDB['count_extension'] => $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'],
					"extend_all.".$inforDB['count_extension']=>array('extend_date'=>$ngay_gia_han,'number_day_loan'=>$inforDB['loan_infor']['number_day_loan'],'so_lan'=>$inforDB['count_extension']),
					"type_gh"=>"origin"
				)
			);
		if(empty($ck_contract ))
		{

			//$contractId = $this->contract_model->insertReturnId($inforDB);
		}else{
			$code_contract=$ck_contract['code_contract'];
			unset($inforDB['code_contract']);
			unset($inforDB['number_contract']);
			unset($inforDB['code_contract_child_gh']);
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			unset($inforDB['fee']);
          //   if(isset($ck_contract['extend_date']) && $ck_contract['extend_date'] >0)
          //   {
          //   	$inforDB['extend_date'] = (int)$ck_contract['extend_date'];
		        // $inforDB['disbursement_date'] = (int)$ck_contract['extend_date'];
          //   }
			$this->contract_model->update(
				array("code_contract" => $code_contract),
				$inforDB
			);
			$inforDB['code_contract']=$code_contract;
		}
        
			$response = array(
					'status' => '200',
					'msg' => 'OK',
					'data' => $inforDB,
			        'disbursement_date' => $inforDB['disbursement_date']
				    );
				return $response;


	}

	public function check_approve_co_cau($data)
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$data['code_contract'] = $this->security->xss_clean($data['code_contract']);
		$number_day_loan = $this->security->xss_clean($data['number_day_loan']);

		$amount_money = $this->security->xss_clean($data['amount_money']);

		$structure_date = $this->security->xss_clean($data['structure_date']);
		$so_lan = $this->security->xss_clean($data['so_lan']);

		$type_loan = $this->security->xss_clean($data['type_loan']);
		$type_interest = $this->security->xss_clean($data['type_interest']);

		$inforDB = $this->contract_model->findOne(array("code_contract" => $data['code_contract']));
		if(empty($inforDB)) return;
       $KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
		$ngay_co_cau=(int)$structure_date;
		 if($ngay_co_cau >$KiPhaiThanhToanXaNhat)
		 {
          $ngay_co_cau=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
		 }
		$tien_goc_con_lai = $this->temporary_plan_contract_model->tien_goc_con_lai($inforDB['code_contract']);
		if(!empty($amount_money))
		{
			$tien_goc_con_lai=$amount_money;
		}
		$count_structure=(int)$so_lan;
		
		$origin_code_contract = !empty($inforDB['code_contract_parent_cc']) ? $inforDB['code_contract_parent_cc'] : $inforDB['code_contract'];
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['count_structure'] = $count_structure ;
		$inforDB['code_contract_parent_cc'] = $origin_code_contract;
		$inforDB['loan_infor']['number_day_loan'] = !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['type_interest'] = !empty($type_interest) ? $type_interest : $inforDB['loan_infor']['type_interest'];
		$inforDB['loan_infor']['amount_money'] = !empty($tien_goc_con_lai) ? $tien_goc_con_lai : $inforDB['loan_infor']['amount_money'];
		$arr_type_loan=array();
		if($type_loan=="DKX")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ee7a104d435e3b8ae66';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cho vay';
			$inforDB['loan_infor']['type_loan']['code'] = 'DKX';
		}else if($type_loan=="CC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ed2a104d435e3b8ae65';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cầm cố';
			$inforDB['loan_infor']['type_loan']['code'] = 'CC';
		}else if($type_loan=="TC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5fdf75fa6653056471f0b7fe';
			$inforDB['loan_infor']['type_loan']['text'] = 'Tín chấp';
			$inforDB['loan_infor']['type_loan']['code'] = 'TC';
		}
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['type_cc']=empty($inforDB['code_contract_parent_cc']) ? 'origin' : $inforDB['count_structure'];
	
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
		$inforDB['created_at'] = $this->createdAt;
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
		$inforDB['structure_all'] = array();
		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['structure_date'] = $ngay_co_cau;
		$inforDB['disbursement_date'] = $ngay_co_cau;
		
        $this->contract_model->update(
				array("_id" => $inforDB['_id']),
				array(
					'status'=>34
				)
			);
		unset($inforDB['_id']);
		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
		if(!empty($ck_contract ))
		{
			unset($inforDB['code_contract']);
			unset($inforDB['number_contract']);
			unset($inforDB['code_contract_child_cc']);
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			unset($inforDB['fee']);
		
			$inforDB['code_contract']=$ck_contract['code_contract'];
		
		}
      	$response = array(
			'status' => '200',
			'msg' => 'OK',
			'data' => $inforDB,
			'disbursement_date' => $inforDB['disbursement_date']
		);
		return $response;

	}

	// Lấy lãi NĐT đã trừ vào kỳ cuối
	private function get_interest_end_period_by_coupon($code_contract, $code_coupon, $date_pay)
	{
		$coupon_infor = $this->coupon_model->findOne(['code' => $code_coupon]);
		$temporary_plan_contracts = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
		$tempo_not_pay = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = !empty($date_pay) ? $date_pay : strtotime(date('Y-m-d') . '  23:59:59');
		$date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
		$time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo))) / (24 * 60 * 60));
		$is_payment_slow = false;
		$reduced_profit = 0;
		if ($time > 0) {
			$is_payment_slow = true;
		}
		if (!empty($coupon_infor)) {
			if (!empty($temporary_plan_contracts)) {
				// giam lai 03 thang dau
				if (isset($coupon_infor['is_reduction_interest']) && $coupon_infor['is_reduction_interest'] == "active") {
					foreach ($temporary_plan_contracts as $key => $tempo) {
						if ($tempo['ky_tra'] == 1 || $tempo['ky_tra'] == 2 || $tempo['ky_tra'] == 3) {
							$reduced_profit += $tempo['lai_ky'];
						}
					}
					// giam lai 01 thang dau
				} elseif (isset($coupon_infor['down_interest_on_month']) && $coupon_infor['down_interest_on_month'] == "active") {
					foreach ($temporary_plan_contracts as $key1 => $tempo1) {
						if ($tempo1['ky_tra'] == 1) {
							$reduced_profit += $tempo1['lai_ky'];
						}
					}
				}
				//fore bảng lãi kỳ để xác định chậm trả
				foreach ($temporary_plan_contracts as $temporary) {
					if ($temporary['so_ngay_cham_tra'] > 0) {
						$is_payment_slow = true;
						break;
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'amount_interest_reduction' => (!$is_payment_slow) ? $reduced_profit : 0,
		);
		return $response;
	}
	
   




}

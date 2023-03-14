<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Caculator extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model("log_contract_thn_model");
		$this->load->model('user_model');
		$this->load->model('coupon_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("notification_model");
		$this->load->model("store_model");
		$this->load->helper('lead_helper');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
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
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];

					// Get access right
					$roles = $this->role_model->getRoleByUserId((string)$this->id);
					$this->roleAccessRights = $roles['role_access_rights'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
		unset($this->dataPost['type']);

	}

	public function get_one_contract_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$field = $this->security->xss_clean($this->dataPost['field']);
		$value = $this->security->xss_clean($this->dataPost['value']);
		$status = isset($this->dataPost['status']) ? (int)$this->dataPost['status'] : 17;
		$contract = $this->contract_model->findOne(array($field => $value, 'status' => $status));

		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_contract_cc_gh_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$field = $this->security->xss_clean($this->dataPost['field']);
		$value = $this->security->xss_clean($this->dataPost['value']);

		$contract = $this->contract_model->findOne(array($field => $value));

		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	private function periodDays($start_date, $per, $disbursement_date, $period_pay_interest)
	{

		$ngay_ky_tra = 0;

		$from = new DateTime($start_date);
		$day = $from->format('j');
		$from->modify('first day of this month');
		$period = new DatePeriod($from, new DateInterval('P1M'), $per);
		$arr_date = [];
		foreach ($period as $date) {
			$lastDay = clone $date;
			$lastDay->modify('last day of this month');
			$date->setDate($date->format('Y'), $date->format('n'), $day);
			if ($date > $lastDay) {
				$date = $lastDay;
			}
			$arr_date[] = $date->format('Y-m-d');
		}
		$datetime1 = new DateTime($arr_date[$per - 1]);

		$datetime2 = new DateTime($arr_date[$per]);

		$difference = $datetime1->diff($datetime2);

		return array('date' => strtotime($arr_date[$per]), 'days' => $difference->days);

	}

	public function caculator_monthly_fee_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$html = "";

		$type_loan = !empty($this->dataPost['type_loan']) ? $this->dataPost['type_loan'] : "DKX";
		$type_property = !empty($this->dataPost['type_property']) ? $this->dataPost['type_property'] : "XM";
		$ngay_giai_ngan = !empty($this->dataPost['ngay_giai_ngan']) ? strtotime($this->dataPost['ngay_giai_ngan']) : date('Y-m-d');
		$amount_money = !empty($this->dataPost['amount_money']) ? $this->dataPost['amount_money'] : "10000000";
		$number_day_loan = !empty($this->dataPost['ky_han']) ? $this->dataPost['ky_han'] * 30 : "360";
		$period_pay_interest = !empty($this->dataPost['period_pay_interest']) ? $this->dataPost['period_pay_interest'] : "30";
		$hinh_thuc_phi = !empty($this->dataPost['hinh_thuc_phi']) ? $this->dataPost['hinh_thuc_phi'] : "bpc";
		$coupon = !empty($this->dataPost['coupon']) ? $this->dataPost['coupon'] : "";
		if ($hinh_thuc_phi != 'coupon')
			$coupon = "";
		$loan_interest_fee = !empty($this->dataPost['loan_interest_fee']) ? $this->dataPost['loan_interest_fee'] : "0";
		$management_consulting_fee = !empty($this->dataPost['management_consulting_fee']) ? $this->dataPost['management_consulting_fee'] : "0";
		$renewal_fee = !empty($this->dataPost['renewal_fee']) ? $this->dataPost['renewal_fee'] : "0";
		$tien_giam_tru_bhkv = !empty($this->dataPost['tien_giam_tru_bhkv']) ? $this->dataPost['tien_giam_tru_bhkv'] : "0";
		$type_interest = !empty($this->dataPost['type_interest']) ? $this->dataPost['type_interest'] : "1";
		$arr_return = $this->spreadsheetFeeLoan($type_loan, $type_property, $ngay_giai_ngan, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $hinh_thuc_phi, $coupon, $loan_interest_fee, $management_consulting_fee, $renewal_fee, $tien_giam_tru_bhkv);


		// $html=  gen_html_caculator_monthly_fee($arr_return);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr_return
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function caculator_monthly_fee_w_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$html = "";

		$type_loan = !empty($this->dataPost['type_loan']) ? $this->dataPost['type_loan'] : "DKX";
		$loan_product = !empty($this->dataPost['loan_product']) ? $this->dataPost['loan_product'] : "1";
		$type_property = !empty($this->dataPost['type_property']) ? $this->dataPost['type_property'] : "XM";
		$ngay_giai_ngan = !empty($this->dataPost['ngay_giai_ngan']) ? $this->dataPost['ngay_giai_ngan'] : date('Y-m-d');
		$amount_money = !empty($this->dataPost['amount_money']) ? $this->dataPost['amount_money'] : "10000000";
		$number_day_loan = !empty($this->dataPost['ky_han']) ? $this->dataPost['ky_han'] * 30 : "360";
		$period_pay_interest = !empty($this->dataPost['period_pay_interest']) ? $this->dataPost['period_pay_interest'] : "30";
		$hinh_thuc_phi = !empty($this->dataPost['hinh_thuc_phi']) ? $this->dataPost['hinh_thuc_phi'] : "bpc";
		$coupon = !empty($this->dataPost['coupon']) ? $this->dataPost['coupon'] : "";
		if ($hinh_thuc_phi != 'coupon')
			$coupon = "";
		$data_coupon = $this->coupon_model->findOne(array("code" => $coupon));
		$is_reduction_interest = isset($data_coupon['is_reduction_interest']) ? $data_coupon['is_reduction_interest'] : "deactive";
		$down_interest_on_month = isset($data_coupon['down_interest_on_month']) ? $data_coupon['down_interest_on_month'] : "deactive";
		$loan_interest_fee = !empty($this->dataPost['loan_interest_fee']) ? $this->dataPost['loan_interest_fee'] : "0";
		$management_consulting_fee = !empty($this->dataPost['management_consulting_fee']) ? $this->dataPost['management_consulting_fee'] : "0";
		$renewal_fee = !empty($this->dataPost['renewal_fee']) ? $this->dataPost['renewal_fee'] : "0";
		$type_interest = !empty($this->dataPost['loan_interest']) ? $this->dataPost['loan_interest'] : "2";
		$ngay_tat_toan = !empty($this->dataPost['ngay_tat_toan']) ? strtotime($this->dataPost['ngay_tat_toan']) : date('Y-m-d');
		$tien_giam_tru_bhkv = 0;
		$arr_return = $this->spreadsheetFeeLoan($type_loan, $type_property, $ngay_giai_ngan, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $hinh_thuc_phi, $coupon, $loan_interest_fee, $management_consulting_fee, $renewal_fee, $loan_product, $is_reduction_interest, $tien_giam_tru_bhkv, $down_interest_on_month);
		$fee = $this->get_fee($type_loan, $type_property, $ngay_giai_ngan, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $hinh_thuc_phi, $coupon, $loan_interest_fee, $management_consulting_fee, $renewal_fee, $loan_product);
		$phi_tat_toan = $this->tinh_phi_tat_toan($arr_return, $ngay_tat_toan, $ngay_giai_ngan, $type_loan, $fee)['tong_tat_toan'];
		//$phi_tat_toan=  $this->tinh_phi_tat_toan($arr_return,$ngay_tat_toan,$ngay_giai_ngan,$type_loan,$fee);


		$html = gen_html_caculator_loan($arr_return, $phi_tat_toan);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function tinh_phi_tat_toan($arr_lai_ky, $ngay_tat_toan, $ngay_giai_ngan, $type_loan, $fee)
	{
		$ngay_giai_ngan = strtotime($ngay_giai_ngan);
		if (!empty($arr_lai_ky)) {
			$ck_ky_next = 0;
			$tong_goc_con = 0;
			$ky_next = array();
			$tong_lai_phi_den_tt = 0;
			$goc_chua_tra_den_ht = 0;
			$so_ngay_vay = 0;
			$penalty_total = 0;
			foreach ($arr_lai_ky as $key => $value) {
				$so_ngay_cham_tra = 0;
				$tong_goc_con += (float)$value['tien_goc_1ky'];
				if ($value['status'] == 1) {
					if ($value['ky_tra'] == 1) {
						$so_ngay_cham_tra = intval(($ngay_tat_toan - $value['ngay_ky_tra']) / (24 * 60 * 60)) + 1;


					} else {


						$so_ngay_cham_tra = intval(($ngay_tat_toan - $value['ngay_ky_tra']) / (24 * 60 * 60));
					}


				}
				if ($value['ngay_ky_tra'] < $ngay_tat_toan) {
					$tong_lai_phi_den_tt += (float)$value['phi_tu_van'] + (float)$value['phi_tham_dinh'] + (float)$value['lai_ky'];
				}
				$penalty_total += $this->contract_model->tinh_phi_phat($value['tien_tra_1_ky'], $fee['fee']['penalty_percent'], $fee['fee']['penalty_amount'], $so_ngay_cham_tra, $value['so_ngay']);
				if ($value['ngay_ky_tra'] > $ngay_tat_toan) {
					$goc_chua_tra_den_dao_han += (float)$value['tien_goc_1ky'];
				}
				if ($value['ngay_ky_tra'] >= $ngay_tat_toan && $ck_ky_next == 0) {
					$ck_ky_next = 1;
					$ky_next = $value;
				}
				$so_ngay_vay += $value['so_ngay'];
			}
			if (!empty($ky_next)) {
				//Lấy lãi phí
				$ngay_trong_ky = $ky_next['so_ngay'];
				$ngay_ky_tra_ki_tiep_theo = $ky_next['ngay_ky_tra'];
				$lai_con_lai_phai_tra_cua_ki_tiep_theo = (float)$ky_next['lai_ky'];
				$phi_con_lai_phai_tra_cua_ki_tiep_theo = (float)$ky_next['phi_tu_van'] + (float)$ky_next['phi_tham_dinh'];
				$timestamp30days = $ngay_trong_ky * 86400; // 1/5
				$rangeDate = $ngay_ky_tra_ki_tiep_theo - $ngay_tat_toan;  // = 1/6 - 15/5 = 23
				$so_ngay_no_thuc_te = 0;
				if ($timestamp30days - $rangeDate > 0) {
					$so_ngay_no_thuc_te = round(($timestamp30days - $rangeDate) / 86400);
				}
				if ($ngay_trong_ky == 0) $ngay_trong_ky = 30;
				if ($so_ngay_no_thuc_te <= 0) $so_ngay_no_thuc_te = 1;
				$lai_con_no_thuc_te = 0;
				$phi_con_no_thuc_te = 0;
				$lai_con_no_thuc_te = $lai_con_lai_phai_tra_cua_ki_tiep_theo * $so_ngay_no_thuc_te / $ngay_trong_ky;
				$phi_con_no_thuc_te = $phi_con_lai_phai_tra_cua_ki_tiep_theo * $so_ngay_no_thuc_te / $ngay_trong_ky;
				//lấy tất toán trước hạn
				$datediff = $ngay_tat_toan - $ngay_giai_ngan;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
				$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
				$so_ngay_vay_thuc_te = ($so_ngay_vay_thuc_te < 0) ? 1 : $so_ngay_vay_thuc_te;
				$fee['disbursement_date'] = $ngay_giai_ngan;
				$phi_tat_toan_truoc_han = $this->get_phi_tat_toan_truoc_han($fee, $ngay_tat_toan, $so_ngay_vay, $so_ngay_vay_thuc_te, $type_loan, $goc_chua_tra_den_dao_han);
				return array('tong_tat_toan' => $tong_goc_con + $tong_lai_phi_den_tt + $phi_tat_toan_truoc_han + $lai_con_no_thuc_te + $phi_con_no_thuc_te + $penalty_total, 'so_ngay_vay_thuc_te' => $so_ngay_vay_thuc_te, 'so_ngay_vay_thuc_te' => $so_ngay_vay_thuc_te, 'phi_tat_toan_truoc_han' => $phi_tat_toan_truoc_han, 'lai_con_no_thuc_te' => $lai_con_no_thuc_te, 'phi_con_no_thuc_te' => $phi_con_no_thuc_te, 'tong_lai_phi_den_tt' => $tong_lai_phi_den_tt, 'goc_chua_tra_den_dao_han' => $goc_chua_tra_den_dao_han, 'penalty_total' => $penalty_total);
				//return $lai_con_no_thuc_te;
			} else {
				return 0;


			}
		}
	}

	public function get_phi_tat_toan_truoc_han($contractDB, $date_pay = "", $so_ngay_vay = 0, $so_ngay_vay_thuc_te = 0, $type_loan, $goc_chua_tra_den_dao_han)
	{
		$ngay_giai_ngan = $contractDB['disbursement_date'];

		$timestamp_ngay_giai_ngan = $ngay_giai_ngan;
		$hinh_thuc_vay = $type_loan;


		$percent_prepay_phase_1 = 0;
		$percent_prepay_phase_2 = 0;
		$percent_prepay_phase_3 = 0;
		if (!empty($contractDB['fee'])) {
			$fee = $contractDB['fee'];
			$percent_prepay_phase_1 = floatval($fee['percent_prepay_phase_1']) / 100;
			$percent_prepay_phase_2 = floatval($fee['percent_prepay_phase_2']) / 100;
			$percent_prepay_phase_3 = floatval($fee['percent_prepay_phase_3']) / 100;
		}
		$phi_tat_toan_truoc_han = 0;

		$phase = $so_ngay_vay_thuc_te / $so_ngay_vay;
		//Phase 1
		if ($phase > 0 && $phase < 1 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_1 * $goc_chua_tra_den_dao_han;
		//Phase 2
		if ($phase > 1 / 3 && $phase < 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_2 * $goc_chua_tra_den_dao_han;
		//Phase 3
		if ($phase > 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_3 * $goc_chua_tra_den_dao_han;

		//hình thức vay = CC -> Free = $so_ngay_vay_thuc_te > $so_ngay_vay - 3
		if ($hinh_thuc_vay == 'CC') {
			if ($so_ngay_vay_thuc_te > $so_ngay_vay - 2) {
				$phi_tat_toan_truoc_han = 0;
			}
		}
		//hình thức vay = DKX -> Free = $so_ngay_vay_thuc_te > $so_ngay_vay - 7
		if ($hinh_thuc_vay == 'DKX') {
			if ($so_ngay_vay_thuc_te > $so_ngay_vay - 6) {
				$phi_tat_toan_truoc_han = 0;
			}
		}
		return $phi_tat_toan_truoc_han;
	}

	private function get_fee($type_loan, $typeProperty, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $hinh_thuc_phi, $coupon, $loan_interest_fee, $management_consulting_fee, $renewal_fee, $loan_product)
	{
//    $amount_money  tong tien
//    $type_loan  hinh thuc vay
//    $number_day_loan  tong so ngay vay
//    $period_pay_interest   so ngay thuc te 1 ky
//    $type_interest   hinh thuc tra lai
//    $insurrance  bao hiem
		$arr_return = array();

		// get thông tin phí vay
		if ($hinh_thuc_phi == "bpc") {
			$contract = array('fee' => $this->getFee('', $type_loan, $typeProperty, $number_day_loan, $loan_product, $amount_money));
			// return $contract;
		} else if ($hinh_thuc_phi == "coupon") {
			$contract = array('fee' => $this->getFee($coupon, $type_loan, $typeProperty, $number_day_loan, $loan_product, $loan_product, $amount_money));
		} else if ($hinh_thuc_phi == "other") {
			$contract = array('fee' => $this->getFee('', $type_loan, $typeProperty, $number_day_loan, $loan_product, $amount_money));
			if (!empty($contract['fee'])) {
				if (!empty($contract)) {

					$contract['fee']['percent_advisory'] = $management_consulting_fee;


					$contract['fee']['percent_expertise'] = $renewal_fee;


					$contract['fee']['percent_interest_customer'] = $loan_interest_fee;
				}

			}
		}
		return $contract;
	}

	private function spreadsheetFeeLoan($type_loan, $typeProperty, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $hinh_thuc_phi, $coupon, $loan_interest_fee, $management_consulting_fee, $renewal_fee, $loan_product, $is_reduction_interest, $tien_giam_tru_bhkv, $down_interest_on_month)
	{
//    $amount_money  tong tien
//    $type_loan  hinh thuc vay
//    $number_day_loan  tong so ngay vay
//    $period_pay_interest   so ngay thuc te 1 ky
//    $type_interest   hinh thuc tra lai
//    $insurrance  bao hiem
		$arr_return = array();

		// get thông tin phí vay
		if ($hinh_thuc_phi == "bpc") {
			$contract = array('fee' => $this->getFee('', $type_loan, $typeProperty, $number_day_loan, $loan_product, $amount_money));
			// return $contract;
		} else if ($hinh_thuc_phi == "coupon") {
			$contract = array('fee' => $this->getFee($coupon, $type_loan, $typeProperty, $number_day_loan, $loan_product, $amount_money));
		} else if ($hinh_thuc_phi == "other") {
			$contract = array('fee' => $this->getFee('', $type_loan, $typeProperty, $number_day_loan, $loan_product, $amount_money));
			if (!empty($contract['fee'])) {
				if (!empty($contract)) {

					$contract['fee']['percent_advisory'] = $management_consulting_fee;


					$contract['fee']['percent_expertise'] = $renewal_fee;


					$contract['fee']['percent_interest_customer'] = $loan_interest_fee;
				}

			}
		}

		$fee = array();
		$tong_lai_3ky = 0;
		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		if (!empty($contract['fee'])) {
			$fee = $contract['fee'];
			if (empty($fee['percent_advisory'])) {
				$fee['percent_advisory'] = 0;
				$pham_tram_phi_tu_van = 0;
			} else {
				$pham_tram_phi_tu_van = floatval($fee['percent_advisory']) / 100;
			}
			if (empty($fee['percent_expertise'])) {
				$fee['percent_expertise'] = 0;
				$pham_tram_phi_tham_dinh = 0;
			} else {
				$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise']) / 100;
			}
			if (empty($fee['percent_interest_customer'])) {
				$fee['percent_interest_customer'] = 0;
				$lai_suat_ndt = 0;
			} else {
				$lai_suat_ndt = floatval($fee['percent_interest_customer']) / 100;
			}
		}
		$tien_goc = $amount_money;

		// số kỳ vay
		$so_ky_vay = (int)$number_day_loan / (int)$period_pay_interest;

		$lai_luy_ke = 0;
		//Hinh thức lãi dư nợ giảm dần
		if ($type_interest == 1) {
			//tiền trả 1 kỳ pow(2, -3)
			// $goc_lai_1_ky = ($tien_goc*$lai_suat_ndt)/(1-pow((1+$lai_suat_ndt),-$so_ky_vay));
			$goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_goc);
			// tong tien phai tra 1 ky
			$tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
			//tiền trả 1 kỳ làm tròn
			$round_tien_tra_1_ky = round($tien_tra_1_ky);

			//gốc còn lại
			$tien_goc_con = $tien_goc;
			//tong cac loai phi
			$so_ngay = 0;
			//khoan vay 1 ky
			$lai_ki_dau = 0;
			for ($i = 1; $i <= $so_ky_vay; $i++) {
				//kỳ trả
				$date_ky_tra = $this->periodDays($disbursement_date, $i, $disbursement_date, $period_pay_interest)['date'];
				$so_ngay = $this->periodDays($disbursement_date, $i, $disbursement_date, $period_pay_interest)['days'];
				$ky_tra = $i;
				$current_plan = $i == 1 ? $i : 2;
				//lãi
				$lai_ky = $lai_suat_ndt * $tien_goc_con;
				if ($is_reduction_interest == "active") {
					if ($ky_tra == 1 || $ky_tra == 2 || $ky_tra == 3) {
						$tong_lai_3ky += $lai_ky;
					}
					if ($ky_tra == $so_ky_vay) {
						$tien_tra_1_ky = $tien_tra_1_ky - $tong_lai_3ky;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				} elseif ($down_interest_on_month == "active") {
					if ($ky_tra == 1) {
						$lai_ki_dau += $lai_ky;
					}
					if ($ky_tra == $so_ky_vay) {
						// trừ tiền coupon có giảm lãi 1 tháng đầu vào kỳ cuối
						$tien_tra_1_ky = $tien_tra_1_ky - $lai_ki_dau;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				}
				if ($tien_giam_tru_bhkv > 0) {

					if ($ky_tra == $so_ky_vay) {
						$tien_tra_1_ky = $tien_tra_1_ky - $tien_giam_tru_bhkv;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				}

				// goc da tra
				$tien_goc_1ky = $goc_lai_1_ky - $lai_ky;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
				$lai_luy_ke = $lai_luy_ke + $tong_phi_lai;
				//tiền gốc
				//tiền gốc còn lại
				$tien_goc_con = $tien_goc_con - $tien_goc_1ky;
				//tiền tất toán
				$data_1ky = array(

					'type' => $type_interest,
					'current_plan' => $current_plan, // kỳ hiện tại phải đóng
					'is_penalty' => false, // kỳ bị phat
					'penalty' => 0,
					'investor_code' => $investor_code,
					'ky_tra' => $ky_tra,
					'so_ngay' => $so_ngay,
					'ngay_ky_tra' => $date_ky_tra,
					'tien_tra_1_ky' => $tien_tra_1_ky,
					'round_tien_tra_1_ky' => $round_tien_tra_1_ky,
					'tien_goc_1ky' => $tien_goc_1ky,
					'tong_phi_lai' => $tong_phi_lai,
					'phi_tu_van' => $phi_tu_van,
					'phi_tham_dinh' => $phi_tham_dinh,
					'lai_ky' => $lai_ky,
					'lai_luy_ke' => $lai_luy_ke,
					'tien_goc_con' => $tien_goc_con,
					'da_thanh_toan' => 0,
					'status' => 1, // 1: sap toi, 2: da dong, 3: qua han
					'created_at' => $this->createdAt,

					"tien_goc_1ky_phai_tra" => $tien_goc_1ky,
					"tien_goc_1ky_da_tra" => 0,
					"tien_goc_1ky_con_lai" => $tien_goc_1ky,

					"tien_lai_1ky_phai_tra" => $lai_ky,
					"tien_lai_1ky_da_tra" => 0,
					"tien_lai_1ky_con_lai" => $lai_ky,

					"tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
					"tien_phi_1ky_da_tra" => 0,
					"tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,


					"tien_phi_cham_tra_1ky_da_tra" => 0,
					"tien_phi_cham_tra_1ky_con_lai" => 0,

					"fee_delay" => 0,
					"fee_finish_contract" => 0,
					"fee_extend" => 0

				);
				$arr_return += [$i => $data_1ky];

			}
			return $arr_return;
		} else {

			//hình thức lãi hàng tháng, gốc cuối kỳ
			//khoan vay 1 ky
			for ($i = 1; $i <= $so_ky_vay; $i++) {
				//kỳ trả
				$date_ky_tra = $this->periodDays($disbursement_date, $i, $disbursement_date, $period_pay_interest)['date'];
				$so_ngay = $this->periodDays($disbursement_date, $i, $disbursement_date, $period_pay_interest)['days'];
				$ky_tra = $i;
				$current_plan = $i == 1 ? $i : 2;
				//lãi
				$lai_ky = round($lai_suat_ndt * $tien_goc);

				// goc da tra
				$tien_goc_1ky = $i == $so_ky_vay ? $tien_goc : 0;
				//tiền gốc còn lại
				$tien_goc_con = $i == $so_ky_vay ? 0 : $tien_goc;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
				$tien_tra_1_ky = $tien_goc_1ky + $tong_phi_lai;
				$lai_luy_ke = $lai_luy_ke + $tong_phi_lai;
				if ($is_reduction_interest == "active") {
					if ($ky_tra == 1 || $ky_tra == 2 || $ky_tra == 3) {
						$tong_lai_3ky += $lai_ky;
					}
					if ($ky_tra == $so_ky_vay) {
						$tien_tra_1_ky = $tien_tra_1_ky - $tong_lai_3ky;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				} elseif ($down_interest_on_month == "active") {
					if ($ky_tra == 1) {
						$lai_ki_dau += $lai_ky;
					}
					if ($ky_tra == $so_ky_vay) {
						// trừ tiền coupon có giảm lãi 1 tháng đầu vào kỳ cuối
						$tien_tra_1_ky = $tien_tra_1_ky - $lai_ki_dau;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				}
				if ($tien_giam_tru_bhkv > 0) {

					if ($ky_tra == $so_ky_vay) {
						$tien_tra_1_ky = $tien_tra_1_ky - $tien_giam_tru_bhkv;
						$round_tien_tra_1_ky = round($tien_tra_1_ky);
					}
				}
				$data_1ky = array(
					'type' => $type_interest,
					'current_plan' => $current_plan, // kỳ hiện tại phải đóng
					'is_penalty' => false, // kỳ bị phat
					'penalty' => 0,
					'ky_tra' => $ky_tra,
					'so_ngay' => $so_ngay,
					'ngay_ky_tra' => $date_ky_tra,
					'tien_tra_1_ky' => $tien_tra_1_ky,
					'tien_goc_1ky' => $tien_goc_1ky,
					'tien_goc_con' => $tien_goc_con,
					'da_thanh_toan' => 0,
					'phi_tu_van' => $phi_tu_van,
					'phi_tham_dinh' => $phi_tham_dinh,
					'lai_ky' => $lai_ky,
					'lai_luy_ke' => $lai_luy_ke,
					'status' => 1,  // 1: sap toi, 2: da dong, 3: qua han
					'created_at' => $this->createdAt,

					"tien_goc_1ky_phai_tra" => $tien_goc_1ky,
					"tien_goc_1ky_da_tra" => 0,
					"tien_goc_1ky_con_lai" => $tien_goc_1ky,

					"tien_lai_1ky_phai_tra" => $lai_ky,
					"tien_lai_1ky_da_tra" => 0,
					"tien_lai_1ky_con_lai" => $lai_ky,

					"tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
					"tien_phi_1ky_da_tra" => 0,
					"tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,

					"tien_phi_cham_tra_1ky_da_tra" => 0,
					"tien_phi_cham_tra_1ky_con_lai" => 0,
					"fee_delay" => 0,
					"fee_finish_contract" => 0,
					"fee_extend" => 0

				);

				$arr_return += [$i => $data_1ky];
			}
			return $arr_return;
		}

	}

	public function getFee($code_coupon, $typeLoan, $typeProperty, $number_day_loan, $loan_product, $amount_loan)
	{

		$default = array(
			"percent_interest_customer" => 0,
			"percent_interest_investor" => 0,
			"percent_advisory" => 0,
			"percent_expertise" => 0,
			"penalty_percent" => 0,
			"penalty_amount" => 0,
			"extend" => 0,
			"percent_prepay_phase_1" => 0,
			"percent_prepay_phase_2" => 0,
			"percent_prepay_phase_3" => 0
		);

		if ($typeProperty == "NĐ") {
			$typeLoan = "NĐ";
		}
		if ($typeLoan == "DKX" && $typeProperty == "XM") {
			$typeLoan = "DKXM";
		}
		if ($typeLoan == "DKX" && $typeProperty == "OTO") {
			$typeLoan = "DKXOTO";
		}
		if ($typeLoan == "DKX" && $typeProperty == "TC") {
			$typeLoan = "TC";
		}
		if ($loan_product == 14) {
			$typeLoan = "KDOL";
		}
		if ($loan_product == 14 && $typeProperty == "TC") {
			$typeLoan = "KDOL_TC";
		}

		if ($typeLoan == "NĐ") {
			$data = $this->fee_loan_model->findOne(array("from" => ['$lte' => (int)$this->createdAt], "to" => ['$gte' => (int)$this->createdAt], "status" => 'active', 'type' => "bieu-phi-nha-dat"));
			$default = array();
			if (!empty($data)) {
				$default['percent_prepay_phase_1'] = $data['infor']['percent_prepay_phase_1'];
				$default['percent_prepay_phase_2'] = $data['infor']['percent_prepay_phase_2'];
				$default['percent_prepay_phase_3'] = $data['infor']['percent_prepay_phase_3'];
				$default['extend'] = $data['infor']['extend'];
				$default['penalty_amount'] = $data['infor']['penalty_amount'];
				$default['penalty_percent'] = $data['infor']['penalty_percent'];
				if ($amount_loan <= 100000000) {
					$default['percent_interest_customer'] = $data['infor']['100']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['100']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['100']['percent_expertise'];
				} elseif ($amount_loan > 100000000 && $amount_loan <= 200000000) {
					$default['percent_interest_customer'] = $data['infor']['100-200']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['100-200']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['100-200']['percent_expertise'];
				} elseif ($amount_loan > 200000000) {
					$default['percent_interest_customer'] = $data['infor']['200']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['200']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['200']['percent_expertise'];
				}
			}
		} else {
			//Get record by time
			$data = $this->fee_loan_model->findOne(
				array(
					"status" => 'active',
					"type" => array(
						'$exists' => false
					),
					"from" => ['$lte' => (int)$this->createdAt],
					"to" => ['$gte' => (int)$this->createdAt]
				)
			);
			if (!empty($data)) $default = $data['infor'][$number_day_loan][$typeLoan];

		}

		$data['code_coupon'] = $this->security->xss_clean($code_coupon);
		$data_coupon = $this->coupon_model->findOne(array("code" => $data['code_coupon'], 'status' => 'active'));

		$arrNew = array();
		foreach ($default as $key => $value) {
			if (empty($data_coupon)) {
				$arrNew[$key] = (float)$value;
			} else {
				if (isset($data_coupon['set_by_coupon']) && $data_coupon['set_by_coupon'] == 'active') {
					if (isset($data_coupon[$key]) && $data_coupon[$key] > 0) {
						$fee = (float)$data_coupon[$key];
					} else {
						if (isset($data_coupon[$key]) && ($key == 'percent_advisory' || $key == 'percent_expertise' || $key == 'percent_interest_customer')) {
							$fee = (float)$data_coupon[$key];
						} else {
							$fee = (float)$value;
						}
					}
					$arrNew[$key] = ($fee < 0) ? 0 : $fee;

				} else {
					if (isset($data_coupon[$key])) {
						if (isset($data_coupon[$key]) && ($key == 'percent_advisory' || $key == 'percent_expertise')) {
							$fee = (float)$value - (float)$data_coupon[$key];
						} else {
							$fee = (float)$value;
						}
					} else {
						$fee = (float)$value;
					}
					$arrNew[$key] = ($fee < 0) ? 0 : $fee;

				}
			}
		}

		return $arrNew;
	}
}

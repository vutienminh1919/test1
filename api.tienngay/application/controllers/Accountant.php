<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Accountant extends REST_Controller
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
		$this->load->model('fee_loan_model');
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
		$this->load->model("coupon_model");
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

	public function update_relative_infor_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$customer_phone = !empty($data['customer_phone']) ? $data['customer_phone'] : "";
		$phone1 = !empty($data['phone_1']) ? $data['phone_1'] : "";
		$phone2 = !empty($data['phone_2']) ? $data['phone_2'] : "";
		$address_1 = !empty($data['address_1']) ? $data['address_1'] : "";
		$address_2 = !empty($data['address_2']) ? $data['address_2'] : "";
		$address = !empty($data['address']) ? $data['address'] : "";
		$contract = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		// $this->log_property($data);
		$relative_infor = $contract['relative_infor'];
		$relative_infor['customer_phone_number'] = $customer_phone;
		$relative_infor['phone_number_relative_1'] = $phone1;
		$relative_infor['phone_number_relative_2'] = $phone2;
		$relative_infor['hoursehold_relative_1'] = $address_1;
		$relative_infor['hoursehold_relative_2'] = $address_2;
		$relative_infor['address'] = $address;
		$log_contract_thn = $this->log_contract_thn_model->findOne(array("contract_id" => $id));
		$insertLog = array(
			"type" => "contract",
			"action" => "update_info",
			"contract_id" => $id,
			"old" => $contract,
			"new" => $relative_infor,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		if (empty($log_contract_thn)) {
			$this->log_contract_thn_model->insert($insertLog);
		} else {
			$this->log_contract_thn_model->update(array("contract_id" => $id), array("new" => $relative_infor));
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_general_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		}

		if (!empty($code_store)) {
			$condition['source'] = $code_store;
		}

		if (!empty($condition)) {
			$contract = $this->contract_model->getByRole($condition);
		} else {
			$contract = $this->contract_model->find();
		}
		$arr_data_QC_nguon = lead_nguon();
		$arr_data_QC_SOU = [];
		$arr_data_QC_CAM = [];
		$arr_return_QC_SOU = [];
		$arr_return_QC_CAM = [];
		$arr_return_QC_nguon = [];
		$arr_phone = [];
		$html = "";

		//var_dump($arr_return_QC_CAM) ; die;
		// $html=  gen_html_QC($arr_return_QC_nguon,$arr_return_QC_SOU,$arr_return_QC_CAM);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_debt_group_pgd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'),
				'stores' => array($code_store),
				'status' => 17
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		}

		if (!empty($condition)) {
			$contract = $this->contract_model->getContractByRole($condition);
		} else {
			$contract = $this->contract_model->find();
		}
		$store = $this->store_model->find_where_in('status', ['active', 'deactive']);
		$total_du_no_giai_ngan = 0;
		$total_du_no_dang_cho_vay = 0;
		$total_du_no_nhom1 = 0; //DPD < 10 NGÀY
		$total_du_no_nhom2 = 0; //10 =< DPD <=90 NGÀY
		$total_du_no_nhom3 = 0; //90 < DPD <= 180 NGÀY
		$total_du_no_nhom4 = 0; //180 < DPD <=360 NGÀY
		$total_du_no_nhom5 = 0; //DPD > 360 NGÀY
		$total_du_no_xau = 0;   //nhóm 3,4,5
		$tyle_nhom1_giai_ngan = 0; // (nhom1/giaingan)*100
		$tyle_nhom1_dang_cho_vay = 0; // (nhom1/dangchovay)*100
		$tyle_nhom2_giai_ngan = 0; // (nhom2/giaingan)*100
		$tyle_nhom2_dang_cho_vay = 0; // (nhom2/dangchovay)*100
		$tyle_nhom3_giai_ngan = 0; // (nhom3/giaingan)*100
		$tyle_nhom3_dang_cho_vay = 0; // (nhom3/dangchovay)*100
		$tyle_nhom4_giai_ngan = 0; // (nhom4/giaingan)*100
		$tyle_nhom4_dang_cho_vay = 0; // (nhom4/dangchovay)*100
		$tyle_nhom5_giai_ngan = 0; // (nhom5/giaingan)*100
		$tyle_nhom5_dang_cho_vay = 0; // (nhom5/dangchovay)*100
		$tyle_noxau_giai_ngan = 0; // (noxau/giaingan)*100
		$tyle_noxau_dang_cho_vay = 0; // (noxau/dangchovay)*100

		$html = "";
		$arr_return_daily = [];
		foreach ($store as $key1 => $value1) {
			$total_du_no_giai_ngan = 0;
			$total_du_no_dang_cho_vay = 0;
			$total_du_no_nhom1 = 0; //DPD < 10 NGÀY
			$total_du_no_nhom2 = 0; //10 =< DPD <=90 NGÀY
			$total_du_no_nhom3 = 0; //90 < DPD <= 180 NGÀY
			$total_du_no_nhom4 = 0; //180 < DPD <=360 NGÀY
			$total_du_no_nhom5 = 0; //DPD > 360 NGÀY
			$total_du_no_xau = 0;   //nhóm 3,4,5
			$tyle_nhom1_giai_ngan = 0; // (nhom1/giaingan)*100
			$tyle_nhom1_dang_cho_vay = 0; // (nhom1/dangchovay)*100
			$tyle_nhom2_giai_ngan = 0; // (nhom2/giaingan)*100
			$tyle_nhom2_dang_cho_vay = 0; // (nhom2/dangchovay)*100
			$tyle_nhom3_giai_ngan = 0; // (nhom3/giaingan)*100
			$tyle_nhom3_dang_cho_vay = 0; // (nhom3/dangchovay)*100
			$tyle_nhom4_giai_ngan = 0; // (nhom4/giaingan)*100
			$tyle_nhom4_dang_cho_vay = 0; // (nhom4/dangchovay)*100
			$tyle_nhom5_giai_ngan = 0; // (nhom5/giaingan)*100
			$tyle_nhom5_dang_cho_vay = 0; // (nhom5/dangchovay)*100
			$tyle_noxau_giai_ngan = 0; // (noxau/giaingan)*100
			$tyle_noxau_dang_cho_vay = 0; // (noxau/dangchovay)*100
			if (!empty($contract)) {
				foreach ($contract as $key => $value) {
					$contr = $value;
					//        $response = array(
					//     'status' => REST_Controller::HTTP_OK,
					//     'data' => $contr['store']['id']
					// );
					// $this->set_response($response, REST_Controller::HTTP_OK);
					// return;
					if (isset($contr['store']['id']))
						if ((string)$value1['_id'] == $contr['store']['id']) {
							if ($contr['status'] == 17) {
								$con = array(
									'code_contract' => $contr['code_contract'],
									'type' => 2,
									"status" => 1
								);
								$so_tien_da_tra = 0;
								$transaction = $this->transaction_model->find_where($con);
								foreach ($transaction as $key => $tran) {
									$so_tien_da_tra += $tran['amount'];
								}

								$cond = array(
									'code_contract' => $contr['code_contract'],
									'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
								);
								$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
								$contr['detail'] = array();
								if (!empty($detail)) {
									$total_paid = 0;
									foreach ($detail as $de) {

										$total_paid = $total_paid + $de['tien_tra_1_ky'];
									}
									$contr['detail'] = $detail[0];
									$contr['detail']['total_paid'] = $total_paid;
								} else {
									$condition_new = array(
										'code_contract' => $contr['code_contract'],
										'status' => 1
									);
									$detail_new = $this->contract_tempo_model->getContract($condition_new);
									if (!empty($detail_new)) {
										$contr['detail'] = $detail_new[0];

										$contr['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
									}
								}

								$total_du_no_giai_ngan += $contr['loan_infor']['amount_money'];
								$total_du_no_dang_cho_vay += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
								if (!empty($contr['detail']) && $contr['detail']['status'] == 1) {
									$current_day = strtotime(date('m/d/Y'));
									$datetime = !empty($contr['detail']['ngay_ky_tra']) ? intval($contr['detail']['ngay_ky_tra']) : $current_day;
									$time = intval(($current_day - $datetime) / (24 * 60 * 60));

									if ($time <= 10) {
										$total_du_no_nhom1 += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
									} else if ($time > 10 && $time <= 90) {
										$total_du_no_nhom2 += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
									} else if ($time > 90 && $time <= 180) {
										$total_du_no_nhom3 += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
									} else if ($time > 180 && $time <= 360) {
										$total_du_no_nhom4 += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
									} else if ($time > 360) {
										$total_du_no_nhom5 += $contr['loan_infor']['amount_money'] - $so_tien_da_tra;
									}
								}
							}
						}
				}
				$total_du_no_xau = $total_du_no_nhom3 + $total_du_no_nhom4 + $total_du_no_nhom5;
				$tyle_nhom1_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_nhom1 / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_nhom1_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_nhom1 / $total_du_no_dang_cho_vay) * 100 : 0;

				$tyle_nhom2_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_nhom2 / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_nhom2_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_nhom2 / $total_du_no_dang_cho_vay) * 100 : 0;

				$tyle_nhom3_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_nhom3 / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_nhom3_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_nhom3 / $total_du_no_dang_cho_vay) * 100 : 0;

				$tyle_nhom4_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_nhom4 / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_nhom4_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_nhom4 / $total_du_no_dang_cho_vay) * 100 : 0;

				$tyle_nhom5_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_nhom5 / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_nhom5_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_nhom5 / $total_du_no_dang_cho_vay) * 100 : 0;

				$tyle_noxau_giai_ngan = ($total_du_no_giai_ngan > 0) ? ($total_du_no_xau / $total_du_no_giai_ngan) * 100 : 0;
				$tyle_noxau_dang_cho_vay = ($total_du_no_dang_cho_vay > 0) ? ($total_du_no_xau / $total_du_no_dang_cho_vay) * 100 : 0;

				$arr_return_daily += [
					$key1 => [
						'pgd' => $value1['name'],
						'total_du_no_giai_ngan' => $total_du_no_giai_ngan,
						'total_du_no_dang_cho_vay' => $total_du_no_dang_cho_vay,
						'total_du_no_nhom1' => $total_du_no_nhom1,
						'total_du_no_nhom2' => $total_du_no_nhom2,
						'total_du_no_nhom3' => $total_du_no_nhom3,
						'total_du_no_nhom4' => $total_du_no_nhom4,
						'total_du_no_nhom5' => $total_du_no_nhom5,
						'total_du_no_xau' => $total_du_no_xau,
						'tyle_nhom1_giai_ngan' => $tyle_nhom1_giai_ngan,
						'tyle_nhom1_dang_cho_vay' => $tyle_nhom1_dang_cho_vay,
						'tyle_nhom2_giai_ngan' => $tyle_nhom2_giai_ngan,
						'tyle_nhom2_dang_cho_vay' => $tyle_nhom2_dang_cho_vay,
						'tyle_nhom3_giai_ngan' => $tyle_nhom3_giai_ngan,
						'tyle_nhom3_dang_cho_vay' => $tyle_nhom3_dang_cho_vay,
						'tyle_nhom4_giai_ngan' => $tyle_nhom4_giai_ngan,
						'tyle_nhom4_dang_cho_vay' => $tyle_nhom4_dang_cho_vay,
						'tyle_nhom5_giai_ngan' => $tyle_nhom5_giai_ngan,
						'tyle_nhom5_dang_cho_vay' => $tyle_nhom5_dang_cho_vay,
						'tyle_noxau_giai_ngan' => $tyle_noxau_giai_ngan,
						'tyle_noxau_dang_cho_vay' => $tyle_noxau_dang_cho_vay
					]
				];
			}
		}
		$html = gen_html_report_debt_group_pgd($arr_return_daily);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_work_results_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		}

		if (!empty($code_store)) {
			$condition['source'] = $code_store;
		}

		if (!empty($condition)) {
			$contract = $this->contract_model->getByRole($condition);
		} else {
			$contract = $this->contract_model->find();
		}
		$arr_data_QC_nguon = lead_nguon();
		$arr_data_QC_SOU = [];
		$arr_data_QC_CAM = [];
		$arr_return_QC_SOU = [];
		$arr_return_QC_CAM = [];
		$arr_return_QC_nguon = [];
		$arr_phone = [];
		$html = "";

		// var_dump($arr_return_QC_CAM) ; die;
		$html = gen_html_QC($arr_return_QC_nguon, $arr_return_QC_SOU, $arr_return_QC_CAM);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function caculator_monthly_fee_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$html = "";
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$money_lead = !empty($this->dataPost['money_lead']) ? $this->dataPost['money_lead'] : "";
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : "";
		$typeProperty = !empty($this->dataPost['typeProperty']) ? $this->dataPost['typeProperty'] : "";
		$fee = !empty($this->dataPost['fee']) ? $this->dataPost['fee'] : array();
		$ky_han = !empty($this->dataPost['ky_han']) ? $this->dataPost['ky_han'] : "";
		$hinh_thuc_tra_lai = !empty($this->dataPost['hinh_thuc_tra_lai']) ? $this->dataPost['hinh_thuc_tra_lai'] : "";
		$management_consulting_fee = !empty($this->dataPost['management_consulting_fee']) ? $this->dataPost['management_consulting_fee'] : "";
		$renewal_fee = !empty($this->dataPost['renewal_fee']) ? $this->dataPost['renewal_fee'] : "";
		$loan_interest = !empty($this->dataPost['loan_interest']) ? $this->dataPost['loan_interest'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$disbursement_date = !empty($this->dataPost['disbursement_date']) ? $this->dataPost['disbursement_date'] : strtotime(date('Y-m-d'));
		$hinh_thuc_vay = in_array($hinh_thuc_vay, array('1', '2')) ? 'DKX' : 'CC';
		$tien_giam_tru_bhkv = !empty($this->dataPost['tien_giam_tru_bhkv']) ? $this->dataPost['tien_giam_tru_bhkv'] : "0";
		$loan_infor_kdol = !empty($contract['loan_infor']['loan_product']['code']) ? $contract['loan_infor']['loan_product']['code'] : "1";
		$code_coupon = !empty($this->dataPost['code_coupon']) ? $this->dataPost['code_coupon'] : "";
		$data_coupon = $this->coupon_model->findOne(array("code" => $code_coupon));
		$is_reduction_interest = isset($data_coupon['is_reduction_interest']) ? $data_coupon['is_reduction_interest'] : "deactive";
		$down_interest_on_month = isset($data_coupon['down_interest_on_month']) ? $data_coupon['down_interest_on_month'] : "deactive";
		$arr_return = $this->generateFeeLoanbyMonth($fee, $typeProperty, $hinh_thuc_vay, $disbursement_date, $money_lead, $ky_han, 30, $hinh_thuc_tra_lai, $management_consulting_fee, $renewal_fee, $loan_interest, $loan_infor_kdol, $is_reduction_interest, $tien_giam_tru_bhkv, $down_interest_on_month);


		$html = gen_html_caculator_monthly_fee($arr_return);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function caculator_charge_settlement_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$date = !empty($data['date']) ? $data['date'] : "";
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";

		$dataDB = $this->contract_model->findOne(array("code_contract_disbursement" => $code_contract));

		if (empty($dataDB)) {
			$dataDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
			if (empty($dataDB))
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Không tồn tại hợp đồng",
					'check' => $code_contract
				);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
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
		// $date_now = date('d-m-Y');
		$now = strtotime($date);
		$all_contract_tempo = $this->contract_tempo_model->find_where(array('code_contract' => $dataDB['code_contract'], 'status' => 1));
		if (empty($all_contract_tempo)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại lãi kỳ của hợp đồng",
				'check' => $code_contract
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$so_ngay_vay = (int)$dataDB['loan_infor']['number_day_loan'];
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
				//$goc_lai_1_ky = round(($tien_gian_ngan*$lai_suat_ndt)/(1-pow((1+$lai_suat_ndt),-$so_ky_vay)));
				$goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_gian_ngan);
				$lai_ky = $lai_suat_ndt * $tien_goc_con;
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$phi_thanh_toan_truoc_han = 0;
				} else if (($so_ngay_da_vay >= (2 * $so_ngay_vay / 3)) && $so_ngay_chenh_lech <= 0) {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
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
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$phi_tham_dinh = $phi_tham_dinh / 30 * $so_ngay_vay_thuc_te;
					$phi_tu_van = $phi_tu_van / 30 * $so_ngay_vay_thuc_te;
					$lai_ky = $lai_ky / 30 * $so_ngay_vay_thuc_te;
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $lai_ky;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con * $phan_tram_phi_phat_tra_cham * $so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			}

		}

		//Get lãi + phí đến thời điểm đáo hạn
		$laiPhiDenThoiDiemDaoHan = $this->lai_phi_bang_ky_den_thoi_diem_dao_han($dataDB['code_contract_disbursement']);

		$arr_return_kq = array(1 => [
			'laiPhiDenThoiDiemDaoHan' => $laiPhiDenThoiDiemDaoHan,
			'total_paid' => $tong_tien_thanh_toan,
			'day_debt' => $so_ngay_vay_thuc_te,
			'so_ngay_da_vay_hop_dong' => $so_ngay_da_vay,
			'lai_ky' => $lai_ky,
			'phi_tham_dinh' => $phi_tham_dinh,
			'phi_tu_van' => $phi_tu_van,
			'tien_goc_con' => $tien_goc_con,
			'phi_thanh_toan_truoc_han' => $phi_thanh_toan_truoc_han,
			'tien_phi_phat_tra_cham' => $tien_phi_phat_tra_cham]
		);
		$html = "";
		$html = gen_html_caculator_charge_settlement($arr_return_kq);
		// var_dump($arr_return_kq); die;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,
			'check' => $arr_return_kq
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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

	public function lai_phi_bang_ky_den_thoi_diem_dao_han($contractCode)
	{
		//Lấy thông tin kì hiện tại
		$tempPlan = $this->temporary_plan_contract_model->find_where(array(
			"code_contract_disbursement" => $contractCode
		));
		$tong_lai_phi_con_lai_den_thoi_diem_dao_han = 0;
		foreach ($tempPlan as $item) {
			$tien_lai_1ky_con_lai = !empty($item['tien_lai_1ky_con_lai']) ? $item['tien_lai_1ky_con_lai'] : 0;
			$tien_phi_1ky_con_lai = !empty($item['tien_phi_1ky_con_lai']) ? $item['tien_phi_1ky_con_lai'] : 0;
			$tong_lai_phi_con_lai_den_thoi_diem_dao_han = $tong_lai_phi_con_lai_den_thoi_diem_dao_han +
				$tien_lai_1ky_con_lai +
				$tien_phi_1ky_con_lai;
		}
		return $tong_lai_phi_con_lai_den_thoi_diem_dao_han;
	}

	private function getUserGroupRole($GroupIds)
	{
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = key($item);
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}

	public function getFee($typeProperty, $typeLoan, $number_day_loan, $loan_product, $amount_loan)
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
		$arrNew = array();
		//Get record by time
		$data = $this->fee_loan_model->findTop(array("from" => array('$lt' => $this->createdAt)), 1);
		if ($typeLoan == "DKX" && $typeProperty == "XM") {
			$typeLoan = "DKXM";
		}
		if ($typeLoan == "DKX" && $typeProperty == "OTO") {
			$typeLoan = "DKXOTO";
		}
		if ($typeLoan == "DKX" && $typeProperty == "TC") {
			$typeLoan = "TC";
		}
		if ($loan_product == 14 && $typeProperty == "TC") {
			$typeLoan = "KDOL_TC";
		}

		if ($typeLoan == "NĐ") {
			$data = $this->fee_loan_model->findOne(array("from" => ['$lte' => $this->createdAt], "to" => ['$gte' => $this->createdAt], "status" => 'active', 'type' => "bieu-phi-nha-dat"));
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
					"from" => ['$lte' => $contract['disbursement_date']],
					"to" => ['$gte' => $contract['disbursement_date']]
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
						if (isset($data_coupon[$key]) && ($key == 'percent_advisory' || $key == 'percent_expertise')) {
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


	private function periodDays($start_date, $per, $disbursement_date, $period_pay_interest)
	{
		$date_ngay_t = strtotime($this->config->item("date_t_apply"));
		$ngay_ky_tra = 0;
		if ($disbursement_date < $date_ngay_t) {
			$ngay_ky_tra = $disbursement_date + (intval($period_pay_interest) * 24 * 60 * 60 * $per) - 24 * 60 * 60;
			$so_ngay = $period_pay_interest;
			return array('date' => $ngay_ky_tra, 'days' => $so_ngay);
		} else {
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
	}


	private function generateFeeLoanbyMonth($fee = array(), $typeProperty, $type_loan, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest = 30, $type_interest, $percent_advisory = "", $percent_expertise = "", $percent_interest_customer = "", $loan_infor_kdol = 1, $is_reduction_interest, $tien_giam_tru_bhkv, $down_interest_on_month)
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		//    $amount_money  tong tien
		//    $type_loan  hinh thuc vay
		//    $number_day_loan  tong so ngay vay
		//    $period_pay_interest   so ngay thuc te 1 ky
		//    $type_interest   hinh thuc tra lai


		// get thông tin phí vay
		$arr_return = array();
		$n = 0;

		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		$tong_lai_3ky = 0;
		if (empty($fee)) {
			$fee = $this->getFee($typeProperty, $type_loan, $number_day_loan, $loan_infor_kdol, $amount_money);
		}

		if (isset($fee['percent_advisory'])) {
			$pham_tram_phi_tu_van = floatval($fee['percent_advisory']) / 100;


			$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise']) / 100;


			$lai_suat_ndt = floatval($fee['percent_interest_customer']) / 100;
		}

		// if(!empty($percent_advisory))
		//  $pham_tram_phi_tu_van = floatval($percent_advisory)/100;
		// if(!empty($percent_expertise))
		//  $pham_tram_phi_tham_dinh = floatval($percent_expertise)/100;
		// if(!empty($percent_interest_customer))
		//  $lai_suat_ndt = floatval($percent_interest_customer)/100;
		// var_dump($type_loan,$disbursement_date, $amount_money, $number_day_loan, $period_pay_interest=30, $type_interest,$percent_advisory="",$percent_expertise="",$percent_interest_customer=""); die;
		$tien_goc = $amount_money;
		// số kỳ vay
		$so_ky_vay = (int)$number_day_loan / (int)$period_pay_interest;

		$lai_luy_ke = 0;
		$goc_lai_1_ky = 0;
		//Hinh thức lãi dư nợ giảm dần
		if ($type_interest == 1) {
			//tiền trả 1 kỳ pow(2, -3)
			$goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_goc);

			// tong tien phai tra 1 ky
			$tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
			//tiền trả 1 kỳ làm tròn
			$round_tien_tra_1_ky = round($tien_tra_1_ky);

			//gốc còn lại
			$tien_goc_con = $tien_goc;
			//tong cac loai phi
			//      $tong_phi_tu_van = 0;
			//      $tong_phi_tham_dinh  = 0;
			//khoan vay 1 ky
			for ($i = 1; $i <= $so_ky_vay; $i++) {
				//kỳ trả
				$date_ky_tra = $this->periodDays(date('Y-m-d', $disbursement_date), $i, $disbursement_date, $period_pay_interest)['date'];
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


					'ky_tra' => $ky_tra,
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

					"fee_delay" => 0,
					"fee_finish_contract" => 0,
					"fee_extend" => 0

				);
				$n++;
				$arr_return += [$n => $data_1ky];

			}
			return $arr_return;
		} else {

			//hình thức lãi hàng tháng, gốc cuối kỳ
			//khoan vay 1 ky
			for ($i = 1; $i <= $so_ky_vay; $i++) {
				//kỳ trả
				$date_ky_tra = $this->periodDays(date('Y-m-d', $disbursement_date), $i, $disbursement_date, $period_pay_interest)['date'];
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

					"fee_delay" => 0,
					"fee_finish_contract" => 0,
					"fee_extend" => 0

				);
				$n++;
				$arr_return += [$n => $data_1ky];

			}
			return $arr_return;
		}
	}

}

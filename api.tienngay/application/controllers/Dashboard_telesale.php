<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Dashboard_telesale extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('menu_model');
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_model');
		$this->load->model('mic_model');
		$this->load->model('gic_easy_model');
		$this->load->model('gic_plt_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_gic_model');
		$this->load->model('log_mic_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
		$this->load->model("notification_app_model");
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("sms_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('coupon_model');
		$this->load->model('verify_identify_contract_model');
		$this->load->model('device_model');
		$this->load->helper('lead_helper');
		$this->load->model('vbi_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('asset_management_model');
		$this->load->model('thongbao_model');
		$this->load->model('borrowed_model');
		$this->load->model('log_borrowed_model');
		$this->load->model('borrowed_noti_model');
		$this->load->model('file_return_model');
		$this->load->model('log_file_return_model');
		$this->load->model('log_sendfile_model');
		$this->load->model('log_fileManager_model');
		$this->load->model('file_manager_model');
		$this->load->model('email_template_model');
		$this->load->model('email_history_model');
		$this->load->model('group_role_thn_model');
		$this->load->model('recording_model');
		$this->load->model('kpi_thn_model');
		$this->load->model('contract_debt_caller_model');
		$this->load->model('contract_assign_debt_model');
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('debt_contract_model');
		$this->load->model('kpi_month_model');
		$this->load->model('kpi_thn_commission_model');
		$this->load->model('report_commission_thn_model');

		date_default_timezone_set('Asia/Ho_Chi_Minh');

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

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

	public function index_dashboard_telesale_post()
	{

		$condition = [];
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01");
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d");
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$data = [];
		//Lead theo trạng thái
		$condition['status_sale'] = ["1"];
		$status_moi = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["2"];
		$condition['office_at'] = 1;
		$status_dong_y_vay = $this->lead_model->get_count_lead_time($condition);
		unset($condition['office_at']);

		$condition['status_sale'] = ["5"];
		$status_dang_suy_nghi = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["6"];
		$status_dang_cho_duyet = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["8"];
		$status_da_ky_hop_dong = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["9"];
		$status_da_ra_pgd = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["10","11","12","13","14","15","16","17","18"];
		$status_cho_goi_lai = $this->lead_model->get_count_lead_time($condition);

		$condition['status_sale'] = ["19"];
		$status_huy = $this->lead_model->get_count_lead_time($condition);

		$data = [
			"Mới" => $status_moi,
			"Đồng ý vay" => $status_dong_y_vay,
			"Đang suy nghĩ" => $status_dang_suy_nghi,
			"Đang chờ duyệt" => $status_dang_cho_duyet,
			"Đã ký hợp đồng" => $status_da_ky_hop_dong,
			"Đã ra PGD" => $status_da_ra_pgd,
			"Chờ gọi lại" => $status_cho_goi_lai,
			"Hủy" => $status_huy,
		];
		$total_lead_status = $status_moi + $status_dong_y_vay + $status_dang_suy_nghi + $status_dang_cho_duyet + $status_da_ky_hop_dong + $status_da_ra_pgd + $status_cho_goi_lai + $status_huy;
		unset($condition['status_sale']);
		unset($condition['update_at']);

		//Tổng lead về
		$total_lead = $this->lead_model->get_count_lead_time($condition);

		//Tổng lead xử lý
		$condition['status_sale'] = ["2", "19", "3", "4", "5", "6", "7", "8", "9"];

		$total_lead_update = $this->lead_model->get_count_lead_time($condition);

		//Tổng lead QLF
		$condition['status_sale'] = ['2'];
		$condition['office_at'] = 1;
		$total_lead_qlf = $this->lead_model->get_count_lead_time($condition);

		//Tổng hợp đồng giải ngân, Số tiền giải ngân

		$condition_time = array(
			'$gte' => $condition['fdate'],
			'$lte' => $condition['tdate']
		);
		$count_hd_giaingan = $this->contract_model->count(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19]),'customer_infor.customer_resources' => ['$in' => ['1','2','3','4','5','6','7','12','14','15','16']] ,'disbursement_date' => $condition_time, 'loan_infor.type_property.code' => ['$in' => ['XM','OTO']] ,'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false)]);

		$price_hd_giaingan = $this->contract_model->sum_where_total(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19]),'customer_infor.customer_resources' => ['$in' => ['1','2','3','4','5','6','7','12','14','15','16']] ,'disbursement_date' => $condition_time, 'loan_infor.type_property.code' => ['$in' => ['XM','OTO']] ,'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false)], array('$toLong' => '$loan_infor.amount_money'));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'total_lead' => $total_lead,
			'total_lead_update' => $total_lead_update,
			'total_lead_qlf' => $total_lead_qlf,
			'count_hd_giaingan' => $count_hd_giaingan,
			'price_hd_giaingan' => $price_hd_giaingan,
			'data' => $data,
			'total_lead_status' => $total_lead_status
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function getGroupRole_telesale()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'telesales'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						if (!in_array($item['email'], $arr)) {
							array_push($arr, $item['email']);

						}

					}

				}
			}
		}
		return array_unique($arr);
	}

	public function table_telesale_post(){

		//Thông số nhân viên
		$telesale = [];
		$sort = [];
		$sort_convert = [];
		$count = 0;
		$list_telesale = $this->getGroupRole_telesale();
		if (!empty($list_telesale)){
			foreach ($list_telesale as $key => $value){

				if ($value == "ngochtm@tienngay.vn" || $value == "vbeecall@tienngay.vn" || $value == "maiht@tienngay.vn" || $value == "yenpth@tienngay.vn"){
					continue;
				}

				$condition = [];
				$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01");
				$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d");
				if (!empty($fdate)) {
					$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
				}
				if (!empty($tdate)) {
					$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
				}


				//Tên
				$telesale[$count]['name'] = $value;

				//Tổng lead xử lý
				$condition['status_sale'] = ["2", "19", "3", "4", "5", "6", "7", "8", "9"];
				$condition['cskh'] = $value;
				$telesale[$count]['lead_xu_ly'] = $this->lead_model->get_count_lead_time($condition);

				//Tổng lead QLF
				$condition['status_sale'] = ['2'];
				$condition['office_at'] = 1;
				$telesale[$count]['lead_qlf'] = $this->lead_model->get_count_lead_time($condition);
				$value_lead_qlf = $telesale[$count]['lead_qlf'];
				//Tỉ lệ
				$telesale[$count]['ti_le'] = ($telesale[$count]['lead_xu_ly'] != 0) ? number_format((($telesale[$count]['lead_qlf'] / $telesale[$count]['lead_xu_ly'])*100), 2) : 0.00;


				//Tổng hợp đồng giải ngân, Số tiền giải ngân
				$count_hd_giaingan = 0;
				$price_hd_giaingan = 0;
				$lead_hd_gn = $this->lead_model->get_data_lead_time($condition);

				if (!empty($lead_hd_gn)) {
					foreach ($lead_hd_gn as $item) {
						//Lấy thời gian trong 1 tháng
						$month = date('m', strtotime($fdate . ' 00:00:00'));
						$condition['fdate'] = strtotime(date("Y-$month-01 0:00:00" ));
						$condition['tdate'] = strtotime(date("Y-$month-d 23:59:59"));

						$contract = $this->contract_model->find_where_telesale($item['phone_number'], $condition);

						if (!empty($contract)) {
							if (in_array($contract[0]['status'], [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])) {
								$count_hd_giaingan++;
								$price_hd_giaingan += $contract[0]['loan_infor']['amount_money'];
							}
						} else {
							$array_name = $this->contract_model->find_where_telesale_name($item['fullname'], $condition);
							if (!empty($array_name)){
								if (in_array($array_name[0]['status'], [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])) {
									$count_hd_giaingan++;
									$price_hd_giaingan += $contract[0]['loan_infor']['amount_money'];
								}
							}
						}
					}
					$telesale[$count]['count_hd_giaingan'] = $count_hd_giaingan;
				}

				//Tiền hđ giải ngân từ lead qlf các tháng trc
				$price_giaingan = 0;
				$newdate = strtotime ( '-3 month' , strtotime ( date('Y-m-d 00:00:00', strtotime($fdate))) ) ;
				$condition['fdate'] = (int)(trim($newdate));
				$lead_hd_gn = $this->lead_model->get_data_lead_time($condition);

				if (!empty($lead_hd_gn)) {
					foreach ($lead_hd_gn as $item) {
						//Lấy thời gian trong 1 tháng
						$month = date('m', strtotime($fdate . ' 00:00:00'));
						$condition['fdate'] = strtotime(date("Y-$month-01 0:00:00"));
						$condition['tdate'] = strtotime(date("Y-$month-d 23:59:59"));

						$contract = $this->contract_model->find_where_telesale($item['phone_number'], $condition);

						if (!empty($contract)) {
							if (in_array($contract[0]['status'], [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])) {
								$price_giaingan += $contract[0]['loan_infor']['amount_money'];
							}
						} else {
							$array_name = $this->contract_model->find_where_telesale_name($item['fullname'], $condition);
							if (!empty($array_name)){
								if (in_array($array_name[0]['status'], [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])) {
									$price_giaingan += $array_name[0]['loan_infor']['amount_money'];
								}
							}
						}
					}
					$telesale[$count]['price_giaingan'] = $price_giaingan;
				}


				//Tỉ lệ convert
				$telesale[$count]['ti_le_convert'] = ($telesale[$count]['lead_qlf'] != 0) ? number_format((($telesale[$count]['count_hd_giaingan'] / $telesale[$count]['lead_qlf'])*100), 2) : 0.00;
				$ti_le_convert = $telesale[$count]['ti_le_convert'];


				unset($condition['office_at']);
				if ($value != "loanntp@tienngay.vn"){
					$sort += [$value => $value_lead_qlf];
					$sort_convert += [$value => $ti_le_convert];
				}

				$count++;


			}
		}

		asort($sort);
		asort($sort_convert);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $telesale,
			'sort' => $sort,
			'sort_convert' => $sort_convert
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function table_store_post(){

		//Thông số nhân viên
		$store = [];
		$sort_convert = [];
		$sort_price = [];
		$count = 0;
		$list_store = $this->store_model->find_where_sort(['status' => 'active', 'type_pgd' => "1"]);

		$pgd_unset = ["BPD01","Direct Sale AGN","Direct Sale BD","Direct Sale HCM 1","Direct Sale HCM 2","Direct Sale KGN","Direct Sale QN","Direct Sale TH","IT test","Priority"];

		if (!empty($list_store)){
			foreach ($list_store as $key => $value){

				if (in_array($value['name'],$pgd_unset)){
					continue;
				}

				$condition = [];
				$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01");
				$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d");
				if (!empty($fdate)) {
					$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
				}
				if (!empty($tdate)) {
					$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
				}

				//Tên
				$store[$count]['name'] = $value['name'];
				$store[$count]['code_area'] = $value['code_area'];


				//Tổng lead QLF
				$condition['status_sale'] = ['2'];
				$condition['office_at'] = 1;
				$condition['id_PDG'] = (string)$value['_id'];
				$store[$count]['lead_qlf'] = $this->lead_model->get_count_lead_time($condition);


				//Tổng hợp đồng giải ngân, Số tiền giải ngân
				//Tiền hđ giải ngân từ lead qlf các tháng trc

				$condition_time = array(
					'$gte' => $condition['fdate'],
					'$lte' => $condition['tdate']
				);

				$store[$count]['count_hd_giaingan'] = $this->contract_model->count(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19]),'customer_infor.customer_resources' => ['$in' => ['1','2','3','4','5','6','7','12','14','15','16']] ,'disbursement_date' => $condition_time, 'loan_infor.type_property.code' => ['$in' => ['XM','OTO']], 'store.id' => (string)$value['_id']  ,'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false)]);


				$store[$count]['price_giaingan'] = $this->contract_model->sum_where_total(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19]),'customer_infor.customer_resources' => ['$in' => ['1','2','3','4','5','6','7','12','14','15','16']] ,'disbursement_date' => $condition_time, 'loan_infor.type_property.code' => ['$in' => ['XM','OTO']], 'store.id' => (string)$value['_id']  ,'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false)], array('$toLong' => '$loan_infor.amount_money'));


				//Tỉ lệ convert
				$store[$count]['ti_le_convert'] = ($store[$count]['lead_qlf'] != 0) ? number_format((($store[$count]['count_hd_giaingan'] / $store[$count]['lead_qlf'])*100), 2) : 0;

				$sort_convert += [$value['name'] => $store[$count]['ti_le_convert']];
				$sort_price += [$value['name'] => $store[$count]['price_giaingan']];
				$count++;

			}
		}
		asort($sort_convert);
		asort($sort_price);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $store,
			'sort_convert' => $sort_convert,
			'sort_price' => $sort_price,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;



	}








}

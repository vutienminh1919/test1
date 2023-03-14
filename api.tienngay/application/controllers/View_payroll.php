<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class View_payroll extends REST_Controller
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
		$this->load->model('report_kpi_commission_pgd_model');
		$this->load->model('report_kpi_commission_user_model');
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


	public function get_count_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$month = !empty($this->dataPost['fdate_month']) ? date('m', strtotime($this->dataPost['fdate_month'])) : date('m');
		$year = !empty($this->dataPost['fdate_month']) ? date('Y', strtotime($this->dataPost['fdate_month'])) : date('Y');
		$email_user = !empty($this->dataPost['email_user']) ? $this->dataPost['email_user'] : "";
		$store_search = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

		$groupRoles = $this->getGroupRole($this->id);
		$stores = $this->getStores_list($this->id);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$flag = false;
		if (!empty($email_user) && $email_user != "") {
			$flag = true;
		}

		if ((in_array('giao-dich-vien', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)) || $flag == true) {
			$condition['created_by'] = $this->uemail;
			if ($flag == true){
				$condition['created_by'] = $email_user;
			}

			$count = $this->report_kpi_commission_user_model->getCountByRole($condition);

			if (empty($count)) {
				return;
			}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $count
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		}
		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$condition['store'] = array('$in'=>$stores);

			if (!empty($store_search) && $store_search != ""){
				$condition['store'] = array('$in'=>[$store_search]);
			}

			$count = $this->report_kpi_commission_pgd_model->getCountByRole($condition);

			if (empty($count)) {
				return;
			}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $count
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		}





	}

	public function get_all_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$month = !empty($this->dataPost['fdate_month']) ? date('m', strtotime($this->dataPost['fdate_month'])) : date('m');
		$year = !empty($this->dataPost['fdate_month']) ? date('Y', strtotime($this->dataPost['fdate_month'])) : date('Y');
		$email_user = !empty($this->dataPost['email_user']) ? $this->dataPost['email_user'] : "";
		$store_search = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";


		$groupRoles = $this->getGroupRole($this->id);
		$stores = $this->getStores_list($this->id);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$flag = false;
		if (!empty($email_user) && $email_user != "") {
			$flag = true;
		}


		if ((in_array('giao-dich-vien', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)) || $flag == true) {
			$condition['created_by'] = $this->uemail;
			if ($flag == true){
				$condition['created_by'] = $email_user;
			}

			$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
			$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

			$data = $this->report_kpi_commission_user_model->getDataByRole($condition , $per_page , $uriSegment);

			$rkcum = new Report_kpi_commission_user_model();
			$tong_tien_hoa_hong = $rkcum->sum_where_total_mongo_read(['user'=>$condition['created_by'], 'month' => $month,'year' => $year],'$commision.tien_hoa_hong');


			if (empty($data)) {
				return;
			}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $data,
				'tong_tien_hoa_hong' => $tong_tien_hoa_hong
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		}
		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$condition['store'] = array('$in'=>$stores);

			if (!empty($store_search) && $store_search != ""){
				$condition['store'] = array('$in'=>[$store_search]);
			}


			$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
			$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

			$data = $this->report_kpi_commission_pgd_model->getDataByRole($condition , $per_page , $uriSegment);

			if (empty($data)) {
				return;
			}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $data,

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;


		}





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

	private function getStores_list($userId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleStores = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['users']) && count($role['users']) > 0) {
					$arrUsers = array();
					foreach ($role['users'] as $item) {
						array_push($arrUsers, key($item));
					}
					//Check userId in list key of $users
					if (in_array($userId, $arrUsers) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['stores'] as $key => $item) {
								array_push($roleStores,key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}



}

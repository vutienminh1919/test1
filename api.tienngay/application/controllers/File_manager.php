<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class File_manager extends REST_Controller
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
		$this->load->model('borrow_paper_model');

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


	public function process_create_fileReturn_post()
	{
		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);

		$this->dataPost['taisandikem'] = $this->security->xss_clean($this->dataPost['taisandikem']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['stores'])));

		if ($check_area['code_area'] == "Priority" || $check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$this->dataPost["area"] = "MB";
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$this->dataPost["area"] = "MK";
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$this->dataPost["area"] = "MN";
			$user = $this->quan_ly_ho_so_mn();
		}


		//Validate
		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Danh sách hồ sơ không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		if (empty($this->dataPost['fileReturn_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_manager_model->find_where(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement_text']));
		foreach ($check_fileReturn as $key => $value) {
			if ($value['status'] != "2") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hợp đồng đã tạo yêu cầu gửi"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->file_manager_model->insertReturnId($this->dataPost);

		$log = array(
			"type" => "fileReturn",
			"action" => "CVKD tạo mới YC",
			"fileReturn_id" => (string)$contractId,
			"fileReturn" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
		$this->sendEmailApprove_qlhs($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$contractId,
					'action' => 'FileReturn_create',
					'note' => 'Mới',
					'user_id' => (string)$qlhs,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_all_sendFile_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$code_contract_disbursement_search = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($code_contract_disbursement_search)) {
			$condition['code_contract_disbursement_search'] = $code_contract_disbursement_search;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}

//		if (in_array($this->id, $this->quan_ly_ho_so_mb())) {
//			$condition['area'] = "MB";
//		}
//		if (in_array($this->id, $this->quan_ly_ho_so_mn())) {
//			$condition['area'] = "MN";
//		}
//		if (in_array($this->id, $this->quan_ly_ho_so_mekong())){
//			$condition['area'] = "MK";
//		}
		if (in_array('quan-ly-ho-so', $groupRoles)){

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;

		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$fileReturn = $this->file_manager_model->getDataByRole($condition, $per_page, $uriSegment);
		if (empty($fileReturn)) {
			return;
		}
		if (!empty($fileReturn)) {
			foreach ($fileReturn as $file) {
				$contractDB = $this->contract_model->findOne(["code_contract_disbursement" => $file['code_contract_disbursement_text']]);
				$file['type_contract'] = $contractDB['customer_infor']['type_contract_sign'] ? $contractDB['customer_infor']['type_contract_sign'] : '2';
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileReturn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_sendFile_tattoan_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$code_contract_disbursement_search = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($code_contract_disbursement_search)) {
			$condition['code_contract_disbursement_search'] = $code_contract_disbursement_search;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}

		if (in_array('quan-ly-ho-so', $groupRoles)){

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;

		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$fileReturn = $this->file_manager_model->getDataByRole_tattoan($condition, $per_page, $uriSegment);
		if (empty($fileReturn)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileReturn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_count_all_sendfile_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$code_contract_disbursement_search = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($code_contract_disbursement_search)) {
			$condition['code_contract_disbursement_search'] = $code_contract_disbursement_search;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}

		if (in_array($this->id, $this->quan_ly_ho_so_mb())) {
			$condition['area'] = "MB";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mn())) {
			$condition['area'] = "MN";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mekong())){
			$condition['area'] = "MK";
		}

		$sendFile_count = $this->file_manager_model->getCountByRole($condition);

		if (empty($sendFile_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $sendFile_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_count_all_tattoan_post()
	{
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$code_contract_disbursement_search = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($code_contract_disbursement_search)) {
			$condition['code_contract_disbursement_search'] = $code_contract_disbursement_search;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}

		if (in_array($this->id, $this->quan_ly_ho_so_mb())) {
			$condition['area'] = "MB";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mn())) {
			$condition['area'] = "MN";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mekong())){
			$condition['area'] = "MK";
		}

		$sendFile_count = $this->file_manager_model->getCountByRole_tat_toan($condition);

		if (empty($sendFile_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $sendFile_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_one_post()
	{

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$file_manager = $this->file_manager_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));

		if (!empty($file_manager)){
			$file_pdf = $this->contract_model->findOne(['code_contract_disbursement' => $file_manager['code_contract_disbursement_text']]);
			if (!empty($file_pdf)){
				$file_manager['file_pdf'] = $file_pdf['image_accurecy']['digital'];
			}
		}

		if (empty($file_manager)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $file_manager,
		);

		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function cancel_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "2";
		$log = array(
			"type" => "fileReturn",
			"action" => "Hủy",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" || $check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "2";

		$this->sendEmailApprove_qlhs($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Hủy yêu cầu',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 2,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "2"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function get_one_fileReturn_post()
	{

		$data = $this->input->post();
		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
		if (empty($fileReturn)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileReturn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function process_update_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);

		$this->dataPost['taisandikem'] = $this->security->xss_clean($this->dataPost['taisandikem']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);


		//Validate
		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Danh sách hồ sơ không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (empty($this->dataPost['fileReturn_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "fileReturn",
			"action" => "Sửa",
			"fileReturn_id" => $this->dataPost['id'],
			"fileReturn" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $check_fileReturn['_id']), ["file" => $this->dataPost['file'], "giay_to_khac" => $this->dataPost['giay_to_khac'], "taisandikem" => $this->dataPost['taisandikem'], "ghichu" => $this->dataPost['ghichu'], "fileReturn_img" => $this->dataPost['fileReturn_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function send_file_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "3";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "3";
		$this->sendEmailApprove_qlhs($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'YC gửi HS giải ngân',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 3,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "3"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function sendEmailApprove_borrowed($fileReturn, $user_qlhs)
	{
		$status_text = "";
		$id = $fileReturn['_id'];

		if ($fileReturn['status'] == "1") {
			$status_text = "Mới";
		} elseif ($fileReturn['status'] == "2") {
			$status_text = "Hủy yêu cầu";
		} elseif ($fileReturn['status'] == "3") {
			$status_text = "PGD YC mượn HS giải ngân";
		} elseif ($fileReturn['status'] == "4") {
			$status_text = "Yêu cầu mượn HS";
		} elseif ($fileReturn['status'] == "5") {
			$status_text = "QLHS trả về yêu cầu mượn";
		} elseif ($fileReturn['status'] == "6") {
			$status_text = "Chờ nhận hồ sơ";
		} elseif ($fileReturn['status'] == "7") {
			$status_text = "Đang mượn hồ sơ";
		} elseif ($fileReturn['status'] == "8") {
			$status_text = "Chưa nhận đủ HS mượn";
		} elseif ($fileReturn['status'] == "9") {
			$status_text = "Trả HS mượn về HO";
		} elseif ($fileReturn['status'] == "10") {
			$status_text = "Lưu kho";
		} elseif ($fileReturn['status'] == "11") {
			$status_text = "Chưa trả đủ HS đã mượn";
		} elseif ($fileReturn['status'] == "12") {
			$status_text = "Quá hạn mượn HS";
		} elseif ($fileReturn['status'] == "13") {
			$status_text = "Trả hồ sơ cho KH tất toán";
		} elseif ($fileReturn['status'] == "14") {
			$status_text = "QLHS xác nhận KH đã tất toán";
		}

		$data = array(
			'code' => "vfc_send_email_qlhs",
			'code_contract_disbursement' => $fileReturn['code_contract_disbursement_text'],
			'status' => $status_text,
			'url' => "https://lms.tienngay.vn/file_manager/detail_borrowed?id=$id"
		);

		foreach ($user_qlhs as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data['email'] = "$value";
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmail($data);
			}

		}
		return;
	}


	private function sendEmailApprove_qlhs($fileReturn, $user_qlhs)
	{
		$status_text = "";
		$id = $fileReturn['_id'];

		if ($fileReturn['status'] == "1") {
			$status_text = "Mới";
		} elseif ($fileReturn['status'] == "2") {
			$status_text = "Hủy yêu cầu";
		} elseif ($fileReturn['status'] == "3") {
			$status_text = "YC gửi HS giải ngân";
		} elseif ($fileReturn['status'] == "4") {
			$status_text = "QLHS YC bổ sung";
		} elseif ($fileReturn['status'] == "5") {
			$status_text = "Đã XN YC gửi HS";
		} elseif ($fileReturn['status'] == "6") {
			$status_text = "Hoàn tất lưu kho";
		} elseif ($fileReturn['status'] == "7") {
			$status_text = "QLHS chưa nhận HS";
		} elseif ($fileReturn['status'] == "8") {
			$status_text = "YC trả HS sau tất toán";
		} elseif ($fileReturn['status'] == "9") {
			$status_text = "QLHS đã xác nhận YC trả HS";
		} elseif ($fileReturn['status'] == "10") {
			$status_text = "YC bổ sung HS";
		} elseif ($fileReturn['status'] == "11") {
			$status_text = "Đã trả HS sau tất toán";
		} elseif ($fileReturn['status'] == "13") {
			$status_text = "Trả về yêu cầu";
		}
		$data = array(
			'code' => "vfc_send_email_qlhs",
			'code_contract_disbursement' => $fileReturn['code_contract_disbursement_text'],
			'status' => $status_text,
			'url' => "https://lms.tienngay.vn/file_manager/detail?id=$id"
		);

		foreach ($user_qlhs as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data['email'] = "$value";
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmail($data);
			}

		}
		return;
	}

	private function sendEmailBorrow_qlhs($fileReturn, $user_qlhs)
	{
		$status_text = "";
		$id = $fileReturn['_id'];

		if ($fileReturn['status'] == "1") {
			$status_text = "Mới";
		} elseif ($fileReturn['status'] == "2") {
			$status_text = "Xác nhận yêu cầu mượn giấy đi đường";
		} elseif ($fileReturn['status'] == "3") {
			$status_text = "Hủy";
		}
		$data = array(
			'code' => "vfc_email_borrow_paper",
			'code_contract_disbursement' => $fileReturn['code_contract_disbursement_value'],
			'status' => $status_text,
			'url' => "https://lms.tienngay.vn/file_manager/borrow_travel_paper",

		);

		foreach ($user_qlhs as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data['email'] = "$value";
				$data['email_show'] = "$value";
				$data['API_KEY'] = $this->config->item('API_KEY');
//				$this->user_model->send_Email($data);
				$this->sendEmail($data);
			}

		}
		return;
	}



	public function bosunghoso_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu_qlhs'] = $this->security->xss_clean($this->dataPost['ghichu_qlhs']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "4";
		$log = array(
			"type" => "fileReturn",
			"action" => "Trả về",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$user_gdv = array($fileReturn['created_by']['id']);
		$fileReturn['status'] = "4";
		$this->sendEmailApprove_qlhs($fileReturn, $user_gdv);

		if (!empty($user_gdv)) {
			foreach (array_unique($user_gdv) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'QLHS YC bổ sung',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 4,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "4", "ghichu_qlhs" => $this->dataPost['ghichu_qlhs']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Return borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function guibosunghoso_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);

		$this->dataPost['taisandikem'] = $this->security->xss_clean($this->dataPost['taisandikem']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);

		//Validate
		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "File trả không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (empty($this->dataPost['fileReturn_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "3";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($check_fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$check_fileReturn['status'] = "3";
		$this->sendEmailApprove_qlhs($check_fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'CVKD gửi HS bổ sung',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 3,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $check_fileReturn['_id']), array("status" => "3", "file" => $this->dataPost['file'], "giay_to_khac" => $this->dataPost['giay_to_khac'], "taisandikem" => $this->dataPost['taisandikem'], "ghichu" => $this->dataPost['ghichu'], "fileReturn_img" => $this->dataPost['fileReturn_img']));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function approve_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "5";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$fileReturn['status'] = "5";
		$user = array($fileReturn['created_by']['id']);
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Đã XN YC gửi HS',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 5,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "5", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function save_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "6";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);
		$fileReturn['status'] = "6";
		$user = array($fileReturn['created_by']['id']);
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Hoàn tất lưu kho',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 6,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "6", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve save success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function not_received_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "7";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);
		$fileReturn['status'] = "7";
		$user = array($fileReturn['created_by']['id']);
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'QLHS chưa nhận HS',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 7,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "7", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function return_file_v2_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$check_tt = $this->contract_model->findOne(array("code_contract_disbursement" => $fileReturn['code_contract_disbursement_text']));

//		if ($check_tt['status'] != 19) {
//			$response = array(
//				'status' => REST_Controller::HTTP_UNAUTHORIZED,
//				'message' => "Hợp đồng chưa được tất toán"
//
//			);
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
//		}

		$this->dataPost['status'] = "8";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "8";
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'YC trả HS sau tất toán',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 8,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "8", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function quan_ly_ho_so_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function asm_hn1()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "asm-hn1")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function asm_hn2()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "asm-hn2")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function asm_hcm1()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "asm-hcm1")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function asm_hcm2()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "asm-hcm2")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {
						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function asm_mekong()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "asm-mekong")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}


	public function quan_ly_ho_so_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function quan_ly_ho_so_mekong()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "qlhs-mekong")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	private function getGroupRole_asm()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'quan-ly-khu-vuc'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}

	private function getGroupRole_gdv()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'giao-dich-vien'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}

	public function return_v2_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);

		$this->dataPost['fileReturn_img_v2'] = $this->security->xss_clean($this->dataPost['fileReturn_img_v2']);

		//Validate
		if (empty($this->dataPost['fileReturn_img_v2'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "9";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);
		$check_fileReturn['status'] = "9";
		$user = array($check_fileReturn['created_by']['id']);
		$this->sendEmailApprove_qlhs($check_fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'QLHS đã xác nhận YC trả HS',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 9,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $check_fileReturn['_id']), array("status" => "9", "ghichu" => $this->dataPost['ghichu'], "fileReturn_img_v2" => $this->dataPost['fileReturn_img_v2']));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function cvkd_ycbs_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);

		$this->dataPost['taisandikem'] = $this->security->xss_clean($this->dataPost['taisandikem']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);

		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);

		//Validate
		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "File trả không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (empty($this->dataPost['fileReturn_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "10";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($check_fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$check_fileReturn['status'] = "10";
		$this->sendEmailApprove_qlhs($check_fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'YC bổ sung HS',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 10,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $check_fileReturn['_id']), array("status" => "10", "file_v2" => $this->dataPost['file'], "giay_to_khac_v2" => $this->dataPost['giay_to_khac'], "taisandikem_v2" => $this->dataPost['taisandikem'], "ghichu" => $this->dataPost['ghichu'], "fileReturn_img_v2" => $this->dataPost['fileReturn_img']));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function trahososautattoan_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "11";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "11";
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Đã trả HS sau tất toán',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 11,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "11", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function traveyeucautattoan_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "13";
		$log = array(
			"type" => "fileReturn",
			"action" => "Xác nhận",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_fileManager_model->insert($log);


		$user = array($fileReturn['created_by']['id']);
		$fileReturn['status'] = "13";
		$this->sendEmailApprove_qlhs($fileReturn, $user);
		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Trả về yêu cầu',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 13,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->file_manager_model->update(array("_id" => $fileReturn['_id']), ["status" => "13", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_log_one_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileManager = $this->log_fileManager_model->find_where(array("fileReturn_id" => $this->dataPost['id']));

		if (empty($fileManager)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileManager
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

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


	private function getGroupRole_email($userId)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($arr, $item[key($item)]['email']);
					continue;
				}
			}
		}
		return array_unique($arr);
	}

	private function getStores($userId)
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
								array_push($roleStores, key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}


	private function getUserbyStores_email($storeId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['stores']) && count($role['stores']) == 1) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if (in_array($storeId, $arrStores) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['users'] as $key => $item) {
								array_push($roleAllUsers, $item[key($item)]['email']);
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}


	public function check_file_manager_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		$condition = [];
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}

		$borrowed = $this->file_manager_model->where_in_status($condition);
		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}

	public function process_create_borrowed_post()
	{

		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);
		$this->dataPost['borrowed_start'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_start']));
		$this->dataPost['borrowed_end'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_end']));
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['groupRoles_store'] = $this->security->xss_clean($this->dataPost['groupRoles_store']);
		$this->dataPost['lydomuon'] = $this->security->xss_clean($this->dataPost['lydomuon']);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['stores'])));
		$this->dataPost['area'] = $check_area['code_area'];

		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}


		//Validate
		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

//		$check_borrowed = $this->borrowed_model->find_where(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement_text']));
//
//		if (!empty($check_borrowed)) {
//			if ($check_borrowed[0]->status != "10" && ($check_borrowed[0]->status != "2")) {
//				$response = array(
//					'status' => REST_Controller::HTTP_UNAUTHORIZED,
//					'message' => "Hồ sơ của hợp đồng đang được mượn"
//				);
//				$this->set_response($response, REST_Controller::HTTP_OK);
//				return;
//			}
//		}

		if (empty($this->dataPost['lydomuon'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Lý do mượn không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Danh sách hồ sơ không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		if (empty($this->dataPost['borrowed_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian bắt đầu mượn không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['borrowed_end'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian trả không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['borrowed_start'] > $this->dataPost['borrowed_end']) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian trả phải lớn hơn thời gian mượn"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['borrowed_start'] == $this->dataPost['borrowed_end']) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian mượn và thời gian trả không trùng nhau"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_borrowed = $this->dataPost['borrowed_end'] - $this->dataPost['borrowed_start'];
		$years = floor($check_borrowed / (365 * 60 * 60 * 24));
		$months = floor(($check_borrowed - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
		$days = floor(($check_borrowed - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

		if ($days > 15 || $months >= 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian mượn không được quá 15 ngày"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->borrowed_model->insertReturnId($this->dataPost);

		$log = array(
			"type" => "borrowed",
			"action" => "Tạo YC mượn HS",
			"borrowed_id" => (string)$contractId,
			"borrowed" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$contractId)));
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$contractId,
					'action' => 'Borrowed_create',
					'note' => 'Mới',
					'user_id' => (string)$qlhs,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_count_all_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$groupRoles_store_search = !empty($this->dataPost['groupRoles_store_search']) ? $this->dataPost['groupRoles_store_search'] : "";

		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($groupRoles_store_search)) {
			$condition['groupRoles_store_search'] = $groupRoles_store_search;
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = true;
		if (in_array('quan-ly-ho-so', $groupRoles)) {
			$all = false;
		}


		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;
			$condition['groupRoles_store'] = "Cửa hàng trưởng";

		} elseif (in_array('quan-ly-ho-so', $groupRoles)){

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;

		} else {
			if ($all) {
				$condition['created_by'] = $this->uemail;

				if ($this->uemail == "sangnv@tienngay.vn" || $this->uemail == "vulq@tienngay.vn"){
					$condition['created_by'] = "1";
				}

			}
		}

		$borrowed_count = $this->borrowed_model->getCountByRole($condition);

		if (empty($borrowed_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$groupRoles_store_search = !empty($this->dataPost['groupRoles_store_search']) ? $this->dataPost['groupRoles_store_search'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status)) {
			$condition['status'] = (string)$status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($groupRoles_store_search)) {
			$condition['groupRoles_store_search'] = $groupRoles_store_search;
		}

		$groupRoles = $this->getGroupRole($this->id);

		$all = true;
		if (in_array('quan-ly-ho-so', $groupRoles) || in_array('quan-ly-cap-cao', $groupRoles)) {
			$all = false;
		}


		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ky-khu-vuc', $groupRoles)) {

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;
			$condition['groupRoles_store'] = "Cửa hàng trưởng";

		} elseif (in_array('quan-ly-ho-so', $groupRoles)){

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;

		} else {
			if ($all) {
				$condition['created_by'] = $this->uemail;

				if ($this->uemail == "sangnv@tienngay.vn" || $this->uemail == "vulq@tienngay.vn"){
					$condition['created_by'] = "1";
				}

			}
		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;


		$borrowed = $this->borrowed_model->getDataByRole($condition, $per_page, $uriSegment);

		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_checkquahan_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$borrowed = $this->borrowed_model->getDataByRole_quahan($condition, $per_page, $uriSegment);

		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_count_all_quahan_post()
	{
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$borrowed_count = $this->borrowed_model->getCountByRole_quahan($condition);

		if (empty($borrowed_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_log_one_borrowed_post()
	{
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileManager = $this->log_borrowed_model->find_where(array("borrowed_id" => $this->dataPost['id']));

		if (empty($fileManager)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileManager
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_borrowed_post()
	{

		$data = $this->input->post();

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));

		if (empty($borrowed)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function cancel_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "2";
		$log = array(
			"type" => "fileReturn",
			"action" => "Hủy",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user_qlhs = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user_qlhs = $this->quan_ly_ho_so_mekong();
		} else {
			$user_qlhs = $this->quan_ly_ho_so_mn();
		}
		$user_send = array($fileReturn['created_by']['id']);

		$user = array_merge($user_send, $user_qlhs);
		$fileReturn['status'] = "2";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Hủy yêu cầu',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 2,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "2"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['borrowed_start'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_start']));
		$this->dataPost['borrowed_end'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_end']));
		$this->dataPost['groupRoles_store'] = $this->security->xss_clean($this->dataPost['groupRoles_store']);
		$this->dataPost['lydomuon'] = $this->security->xss_clean($this->dataPost['lydomuon']);

		//Validate
		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

//		$check_borrowed = $this->borrowed_model->find_where(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement_text']));
//
//		if (!empty($check_borrowed)) {
//			if ($check_borrowed[0]->status != "10" && ($check_borrowed[0]->status != "2")) {
//				$response = array(
//					'status' => REST_Controller::HTTP_UNAUTHORIZED,
//					'message' => "Hồ sơ của hợp đồng đang được mượn"
//				);
//				$this->set_response($response, REST_Controller::HTTP_OK);
//				return;
//			}
//		}

		if (empty($this->dataPost['lydomuon'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Lý do mượn không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Danh sách hồ sơ không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		if (empty($this->dataPost['borrowed_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian bắt đầu mượn không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['borrowed_end'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian trả không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['borrowed_start'] > $this->dataPost['borrowed_end']) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian trả phải lớn hơn thời gian mượn"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($this->dataPost['borrowed_start'] == $this->dataPost['borrowed_end']) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian mượn và thời gian trả không trùng nhau"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_borrowed = $this->dataPost['borrowed_end'] - $this->dataPost['borrowed_start'];
		$years = floor($check_borrowed / (365 * 60 * 60 * 24));
		$months = floor(($check_borrowed - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
		$days = floor(($check_borrowed - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

		if ($days > 15 || $months >= 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian mượn không được quá 15 ngày"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "borrowed",
			"action" => "Sửa",
			"borrowed_id" => $this->dataPost['id'],
			"borrowed" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $check_fileReturn['_id']), ["file" => $this->dataPost['file'], "giay_to_khac" => $this->dataPost['giay_to_khac'], "ghichu" => $this->dataPost['ghichu'], "borrowed_start" => $this->dataPost['borrowed_start'], "borrowed_end" => $this->dataPost['borrowed_end'], "groupRoles_store" => $this->dataPost['groupRoles_store'], "lydomuon" => $this->dataPost['lydomuon']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function asm_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "3";
		$log = array(
			"type" => "borrowed",
			"action" => "Hủy",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		if ($fileReturn['area'] == "KV_HN1" || $fileReturn['area'] == "KV_MT1") {
			$user = $this->asm_hn1();
		} elseif ($fileReturn['area'] == "KV_HN2" || $fileReturn['area'] == "KV_QN") {
			$user = $this->asm_hn2();
		} elseif ($fileReturn['area'] == "KV_HCM1") {
			$user = $this->asm_hcm1();
		} elseif ($fileReturn['area'] == "KV_HCM2") {
			$user = $this->asm_hcm2();
		} elseif ($fileReturn['area'] == "KV_MK") {
			$user = $this->asm_mekong();
		}
		$fileReturn['status'] = "3";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'PGD YC mượn HS giải ngân',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 3,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "3"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function qlhs_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "4";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "4";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Yêu cầu mượn HS',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 4,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "4", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function qlhs_trahoso_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);


		//Validate
		if (empty($this->dataPost['ghichu'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "5";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_borrowed_model->insert($log);


		$user = array($check_fileReturn['created_by']['id']);
		$check_fileReturn['status'] = "5";
		$this->sendEmailApprove_borrowed($check_fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'QLHS trả về yêu cầu mượn',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 5,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "5", "ghichu" => $this->dataPost['ghichu']]);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileApprove_img'] = $this->security->xss_clean($this->dataPost['fileApprove_img']);

		//Validate
		if (empty($this->dataPost['fileApprove_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh hồ sơ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "6";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_borrowed_model->insert($log);

		$user = array($check_fileReturn['created_by']['id']);
		$check_fileReturn['status'] = "6";
		$this->sendEmailApprove_borrowed($check_fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Chờ nhận hồ sơ',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 6,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "6", "ghichu" => $this->dataPost['ghichu'], "fileApprove_img" => $this->dataPost['fileApprove_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function borrowed_danhanhoso_post()
	{
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "7";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "7";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Đang mượn hồ sơ',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 7,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "7", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function borrowed_trahskhachhangtattoan_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		//Validate
		if (empty($this->dataPost['ghichu'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['file_img_approve'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh hồ sơ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "13";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "13";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Trả HS khách hàng tất toán',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 13,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "13", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function borrowed_xacnhankhdatattoan_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "14";

		$fileReturn_log = $this->file_manager_model->find_where(array("code_contract_disbursement_text" => $fileReturn['code_contract_disbursement_text']));

		//KH đã tất toán
		if (!empty($fileReturn_log)){
			$this->file_manager_model->update(array("_id" => new MongoDB\BSON\ObjectId((string)$fileReturn_log[0]['_id'])), ["status" => "11"]);
			$log_tt = array(
				"type" => "fileReturn",
				"action" => "Xác nhận",
				"fileReturn_id" => (string)$fileReturn_log[0]['_id'],
				"old" => $fileReturn_log[0],
				"new" => ["status" => "11", "ghichu" => "(QLHS xác nhận KH đã tất toán)"],
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->log_fileManager_model->insert($log_tt);
		}


		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user = array($fileReturn['created_by']['id']);
		$fileReturn['status'] = "14";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Xác nhận khách hàng đã tất toán',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 14,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "14", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function return_borrowed_post()
	{
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);

		//Validate
		if (empty($this->dataPost['ghichu'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['fileReturn_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh hồ sơ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "8";
		$log = array(
			"type" => "borrowed",
			"action" => "Chưa nhận đủ hồ sơ",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($check_fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$check_fileReturn['status'] = "8";
		$this->sendEmailApprove_borrowed($check_fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Chưa nhận đủ HS mượn',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 8,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "8", "ghichu" => $this->dataPost['ghichu'], "fileReturn_img" => $this->dataPost['fileReturn_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function borrowed_trahsdamuon_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "9";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));
		if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
			$user = $this->quan_ly_ho_so_mb();
		} else if ($check_area['code_area'] == "KV_MK") {
			$user = $this->quan_ly_ho_so_mekong();
		} else {
			$user = $this->quan_ly_ho_so_mn();
		}
		$fileReturn['status'] = "9";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Trả HS mượn về HO',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 9,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "9", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function borrowed_luukho_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['file_img_approve'] = $this->security->xss_clean($this->dataPost['file_img_approve']);

		$fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$this->dataPost['status'] = "10";
		$log = array(
			"type" => "borrowed",
			"action" => "Xác nhận",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user = array($fileReturn['created_by']['id']);
		$fileReturn['status'] = "10";
		$this->sendEmailApprove_borrowed($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Lưu kho',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 10,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $fileReturn['_id']), ["status" => "10", "ghichu" => $this->dataPost['ghichu'], "file_img_approve" => $this->dataPost['file_img_approve']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function chua_tra_hs_da_muon_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['ghichu'] = $this->security->xss_clean($this->dataPost['ghichu']);
		$this->dataPost['fileReturn_qlhs_img'] = $this->security->xss_clean($this->dataPost['fileReturn_qlhs_img']);

		//Validate
		if (empty($this->dataPost['ghichu'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['fileReturn_qlhs_img'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ảnh hồ sơ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['status'] = "11";
		$log = array(
			"type" => "borrowed",
			"action" => "Chưa nhận đủ hồ sơ trả",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_borrowed_model->insert($log);

		$user = array($check_fileReturn['created_by']['id']);
		$check_fileReturn['status'] = "11";
		$this->sendEmailApprove_borrowed($check_fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_cancel',
					'note' => 'Chưa trả đủ HS đã mượn',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 11,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "11", "ghichu" => $this->dataPost['ghichu'], "fileReturn_qlhs_img" => $this->dataPost['fileReturn_qlhs_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function sendEmail($dataPost)
	{
		$email_template = $this->email_template_model->findOne(array('code' => $dataPost['code'], 'status' => 'active'));

		$domain = $this->config->item('sendgrid_domain');
		// var_dump($email_template); die;

		$from = 'support@tienngay.vn';
		$from_name = $email_template['from_name'];
		$subject = $email_template['subject'];
		$message = $this->getEmailStr($email_template['message'], $dataPost);
		$status = 'active';
		$data = array(
			"code" => $dataPost['code'],
			"from" => $from,
			"from_name" => $from_name,
			"to" => $dataPost['email'],
			"subject" => $subject,
			"email_domain" => $domain,
			"status" => $status,
			"message" => $message,
//			"device" => $this->agent->browser() . ';' . $this->agent->platform(),
//			"ipaddress" => getIpAddress(),
			"created_at" => (int)$this->createdAt
		);

		//var_dump('expression');

		$this->email_history_model->insert($data);
		return;


	}

	public function send($from, $to, $subject, $message, $from_name)
	{

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($from, $from_name);
		$email->setSubject($subject);
		$email->addTo($to, "");
		$email->addContent(
			"text/html", $message
		);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
			// print $response->statusCode() . "\n";
			//    print_r($response->headers());
			//    print $response->body() . "\n";
			if ($response->statusCode() == '202') {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}


	}

	public function getGroupRole_hcns()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'quan-ly-ho-so'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}

	public function getEmailStr($emailTemplate, $filter)
	{
		foreach ($filter as $key => $value) {
			$emailTemplate = str_replace("{" . $key . "}", $value, $emailTemplate);
		}
		return $emailTemplate;
	}

	public function check_file_post()
	{

		$this->dataPost['code_contract_disbursement'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement']);

		$check_area = $this->borrowed_model->findOne(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement']));

		if (empty($check_area)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			"data" => $check_area
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function cron_update_hopdongtra_qlhs_post()
	{
		$condition = [];

		$start = strtotime('-15 day', strtotime(date('Y-m-d', (int)$this->createdAt)));

		$start1 = date('Y-m-d', $start);

		if (!empty($start1)) {
			$condition = array(
				'start' => strtotime(trim($start1)),

			);
		}

//		$contractData = $this->contract_model->find_where_mhd_cron($condition,$limit=10,$offset = 0);
		$contractData = $this->contract_model->find_where_mhd_cron($condition);

		if (!empty($contractData)) {
			foreach ($contractData as $value) {
				$data = [];
				$check_contract = $this->file_manager_model->findOne(["code_contract_disbursement_text" => $value['code_contract_disbursement']]);
				if (empty($check_contract)) {

					$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($value['store']->id)));


					if ($check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
						$area = "MB";
					} else if ($check_area['code_area'] == "KV_MK") {
						$area = "MK";
					} else {
						$area = "MN";
					}

					$data = [
						"code_contract_disbursement_text" => $value['code_contract_disbursement'],
						"file" => [
							"Thỏa thuận 3 bên",
							"Văn bản bàn giao tài sản",
							"Thông báo",
							"Đăng ký xe/Cà vẹt",
							"Hợp đồng mua bán",
							"Đăng kiểm",
							"Giấy cam kết",
							"Ủy quyền",
							"Chìa khóa",
							"Sổ đỏ"
						],
						"giay_to_khac" => "",
						"taisandikem" => "",
						"ghichu" => "",
						"fileReturn_img" => "",
						"stores" => $value['store']->id,
						"area" => $area,
						"file_img_approve" => "",
						"fileReturn_img_v2" => "",
						"created_at" => $this->createdAt,
						"status" => "6",
						"created_by" => "cron_admin"
					];
					$this->file_manager_model->insert($data);
				}

			}
		}
		echo "okei";

	}

	public function cron_store_file_manager_post(){
		//Cập nhật store hồ sơ
		$list_file = $this->file_manager_model->find();

		if (!empty($list_file)) {

			foreach ($list_file as $value) {

				if (!empty($value['code_contract_disbursement_text'])){

					$check_store_new = $this->contract_model->findOne_storeId(['code_contract_disbursement'=> $value['code_contract_disbursement_text']]);

					if (!empty($check_store_new['store']['id'])){

						$this->file_manager_model->update(["_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])], ['stores' => $check_store_new['store']['id']]);

						//Mekong -- 5f87c5acd6612bd45a08fc62(308 Đường 30/4) -- 5f6ac65cd6612b2e6c4a7db3(1797 Trần Hưng Đạo) -- 5f6ac5acd6612b295d77fd54(63 Đường 26 tháng 3)
//						if ($check_store_new['store']['id'] == "5f87c5acd6612bd45a08fc62" || $check_store_new['store']['id'] == "5f6ac65cd6612b2e6c4a7db3" || $check_store_new['store']['id'] == "5f6ac5acd6612b295d77fd54"){
//							$this->file_manager_model->update(["_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])], ['area' => "MK"]);
//						}
						//Direct Sale BD -- 6176264c9bf0aa68cd55c404
						if ($check_store_new['store']['id'] == "61945bd9b5987f1710347a65"){
							$this->file_manager_model->update(["_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])], ['area' => "MB"]);
						}
					}
				}

			}
		}
		echo "cron_ok";
	}

	public function cron_store_borrowed_post(){
		//Cập nhật store mượn/trả hồ sơ
		$list_borrowed = $this->borrowed_model->find();

		if (!empty($list_borrowed)){

			foreach ($list_borrowed as $value){

				$check_store_new = $this->contract_model->findOne_storeId(['code_contract_disbursement'=> $value['code_contract_disbursement_text']]);

				if (!empty($check_store_new['store']['id'])){

					$this->borrowed_model->update(["_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])], ['stores' => $check_store_new['store']['id']]);

				}
			}
		}
		echo "cron_ok";
	}

	public function get_store_status_active_new_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;


		if (in_array($this->id, $this->quan_ly_ho_so_mb())) {
			$condition['area'] = "MB";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mn())) {
			$condition['area'] = "MN";
		}
		if (in_array($this->id, $this->quan_ly_ho_so_mekong())) {
			$condition['area'] = "MK";
		}

		$store = $this->store_model->find_where_in_new($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $store
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
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

	public function muontrahoso_excel_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status)) {
			$condition['status'] = (string)$status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$groupRoles = $this->getGroupRole($this->id);

		$all = true;
		if (in_array('quan-ly-ho-so', $groupRoles) || in_array('quan-ly-cap-cao', $groupRoles)) {
			$all = false;
		}


		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;
			$condition['groupRoles_store'] = "Cửa hàng trưởng";

		}elseif (in_array('quan-ly-ho-so', $groupRoles)){

			$stores_list = $this->getStores_list($this->id);
			$condition['stores_list'] = $stores_list;

		}
		else {
			if ($all) {
				$condition['created_by'] = $this->uemail;

				if ($this->uemail == "sangnv@tienngay.vn" || $this->uemail == "vulq@tienngay.vn"){
					$condition['created_by'] = "1";
				}

			}
		}

		$borrowed = $this->borrowed_model->getDataByRole_excel($condition);

		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;



	}


	public function get_all_contract_luukho_post(){

		$stores = $this->getStores_list($this->id);

		$contractData = $this->file_manager_model->find_where_select(['status' => '6', 'stores' => ['$in' => $stores]], ['code_contract_disbursement_text']);

		if (empty($contractData)){
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}

	public function process_create_borrow_travel_paper_post(){

		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['fileReturn'] = $this->security->xss_clean($this->dataPost['fileReturn']);

		//Validate
		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['fileReturn'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "File upload không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$storeData = $this->contract_model->find_one_select(['code_contract_disbursement' => $this->dataPost['code_contract_disbursement_value']],['store','customer_infor.customer_name']);

		if (!empty($storeData)){
			$this->dataPost['store'] = $storeData;
			$this->dataPost['customer_name'] = $storeData['customer_infor']['customer_name'];

		}

		$user = $this->quan_ly_ho_so_mb();

		$this->dataPost['created_at'] = $this->createdAt;
		$this->dataPost['created_by'] = $this->uemail;
		$this->dataPost['user_id'] = $this->id;
		//1 - Yêu cầu mượn giấy đi đường, 2 - Xác nhận gửi giấy đi đường, 3 - Hủy
		$this->dataPost['status'] = 1;

		$check_borrow_paper = $this->borrow_paper_model->findOne(['code_contract_disbursement_value' => $this->dataPost['code_contract_disbursement_value'],'status' => ['$in' => [1,2]]]);

		if (!empty($check_borrow_paper)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Đã mượn giấy đi đường"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$id_borrow_paper = $this->borrow_paper_model->insertReturnId($this->dataPost);
		}

		$fileReturn = $this->borrow_paper_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$id_borrow_paper)));
		$this->sendEmailBorrow_qlhs($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$id_borrow_paper,
					'action' => 'borrow_travel_paper',
					'note' => 'Gửi YC cấp giấy đi đường',
					'user_id' => (string)$qlhs,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_all_borrow_paper_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status)) {
			$condition['status'] = (string)$status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$groupRoles = $this->getGroupRole($this->id);
		$stores_list = $this->getStores_list($this->id);

		if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['stores_list'] = $stores_list;
		}

		if (in_array('cua-hang-truong', $groupRoles)) {
			$condition['stores_list'] = $stores_list;
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$borrowed = $this->borrow_paper_model->getDataByRole($condition, $per_page, $uriSegment);

		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;



	}

	public function get_count_borrow_paper_post(){
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status)) {
			$condition['status'] = (string)$status;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$groupRoles = $this->getGroupRole($this->id);
		$stores_list = $this->getStores_list($this->id);

		if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['stores_list'] = $stores_list;
		}

		if (in_array('cua-hang-truong', $groupRoles)) {
			$condition['stores_list'] = $stores_list;
		}

		$borrowed = $this->borrow_paper_model->getDataByRole_count($condition);

		if (empty($borrowed)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function approve_borrow_travel_paper_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->borrow_paper_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$fileReturn['status'] = 2;
		$user = array((string)$fileReturn['user_id']);

		$this->sendEmailBorrow_qlhs($fileReturn, $user);


		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'borrow_paper',
					'note' => 'Đã XN YC Mượn Giấy Đi Đường',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrow_paper_model->update(array("_id" => $fileReturn['_id']), ["status" => 2]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function cancel_borrow_travel_paper_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->borrow_paper_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$fileReturn['status'] = 3;
		$user = array((string)$fileReturn['user_id']);

		$this->sendEmailBorrow_qlhs($fileReturn, $user);

		if (!empty($user)) {
			foreach (array_unique($user) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'borrow_paper',
					'note' => 'Hủy YC Mượn Giấy Đi Đường',
					'user_id' => $re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->borrow_paper_model->update(array("_id" => $fileReturn['_id']), ["status" => 3]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}





}

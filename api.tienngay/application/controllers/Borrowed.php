<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Borrowed extends REST_Controller
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
		$this->load->model('sendfile_model');
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


	public function process_create_borrowed_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;


		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);
		$this->dataPost['borrowed_start'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_start']));
		$this->dataPost['borrowed_end'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_end']));
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$this->dataPost['borrowed_img'] = "";

		//Thông tin khách hàng
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
					'message' => "File mượn không được để trống"
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

		$check_borrowed = $this->borrowed_model->find_where(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement_text']));

		if (!empty($check_borrowed)) {
			if ($check_borrowed[0]->status != "3" && ($check_borrowed[0]->status != "6")) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hồ sơ của hợp đồng đang được mượn"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}


		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->borrowed_model->insertReturnId($this->dataPost);

		$log = array(
			"type" => "borrowed",
			"action" => "create",
			"borrowed_id" => $contractId,
			"borrowed" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user_asm = $this->getGroupRole_asm();

		if (!empty($user_asm)) {
			foreach (array_unique($user_asm) as $asm) {
				$data_approve = [
					'action_id' => (string)$contractId,
					'action' => 'Borrowed_create',
					'note' => 'Chờ ASM duyệt mượn HS',
					'user_id' => (string)$asm,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 8,
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

	public function get_all_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_text']) ? $this->dataPost['code_contract_disbursement_text'] : "";
		$status_borrowed = !empty($this->dataPost['status_borrowed']) ? $this->dataPost['status_borrowed'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status_borrowed)) {
			$condition['status_borrowed'] = (string)$status_borrowed;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

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

	public function get_all_header_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();

		$contract = $this->thongbao_model->getDataByRole_header();
		$time = $this->createdAt;
		$all_header = [];

		foreach ($contract as $key => $item) {
			if (!empty($item->end_date)) {
				if ($time >= $item->start_date && $time <= $item->end_date) {
					array_push($all_header, $item);
				}
			} elseif (empty($item->end_date) && $time >= $item->start_date) {
				array_push($all_header, $item);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $all_header
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_count_all_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_text']) ? $this->dataPost['code_contract_disbursement_text'] : "";
		$status_borrowed = !empty($this->dataPost['status_borrowed']) ? $this->dataPost['status_borrowed'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status_borrowed)) {
			$condition['status_borrowed'] = $status_borrowed;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
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

	public function get_one_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
		if (empty($borrowed)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function process_update_borrowed_post()
	{
		//	$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);
		$this->dataPost['borrowed_start'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_start']));
		$this->dataPost['borrowed_end'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_end']));
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);

		$this->dataPost['borrowed_img'] = "";

		//Thông tin khách hàng
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
					'message' => "File mượn không được"
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


		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "borrowed",
			"action" => "update",
			"borrowed" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), $this->dataPost);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cancel_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));


		$log = array(
			"type" => "borrowed",
			"action" => "cancel",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);


		$user_cht = $this->getGroupRole_cht();
		$user_asm = $this->getGroupRole_asm();
		$result = array_merge($user_cht, $user_asm);

		if (!empty($result)) {
			foreach (array_unique($result) as $cht) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'Borrowed',
					'note' => 'Hủy',
					'user_id' => $cht,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 3,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "3"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_borrowed_post()
	{
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);

		$this->dataPost['borrowed_start'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_start']));
		$this->dataPost['borrowed_end'] = strtotime($this->security->xss_clean($this->dataPost['borrowed_end']));


		//Thông tin khách hàng


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

		if (empty($this->dataPost['giay_to_khac'])) {
			if (empty($this->dataPost['file'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "File mượn không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "borrowed",
			"action" => "approve",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user_cht = $this->getGroupRole_cht();
		if (!empty($user_cht)) {
			foreach (array_unique($user_cht) as $cht) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'Borrowed_create',
					'note' => 'Chờ đến nhận HS',
					'user_id' => (string)$cht,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 2,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "2", "file" => $this->dataPost['file'], "giay_to_khac" => $this->dataPost['giay_to_khac'], "borrowed_end" => $this->dataPost['borrowed_end']]);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function approve_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['fileReturn_start'] = strtotime($this->security->xss_clean($this->dataPost['fileReturn_start']));
		$this->dataPost['note'] = ($this->security->xss_clean($this->dataPost['note']));


		//Thông tin khách hàng

		if (empty($this->dataPost['fileReturn_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thời gian hẹn gửi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_return_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "fileReturn",
			"action" => "approve",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_file_return_model->insert($log);

		$user_cht = $this->getGroupRole_cht();
		$user_gdv = $this->getGroupRole_gdv();
		$result = array_merge($user_cht, $user_gdv);

		if (!empty($result)) {
			foreach (array_unique($result) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'fileReturn_create',
					'note' => 'Gửi hs - Chờ nhận hồ sơ',
					'user_id' => (string)$re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'fileReturn_status' => 2,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->file_return_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "2", "note" => $this->dataPost['note'], "fileReturn_start" => $this->dataPost['fileReturn_start']]);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function confirm_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['borrowed_img'] = $this->security->xss_clean($this->dataPost['borrowed_img']);

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "borrowed",
			"action" => "confirm",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user_cht = $this->getGroupRole_cht();
		if (!empty($user_cht)) {
			foreach (array_unique($user_cht) as $cht) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'Borrowed',
					'note' => 'Đang mượn',
					'user_id' => $cht,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 4,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "4", "borrowed_img" => $this->dataPost['borrowed_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Confirm borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function pay_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "borrowed",
			"action" => "pay",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user_cht = $this->getGroupRole_cht();
		$user_hcns = $this->getGroupRole_hcns();
		$result = array_merge($user_cht, $user_hcns);

		foreach (array_unique($result) as $c) {
			$data_approve = [
				'action_id' => (string)$this->dataPost['id'],
				'action' => 'Borrowed',
				'note' => 'Chờ trả HS',
				'user_id' => $c,
				'status' => 1, //1: new, 2 : read, 3: block,
				'borrowed_status' => 5,
				'created_at' => $this->createdAt,
				"created_by" => $this->uemail
			];
			$this->borrowed_noti_model->insert($data_approve);
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "5"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Pay borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function paid_borrowed_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['time_return'] = (int)$this->security->xss_clean($this->dataPost['time_return']);
		$this->dataPost['borrowed_img'] = $this->security->xss_clean($this->dataPost['borrowed_img']);

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "borrowed",
			"action" => "paid",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_borrowed_model->insert($log);

		$user_cht = $this->getGroupRole_cht();

		foreach (array_unique($user_cht) as $c) {
			$data_approve = [
				'action_id' => (string)$this->dataPost['id'],
				'action' => 'Borrowed',
				'note' => 'Đã trả',
				'user_id' => $c,
				'status' => 1, //1: new, 2 : read, 3: block,
				'borrowed_status' => 6,
				'created_at' => $this->createdAt,
				"created_by" => $this->uemail
			];
			$this->borrowed_noti_model->insert($data_approve);
		}

		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "6", "time_return" => $this->dataPost['time_return'], "borrowed_img" => $this->dataPost['borrowed_img']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Paid borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_log_one_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$borrowed = $this->log_borrowed_model->find_where(array("borrowed_id" => $this->dataPost['id']));

		if (empty($borrowed)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $borrowed
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_log_one_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->log_file_return_model->find_where(array("fileReturn_id" => $this->dataPost['id']));

		if (empty($fileReturn)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileReturn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function update_qua_han_post()
	{
		//cron 1 ngay 1 lan
		$check_borrowed = $this->borrowed_model->find_where(array("status" => "6"));
		$user_cht = $this->getGroupRole_cht();

		if (!empty($check_borrowed)) {
			foreach ($check_borrowed as $key => $value) {
				if ($this->createdAt > $value->borrowed_end && empty($value->time_return)) {

					$log = array(
						"type" => "borrowed",
						"action" => "qua_han",
						"borrowed_id" => $value['_id'],
						"old" => $value,
						"new" => ["status" => "7"],
						"created_at" => $this->createdAt,
						"created_by" => "cron"
					);

					$this->log_borrowed_model->insert($log);

					if (!empty($user_cht)) {
						foreach (array_unique($user_cht) as $c) {
							$data_approve = [
								'action_id' => (string)$value['_id'],
								'action' => 'Borrowed',
								'note' => 'Quá hạn',
								'user_id' => $c,
								'status' => 1, //1: new, 2 : read, 3: block,
								'borrowed_status' => 7,
								'created_at' => $this->createdAt,
								"created_by" => $this->uemail
							];
							$this->borrowed_noti_model->insert($data_approve);
						}
					}

					$this->borrowed_model->update(array("_id" => $value['_id']), ["status" => "7"]);
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function approve_borrowed_asm_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$borrowed = $this->borrowed_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		if (!empty($borrowed)) {
			if ($borrowed['status'] == "3") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hồ sơ đã bị hủy",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

		}

		$log = array(
			"type" => "borrowed",
			"action" => "approve_asm",
			"borrowed_id" => $this->dataPost['id'],
			"old" => $borrowed,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_borrowed_model->insert($log);

		$user_hcns = $this->getGroupRole_hcns();
		if (!empty($user_hcns)) {
			foreach (array_unique($user_hcns) as $hcns) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'Borrowed_create',
					'note' => 'Chờ duyệt mượn HS',
					'user_id' => (string)$hcns,
					'status' => 1, //1: new, 2 : read, 3: block,
					'borrowed_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->borrowed_model->update(array("_id" => $borrowed['_id']), ["status" => "1"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


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

	public function getGroupRole_vpp()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'hanh-chinh'));

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

	public function getGroupRole_cht()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'cua-hang-truong'));

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

	public function getGroupRole_asm()
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

	public function process_create_fileReturn_post()
	{

		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);
		$this->dataPost['code_contract_disbursement_text'] = trim(implode("", $this->security->xss_clean($this->dataPost['code_contract_disbursement_text'])));
		$this->dataPost['file'] = $this->security->xss_clean($this->dataPost['file']);
		$this->dataPost['giay_to_khac'] = $this->security->xss_clean($this->dataPost['giay_to_khac']);
		$this->dataPost['fileReturn_start'] = strtotime($this->security->xss_clean($this->dataPost['fileReturn_start']));

		$this->dataPost['fileReturn_img'] = "";

		//Thông tin khách hàng
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
					'message' => "File trả không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (empty($this->dataPost['fileReturn_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày yêu cầu gửi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_fileReturn = $this->file_return_model->find_where(array("code_contract_disbursement_text" => $this->dataPost['code_contract_disbursement_text']));

//		if (!empty($check_fileReturn)) {
//			if ($check_fileReturn[0]->status == "2") {
//				$response = array(
//					'status' => REST_Controller::HTTP_UNAUTHORIZED,
//					'message' => "Hồ sơ của hợp đồng đã được trả"
//				);
//				$this->set_response($response, REST_Controller::HTTP_OK);
//				return;
//			}
//		}

		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->file_return_model->insertReturnId($this->dataPost);

		$log = array(
			"type" => "fileReturn",
			"action" => "create",
			"fileReturn_id" => (string)$contractId,
			"fileReturn" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_file_return_model->insert($log);

		$user_qlhs = $this->getGroupRole_hcns();

		if (!empty($user_qlhs)) {
			foreach (array_unique($user_qlhs) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$contractId,
					'action' => 'FileReturn_create',
					'note' => 'Gửi hs - Chờ nhận hồ sơ gửi lên',
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

	public function get_all_fileReturn_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_text']) ? $this->dataPost['code_contract_disbursement_text'] : "";
		$status_fileReturn = !empty($this->dataPost['status_fileReturn']) ? $this->dataPost['status_fileReturn'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status_fileReturn)) {
			$condition['status_fileReturn'] = (string)$status_fileReturn;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


//		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
//		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

//		if (!empty($start) && !empty($end)) {
//			$condition = array(
//				'start' => strtotime(trim($start) . ' 00:00:00'),
//				'end' => strtotime(trim($end) . ' 23:59:59')
//			);
//		}

		$fileReturn = $this->file_return_model->getDataByRole($condition, $per_page, $uriSegment);
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

	public function get_count_all_fileReturn_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code_contract_disbursement_text = !empty($this->dataPost['code_contract_disbursement_text']) ? $this->dataPost['code_contract_disbursement_text'] : "";
		$status_fileReturn = !empty($this->dataPost['status_fileReturn']) ? $this->dataPost['status_fileReturn'] : "";

		if (!empty($code_contract_disbursement_text)) {
			$condition['code_contract_disbursement_text'] = $code_contract_disbursement_text;
		}
		if (!empty($status_fileReturn)) {
			$condition['status_fileReturn'] = (string)$status_fileReturn;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


//		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
//		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
//
//		if (!empty($start) && !empty($end)) {
//			$condition = array(
//				'start' => strtotime(trim($start) . ' 00:00:00'),
//				'end' => strtotime(trim($end) . ' 23:59:59')
//			);
//		}

		$returnFile_count = $this->file_return_model->getCountByRole($condition);

		if (empty($returnFile_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $returnFile_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function get_one_fileReturn_post()
	{

		//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$fileReturn = $this->file_return_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
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

		$this->dataPost['fileReturn_start'] = strtotime($this->security->xss_clean($this->dataPost['fileReturn_start']));


		$this->dataPost['fileReturn_img'] = "";

		//Thông tin khách hàng
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
					'message' => "File trả không được để trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (empty($this->dataPost['fileReturn_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày yêu cầu gửi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		$check_fileReturn = $this->file_return_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "fileReturn",
			"action" => "update",
			"fileReturn" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_file_return_model->insert($log);

		unset($this->dataPost['id']);

		$this->file_return_model->update(array("_id" => $check_fileReturn['_id']), $this->dataPost);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function getGroupRole_gdv()
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

	public function confirm_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['fileReturn_img'] = $this->security->xss_clean($this->dataPost['fileReturn_img']);

		$fileReturn = $this->file_return_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "fileReturn",
			"action" => "confirm",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_file_return_model->insert($log);

		$user_cht = $this->getGroupRole_cht();
		$user_gdv = $this->getGroupRole_gdv();
		$result = array_merge($user_cht, $user_gdv);

		if (!empty($result)) {
			foreach (array_unique($result) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_Confirm',
					'note' => 'Gửi hs - Đã nhận hồ sơ',
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

		$this->file_return_model->update(array("_id" => $fileReturn['_id']), ["status" => "3", "fileReturn_img" => $this->dataPost['fileReturn_img'], "receive" => $this->createdAt]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Confirm borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cancel_fileReturn_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->file_return_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "fileReturn",
			"action" => "pending",
			"fileReturn_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_file_return_model->insert($log);


		$user_cht = $this->getGroupRole_cht();
		$user_gdv = $this->getGroupRole_gdv();
		$result = array_merge($user_cht, $user_gdv);

		if (!empty($result)) {
			foreach (array_unique($result) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'FileReturn_pending',
					'note' => 'Gửi hs - Chưa nhận được hồ sơ',
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

		$this->file_return_model->update(array("_id" => $fileReturn['_id']), ["status" => "4"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function process_create_sendFile_post()
	{

		$this->dataPost['store_take_value'] = $this->security->xss_clean($this->dataPost['store_take_value']);
		$this->dataPost['van_phong_pham_value'] = $this->security->xss_clean($this->dataPost['van_phong_pham_value']);
		$this->dataPost['cong_cu_value'] = $this->security->xss_clean($this->dataPost['cong_cu_value']);
		$this->dataPost['send_start'] = strtotime($this->security->xss_clean($this->dataPost['send_start']));
		$this->dataPost['send_end'] = strtotime($this->security->xss_clean($this->dataPost['send_end']));
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$this->dataPost['img_send_file'] = $this->security->xss_clean($this->dataPost['img_send_file']);


		//Thông tin
		if (empty($this->dataPost['store_take_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "PGD không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['van_phong_pham_value']) && empty($this->dataPost['cong_cu_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Văn phòng phẩm hoặc công cụ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['send_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày gửi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['send_end'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày dự kiến nhận không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (!empty($this->dataPost['send_start']) && !empty($this->dataPost['send_end'])){
			if ($this->dataPost['send_start'] > $this->dataPost['send_end']){
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày gửi không được lớn hơn ngày dự kiến"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}


		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->sendfile_model->insertReturnId($this->dataPost);

		$log = array(
			"type" => "sendFile",
			"action" => "create",
			"sendFile_id" => (string)$contractId,
			"sendFile" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_sendfile_model->insert($log);


		foreach ($this->dataPost['store_take_value'][0] as $t) {
			$user = $this->getGroupRole_slug($t);
			foreach (array_unique($user) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$contractId,
					'action' => 'SendFile_create',
					'note' => 'Gửi vpp - Chờ nhận văn phòng phẩm',
					'user_id' => (string)$qlhs,
					'status' => 1, //1: new, 2 : read, 3: block,
					'SendFile_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new send file success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function getGroupRole_slug($slug)
	{
		$groupRoles = $this->role_model->find_where(array("status" => "active", 'slug' => $slug));
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

	public function get_count_all_sendfile_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status_sendFile = !empty($this->dataPost['status_sendFile']) ? $this->dataPost['status_sendFile'] : "";

		if (!empty($status_sendFile)) {
			$condition['status_sendFile'] = (string)$status_sendFile;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$sendFile_count = $this->sendfile_model->getCountByRole($condition);

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

	public function get_all_sendFile_post()
	{
		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		$status_sendFile = !empty($this->dataPost['status_sendFile']) ? $this->dataPost['status_sendFile'] : "";

		if (!empty($status_sendFile)) {
			$condition['status_sendFile'] = (string)$status_sendFile;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$fileReturn = $this->sendfile_model->getDataByRole($condition, $per_page, $uriSegment);
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

	public function get_one_sendFile_post()
	{

		$data = $this->input->post();
		$sendFile = $this->sendfile_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
		if (empty($sendFile)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $sendFile
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function process_update_sendFile_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['store_take_value'] = $this->security->xss_clean($this->dataPost['store_take_value']);
		$this->dataPost['van_phong_pham_value'] = $this->security->xss_clean($this->dataPost['van_phong_pham_value']);
		$this->dataPost['cong_cu_value'] = $this->security->xss_clean($this->dataPost['cong_cu_value']);
		$this->dataPost['send_start'] = strtotime($this->security->xss_clean($this->dataPost['send_start']));
		$this->dataPost['send_end'] = strtotime($this->security->xss_clean($this->dataPost['send_end']));
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$this->dataPost['img_send_file'] = $this->security->xss_clean($this->dataPost['img_send_file']);

		//Thông tin
		if (empty($this->dataPost['store_take_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "PGD không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['van_phong_pham_value']) && empty($this->dataPost['cong_cu_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Văn phòng phẩm hoặc công cụ không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['send_start'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày gửi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['send_end'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày dự kiến nhận không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (!empty($this->dataPost['send_start']) && !empty($this->dataPost['send_end'])){
			if ($this->dataPost['send_start'] > $this->dataPost['send_end']){
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày gửi không được lớn hơn ngày dự kiến"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}


		$check_sendFile = $this->sendfile_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;

		$log = array(
			"type" => "sendFile",
			"action" => "update",
			"sendFile_id" => (string)$this->dataPost['id'],
			"sendFile" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_sendfile_model->insert($log);

		foreach ($check_sendFile['store_take_value'][0] as $t) {
			$user = $this->getGroupRole_slug($t);
			foreach (array_unique($user) as $qlhs) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'SendFile_update',
					'note' => 'Gửi vpp - Sửa thông tin vpp',
					'user_id' => (string)$qlhs,
					'status' => 1, //1: new, 2 : read, 3: block,
					'SendFile_status' => 1,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}

		unset($this->dataPost['id']);

		$this->sendfile_model->update(array("_id" => $check_sendFile['_id']), $this->dataPost);



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update send file success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function confirm_sendFile_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['img_send_file'] = $this->security->xss_clean($this->dataPost['img_send_file']);
		$this->dataPost['return_time'] = $this->createdAt;

		$check_fileReturn = $this->sendfile_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "sendFile",
			"action" => "approve",
			"sendFile_id" => $this->dataPost['id'],
			"old" => $check_fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_sendfile_model->insert($log);

		$arr_store = [];
		if (!empty($check_fileReturn)){
			foreach ($check_fileReturn['store_take_value'][0] as $store){
				$check = $this->check_name($store);
				array_push($arr_store, $check);
			}
		}

		$user_vpp = $this->getGroupRole_vpp();

		if (!empty($user_vpp)) {
			foreach (array_unique($user_vpp) as $re) {
				$data_approve = [
					'action_id' => (string)$this->dataPost['id'],
					'action' => 'SendFile_create',
					'note' => 'Gửi vpp - '. implode(", ", $arr_store) . " - Đã nhận",
					'user_id' => (string)$re,
					'status' => 1, //1: new, 2 : read, 3: block,
					'SendFile_status' => 2,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->borrowed_noti_model->insert($data_approve);
			}
		}


		unset($this->dataPost['id']);

		$this->sendfile_model->update(array("_id" => $check_fileReturn['_id']), ["status" => "2", "return_time" => $this->createdAt]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Approve send file success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_log_one_sendFile_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->log_sendfile_model->find_where(array("sendFile_id" => $this->dataPost['id']));

		if (empty($fileReturn)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $fileReturn
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function cancel_sendFile_post()
	{

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$fileReturn = $this->sendfile_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "sendFile",
			"action" => "cancel",
			"sendFile_id" => $this->dataPost['id'],
			"old" => $fileReturn,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_sendfile_model->insert($log);

		foreach ($fileReturn['store_take_value'][0] as $value) {

			$user = $this->getGroupRole_slug($value);

			if (!empty($user)) {
				foreach (array_unique($user) as $re) {
					$data_approve = [
						'action_id' => (string)$this->dataPost['id'],
						'action' => 'SendFile_pending',
						'note' => 'Gửi vpp - Hủy yêu cầu',
						'user_id' => $re,
						'status' => 1, //1: new, 2 : read, 3: block,
						'sendFile_status' => 3,
						'created_at' => $this->createdAt,
						"created_by" => $this->uemail
					];
					$this->borrowed_noti_model->insert($data_approve);
				}
			}
		}


		unset($this->dataPost['id']);

		$this->sendfile_model->update(array("_id" => $fileReturn['_id']), ["status" => "3"]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cancel send file success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function check_name_post()
	{

		$this->dataPost['slug'] = $this->security->xss_clean($this->dataPost['slug']);

		$groupRoles = $this->role_model->find_where(array("status" => "active", 'slug' => $this->dataPost['slug']));

		if (!empty($groupRoles)) {

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $groupRoles[0]['name']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);

		}
	}

	public function check_name($slug)
	{
		$groupRoles = $this->role_model->find_where(array("status" => "active", 'slug' => $slug));

		if (!empty($groupRoles)) {
			return $groupRoles[0]['name'];

		}
	}

	public function update_qua_han_vpp_post()
	{

		//cron 1 ngay 1 lan
		$check_borrowed = $this->sendfile_model->find_where(array("status" => "1"));

		if (!empty($check_borrowed)) {
			foreach ($check_borrowed as $key => $value) {

				$check_time = strtotime(trim(date("Y-m-d", $value->send_end)) . ' 23:59:59');

				if ($this->createdAt > $check_time && empty($value->return_time)) {

					$log = array(
						"type" => "sendFile",
						"action" => "chua-nhan-vpp",
						"sendFile_id" => (string)$value['_id'],
						"old" => $value,
						"new" => ["status" => "4"],
						"created_at" => $this->createdAt,
						"created_by" => "cron"
					);

					$this->log_sendfile_model->insert($log);

					foreach ($value['store_take_value'][0] as $item) {

//						$user = $this->getGroupRole_slug($item);
						$user = $this->getGroupRole_vpp();

						if (!empty($user)) {
							foreach (array_unique($user) as $c) {
								$data_approve = [
									'action_id' => (string)$value['_id'],
									'action' => 'SendFile',
									'note' => 'Gửi vpp - Chưa nhận đc VPP',
									'user_id' => $c,
									'status' => 1, //1: new, 2 : read, 3: block,
									'sendFile_status' => 4,
									'created_at' => $this->createdAt,
									"created_by" => $this->check_name($item)
								];
								$this->borrowed_noti_model->insert($data_approve);
							}
						}

					}

					$this->sendfile_model->update(array("_id" => $value['_id']), ["status" => "4"]);
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}




}

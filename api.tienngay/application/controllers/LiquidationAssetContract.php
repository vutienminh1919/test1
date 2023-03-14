<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';
require_once APPPATH . 'libraries/Vbi_tnds_oto.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class LiquidationAssetContract extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
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
		$this->load->model("transaction_extend_model");
		$this->load->model("contract_tnds_model");
		$this->load->model("log_mic_tnds_model");
		$this->load->model("log_vbi_tnds_model");
		$this->load->model("main_property_model");
		$this->load->model("area_model");
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

	// Luồng duyệt hợp đồng thanh lý của thu hồi nợ

	public function approve_liquidations_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['date_seize'] = $this->security->xss_clean($this->dataPost['date_seize']);
		$this->dataPost['name_person_seize'] = $this->security->xss_clean($this->dataPost['name_person_seize']);
		$this->dataPost['license_plates'] = $this->security->xss_clean($this->dataPost['license_plates']);
		$this->dataPost['frame_number'] = $this->security->xss_clean($this->dataPost['frame_number']);
		$this->dataPost['engine_number'] = $this->security->xss_clean($this->dataPost['engine_number']);
		$this->dataPost['license_number'] = $this->security->xss_clean($this->dataPost['license_number']);

		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$this->dataPost['debt_remain_root'] = $this->security->xss_clean($this->dataPost['debt_remain_root']);
		$this->dataPost['suggest_price'] = $this->security->xss_clean($this->dataPost['suggest_price']);
		$this->dataPost['name_buyer'] = $this->security->xss_clean($this->dataPost['name_buyer']);
		$this->dataPost['phone_number_buyer'] = $this->security->xss_clean($this->dataPost['phone_number_buyer']);
		$this->dataPost['image_file'] = $this->security->xss_clean($this->dataPost['image_file']);
		$this->dataPost['data_send_approve'] = $this->security->xss_clean($this->dataPost['data_send_approve']);

		if (!empty($this->info['is_superadmin']) && $this->info['is_superadmin'] != 1) {
			// Check access right by status
			$isAccess = $this->checkApproveLiquidationsByAccessRight($this->roleAccessRights, $this->dataPost['status']);
			if ($isAccess == FALSE) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'Do not have access right'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		// Check old status
		$contract = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($contract['store']['id'])));

		//Insert log
		$log = array(
			"type" => "contract",
			"action" => "approve",
			"contract_id" => $this->dataPost['id'],
			"old" => $contract,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		if (empty($this->dataPost['status']) && $this->dataPost['data_send_approve'] == "cancel_approve") {
			$log['action'] = "tp_thn_cancel_liquidations";
			$log['new']['status'] = (int)$contract["liquidation_info"]["old_status"];
		}
		if ($contract['status'] == '17' && (int)$this->dataPost['status'] == '37') {
			$log['action'] = "nv_thn_send_request_liquidations";
		}
		if ($contract['status'] == '37' && (int)$this->dataPost['status'] == '38') {
			$log['action'] = "tp_thn_send_ceo_confirm";
		}
		if ($contract['status'] == '39' && (int)$this->dataPost['status'] == '38') {
			$log['action'] = "wait_tp_thn_confirm";
		}
		if ($contract['status'] == '38' && (int)$this->dataPost['status'] == '43') {
			$log['action'] = "ceo_cancel_approve";
		}
		if ($contract['status'] == '38' && (int)$this->dataPost['status'] == '39') {
			$log['action'] = "ceo_approve_liquidations";
		}
		if ($contract['status'] == '43' && (int)$this->dataPost['status'] == '38') {
			$log['action'] = "tp_thn_send_ceo_again";
		}
		if ($contract['status'] == '39' && (int)$this->dataPost['status'] == '40') {
			$log['action'] = "tp_thn_confirm_liquidations";
		}

		$this->log_model->insert($log);

		$status = (int)$this->dataPost['status'];
		//Update status contract

		if ($status == 37 && (($contract["status"] == 17) || ($contract["status"] == 20))) {
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"liquidation_info.date_seize" => $this->dataPost["date_seize"],
				"liquidation_info.name_person_seize" => $this->dataPost["name_person_seize"],
				"liquidation_info.license_plates" => $this->dataPost["license_plates"],
				"liquidation_info.frame_number" => $this->dataPost["frame_number"],
				"liquidation_info.engine_number" => $this->dataPost["engine_number"],
				"liquidation_info.license_number" => $this->dataPost["license_number"],
				"liquidation_info.old_status" => $contract["status"],
				"liquidation_info.created_at_request" => $this->createdAt,
				"liquidation_info.created_by_request" =>$this->uemail

			);
		} elseif ($status == 38) {
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note'],
				"suggest_price_info.debt_remain_root" => $this->dataPost['debt_remain_root'],
				"suggest_price_info.suggest_price" => $this->dataPost['suggest_price'],
				"suggest_price_info.name_buyer" => $this->dataPost['name_buyer'],
				"suggest_price_info.phone_number_buyer" => $this->dataPost['phone_number_buyer'],
				"suggest_price_info.image_liquidation_file" => $this->dataPost['image_file'],
				"suggest_price_info.created_at_suggest" => $this->createdAt,
				"suggest_price_info.created_by_suggest" => $this->uemail,
			);
		} elseif (empty($status)) {
			$arrUpdate = array(
				"status" => (int)$contract["liquidation_info"]["old_status"],
				"note" => $this->dataPost['note'],
			);
		} elseif ($status == 39 || $status == 43) {
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note'],
			);
		} elseif ($status == 40) {
			$arrUpdate = array(
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['note'],
				"liquidation_info.created_at_liquidations" => $this->createdAt,
				"liquidation_info.created_by_liquidations" => $this->uemail,
				"liquidation_info.status_liquidations" => 40
			);
		}

		$this->contract_model->update(array("_id" => $contract['_id']), $arrUpdate);
		$note = '';
		$user_ids = array();
		$user_ids_approve = array();

		//5de72198d6612b4076140606 super admin
		//5ea803b0d6612b991c2cdc97 TP THN
		//602210b35324a7fcfd3ed98e id CEO live haileminh.ftu@gmail.com
		//5def3e8fc1bff1475f7bf6c4 id CEO loal hailm@tienngay.vn
		//5ea1b6abd6612b6dd20de539 THN
		if ($status == 37 && (($contract["status"] == 17) || ($contract["status"] == 20))) {
			$note = "Chờ TP THN duyệt yêu cầu thanh lý";
			$tp_thn_id = array(
				"5ea803b0d6612b991c2cdc97"
			);
			$user_ids_approve = $this->getUserGroupRole($tp_thn_id);
			$data_send = array(
				"code" => "vfc_send_head_of_debt_collection",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
				"phone_store" => $store['phone'],
				"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì",
				"license_plates" => $contract['liquidation_info']['license_plates'],
				"frame_number" => $contract['liquidation_info']['frame_number'],
				"engine_number" => $contract['liquidation_info']['engine_number'],
				"license_number" => $contract['liquidation_info']['license_number'],
				"name_person_seize" => $contract['liquidation_info']['name_person_seize'],
			);
			$this->sendEmailApproveLiquidation($user_ids_approve, $data_send, $status);
		} elseif (empty($status)) {
			$note = "TP THN không duyệt yêu cầu thanh lý";
			$user_created = $this->user_model->findOne(array('email' => $contract['liquidation_info']['created_by_request']));
			array_push($user_ids, (string)$user_created['_id']);
		} elseif ($status == 38) {
			//id CEO => 608137415324a7567e5ffe04
			$note = "Chờ CEO duyệt thanh lý";
			$user_high_manager = array(
				"608137415324a7567e5ffe04"
			);
			$user_ids_approve = $this->getUserGroupRole($user_high_manager);
			$data_send = array(
				"code" => "vfc_send_ceo",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
				"phone_store" => $store['phone'],
				"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì",
				"debt_remain_root" => $contract['suggest_price_info']['debt_remain_root'],
				"suggest_price" => $contract['suggest_price_info']['suggest_price'],
				"name_buyer" => $contract['suggest_price_info']['name_buyer'],
				"phone_number_buyer" => $contract['suggest_price_info']['phone_number_buyer']
			);
			$this->sendEmailApproveLiquidation($user_ids_approve, $data_send, $status);
		} elseif ($status == 43) {
			$note = "CEO hủy duyệt đề xuất thanh lý";
			$tp_thn_id = array(
				"5ea803b0d6612b991c2cdc97"
			);
			$user_ids_approve = $this->getUserGroupRole($tp_thn_id);
			$data_send = array(
				"code" => "vfc_ceo_cancel_approve_liquidations",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
				"phone_store" => $store['phone'],
				"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì",
				"debt_remain_root" => $contract['suggest_price_info']['debt_remain_root'],
				"suggest_price" => $contract['suggest_price_info']['suggest_price'],
				"name_buyer" => $contract['suggest_price_info']['name_buyer'],
				"phone_number_buyer" => $contract['suggest_price_info']['phone_number_buyer'],
				"status_contract_past" => $contract["status"],
				"note" => $note
			);
			$this->sendEmailApproveLiquidation($user_ids_approve, $data_send, $status);
		} elseif ($status == 39) {
			$note = "Chờ TP THN xác nhận thanh lý";
			$tp_thn_ids = array("5ea803b0d6612b991c2cdc97");
			$user_ids_approve = $this->getUserGroupRole($tp_thn_ids);
		} elseif ($status == 40) {
			$note = "Tạo phiếu thu Tất toán cho HĐ đã thanh lý tài sản";
			$id_thn_group_role = array("5ea1b6abd6612b6dd20de539");
			$tp_thn_ids = array("5ea803b0d6612b991c2cdc97");
			$thn_user_ids = $this->getUserGroupRole($id_thn_group_role);
			$tp_thn_id = $this->getUserGroupRole($tp_thn_ids);
			$user_created = $this->user_model->findOne(array('email' => $contract['liquidation_info']['created_by_request']));
			$arrTemp = array_intersect($thn_user_ids, $tp_thn_id);
			$user_ids = array_values($arrTemp);
			array_push($user_ids, (string)$user_created['_id']);
		} elseif ($status == 17 || $status == 20) {
			$note = "TP THN hủy yêu cầu thanh lý tài sản";
			$thn_user_ids = array(
				"5ea1b6abd6612b6dd20de539"
			);
			$user_thn_id = $this->getUserGroupRole($thn_user_ids);
			$data_send = array(
				"code" => "vfc_cancel_send_nv_thn",
				"customer_name" => $contract['customer_infor']['customer_name'],
				"code_contract" => $contract['code_contract'],
				"store_name" => $contract['store']['name'],
				"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
				"product" => $contract['loan_infor']['type_loan']['text'],
				"product_detail" => $contract['loan_infor']['name_property']['text'],
				"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
				"phone_store" => $store['phone'],
				"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng, gốc cuối kì",
				"license_plates" => $contract['liquidation_info']['license_plates'],
				"frame_number" => $contract['liquidation_info']['frame_number'],
				"engine_number" => $contract['liquidation_info']['engine_number'],
				"license_number" => $contract['liquidation_info']['license_number'],
				"name_person_seize" => $contract['liquidation_info']['name_person_seize'],
				"email_nv_thn" => $contract['liquidation_info']['created_by_request'],
				"note" => $note

			);
			$this->sendEmailApproveLiquidation($user_thn_id, $data_send, $status);
		}

		$link_detail = 'pawn/detail?id=' . (string)$contract['_id'];
		$link_detail_view_v2 = 'accountant/view_v2?id=' . (string)$contract['_id'];
		// oke
		$dataSocket = array();
		if (!empty($user_ids)) {
			$user_ids = array_values($user_ids);
			foreach ($user_ids as $u) {
				if ($status == 40) {
					$link_detail = 'accountant/view_v2?id=' . (string)$contract['_id'];
				} else {
					$link_detail = 'pawn/detail?id=' . (string)$contract['_id'];
				}
				$data_notification = [
					'action_id' => (string)$contract['_id'],
					'action' => 'contract',
					'title' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
					'detail' => $link_detail,
					'note' => $note,
					'user_id' => $u,
					'status' => 1, //1: new, 2 : read, 3: block,
					'contract_status' => $status,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->notification_model->insertReturnId($data_notification);
			}
		}
		if (!empty($user_ids_approve)) {
			$user_ids_approve = array_values($user_ids_approve);
			if ($note == '') {
				$note = 'Chờ duyệt thanh lý tài sản';
			}
			foreach ($user_ids_approve as $us) {
				if ($status == 37 && (($contract["status"] == 17) || ($contract["status"] == 20))) {
					$note = 'NV thu hồi nợ gửi yêu cầu tạo thanh lý tài sản';
				} else if ($status == 38) {
					$note = 'Chờ CEO duyệt thanh lý';
				} else if ($status == 39) {
					$note = 'Chờ TP THN xác nhận thanh lý';
				} else if ($status == 43) {
					$note = 'CEO từ chối duyệt thanh lý';
				}

				$data_approve = [
					'action_id' => (string)$contract['_id'],
					'action' => 'contract',
					'detail' => $link_detail,
					'title' => $contract['customer_infor']['customer_name'] . ' - ' . $contract['store']['name'],
					'note' => $note,
					'user_id' => $us,
					'status' => 1, //1: new, 2 : read, 3: block,
					'contract_status' => $status,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$this->notification_model->insertReturnId($data_approve);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Duyệt thành công!',
			'dataSocket' => $dataSocket
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function sendEmailApproveLiquidation($user_id, $data, $status)
	{
		foreach ($user_id as $key => $value) {
			if ($status == 37 || $status == 38) {
				$dataUser = $this->user_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($value)));
				$email = !empty($dataUser['email']) ? $dataUser['email'] : "";

				$full_name = !empty($dataUser['full_name']) ? $dataUser['full_name'] : "";
				if (!empty($email)) {
					$data['email'] = $email;
					$data['full_name'] = $full_name;
					$data['API_KEY'] = $this->config->item('API_KEY');
					$this->user_model->send_Email($data);
				}
			}
		}
		//gửi cho nhân viên thu hồi nợ
		if ($status == 17 || $status == 20) {
			if (!empty($data['created_by'])) {
				$data['email'] = $data['email_nv_thn'];
				$data['API_KEY'] = $this->config->item('API_KEY');
				// return $data;
				$this->user_model->send_Email($data);
			}
		}
		// status == 40 send email cho khach hang (thong bao tai san da duoc thanh ly)
		if ($status == 40 && !empty($data['customer_email'])) {
			if (!empty($data['customer_email'])) {
				$data['code'] = 'vfc_liquidations_send_customer';
				$data['email'] = $data['customer_email'];
				$data['API_KEY'] = $this->config->item('API_KEY');
				// return $data;
				$this->user_model->send_Email($data);
			}
		}
	}

	private function checkApproveLiquidationsByAccessRight($roleAccessRights, $status)
	{
		$isAccess = false;
		//Status = 38 = TP THN duyệt yêu cầu -> Chờ CEO duyệt = 60a62bae5324a75dc12b8b75
		//Status = 39 = CEO duyệt đề xuất thanh lý -> Chờ TP THN xác nhận = 60a62c305324a767e20de7c3

		if ($status == 38 && in_array('60a62bae5324a75dc12b8b75', $roleAccessRights) ||
			$status == 39 && in_array('60a62c305324a767e20de7c3', $roleAccessRights))
//				if (
//					$status == 38 && in_array('60a631901f710000f30034e4', $roleAccessRights) ||
//					$status == 39 && in_array('60a6319d1f710000f30034e5', $roleAccessRights))
			$isAccess = true;
		return $isAccess;
	}

	public function contract_tempo_liquidations_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost = $this->input->post()['condition'];
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
		$id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
		$bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
		$investor_code = !empty($this->dataPost['investor_code']) ? $this->dataPost['investor_code'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "17";
		$status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($status_disbursement)) {
			$condition['status_disbursement'] = $status_disbursement;
		}

		if (!empty($investor_code)) {
			$condition['investor_code'] = $investor_code;
		}
		if (!empty($store_id)) {
			$condition['store'] = $store_id;
		}

		if (!empty($id_card)) {
			$condition['id_card'] = $id_card;
		}
		if (!empty($bucket)) {
			$condition['bucket'] = $bucket;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = trim($customer_phone_number);
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}
		$groupRoles = $this->getGroupRole($this->id);

		$all = false;
		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles)) {
			$all = true;
		}
		if (!empty($code_contract_disbursement) || !empty($customer_name) || !empty($customer_phone_number)) {
			$all = false;
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
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$contract = array();

		$contract = $this->contract_model->getContractLiquidations(array(), $condition, $per_page, $uriSegment);
		$total = $this->contract_model->getContractByTimeAllLiquidations(array(), $condition);


		if (!empty($contract)) {
			foreach ($contract as $key => $c) {
				$cond = array();
				$c['investor_name'] = "";
				if (isset($c['investor_code'])) {
					$investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
					$c['investor_name'] = $investors['name'];
				}
				if (isset($c['code_contract'])) {
					$cond = array(
						'code_contract' => $c['code_contract'],
						'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
					);
				}
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				$c['detail'] = array();
				if (!empty($detail)) {
					$total_paid = 0;
					$total_phi_phat_cham_tra = 0;
					$total_da_thanh_toan = 0;
					foreach ($detail as $de) {

						$total_paid = $total_paid + $de['tien_tra_1_ky'];
						$total_phi_phat_cham_tra += $de['penalty'];
						$total_da_thanh_toan += $de['da_thanh_toan'];
					}
					$c['detail'] = $detail[0];
					$c['detail']['total_paid'] = $total_paid;
					$c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
					$c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
				} else {
					$condition_new = array(
						'code_contract' => $c['code_contract'],
						'status' => 1
					);
					$detail_new = $this->contract_tempo_model->getContract($condition_new);
					if (!empty($detail_new)) {
						$c['detail'] = $detail_new[0];

						$c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
						$c['detail']['total_phi_phat_cham_tra'] = $detail_new[0]['penalty'];
						$c['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
					}
				}

				$time = 0;

				$c['time'] = $time;
				if ($c['status'] == 19 || $c['status'] == 23)
					$c['time'] = '-';

				$tempo = $this->contract_tempo_model->find_where(['code_contract' => $c['code_contract'], 'status' => 1]);
				if (!empty($tempo)) {
					$c['tempo'] = $tempo[0];
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_date_liquidations_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$date_liquidations = !empty($data['date_liquidations']) ? $data['date_liquidations'] : "";

		$count = $this->contract_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		$contract_old = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		if ($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		unset($data['id']);
		$log = array(
			"type" => "contract",
			"action" => "update",
			"contract_id" => $data['id'],
			"old" => $contract_old,
			"new" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($log);

		$this->contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			["liquidation_info.created_at_liquidations" => (int)$date_liquidations]
		);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'contract' => $contract_old['liquidation_info']["created_at_liquidations"],
			'message' => "Cập nhập ngày thanh lý tài sản thành công!"
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

	private function transferSocket($data)
	{
		$version = new Version2X($this->config->item('IP_SOCKET_SERVER'));
		$dataNotify['res'] = $data['status'];
		if (!empty($data['approve'])) {
			$dataApprove['res'] = $data['approve'];
		}
		try {
			$client = new Client($version);
			$client->initialize();
			$client->emit('notify_status', $dataNotify);
			if (!empty($dataApprove)) {
				$client->emit('notify_approve', $dataApprove);
			}
			$client->close();
		} catch (Exception $e) {

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
}

?>

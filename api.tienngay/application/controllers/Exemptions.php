<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class exemptions extends REST_Controller
{

	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model('menu_model');
		$this->load->helper('lead_helper');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('group_role_model');
		$this->load->model('contract_model');
		$this->load->model("transaction_model");
		$this->load->model('contract_tempo_model');
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('exemptions_model');
		$this->load->model('log_exemptions_model');
		$this->load->model("store_model");
		$this->load->model("notification_model");
		$this->load->model("device_model");
		$this->load->model("email_history_model");
		$this->load->model("email_template_model");
		$this->load->model("lead_model");
		$this->load->model("kpi_area_model");
		$this->load->model("kpi_gdv_model");
		$this->load->model("kpi_pgd_model");


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

	public function approve_exemptions_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id_contract'] = $this->security->xss_clean($this->dataPost['id_contract']);
		$this->dataPost['id_exemption'] = $this->security->xss_clean($this->dataPost['id_exemption']);
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['code_contract_disbursement'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement']);
		$this->dataPost['customer_phone_number'] = $this->security->xss_clean($this->dataPost['customer_phone_number']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$this->dataPost['ky_tra'] = $this->security->xss_clean($this->dataPost['ky_tra']);
		$this->dataPost['ngay_ky_tra'] = $this->security->xss_clean($this->dataPost['ngay_ky_tra']);
		$this->dataPost['customer_name'] = $this->security->xss_clean($this->dataPost['customer_name']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$this->dataPost['status_update'] = $this->security->xss_clean($this->dataPost['status_update']);
		$this->dataPost['amount_customer_suggest'] = $this->security->xss_clean($this->dataPost['amount_customer_suggest']);
		$this->dataPost['amount_tp_thn_suggest'] = $this->security->xss_clean($this->dataPost['amount_tp_thn_suggest']);
		$this->dataPost['user_receive_approve'] = $this->security->xss_clean($this->dataPost['user_receive_approve']);
		$this->dataPost['user_receive_cc'] = $this->security->xss_clean($this->dataPost['user_receive_cc']);
		$this->dataPost['date_suggest'] = $this->security->xss_clean($this->dataPost['date_suggest']);
		$this->dataPost['start_date_effect'] = $this->security->xss_clean($this->dataPost['start_date_effect']);
		$this->dataPost['end_date_effect'] = $this->security->xss_clean($this->dataPost['end_date_effect']);
		$this->dataPost['image_file'] = $this->security->xss_clean($this->dataPost['image_file']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$this->dataPost['note_lead'] = $this->security->xss_clean($this->dataPost['note_lead']);
		$this->dataPost['note_tp_thn'] = $this->security->xss_clean($this->dataPost['note_tp_thn']);
		$this->dataPost['note_qlcc'] = $this->security->xss_clean($this->dataPost['note_qlcc']);
		$this->dataPost['position'] = $this->security->xss_clean($this->dataPost['position']);
		$this->dataPost['type_payment_exem'] = $this->security->xss_clean($this->dataPost['type_payment_exem']);
		$this->dataPost['customer_identify'] = $this->security->xss_clean($this->dataPost['customer_identify']);
		if (!empty($this->dataPost['status'])) {
			$status = $this->dataPost['status'];
		}
		if (!empty($this->dataPost['status_update'])) {
			$status_update = (int)$this->dataPost['status_update'];
		}
		if (!empty($this->dataPost['code_contract'])) {
			$code_contract = $this->dataPost['code_contract'];
		}
		if (!empty($this->dataPost['id_exemption'])) {
			$id_exemption_post = $this->dataPost['id_exemption'];
		}
		$old_exemption_contract = $this->exemptions_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_exemption_post)]);

		// B1: Tạo đơn miễn giảm.
		$log = [];
		if ($status == 1) {
			$data_insert = [
				'id_contract' => $this->dataPost['id_contract'],
				'code_transaction' => '',
				'code_contract' => $code_contract,
				'code_contract_disbursement' => $this->dataPost['code_contract_disbursement'],
				'customer_name' => $this->dataPost['customer_name'],
				'customer_phone_number' => $this->dataPost['customer_phone_number'],
				'store' => $this->dataPost['store'],
				'status' => (int)$status,
				'amount_customer_suggest' => $this->dataPost['amount_customer_suggest'],
				'ky_tra' => (int)$this->dataPost['ky_tra'],
				'ngay_ky_tra' => (int)$this->dataPost['ngay_ky_tra'],
				'date_suggest' => (int)$this->dataPost['date_suggest'],
				'start_date_effect' => (int)$this->dataPost['start_date_effect'],
				'end_date_effect' => (int)$this->dataPost['end_date_effect'],
				'image_exemption_profile' => $this->dataPost['image_file'],
				'note' => $this->dataPost['note'],
				'created_profile_at' => $this->createdAt,
				'type_payment_exem' => $this->dataPost['type_payment_exem'],
				'created_profile_by' => $this->uemail,
				'customer_identify' => $this->dataPost['customer_identify'],
			];

			$check_isset_record = $this->exemptions_model->find_where(['code_contract' => $code_contract]);
			if (!empty($check_isset_record)) {
				foreach ($check_isset_record as $key => $check) {
					if ((!empty($check['ky_tra']) && $check['ky_tra'] == $this->dataPost['ky_tra']) || $check['type_payment_exem'] == 2) {
						$response = [
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Đã tồn tại đơn miễn giảm của kỳ hiện tại!"
						];
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$id_exemption_insert_return = $this->exemptions_model->insertReturnId($data_insert);
						$log = [
							'type' => 'application_exemptions',
							'action' => 'create_exemption_profile',
							'code_contract' => $this->dataPost['code_contract'],
							'ky_tra' => $this->dataPost['ky_tra'],
							'exemptions_id' => (string)$id_exemption_insert_return,
							'record_exemptions' => $data_insert,
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						];
						$this->log_exemptions_model->insert($log);
					}
				}
			} else {
				$id_exemption_insert_return = $this->exemptions_model->insertReturnId($data_insert);
				$log = [
					'type' => 'application_exemptions',
					'action' => 'create_exemption_profile',
					'code_contract' => $this->dataPost['code_contract'],
					'ky_tra' => $this->dataPost['ky_tra'],
					'exemptions_id' => (string)$id_exemption_insert_return,
					'record_exemptions' => $data_insert,
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
				];
				$this->log_exemptions_model->insert($log);
			}
		}

		// B2: Lead THN xử lý đơn miễn giảm && TP, QLCC trả về
		if (in_array($status, [2, 3, 4, 7, 8, 9])) {
			$array_update = [
				'status' => (int)$status,
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail,
			];
			if ($this->dataPost['position'] == "lead") {
				$array_update['note_lead'] = $this->dataPost['note_lead'];
			} elseif ($this->dataPost['position'] == "tp") {
				$array_update['note_tp_thn'] = $this->dataPost['note_tp_thn'];
			} elseif ($this->dataPost['position'] == "qlcc") {
				$array_update['note_qlcc'] = $this->dataPost['note_qlcc'];
			}
		}

		// Gửi lại đơn miễn giảm
		if (isset($status_update) && $status_update == 1) {
			$array_update = [
				'status' => 1,
				'type_payment_exem' => $this->dataPost['type_payment_exem'],
				'amount_customer_suggest' => $this->dataPost['amount_customer_suggest'],
				'date_suggest' => (int)$this->dataPost['date_suggest'],
				'start_date_effect' => (int)$this->dataPost['start_date_effect'],
				'end_date_effect' => (int)$this->dataPost['end_date_effect'],
				'image_exemption_profile' => $this->dataPost['image_file'],
				'note' => $this->dataPost['note'],
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail,
			];
		}

		// B3: TP THN xử lý đơn miễn giảm
		// TP THN Duyệt
		if ($status == 5) {
			$array_update = [
				'image_exemption_profile' => $this->dataPost['image_file'],
				'amount_tp_thn_suggest' => $this->dataPost['amount_tp_thn_suggest'],
				'note_tp_thn' => $this->dataPost['note_tp_thn'],
				'status' => (int)$status,
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail,
			];
		}

		// TP THN Gửi lên cấp cao
		if ($status == 6) {
			$array_update = [
				'image_exemption_profile' => $this->dataPost['image_file'],
				'amount_tp_thn_suggest' => $this->dataPost['amount_tp_thn_suggest'],
				'note_tp_thn' => $this->dataPost['note_tp_thn'],
				'user_receive_approve' => $this->dataPost['user_receive_approve'],
				'user_receive_cc' => $this->dataPost['user_receive_cc'],
				'status' => (int)$status,
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail,
			];
		}

		if (!empty($id_exemption_post)) {
			$this->exemptions_model->update(
				['_id' => new \MongoDB\BSON\ObjectId($id_exemption_post)],
				$array_update
			);
			$log = [
				'type' => 'application_exemptions',
				'code_contract' => $this->dataPost['code_contract'],
				'ky_tra' => $this->dataPost['ky_tra'],
				'exemptions_id' => $this->dataPost['id_exemption'],
				'old' => $old_exemption_contract,
				'new' => $array_update,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail,
			];
			if ($this->dataPost['position'] == "lead") {
				if ($status == 2) {
					$log['action'] = 'lead_thn_cancel_exemption_application';
				} elseif ($status == 3) {
					$log['action'] = 'lead_thn_return';
				} elseif ($status == 4) {
					$log['action'] = 'lead_thn_confirm';
				}
			} elseif ($this->dataPost['position'] == "tp") {
				if ($status == 8) {
					$log['action'] = 'tp_thn_return';
				} elseif ($status == 2) {
					$log['action'] = 'tp_thn_cancel_exemption_application';
				}
			} elseif ($this->dataPost['position'] == "qlcc") {
				if ($status == 7) {
					$log['action'] = 'qlcc_confirm';
				} elseif ($status == 9) {
					$log['action'] = 'qlcc_return';
				}
			}
			if (isset($status_update) && $status_update == 1) {
				$log['action'] = 'update_exemption_application';
			}
			if ($status == 5) {
				$log['action'] = 'tp_thn_confirm';
			}
			if ($status == 6) {
				$log['action'] = 'tp_thn_send_up_qlcc';
			}

			$this->log_exemptions_model->insert($log);
		}

		if (!empty($id_exemption_post)) {
			$exemption_contract = $this->exemptions_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_exemption_post)]);
		} else {
			$exemption_contract = $this->exemptions_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_exemption_insert_return)]);
		}
		// Gửi thông báo tới user liên quan
		$note = '';
		$array_id_user_receive_message = [];
		$link_detail = 'accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract';
		$link_detail_update = 'accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_update_exemption_contract';

		if ($status == 1 || $status_update == 1) {
			$note = 'Chờ Lead THN xử lý đơn miễn giảm';
			$array_id_user_receive_message = $this->getGroupRole_lead_THN();
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract, $status_update);
		} elseif (in_array($status,[3,8,9])) {
			if ($this->dataPost['position'] == "lead") {
				$note = 'Lead THN trả về đơn miễn giảm';
			} elseif ($this->dataPost['position'] == "tp") {
				$note = 'TP THN không chấp nhận đơn miễn giảm';
			} elseif ($this->dataPost['position'] == "qlcc") {
				$note = 'QLCC không chấp nhận đơn miễn giảm';
			}

			$user_created = $this->user_model->findOne(array('email' => $exemption_contract['created_profile_by']));
			$array_id_user_receive_message[] = (string)$user_created['_id'];
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail_update, $exemption_contract);
		} elseif ($status == 4) {
			$note = 'Chờ TP THN xử lý đơn miễn giảm';
			$array_id_user_receive_message = $this->getGroupRole_TP_THN();
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract);
		} elseif ($status == 5) {
			$note = 'TP THN đã duyệt đơn miễn giảm';
			$user_created = $this->user_model->findOne(array('email' => $exemption_contract['created_profile_by']));
			$id_user_create[] = (string)$user_created['_id'];
			$id_leads_thn = $this->getGroupRole_lead_THN();
			$array_id_user_receive_message = array_merge($id_user_create,$id_leads_thn);
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract);
		} elseif ($status == 6) {
			$note = 'Chờ QLCC duyệt đơn miễn giảm';
			$array_id_user_receive_message = $this->dataPost['user_receive_approve'];
			$array_id_user_receive_cc_message = $this->dataPost['user_receive_cc'];
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract);

			$this->sendEmailApproveExemptionContract($exemption_contract, $status, $array_id_user_receive_message);
			$this->sendEmailCcExemptionContract($exemption_contract, $status, $array_id_user_receive_cc_message);
		} elseif ($status == 7) {
			$note = 'QLCC đã duyệt đơn miễn giảm';
			$user_created = $this->user_model->findOne(array('email' => $exemption_contract['created_profile_by']));
			$array_id_user_receive_message[] = (string)$user_created['_id'];
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract);
		} elseif ($status == 2) {
			$note = 'Lead THN Hủy đơn miễn giảm';
			$user_created = $this->user_model->findOne(array('email' => $exemption_contract['created_profile_by']));
			$array_id_user_receive_message[] = (string)$user_created['_id'];
			$this->push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function getGroupRole_lead_THN()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'lead-thn'));
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

	public function get_group_role_high_manager_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'cap-cao-duyet-mien-giam'));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						$arr[$key] = $item;
					}
				}
			}
		}

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_group_role_cc_receive_email_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'cc-nhan-email-mien-giam'));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {
				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						$arr[$key] = $item;
					}
				}
			}
		}

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function getGroupRole_TP_THN()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'tbp-thu-hoi-no'));
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

	public function get_all_application_exemptions_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post()['condition'];
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
		$id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
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
		$data_exemption = $this->exemptions_model->get_all(array(), $condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->exemptions_model->get_all(array(), $condition);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data_exemption,
			'total' => $total
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_one_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$contract_exemptions = $this->exemptions_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_exemptions
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$contract_exemptions = $this->exemptions_model->find_where(['code_contract' => $this->dataPost['code_contract']]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'contract' => $contract_exemptions
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_by_id_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id_contract'] = $this->security->xss_clean($this->dataPost['id_contract']);
		$contract_exemptions = $this->exemptions_model->find_where(['id_contract' => $this->dataPost['id_contract']]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'contract' => $contract_exemptions
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_transaction_discount_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['ky_tra_hien_tai'] = $this->security->xss_clean($this->dataPost['ky_tra_hien_tai']);

		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : '';
		$ky_tra_hien_tai = !empty($this->dataPost['ky_tra_hien_tai']) ? (int)$this->dataPost['ky_tra_hien_tai'] : '';
		$transaction_discount = $this->transaction_model->find_where(['code_contract' => $code_contract,'ky_tra_hien_tai' => $ky_tra_hien_tai, "type" => array('$in' => array(4)), "status" => array('$ne' => 3)]);
		$transaction_discount_finish = $this->transaction_model->find_where(['code_contract' => $code_contract,'ky_tra_hien_tai' => $ky_tra_hien_tai, "type" => array('$in' => array(3)), "status" => array('$ne' => 3)]);
		if (!empty($transaction_discount)) {
			$check_discount = false;
			foreach ($transaction_discount as $key => $tran) {
				if ($tran->discounted_fee > 0) {
					$check_discount = true;
				}
			}
		}
		if (!empty($transaction_discount_finish)) {
			$check_discount_finish = false;
			foreach ($transaction_discount as $key1 => $tran_finish) {
				if ($tran_finish->discounted_fee > 0) {
					$check_discount_finish = true;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction_discount,
			'check_discount' => $check_discount,
			'check_discount_finish' => $check_discount_finish,

		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_log_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$contract_exemptions = $this->log_exemptions_model->find_where(['code_contract' => $this->dataPost['code_contract']]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'contract' => $contract_exemptions
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function restore_exemption_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id_exemption'] = $this->security->xss_clean($this->dataPost['id_exemption']);
		$this->dataPost['id_contract'] = $this->security->xss_clean($this->dataPost['id_contract']);
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['type_payment_exem'] = $this->security->xss_clean($this->dataPost['type_payment_exem']);

		$array_update = [
			'status' => 3,
			'type_payment_exem' => $this->dataPost['type_payment_exem'],
			'updated_at' => $this->createdAt,
			'updated_by' => $this->uemail,
		];

		$this->exemptions_model->update(
			['_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id_exemption'])],
			$array_update
		);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => ''
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_current_period_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$ky_tra_hien_tai = 0;
		$ngay_den_han = 0;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$contract = $this->temporary_plan_contract_model->find_where(['code_contract' => $this->dataPost['code_contract']]);
		$contract_tempo = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $this->dataPost['code_contract'], 'status' => 1]);
		$contract_tempo_all = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($this->dataPost['code_contract']);
		$ky_tra_hien_tai = !empty($contract_tempo[0]['ky_tra']) ? intval($contract_tempo[0]['ky_tra']) : intval($contract_tempo_all[0]['ky_tra']);
		$ngay_den_han = !empty($contract_tempo[0]['ngay_ky_tra']) ? intval($contract_tempo[0]['ngay_ky_tra']) : intval($contract_tempo_all[0]['ngay_ky_tra']);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'contract' => $contract,
			'ky_tra_hien_tai' => $ky_tra_hien_tai,
			'ngay_den_han' => $ngay_den_han,
		];
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

	private function push_notification_exemption_to_thn($array_id_user_receive_message, $status, $note, $link_detail, $exemption_contract, $status_update = '')
	{
		foreach ($array_id_user_receive_message as $key => $id_user) {
			if (!empty($id_user)) {
				$data_notification = [
					'action_id' => (string)$exemption_contract['_id'],
					'action' => 'contract_exemptions',
					'detail' => $link_detail,
					'title' => $exemption_contract['customer_name'] . ' - ' . $exemption_contract['store']['name'],
					'note' => $note,
					'user_id' => $id_user,
					'status' => 1, //1: new, 2 : read, 3: block,
					'status_exemption_contract' => $status,
					'type_notification' => 1, //1: thông báo miễn giảm,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$code_contract = $exemption_contract['code_contract'];
				$code_contract_disbursement = $exemption_contract['code_contract_disbursement'];
				$customer_name = $exemption_contract['customer_name'];
				$this->notification_model->insertReturnId($data_notification);
				$device = $this->device_model->find_where(['user_id' => $id_user]);

				if (!empty($device) && $id_user == $device[0]['user_id']) {
					$badge = $this->get_count_notification_user($id_user);
					$fcm = new Fcm();
					$to = [];
					foreach ($device as $de) {
						$to[] = $de->device_token;
					}
					if ($status == 1) {
						$fcm->setTitle('Chờ Lead THN xử lý đơn miễn giảm! ');
					} elseif ($status_update == 1) {
						$fcm->setTitle('Chờ Lead THN duyệt lại đơn miễn giảm! ');
					} elseif ($status == 2) {
						$fcm->setTitle('Đơn miễn giảm đã bị hủy! ');
					} elseif ($status == 3) {
						$fcm->setTitle('Lead THN yêu cầu bổ sung hồ sơ! ');
					} elseif ($status == 4) {
						$fcm->setTitle('Chờ TP THN duyệt đơn miễn giảm! ');
					} elseif ($status == 5) {
						$fcm->setTitle('TP THN đã duyệt đơn miễn giảm! ');
					} elseif ($status == 6) {
						$fcm->setTitle('Chờ QLCC duyệt đơn miễn giảm! ');
					} elseif ($status == 7) {
						$fcm->setTitle('QLCC đã duyệt đơn miễn giảm! ');
					} elseif ($status == 8) {
						$fcm->setTitle('TP THN yêu cầu bổ sung hồ sơ! ');
					} elseif ($status == 9) {
						$fcm->setTitle('QLCC yêu cầu bổ sung hồ sơ! ');
					}

					$fcm->setMessage("HĐ: $code_contract_disbursement, KH: $customer_name");
					if (in_array($status,[3,8,9])) {
//						$click_action = 'http://localhost/tienngay/cpanel.tienngay/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_update_exemption_contract';
//						$click_action = 'https://sandboxcpanel.tienngay.vn/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_update_exemption_contract';
						$click_action = 'https://cpanel.tienngay.vn/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_update_exemption_contract';
					} else {
//						$click_action = 'http://localhost/tienngay/cpanel.tienngay/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract';
//						$click_action = 'https://sandboxcpanel.tienngay.vn/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract';
						$click_action = 'https://cpanel.tienngay.vn/accountant/view_v2?id=' . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract';

					}

					$fcm->setClickAction($click_action);
					$fcm->setBadge($badge);
					$message = $fcm->getMessage();
					$result = $fcm->sendToTopicCpanel($to, $message, $message);

				}
			}
		}
	}

	private function get_count_notification_user($user_id)
	{
		$condition = [];
		$condition['user_id'] = (string)$user_id;
		$condition['type_notification'] = 1;
		$condition['status'] = 1;
		$unRead = $this->notification_model->get_count_notification_user($condition);
		return $unRead;
	}

	private function sendEmailApproveExemptionContract($exemption_contract, $status, $email_receive)
	{
		$status_text = "";
		$id = (string)$exemption_contract["_id"];

		if ($status == 6) {
			$status_text = "Chờ quản lý cấp cao xử lý đơn miễn giảm";
		}
		$data_send_email = array(
			'code' => "vfc_send_email_approve_exemption_contract",
			'customer_name' => $exemption_contract['customer_name'],
			'code_contract_disbursement' => $exemption_contract['code_contract_disbursement'],
			'amount_tp_thn_suggest' => number_format($exemption_contract['amount_tp_thn_suggest']),
			'status' => $status_text,
//			'url' => "https://sandboxcpanel.tienngay.vn/accountant/view_v2?id=" . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract',
			'url' => "https://cpanel.tienngay.vn/accountant/view_v2?id=" . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract',
		);

		foreach ($email_receive as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data_send_email['email'] = "$value";
				$data_send_email['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data_send_email);
//				$this->sendEmail($data_send_email);
			}
		}
		return;
	}

	private function sendEmailCcExemptionContract($exemption_contract, $status, $email_receive)
	{
		$status_text = "";
		$id = (string)$exemption_contract["_id"];

		if ($status == 6) {
			$status_text = "Chờ quản lý cấp cao xử lý đơn miễn giảm";
		}
		$data_send_email = array(
			'code' => "vfc_send_cc_exemption_contract",
			'customer_name' => $exemption_contract['customer_name'],
			'code_contract_disbursement' => $exemption_contract['code_contract_disbursement'],
			'amount_tp_thn_suggest' => number_format($exemption_contract['amount_tp_thn_suggest']),
			'status' => $status_text,
//			'url' => "https://sandboxcpanel.tienngay.vn/accountant/view_v2?id=" . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract',
			'url' => "https://cpanel.tienngay.vn/accountant/view_v2?id=" . $exemption_contract['id_contract'] . '#tab_content_history_exemption_contract',
		);

		foreach ($email_receive as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data_send_email['email'] = "$value";
				$data_send_email['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data_send_email);
//				$this->sendEmail($data_send_email);
			}
		}
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
			"created_at" => (int)$this->createdAt
		);

		//var_dump('expression');

		$this->email_history_model->insert($data);
		return;

	}

	public function getEmailStr($emailTemplate, $filter)
	{
		foreach ($filter as $key => $value) {
			$emailTemplate = str_replace("{" . $key . "}", $value, $emailTemplate);
		}
		return $emailTemplate;
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

	public function noti_cskh_thoi_gian_khach_hen_post(){
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = [];

		$create_at = date('Y-m-d H:i:s');

		$cenvertedTime = date('Y-m-d H:i:s',strtotime('+5 minutes',strtotime($create_at)));

		$condition['cenvertedTime'] = strtotime($cenvertedTime);

		$leadData = $this->lead_model->get_thoi_gian_khach_hen($condition);

		if (!empty($leadData)){
			foreach ($leadData as $value){

				if (!empty($value['cskh'])){
					$user_id = $this->user_model->findOne(['email' => $value['cskh']]);
					if (!empty($user_id)){
						$id_user = (string)$user_id['_id'];

						if (!empty($id_user)) {
							$device = $this->device_model->find_where(['user_id' => $id_user]);
							if (!empty($device) && $id_user == $device[0]['user_id']) {
								$badge = $this->get_count_notification_user($id_user);
								$fcm = new Fcm();
								$to = [];
								foreach ($device as $de) {
									$to[] = $de->device_token;
								}
								$fcm->setTitle('Khách hàng ' . $value['fullname'] .  " hẹn gọi lại! ");

//								$click_action = 'http://localhost/tienngay/cpanel.tienngay/lead_custom?tab=6';
								$click_action = 'https://lms.tienngay.vn/lead_custom?tab=6';
//								$click_action = 'https://sandboxcpanel.tienngay.vn/lead_custom?tab=6';

								$fcm->setClickAction($click_action);
								$fcm->setMessage("Thời gian: " . date('d/m/Y H:i:s', $value['thoi_gian_khach_hen'] ) . ", SĐT: " .  hide_phone($value['phone_number']));
								$fcm->setBadge($badge);
								$message = $fcm->getMessage();
								$result = $fcm->sendToTopicCpanel($to, $message, $message);

								$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])), ['status_thoi_gian_khach_hen' => "2"]);


							}
						}
					}
				}

			}



		}
	}

	public function check_update_noti_kpi_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles)){
			if (in_array("quan-ly-cap-cao", $groupRoles)){
				$check_kpi_area = $this->kpi_area_model->find_where(["year" => date('Y'), "month" => date('m')]);

				if (empty($check_kpi_area)){
					$click_action = "kpi/listKPI_area";
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'click_action' => $click_action
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
				}
				return;
			}

			if (in_array("quan-ly-khu-vuc",$groupRoles)){
				$stores = $this->getStores_list($this->id);
				if (!empty($stores)){
					$check_kpi_pgd = $this->kpi_pgd_model->find_where(["year" => date('Y'), "month" => date('m'),'store.id' => $stores[0]]);
					if (empty($check_kpi_pgd)){
						$click_action = "kpi/listKPI_pgd";
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'click_action' => $click_action
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
					}
				}
				return;
			}

			if (in_array("cua-hang-truong", $groupRoles)){
				$stores = $this->getStores_list($this->id);
				if (!empty($stores)){
					$check_kpi_gdv = $this->kpi_gdv_model->find_where(["year" => date('Y'), "month" => date('m'),'store.id' => $stores[0]]);
					if (empty($check_kpi_gdv)){
						$click_action = "kpi/listKPI_gdv";
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'click_action' => $click_action
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
					}
				}
				return;
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_BAD_REQUEST,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function push_noti_kpi($id_user, $setTitle, $click_action){

			$device = $this->device_model->find_where(['user_id' => $id_user]);
			if (!empty($device) && $id_user == $device[0]['user_id']) {
				$badge = $this->get_count_notification_user($id_user);
				$fcm = new Fcm();
				$to = [];
				foreach ($device as $de) {
					$to[] = $de->device_token;
				}
				$fcm->setTitle("Vui lòng cài đặt Kpi " . $setTitle);

				$fcm->setClickAction($click_action);
				$fcm->setMessage("Thời gian: Set Kpi tháng " . date('m'));
				$fcm->setBadge($badge);
				$message = $fcm->getMessage();
				$result = $fcm->sendToTopicCpanel($to, $message, $message);


		}

	}

	private function getGroupRole_check($slug)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => $slug));

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
		return array_unique($arr);
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

	private function getUserbyStores($storeId)
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
					foreach ($storeId as $s){

						if (in_array($s, $arrStores) == TRUE) {
							if (!empty($role['stores'])) {
								//Push store

								foreach ($role['users'] as $key => $item) {
									foreach ($item as $e){
										array_push($roleAllUsers, $e->email);
									}
								}

							}
						}
					}

				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	public function exportExcelExemption_post()
	{

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$data_exemption = $this->exemptions_model->exportExcelExemption($condition);

		if (!empty($data_exemption)) {
			foreach ($data_exemption as $value) {
				$dataContract = $this->contract_model->find_one_select(['code_contract' => $value['code_contract']], ['disbursement_date', 'loan_infor.type_interest', 'status', 'expire_date', 'loan_infor.amount_money','original_debt.du_no_goc_con_lai','debt.so_ngay_cham_tra','store']);
				if (!empty($dataContract)) {
					$value['type_interest'] = $dataContract['loan_infor']['type_interest'];
					$value['disbursement_date'] = $dataContract['disbursement_date'];
					$value['statusContract'] = $dataContract['status'];
					$value['expire_date'] = $dataContract['expire_date'];
					$value['amount_money'] = $dataContract['loan_infor']['amount_money'];
					$value['tong_tien_goc_con'] = $dataContract['original_debt']['du_no_goc_con_lai'];
					$value['bucket'] = $dataContract['debt']['so_ngay_cham_tra'];
					$value['store'] = $dataContract['store']['name'];
				}

				//Tổng tiền đã thu trước thời điểm tất toán
				$value['totalTran'] = 0;
				$total_where = $this->transaction_model->find_where_select(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [4, 5])], ['total']);
				if(!empty($total_where)){
					foreach ($total_where as $item){
						$value['totalTran'] += (int)$item['total'];
					}
				}

				//Tổng cần thu (gốc + lãi + phí)
				$value['total_tong_can_thu'] = $this->transaction_model->sum_where(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [3])], '$amount_total');

				//Khách hàng thanh toán tại ngày tất toán
				$value['total_thanh_toan_tat_toan'] = $this->transaction_model->sum_where(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [3])], '$total');

			}
		}

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data_exemption,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	//lấy hết hợp đồng đã được duyệt giảm (blackList)
	public function getAllContractExempted_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		// var_dump($this->dataPost); die;
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$store_id = !empty($this->dataPost['store_id']) ? $this->dataPost['store_id'] : "";
		$id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "17";
		$status_disbursement = !empty($this->dataPost['status_disbursement']) ? $this->dataPost['status_disbursement'] : "";
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = [
				'start' => $start,
				'end' => $end
			];
		} else if (!empty($start)) {
			$condition = [
				'start' => $start,
			];
		} else if (!empty($end)) {
			$condition = [
				'end' => $end,
			];
		}
		// var_dump($condition); die;
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
			$condition['store_id'] = $store_id;
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
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = trim($customer_identify);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('cua-hang-truong', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('giao-dich-vien', $groupRoles)) {
			$all = true;
		}
		if (!empty($code_contract_disbursement) || !empty($customer_name) || !empty($customer_phone_number)) {
			$all = false;
		}
		// if ($all) {
		// 	$stores = $this->getStores($this->id);
		// 	if (empty($stores)) {
		// 		$response = array(
		// 			'status' => REST_Controller::HTTP_OK,
		// 			'data' => array()
		// 		);
		// 		$this->set_response($response, REST_Controller::HTTP_OK);
		// 		return;
		// 	}
		// 	$condition['stores'] = $stores;
		// }
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$contract = array();
		$data_exemption = $this->exemptions_model->getAllContractExempted(array(), $condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->exemptions_model->getAllContractExempted(array(), $condition);


		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data_exemption,
			'total' => $total
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	//insert thêm customer_identify (nếu bản ghi nào chưa có) 
	public function insertIdentify_post() {
		$data = $this->input->post();
		$getContractExemption = $this->exemptions_model->find_where(['status' => ['$in' => [1,2,3,4,5,6,7,8,9]]]);
		foreach($getContractExemption as $c) {
			$codeContract[] =  $c['code_contract'];
		}
		//lấy trong table contract
		$contracts = $this->contract_model->find_where(['code_contract' => ['$in' => $codeContract]]);
		foreach ($contracts as $key => $contract) {
			if (empty($c['customer_identify'])) {
				$this->exemptions_model->update(['code_contract' => $contract['code_contract']],
				[
					"customer_identify" => $contract['customer_infor']['customer_identify'],
				]);
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			"messages" => "OK",
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//xuất excel đơn miễn giảm
	public function exportExcelExempted_post()
	{
		$dataPost = $this->input->post();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$customer_name = !empty($dataPost['customer_name']) ? $dataPost['customer_name'] : "";
		$customer_identify = !empty($dataPost['customer_identify']) ? $dataPost['customer_identify'] : "";
		$customer_phone_number = !empty($dataPost['customer_phone_number']) ? $dataPost['customer_phone_number'] : "";
		$code_contract = !empty($dataPost['code_contract']) ? $dataPost['code_contract'] : "";
		$code_contract_disbursement = !empty($dataPost['code_contract_disbursement']) ? $dataPost['code_contract_disbursement'] : "";
		$store = !empty($dataPost['store_id']) ? $dataPost['store_id'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = [
				'start' => $start,
				'end' => $end
			];
		} else if (!empty($start)) {
			$condition = [
				'start' => $start
			];
		} else if (!empty($end)) {
			$condition = [
				'end' => $end
			];
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
		if (!empty($store)) {
			$condition['store_id'] = $store;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = trim($customer_identify);
		}

		$data_exempted = $this->exemptions_model->exportExcelExempted($condition);
		if (!empty($data_exempted)) {
			foreach ($data_exempted as $value) {
				$dataContract = $this->contract_model->find_one_select(['code_contract' => $value['code_contract']], ['disbursement_date', 'loan_infor.type_interest', 'status', 'expire_date', 'loan_infor.amount_money','original_debt.du_no_goc_con_lai','debt.so_ngay_cham_tra','store']);
				if (!empty($dataContract)) {
					$value['type_interest'] = $dataContract['loan_infor']['type_interest'];
					$value['disbursement_date'] = $dataContract['disbursement_date'];
					$value['statusContract'] = $dataContract['status'];
					$value['expire_date'] = $dataContract['expire_date'];
					$value['amount_money'] = $dataContract['loan_infor']['amount_money'];
					$value['tong_tien_goc_con'] = $dataContract['original_debt']['du_no_goc_con_lai'];
					$value['bucket'] = $dataContract['debt']['so_ngay_cham_tra'];
					$value['store'] = $dataContract['store']['name'];
				}
				//Tổng tiền đã thu trước thời điểm tất toán
				$value['totalTran'] = 0;
				$total_where = $this->transaction_model->find_where_select(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [4, 5])], ['total']);
				if(!empty($total_where)){
					foreach ($total_where as $item){
						$value['totalTran'] += (int)$item['total'];
					}
				}
				//Tổng cần thu (gốc + lãi + phí)
				$value['total_tong_can_thu'] = $this->transaction_model->sum_where(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [3])], '$amount_total');
				//Khách hàng thanh toán tại ngày tất toán
				$value['total_thanh_toan_tat_toan'] = $this->transaction_model->sum_where(['code_contract' => $value['code_contract'], 'status' => 1, 'type' => array('$in' => [3])], '$total');
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data_exempted,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getContractExemption_post()
	{
		$data = $this->input->post();
		$id = $data['id_contract'];
		$contract = $this->exemptions_model->find_where(['id_contract' => $id, 'status' => ['$in' => [5,7]]]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			"messages" => "OK",
			"data" => $contract,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getIdentify_post()
	{
		$data = $this->input->post();
		$id = $data['id_contract'];
		$arrIdentify = false;
		$customer_identify = $data['customer_identify'];
		$contract = $this->contract_model->findOne(['_id' =>  new \MongoDB\BSON\ObjectId($id)]);
		if(!empty($contract)) {
			$contract_debt = $this->exemptions_model->findOne(['customer_identify' => $contract['customer_infor']['customer_identify']]);
			if (!empty($contract_debt)) {
				$arrIdentify = true;
			}
		}	
		$response = [
			'status' => REST_Controller::HTTP_OK,
			"messages" => "OK",
			"data" => $arrIdentify,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Contract_ksnb extends REST_Controller
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
		$this->load->model('contract_ksnb_model');
		$this->load->model('log_ksnb_model');
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


	public function process_create_contract_ksnb_post()
	{

		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);

		if (empty($this->dataPost['code_contract_disbursement_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check_contract = $this->contract_ksnb_model->find_where(array("code_contract_disbursement_value" => $this->dataPost['code_contract_disbursement_value']));

		if (!empty($check_contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng đã được chọn"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		}

		$this->dataPost['created_at'] = $this->createdAt;

		$contractId = $this->contract_ksnb_model->insertReturnId($this->dataPost);

//		$log = array(
//			"type" => "contract_ksnb",
//			"action" => "create",
//			"contract_ksnb_id" => $contractId,
//			"contract_ksnb" => $this->dataPost,
//			"created_at" => $this->createdAt,
//			"created_by" => $this->uemail
//		);
//		$this->log_ksnb_model->insert($log);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function add_contract_ksnb_post()
	{

		$this->dataPost['code_contract_disbursement_value'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement_value']);

		$check_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $this->dataPost['code_contract_disbursement_value']));

		if (!empty($check_contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Success",
				'data' => $check_contract

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


	}

	public function get_count_all_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";

		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

		$contract_count = $this->contract_ksnb_model->getCountByRole($condition);

		if (empty($contract_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function get_all_post()
	{

		$this->dataPost = $this->input->post();
		$condition = [];

		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";

		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;


		$contract = $this->contract_ksnb_model->getDataByRole($condition, $per_page, $uriSegment);
		if (empty($contract)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function process_update_description_img_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;


		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['contract_ksnb'] = $this->security->xss_clean(!empty($this->dataPost['contract_ksnb']) ? $this->dataPost['contract_ksnb'] : array());


		$arrUpdate = array("contract.image_accurecy.contract_ksnb" => $this->dataPost['contract_ksnb']);
		$this->contract_ksnb_model->update(
			array("contract._id" => $this->dataPost['id']),
			$arrUpdate
		);

//		$contract_ksnb = $this->contract_ksnb_model->findOne(array("contract._id" => $this->dataPost['id']));
//
//		$log = array(
//			"type" => "contract_ksnb",
//			"action" => "update_img",
//			"contract_ksnb_id" => (string)$contract_ksnb['_id'],
//			"old" => $contract_ksnb,
//			"new" => $this->dataPost,
//			"created_at" => $this->createdAt,
//			"created_by" => $this->uemail
//		);
//		$this->log_ksnb_model->insert($log);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
			'data' => $arrUpdate
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_image_accurecy_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$dataDB = $this->contract_ksnb_model->findOne(array("contract._id" => $data['id']));

		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataDB['contract']['image_accurecy']['contract_ksnb'],
			'contract_status' => $dataDB['status']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_note_post()
	{

		$data = $this->input->post();

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);


		if (empty($this->dataPost['note'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$contract_ksnb = $this->transaction_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$log = array(
			"type" => "contract_ksnb",
			"action" => "update_note",
			"contract_ksnb_id" => (string)$this->dataPost['id'],
			"old" => $contract_ksnb,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log);

		$this->transaction_model->update(array("_id" => $contract_ksnb['_id']), ["note_ksnb" => $this->dataPost['note']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success Note"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function payment_contract_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['amount_debt_cc'] = $this->security->xss_clean($this->dataPost['amount_debt_cc']);
		$this->dataPost['amount_cc'] = $this->security->xss_clean($this->dataPost['amount_cc']);
		$this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']);
		$this->dataPost['valid_amount'] = (float)$this->security->xss_clean($this->dataPost['valid_amount']);
		$this->dataPost['fee_reduction'] = (float)$this->security->xss_clean($this->dataPost['fee_reduction']);
		if (isset($this->dataPost['amount_total'])) {
			$this->dataPost['amount_total'] = (float)$this->security->xss_clean($this->dataPost['amount_total']);
		} else {
			$this->dataPost['amount_total'] = (float)$this->dataPost['valid_amount'];
		}
		$this->dataPost['valid_amount'] = (float)$this->dataPost['valid_amount'] - $this->dataPost['fee_reduction'];
		$this->dataPost['reduced_fee'] = (float)$this->security->xss_clean($this->dataPost['reduced_fee']);
		$this->dataPost['discounted_fee'] = (float)$this->security->xss_clean($this->dataPost['discounted_fee']);
		$this->dataPost['other_fee'] = (float)$this->security->xss_clean($this->dataPost['other_fee']);
		$this->dataPost['phi_phat_sinh'] = (float)$this->security->xss_clean($this->dataPost['phi_phat_sinh']);

		$this->dataPost['penalty_pay'] = (float)$this->security->xss_clean($this->dataPost['penalty_pay']);
		$this->dataPost['name'] = $this->security->xss_clean($this->dataPost['name']);
		$this->dataPost['name_relative'] = $this->security->xss_clean($this->dataPost['name_relative']);
		$this->dataPost['phone'] = $this->security->xss_clean($this->dataPost['phone']);
		$this->dataPost['type_payment'] = $this->security->xss_clean($this->dataPost['type_payment']);
		$this->dataPost['payment_method'] = $this->security->xss_clean($this->dataPost['payment_method']);
		$this->dataPost['type_pt'] = $this->security->xss_clean($this->dataPost['type_pt']);
		$this->dataPost['note'] = isset($this->dataPost['note']) ? $this->dataPost['note'] : '';
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']); // cua hang
		$this->dataPost['date_pay'] = isset($this->dataPost['date_pay']) ? strtotime($this->dataPost['date_pay'] . date(' H:i:s')) : '';
		$this->dataPost['amount'] = (float)$this->dataPost['amount'];
		$this->dataPost['amount_total'] = (float)$this->dataPost['amount'] + $this->dataPost['fee_reduction'];
		unset($this->dataPost['secret_key']);

		//Check null
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['amount']) && $this->dataPost['type_payment'] == 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền thanh toán không thể trống"

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['type_pt'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Loại thanh toán không thể trống",
				'data' => $this->dataPost
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['store'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin phòng giao dịch lập phiếu thu không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		// if(empty($this->dataPost['name'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Tên người thanh toán không thể trống"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }
		// if(empty($this->dataPost['phone'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "SDT người thanh toán không thể trống"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }
		if (empty($this->dataPost['payment_method'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Phương thức thanh toán không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$tat_toan = $this->transaction_model->count(array('code_contract' => $this->dataPost['code_contract'], 'status' => array('$ne' => 3), 'type' => 3));
		if ($tat_toan > 0 && $this->dataPost['type_pt'] == 3) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch tất toán đã tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$status = 3;
		if ((int)$this->dataPost['payment_method'] == 1) {
			$status = 1;
		} else if ((int)$this->dataPost['payment_method'] == 2) {
			$status = 2; // banking
		} else {
			$status = 3; // khong xac dinh
		}

		$contractDB = $this->contract_model->findOne(array('code_contract' => $this->dataPost['code_contract']));

		//Insert data
		$code = 'PT_' . date("Ymd") . '_' . uniqid();
		$data_transaction = array(
			"code_contract" => !empty($contractDB) && !empty($contractDB['code_contract']) ? $contractDB['code_contract'] : "",
			"code_contract_disbursement" => !empty($contractDB) && !empty($contractDB['code_contract_disbursement']) ? $contractDB['code_contract_disbursement'] : "",
			"customer_name" => !empty($contractDB) ? $contractDB['customer_infor']['customer_name'] : "",
			"total" => isset($this->dataPost['amount']) ? $this->dataPost['amount'] : 0, // số tiền khách hang đưa
			"amount_total" => isset($this->dataPost['amount_total']) ? $this->dataPost['amount_total'] : 0,// tổng số tiền phải thanh toán
			"valid_amount" => isset($this->dataPost['valid_amount']) ? $this->dataPost['valid_amount'] : 0,// tổng số tiền hợp lệ thanh toán
			"reduced_fee" => isset($this->dataPost['reduced_fee']) ? $this->dataPost['reduced_fee'] : 0,// tổng số tiền phí giảm ngân hàng
			"total_deductible" => isset($this->dataPost['fee_reduction']) ? $this->dataPost['fee_reduction'] : 0,// tổng số tiền giảm
			"discounted_fee" => isset($this->dataPost['discounted_fee']) ? $this->dataPost['discounted_fee'] : 0,// tổng số tiền phí giảm trừ
			"other_fee" => isset($this->dataPost['other_fee']) ? $this->dataPost['other_fee'] : 0,// tổng số tiền phí giảm khác
			"fee_reduction" => isset($this->dataPost['fee_reduction']) ? $this->dataPost['fee_reduction'] : 0,// tổng số tiền giảm
			"penalty_pay" => isset($this->dataPost['penalty_pay']) ? $this->dataPost['penalty_pay'] : 0,// tổng số tiền phạt

			"code" => $code,
			"type" => (int)$this->dataPost['type_pt'], //3 tat toan. 4 thanh toan ky lai. 5 gia han hop dong
			"code_contract" => !empty($contractDB) && !empty($contractDB['code_contract']) ? $contractDB['code_contract'] : "",
			"payment_method" => $this->dataPost['payment_method'],
			"store" => $this->dataPost['store'],
			"date_pay" => (int)$this->dataPost['date_pay'],
			"status" => 4,
			"status_ksnb" => 1,
			"customer_bill_phone" => $this->dataPost['phone'],
			"customer_bill_name" => $this->dataPost['name'],
			"relative_with_contract_owner" => $this->dataPost['name_relative'],
			"note" => $this->dataPost['note'],
			"type_payment" => (int)$this->dataPost['type_payment'],
			"created_by" => $this->uemail,
			"created_at" => $this->createdAt,
		);
		if ($data_transaction['type_payment'] == 3) {
			$data_transaction["amount_debt_cc"] = (int)$this->dataPost['amount_debt_cc'];
			$data_transaction["amount_cc"] = (int)$this->dataPost['amount_cc'];
		}
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		//Write log
		$this->dataPost['status'] = 4;
		$log = array(
			"type" => "contract",
			"action" => "payment_ksnb",
			"transaction_ksnb_id" => (string)$transaction_id,
			"data_post" => $this->dataPost,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);

		$log_ksnb = array(
			"type" => "contract",
			"action" => "Kiểm soát nội bộ tạo phiếu thu",
			"transaction_ksnb_id" => (string)$transaction_id,
			"old" => $this->dataPost,
			"created_by" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_ksnb_model->insert($log_ksnb);

		$this->debt_recovery_one($this->dataPost['code_contract']);
		//Thanh toán lãi k
		$url = 'transaction/sendApprove?id=' . (string)$transaction_id . '&view=QLHDV';
		$url_printed = "transaction/printed_billing_contract/" . (string)$transaction_id;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Đang khởi tạo phiếu thu!',
			'url' => $url,
			'transaction_id' => (string)$transaction_id,
			'url_printed' => $url_printed

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
		//}
	}

	public function debt_recovery_one($code_contract)
	{

		$c = $this->contract_model->findOne(array('code_contract' => $code_contract));

		if (isset($c['code_contract'])) {
			$cond = array(
				'code_contract' => $c['code_contract'],

			);
		}
		$da_thanh_toan_pt = $this->transaction_model->sum_where(array('code_contract' => $c['code_contract'], 'status' => 1, 'type' => array('$in' => [4, 5])), '$total');
		$da_thanh_toan_gh_cc = $this->transaction_model->findOne(array('code_contract' => $c['code_contract'], 'status' => array('$ne' => 3), 'type_payment' => array('$gt' => 1)));
		$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
		$c['detail'] = array();
		if (!empty($detail)) {
			$total_paid = 0;
			$total_paid_1ky = 0;
			$total_goc = 0;
			$total_lai = 0;
			$total_phi = 0;
			$total_da_thanh_toan = 0;
			$total_phi_phat_cham_tra = 0;
			$time = 0;
			$n = 0;
			$ky_tt_xa_nhat = 0;
			$ky_tt_xa_nhi = 0;
			$thoi_han_vay = 0;

			foreach ($detail as $de) {

				$total_paid += (isset($de['tien_tra_1_ky'])) ? $de['tien_tra_1_ky'] : 0;
				$total_goc += (isset($de['tien_goc_1ky_con_lai'])) ? $de['tien_goc_1ky_con_lai'] : 0;
				$total_phi += (isset($de['tien_phi_1ky_con_lai'])) ? $de['tien_phi_1ky_con_lai'] : 0;
				$total_lai += (isset($de['tien_lai_1ky_con_lai'])) ? $de['tien_lai_1ky_con_lai'] : 0;
				$total_da_thanh_toan += $de['da_thanh_toan'];

				if ((count($detail) - 1) == $de['ky_tra'])
					$ky_tt_xa_nhi = $de['ngay_ky_tra'];

				if (count($detail) == $de['ky_tra'])
					$ky_tt_xa_nhat = $de['ngay_ky_tra'];

				$thoi_han_vay = $thoi_han_vay + $de['so_ngay'];
			}
			if (empty($ky_tt_xa_nhi))
				$ky_tt_xa_nhi = $c['disbursement_date'];
			$time = 0;
			$current_day = strtotime(date('m/d/Y'));
			$datetime = $current_day;
			$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $c['code_contract'], 'status' => 1]);
			if (!empty($detail)) {
				$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
				$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			}
			$penalty = $this->contract_model->get_phi_phat_cham_tra((string)$c['_id'], strtotime(date('Y-m-d') . ' 23:59:59'));
			$total_phi_phat_cham_tra = $penalty['penalty_now'] + $penalty['tong_penalty_con_lai'];
			$check_gia_han = 2;
			$check_tt_gh = 0;
			$check_tt_cc = 0;
			if ($current_day >= $ky_tt_xa_nhi && $c['loan_infor']['type_interest'] == 2 && strtotime(date('Y-m-d') . ' 00:00:00') >= $c['disbursement_date']) {
				$check_gia_han = 1;
			}
			if (!empty($da_thanh_toan_gh_cc)) {
				if ($da_thanh_toan_gh_cc['status'] == 1 && $da_thanh_toan_gh_cc['type_payment'] == 2) {
					//đã thanh toán gia hạn
					$check_tt_gh = 1;
				}
				if (in_array($da_thanh_toan_gh_cc['status'], [2, 4]) && $da_thanh_toan_gh_cc['type_payment'] == 2) {
					//chờ thanh toán gia hạn
					$check_tt_gh = 2;
				}
				if ($da_thanh_toan_gh_cc['status'] == 1 && $da_thanh_toan_gh_cc['type_payment'] == 3) {
					//đã thanh toán gia hạn
					$check_tt_cc = 1;
				}
				if (in_array($da_thanh_toan_gh_cc['status'], [2, 4]) && $da_thanh_toan_gh_cc['type_payment'] == 3) {
					//chờ thanh toán gia hạn
					$check_tt_cc = 2;
				}

			}
			$data = [
				'expire_date' => $ky_tt_xa_nhat,
				'debt' => [
					'current_day' => $current_day,
					'ngay_ky_tra' => $datetime,
					'so_ngay_cham_tra' => $time,
					'tong_tien_phai_tra' => $total_paid,
					'tong_tien_goc_con' => $total_goc,
					'tong_tien_phi_con' => $total_phi,
					'tong_tien_lai_con' => $total_lai,
					'tong_tien_cham_tra_con' => $total_phi_phat_cham_tra,
					'tong_tien_da_thanh_toan' => $total_da_thanh_toan,
					'tong_tien_da_thanh_toan_pt' => $da_thanh_toan_pt,
					'ky_tt_xa_nhat' => $ky_tt_xa_nhat,
					'ky_tt_xa_nhi' => $ky_tt_xa_nhi + 24 * 60 * 60,
					'check_gia_han' => $check_gia_han,
					'check_tt_gh' => $check_tt_gh,
					'check_tt_cc' => $check_tt_cc,
					'thoi_han_vay' => $thoi_han_vay,
					'run_date' => date('d-m-Y H:i:s')
				]
			];
			$this->contract_model->update(
				array("_id" => $c['_id']),
				$data
			);
		}


		return 'OK';

	}

	public function get_log_one_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);

		$transaction = $this->log_ksnb_model->find_where(array("transaction_ksnb_id" => $this->dataPost['id']));

		if (empty($transaction)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}


}




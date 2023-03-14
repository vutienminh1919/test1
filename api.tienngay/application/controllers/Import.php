<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Import extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('investor_model');
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
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("sms_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('coupon_model');
		$this->load->model('verify_identify_contract_model');
		$this->load->model('personnel_model');
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
		date_default_timezone_set('Asia/Ho_Chi_Minh');

	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

	public function contract_update_status_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$start = !empty($this->dataPost['condition']['start']) ? $this->dataPost['condition']['start'] : "";
		$end = !empty($this->dataPost['condition']['end']) ? $this->dataPost['condition']['end'] : "";
		$is_change_dang_vay = !empty($this->dataPost['is_change_dang_vay']) ? $this->dataPost['is_change_dang_vay'] : "";
		$is_change_tat_toan = !empty($this->dataPost['is_change_tat_toan']) ? $this->dataPost['is_change_tat_toan'] : "";

		$code_contract_disbursement = !empty($this->dataPost['condition']['code_contract_disbursement']) ? $this->dataPost['condition']['code_contract_disbursement'] : "";
		$customer_name = !empty($this->dataPost['condition']['customer_name']) ? $this->dataPost['condition']['customer_name'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$contract = array();
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($is_change_dang_vay)) {
			$condition['is_change_dang_vay'] = $is_change_dang_vay;
		}
		if (!empty($is_change_tat_toan)) {
			$condition['is_change_tat_toan'] = $is_change_tat_toan;
		}
		$contract = $this->contract_model->getUpdate_status($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->contract_model->getUpdate_status($condition);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
    public function contract_import_gh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$start = !empty($this->dataPost['condition']['start']) ? $this->dataPost['condition']['start'] : "";
		$end = !empty($this->dataPost['condition']['end']) ? $this->dataPost['condition']['end'] : "";
	

		$code_contract_disbursement = !empty($this->dataPost['condition']['code_contract_disbursement']) ? $this->dataPost['condition']['code_contract_disbursement'] : "";
		$customer_name = !empty($this->dataPost['condition']['customer_name']) ? $this->dataPost['condition']['customer_name'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$contract = array();
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		$condition['is_giahan'] = 1;
		$contract = $this->contract_model->get_import_gh_cc($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->contract_model->get_import_gh_cc($condition);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	 public function contract_import_cc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$start = !empty($this->dataPost['condition']['start']) ? $this->dataPost['condition']['start'] : "";
		$end = !empty($this->dataPost['condition']['end']) ? $this->dataPost['condition']['end'] : "";
	

		$code_contract_disbursement = !empty($this->dataPost['condition']['code_contract_disbursement']) ? $this->dataPost['condition']['code_contract_disbursement'] : "";
		$customer_name = !empty($this->dataPost['condition']['customer_name']) ? $this->dataPost['condition']['customer_name'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$contract = array();
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		$condition['is_cocau'] = 1;
		$contract = $this->contract_model->get_import_gh_cc($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->contract_model->get_import_gh_cc($condition);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function process_create_contract_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']);
		$this->dataPost['fee'] = $this->security->xss_clean($this->dataPost['fee']);
		$this->dataPost['status'] = 17;
		$this->dataPost['type'] = "old_contract";
		$this->dataPost['status_disbursement'] = 2;
		$this->dataPost['created_by'] = $this->security->xss_clean($this->dataPost['created_by']);
		//Check null
		$lead = $this->lead_model->findOne_langding(array("phone_number" => $this->dataPost['customer_infor']['customer_phone_number']));
		if (isset($lead[0]['_id'])) {
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$last = 3 - $time;
			if ($time <= 8) {
				$this->dataPost['customer_infor']['id_lead'] = (string)$lead[0]['_id'];
				$this->dataPost['customer_infor']['customer_resources'] = "hoiso";
			}
		}

		//If new customer then create account for customer
		//Init mã hợp đồng
		$typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";

		//Init code_contract_number hợp đồng
		$resNumberCodeContract = $this->initNumberContractCode();
		//Insert contract model
		$this->dataPost['code_contract'] = "00000" . $resNumberCodeContract['max_number_contract'];
		$this->dataPost['number_contract'] = $resNumberCodeContract['max_number_contract'];
		$this->dataPost['store']['object_id'] = new MongoDB\BSON\ObjectId($this->dataPost['store']['id']);
		$code_contract_parent = $this->security->xss_clean($this->dataPost['code_contract_parent']);
		if (!empty($code_contract_parent)) {
			$this->dataPost['code_contract_parent'] = $code_contract_parent;
			$this->dataPost['count_extension'] = (int)$this->contract_model->count_in('code_contract_parent', array($code_contract_parent)) + 1;
		}
		$arrImages = array(
			"identify" => "",
			"household" => "",
			"driver_license" => "",
			"vehicle" => "",
			"expertise" => ""
		);
		$this->dataPost['image_accurecy'] = $arrImages;
//		$arrFee = $this->getFee();
//		$this->dataPost['fee'] = $arrFee;
		$this->dataPost['created_at'] = (int)$this->dataPost['created_at'];
		$this->dataPost['disbursement_date'] = (int)$this->dataPost['disbursement_date'];
		$this->dataPost['updated_at'] = (int)$this->dataPost['updated_at'];
		//udate dashboard
		//$dashboard = $this->process_update_dashboard($contractId, "import");
		$contractData = $this->contract_model->findOne(array("code_contract_disbursement" => trim($this->dataPost['code_contract_disbursement'])));
		if (empty($contractData)) {
			$contractId = $this->contract_model->insertReturnId($this->dataPost);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "create",
				"contract_id" => (string)$contractId,
				"old" => $this->dataPost,
				"created_at" => $this->createdAt,
				"created_by" => $this->dataPost['created_by']
			);

			$this->log_model->insert($insertLog);
		} else {


		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success",
			'db' => $this->dataPost['code_contract_disbursement'],
			//'dashboard' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_contract_import_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$this->dataPost['customer_infor'] = $this->security->xss_clean($this->dataPost['customer_infor']);
		$this->dataPost['current_address'] = $this->security->xss_clean($this->dataPost['current_address']);
		$this->dataPost['houseHold_address'] = $this->security->xss_clean($this->dataPost['houseHold_address']);
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['job_infor'] = $this->security->xss_clean($this->dataPost['job_infor']);
		$this->dataPost['relative_infor'] = $this->security->xss_clean($this->dataPost['relative_infor']);
		$this->dataPost['loan_infor'] = $this->security->xss_clean($this->dataPost['loan_infor']);
		$this->dataPost['property_infor'] = $this->security->xss_clean($this->dataPost['property_infor']);
		$this->dataPost['receiver_infor'] = $this->security->xss_clean($this->dataPost['receiver_infor']);
		$this->dataPost['expertise_infor'] = $this->security->xss_clean($this->dataPost['expertise_infor']);
		$this->dataPost['fee'] = $this->security->xss_clean($this->dataPost['fee']);
		$this->dataPost['import_update_contract'] = 1;

		$this->dataPost['disbursement_date'] = (int)$this->security->xss_clean($this->dataPost['disbursement_date']);
		$this->dataPost['investor_code'] = $this->security->xss_clean($this->dataPost['investor_code']);
		$this->dataPost['code_contract_disbursement'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement']);

		//If new customer then create account for customer
		$typeProperty = !empty($this->dataPost['loan_infor']['type_property']['code']) ? $this->dataPost['loan_infor']['type_property']['code'] : "";
		$typeLoan = !empty($this->dataPost['loan_infor']['type_loan']['code']) ? $this->dataPost['loan_infor']['type_loan']['code'] : "";
		$this->dataPost['investor_code'] = (string)$this->dataPost['investor_code'];
		$this->dataPost['disbursement_date'] = (int)$this->dataPost['disbursement_date'];
		$this->dataPost['status'] = (int)$this->dataPost['status'];
		$this->dataPost['updated_at'] = $this->createdAt;
		unset($this->dataPost['type']);
//		unset($this->dataPost['code_contract']);
//		unset($this->dataPost['customer_infor']['customer_phone_number']);
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		//udate dashboard
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));

		$contractData['import_update_contract'] = strtotime(date("Y-m-d"));

		if (empty($contractData)) {

			$day_check = strtotime(date('2021-1-1'));


			if ($this->dataPost['disbursement_date'] < $day_check) {

				if (empty($this->dataPost['code_contract_disbursement'])) {

					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã hợp đồng gốc không thể trống"
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}

			if (empty($this->dataPost['disbursement_date'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày giải ngân không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			if (empty($this->dataPost['status'])) {

				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Trạng thái không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['investor_code'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã NĐT không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['customer_infor']['customer_name'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Tên khách hàng không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['type_loan'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hình thức không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['type_property'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Loại tài sản không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['price_property'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số tiền vay không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['price_property'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số tiền vay không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['type_interest'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hình thức trả lãi không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['loan_infor']['number_day_loan'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Thời gian vay không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_interest_customer'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Lãi suất NĐT không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_advisory'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí tư vấn không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_expertise'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí thẩm định và lưu trữ tài sản không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['penalty_percent'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí quản lý số tiền vay chậm trả không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['penalty_amount'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí quản lý số tiền vay chậm trả không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_prepay_phase_1'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí tất toán trước hạn (trước 1/3) không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_prepay_phase_2'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí tất toán trước hạn (trước 2/3) không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['percent_prepay_phase_3'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí tất toán trước hạn (còn lại) không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (empty($this->dataPost['fee']['fee_extend'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Phí tư vấn gia hạn không thể trống"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			$this->contract_model->insert($this->dataPost);

		} else {

			$arr_update = array();


			foreach ($this->dataPost as $key => $value) {
				if (!empty($value)) {
					if (is_array($value)) {
						foreach ($value as $key1 => $value1) {
							if (!empty($value1)) {
								$contractData[$key][$key1] = $value1;
							}
						}
					} else {
						$contractData[$key] = $value;
					}
				}
			}

			$this->contract_model->update(array('_id' => $contractData['_id']), $contractData);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "update_import",
				"contract_id" => (string)$contractData['_id'],
				"new" => $arr_update,
				"old" => $contractData,
				"created_at" => $this->createdAt,
				"created_by" => $this->dataPost['created_by']
			);


			$this->log_model->insert($insertLog);
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhật hợp đồng thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function investor_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['created_at'] = (int)$this->dataPost['created_at'];
		$this->dataPost['updated_at'] = (int)$this->dataPost['updated_at'];
		//udate dashboard

		// if (empty($this->dataPost['code']) && empty($this->dataPost['name']) && empty($this->dataPost['dentity_card']) && empty($this->dataPost['date_of_birth']) && empty($this->dataPost['phone']) && empty($this->dataPost['email']) && empty($this->dataPost['address']) && empty($this->dataPost['tax_code']) && empty($this->dataPost['balance']) && empty($this->dataPost['percent_interest_investor'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "File không có gì"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }

		if (empty($this->dataPost['code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã nhà đầu tư không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$investorData = $this->investor_model->findOne(array("code" => trim($this->dataPost['code'])));


//		if (empty($investorData)) {
//			$investorId = $this->investor_model->insertReturnId($this->dataPost);
//
//		} else {
//
//			$response = array(
//				'status' => REST_Controller::HTTP_UNAUTHORIZED,
//				'message' => "Mã nhà đầu tư đã tồn tại"
//			);
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
//		}


		// if (empty($this->dataPost['name'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Tên nhà đầu tư không thể trống"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }
		// if (empty($this->dataPost['tax_code'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Mã số thuế không thể trống"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }
		// if (empty($this->dataPost['percent_interest_investor'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Lãi không thể trống"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }

		// if (!preg_match("/^[0-9]{10}$/", $this->dataPost['phone'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Số điện thoại không đúng định dạng"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }
		// if (!preg_match("/^[0-9]{9,12}$/", $this->dataPost['dentity_card'])) {
		// 	$response = array(
		// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 		'message' => "Số chứng minh không đúng định dạng"
		// 	);
		// 	$this->set_response($response, REST_Controller::HTTP_OK);
		// 	return;
		// }

		if ($this->dataPost['code'] == $investorData['code']) {
			$this->investor_model->update(array('_id' => $investorData['_id']), $this->dataPost);
		} else {
			$this->investor_model->insertReturnId($this->dataPost);
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new investor success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_dang_vay_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;


		$this->dataPost['status'] = 17;
		$this->dataPost['status_disbursement'] = 2;
		$this->dataPost['disbursement_date'] = (int)$this->dataPost['disbursement_date'];
		$this->dataPost['updated_at'] = $this->createdAt;
		$this->dataPost['is_change_dang_vay'] = 1;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi và mã hợp đồng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));
		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		}
		//       if ($contractData['customer_infor']['customer_name']!=$this->dataPost['customer_name']) {
		//        	$response = array(
		// 	'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 	'message' => "Thông tin họ tên khách hàng không khớp",

		// );
		//        	$this->set_response($response, REST_Controller::HTTP_OK);
		// return;
		//        }
		if (empty($this->dataPost['code_contract_disbursement'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng và mã phiếu ghi không được để trống",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['investor_code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã nhà đầu tư không được để trống",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['disbursement_date'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày giải ngân không được để trống",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		unset($this->dataPost['code_contract']);
		unset($this->dataPost['customer_name']);

		if (!isset($contractData['code_contract_disbursement'])) {

		} else {
			if ($this->dataPost['code_contract_disbursement'] != $contractData['code_contract_disbursement']) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã hợp đồng import khác mã hợp đồng trên hệ thống",

				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;

			}
			unset($this->dataPost['code_contract_disbursement']);
		}
		$this->contract_model->update(array('_id' => $contractData['_id']), $this->dataPost);
		//Insert log
		$insertLog = array(
			"type" => "contract",
			"action" => "update_import_dang_vay",
			"contract_id" => (string)$contractId,
			"old" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->dataPost['created_by']
		);

		$this->log_model->insert($insertLog);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success",

			//'dashboard' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
    public function process_gia_han_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		
		$this->dataPost['updated_at'] = $this->createdAt;

		if (empty($this->dataPost['code_contract']) || empty($this->dataPost['code_contract_disbursement'])  ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng, mã phiếu ghi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
        if (empty($this->dataPost['number_day_loan'])    || empty($this->dataPost['lan_gia_han']) ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày và số ngày, số lần không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		

		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));

		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		} else {

			if ($contractData['status'] < 17) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hợp đồng import không ở trạng thái đã vay",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
             $dataDB['extend_all'] = (array)$dataDB['extend_all'];
            $lan_gia_han= $this->dataPost['lan_gia_han'];
     
             $dataDB['extend_all'] = array('extend_date'=>(int)$this->dataPost['date_gia_han'],'number_day_loan'=>(int)$this->dataPost['number_day_loan'],'is_import'=>1,'date_run'=>date('d-m-Y'),"so_lan"=>$lan_gia_han);
             
			$arr_update = array(
				
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
				"extend_all.".$lan_gia_han=>$dataDB['extend_all'],
				"type_gh" => "origin",
				"is_import_gh" => 1
			);
		$this->contract_model->update(array('_id' => $contractData['_id']), $arr_update);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "update_import_gia_han",
				"contract_id" => (string)$contractId,
				"old" => $this->dataPost,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);

			$this->log_model->insert($insertLog);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhật gia hạn thành công",

			//'dashboard' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	 public function process_co_cau_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		
		
		if (empty($this->dataPost['code_contract']) || empty($this->dataPost['code_contract_disbursement'])  ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng, mã phiếu ghi không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['type_loan'])  ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hình thức cho vay không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
        if (empty($this->dataPost['amount_money_cc'])  ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['number_day_loan'])  || empty($this->dataPost['date_co_cau']) || empty($this->dataPost['lan_co_cau']) ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày và số ngày, số lần cơ cấu không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));

		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		} else {

			if ($contractData['status'] < 17) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hợp đồng import không ở trạng thái đã vay",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
             $arr_type_loan=array();
             $type_loan=$this->dataPost['type_loan'];
              $type_interest=$this->dataPost['type_interest'];
        if($type_loan=="CV")
        {
        	$arr_type_loan['id'] = '5da82ee7a104d435e3b8ae66';
        	$arr_type_loan['text'] = 'Cho vay';
        	$arr_type_loan['code'] = 'DKX';
        }else if($type_loan=="CC")
         {
        	$arr_type_loan['id'] = '5da82ed2a104d435e3b8ae65';
        	$arr_type_loan['text'] = 'Cầm cố';
        	$arr_type_loan['code'] = 'CC';
        }else if($type_loan=="TC")
         {
        	$arr_type_loan['id'] = '5fdf75fa6653056471f0b7fe';
        	$arr_type_loan['text'] = 'Tín chấp';
        	$arr_type_loan['code'] = 'TC';
        }
        $dataDB['structure_all'] = (array)$dataDB['structure_all'];
        $lan_co_cau=$this->dataPost['lan_co_cau'];
         // for( $i = 1;$i<= $lan_co_cau;$i++){
        $dataDB['structure_all'] = array(
					'structure_date'=>(int)$this->dataPost['date_co_cau'],
					'number_day_loan'=>(int)$this->dataPost['number_day_loan'],
					'amount_money'=>(int)$this->dataPost['amount_money_cc'],
					'type_loan'=>$arr_type_loan,
					'type_interest'=>$type_interest,
					'so_lan'=>$lan_co_cau,
					'date_run'=>date('d-m-Y'));
  //  }
			$arr_update = array(
				'is_import_cc'=>1,
					"type_cc" => "origin",
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
				"structure_all.".$lan_co_cau=>$dataDB['structure_all']
			);
		$this->contract_model->update(array('_id' => $contractData['_id']), $arr_update);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "update_import_co_cau",
				"contract_id" => (string)$contractId,
				"old" => $this->dataPost,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);

			$this->log_model->insert($insertLog);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhật gia hạn thành công",

			//'dashboard' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function process_update_tat_toan_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['is_change_tat_toan'] = 1;
		$this->dataPost['updated_at'] = $this->createdAt;

		if (empty($this->dataPost['code_contract']) && empty($this->dataPost['customer_name']) && empty($this->dataPost['code_contract_disbursement'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "File không có gì"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));

		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		} else {

			if ($contractData['status'] != 17) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hợp đồng import không ở trạng thái đang vay",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}


			if ($contractData['customer_infor']['customer_name'] != $this->dataPost['customer_name']) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Thông tin họ tên khách hàng không khớp",

				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}


			if (empty($this->dataPost['code_contract_disbursement'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã hợp đồng không được để trống",

				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			unset($this->dataPost['code_contract']);
			unset($this->dataPost['customer_name']);
//			unset($this->dataPost['code_contract_disbursement']);
			unset($this->dataPost['disbursement_date']);

			$this->contract_model->update(array('_id' => $contractData['_id']), $this->dataPost);
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "update_import_tat_toan",
				"contract_id" => (string)$contractId,
				"old" => $this->dataPost,
				"created_at" => $this->createdAt,
				"created_by" => $this->dataPost['created_by']
			);

			$this->log_model->insert($insertLog);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new contract success",

			//'dashboard' => $contractData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	private function initNumberContractCode()
	{
		$maxNumber = $this->contract_model->getMaxNumberContract();
		$maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract'] + 1 : 1;
		$res = array(
			"max_number_contract" => $maxNumberContract
		);
		return $res;
	}


	public function payment_finish_contract_import($inputData)
	{
		//Check null
		if (empty($inputData['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			return $response;
		}
	
		if (empty($inputData['store'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin phòng giao dịch lập phiếu thu không thể trống"
			);
			return $response;
		}
		$data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($inputData['store']['id'])));
		if (!empty($data_store)) {
			$inputData['store']['id'] = (string)$data_store['_id'];
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin phòng giao dịch lập phiếu thu sai thông tin id"
			);
			return $response;
		}
		if (empty($inputData['payment_method'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Phương thức thanh toán không thể trống"
			);
			return $response;
		}
		if (empty($inputData['type_pt'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Kiểu thanh toán không thể trống"
			);
			return $response;
		}
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($inputData['code_contract'])));
		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			return $response;
		}
		
		if (!empty($inputData['code_transaction_bank'])) {
			// Check duplicate
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $inputData['code_transaction_bank'], "status" => array('$ne' => 3)));
			if ($inputData['allow_duplicate'] == 0) {
				if (!empty($transaction_ck_pt)) {
					foreach ($transaction_ck_pt as $key => $value) {
						if ($value['date_pay'] != (int)$inputData['created_at']) {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => "Phiếu thu đã tồn tại (khác ngày):"
							);
							$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " data response: " . json_encode($response));
							return $response;
						}
						
						if ($value['date_pay'] == (int)$inputData['created_at'] && $value['code_contract'] == $inputData['code_contract']) {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => "Phiếu thu đã tồn tại (trùng ngày):"
							);
							$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " data response: " . json_encode($response));
							return $response;
						}
					}
				}
			}
		}

		$inputData['code_contract'] = $this->security->xss_clean($inputData['code_contract']);
		$inputData['code_contract_disbursement'] = $this->security->xss_clean($inputData['code_contract_disbursement']);
		$inputData['name'] = $this->security->xss_clean($inputData['name']);
		$inputData['phone'] = $this->security->xss_clean($inputData['phone']);
		$inputData['payment_method'] = $this->security->xss_clean($inputData['payment_method']);
		$inputData['note'] = isset($inputData['note']) ? $inputData['note'] : '';
		$inputData['store'] = $this->security->xss_clean($inputData['store']); // cua hang
		$inputData['created_by'] = $this->security->xss_clean($inputData['created_by']);
		$inputData['created_at'] = !empty($inputData['created_at']) ? $this->security->xss_clean($inputData['created_at']) : $this->createdAt;

		$inputData['bank'] = !empty($inputData['bank']) ? $this->security->xss_clean($inputData['bank']) : "";
        $inputData['url_img'] = !empty($inputData['url_img']) ? $this->security->xss_clean($inputData['url_img']) : "";
		$inputData['discounted_fee'] = !empty($inputData['discounted_fee']) ? $this->security->xss_clean($inputData['discounted_fee']) : "";
		$inputData['note'] = !empty($inputData['note']) ? $this->security->xss_clean($inputData['note']) : "";
		$inputData['allow_duplicate'] = !empty($inputData['allow_duplicate']) ? $inputData['allow_duplicate'] : 0;

		$contractDB = $this->contract_model->findOne(array('code_contract' => $inputData['code_contract']));
		//Insert data
		$code = $this->transaction_model->getNextTranCode($contractDB['code_contract']);

		$data_transaction = array(
			"total" => (int)$inputData['amount'],
			"code" => $code,
			"type" => 3, // tat toan
			"code_contract" => $inputData['code_contract'],
			"code_contract_disbursement" => $inputData['code_contract_disbursement'],
			"payment_method" => (int)$inputData['payment_method'],
			"store" => $inputData['store'],
			"status" => 1,
			"is_import" => 1,
			"customer_bill_phone" => $inputData['phone'],
			"customer_bill_name" => $inputData['customer_name'],
			"note" => $inputData['note'] . '1',
			"created_by" => $inputData['created_by'],
			"created_at" => (int)$inputData['created_at'],
			"date_pay" => (int)$inputData['created_at'],
			"type_import" => "new"
		);
		if (!empty($inputData['url_img'])) {
			$random = sha1(random_string());
			$data1 = array(
				'path' => $inputData['url_img'],
				'file_type' => 'image/jpeg',
				'file_name' => 'import.jpg'
			);
		    $data_transaction['image_banking'] = [
		    	"image_expertise"=>[$random=>$data1]
		    ];
		}
		if (!empty($inputData['discounted_fee'])) {
		      $data_transaction['discounted_fee']=(int)$inputData['discounted_fee'];
		      $data_transaction['total_deductible']=(int)$inputData['discounted_fee'];
		}
		if (!empty($inputData['note'])) {
		   $data_transaction['note']=$inputData['note'];
		}
		$data_transaction["bank"] = $inputData['bank'];
		$data_transaction["code_transaction_bank"] = $inputData['code_transaction_bank'];
		$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " data insert: " . json_encode($data_transaction));
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Import thành công',
			'transaction_id' => $transaction_id
		);
		$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " data response: " . json_encode($response));
		return $response;

	}

	public function payment_contract_import($inputData)
	{   
		if (empty($inputData['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			return $response;
		}
		
		if (empty($inputData['type_pt'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Loại thanh toán không thể trống",
			);
			return $response;
		}
		if (empty($inputData['store'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin phòng giao dịch lập phiếu thu không thể trống"
			);
			return $response;
		}
		$data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($inputData['store']['id'])));
		if (!empty($data_store)) {
			$inputData['store']['id'] = (string)$data_store['_id'];
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Thông tin phòng giao dịch lập phiếu thu sai thông tin id"
			);
			return $response;
		}
		if (empty($inputData['payment_method'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Phương thức thanh toán không thể trống"
			);
			return $response;
		}
		if (empty($inputData['type_pt'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Kiểu thanh toán không thể trống"
			);
			return $response;
		}
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($inputData['code_contract'])));
		if (empty($contractData)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",

			);
			return $response;
		}
		
		if (!empty($inputData['code_transaction_bank'])) {
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $inputData['code_transaction_bank'], "status" => array('$ne' => 3)));
			if ($inputData['allow_duplicate'] == 0) {
				if (!empty($transaction_ck_pt)) {
					foreach ($transaction_ck_pt as $key => $value) {
						if ($value['date_pay'] != (int)$inputData['created_at']) {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => "Phiếu thu đã tồn tại (khác ngày):"
							);
							return $response;
						}
					
						if ($value['date_pay'] == (int)$inputData['created_at'] && $value['code_contract'] == $tinputData['code_contract']) {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => "Phiếu thu đã tồn tại (trùng ngày):"
							);
							return $response;
						}
					}
				}
			}
		}
		$inputData['code_contract'] = $this->security->xss_clean($inputData['code_contract']);
		$inputData['amount'] = $this->security->xss_clean($inputData['amount']);
		if (isset($inputData['amount_total'])) {
			$inputData['amount_total'] = $this->security->xss_clean($inputData['amount_total']);
		}
		$inputData['name'] = $this->security->xss_clean($inputData['name']);
		$inputData['phone'] = $this->security->xss_clean($inputData['phone']);
		$inputData['payment_method'] = $this->security->xss_clean($inputData['payment_method']);
		$inputData['type_pt'] = $this->security->xss_clean($inputData['type_pt']);
		$inputData['note'] = isset($inputData['note']) ? $inputData['note'] : '';
		$inputData['store'] = $this->security->xss_clean($inputData['store']); // cua hang
		$inputData['created_by'] = $this->security->xss_clean($inputData['created_by']);
		$inputData['created_at'] = !empty($inputData['created_at']) ? $this->security->xss_clean($inputData['created_at']) : $this->createdAt;
		$inputData['code_transaction_bank'] = !empty($inputData['code_transaction_bank']) ? $this->security->xss_clean($inputData['code_transaction_bank']) : "";
		$inputData['url_img'] = !empty($inputData['url_img']) ? $this->security->xss_clean($inputData['url_img']) : "";
		$inputData['discounted_fee'] = !empty($inputData['discounted_fee']) ? $this->security->xss_clean($inputData['discounted_fee']) : "";
		$inputData['note'] = !empty($inputData['note']) ? $this->security->xss_clean($inputData['note']) : "";
		$inputData['allow_duplicate'] = !empty($inputData['allow_duplicate']) ? $inputData['allow_duplicate'] : 0;

		$contractDB = $this->contract_model->findOne(array('code_contract' => $inputData['code_contract']));
		//Insert data
		$code = $this->transaction_model->getNextTranCode($contractDB['code_contract']);

		$data_transaction = array(
			"code_contract" => $inputData['code_contract'],
			"code_contract_disbursement" => $inputData['code_contract_disbursement'],
			"total" => (int)$inputData['amount'],// số tiền khách hang đưa
			"amount_total" => isset($inputData['amount_total']) ? (int)$inputData['amount_total'] : 0,// tổng số tiền phải thanh toán
			"code" => $code,
			"type" => (int)$inputData['type_pt'], //3 tat toan. 4 thanh toan ky lai. 5 gia han hop dong
			"payment_method" => (int)$inputData['payment_method'],
			"store" => $inputData['store'],
			"status" => 1,
			"is_import" => 1,
			"customer_bill_phone" => $inputData['phone'],
			"customer_bill_name" => $inputData['customer_name'],
			"note" => $inputData['note'] . '1',
			"created_by" => $inputData['created_by'],
			"created_at" => (int)$inputData['created_at'],
			"date_pay" => (int)$inputData['created_at'],
			"type_import" => "new"
		);
		if (!empty($inputData['url_img'])) {
			$random = sha1(random_string());
			$data1 = array(
				'path' => $inputData['url_img'],
				'file_type' => 'image/jpeg',
				'file_name' => 'import.jpg'
			);
		    $data_transaction['image_banking'] = [
		    	"image_expertise"=>[$random=>$data1]
		    ];
		}
		if (!empty($inputData['discounted_fee'])) {
		      $data_transaction['discounted_fee']=(int)$inputData['discounted_fee'];
		      $data_transaction['total_deductible']=(int)$inputData['discounted_fee'];
		}
		if (!empty($inputData['note'])) {
		   $data_transaction['note']=$inputData['note'];
		}
		$data_transaction["bank"] = $inputData['bank'];
		$data_transaction["code_transaction_bank"] = $inputData['code_transaction_bank'];
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		// var_dump($data_transaction); die();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Import thành công',
			'url' => $url,
			'transaction_id' => (string)$transaction_id,
			'code' => $code
		);
		return $response;

	}

	public function nhansu_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$personnelData = $this->personnel_model->findOne(array("customer_code" => trim($this->dataPost['customer_code'])));

		if (empty($this->dataPost['customer_phone'])) {
			$this->dataPost['customer_phone'] = " ";
		}

		if ($this->dataPost['customer_code'] == $personnelData['customer_code']) {
			$this->personnel_model->update(array('_id' => $personnelData['_id']), $this->dataPost);
		} else {
			$this->personnel_model->insert($this->dataPost);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	function get_all_nhansu_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$personnel = $this->personnel_model->find();

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $personnel
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function process_contract_phieu_thu_import_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
     $this->dataPost = $this->input->post();
		
		$this->dataPost['code_contract_disbursement'] = $this->security->xss_clean($this->dataPost['code_contract_disbursement']);
		$this->dataPost['code'] = $this->security->xss_clean($this->dataPost['code']);
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);

		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['code_contract_disbursement'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu thu không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		//udate dashboard
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));
		if (!empty($contractData) && $contractData['code_contract_disbursement']!=$this->dataPost['code_contract_disbursement']) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu thu không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$tranData = $this->transaction_model->findOne(array("code" => trim($this->dataPost['code'])));
           if(empty($contractData) || empty($tranData))
           {
           	$response = array(
			'status' => REST_Controller::HTTP_UNAUTHORIZED,
			'message' => "Import thất bại"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
           }
		    $arr_update=[
		    	'code_contract'=>$this->dataPost['code_contract'],
		    	'code_contract_disbursement'=>$this->dataPost['code_contract_disbursement'],
		    ];

			$this->transaction_model->update(array('_id' => $tranData['_id']), $arr_update);
			$this->dataPost['transaction_id']= (string)$tranData['_id'];
			//Insert log
			$insertLog = array(
				"type" => "transaction",
				"action" => 'update',
				"action_exten" => 'update_contract',
				"data_post" => $this->dataPost,
				"data_old" => $tranData,
				"transaction_id" => (string)$tranData['_id'],
				"email" => $this->uemail,
				"created_at" => $this->createdAt
			);


			$this->log_model->insert($insertLog);
		


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "import thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function contract_import_update_contract_phieu_thu_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$start = !empty($this->dataPost['condition']['start']) ? $this->dataPost['condition']['start'] : "";
		$end = !empty($this->dataPost['condition']['end']) ? $this->dataPost['condition']['end'] : "";
		

		$code_contract_disbursement = !empty($this->dataPost['condition']['code_contract_disbursement']) ? $this->dataPost['condition']['code_contract_disbursement'] : "";
		
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$contract = array();
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		
		$contract = $this->log_model->getUpdate_transaction_import($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->log_model->getUpdate_transaction_import($condition);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function process_contract_status_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
     $this->dataPost = $this->input->post();
		
		
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);

		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['status'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Trạng thái"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		
		//udate dashboard
		$contractData = $this->contract_model->findOne(array("code_contract" => trim($this->dataPost['code_contract'])));
		if (empty($contractData) ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		
		
		    $arr_update=[
		    	'status'=>(int)$this->dataPost['status']
		    ];

			$this->contract_model->update(array('_id' => $contractData['_id']), $arr_update);
			
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => 'update',
				"action_exten" => 'update_status_contract',
				"data_post" => $this->dataPost,
				"data_old" => $contractData,
				"contract_id" => (string)$contractData['_id'],
				"email" => 'admin',
				"created_at" => $this->createdAt
			);


			$this->log_model->insert($insertLog);
		


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "import thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	/**
    * Update phiếu thu miễn giảm
    */
    public function updatePhieuThuMienGiam_post()
    {
        $flag = notify_token($this->flag_login);
		if ($flag == false) return;
     	$data = $this->input->post();
     	$contract_codes = [];
     	foreach ($data as $key => $value) {
     		if (empty($value['total_deductible']) || empty($value['code'])) {
     			continue;
     		}
     		$date_pay = false;
     		if (!empty($value['date_pay'])) {
				$date = new DateTime($value['date_pay'] . ' 23:59:59', new DateTimeZone('Asia/Ho_Chi_Minh'));
				$date_pay = $date->format('U');
     		}
     		$update = [
                'total_deductible'              => (int)$value['total_deductible'],
                'discounted_fee'                => (int)$value['discounted_fee'],
                'note'                  		=> $value['note'],
                'updated_at'                    => (int)$value['updated_at'],
                'updated_by'                    => $value['updated_by'],
                'type_import'                   => $value['type_import'],
                'type'							=> (int)$value['type_pt'],
            ];
            if ($date_pay) {
            	$update['date_pay'] = (int)$date_pay;
            }
            $this->transaction_model->update(['code' => $value['code']], $update);
            $contract_codes[] = $value['code_contract'];
     	}
        $response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update phiếu thu miễn giảm thành công",
			'contract_codes' => $contract_codes
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
    }

    /**
    * Update phiếu thu miễn giảm
    */
    public function createPhieuThu_post()
    {
    	$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " =================createPhieuThu ======================= ");

        $flag = notify_token($this->flag_login);
		if ($flag == false) return;
    	$data = $this->input->post();
    	$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " createPhieuThu " . json_encode($data));
    	$tranIds = [];
    	foreach ($data as $key => $value) {
     		
    		if (isset($value["type_pt"]) && ($value["type_pt"] == 4 || $value["type_pt"] == 5)) {
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " =================payment_contract_import ======================= ");
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", "data: " . json_encode($value));
    			$response = $this->payment_contract_import($value);
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", "data: " . json_encode($response));

    		} else if (isset($value["type_pt"]) && $value["type_pt"] == 3) {
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", " =================payment_finish_contract_import ======================= ");
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", "data: " . json_encode($value));
    			$response = $this->payment_finish_contract_import($value);
    			$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", "data: " . json_encode($response));
    		}
    		if (empty($response)) {
    			continue;
    		}
    		if ($response["status"] == 200 && isset($response["transaction_id"])) {
				$tranIds[] = $response["transaction_id"];
			}
     	}

        $response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "phiếu thu thành công.",
			'tranIds' => $tranIds
		);
		$this->WriteLog("Import-PT-" . date("Ymd", time()) . ".txt", "createPhieuThu finished: " . json_encode($response));
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
    }

    public function WriteLog($fileName,$data,$breakLine=true,$addTime=true) {
        $fp = fopen("log/".$fileName,'a');
        if ($fp)
        {
            if ($breakLine)
            {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ",time()).$data. " \n";
                else
                    $line = $data. " \n";
            }
            else
            {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ",time()).$data;
                else
                    $line = $data;
            }
            fwrite($fp,$line);
            fclose($fp);
        }
    }

}

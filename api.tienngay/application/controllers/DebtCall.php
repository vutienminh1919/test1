<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;

class DebtCall extends \Restserver\Libraries\REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('store_model');
		$this->load->model('log_model');
		$this->load->model('role_model');
		$this->load->model('province_model');
		$this->load->model('district_model');
		$this->load->model('radio_field_model');
		$this->load->model('area_debt_recovery_model');
		$this->load->model('debt_recovery_model');
		$this->load->model('notification_app_model');
		$this->load->model('device_model');
		$this->load->model('user_model');
		$this->load->model('tracking_location_model');
		$this->load->model('contract_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('contract_debt_model');
		$this->load->model('contract_debt_recovery_model');
		$this->load->model('contract_debt_caller_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('transaction_model');
		$this->load->model('call_debt_manager_model');
		$this->load->model("area_model");
		$this->load->model("log_debt_caller_model");
		$this->load->model("notification_model");
		$this->load->model("store_call_model");
		$this->load->model("time_to_field_model");
		$this->load->model("contract_assign_debt_model");
		$this->load->helper('lead_helper');
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('group_role_model');

		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$dataPost = $this->input->post();
		$this->flag_login = 1;
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
				if ($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
				}
			}
		}
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

	public function assign_contract_to_debt_caller_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $this->security->xss_clean(trim($data['code_contract_disbursement'])) : '';
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean($data['customer_name']) : '';
		$email_call = !empty($data['email_call']) ? $this->security->xss_clean(trim($data['email_call'])) : '';
		$note = !empty($data['note']) ? $this->security->xss_clean(trim($data['note'])) : '';
		$status = !empty($data['status']) ? $this->security->xss_clean(trim($data['status'])) : '';
		$contract = $this->contract_debt_model->findOne([
			'code_contract' => trim($code_contract),
			'status' =>
				[
					'$gte' => 17,
					'$lt' => 35,
				]
		]);
		$user = $this->user_model->findOne(['email' => trim($email_call), 'status' => 'active']);
		$user_name = $user['full_name'];
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => 'Import hoàn thành!',
				'data2' => $code_contract_disbursement
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {

			$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);

			//	Check miền của HĐ
			$store_contract = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['store']['id'])]);
			$area_contract = $this->area_model->findOne(array("code" => $store_contract['code_area']));
			$domain_contract = $area_contract['domain']->code;

			$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
			$array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
			$array_lead_thn_mb_id = $this->get_id_lead_thn_mb();
			$array_lead_thn_mn_id = $this->get_id_lead_thn_mn();
			if (in_array($this->id, $array_tp_thn_mb_id) || in_array($this->id, $array_lead_thn_mb_id)) {
				if ($domain_contract != 'MB') {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' không thuộc khu vực miền Bắc!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else if (in_array($this->id, $array_tp_thn_mn_id) || in_array($this->id, $array_lead_thn_mn_id)) {
				if ($domain_contract != 'MN') {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' không thuộc khu vực miền Nam!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else {
				if (!in_array($domain_contract, ["MB", "MN"])) {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' có khu vực chưa xác định!!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}

			if (empty($user)) {
				$response = array(
					'status' => REST_Controller::HTTP_BAD_REQUEST,
					'message' => 'User ' . $email_call . ' không tồn tại!',
					'data2' => $code_contract_disbursement
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				$email_call_mb = $this->get_email_user_call_thn_mb_internal();
				$email_call_mn = $this->get_email_user_call_thn_mn_internal();
				$email_call_all = array_merge($email_call_mb, $email_call_mn);
				if (!in_array($user['email'], $email_call_all)) {
					$response = array(
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'User ' . $email_call . ' không phải nhân viên Call!',
						'data2' => $code_contract_disbursement
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
				// nếu tồn tại PT tất toán trong tháng thì gán cả HĐ đã tất toán cho Call
				if ($contract['status'] == 19) {
					$first_day_of_month = strtotime(date('Y-m-01') . '00:00:00');
					$current_day = strtotime(date('Y-m-d') . '23:59:59');
					$check_payment_current_month = $this->transaction_model->find_where(array('code_contract' => $contract['code_contract'], 'status' => 1, 'type' => 3, 'date_pay' => array('$gte' => $first_day_of_month, '$lte' => $current_day)));
					if (!empty($check_payment_current_month)) {
						$this->contract_model->update(['_id' => $contract['_id']], ['debt_caller_id' => (string)$user['_id']]);
						$root_debt = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
						$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
						$assign = $this->contract_debt_caller_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
						$current_month = (string)date('m');
						$current_year = (string)date('Y');
						$contract_field = $this->contract_assign_debt_model->findOne(['code_contract' => $contract['code_contract'], 'month' => $current_month, 'year' => $current_year]);
						if (!empty($contract_field) && !in_array($contract_field['status'], [3, 4, 5])) {
							$response = [
								'status' => REST_Controller::HTTP_BAD_REQUEST,
								'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' xóa hợp đồng Field trước khi gán Call!',
								'data2' => $contract['code_contract_disbursement']
							];
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						} else {
							if (!empty($assign)) {
								if (in_array($assign['status'], [3, 4, 5])) {
									$arr_update = [
										"debt_caller_id" => (string)$user['_id'],
										"debt_caller_email" => $email_call,
										"debt_caller_name" => $user_name,
										'status' => 2
									];
									$this->contract_debt_caller_model->update(
										['_id' => $assign['_id']],
										$arr_update
									);
									$this->log_debt_caller_model->insert(
										[
											'type' => 'assign_contract_call',
											'action' => 'update',
											"contract_id" => (string)$contract['_id'],
											'old' => $assign,
											'new' => $arr_update,
											"created_at" => $this->createdAt,
											"created_by" => $this->uemail
										]
									);
								} else {
									$response = [
										'status' => REST_Controller::HTTP_BAD_REQUEST,
										'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' đã tồn tại hoặc đã gán cho nhân viên call xử lý!!',
										'data2' => $contract['code_contract_disbursement']
									];
									$this->set_response($response, REST_Controller::HTTP_OK);
									return;
								}
							} else {
								$param_insert = [
									"contract_id" => (string)$contract['_id'],
									'code_contract' => $code_contract,
									'code_contract_disbursement' => $contract['code_contract_disbursement'],
									'customer_name' => $contract['customer_infor']['customer_name'],
									'customer_phone_number' => $contract['customer_infor']['customer_phone_number'],
									"debt_caller_id" => (string)$user['_id'],
									'debt_caller_email' => $email_call,
									'debt_caller_name' => $user_name,
									'domain_contract' => $domain_contract,
									'amount_money' => $contract['loan_infor']['amount_money'],
									'number_day_loan' => $contract['loan_infor']['number_day_loan'],
									'status_contract' => $contract['status'],
									'status' => 2,
									"pos_origin" => !empty($root_debt) ? round($root_debt) : 0,
									"root_debt" => !empty($root_debt) ? round($root_debt) : 0,
									"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
									"so_ngay_cham_tra" => !empty($bucket['time']) ? $bucket['time'] : 0,
									"ngay_den_han" => !empty($bucket['ngay_den_han']) ? $bucket['ngay_den_han'] : 0,
									'month' => date('m'),
									'year' => date('Y'),
									'store_id' => $contract['store']['id'],
									'store_name' => $contract['store']['name'],
									'note' => $note,
									'created_at' => $this->createdAt,
									'created_by' => $this->uemail
								];
								$this->contract_debt_caller_model->insert($param_insert);
								$this->log_debt_caller_model->insert(
									[
										'type' => 'assign_contract_call',
										'action' => 'insert',
										"contract_id" => (string)$contract['_id'],
										'old' => $param_insert,
										'new' => $param_insert,
										"created_at" => $this->createdAt,
										"created_by" => $this->uemail
									]
								);
							}
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'Gán hợp đồng cho Caller thành công!',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					}
				} else {
					$this->contract_model->update(['_id' => $contract['_id']], ['debt_caller_id' => (string)$user['_id']]);
					$root_debt = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
					$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
					$assign = $this->contract_debt_caller_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
					$current_month = (string)date('m');
					$current_year = (string)date('Y');
					$contract_field = $this->contract_assign_debt_model->findOne(['code_contract' => $contract['code_contract'], 'month' => $current_month, 'year' => $current_year]);
					if (!empty($contract_field) && !in_array($contract_field['status'], [3, 4, 5])) {
						$response = [
							'status' => REST_Controller::HTTP_BAD_REQUEST,
							'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' xóa hợp đồng Field trước khi gán Call!',
							'data2' => $contract['code_contract_disbursement']
						];
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						if (!empty($assign)) {
							if (in_array($assign['status'], [3, 4, 5])) {
								$arr_update = [
									"debt_caller_id" => (string)$user['_id'],
									"debt_caller_email" => $email_call,
									"debt_caller_name" => $user_name,
									'status' => 1
								];
								$this->contract_debt_caller_model->update(
									['_id' => $assign['_id']],
									$arr_update
								);
								$this->log_debt_caller_model->insert(
									[
										'type' => 'assign_contract_call',
										'action' => 'update',
										"contract_id" => (string)$contract['_id'],
										'old' => $assign,
										'new' => $arr_update,
										"created_at" => $this->createdAt,
										"created_by" => $this->uemail
									]
								);
							} else {
								$response = [
									'status' => REST_Controller::HTTP_BAD_REQUEST,
									'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' đã tồn tại hoặc đã gán cho nhân viên call xử lý!!',
									'data2' => $contract['code_contract_disbursement']
								];
								$this->set_response($response, REST_Controller::HTTP_OK);
								return;
							}
						} else {
							$param_insert = [
								"contract_id" => (string)$contract['_id'],
								'code_contract' => $code_contract,
								'code_contract_disbursement' => $contract['code_contract_disbursement'],
								'customer_name' => $contract['customer_infor']['customer_name'],
								'customer_phone_number' => $contract['customer_infor']['customer_phone_number'],
								"debt_caller_id" => (string)$user['_id'],
								'debt_caller_email' => $email_call,
								'debt_caller_name' => $user_name,
								'domain_contract' => $domain_contract,
								'amount_money' => $contract['loan_infor']['amount_money'],
								'number_day_loan' => $contract['loan_infor']['number_day_loan'],
								'status_contract' => $contract['status'],
								'status' => 1,
								"pos_origin" => !empty($root_debt) ? round($root_debt) : 0,
								"root_debt" => !empty($root_debt) ? round($root_debt) : 0,
								"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
								"so_ngay_cham_tra" => !empty($bucket['time']) ? $bucket['time'] : 0,
								"ngay_den_han" => !empty($bucket['ngay_den_han']) ? $bucket['ngay_den_han'] : 0,
								'month' => date('m'),
								'year' => date('Y'),
								'store_id' => $contract['store']['id'],
								'store_name' => $contract['store']['name'],
								'note' => $note,
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail
							];
							$this->contract_debt_caller_model->insert($param_insert);
							$this->log_debt_caller_model->insert(
								[
									'type' => 'assign_contract_call',
									'action' => 'insert',
									"contract_id" => (string)$contract['_id'],
									'old' => $param_insert,
									'new' => $param_insert,
									"created_at" => $this->createdAt,
									"created_by" => $this->uemail
								]
							);
						}
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Gán hợp đồng cho Caller thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
	}

	public function check_import_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $this->security->xss_clean(trim($data['code_contract_disbursement'])) : '';
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean(trim($data['customer_name'])) : '';
		$email_call = !empty($data['email_call']) ? $this->security->xss_clean(trim($data['email_call'])) : '';
		$contract = $this->contract_debt_model->findOne([
			'code_contract' => trim($code_contract),
			'status' =>
				['$nin' => [19],
					'$gte' => 17,
					'$lt' => 35
				]
		]);
		if (!empty($email_call)) {
			$user = $this->user_model->findOne(['email' => trim($email_call), 'status' => 'active']);
		}

		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Mã hợp đồng không đúng!',
				'data2' => $contract['code_contract_disbursement']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {

			$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);

			//	Check miền của HĐ
			$store_contract = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['store']['id'])]);
			$area_contract = $this->area_model->findOne(array("code" => $store_contract['code_area']));
			$domain_contract = $area_contract['domain']->code;

			$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
			if (in_array($this->id, $array_tp_thn_mb_id)) {
				if ($domain_contract == 'MN') {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' thuộc khu vực miền Nam!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else {
				if ($domain_contract == 'MB') {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' thuộc khu vực miền Bắc!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
			if (empty($user)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'User ' . $email_call . 'không tồn tại!',
					'data2' => $contract['code_contract_disbursement']
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				if (!empty($contract['debt_caller_id'])) {
					if ((string)$user['_id'] == $contract['debt_caller_id']) {
						$check_assign = $this->contract_debt_caller_model->findOne(["contract_id" => (string)$contract['_id'], "debt_caller_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
						if (empty($check_assign)) {
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => 'Hợp đồng chưa được gán cho Call!',
								'data2' => $contract['code_contract_disbursement']
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						} else {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'Kiểm tra thành công!',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					} else {
						$check_assign = $this->contract_debt_caller_model->findOne(["contract_id" => (string)$contract['_id'], "debt_caller_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
						if (empty($check_assign)) {
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => 'Hợp đồng chưa được gán cho Call!',
								'data2' => $contract['code_contract_disbursement']
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						} else {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'Kiểm tra thành công!',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					}
				} else {
					$check_assign = $this->contract_debt_caller_model->findOne(["contract_id" => (string)$contract['_id'], "debt_caller_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
					if (empty($check_assign)) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => 'Hợp đồng chưa được gán cho Call!',
							'data2' => $contract['code_contract_disbursement']
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Kiểm tra thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
	}

	public function update_email_caller_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_contract_debt = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$email_debt_caller = !empty($data['email_debt_caller']) ? $this->security->xss_clean($data['email_debt_caller']) : '';

		if (empty($id_contract_debt)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'id đang trống!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($email_debt_caller)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'email đang trống!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$find_user = $this->user_model->findOne(['email' => $email_debt_caller]);
		$contract_assign = $this->contract_debt_caller_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract_debt)]);
		$contractDB = $this->contract_model->findOne(['code_contract' => $contract_assign['code_contract']]);
		if (!empty($contract_assign)) {
			$this->contract_debt_caller_model->update(
				['_id' => $contract_assign['_id']],
				[
					'debt_caller_email' => $email_debt_caller,
					'debt_caller_id' => (string)$find_user['_id'],
					'debt_caller_name' => $find_user['full_name']
				],
			);
			$this->contract_model->update(
				['code_contract' => $contract_assign['code_contract']],
				['debt_caller_id' => (string)$find_user['_id']]
			);
		}
		$note = "Bạn đã nhận được hợp đồng từ " . $contract_assign['debt_caller_name'];
		$status = "";
		$this->push_notification_contract_to_call_thn((string)$find_user['_id'], $status, $contract_assign, $note);
		$this->log_debt_caller_model->insert(
			[
				'type' => 'contract',
				'action' => 'update',
				"contract_id" => (string)$contractDB['_id'],
				'old' => $contract_assign,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập nhân viên thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function setup_time_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_contract_debt = !empty($data['contract_caller_id']) ? $this->security->xss_clean($data['contract_caller_id']) : '';
		$date_range_to_field = !empty($data['date_range_to_field']) ? $this->security->xss_clean($data['date_range_to_field'] . ' 23:59:59') : '';

		$contract_caller = $this->contract_debt_caller_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract_debt)]);


		$date_range_convert = strtotime($date_range_to_field);
		$day_of_convert = date('d', $date_range_convert);
//		if ($day_of_convert > 10) {
//			$response = [
//				'status' => REST_Controller::HTTP_UNAUTHORIZED,
//				'message' => 'Ngày setup chuyển Field không được lớn hơn ngày mùng 10!'
//			];
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
//		}
		if (empty($contract_caller)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Không tồn tại hợp đồng!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if ($contract_caller['created_at'] > $date_range_convert) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Ngày setup chuyển field không được nhỏ hơn ngày gán HĐ cho Call!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$contract_debt_caller = $this->contract_debt_caller_model->findOneAndUpdate(
			["_id" => $contract_caller['_id']],
			['time_range_to_field' => $date_range_convert]
		);
		$this->log_debt_caller_model->insert(
			[
				'type' => 'contract',
				'action' => 'update',
				'old' => $contract_caller,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_time_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$this->dataPost = $this->input->post();
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$this->dataPost['time_field'] = $this->security->xss_clean($this->dataPost['time_field'] . ' 23:59:59');
		$contract_caller = $this->contract_debt_caller_model->findOne(['contract_id' => $this->dataPost['contract_id']]);
		if (empty($this->dataPost['time_field'])) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Thời gian chuyển Field không được để trống!!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$date_range_convert = strtotime($this->dataPost['time_field']);
		$day_of_convert = date('d', $date_range_convert);

//		if ($day_of_convert > 10) {
//			$response = [
//				'status' => REST_Controller::HTTP_UNAUTHORIZED,
//				'message' => 'Thời gian chuyển Field không được lớn hơn ngày 10!!'
//			];
//			$this->set_response($response,REST_Controller::HTTP_OK);
//			return;
//		}

		if (!empty($contract_caller)) {
			if (isset($contract_caller['time_range_to_field'])) {
				if ($date_range_convert < $contract_caller['time_range_to_field']) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Thời gian chuyển Field không được nhỏ hơn thơi gian đã setup trước đó!'
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
				if ($date_range_convert < $date_range_convert['created_at']) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Ngày setup chuyển field không được nhỏ hơn ngày gán HĐ cho Call!'
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}


		$this->contract_debt_caller_model->update(
			['_id' => $contract_caller['_id']],
			[
				'time_range_to_field' => $date_range_convert,
				'update_range_time' => 1
			]
		);

		$this->log_debt_caller_model->insert(
			[
				'type' => 'contract',
				'action' => 'update',
				'old' => $contract_caller,
				'new' => $this->dataPost,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_all_contract_call_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : '';
		$end = !empty($data['end']) ? $data['end'] : '';
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : '';
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : '';
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$phone_number = !empty($data['phone_number']) ? $data['phone_number'] : '';
		$email = !empty($data['email']) ? $data['email'] : '';
		$status = !empty($data['status']) ? $data['status'] : '';
		$status_contract = !empty($data['status_contract']) ? $data['status_contract'] : '';
		$store_id = !empty($data['store_id']) ? $data['store_id'] : '';
		$tab = !empty($data['tab']) ? $data['tab'] : '';
		$condition = array();

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($phone_number)) {
			$condition['phone_number'] = trim($phone_number);
		}
		if (!empty($email)) {
			$condition['email'] = trim($email);
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($status_contract)) {
			$condition['status_contract'] = $status_contract;
		}
		if (!empty($store_id)) {
			$condition['store'] = $store_id;
		}
		if (!empty($tab)) {
			$condition['tab'] = trim($tab);
		}

//		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
//		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

		$array_call_thn_mb_user_id = $this->get_id_user_call_thn_mb();
		$array_id_tp_thn_mb = $this->get_id_tp_thn_mb();
		$array_id_lead_thn_mb = $this->get_id_lead_thn_mb();
		if (in_array($this->id, $array_call_thn_mb_user_id) || in_array($this->id, $array_id_tp_thn_mb) || in_array($this->id, $array_id_lead_thn_mb)) {
			$list_user_call = $this->get_email_user_call_thn_mb_internal();
			$condition['domain_contract'] = 'MB';
		} else {
			$list_user_call = $this->get_email_user_call_thn_mn_internal();
			$condition['domain_contract'] = 'MN';
		}

		$user_email_thn = $this->get_email_user_thn();
		$user_email_tp_thn = $this->get_email_user_tp_thn();
		if (in_array($this->uemail, $user_email_thn) && in_array($this->uemail, $user_email_tp_thn) !== true) {
			$condition['debt_caller_email'] = $this->uemail;
		}

		$contract_debt_caller = $this->contract_debt_caller_model->get_all_contract_assign_to_call(array(), $condition);
		$condition['total'] = true;
		$total = $this->contract_debt_caller_model->get_all_contract_assign_to_call(array(), $condition);
		if ($tab == 'review') {
			$arr_return_review = [];
			$bucket_b0 = [];
			$bucket_b1 = [];
			$bucket_b2 = [];
			$bucket_b3 = [];
			$bucket_b4 = [];
			$bucket_b5 = [];
			$bucket_b6 = [];
			$bucket_b7 = [];
			$bucket_b8 = [];
			if (!empty($list_user_call)) {
				foreach ($list_user_call as $email_caller) {
					if (!empty($contract_debt_caller)) {
						foreach ($contract_debt_caller as $key => $contract_call) {
							if ($email_caller == $contract_call['debt_caller_email']) {
								if ($contract_call['bucket'] == 'B0') {
									$bucket_b0[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B1') {
									$bucket_b1[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B2') {
									$bucket_b2[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B3') {
									$bucket_b3[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B4') {
									$bucket_b4[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B5') {
									$bucket_b5[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B6') {
									$bucket_b6[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B7') {
									$bucket_b7[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B8') {
									$bucket_b8[] = $contract_call;
								}
							}
						}
					}
				}
			}
			$ema = 0;
			if (!empty($list_user_call)) {
				foreach ($list_user_call as $key => $email_call) {
					$ema++;
					$sum_all_by_email = sum_values($contract_debt_caller, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_all_by_email = count_values($contract_debt_caller, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b0 = sum_values($bucket_b0, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b0 = count_values($bucket_b0, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b1 = sum_values($bucket_b1, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b1 = count_values($bucket_b1, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b2 = sum_values($bucket_b2, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b2 = count_values($bucket_b2, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b3 = sum_values($bucket_b3, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b3 = count_values($bucket_b3, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b4 = sum_values($bucket_b4, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b4 = count_values($bucket_b4, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b5 = sum_values($bucket_b5, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b5 = count_values($bucket_b5, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b6 = sum_values($bucket_b6, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b6 = count_values($bucket_b6, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b7 = sum_values($bucket_b7, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b7 = count_values($bucket_b7, 'debt_caller_email', $email_call, '', '', '', '');
					$sum_debt_b8 = sum_values($bucket_b8, 'debt_caller_email', $email_call, '', '', '', '', 'root_debt');
					$count_debt_b8 = count_values($bucket_b8, 'debt_caller_email', $email_call, '', '', '', '');
					$arr_return_review += [$ema => [
						'email' => $email_call,
						'count_all_by_email' => $count_all_by_email,
						'sum_all_by_email' => $sum_all_by_email,
						'count_b0' => $count_debt_b0,
						'sum_b0' => $sum_debt_b0,
						'count_b1' => $count_debt_b1,
						'sum_b1' => $sum_debt_b1,
						'count_b2' => $count_debt_b2,
						'sum_b2' => $sum_debt_b2,
						'count_b3' => $count_debt_b3,
						'sum_b3' => $sum_debt_b3,
						'count_b4' => $count_debt_b4,
						'sum_b4' => $sum_debt_b4,
						'count_b5' => $count_debt_b5,
						'sum_b5' => $sum_debt_b5,
						'count_b6' => $count_debt_b6,
						'sum_b6' => $sum_debt_b6,
						'count_b7' => $count_debt_b7,
						'sum_b7' => $sum_debt_b7,
						'count_b8' => $count_debt_b8,
						'sum_b8' => $sum_debt_b8,
					]];
				}
			}
		}
		if (!empty($contract_debt_caller)) {
			foreach ($contract_debt_caller as $key => $contract_call) {
				$contractDB = $this->contract_model->findOne(['code_contract' => $contract_call['code_contract']]);
				$bucket = $this->lay_nhom_no_hop_dong($contractDB['code_contract']);
				if ($contract_call['code_contract'] == $contractDB['code_contract']) {
					$contract_call['status_contract_realtime'] = $contractDB['status'];
					$contract_call['bucket'] = $bucket['bucket'];
					$contract_call['so_ngay_cham_tra'] = $bucket['time'];
					$contract_call['status_contract_realtime'] = $contractDB['status'];
					$contract_call['evaluate'] = $contractDB['reminder_now'];
					$contract_call['debt_root_contract'] = $contractDB['original_debt']['du_no_goc_con_lai'];
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_debt_caller,
			'total' => $total,
			'data_review' => $arr_return_review
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_contract_request_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : '';
		$end = !empty($data['end']) ? $data['end'] : '';
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : '';
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : '';
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$status = !empty($data['status']) ? $data['status'] : '';
		$status_contract = !empty($data['status_contract']) ? $data['status_contract'] : '';
		$store_id = !empty($data['store_id']) ? $data['store_id'] : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}

		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}

		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}

		if (!empty($status)) {
			$condition['status'] = $status;
		}

		if (!empty($status_contract)) {
			$condition['status_contract'] = $status_contract;
		}

		if (!empty($store_id)) {
			$condition['store'] = $store_id;
		}

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

		$array_call_thn_mb_user_id = $this->get_id_user_call_thn_mb();
		$array_id_tp_thn_mb = $this->get_id_tp_thn_mb();

		if (in_array($this->id, $array_call_thn_mb_user_id) || in_array($this->id, $array_id_tp_thn_mb)) {
			$condition['domain_contract'] = 'MB';
		} else {
			$condition['domain_contract'] = 'MN';
		}

		$user_email_thn = $this->get_email_user_thn();
		$user_email_tp_thn = $this->get_email_user_tp_thn();

		if (in_array($this->uemail, $user_email_thn) && in_array($this->uemail, $user_email_tp_thn) !== true) {
			$condition['debt_caller_email'] = $this->uemail;
		}

		$contract_debt_to_field = $this->contract_debt_caller_model->get_all_contract_to_field(array(), $condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->contract_debt_caller_model->get_all_contract_to_field(array(), $condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_debt_to_field,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_mission_debt_caller_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$fdate = !empty($data['start']) ? $data['start'] : "";
		$tdate = !empty($data['end']) ? $data['end'] : "";
		$debt_caller_id = !empty($data['debt_caller_id']) ? $data['debt_caller_id'] : "";
		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		} else {
			$condition['month'] = date('m');
			$condition['year'] = date('Y');
		}
		if (!empty($debt_caller_id)) {
			$condition['debt_caller_id'] = $debt_caller_id;
		}
		$current_month = date('m');
		$current_year = date('Y');

		//	Phân quyền: trong role TP THN MB và có Menu QLNV call
		$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
		$array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
		if (in_array($this->id, $array_tp_thn_mb_id)) {
			$condition['domain_contract'] = 'MB';
			$list_user = $this->get_email_user_call_thn_mb_internal();
			$time_to_field = $this->time_to_field_model->findOne(['area' => 'MB', "month" => date('m'), "year" => date('Y')]);
			$log_time_to_field = $this->log_debt_caller_model->getTimeSetupLog(['type' => 'time_to_field', 'action' => 'setup', 'area' => 'MB']);
		} else if (in_array($this->id, $array_tp_thn_mn_id)) {
			$condition['domain_contract'] = 'MN';
			$list_user = $this->get_email_user_call_thn_mn_internal();
			$time_to_field = $this->time_to_field_model->findOne(['area' => 'MN', "month" => date('m'), "year" => date('Y')]);
			$log_time_to_field = $this->log_debt_caller_model->getTimeSetupLog(['type' => 'time_to_field', 'action' => 'setup', 'area' => 'MN']);
		} else {
			$list_user = array();
		}

		$data_contract_call = [];

		if ((empty($fdate) && empty($tdate))) {
			foreach ($list_user as $key => $email_caller) {
				$contract_debt_call_all = $this->contract_debt_caller_model->find_where(
					['month' => date('m'),
						'year' => date('Y'),
						'debt_caller_email' => $email_caller,
						'domain_contract' => $condition['domain_contract'],
						'bucket' => array('$in' => array('B0', 'B1')),
						'status' => array('$ne' => 279)
					]);
				$debt_root = sum_values($contract_debt_call_all, 'debt_caller_email', $email_caller, '', '', '', '', 'root_debt');
				$impacted = $this->count_value_impacted_call($contract_debt_call_all);
				$number_of_calling = $this->count_number_of_call($contract_debt_call_all);
				foreach ($contract_debt_call_all as $contract_debt) {
					$data_contract_call[$key][$contract_debt['store_id']][$contract_debt['store_name']][] = $contract_debt;
				}
				$data_contract_call[$key]['email'] = $email_caller;
				$user_name = $this->user_model->findOne(['email' => $email_caller]);
				$data_contract_call[$key]['user_name'] = $user_name['full_name'];
				$data_contract_call[$key]['tong_hop_dong_giao'] = count($contract_debt_call_all);
				$data_contract_call[$key]['du_no_goc_con_lai'] = $debt_root;
				$data_contract_call[$key]['tong_hop_dong_da_tac_dong'] = $impacted;
				$data_contract_call[$key]['tong_so_cuoc_goi'] = $number_of_calling;
			}
		} else {
			foreach ($list_user as $key1 => $email_caller) {
				$condition['debt_caller_email'] = $email_caller;
				$contract_debt_call_all = $this->contract_debt_caller_model->get_all_contract_debt_caller($condition);
				$debt_root = sum_values($contract_debt_call_all, 'debt_caller_email', $email_caller, '', '', '', '', 'root_debt');
				$impacted = $this->count_value_impacted_call($contract_debt_call_all);
				$number_of_calling = $this->count_number_of_call($contract_debt_call_all);
				foreach ($contract_debt_call_all as $contract_debt) {
					$data_contract_call[$key1][$contract_debt['store_id']][$contract_debt['store_name']][] = $contract_debt;
				}
				$data_contract_call[$key1]['email'] = $email_caller;
				$user_name = $this->user_model->findOne(['email' => $email_caller]);
				$data_contract_call[$key1]['user_name'] = $user_name['full_name'];
				$data_contract_call[$key1]['tong_hop_dong_giao'] = count($contract_debt_call_all);
				$data_contract_call[$key1]['du_no_goc_con_lai'] = $debt_root;
				$data_contract_call[$key1]['tong_hop_dong_da_tac_dong'] = $impacted;
				$data_contract_call[$key1]['tong_so_cuoc_goi'] = $number_of_calling;
			}
		}
		$sum_of_contract_assign = 0;
		$sum_of_debt_root_remain = 0;
		$sum_of_contract_impacted = 0;
		$sum_of_number_calling = 0;
		if (!empty($data_contract_call)) {
			foreach ($data_contract_call as $item) {
				$sum_of_contract_assign += $item['tong_hop_dong_giao'];
				$sum_of_debt_root_remain += $item['du_no_goc_con_lai'];
				$sum_of_contract_impacted += $item['tong_hop_dong_da_tac_dong'];
				$sum_of_number_calling += $item['tong_so_cuoc_goi'];
			}
		}
		usort($data_contract_call, function ($a, $b) {
			return $b['du_no_goc_con_lai'] <=> $a['du_no_goc_con_lai'];
		});
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data_contract_call,
			'tong_hop_dong_giao' => $sum_of_contract_assign,
			'tong_du_no_goc_con_lai' => $sum_of_debt_root_remain,
			'tong_hop_dong_da_tac_dong' => $sum_of_contract_impacted,
			'tong_so_cuoc_goi' => $sum_of_number_calling,
			'start_time' => $time_to_field['start_time'],
			'end_time' => $time_to_field['end_time'],
			'log_time_to_field' => $log_time_to_field
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_contract_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$data['contract_id'] = $this->security->xss_clean($data['contract_id']);
		$data['status'] = (int)$this->security->xss_clean($data['status']);
		$data['note'] = $this->security->xss_clean($data['note']);

		if (!empty($data['status'])) {
			$status = (int)$data['status'];
		}
		$contract_call_to_field = $this->contract_debt_caller_model->findOne(['contract_id' => $data['contract_id']]);
		if (!empty($contract_call_to_field)) {
			if ($status == 279) {
				$this->contract_debt_caller_model->update(
					['contract_id' => $contract_call_to_field['contract_id']],
					[
						'status' => $status,
						'note' => $data['note'],
					]
				);
			} else {
				$log_debt_caller = $this->log_debt_caller_model->find_where(['type' => 'reminder', 'old.contract_id' => $data['contract_id']]);
				if (!empty($log_debt_caller[0]['old']['reminder_now'])) {
					$reminder_now = $log_debt_caller[0]['old']['reminder_now'];
				} else {
					$reminder_now = '1';
				}
				$this->contract_debt_caller_model->update(
					['contract_id' => $contract_call_to_field['contract_id']],
					[
						'status' => $status,
						'reminder_now' => $reminder_now,
						'note' => $data['note']
					]
				);
			}
		}
		$this->log_debt_caller_model->insert(
			[
				'type' => 'confirm_to_field',
				'action' => 'approve',
				"contract_id" => (string)$data['contract_id'],
				'old' => $contract_call_to_field,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);

		if ($data['status'] == 279) {
			$note = 'Yêu cầu chuyển Field đã được duyệt!';
		} elseif ($data['status'] == 278) {
			$note = 'Yêu cầu chuyển Field không được duyệt!';
		}
		$link_detail = 'debtcall/list_contract_call?code_contract_disbursement=' . $contract_call_to_field['code_contract_disbursement'];
		$data_notification = [
			'action_id' => $contract_call_to_field['contract_id'],
			'action' => 'approve',
			'title' => $contract_call_to_field['customer_name'] . ' - ' . $contract_call_to_field['store_name'],
			'detail' => $link_detail,
			'note' => $note,
			'user_id' => $contract_call_to_field['debt_caller_id'],
			'status' => 1, //1: new, 2 : read, 3: block,
			'contract_debt_status' => $status,
			'created_at' => $this->createdAt,
			"created_by" => $this->uemail
		];
		$this->notification_model->insertReturnId($data_notification);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_contract_debt_log_post()
	{
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$contract_log = $this->log_debt_caller_model->contractLog(['contract_id' => $contract_id]);
		$html = gen_html_contract_debt_history($contract_log);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'html' => $html
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function count_value_impacted_call($array)
	{
		$count = 0;
		foreach ($array as $item) {
			if (!empty($item['reminder_now'])) {
				$count++;
			}
		}
		return $count;
	}

	private function count_number_of_call($array)
	{
		$count = 0;
		foreach ($array as $item) {
			if (!empty($item['result_reminder'])) {
				foreach ($item['result_reminder'] as $item1) {
					if ($item['created_at'] < $item1['created_at'] && $item['debt_caller_email'] == $item1['created_by']) {
						$count++;
					}
				}
			}
		}
		return $count;
	}

	private function lay_tien_goc_con_lai_chua_tra($code_contract)
	{
		$detail = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		if (!empty($detail)) {
			$root_debt = 0;
			foreach ($detail as $value) {
				if (!empty($value['tien_goc_1ky'])) {
					$root_debt += (double)$value['tien_goc_1ky'];
				}
			}
		}
		return $root_debt;
	}

	private function lay_nhom_no_hop_dong($code_contract)
	{
		$data = [];
		$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 1]);
		$detail1 = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 2]);
		if (!empty($detail)) {
			$time = 0;
			$current_day = strtotime(date('y-m-d'));
			$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
			$time = intval(($current_day - strtotime(date('Y-m-d', $datetime))) / (24 * 60 * 60));
			$data['time'] = $time;
			$data['ngay_den_han'] = $datetime;

			if ($time <= 0) {
				$data['bucket'] = 'B0';
			} else if ($time >= 1 && $time <= 30) {
				$data['bucket'] = 'B1';
			} else if ($time >= 31 && $time <= 60) {
				$data['bucket'] = 'B2';
			} else if ($time >= 61 && $time <= 90) {
				$data['bucket'] = 'B3';
			} else if ($time >= 91 && $time <= 120) {
				$data['bucket'] = 'B4';
			} else if ($time >= 121 && $time <= 150) {
				$data['bucket'] = 'B5';
			} else if ($time >= 151 && $time <= 180) {
				$data['bucket'] = 'B6';
			} else if ($time >= 181 && $time <= 360) {
				$data['bucket'] = 'B7';
			} else {
				$data['bucket'] = 'B8';
			}
		} else {
			if (!empty($detail1)) {
				$time1 = 0;
				foreach ($detail1 as $item) {
					$time1 += $item['so_ngay_cham_tra'];
				}
				$data['time'] = $time1;
				if ($time1 <= 0) {
					$data['bucket'] = 'B0';
				} else if ($time1 >= 1 && $time1 <= 30) {
					$data['bucket'] = 'B1';
				} else if ($time1 >= 31 && $time1 <= 60) {
					$data['bucket'] = 'B2';
				} else if ($time1 >= 61 && $time1 <= 90) {
					$data['bucket'] = 'B3';
				} else if ($time1 >= 91 && $time1 <= 120) {
					$data['bucket'] = 'B4';
				} else if ($time1 >= 121 && $time1 <= 150) {
					$data['bucket'] = 'B5';
				} else if ($time1 >= 151 && $time1 <= 180) {
					$data['bucket'] = 'B6';
				} else if ($time1 >= 181 && $time1 <= 360) {
					$data['bucket'] = 'B7';
				} else {
					$data['bucket'] = 'B8';
				}
			}
		}
		return $data;
	}

	private function get_id_user_call_thn_mb()
	{
		$data_role = $this->role_model->findOne(['slug' => 'call-thu-hoi-no-mien-bac']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	public function get_email_user_thn()
	{
		$user_thn = $this->role_model->findOne(array('slug' => 'phong-thu-hoi-no'));
		$thn = [];
		foreach ($user_thn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$thn[] = $value;
				}
			}
		}
		return $thn;
	}

	public function get_email_user_tp_thn()
	{
		$user_tp_thn = $this->role_model->findOne(array('slug' => 'tbp-thu-hoi-no'));
		$tp_thn = [];
		foreach ($user_tp_thn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$tp_thn[] = $value;
				}
			}
		}
		return $tp_thn;
	}

	public function get_email_user_lead_thn()
	{
		$user_tp_thn = $this->role_model->findOne(array('slug' => 'lead-thn'));
		$lead_thn = [];
		foreach ($user_tp_thn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$lead_thn[] = $value;
				}
			}
		}
		return $lead_thn;
	}

	public function get_email_user_call_thn_mb_post()
	{
		$user_email_thn_mb = $this->role_model->findOne(array('slug' => 'call-thu-hoi-no-mien-bac'));
		$email_call_thn_mb = [];
		foreach ($user_email_thn_mb['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mb[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_call_thn_mb
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_email_user_call_thn_mn_post()
	{
		$user_email_thn_mn = $this->role_model->findOne(array('slug' => 'call-thu-hoi-no-mien-nam'));
		$email_call_thn_mn = [];
		foreach ($user_email_thn_mn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mn[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_call_thn_mn
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function get_email_user_call_thn_mb_internal()
	{
		$user_email_thn_mb = $this->role_model->findOne(array('slug' => 'call-thu-hoi-no-mien-bac'));
		$email_call_thn_mb = [];
		foreach ($user_email_thn_mb['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mb[] = $value;
				}
			}
		}
		return $email_call_thn_mb;
	}

	private function get_email_user_call_thn_mn_internal()
	{
		$user_email_thn_mn = $this->role_model->findOne(array('slug' => 'call-thu-hoi-no-mien-nam'));
		$email_call_thn_mn = [];
		foreach ($user_email_thn_mn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mn[] = $value;
				}
			}
		}
		return $email_call_thn_mn;
	}

	public function get_email_user_field_thn_mb_post()
	{
		$user_email_thn_mb = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-bac'));
		$email_field_thn_mb = [];
		foreach ($user_email_thn_mb['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_field_thn_mb[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_field_thn_mb
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_email_user_field_thn_mn_post()
	{
		$user_email_thn_mn = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-nam'));
		$email_field_thn_mn = [];
		foreach ($user_email_thn_mn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_field_thn_mn[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_field_thn_mn
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function get_id_user_call_thn_mn()
	{
		$data_role = $this->role_model->findOne(['slug' => 'call-thu-hoi-no-mien-nam']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	private function get_id_tp_thn_mb()
	{
		$data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-bac']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	private function get_id_tp_thn_mn()
	{
		$data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-nam']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
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

	public function getRole_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$userId = !empty($data['user_id']) ? $data['user_id'] : '';
		$groupRoles = $this->role_model->find_where(array("status" => "active"));

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
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function sync_contract_to_caller_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';

		$detail = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$root_debt = 0;
		if (!empty($detail)) {
			foreach ($detail as $value) {
				if (!empty($value['tien_goc_1ky'])) {
					$root_debt += (double)$value['tien_goc_1ky'];
				}
			}
		}

		$detail_tempo = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = strtotime(date('m/d/Y'));
		$ngay_den_han = $current_day;
		if (!empty($detail_tempo)) {
			$ngay_den_han = !empty($detail_tempo[0]['ngay_ky_tra']) ? intval($detail_tempo[0]['ngay_ky_tra']) : $current_day;
		}
		$bucket = $this->lay_nhom_no_hop_dong($code_contract);
		$contract_caller = $this->contract_debt_caller_model->findOne(['code_contract' => $code_contract, 'month' => date('m'), 'year' => date('Y')]);
		$contract_field = $this->contract_assign_debt_model->findOne(['code_contract' => $code_contract, 'month' => date('m'), 'year' => date('Y')]);
		$contractDB = $this->contract_model->findOne(['code_contract' => $code_contract]);

		if (!empty($contractDB)) {
			$array_update = [
				"root_debt" => $root_debt,
				"ngay_den_han" => $ngay_den_han,
				"status_contract" => (int)$contractDB['status'],
				"bucket" => !empty($bucket['bucket']) ? $bucket['bucket'] : $contract_caller['bucket'],
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
			];
			$arr_update_field = [
				"status_contract" => (int)$contractDB['status']
			];
		}

		if (!empty($contract_caller)) {
			$this->contract_debt_caller_model->update(
				["_id" => $contract_caller['_id']],
				$array_update
			);
		}
		if (!empty($contract_field)) {
			$this->contract_assign_debt_model->update(
				["_id" => $contract_field['_id']],
				$arr_update_field
			);
		}
		$this->log_debt_caller_model->insert(
			[
				'type' => 'contract',
				'action' => 'update',
				"contract_id" => (string)$contractDB['_id'],
				'old' => $contract_caller,
				'new' => $array_update,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success!"
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function setup_time_to_field_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start_time = !empty($data['start_time']) ? $this->security->xss_clean($data['start_time'] . ' 00:00:00') : '';
		$end_time = !empty($data['end_time']) ? $this->security->xss_clean($data['end_time'] . ' 23:59:59') : '';

		if (empty($start_time)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Bạn chưa chọn thời gian bắt đầu!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($end_time)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Bạn chưa chọn thời gian kết thúc!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
		$array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
		$current_month = date('m');
		$current_year = date('Y');
		$current_date = strtotime(date('Y-m-d') . ' 23:59:59');
		if (!empty($start_time)) {
			$start_time_convert = strtotime($start_time);
			$day_of_start_time = date('d', $start_time_convert);
			$month_of_start_time = date('m', $start_time_convert);
		}
		if (!empty($end_time)) {
			$end_time_convert = strtotime($end_time);
			$day_of_end_time = date('d', $end_time_convert);
			$month_of_end_time = date('m', $end_time_convert);
		}
		if ($month_of_start_time != $current_month || $month_of_end_time != $current_month) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Ngày setup chuyển field không thuộc tháng hiện tại!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($day_of_start_time >= $day_of_end_time) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Ngày bắt đầu không được lớn hơn hoặc bằng ngày kết thúc!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (($day_of_start_time > 15 || $day_of_start_time < 10) || ($day_of_end_time > 15)) {
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Ngày setup chuyển field hợp lệ từ ngày 10-15!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		if (in_array($this->id, $array_tp_thn_mb_id)) {
			$param_time_to_field = [
				"area" => "MB",
				"start_time" => $start_time_convert,
				"end_time" => $end_time_convert,
				'month' => $current_month,
				'year' => $current_year,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			];
			$check_time_setup = $this->time_to_field_model->findOne(['area' => 'MB', "month" => date('m'), "year" => date('Y')]);
			if (!empty($check_time_setup)) {
				if ($end_time_convert <= $current_date) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Ngày kết thúc không được nhỏ hơn hoặc bằng ngày hiện tại!'
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		} elseif (in_array($this->id, $array_tp_thn_mn_id)) {
			$param_time_to_field = [
				"area" => "MN",
				"start_time" => $start_time_convert,
				"end_time" => $end_time_convert,
				'month' => $current_month,
				'year' => $current_year,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			];
			$check_time_setup = $this->time_to_field_model->findOne(['area' => 'MN', "month" => date('m'), "year" => date('Y')]);
			if (!empty($check_time_setup)) {
				if ($end_time_convert <= $current_date) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => 'Ngày kết thúc không được nhỏ hơn hoặc bằng ngày hiện tại!'
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}

		if (in_array($this->id, $array_tp_thn_mb_id)) {
			$contract_caller = $this->contract_debt_caller_model->find_where(["domain_contract" => "MB", "month" => date('m'), "year" => date('Y'), "status" => array('$nin' => [37, 277, 278, 279])]);
		} elseif (in_array($this->id, $array_tp_thn_mn_id)) {
			$contract_caller = $this->contract_debt_caller_model->find_where(["domain_contract" => "MN", "month" => date('m'), "year" => date('Y'), "status" => array('$nin' => [37, 277, 278, 279])]);
		} else {
			$contract_caller = array();
			$response = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Khu vực chưa xác định!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$array_update = [
			"start_time" => $start_time_convert,
			"end_time" => $end_time_convert
		];

		if (!empty($contract_caller)) {
			//Thêm time_setup vào bảng time_to_field
			if (empty($check_time_setup)) {
				$this->time_to_field_model->insert($param_time_to_field);
			} else {
				$this->time_to_field_model->update(
					['_id' => $check_time_setup['_id']],
					[
						"start_time" => $start_time_convert,
						"end_time" => $end_time_convert,
						"updated_at" => $this->createdAt,
						"updated_by" => $this->uemail
					]
				);
			}
			//Update time_setup cho từng HĐ Call
			foreach ($contract_caller as $contract) {
				$this->contract_debt_caller_model->update(
					["_id" => $contract["_id"]],
					$array_update
				);
			}
			if (!empty($check_time_setup)) {
				$time_to_field_old = $check_time_setup;
			} else {
				$time_to_field_old = array();
			}
			if (in_array($this->id, $array_tp_thn_mb_id)) {
				$param_debt_log = [
					'type' => 'time_to_field',
					'action' => 'setup',
					'area' => "MB",
					'old' => $time_to_field_old,
					'new' => $array_update,
					"created_at" => $this->createdAt,
					"created_by" => $this->uemail
				];
			} elseif (in_array($this->id, $array_tp_thn_mn_id)) {
				$param_debt_log = [
					'type' => 'time_to_field',
					'action' => 'setup',
					'area' => "MN",
					'old' => $time_to_field_old,
					'new' => $array_update,
					"created_at" => $this->createdAt,
					"created_by" => $this->uemail
				];
			} else {
				$param_debt_log = [
					'type' => 'time_to_field',
					'action' => 'setup',
					'area' => "undefined",
					'old' => $contract,
					'new' => $array_update,
					"created_at" => $this->createdAt,
					"created_by" => $this->uemail
				];
			}
			$this->log_debt_caller_model->insert($param_debt_log);
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thiết lập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_time_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
		$array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
		if (in_array($this->id, $array_tp_thn_mb_id)) {
			$time_setup = $this->time_to_field_model->findOne(['area' => 'MB', "month" => date('m'), "year" => date('Y')]);
		} elseif (in_array($this->id, $array_tp_thn_mn_id)) {
			$time_setup = $this->time_to_field_model->findOne(['area' => 'MN', "month" => date('m'), "year" => date('Y')]);
		}

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $time_setup,
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function check_time_post()
	{
		$current_date = strtotime(date('Y-m-d') . ' 23:59:59');
		$current_day = strtotime(date('m/d/Y'));
		echo '<pre>';
		print_r($current_date);
		echo '</pre>';
		echo '<pre>';
		print_r($current_day);
		echo '</pre>';
	}

	private function count_status_to_field($array)
	{
		$count = 0;
		foreach ($array as $item) {
			if (!empty($item['status']) && $item['status'] == 279) {
				$count++;
			}
		}
		return $count;
	}

	private function get_id_lead_thn_mb()
	{
		$data_role = $this->role_model->findOne(['slug' => 'lead-thn-mien-bac']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	public function get_id_lead_thn_mn()
	{
		$data_role = $this->role_model->findOne(['slug' => 'lead-thn-mien-nam']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	public function approve_all_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_contract_debt = !empty($data['contract_caller_id']) ? $this->security->xss_clean($data['contract_caller_id']) : '';
		$status = !empty($data['status']) ? $this->security->xss_clean($data['status']) : '';
		$approve_note = !empty($data['note']) ? $this->security->xss_clean($data['note']) : '';
		$contract_caller = $this->contract_debt_caller_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract_debt)]);
		if ($status == 2) {
			$note = "Bạn đã nhận được hợp đồng";
//			$this->push_notification_contract_to_call_thn((string)$contract_caller['debt_caller_id'], $status, $contract_caller, $note);
		} elseif ($status == 3) {
			$note = "Trưởng phòng từ chối duyệt hợp đồng gán cho Call";
			//$id_lead_assign = $this->user_model->findOne(['email' => $contract_caller['created_by']]);
//			$this->push_notification_contract_to_call_thn((string)$id_lead_assign['_id'], $status, $contract_caller, $note);
		} elseif ($status == 5) {
			$note = "Trưởng phòng đã xóa hợp đồng trong danh sách của bạn!";
//			$this->push_notification_contract_to_call_thn((string)$contract_caller['debt_caller_id'], $status, $contract_caller, $note);
		}
		$arr_update = [
			'status' => (int)$status,
			'note' => $approve_note,
			'updated_at' => $this->createdAt,
			'updated_by' => $this->uemail
		];
		$contract_debt_caller = $this->contract_debt_caller_model->findOneAndUpdate(
			["_id" => $contract_caller['_id']],
			$arr_update
		);
		$this->log_debt_caller_model->insert(
			[
				'type' => 'assign_contract_call',
				'action' => 'update',
				'old' => $contract_caller,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function push_notification_contract_to_call_thn($id_user, $status, $contract, $note)
	{
		if ($status == 3) {
			$tab = 'block';
		} elseif ($status == 5) {
			$tab = 'cancel';
		} else {
			$tab = 'assigned';
		}
		if (!empty($id_user)) {
			$data_notification = [
				'action_id' => (string)$contract['_id'],
				'action' => 'contract_caller',
				'detail' => "DebtCall/list_contract_call?tab=" . $tab . "&code_contract_disbursement=" . $contract['code_contract_disbursement'],
				'title' => $contract['customer_name'] . ' - ' . $contract['store_name'],
				'note' => $note,
				'user_id' => $id_user,
				'status' => 1, //1: new, 2 : read, 3: block,
				'status_contract' => $status,
				'type_notification' => 1, //1: thông báo miễn giảm,
				'created_at' => $this->createdAt,
				"created_by" => $this->uemail
			];
			$code_contract_disbursement = $contract['code_contract_disbursement'];
			$customer_name = $contract['customer_name'];

			$this->notification_model->insertReturnId($data_notification);
			$device = $this->device_model->find_where(['user_id' => $id_user]);
			if (!empty($device) && $id_user == $device[0]['user_id']) {
				$fcm = new Fcm();
				$to = [];
				foreach ($device as $de) {
					$to[] = $de->device_token;
				}
				$badge = $this->get_count_notification_user($id_user);
//				$click_action = 'http://localhost/tienngay/cpanel.tienngay/DebtCall/list_contract_call?code_contract_disbursement=' . $contract['code_contract_disbursement'] . '&tab=' . $tab;
//				$click_action = 'https://sandboxcpanel.tienngay.vn/DebtCall/list_contract_call?code_contract_disbursement='.$contract['code_contract_disbursement'] . '&tab=' . $tab;
				$click_action = 'https://cpanel.tienngay.vn/DebtCall/list_contract_call?code_contract_disbursement=' . $contract['code_contract_disbursement'] . '&tab=' . $tab;

				$fcm->setTitle($note);
				$fcm->setMessage("HĐ: $code_contract_disbursement, KH: $customer_name");
				$fcm->setClickAction($click_action);
				$fcm->setBadge($badge);
				$message = $fcm->getMessage();
				$result = $fcm->sendToTopicCpanel($to, $message, $message);
			}
		}
	}

	public function assign_contract_to_debt_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $this->security->xss_clean(trim($data['code_contract_disbursement'])) : '';
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean($data['customer_name']) : '';
		$email_field = !empty($data['email_field']) ? $this->security->xss_clean(trim($data['email_field'])) : '';
		$note = !empty($data['note']) ? $this->security->xss_clean(trim($data['note'])) : '';
		$contract = $this->contract_debt_model->findOne([
			'code_contract' => trim($code_contract),
			'status' =>
				[
					'$gte' => 17,
					'$lt' => 35,
				]
		]);
		$user = $this->user_model->findOne(['email' => trim($email_field), 'status' => 'active']);
		$user_name = $user['full_name'];
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => 'Mã hợp đồng không đúng hoặc không tồn tại!',
				'data2' => $code_contract_disbursement
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			//	Check miền của HĐ
			$store_contract = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['store']['id'])]);
			$area_contract = $this->area_model->findOne(array("code" => $store_contract['code_area']));
			$domain_contract = $area_contract['domain']->code;
			$array_tp_thn_mb_id = $this->get_id_tp_thn_mb();
			$array_tp_thn_mn_id = $this->get_id_tp_thn_mn();
			$array_lead_thn_mb_id = $this->get_id_lead_thn_mb();
			$array_lead_thn_mn_id = $this->get_id_lead_thn_mn();
			if (in_array($this->id, $array_tp_thn_mb_id) || in_array($this->id, $array_lead_thn_mb_id)) {
				if ($domain_contract != 'MB') {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' không thuộc khu vực miền Bắc!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else if (in_array($this->id, $array_tp_thn_mn_id) || in_array($this->id, $array_lead_thn_mn_id)) {
				if ($domain_contract != 'MN') {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' không thuộc khu vực miền Nam!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else {
				if (!in_array($domain_contract, ["MB", "MN"])) {
					$response = [
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' có khu vực chưa xác định!!',
						'data2' => $contract['code_contract_disbursement']
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
			if (empty($user)) {
				$response = array(
					'status' => REST_Controller::HTTP_BAD_REQUEST,
					'message' => 'User ' . $email_field . ' không tồn tại!',
					'data2' => $code_contract_disbursement
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {

				$email_field_mb = $this->get_email_user_field_thn_mb_internal();
				$email_field_mn = $this->get_email_user_field_thn_mn_internal();
				$email_field_mb_b4 = $this->field_thn_mb_b4();
				$email_field_mn_b4 = $this->field_thn_mn_b4();

				$email_field_all = array_merge($email_field_mb, $email_field_mn, $email_field_mb_b4, $email_field_mn_b4);
				$unique_email_field = array_unique($email_field_all);
				if (!in_array($user['email'], $unique_email_field)) {
					$response = array(
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'message' => 'User ' . $email_field . ' không phải nhân viên Field!!',
						'data2' => $code_contract_disbursement
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
				if ($contract['status'] == 19) {
					$first_day_of_month = strtotime(date('Y-m-01') . '00:00:00');
					$current_day = strtotime(date('Y-m-d') . '23:59:59');
					$check_payment_current_month = $this->transaction_model->find_where(array('code_contract' => $contract['code_contract'], 'status' => 1, 'type' => 3, 'date_pay' => array('$gte' => $first_day_of_month, '$lte' => $current_day)));
					if (!empty($check_payment_current_month)) {
						$root_debt = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
						$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
						$assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
						$current_month = (string)date('m');
						$current_year = (string)date('Y');
						$contract_call = $this->contract_debt_caller_model->findOne(['code_contract' => $contract['code_contract'], 'month' => $current_month, 'year' => $current_year]);
						if (!empty($contract_call) && !in_array($contract_call['status'], [3, 4, 5, 37])) {
							$response = [
								'status' => REST_Controller::HTTP_BAD_REQUEST,
								'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' Xóa hợp đồng Call trước khi gán Field!',
								'data2' => $contract['code_contract_disbursement']
							];
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						} else {
							if (!empty($assign)) {
								if (in_array($assign['status'], [3, 4])) {
									$arr_update = [
										"user_id" => (string)$user['_id'],
										"debt_field_email" => $email_field,
										"debt_field_name" => $user_name,
										'status' => 2
									];
									$this->contract_assign_debt_model->update(
										['_id' => $assign['_id']],
										$arr_update
									);
									$this->log_call_debt_model->insert(
										[
											'type' => 'assign_contract_field',
											'action' => 'update',
											'old' => $assign,
											'new' => $arr_update,
											"created_at" => $this->createdAt,
											"created_by" => $this->uemail
										]
									);
								} else {
									$response = [
										'status' => REST_Controller::HTTP_BAD_REQUEST,
										'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' đã tồn tại hoặc đã gán cho nhân viên field xử lý!!',
										'data2' => $contract['code_contract_disbursement']
									];
									$this->set_response($response, REST_Controller::HTTP_OK);
									return;
								}
							} else {
								$param_insert = [
									"contract_id" => (string)$contract['_id'],
									'code_contract' => $code_contract,
									'code_contract_disbursement' => $contract['code_contract_disbursement'],
									'current_address' => $contract['current_address'],
									'customer_name' => $contract['customer_infor']['customer_name'],
									'customer_phone_number' => $contract['customer_infor']['customer_phone_number'],
									"user_id" => (string)$user['_id'],
									'debt_field_email' => $email_field,
									'debt_field_name' => $user_name,
									'domain_contract' => $domain_contract,
									'amount_money' => $contract['loan_infor']['amount_money'],
									'number_day_loan' => $contract['loan_infor']['number_day_loan'],
									'status_contract' => $contract['status'],
									'status' => 2,
									"pos_origin" => !empty($root_debt) ? round($root_debt) : 0,
									"pos" => !empty($root_debt) ? round($root_debt) : 0,
									"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
									"time_due" => !empty($bucket['time']) ? $bucket['time'] : 0,
									"ngay_den_han" => !empty($bucket['ngay_den_han']) ? $bucket['ngay_den_han'] : 0,
									'month' => date('m'),
									'year' => date('Y'),
									'store_id' => $contract['store']['id'],
									'store_name' => $contract['store']['name'],
									'note' => $note,
									'created_at' => $this->createdAt,
									'created_by' => $this->uemail
								];
								$this->contract_assign_debt_model->insert($param_insert);
								$this->log_call_debt_model->insert(
									[
										'type' => 'assign_contract_field',
										'action' => 'insert',
										"contract_id" => (string)$contract['_id'],
										'old' => $param_insert,
										'new' => $param_insert,
										"created_at" => $this->createdAt,
										"created_by" => $this->uemail
									]
								);
							}
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'Gán hợp đồng cho Field thành công!',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					}
				} else {
					$result_reminder = !empty($contract['result_reminder']) ? $contract['result_reminder'] : array();
					$note_reminder = array(
						"month" => "Tháng " . date('n'),
						"reminder" => "37",
						"created_at" => $this->createdAt,
						"created_by" => $this->uemail
					);
					$dataPush = array();
					array_push($dataPush, $note_reminder);
					foreach ($result_reminder as $key => $value) {
						array_push($dataPush, $value);
					}
					$arrUpdate = array(
						'result_reminder' => $dataPush,
						'reminder_now' => "37",
						'user_debt' => (string)$user['_id'],
					);
					$this->contract_model->update(['_id' => $contract['_id']], $arrUpdate);
					// Insert log
					$log = array(
						"type" => "contract",
						"action" => "note_reminder",
						"contract_id" => $contract['_id'],
						"old" => $contract,
						"new" => $arrUpdate,
						"created_at" => $this->createdAt,
						"created_by" => $this->uemail
					);
					//$this->log_model->insert($log);
					$this->log_call_debt_model->insert($log);
					$root_debt = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
					$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
					$assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
					$current_month = (string)date('m');
					$current_year = (string)date('Y');
					$contract_call = $this->contract_debt_caller_model->findOne(['code_contract' => $contract['code_contract'], 'month' => $current_month, 'year' => $current_year]);
					if (!empty($contract_call) && !in_array($contract_call['status'], [3, 4, 5, 37])) {
						$response = [
							'status' => REST_Controller::HTTP_BAD_REQUEST,
							'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' Xóa hợp đồng Call trước khi gán Field!',
							'data2' => $contract['code_contract_disbursement']
						];
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
//						$array_update_call = [
//							'status' => 4,
//							'updated_at' => $this->createdAt,
//							'updated_by' => $this->uemail
//						];
//						if (!empty($contract_call)) {
//							$this->contract_debt_caller_model->update(
//								['_id' => new \MongoDB\BSON\ObjectId($contract_call['_id'])],
//								$array_update_call
//							);
//							$this->log_debt_caller_model->insert(
//								[
//									'type' => 'assign_contract_call',
//									'action' => 'update',
//									'old' => $contract_call,
//									'new' => $array_update_call,
//									"created_at" => $this->createdAt,
//									"created_by" => $this->uemail
//								]
//							);
//						}
						if (!empty($assign)) {
							if (in_array($assign['status'], [3, 4])) {
								$arr_update = [
									"user_id" => (string)$user['_id'],
									"debt_field_email" => $email_field,
									"debt_field_name" => $user_name,
									'status' => 1
								];
								$this->contract_assign_debt_model->update(
									['_id' => $assign['_id']],
									$arr_update
								);
								$this->log_call_debt_model->insert(
									[
										'type' => 'assign_contract_field',
										'action' => 'update',
										'old' => $assign,
										'new' => $arr_update,
										"created_at" => $this->createdAt,
										"created_by" => $this->uemail
									]
								);
							} else {
								$response = [
									'status' => REST_Controller::HTTP_BAD_REQUEST,
									'message' => 'Hợp đồng: ' . $contract['code_contract_disbursement'] . ' đã tồn tại hoặc đã gán cho nhân viên field xử lý!!',
									'data2' => $contract['code_contract_disbursement']
								];
								$this->set_response($response, REST_Controller::HTTP_OK);
								return;
							}
						} else {
							$param_insert = [
								"contract_id" => (string)$contract['_id'],
								'code_contract' => $code_contract,
								'code_contract_disbursement' => $contract['code_contract_disbursement'],
								'current_address' => $contract['current_address'],
								'customer_name' => $contract['customer_infor']['customer_name'],
								'customer_phone_number' => $contract['customer_infor']['customer_phone_number'],
								"user_id" => (string)$user['_id'],
								'debt_field_email' => $email_field,
								'debt_field_name' => $user_name,
								'domain_contract' => $domain_contract,
								'amount_money' => $contract['loan_infor']['amount_money'],
								'number_day_loan' => $contract['loan_infor']['number_day_loan'],
								'status_contract' => $contract['status'],
								'status' => 1,
								"pos_origin" => !empty($root_debt) ? round($root_debt) : 0,
								"pos" => !empty($root_debt) ? round($root_debt) : 0,
								"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
								"time_due" => !empty($bucket['time']) ? $bucket['time'] : 0,
								"ngay_den_han" => !empty($bucket['ngay_den_han']) ? $bucket['ngay_den_han'] : 0,
								'month' => date('m'),
								'year' => date('Y'),
								'store_id' => $contract['store']['id'],
								'store_name' => $contract['store']['name'],
								'note' => $note,
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail
							];
							$this->contract_assign_debt_model->insert($param_insert);
							$this->log_call_debt_model->insert(
								[
									'type' => 'assign_contract_field',
									'action' => 'insert',
									"contract_id" => (string)$contract['_id'],
									'old' => $param_insert,
									'new' => $param_insert,
									"created_at" => $this->createdAt,
									"created_by" => $this->uemail
								]
							);
						}
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Gán hợp đồng cho Field thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
	}

	private function get_email_user_field_thn_mb_internal()
	{
		$user_email_thn_mb = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-bac'));
		$email_call_thn_mb = [];
		foreach ($user_email_thn_mb['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mb[] = $value;
				}
			}
		}
		return $email_call_thn_mb;
	}

	private function get_email_user_field_thn_mn_internal()
	{
		$user_email_thn_mn = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-nam'));
		$email_call_thn_mn = [];
		foreach ($user_email_thn_mn['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_call_thn_mn[] = $value;
				}
			}
		}
		return $email_call_thn_mn;
	}

	private function get_id_user_field_thn_mb()
	{
		$data_role = $this->role_model->findOne(['slug' => 'field-thu-hoi-no-mien-bac']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		return $array_user_id;
	}

	public function get_all_contract_field_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($data['start']) ? strtotime(trim($this->security->xss_clean($data['start'])) . ' 00:00:00') : strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = !empty($data['end']) ? strtotime(trim($this->security->xss_clean($data['end'])) . ' 23:59:59') : strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : '';
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : '';
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$customer_phone = !empty($data['customer_phone']) ? $data['customer_phone'] : '';
		$email = !empty($data['email']) ? $data['email'] : '';
		$status = !empty($data['status']) ? $data['status'] : '';
		$status_contract = !empty($data['status_contract']) ? $data['status_contract'] : '';
		$store_id = !empty($data['store_id']) ? $data['store_id'] : '';
		$tab = !empty($data['tab']) ? $data['tab'] : '';
		$condition = array();

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => $start,
				'end' => $end
			);
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($customer_phone)) {
			$condition['customer_phone'] = trim($customer_phone);
		}
		if (!empty($email)) {
			$condition['email'] = trim($email);
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}
		if (!empty($status_contract)) {
			$condition['status_contract'] = $status_contract;
		}
		if (!empty($store_id)) {
			$condition['store'] = $store_id;
		}
		if (!empty($tab)) {
			$condition['tab'] = trim($tab);
		}

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 60;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$array_field_thn_mb_user_id = $this->get_id_user_field_thn_mb();
		$array_id_tp_thn_mb = $this->get_id_tp_thn_mb();
		$array_id_lead_thn_mb = $this->get_id_lead_thn_mb();

		if (in_array($this->id, $array_field_thn_mb_user_id) || in_array($this->id, $array_id_tp_thn_mb) || in_array($this->id, $array_id_lead_thn_mb)) {
			$email_field_mb = $this->get_email_user_field_thn_mb_internal();
			$email_field_mb_b4 = $this->field_thn_mb_b4();
			$merge_email_field_mb = array_merge($email_field_mb, $email_field_mb_b4);
			$list_user_field = array_unique($merge_email_field_mb);
			$condition['domain_contract'] = 'MB';
		} else {
			$email_field_mn = $this->get_email_user_field_thn_mn_internal();
			$email_field_mn_b4 = $this->field_thn_mn_b4();
			$merge_email_field_mn = array_merge($email_field_mn, $email_field_mn_b4);
			$list_user_field = array_unique($merge_email_field_mn);

			$condition['domain_contract'] = 'MN';
		}

		$user_email_thn = $this->get_email_user_thn();
		$user_email_tp_thn = $this->get_email_user_tp_thn();
		if (in_array($this->uemail, $user_email_thn) && in_array($this->uemail, $user_email_tp_thn) !== true) {
			$condition['debt_field_email'] = $this->uemail;
		}
		$contract_debt_field = $this->contract_assign_debt_model->get_all_contract_assign_to_field(array(), $condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->contract_assign_debt_model->get_all_contract_assign_to_field(array(), $condition);
		$contract_review = $this->contract_assign_debt_model->get_all_contract_review($condition);
		if ($tab == 'review') {
			$arr_return_review = [];
			$bucket_b0 = [];
			$bucket_b1 = [];
			$bucket_b2 = [];
			$bucket_b3 = [];
			$bucket_b4 = [];
			$bucket_b5 = [];
			$bucket_b6 = [];
			$bucket_b7 = [];
			$bucket_b8 = [];
			if (!empty($list_user_field)) {
				foreach ($list_user_field as $email_field) {
					if (!empty($contract_review)) {
						foreach ($contract_review as $key => $contract_call) {
							if ($email_field == $contract_call['debt_field_email']) {
								if ($contract_call['bucket'] == 'B0') {
									$bucket_b0[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B1') {
									$bucket_b1[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B2') {
									$bucket_b2[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B3') {
									$bucket_b3[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B4') {
									$bucket_b4[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B5') {
									$bucket_b5[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B6') {
									$bucket_b6[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B7') {
									$bucket_b7[] = $contract_call;
								}
								if ($contract_call['bucket'] == 'B8') {
									$bucket_b8[] = $contract_call;
								}
							}
						}
					}
				}
			}
			$ema = 0;
			if (!empty($list_user_field)) {
				foreach ($list_user_field as $key => $email_field) {
					$ema++;
					$sum_all_by_email = sum_values($contract_review, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_all_by_email = count_values($contract_review, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b0 = sum_values($bucket_b0, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b0 = count_values($bucket_b0, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b1 = sum_values($bucket_b1, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b1 = count_values($bucket_b1, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b2 = sum_values($bucket_b2, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b2 = count_values($bucket_b2, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b3 = sum_values($bucket_b3, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b3 = count_values($bucket_b3, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b4 = sum_values($bucket_b4, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b4 = count_values($bucket_b4, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b5 = sum_values($bucket_b5, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b5 = count_values($bucket_b5, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b6 = sum_values($bucket_b6, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b6 = count_values($bucket_b6, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b7 = sum_values($bucket_b7, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b7 = count_values($bucket_b7, 'debt_field_email', $email_field, '', '', '', '');
					$sum_debt_b8 = sum_values($bucket_b8, 'debt_field_email', $email_field, '', '', '', '', 'pos');
					$count_debt_b8 = count_values($bucket_b8, 'debt_field_email', $email_field, '', '', '', '');
					$arr_return_review += [$ema => [
						'email' => $email_field,
						'count_all_by_email' => $count_all_by_email,
						'sum_all_by_email' => $sum_all_by_email,
						'count_b0' => $count_debt_b0,
						'sum_b0' => $sum_debt_b0,
						'count_b1' => $count_debt_b1,
						'sum_b1' => $sum_debt_b1,
						'count_b2' => $count_debt_b2,
						'sum_b2' => $sum_debt_b2,
						'count_b3' => $count_debt_b3,
						'sum_b3' => $sum_debt_b3,
						'count_b4' => $count_debt_b4,
						'sum_b4' => $sum_debt_b4,
						'count_b5' => $count_debt_b5,
						'sum_b5' => $sum_debt_b5,
						'count_b6' => $count_debt_b6,
						'sum_b6' => $sum_debt_b6,
						'count_b7' => $count_debt_b7,
						'sum_b7' => $sum_debt_b7,
						'count_b8' => $count_debt_b8,
						'sum_b8' => $sum_debt_b8,
					]];
				}
			}
		}
		if (!empty($contract_debt_field)) {
			foreach ($contract_debt_field as $key => $contract_field) {
				$contractDB = $this->contract_model->find_one_select(
					['code_contract' => $contract_field['code_contract']],
					['code_contract', 'status', 'original_debt.du_no_goc_con_lai', 'current_address.province', 'current_address.district']);
				$bucket = $this->lay_nhom_no_hop_dong($contractDB['code_contract']);
				if ($contract_field['code_contract'] == $contractDB['code_contract']) {
					$contract_field['status_contract_realtime'] = $contractDB['status'];
					$contract_field['bucket'] = $bucket['bucket'];
					$contract_field['time_due'] = $bucket['time'];
					$contract_field['status_contract_realtime'] = $contractDB['status'];
					$contract_field['debt_root_contract'] = $contractDB['original_debt']['du_no_goc_con_lai'];
					$contract_field['province'] = $contractDB['current_address']['province'];
					$contract_field['district'] = $contractDB['current_address']['district'];

					if ($contractDB['status'] == 19) {
						$contract_field['complete'] = true;
					}
					$transaction = $this->transaction_model->findOne(['date_pay' => ['$gte' => $start, '$lte' => $end], 'code_contract' => (string)$contract_field['code_contract']]);
					if (!empty($contractDB['user_debt'])) {
						if (!empty($transaction) && ($contract_field['time_due'] >= $contractDB['debt']['so_ngay_cham_tra'])) {
							$contract_field['complete'] = true;
						}
					}
					$debt = $this->contract_debt_recovery_model->find_where(['contract_id' => (string)$contract_field['contract_id'], 'created_at' => ['$gte' => $start, '$lte' => $end]]);
					if (empty($debt)) {
						$contract_field['evaluate'] = 3;
					} else {
						if (!empty($debt[0]['evaluate'])) {
							if (in_array($contract_field['status'], [2])) {
								$this->contract_assign_debt_model->update(
									['_id' => (string)$contract_field['_id']], [
										'status' => 5
									]
								);
							}
							if (!empty($debt[0]['evaluate'] == 1)) {
								$contract_field['evaluate'] = 1;
							}
							if (!empty($debt[0]['evaluate'] == 2)) {
								$contract_field['evaluate'] = 2;
							}
							if (!empty($debt[0]['evaluate'] == 4)) {
								$contract_field['evaluate'] = 4;
							}
							if (!empty($debt[0]['evaluate'] == 5)) {
								$contract_field['evaluate'] = 5;
							}
							if (!empty($debt[0]['evaluate'] == 6)) {
								$contract_field['evaluate'] = 6;
							}
						} else {
							$contract_field['evaluate'] = 3;
						}
					}
					$contract_field['debt_log'] = !empty($debt) ? $debt[0] : '';
					if ($contract_field['bucket'] == 'B0') {
						if (!empty($transaction)) {
							$contract_field['complete'] = true;
						}
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_debt_field,
			'total' => $total,
			'data_review' => $arr_return_review
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_all_contract_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_contract_debt = !empty($data['contract_field_id']) ? $this->security->xss_clean($data['contract_field_id']) : '';
		$status = !empty($data['status']) ? $this->security->xss_clean($data['status']) : '';
		$approve_note = !empty($data['note']) ? $this->security->xss_clean($data['note']) : '';
		$contract_field = $this->contract_assign_debt_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract_debt)]);
		$arr_update = [
			'status' => (int)$status,
			'note' => $approve_note,
			'evaluate' => 3,
			'updated_at' => $this->createdAt,
			'updated_by' => $this->uemail
		];
		$contract_debt_caller = $this->contract_assign_debt_model->findOneAndUpdate(
			["_id" => $contract_field['_id']],
			$arr_update
		);
		$this->log_call_debt_model->insert(
			[
				'type' => 'assign_contract_field',
				'action' => 'update',
				'old' => $contract_field,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_contract_field_log_post()
	{
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$contract_log = $this->log_call_debt_model->contractLog(['contract_id' => $contract_id]);
		$html = gen_html_contract_field_history($contract_log);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'html' => $html
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_email_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id_contract_debt = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$email_debt_field = !empty($data['email_debt_field']) ? $this->security->xss_clean($data['email_debt_field']) : '';
		if (empty($id_contract_debt)) {
			$response = [
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => 'id đang trống!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($email_debt_field)) {
			$response = [
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => 'email field đang trống!'
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$find_user = $this->user_model->findOne(['email' => $email_debt_field]);
		$contract_assign = $this->contract_assign_debt_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_contract_debt)]);
		if (!empty($contract_assign)) {
			$this->contract_assign_debt_model->update(
				['_id' => $contract_assign['_id']],
				[
					'debt_field_id' => (string)$find_user['_id'],
					'debt_field_email' => $email_debt_field,
					'debt_field_name' => $find_user['full_name']
				],
			);
			$this->contract_model->update(
				['code_contract' => $contract_assign['code_contract']],
				['user_debt' => (string)$find_user['_id']]
			);
		}
		$data['note'] = "Chuyển nhân viên Filed";
		$this->log_call_debt_model->insert(
			[
				'type' => 'assign_contract_field',
				'action' => 'update',
				'old' => $contract_assign,
				'new' => $data,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			]
		);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhập nhân viên thành công!'
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function cronStatusContractAssignedToField_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$contract_field = $this->contract_assign_debt_model->getAllByCurrentMonth();
		if (!empty($contract_field)) {
			foreach ($contract_field as $contract_f) {
				$contractDB = $this->contract_model->find_one_select(
					['_id' => new \MongoDB\BSON\ObjectId($contract_f['contract_id'])],
					['code_contract', 'status', 'current_address', 'store', 'customer_infor', 'loan_infor', 'code_contract_disbursement']);
				$store_contract = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contractDB['store']['id'])]);
				$area_contract = $this->area_model->findOne(array("code" => $store_contract['code_area']));
				$domain_contract = $area_contract['domain']->code;
				$user = $this->user_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract_f['user_id']), 'status' => 'active']);
				$status_recovery = $this->contract_debt_recovery_model->find_where(['contract_id' => $contract_f['contract_id']]);
				$evaluate = $status_recovery[0]['evaluate'];
				if (empty($evaluate)) {
					$arr_update = [
						'code_contract' => $contractDB['code_contract'],
						'code_contract_disbursement' => $contractDB['code_contract_disbursement'],
						'customer_name' => $contractDB['customer_infor']['customer_name'],
						'customer_phone_number' => $contractDB['customer_infor']['customer_phone_number'],
						'debt_field_email' => $user['email'],
						'debt_field_name' => $user['full_name'],
						'amount_money' => $contractDB['loan_infor']['amount_money'],
						'number_day_loan' => $contractDB['loan_infor']['number_day_loan'],
						'domain_contract' => $domain_contract,
						'current_address' => $contractDB['current_address'],
						'store_id' => $contractDB['store']['id'],
						'store_name' => $contractDB['store']['name'],
						'status' => 2,
						'status_contract' => $contractDB['status'],
						'evaluate' => 3,
						'note' => 'Đồng bộ dữ liệu import hợp đồng cho Filed từ đầu tháng 11/2021',
						'updated_at' => $this->createdAt,
						'updated_by' => $this->uemail
					];
				} else {
					$arr_update = [
						'code_contract' => $contractDB['code_contract'],
						'code_contract_disbursement' => $contractDB['code_contract_disbursement'],
						'customer_name' => $contractDB['customer_infor']['customer_name'],
						'customer_phone_number' => $contractDB['customer_infor']['customer_phone_number'],
						'debt_field_email' => $user['email'],
						'debt_field_name' => $user['full_name'],
						'amount_money' => $contractDB['loan_infor']['amount_money'],
						'number_day_loan' => $contractDB['loan_infor']['number_day_loan'],
						'domain_contract' => $domain_contract,
						'current_address' => $contractDB['current_address'],
						'store_id' => $contractDB['store']['id'],
						'store_name' => $contractDB['store']['name'],
						'status' => 2,
						'status_contract' => $contractDB['status'],
						'evaluate' => $status_recovery[0]['evaluate'],
						'note' => 'Đồng bộ dữ liệu import hợp đồng cho Filed từ đầu tháng 11/2021',
						'updated_at' => $this->createdAt,
						'updated_by' => $this->uemail
					];
				}
				$this->contract_assign_debt_model->update(['_id' => $contract_f['_id']], $arr_update);
				$this->log_call_debt_model->insert(
					[
						'type' => 'assign_contract_field',
						'action' => 'insert',
						"contract_id" => (string)$contractDB['_id'],
						'old' => $contract_f,
						'new' => $arr_update,
						"created_at" => $this->createdAt,
						"created_by" => $this->uemail
					]
				);
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success!'
		];
		$this->response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_id_tp_thn_mb_for_cpanel_post()
	{
		$data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-bac']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $array_user_id
		];
		$this->response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_id_tp_thn_mn_for_cpanel_post()
	{
		$data_role = $this->role_model->findOne(['slug' => 'tbp-thn-mien-nam']);
		$array_user_id = [];
		if (!empty($data_role)) {
			foreach ($data_role['users'] as $key => $role) {
				foreach ($role as $key1 => $item) {
					array_push($array_user_id, $key1);
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $array_user_id
		];
		$this->response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function push_notification_to_tpthn_post()
	{
		$data = $this->input->post();
		$id_user = !empty($data['id_user']) ? $this->security->xss_clean($data['id_user']) : '';
		$note = "Bạn nhận được yêu cầu duyệt hợp đồng Field";
		$title = "Duyệt hợp đồng Field";
		if (!empty($id_user)) {
			$data_notification = [
				'action' => 'import_contract_field',
				'detail' => "Debt_manager_app/list_contract_field?tab=review",
				'title' => $title,
				'note' => $note,
				'user_id' => $id_user,
				'status' => 1, //1: new, 2 : read, 3: block,
				'created_at' => $this->createdAt,
				"created_by" => $this->uemail
			];

			$this->notification_model->insertReturnId($data_notification);
			$device = $this->device_model->find_where(['user_id' => $id_user]);
			if (!empty($device) && $id_user == $device[0]['user_id']) {
				$fcm = new Fcm();
				$to = [];
				foreach ($device as $de) {
					$to[] = $de->device_token;
				}
				$badge = $this->get_count_notification_user($id_user);
				$click_action = 'https://cpanel.tienngay.vn/Debt_manager_app/list_contract_field?tab=review';
				$fcm->setTitle($note);
				$fcm->setMessage($title);
				$fcm->setClickAction($click_action);
				$fcm->setBadge($badge);
				$message = $fcm->getMessage();
				$result = $fcm->sendToTopicCpanel($to, $message, $message);
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Send success!'
		];
		$this->response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_count_notification_user($user_id)
	{
		$condition = [];
		$condition['user_id'] = (string)$user_id;
		$condition['type_notification'] = 1;
		$condition['status'] = 1;
		$unRead = $this->notification_model->get_count_notification_user($condition);
		return $unRead;
	}

	public function test_tattoan_post()
	{
		$data = $this->input->post();
		$code_contract = $data['code_contract'];
		$first_day_of_month = strtotime(date('Y-m-01') . '00:00:00');
		$current_day = strtotime(date('Y-m-d') . '23:59:59');
		var_dump($code_contract);
		var_dump($first_day_of_month);
		var_dump($current_day);
		$check_payment_current_month = $this->transaction_model->find_where(array('code_contract' => $code_contract, 'status' => 1, 'type' => 3, 'date_pay' => array('$gte' => $first_day_of_month, '$lte' => $current_day)));
		echo '<pre>';
		print_r($check_payment_current_month);
		echo '</pre>';
	}

	public function field_thn_mb_b4()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-bac-b4")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function field_thn_mn_b4()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-nam-b4")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);
						}
					}
				}
			}
		}
		return $data;
	}

	public function get_email_field_mb_b4_post()
	{
		$email_field_mb_b4 = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-bac-b4'));
		$email_field_mb_b4_arr = [];
		foreach ($email_field_mb_b4['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_field_mb_b4_arr[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_field_mb_b4_arr
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_email_field_mn_b4_post()
	{
		$email_field_mn_b4 = $this->role_model->findOne(array('slug' => 'field-thu-hoi-no-mien-nam-b4'));
		$email_field_mn_b4_arr = [];
		foreach ($email_field_mn_b4['users'] as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					$email_field_mn_b4_arr[] = $value;
				}
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $email_field_mn_b4_arr
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function field_thn_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}
	public function field_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function call_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "call-thu-hoi-no-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function call_thn_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "call-thu-hoi-no-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function getContractCallDetailTHN_post()
	{
		$data = $this->input->post();
		$month = !empty($data['month']) ? $data['month'] : date('m') ;
		$year = !empty($data['year']) ? $data['year'] : date('Y');
		$condition = [];
		$leadTHNMB = $this->listLeadThnMb();
		$leadTHNMN = $this->listLeadThnMn();
		$currentUser = $this->uemail;
//		$currentUser = 'thuyetdv@tienngay.vn';
		if(in_array($currentUser, $leadTHNMB)){
			$condition['domain_contract'] = "MB";
		}elseif(in_array($currentUser, $leadTHNMN)){
			$condition['domain_contract'] = "MN";
		}
		$searchLike = '';
		$condition['month'] = $month;
		$condition['year'] = $year;
		$fdate = strtotime($year . '-' . $month . '-01');
		$tdate = strtotime($this->get_created_at_with_year($month, $year));
		$contract = $this->contract_debt_caller_model->getAllContractCall($condition);

		foreach ($contract as $i => $item) {
			$contractDetail = $this->contract_model->findOne(['_id' => new MongoDB\BSON\ObjectId($item->contract_id)]);
			$storeDetail = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($item->store_id)]);
			$logCall = $this->log_call_debt_model->find_where_reminder(['created_by' => $item->debt_caller_email,'action' => 'note_reminder', 'contract_id' => $item->contract_id, 'created_at' => ['$lte' => $tdate,'$gte' => $fdate]]);
			$contract[$i]['contractDetail'] = $contractDetail;
			$contract[$i]['storeDetail'] = $storeDetail;

			$tien_thu_trong_thang = $this->transaction_model->find_where(['status' => 1,'code_contract' => $item['code_contract'], 'date_pay' => ['$gte' => $fdate,'$lte' => $tdate]]);
			foreach ($tien_thu_trong_thang as $t) {
				$contract[$i]['tien_thu_trong_thang'] += $t['total'];
			}
			$tien_1_ky_phai_tra = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $item['code_contract']]);
			$contract[$i]['tien_ky'] = $tien_1_ky_phai_tra[0]['tien_tra_1_ky'];
			$so_ky_thanh_toan = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $item['code_contract'], 'status' => 2]);
			$contract[$i]['so_ky_da_thanh_toan'] = count($so_ky_thanh_toan);
			$tempo = $this->contract_tempo_model->find_where(['code_contract' => $item['code_contract'], 'status' => 1]);

				if (!empty($tempo)) {
					$contract[$i]['ngay_den_han_thanh_toan'] = $tempo[0]['ngay_ky_tra'];
				}

			if(!empty($logCall)){

				$contract[$i]['reminderDetail'][] = !empty($logCall[0]['new']['note']) ? $logCall[0]['new']['note'] : note_renewal($logCall[0]['new']['result_reminder']);
				$contract[$i]['reminderDate'][] = !empty($logCall[0]['created_at']) ? date('d/m/Y',$logCall[0]['created_at']) : '';
				foreach ($logCall[0]['old']['result_reminder'] as $r => $reminder) {
					if($fdate < (int)$reminder['created_at'] && (int)$reminder['created_at'] < $tdate){
						$contract[$i]['reminderDetail'][] = !empty($reminder['note']) ? $reminder['note'] : note_renewal($reminder['reminder']);
						$contract[$i]['reminderDate'][] = !empty($reminder['created_at']) ? date('d/m/y', $reminder['created_at']) : '';
					}
				}
			}

			$contract[$i]['reminderResult'] = !empty($contractDetail['reminder_now']) ? note_renewal($contractDetail['reminder_now']) : '';
			$contract[$i]['ky_tra_hien_tai'] = $contractDetail['debt']['ky_tra_hien_tai'];
			$contract[$i]['relativeDetail'] = $contractDetail['relative_infor']['type_relative_1'] . ',' . $contractDetail['relative_infor']['type_relative_2'];
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $contract
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getContractFieldDetailTHN_post()
	{
		$data = $this->input->post();
		$month = !empty($data['month']) ? $data['month'] : date('m');
		$year = !empty($data['year']) ? $data['year'] : date('Y');
		$condition = [];
		$currentUser = $this->uemail;
//		$currentUser = 'thuyetdv@tienngay.vn';
		$leadTHNMB = $this->listLeadThnMb();
		$leadTHNMN = $this->listLeadThnMn();

		if (in_array($currentUser, $leadTHNMB)) {
			$condition['domain_contract'] = "MB";
		} elseif (in_array($currentUser, $leadTHNMN)) {
			$condition['domain_contract'] = "MN";
		}
		$searchLike = '';
		$condition['month'] = $month;
		$condition['year'] = $year;
		$fdate = strtotime($year . '-' . $month . '-01');
		$tdate = strtotime($this->get_created_at_with_year($month, $year));
		$contract = $this->contract_assign_debt_model->getAllContractField($condition);
		foreach ($contract as $i => $item) {
			$contractDetail = $this->contract_model->findOne(['_id' => new MongoDB\BSON\ObjectId($item->contract_id)]);
			$storeDetail = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($item->store_id)]);
			$logDetail = $this->contract_debt_recovery_model->find_where(['created_by' => $item->debt_field_email,'contract_id' => $item->contract_id, 'created_at' => ['$lte' => (int)$tdate,'$gte' => (int)$fdate]]);

			$contract[$i]['contractDetail'] = $contractDetail;
			$contract[$i]['storeDetail'] = $storeDetail;

			$tien_thu_trong_thang = $this->transaction_model->find_where(['status' => 1,'code_contract' => $item['code_contract'], 'date_pay' => ['$lte' => $tdate,'$gte' => $fdate]]);
			foreach ($tien_thu_trong_thang as $t) {
				$contract[$i]['tien_thu_trong_thang'] += $t['total'];
			}

			$tien_1_ky_phai_tra = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $item['code_contract']]);
			$contract[$i]['tien_ky'] = $tien_1_ky_phai_tra[0]['tien_tra_1_ky'];
			$so_ky_thanh_toan = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $item['code_contract'], 'status' => 2]);
			$tempo = $this->contract_tempo_model->find_where(['code_contract' => $item['code_contract'], 'status' => 1]);

				if (!empty($tempo)) {
					$contract[$i]['ngay_den_han_thanh_toan'] = $tempo[0]['ngay_ky_tra'];
				}
			$contract[$i]['so_ky_da_thanh_toan'] = count($so_ky_thanh_toan);

			if (!empty($logDetail)) {
				foreach ($logDetail as $field) {
//					if ($fdate < (int)$field->created_at && (int)$field->created_at < $tdate) {
					$contract[$i]['fieldDetail'][] = !empty($field->note) ? $field->note : note_renewal($field->status_debt);
					$contract[$i]['fieldDate'][] = !empty($field->created_at) ? date('d/m/y', $field->created_at) : '';
					$contract[$i]['userTHN'][] = $field->created_by;
//					}
					$contract[$i]['fieldResult'] = !empty($field['status_debt']) ? note_renewal($field['status_debt']) : '';

				}
			}
			$contract[$i]['ky_tra_hien_tai'] = $contractDetail['debt']['ky_tra_hien_tai'];
			$contract[$i]['relativeDetail'] = $contractDetail['relative_infor']['type_relative_1'] . ',' . $contractDetail['relative_infor']['type_relative_2'];
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $contract
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function getReportGeneralTHN_post()
	{
		$data = $this->input->post();
		$month = !empty($data['month']) ? $data['month'] : date('m');
		$year = !empty($data['year']) ? $data['year'] : date('Y');;
		$a = $this->call_thn_mb();
		$b = $this->call_thn_mn();
		$c = $this->field_thn_mn_b4();
		$d = $this->field_thn_mb_b4();
		$e = $this->field_thn_mb();
		$f = $this->field_thn_mn();
		$leadTHNMB = $this->listLeadThnMb();
		$leadTHNMN = $this->listLeadThnMn();

		$leadCallMb = $this->listLeadCallMB();
		$leadFieldMb = $this->listLeadFieldMB();
		$leadCallAndFieldMn = $this->listCallAndFieldMn();

		$mb = array_merge($a, $d, $e);
		$mn = array_merge($b, $c, $f);
		$mbCall = $a;
		$mnCall = array_unique($b);
		$mbField = array_merge($d, $e);
		$mnField = array_merge($f, $c);

		$condition = [];
		$currentUser = $this->uemail;
//		$currentUser = 'thuyetdv@tienngay.vn';

		$condition['month'] = $month;
		$condition['year'] = $year;
		$condition['fdate'] = strtotime($year . '-' . $month . '-01');
		$condition['tdate'] = strtotime($this->get_created_at_with_year($month, $year));

		$array = [];
		$array_code = [];

		if (in_array($currentUser, $leadTHNMB)) {
			$condition['domain_contract'] = "MB";
			foreach ($mbCall as $i => $item) {
				$logCall = [];
				$call = 0;
				$countCall = 0;
				$condition['user'] = $item;
//				$condition['user'] = 'nhanvt@tienngay.vn';
				$contractCall = $this->contract_debt_caller_model->getAllContractCallByUser($condition);

				foreach ($contractCall as $c) {
					$condition['id'] = $c['contract_id'];
					$contractCalled = $this->log_call_debt_model->findCalled($condition);
					if (!empty($contractCalled)) {
						$call++;
					}
					$logCall[] = $this->log_call_debt_model->findNoteReminder($condition);
				}


				$report['call'][$item]['lead'] = $leadCallMb[0];
				$report['call'][$item]['contact'] = array_sum($logCall);
				$report['call'][$item]['count'] = !empty($contractCall) ? (count($contractCall) - $call) : '0';
			}
			foreach ($mbField as $m => $item1) {
				$count = 0;
				$condition['user'] = $item1;
//				$condition['user'] = 'tungdv@tienngay.vn';
				$contractField = $this->contract_assign_debt_model->getAllContractFieldByUser($condition);
				foreach ($contractField as $f) {
					$condition['contract_id'] = $f['contract_id'];
					$logFielded = $this->contract_debt_recovery_model->findFielded($condition);
					if (!empty($logFielded)) {
						$count++;
					}
				}
				$logField = $this->contract_debt_recovery_model->findFieldNote($condition);

				$report['field'][$item1]['lead'] = $leadFieldMb[0];
				$report['field'][$item1]['contact'] = $logField;
				$report['field'][$item1]['count'] = !empty($contractField) ? (count($contractField) - $count) : '0';
			}
		}elseif (in_array($currentUser, $leadTHNMN)) {
			$condition['domain_contract'] = "MN";
			foreach ($mnCall as $i => $item) {
				$logCall = [];
				$call = 0;
				$condition['user'] = $item;
				$contractCall = $this->contract_debt_caller_model->getAllContractCallByUser($condition);
//				$contractCalled = $this->contract_debt_caller_model->getContractCalled($condition);
				foreach ($contractCall as $c) {
					$condition['id'] = $c['contract_id'];
					$contractCalled = $this->log_call_debt_model->findCalled($condition);
					$logCall[] = $this->log_call_debt_model->findNoteReminder($condition);
					if (!empty($contractCalled)) {
						$call++;
					}

				}
//				$logCall = $this->log_call_debt_model->findNoteReminder($condition);

				$report['call'][$item]['lead'] = $leadCallAndFieldMn[0];
				$report['call'][$item]['contact'] = array_sum($logCall);
				$report['call'][$item]['count'] =  !empty($contractCall) ? (count($contractCall) - $call) : '0';
			}
			foreach ($mnField as $item1) {
				$count = 0;
				$condition['user'] = $item1;
				$contractField = $this->contract_assign_debt_model->getAllContractFieldByUser($condition);
				foreach ($contractField as $f) {
					$condition['contract_id'] = $f['contract_id'];
					$logFielded = $this->contract_debt_recovery_model->findFielded($condition);
					if (!empty($logFielded)) {
						$count++;
					}
				}
				$logFielded = $this->contract_debt_recovery_model->findFielded($condition);
				$logField = $this->contract_debt_recovery_model->findFieldNote($condition);

				$report['field'][$item1]['lead'] = $leadCallAndFieldMn[0];
				$report['field'][$item1]['contact'] = $logField;
				$report['field'][$item1]['count'] = !empty($contractField) ? (count($contractField) - $count) : '0';
			}
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $report
		];

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	function check_leap_year($year)
{
    if (($year % 400 == 0) || ($year % 4 == 0 && $year % 100 != 0)) {
        return true;
    } else {
        return false;
    }
}

function get_created_at_with_year($month, $year)
{
    $leap_year = $this->check_leap_year((int)$year);
    $end = "";
    switch ($month) {
        case '01':
            $end = "$year-01-31" . ' 23:59:59';
            break;
        case '02':
            if ($leap_year == true) {
                $end = "$year-02-29" . ' 23:59:59';
            } else {
                $end = "$year-02-28" . ' 23:59:59';
            }
            break;
        case '03':
            $end = "$year-03-31" . ' 23:59:59';
            break;
        case '04':
            $end = "$year-04-30" . ' 23:59:59';
            break;
        case '05':
            $end = "$year-05-31" . ' 23:59:59';
            break;
        case '06':
            $end = "$year-06-30" . ' 23:59:59';
            break;
        case '07':
            $end = "$year-07-31" . ' 23:59:59';
            break;
        case '08':
            $end = "$year-08-31" . ' 23:59:59';
            break;
        case '09':
            $end = "$year-09-30" . ' 23:59:59';
            break;
        case '10':
            $end = "$year-10-31" . ' 23:59:59';
            break;
        case '11':
            $end = "$year-11-30" . ' 23:59:59';
            break;
        case '12':
            $end = "$year-12-31" . ' 23:59:59';
            break;
    }
    return $end;
}

	public function listTbpThn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "tbp-thu-hoi-no")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listLeadThn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}
	
	public function listThn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "phong-thu-hoi-no")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		$getCHT = array_diff($data,$this->listTbpThn(), $this->listLeadThn());
		return $getCHT;
	}

	public function listLeadThnMb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = ['thuyetdv@tienngay.vn'];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listLeadThnMn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = ['vulq@tienngay.vn'];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listTBPThnMB()
	{
		$roles = $this->group_role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "tp-thn-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listLeadCallMB()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-call-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listLeadFieldMB()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-field-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}

	public function listCallAndFieldMn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}
					}
				}
			}
		}
		return $data;
	}



}

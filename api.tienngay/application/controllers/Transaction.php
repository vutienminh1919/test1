<?php
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;
require_once APPPATH . 'libraries/BaoHiemPTI.php';
require_once APPPATH . 'libraries/Fcm.php';
require_once APPPATH . 'libraries/VPBank.php';

class Transaction extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		$this->load->model("order_model");
		$this->load->model("dashboard_model");
		$this->load->model("transaction_model");
		$this->load->model("service_vimo_model");
		$this->load->model("customer_billing_model");
		$this->load->model("user_model");
		$this->load->model("hotline_model");
		$this->load->model("role_model");
		$this->load->model("storage_card_model");
		$this->load->model("group_role_model");
		$this->load->model("transaction_contract_model");
		$this->load->model("contract_tempo_model");
		$this->load->model("log_model");
		$this->load->model("sms_model");
		$this->load->model("contract_model");
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('temporary_plan_contract_model');
		$this->load->helper('lead_helper');
		$this->load->model('store_model');
		$this->load->model('area_model');
		$this->load->model("hey_u_model");
		$this->load->model("mic_tnds_model");
		$this->load->model('log_transaction_model');
		$this->load->model('vbi_tnds_model');
		$this->load->model("transaction_extend_model");
		$this->load->model("vbi_utv_model");
		$this->load->model("vbi_sxh_model");
		$this->load->model("log_ksnb_model");
		$this->load->model("bank_transaction_model");
		$this->load->model('payment_model');
		$this->load->model('gic_plt_bn_model');
		$this->load->model('gic_easy_bn_model');
		$this->load->model('pti_vta_bn_model');
		$this->load->model('generate_model');
		$this->load->model('allocation_model');
		$this->load->model("email_history_model");
		$this->load->model("email_template_model");
		$this->load->model("transaction_pending_model");
		$this->load->model("notification_model");
		$this->load->model("device_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->dataPost = $this->input->post();
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
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

	private $createdAt, $dataPost, $isTriple, $libraries, $flag_login, $id, $uemail, $ulang, $app_login, $superadmin, $so_tien_goc_da_tra_tat_toan, $so_tien_lai_da_tra_tat_toan, $so_tien_phi_da_tra_tat_toan, $so_tien_phi_tat_toan_da_tra, $tien_thua_tat_toan, $so_tien_phi_phat_sinh_da_tra, $so_tien_phi_cham_tra_da_tra, $so_tien_phi_gia_han_da_tra;

   private  $so_tien_goc_phai_tra_tat_toan,
		$so_tien_lai_phai_tra_tat_toan,
		$so_tien_phi_phai_tra_tat_toan,
		$so_tien_phi_tat_toan_phai_tra_tat_toan,
		$so_tien_phi_cham_tra_phai_tra_tat_toan,
		$so_tien_phi_gia_han_phai_tra_tat_toan,
		$so_tien_phi_phat_sinh_phai_tra_tat_toan,
		$lai_con_no_thuc_te,
		$phi_con_no_thuc_te,
		$so_tien_goc_phai_tra_hop_dong,
		$so_tien_lai_phai_tra_hop_dong,
		$so_tien_phi_phai_tra_hop_dong,
		$phi_gia_han_phai_tra_hop_dong,
		$phi_cham_tra_phai_tra_hop_dong,
		$phi_tat_toan_phai_tra_hop_dong,
		$phi_phat_sinh_phai_tra_hop_dong;
	public function create_transaction_order_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		unset($this->dataPost['secret_key']);

		$default_api_vimo = false;
		$this->dataPost['total'] = $this->security->xss_clean($this->dataPost['total']); // tong tien
		$this->dataPost['payment_method'] = $this->security->xss_clean($this->dataPost['payment_method']); // phuong thuc thanh toan
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']); // cua hang
		$this->dataPost['customer_bill_name'] = $this->security->xss_clean($this->dataPost['customer_bill_name']); // ten khach hang giao dich
		$this->dataPost['customer_bill_phone'] = $this->security->xss_clean($this->dataPost['customer_bill_phone']); // so dien thoai khach giao dich
		$this->dataPost['order'] = $this->security->xss_clean($this->dataPost['order']); // order khach giao dich

		//Check null
		if (empty($this->dataPost['total'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tổng tiền không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['payment_method'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Phương thức thanh toán không thể trống"
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
		if (empty($this->dataPost['customer_bill_name'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tên khách hàng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['customer_bill_phone'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "SĐT khách hàng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$customer = $this->customer_billing_model->find_where(array('phone_number' => trim($this->dataPost['customer_bill_phone'])));
			if (!$customer) {
				$customer_bill = array(
					'phone_number' => trim($this->dataPost['customer_bill_phone']),
					'cus_bill_name' => trim($this->dataPost['customer_bill_name']),
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
				);
				$this->customer_billing_model->insert($customer_bill);
			}
		}
		if (empty($this->dataPost['order'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Order của khách hàng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$services = array();
		$service = $this->service_vimo_model->find();
		if (!empty($service)) {
			foreach ($service as $ser) {
				$services[$ser['service_code']] = $ser;
			}
		}
		$checkOrder = $this->checkOrderNull($this->dataPost['order']);
		if ($checkOrder['status'] !== 200) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $checkOrder['message'],
				'data' => isset($checkOrder['data']) ? $checkOrder['data'] : 'none'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Insert data
		$code = 'PT_' . date("Ymd") . '_' . uniqid();
		$data_transaction = array(
			"total" => $this->dataPost['total'],
			"code" => $code,
			"type" => 1, //1: thanh toan hoa don, 2: phi phat hop dong
			"payment_method" => $this->dataPost['payment_method'],
			"store" => $this->dataPost['store'],
			"status" => 'new',
			"customer_bill_phone" => $this->dataPost['customer_bill_phone'],
			"customer_bill_name" => $this->dataPost['customer_bill_name'],
			"created_by" => $this->uemail,
			"created_at" => $this->createdAt,
		);

		if ($data_transaction['payment_method'] == 1) {
			$code_billing = $this->initAutoCodeBilling($data_transaction['store'],$data_transaction['type']);
			$data_transaction['code_billing'] = $code_billing['code_billing'];
		}
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		//Write log
		$log = array(
			"type" => "transaction",
			"action" => 'create',
			"data_post" => $this->dataPost,
			"transaction_id" => (string)$transaction_id,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
		foreach ($this->dataPost['order'] as $order) {
			$service_name = '';
			$publisher_name = '';
			if (!empty($services[$order['service_code']])) {
				$service_name = $services[$order['service_code']]['name'];
				foreach ($services[$order['service_code']]['publisher'] as $p) {
					if ($p['code'] === $order['publisher']) {
						$publisher_name = $p['name'];
						break;
					}
				}
			}
			$dashboard = $this->dashboard_model->find();
			$dashboard_id = !empty($dashboard[0]['_id']) ? (string)$dashboard[0]['_id'] : "";
			if ($order['service_code'] === 'BILL_ELECTRIC') {// thanh toán điện
				$amount_electric = 0;
				foreach ($order['data'] as $e) {
					$e['bill_payment']['amount'] = (int)$e['bill_payment']['amount'];
					$e['bill_payment']['otherInfo'] = isset($e['bill_payment']['otherInfo']) ? $e['bill_payment']['otherInfo'] : array();
					$param = array(
						"mc_request_id" => $e['mc_request_id'],
						"transaction_id" => (string)$transaction_id,
						"transaction_code" => $code,
						"service_code" => $order['service_code'],
						"service_name" => $service_name,
						"publisher_name" => $publisher_name,
						"detail" => array(
							"publisher" => $order['publisher'],
							"customer_code" => isset($order['customer_code']) ? trim($order['customer_code']) : '',
							"bill_payment" => isset($e['bill_payment']) ? $e['bill_payment'] : array(),
							"customer_infor" => isset($order['customer_infor']) ? $order['customer_infor'] : array()
						),
						"status" => 'new',
						"api_vimo" => $default_api_vimo,
						"money" => (int)$e['money'], // so tien order
						"store" => $this->dataPost['store'],
						"customer_bill_phone" => $this->dataPost['customer_bill_phone'],
						"customer_bill_name" => $this->dataPost['customer_bill_name'],
						"created_by" => $this->uemail,
						"created_at" => $this->createdAt,
						"updated_at" => $this->createdAt,
					);
					$this->order_model->insert($param);
					//Write log
					$log_order = array(
						"type" => "order",
						"action" => "create",
						"data_post" => $param,
						"email" => $this->uemail,
						"created_at" => $this->createdAt
					);
					$this->log_model->insert($log_order);
					$amount_electric += $e['money'];
				}
				// update dashboard
				$number_electric = !empty($dashboard[0]['multi_services']['electric']) ? ((int)$dashboard[0]['multi_services']['electric'] + 1) : "";
				$amount_bill = !empty($dashboard[0]['receipts_multi_services']['bill']) ? ($dashboard[0]['receipts_multi_services']['bill'] + $amount_electric) : "";
				if (!empty($dashboard[0]['_id'])) {
					$this->dashboard_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($dashboard_id)),
						array(
							"multi_services.electric" => $number_electric,
							"receipts_multi_services.bill" => $amount_bill
						)
					);
				}
			} else if (strpos($order['service_code'], 'PINCODE_') !== false) {// nạp thẻ 

				$card_infor = array(
					'status' => 'new',
					'publisher' => $order['publisher'],
					'amount' => (int)$order['amount'],
					'service_code' => $order['service_code'],
				);
				$data_card = $this->storage_card_model->getCard((int)$order['quantity'], $card_infor, 1);
				$storageCardIds = array();
				if (!empty($data_card)) {
					foreach ($data_card as $card) {
						$item_info = $card['response']['data']['cards'][0];
						$item_info['storage_card_id'] = (string)$card['_id'];
						$items[] = $item_info;
						$storageCardIds[] = $card['_id'];
					}
				}

				$param = array(
					"mc_request_id" => $order['mc_request_id'],
					"transaction_id" => (string)$transaction_id,
					"transaction_code" => $code,
					"service_code" => $order['service_code'],
					"service_name" => $service_name,
					"publisher_name" => $publisher_name,
					"detail" => array(
						"publisher" => $order['publisher'],
						'items' => $items
					),
					"status" => 'success',
					"api_vimo" => true,
					"quantity" => isset($order['quantity']) ? (int)$order['quantity'] : '', // so luong
					"amount" => isset($order['amount']) ? (int)$order['amount'] : 0, // gia thanh
					"money" => (int)$order['money'], // so tien order
					"store" => $this->dataPost['store'],
					"customer_bill_phone" => $this->dataPost['customer_bill_phone'],
					"customer_bill_name" => $this->dataPost['customer_bill_name'],
					"created_by" => $this->uemail,
					"created_at" => $this->createdAt,
					"updated_at" => $this->createdAt,
				);
				$this->order_model->insert($param);
				$data_update_card = array(
					'status' => 'active',
					'time_get_card' => $this->createdAt,
				);
				$this->storage_card_model->findManyIdAndUpdate('_id', $storageCardIds, $data_update_card);
				//Write log
				$log_order = array(
					"type" => "order",
					"action" => "create",
					"data_post" => $param,
					"email" => $this->uemail,
					"created_at" => $this->createdAt
				);
				$this->log_model->insert($log_order);
				$log_card = array(
					"type" => "storage_card",
					"action" => "update",
					"data_post" => $data_update_card,
					"ids" => $storageCardIds,
					"email" => $this->uemail,
					"created_at" => $this->createdAt
				);
				$this->log_model->insert($log_card);
				// update dashboard
				$number_code_mobile = !empty($dashboard[0]['multi_services']['code_mobile']) ? ((int)$dashboard[0]['multi_services']['code_mobile'] + 1) : "";
				$amount_code_mobile = !empty($dashboard[0]['receipts_multi_services']['code_mobile']) ? ($dashboard[0]['receipts_multi_services']['code_mobile'] + (int)$order['amount']) : "";
				if (!empty($dashboard[0]['_id'])) {
					$this->dashboard_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($dashboard_id)),
						array(
							"multi_services.code_mobile" => $number_code_mobile,
							"receipts_multi_services.code_mobile" => $amount_code_mobile
						)
					);
				}
			} else if (strpos($order['service_code'], 'TOPUP_') !== false) { // mua thẻ
				$param = array(
					"mc_request_id" => $order['mc_request_id'],
					"transaction_id" => (string)$transaction_id,
					"transaction_code" => $code,
					"service_code" => $order['service_code'],
					"service_name" => $service_name,
					"publisher_name" => $publisher_name,
					"detail" => array(
						"publisher" => $order['publisher'],
						"receiver" => $order['receiver'],
						"amount" => isset($order['amount']) ? $order['amount'] : '',
					),
					"status" => 'new',
					"api_vimo" => $default_api_vimo,
					"amount" => isset($order['amount']) ? (int)$order['amount'] : 0, // gia thanh
					"money" => (int)$order['money'], // so tien order
					"store" => $this->dataPost['store'],
					"customer_bill_phone" => $this->dataPost['customer_bill_phone'],
					"customer_bill_name" => $this->dataPost['customer_bill_name'],
					"created_by" => $this->uemail,
					"created_at" => $this->createdAt,
					"updated_at" => $this->createdAt,
				);
				$this->order_model->insert($param);
				//Write log
				$log_order = array(
					"type" => "order",
					"action" => "create",
					"data_post" => $param,
					"email" => $this->uemail,
					"created_at" => $this->createdAt
				);
				$this->log_model->insert($log_order);
				// update dashboard
				$number_code_mobile = !empty($dashboard[0]['multi_services']['code_mobile']) ? ((int)$dashboard[0]['multi_services']['code_mobile'] + 1) : "";
				$amount_code_mobile = !empty($dashboard[0]['receipts_multi_services']['code_mobile']) ? ($dashboard[0]['receipts_multi_services']['code_mobile'] + (int)$order['amount']) : "";
				if (!empty($dashboard[0]['_id'])) {
					$this->dashboard_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($dashboard_id)),
						array(
							"multi_services.code_mobile" => $number_code_mobile,
							"receipts_multi_services.code_mobile" => $amount_code_mobile
						)
					);
				}
			} else {
				// hoa don nước BILL_WATER
				// hoa don tai chinh BILL_FINANCE
				if (!empty($order['bill_payment']['amount'])) {
					$order['bill_payment']['amount'] = (int)$order['bill_payment']['amount'];
				}
				$order['bill_payment']['billType'] = isset($order['bill_payment']['billType']) ? $order['bill_payment']['billType'] : array();
				$order['bill_payment']['otherInfo'] = isset($order['bill_payment']['otherInfo']) ? $order['bill_payment']['otherInfo'] : array();
				$param = array(
					"mc_request_id" => $order['mc_request_id'],
					"transaction_id" => (string)$transaction_id,
					"transaction_code" => $code,
					"service_code" => $order['service_code'],
					"service_name" => $service_name,
					"publisher_name" => $publisher_name,
					"detail" => array(
						"publisher" => $order['publisher'],
						"customer_code" => isset($order['customer_code']) ? trim($order['customer_code']) : '',
						"bill_payment" => isset($order['bill_payment']) ? $order['bill_payment'] : array(),
						"customer_infor" => isset($order['customer_infor']) ? $order['customer_infor'] : array()
					),
					"status" => 'new',
					"api_vimo" => $default_api_vimo,
					"quantity" => isset($order['quantity']) ? (int)$order['quantity'] : '', // so luong
					"amount" => isset($order['amount']) ? (int)$order['amount'] : 0, // gia thanh
					"money" => (int)$order['money'], // so tien order
					"store" => $this->dataPost['store'],
					"customer_bill_phone" => $this->dataPost['customer_bill_phone'],
					"customer_bill_name" => $this->dataPost['customer_bill_name'],
					"created_by" => $this->uemail,
					"created_at" => $this->createdAt,
					"updated_at" => $this->createdAt,
				);
				$this->order_model->insert($param);
				//Write log
				$log_order = array(
					"type" => "order",
					"acion" => "create",
					"data_post" => $param,
					"email" => $this->uemail,
					"created_at" => $this->createdAt
				);
				$this->log_model->insert($log_order);
				// update dashboard
				if ($order['service_code'] == 'BILL_WATER') {
					$number_water = !empty($dashboard[0]['multi_services']['water']) ? ((int)$dashboard[0]['multi_services']['water'] + 1) : "";
					$amount_bill = !empty($dashboard[0]['receipts_multi_services']['bill']) ? ($dashboard[0]['receipts_multi_services']['bill'] + (int)$order['money']) : "";
					if (!empty($dashboard[0]['_id'])) {
						$this->dashboard_model->update(
							array("_id" => new MongoDB\BSON\ObjectId($dashboard_id)),
							array(
								"multi_services.water" => $number_water,
								"receipts_multi_services.bill" => $amount_bill
							)
						);
					}

				}
				if ($order['service_code'] == 'BILL_FINANCE') {
					$multi_services = !empty($dashboard[0]['multi_services']['finance']) ? ($dashboard[0]['multi_services']['finance']) : 0;
					$number_finance = $multi_services + 1;
					$amount_finance = !empty($dashboard[0]['receipts_multi_services']['bill']) ? ($dashboard[0]['receipts_multi_services']['bill'] + (int)$order['money']) : "";
					if (!empty($dashboard[0]['_id'])) {
						$this->dashboard_model->update(
							array("_id" => new MongoDB\BSON\ObjectId($dashboard_id)),
							array(
								"multi_services.finance" => $number_finance,
								"receipts_multi_services.bill" => $amount_finance
							)
						);
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thanh toán thành công',
			'url' => 'transaction/detail/' . (string)$transaction_id
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function checkSecretKey($secretKey, $tripleDes)
	{
		$isCorrect = TRUE;
		$libTripleDes = new TripleDes();
		$this->isTriple = $libTripleDes->Decrypt($secretKey, $tripleDes);
		if ($this->isTriple == FALSE) $isCorrect = FALSE;
		return $isCorrect;
	}

	private function checkOrderNull($params)
	{
		//Check null
		if (!is_array($params) || empty($params)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Yêu cầu array data hóa đơn"
			);
			return $response;
		}
		foreach ($params as $param) {
			if (empty($param['service_code'])) { // Mã dịch vụ
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã yêu cầu dịch vụ không thể trống"
				);
				return $response;
			}
			if (empty($param['publisher'])) { // Mã hóa đơn
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Nhà cung cấp dịch vụ không thể trống"
				);
				return $response;
			}

			if ($param['service_code'] === 'BILL_FINANCE' || $param['service_code'] === 'BILL_WATER') {
				if (empty($param['mc_request_id'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã yêu cầu của merchant không thể trống"
					);
					return $response;
				}
				if (empty($param['bill_payment'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "bill_payment không thể trống"
					);
					return $response;
				} else {
					if (empty($param['bill_payment']['billNumber'])) { // Mã hóa đơn
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Mã hóa đơn dịch vụ của merchant không thể trống"
						);
						return $response;
					}
					// if(empty($param['bill_payment']['period'])) { // Kỳ thanh toán
					// 	$response = array(
					// 		'status' => REST_Controller::HTTP_UNAUTHORIZED,
					// 		'message' => "Kỳ thanh toán dịch vụ của merchant không thể trống"
					// 	);
					// 	return $response;
					// }
					if (empty($param['bill_payment']['amount'])) { // Số tiền hóa đơn
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Số tiền hóa đơn dịch vụ của merchant không thể trống"
						);
						return $response;
					}
				}
				if (empty($param['customer_code'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã khách hàng không thể trống"
					);
					return $response;
				}
			} elseif ($param['service_code'] === 'BILL_ELECTRIC') {
				if (empty($param['customer_code'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã khách hàng không thể trống"
					);
					return $response;
				}
				if (empty($param['data']) || !is_array($param['data'])) { // dữ liệu các kỳ
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Dữ liệu order không thể trống"
					);
					return $response;
				}
				foreach ($param['data'] as $param_electric) {
					if (empty($param_electric['mc_request_id'])) { // Mã hóa đơn
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Mã yêu cầu của merchant không thể trống"
						);
						return $response;
					}
					if (empty($param_electric['bill_payment'])) { // Mã hóa đơn
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "bill_payment không thể trống"
						);
						return $response;
					} else {
						if (empty($param_electric['bill_payment']['billNumber'])) { // Mã hóa đơn
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => "Mã hóa đơn dịch vụ của merchant không thể trống"
							);
							return $response;
						}
						if (empty($param_electric['bill_payment']['period'])) { // Kỳ thanh toán
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => "Kỳ thanh toán dịch vụ của merchant không thể trống"
							);
							return $response;
						}
						if (empty($param_electric['bill_payment']['amount'])) { // Số tiền hóa đơn
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => "Số tiền hóa đơn dịch vụ của merchant không thể trống"
							);
							return $response;
						}
						if (empty($param_electric['bill_payment']['billType'])) { // Loại hóa đơn
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => "Loại hóa đơn dịch vụ của merchant không thể trống"
							);
							return $response;
						}
					}
				}
			} elseif (strpos($param['service_code'], 'PINCODE_') !== false) {
				if (empty($param['mc_request_id'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã yêu cầu của merchant không thể trống"
					);
					return $response;
				}
				if (empty($param['quantity'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Số lượng thẻ không thể trống"
					);
					return $response;
				}
				if (empty($param['amount'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mệnh giá thẻ không thể trống"
					);
					return $response;
				}
			} elseif (strpos($param['service_code'], 'TOPUP_') !== false) {
				if (empty($param['mc_request_id'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã yêu cầu của merchant không thể trống"
					);
					return $response;
				}
				if (empty($param['receiver'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "SĐT/Tài khoản người nhận không thể trống"
					);
					return $response;
				}
				if (empty($param['amount'])) { // Mã hóa đơn
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mệnh giá nạp không thể trống"
					);
					return $response;
				}
			} else {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Dịch vụ đã chọn không đúng"
				);
				return $response;
			}

		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success"
		);
		return $response;
	}

	public function get_all_v2_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$sdt = !empty($this->dataPost['sdt']) ? $this->dataPost['sdt'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($sdt)) {
			$condition['sdt'] = $sdt;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
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
			$condition['type'] = array('$ne' => 2);
		}
		$condition['type'] = array('$ne' => 6.0);
		$transaction = $this->transaction_model->getTransaction_v2($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				if (!empty($tran['type']) && in_array($tran['type'], [3, 4])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else if ($tran['status'] == 'new') {
						$tran['progress'] = 'Mới';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
			'groupRoles' => $groupRoles,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$sdt = !empty($this->dataPost['sdt']) ? trim($this->dataPost['sdt']) : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$payment_method = !empty($this->dataPost['payment_method']) ? $this->dataPost['payment_method'] : "";
		$code = !empty($this->dataPost['code']) ? trim($this->dataPost['code']) : "";
		$type_transaction = !empty($this->dataPost['type_transaction']) ? $this->dataPost['type_transaction'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? trim($this->dataPost['code_contract_disbursement']) : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$tab = !empty($this->dataPost['tab']) ? $this->dataPost['tab'] : "";

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($tab)) {
			$condition['tab'] = $tab;
		}
		if (!empty($sdt)) {
			$condition['sdt'] = $sdt;
		}
		if (!empty($payment_method)) {
			$condition['payment_method'] = $payment_method;
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($type_transaction)) {
			$condition['type_transaction'] = $type_transaction;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($store)) {
			$condition['stores'] = is_array($store) ? $store : array($store);
		}
		
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
			//$condition['type'] = array('$ne' => 2);
		}
		if (empty($store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles)|| in_array('cua-hang-truong', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['stores'] = (is_array($store)) ? $store : [$store];
		}
		$condition['type'] = 1;
		$transaction = $this->transaction_model->getTransaction($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->transaction_model->getTransaction($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				if (!empty($tran['type']) && in_array($tran['type'], [3, 4, 5])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else if ($tran['status'] == 'new') {
						$tran['progress'] = 'Mới';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
			'groupRoles' => $groupRoles,
			'total' => $total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_all_ksnb_post(){

//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$code_contract_disbursement_search = !empty($this->dataPost['code_contract_disbursement_search']) ? $this->dataPost['code_contract_disbursement_search'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
//			$condition = array(
//				'start' => strtotime(trim($fdate) . ' 00:00:00'),
//				'end' => strtotime(trim($tdate) . ' 23:59:59')
//			);
			$condition['start'] = strtotime(trim($fdate) . ' 00:00:00');
			$condition['end'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement_search)) {
			$condition['code_contract_disbursement_search'] = $code_contract_disbursement_search;
		}
		if (!empty($store)) {
			$condition['stores'] = (array)$store;
		}


		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('kiem-soat-noi-bo', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
			//$condition['type'] = array('$ne' => 2);
		}
		$condition['type'] = 1;
		$transaction = $this->transaction_model->getTransaction_ksnb($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->transaction_model->getTransaction_ksnb($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				if (!empty($tran['type']) && in_array($tran['type'], [1, 3, 4, 5])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else if ($tran['status'] == 'new') {
						$tran['progress'] = 'Mới';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
			'groupRoles' => $groupRoles,
			'total' => $total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_kt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$full_name = !empty($this->dataPost['full_name']) ? $this->dataPost['full_name'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$type_transaction = !empty($this->dataPost['type_transaction']) ? $this->dataPost['type_transaction'] : "";
		$allocation = !empty($this->dataPost['allocation']) ? $this->dataPost['allocation'] : "";
		$tab = !empty($this->dataPost['tab']) ? $this->dataPost['tab'] : "";
		$code_transaction_bank = !empty($this->dataPost['code_transaction_bank']) ? $this->dataPost['code_transaction_bank'] : "";
		$code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
		$total_tran = 0;
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($type_transaction)) {
			$condition['type_transaction'] = (int)$type_transaction;
		}
		if (!empty($allocation)) {
			$condition['allocation'] = $allocation;
		}
		if (!empty($tab)) {
			$condition['tab'] = $tab;
		}
		if (!empty($full_name)) {
			$condition['full_name'] = $full_name;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($code_transaction_bank)) {
			$condition['code_transaction_bank'] = $code_transaction_bank;
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (in_array('quan-ly-khu-vuc', $groupRoles)) {
			$stores = $this->getStores_list($this->id);
			$condition['stores'] = $stores;
		}

		$transaction = $this->transaction_model->getTransaction_kt($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->transaction_model->getTransaction_kt($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				$cond = array();
				$tran['check'] = "Chưa phân bổ";
               $tong_chia=0;
				if ($tran['status'] == 1 || $tran['status'] == 5) {
					if($tran['type']==3){
					$tong_chia=round($tran['so_tien_lai_da_tra'] + $tran['so_tien_phi_da_tra'] + $tran['so_tien_goc_da_tra'] + $tran['so_tien_phi_cham_tra_da_tra'] + $tran['tien_phi_phat_sinh_da_tra'] + $tran['fee_finish_contract'] + $tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);
				    }else if($tran['type']==4){
                      $tong_chia=round($tran['so_tien_lai_da_tra'] + $tran['so_tien_phi_da_tra'] + $tran['so_tien_goc_da_tra'] + $tran['so_tien_phi_cham_tra_da_tra'] + $tran['tien_phi_phat_sinh_da_tra'] + $tran['fee_finish_contract'] + $tran['tien_thua_thanh_toan'] + $tran['so_tien_phi_gia_han_da_tra']);
				    }
					if ($tong_chia == ($tran['total'])) {
						$tran['check'] = "Phân bổ đúng";
					} else {
						$tran['check'] = "Phân bổ sai";
					}
				} else {
					$tran['check'] = "Chưa phân bổ";
				}

				$tran['full_name'] = '';
				if (isset($tran['code_contract'])) {
					$contract = $this->contract_model->find_where(array('code_contract' => $tran['code_contract']));
					$tran['id_contract'] = (isset($contract[0]['_id'])) ? (string)$contract[0]['_id'] : '';
					$cond = array(
						'code_contract' => $tran['code_contract'],
						'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
					);
					$tran['full_name'] = (isset($contract[0]['customer_infor']['customer_name'])) ? (string)$contract[0]['customer_infor']['customer_name'] : '';
					$tran['code_contract_disbursement'] = (isset($contract[0]['code_contract_disbursement'])) ? (string)$contract[0]['code_contract_disbursement'] : '';
				}
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				$tran['detail'] = array();
				
				if (!empty($detail)) {
					$total_paid = 0;
					$total_phi_phat_cham_tra = 0;
					$total_da_thanh_toan = 0;
					foreach ($detail as $de) {

						$total_paid = $total_paid + $de['tien_tra_1_ky'];
						
						$total_da_thanh_toan += $de['da_thanh_toan'];
					}
					$tran['detail'] = $detail[0];
					$tran['detail']['total_paid'] = $total_paid;
					$tran['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
					$tran['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
				} else {
					$condition_new = array(
						'code_contract' => $tran['code_contract'],
						'status' => 1
					);
					$detail_new = $this->contract_tempo_model->getContract($condition_new);
					if (!empty($detail_new)) {
						$tran['detail'] = $detail_new[0];
						$tran['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
						$tran['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
					}
				}
				if (!empty($tran['type']) && in_array($tran['type'], [3, 4])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else if ($tran['status'] == 'new') {
						$tran['progress'] = 'Mới';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
			'groupRoles' => $groupRoles,
			'total' => $total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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
								array_push($roleStores, key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}

	public function get_tran_investors_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles)) {
			$all = true;
		} else if (!in_array('cua-hang-truong', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
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
			$condition['type'] = 6.0;
		}
		$condition['type'] = 6.0;
		$transaction = $this->transaction_model->getTransaction($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				if (!empty($tran['type']) && in_array($tran['type'], [3, 4])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else if ($tran['status'] == 'new') {
						$tran['progress'] = 'Mới';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function history_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$condition = array(
			'code_contract' => $dataDB['code_contract'],
			'type' => array('$lt' => 6)
		);
		$transaction = $this->transaction_model->find_where($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				if ($tran['status'] == 1) {
					$tran['progress'] = 'Thành công';
				} else if ($tran['status'] == 2) {
					$tran['progress'] = 'Đang chờ';
				} else {
					$tran['progress'] = 'Thất bại';
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_order_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$orders = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$orders = $this->order_model->find_where(array('transaction_code' => $transaction['code']));
		}
		if (!empty($orders)) {
			foreach ($orders as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'orderData' => $orders,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function payment_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
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
		$this->dataPost['date_pay'] = isset($this->dataPost['date_pay']) ? strtotime($this->dataPost['date_pay'] .date(' H:i:s')) : $this->createdAt;
		$this->dataPost['amount'] = (float)$this->dataPost['amount'];
		$this->dataPost['amount_total'] = (float)$this->dataPost['amount'] + $this->dataPost['fee_reduction'];
		$this->dataPost['ky_tra_hien_tai'] = $this->security->xss_clean($this->dataPost['ky_tra_hien_tai']);
		$this->dataPost['ngay_den_han'] = $this->security->xss_clean($this->dataPost['ngay_den_han']);
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
		if (empty($this->dataPost['amount']) &&  $this->dataPost['type_payment'] == 1) {
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

		$tat_toan=$this->transaction_model->count(array('code_contract'=>$this->dataPost['code_contract'],'status'=>array('$ne' => 3),'type'=>3));
		if($tat_toan>0 && $this->dataPost['type_pt']==3)
		{
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
		$code = $this->transaction_model->getNextTranCode($contractDB['code_contract']);
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
			"ky_tra_hien_tai" => (int)$this->dataPost['ky_tra_hien_tai'],
			"ngay_den_han" => (int)$this->dataPost['ngay_den_han'],
			"store" => $this->dataPost['store'],
			"date_pay" => (int)$this->dataPost['date_pay'],
			"status" => 4,
			"customer_bill_phone" => $this->dataPost['phone'],
			"customer_bill_name" => $this->dataPost['name'],
			"relative_with_contract_owner" => $this->dataPost['name_relative'],
			"note" => $this->dataPost['note'],
			"type_payment" => (int)$this->dataPost['type_payment'],
			"bank_remark" => str_replace("_","",$code),
			"created_by" => $this->uemail,
			"created_at" => $this->createdAt,
		);
		if($data_transaction['type_payment']==3)
		{
          $data_transaction["amount_debt_cc"] = (int)$this->dataPost['amount_debt_cc'];
		  $data_transaction["amount_cc"] = (int)$this->dataPost['amount_cc'];
		}

		if ($data_transaction['payment_method'] == 1) {
			$code_billing = $this->initAutoCodeBilling($data_transaction['store'],$data_transaction['type']);
			$data_transaction['code_billing'] = $code_billing['code_billing'];
		}

		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		//Write log
		$log = array(
			"type" => "contract",
			"action" => "payment",
			"data_post" => $this->dataPost,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
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
			if (!empty($dataUserApprove)) {
				$client->emit('notify_approve', $dataApprove);
			}
			$client->close();
		} catch (Exception $e) {

		}

	}

	private function getUserGroupRole_email($GroupIds)
	{
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = $item[key($item)]['email'];
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}

	private function getUserbyStores($storeId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['stores']) && count($role['stores']) > 0) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if (in_array($storeId, $arrStores) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['users'] as $key => $item) {
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}
    //đánh status kỳ tương lai
	private function finishTempoPlan($code, $amount,$date_pay,$total_deductible)
	{
		$transaction = $this->contract_tempo_model->find_where(array('code_contract' => $code, 'status' => 1));
		$contract = $this->contract_model->findOne(array('code_contract' => $code));
		$type_interest = !empty($contract['loan_infor']['type_interest']) ? $contract['loan_infor']['type_interest'] : "1";
		$remaining_amount = $amount;
		$check = false;
	
		foreach ($transaction as $key =>$tran) {
			if($key==count($transaction)-1 && $type_interest ==2)
			{
				break;
			}
			$insert = array();
			if ((int)($tran['tien_tra_1_ky'] - $tran['da_thanh_toan']) > $remaining_amount) {
				$insert['da_thanh_toan'] = $remaining_amount + $tran['da_thanh_toan'];
				if ($tran['type'] == 1) {
					$insert['tien_goc_con'] = $tran['tien_goc_con'] - $remaining_amount;
				}
				if ((int)($tran['tien_tra_1_ky'] - $this->get_amout_limit_debt($date_pay,$tran['round_tien_tra_1_ky'])-$total_deductible - $tran['da_thanh_toan']) <= $remaining_amount) {
					$insert['da_thanh_toan'] = $remaining_amount;
					$insert['status'] = 2; // da dong tien
					$insert['current_plan'] = 2; // da dong tien
					$check = true;
				}

			} else {
				$insert['da_thanh_toan'] = $tran['tien_tra_1_ky'];
				$insert['status'] = 2; // da dong tien
				$insert['current_plan'] = 2; // da dong tien
				$check = true;
			}
			$this->contract_tempo_model->findOneAndUpdate(array("_id" => $tran['_id']), $insert);
			$remaining_amount = $remaining_amount - $tran['tien_tra_1_ky'] + $tran['da_thanh_toan'];
			if ($remaining_amount <= 0) {
				break;
			}
		}
		if ($check) {
			$next_plan = $this->contract_tempo_model->findOne(array("status" => 1, 'current_plan' => 2));
			if (!empty($next_plan)) {
				$this->contract_tempo_model->findOneAndUpdate(array("_id" => $next_plan['_id']), array('current_plan' => 1));
			}
		}
		// update total debt pay
		
		$total_debt_pay = !empty($contract['total_debt_pay']) ? (int)$contract['total_debt_pay'] : 0;
		$total_debt_pay = $total_debt_pay + $amount;
		$this->contract_model->findOneAndUpdate(array("_id" => $contract['_id']), array('total_debt_pay' => $total_debt_pay));
	}
    //lấy tiền kỳ limit
	private function get_amout_limit_debt($date_pay,$amount_ky)
	{
		$amount_limit_debt=0;
         if($date_pay<strtotime('2021-04-18 00:00:00') )
         {
         $amount_limit_debt = $amount_ky * (2 / 100);
			if ($amount_limit_debt > 200000) {
				$amount_limit_debt = 200000;
			}
         }else{
                $amount_limit_debt=12000;
         }
		return $amount_limit_debt;
	}

	public function get_amout_limit_debt_post()
	{
		$data = $this->input->post();

		$amount_limit_debt = 0;
		$amount_limit_debt = $data['amount'] * (2 / 100);
		if ($amount_limit_debt > 200000) {
			$amount_limit_debt = 200000;
		}
		var_dump($amount_limit_debt);

	}

	public function get_image_banking_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$dataDB = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại giao dịch"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($dataDB) && in_array($dataDB['type'],[3,4,5])) {
			$contract = $this->contract_model->findOne(array('code_contract' => $dataDB['code_contract']));
		   $logs=$this->log_model->getLogs(array("type" =>"transaction","action" =>"update","data_post.id" => $data['id']));
		    $logs_tran=$this->log_model->getLogs(array("type" =>"transaction","action" =>"update","data_post.transaction_id" => $data['id']));
		    $logs_app=$this->log_model->getLogs(array("type" =>"transaction","action" =>"approve_transaction","data_post.transaction_id" => $data['id']));
		    $dataDB['logs']=array_merge($logs,$logs_app,$logs_tran);
			$dataDB['contract_id']=(string)$contract['_id'];
			$dataDB['contract_status']=$contract['status'];
			$dataDB['full_name'] = '';
			if (isset($dataDB['code_contract'])) {
				$contract = $this->contract_model->find_where(array('code_contract' => $dataDB['code_contract']));
				$dataDB['id_contract'] = (isset($contract[0]['_id'])) ? (string)$contract[0]['_id'] : '';
				$cond = array(
					'code_contract' => $dataDB['code_contract'],
					'end' => time() - 5 * 24 * 3600, // 5 ngay tieu chuan
				);
				$dataDB['full_name'] = (isset($contract[0]['customer_infor']['customer_name'])) ? (string)$contract[0]['customer_infor']['customer_name'] : '';
				$dataDB['code_contract_disbursement'] = (isset($contract[0]['code_contract_disbursement'])) ? (string)$contract[0]['code_contract_disbursement'] : '';
			}
			$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
			$dataDB['detail'] = array();
			//var_dump($detail[0]); die;
			if (!empty($detail)) {
				$total_paid = 0;
				$total_phi_phat_cham_tra = 0;
				$total_da_thanh_toan = 0;
				foreach ($detail as $de) {
					$total_paid = $total_paid + $de['tien_tra_1_ky'];
					$total_phi_phat_cham_tra += $de['penalty'];
					$total_da_thanh_toan += $de['da_thanh_toan'];
				}
				$dataDB['detail'] = $detail[0];
				$dataDB['detail']['total_paid'] = $total_paid;
				$dataDB['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
				$dataDB['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
			} else {
				$condition_new = array(
					'code_contract' => $dataDB['code_contract'],
					'status' => 1
				);
				$detail_new = $this->contract_tempo_model->getContract($condition_new);
				if (!empty($detail_new)) {
					$dataDB['detail'] = $detail_new[0];

					$dataDB['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'];
					$dataDB['detail']['total_phi_phat_cham_tra'] = $detail_new[0]['penalty'];
					$dataDB['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'];
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function upload_image_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['image_expertise'] = $this->security->xss_clean($data['image_expertise']);
		$dataDB = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update
		$this->transaction_model->update(
			array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
			array("image_banking.image_expertise" => $data['image_expertise'])
		);
		//Insert log
		$insertLog = array(
			"type" => "transaction",
			"action" => "upload_image",
			"transaction_id" => $data['id'],
			"path" => $data['image_expertise'],
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_model->insert($insertLog);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "lưu thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function process_update_description_img_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		// $groupRoles = $this->getGroupRole($this->id);
		// if ($this->superadmin == false && !in_array('ke-toan', $groupRoles) && !in_array('van-hanh', $groupRoles)) {
		// 	// Check access right
		// 	if(!in_array('5def400868a3ff1204003ad9', $this->roleAccessRights)) {
		// 		$response = array(
		// 			'status' => REST_Controller::HTTP_UNAUTHORIZED,
		// 			'data' => array(
		// 				"message" => "No have access right"
		// 			)
		// 		);
		// 		$this->set_response($response, REST_Controller::HTTP_OK);
		// 		return;
		// 	}
		// }

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['expertise'] = $this->security->xss_clean(!empty($this->dataPost['expertise']) ? $this->dataPost['expertise'] : array());
		$transaction = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (!empty($transaction)) {
			$type_transaction = $transaction['type'];
		}

		$arrUpdate = array("image_banking.image_expertise" => $this->dataPost['expertise']);
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			$arrUpdate
		);
			$insertLog = array(
			"type" => "transaction",
			"action" => "upload_image",
			"transaction_id" =>$this->dataPost['id'],
			"old" =>$transaction,
			"new" =>$this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update contract success",
			'aaa' => $arrUpdate,
			'type' => $type_transaction,
			'data' => $transaction
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function upload_image_extension_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['image_extension'] = $this->security->xss_clean($data['image_extension']);
		$dataDB = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		//Update
		$this->transaction_model->update(
			array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
			array("image_banking.image_extension" => $data['image_extension'])
		);
		//Insert log
		$insertLog = array(
			"type" => "transaction",
			"action" => "upload_image",
			"transaction_id" =>$this->dataPost['id'],
			"old" =>$dataDB,
			"new" =>$data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_model->insert($insertLog);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "lưu thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function delete_image_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['id'] = $this->security->xss_clean($data['id']);
		$data['key'] = $this->security->xss_clean($data['key']);
		$data['type_img'] = $this->security->xss_clean($data['type_img']);
		$dataDB = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id'])));
		$arrImg = (array)$dataDB['image_banking'][$data['type_img']];
		$path = $arrImg[$data['key']];
		unset($arrImg[$data['key']]);
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update
		$this->transaction_model->update(
			array("_id" => new \MongoDB\BSON\ObjectId($data['id'])),
			array("image_banking." . $data['type_img'] => $arrImg)
		);
		//Insert log
			$insertLog = array(
			"type" => "transaction",
			"action" => "upload_image",
			"transaction_id" =>$this->dataPost['id'],
			"old" =>$dataDB,
			"new" =>$data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);

		$this->log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Delete image success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function pushUpload($cfile)
	{
		$serviceUpload = $this->config->item("url_service_upload");
		// $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
		$post = array('avatar' => $cfile);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $serviceUpload);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	public function payment_finish_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$this->dataPost['name'] = $this->security->xss_clean($this->dataPost['name']);
		$this->dataPost['name_relative_finish'] = $this->security->xss_clean($this->dataPost['name_relative_finish']);
		$this->dataPost['phone'] = $this->security->xss_clean($this->dataPost['phone']);
		$this->dataPost['payment_method'] = $this->security->xss_clean($this->dataPost['payment_method']);
		$this->dataPost['note'] = isset($this->dataPost['note']) ? $this->dataPost['note'] : '';
		$this->dataPost['store'] = $this->security->xss_clean($this->dataPost['store']); // cua hang
		$this->dataPost['date_pay'] = isset($this->dataPost['date_pay']) ? strtotime($this->dataPost['date_pay'].' 23:59:00') : $this->createdAt;
		$this->dataPost['fee_reduction'] = (isset($this->dataPost['fee_reduction'])) ? (float)$this->dataPost['fee_reduction'] : 0;
		$this->dataPost['valid_amount'] = (float)$this->security->xss_clean($this->dataPost['valid_amount']);
		$this->dataPost['amount_total'] = (float)$this->dataPost['valid_amount'];
		$this->dataPost['valid_amount'] = (float)$this->dataPost['valid_amount'] - $this->dataPost['fee_reduction'];
		$this->dataPost['reduced_fee'] = (float)$this->security->xss_clean($this->dataPost['reduced_fee']);
		$this->dataPost['discounted_fee'] = (float)$this->security->xss_clean($this->dataPost['discounted_fee']);
		$this->dataPost['other_fee'] = (float)$this->security->xss_clean($this->dataPost['other_fee']);
		$this->dataPost['phi_phat_sinh'] = (float)$this->security->xss_clean($this->dataPost['phi_phat_sinh']);

		$this->dataPost['penalty_pay'] = (float)$this->security->xss_clean($this->dataPost['penalty_pay']);
		$this->dataPost['amount'] = (float)$this->dataPost['amount'];
		$this->dataPost['amount_total'] = round($this->dataPost['amount'] + $this->dataPost['fee_reduction']);
		$this->dataPost['type_payment'] = $this->security->xss_clean($this->dataPost['type_payment']);
		unset($this->dataPost['secret_key']);
		//  var_dump($this->dataPost); die;
		//Check null
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không thể trống"
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
		$code = $this->transaction_model->getNextTranCode($contractDB['code_contract']);
		$data_transaction = array(
			"total" => $this->dataPost['amount'],
			"amount_total" => isset($this->dataPost['amount_total']) ? $this->dataPost['amount_total'] : 0,// tổng số tiền phải thanh toán
			"valid_amount" => isset($this->dataPost['valid_amount']) ? $this->dataPost['valid_amount'] : 0,// tổng số tiền hợp lệ thanh toán
			"reduced_fee" => isset($this->dataPost['reduced_fee']) ? $this->dataPost['reduced_fee'] : 0,// tổng số tiền phí giảm ngân hàng
			"discounted_fee" => isset($this->dataPost['discounted_fee']) ? $this->dataPost['discounted_fee'] : 0,// tổng số tiền phí giảm trừ
			"other_fee" => isset($this->dataPost['other_fee']) ? $this->dataPost['other_fee'] : 0,// tổng số tiền phí giảm khác
			"fee_reduction" => isset($this->dataPost['fee_reduction']) ? $this->dataPost['fee_reduction'] : 0,// tổng số tiền giảm
			"total_deductible" => isset($this->dataPost['fee_reduction']) ? $this->dataPost['fee_reduction'] : 0,// tổng số tiền giảm
			"penalty_pay" => isset($this->dataPost['penalty_pay']) ? $this->dataPost['penalty_pay'] : 0,// tổng số tiền phạt

			"code" => $code,
			"type" => 3, // tat toan
			"code_contract" => (!empty($contractDB['code_contract'])) ? $contractDB['code_contract'] : "",
			"code_contract_disbursement" => (!empty($contractDB['code_contract_disbursement'])) ? $contractDB['code_contract_disbursement'] : "",
			"customer_name" => !empty($contractDB) ? $contractDB['customer_infor']['customer_name'] : "",
			"payment_method" => $this->dataPost['payment_method'],
			"store" => $this->dataPost['store'],
			"status" => 4,
			"customer_bill_phone" => $this->dataPost['phone'],
			"customer_bill_name" => $this->dataPost['name'],
			"relative_with_contract_owner" => $this->dataPost['name_relative_finish'],
			"date_pay" => (int)$this->dataPost['date_pay'],
			"note" => $this->dataPost['note'],
			"bank_remark" => str_replace("_","",$code),
			"type_payment" => (int)$this->dataPost['type_payment'],
			"created_by" => $this->uemail,
			"created_at" => $this->createdAt,
		);

		if ($data_transaction['payment_method'] == 1) {
			$code_billing = $this->initAutoCodeBilling($data_transaction['store'],$data_transaction['type']);
			$data_transaction['code_billing'] = $code_billing['code_billing'];
		}
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		//Write log
		$log = array(
			"type" => "contract",
			"action" => "payment",
			"data_post" => $this->dataPost,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
        $this->debt_recovery_one($contractDB['code_contract']);
		$url = 'transaction/sendApprove?id=' . (string)$transaction_id . '&view=QLHDV';
		$url_printed = "transaction/printed_billing_contract/" . (string)$transaction_id;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Đang khởi tạo phiếu thu!',
			'url' => $url,
			'url_printed' => $url_printed,
			'transaction_id' => (string)$transaction_id
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

		//}
	}

	public function create_tran_test_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);

		$this->dataPost['date_pay'] = isset($this->dataPost['date_pay']) ? strtotime($this->dataPost['date_pay']) : $this->createdAt;

		$this->dataPost['amount'] = (float)$this->dataPost['amount'];

		//Check null
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['amount'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền thanh toán không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$transaction = $this->contract_tempo_model->find_where(array('code_contract' => $this->dataPost['code_contract'], 'status' => 1));

		$contractDB = $this->contract_model->findOne(array('code_contract' => $this->dataPost['code_contract']));
		//Insert data
		$code = 'PT_' . date("Ymd") . '_' . uniqid();
		$data_transaction = array(
			"total" => $this->dataPost['amount'],

			"type" => 4, // thanh toan ky lai
			"code_contract" => (!empty($contractDB['code_contract'])) ? $contractDB['code_contract'] : "",
			"code_contract_disbursement" => (!empty($contractDB['code_contract_disbursement'])) ? $contractDB['code_contract_disbursement'] : "",
			"customer_name" => !empty($contractDB) ? $contractDB['customer_infor']['customer_name'] : "",

			"status" => 1,

			"date_pay" => (int)$this->dataPost['date_pay'],

			"created_by" => $this->uemail,
			"created_at" => $this->createdAt,
		);
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
        $this->debt_recovery_one($contractDB['code_contract']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thanh toán thành công',
			'contract_id' => (string)$contractDB['_id'],


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	private function finishContract($code, $amount, $fee_finish_contract, $date_pay)
	{
		$transaction = $this->contract_tempo_model->find_where(array('code_contract' => $code, 'status' => 1));
		foreach ($transaction as $tran) {
			$insert = array();
			//$insert['da_thanh_toan'] = $tran['tien_tra_1_ky'];
			$insert['status'] = 2; // da dong tien
			$insert['current_plan'] = 2; // da dong tien
			$this->contract_tempo_model->findOneAndUpdate(array("_id" => $tran['_id']), $insert);
		}


		// update total debt pay
		$contract = $this->contract_model->findOne(array('code_contract' => $code));
		if (!in_array($contract['status'], [17, 40])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không ở trạng thái đang vay",
				'transaction_id' => '',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$update_contract = array(
			"status" => 19, // da tat toan
			"updated_by" => $this->uemail,
			"updated_at" => $this->createdAt,
		);
		$this->contract_model->findOneAndUpdate(array("code_contract" => $code), $update_contract);
		$total_debt_pay = !empty($contract['total_debt_pay']) ? (int)$contract['total_debt_pay'] : 0;
		$total_debt_pay = $total_debt_pay + $amount;
		$this->contract_model->findOneAndUpdate(array("_id" => $contract['_id']), array('total_debt_pay' => $total_debt_pay));

		//$this->finishTempoPlan($code, $amount,$date_pay,0);
		

		$time = date('m/Y', strtotime('now'));
//		$this->tempo_contract_accounting_model->findOneAndUpdate(
//			array("code_contract" => $code, 'time' => $time),
//			array('fee_finish_contract' => $fee_finish_contract)
//		);

		//$this->tinhFeeFinishContract($code, $fee_finish_contract, $time, $date_pay);
		//$this->debt_recovery_one($code);

	}

	public function debt_recovery_one($code_contract)
	{

		$c = $this->contract_model->findOne(array('code_contract' => $code_contract));
           
            if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        
                    );
                }
       $da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$c['code_contract'],'status'=>1,'type'=>array('$in'=>[4,5])),'$total');
        $da_thanh_toan_gh_cc=$this->transaction_model->findOne(array('code_contract'=>$c['code_contract'],'status'=>array('$ne'=>3),'type_payment'=>array('$gt'=>1) , "type"=>4));
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
                    $time =0;
                    $n=0;
                    $ky_tt_xa_nhat = 0;
                    $ky_tt_xa_nhi = 0;
                    $thoi_han_vay = 0;
                    $ky_tra_hien_tai = 0;

                    foreach ($detail as $de) {

                        $total_paid += (isset($de['tien_tra_1_ky'])) ? $de['tien_tra_1_ky'] : 0;
                        $total_goc +=  (isset($de['tien_goc_1ky_con_lai'])) ? $de['tien_goc_1ky_con_lai'] : 0;
                        $total_phi +=  (isset($de['tien_phi_1ky_con_lai'])) ? $de['tien_phi_1ky_con_lai'] : 0;
                        $total_lai +=  (isset($de['tien_lai_1ky_con_lai'])) ? $de['tien_lai_1ky_con_lai'] : 0;
                        $total_da_thanh_toan += $de['da_thanh_toan'];

                       if((count($detail)-1)==$de['ky_tra'])
                       $ky_tt_xa_nhi =$de['ngay_ky_tra'];

                       if(count($detail)==$de['ky_tra'])
                       $ky_tt_xa_nhat =$de['ngay_ky_tra'];

                     $thoi_han_vay=$thoi_han_vay+$de['so_ngay'];
                }
                if(empty($ky_tt_xa_nhi))
                 $ky_tt_xa_nhi=$c['disbursement_date']; 
               $time = 0;
              $current_day = strtotime(date('m/d/Y'));
              $datetime = $current_day;
              $detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $c['code_contract'], 'status' => 1]);
              if (!empty($detail)) {
              $datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
              $ky_tra_hien_tai = !empty($detail[0]['ky_tra']) ? intval($detail[0]['ky_tra']) : "";
              $time = intval(($current_day - $datetime) / (24*60*60));
               }
                if($c['status']==33 ||  $c['status']==34  ||  $c['status']==19  ||  $c['status']==40)
                $time = 0;
               $penalty=$this->contract_model->get_phi_phat_cham_tra((string)$c['_id'], strtotime(date('Y-m-d').' 23:59:59'));
               $total_phi_phat_cham_tra=$penalty['penalty_now']+$penalty['tong_penalty_con_lai'];
               $check_gia_han=2;
               $check_tt_gh=0;
               $check_tt_cc=0;
              
                 if(!empty($da_thanh_toan_gh_cc))
                  {
                  if($da_thanh_toan_gh_cc['status']==1 && $da_thanh_toan_gh_cc['type_payment']==2)
                  {
                    //đã thanh toán gia hạn
                    $check_tt_gh=1;
                  }
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4]) && $da_thanh_toan_gh_cc['type_payment']==2)
                  {
                    //chờ thanh toán gia hạn
                    $check_tt_gh=2;
                  }
                    if($da_thanh_toan_gh_cc['status']==1 && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //đã thanh toán gia hạn
                    $check_tt_cc=1;
                  }
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4]) && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //chờ thanh toán gia hạn
                    $check_tt_cc=2;
                  }
                   
                  }

                   if( $current_day>=$ky_tt_xa_nhi && $c['loan_infor']['type_interest']==2  && (strtotime(date('Y-m-d').' 00:00:00') >=$c['disbursement_date']) && $check_tt_gh==0 ){
                //đủ yêu cầu gia hạn
                 $check_gia_han=1;
                  }
                  if( $current_day>=$ky_tt_xa_nhi && $c['loan_infor']['type_loan']['code']=='CC'  && (strtotime(date('Y-m-d').' 00:00:00') >=$c['disbursement_date']) && $c['loan_infor']['number_day_loan']=='30'  && $check_tt_gh==0){
                //đủ yêu cầu gia hạn
                 $check_gia_han=1;
                  }
                       $data=[
                        'expire_date'=>$ky_tt_xa_nhat,
                        'debt'=>[
                        'current_day'=>$current_day,
                        'ngay_ky_tra'=>$datetime,
                        'ky_tra_hien_tai'=>$ky_tra_hien_tai,
                         'so_ngay_cham_tra'=>$time,
                         'tong_tien_phai_tra'=>$total_paid,
                         'tong_tien_goc_con'=>$total_goc,
                         'tong_tien_phi_con'=>$total_phi,
                         'tong_tien_lai_con'=>$total_lai,
                         'tong_tien_cham_tra_con'=>$total_phi_phat_cham_tra,
                        'tong_tien_da_thanh_toan'=>$total_da_thanh_toan,
                        'tong_tien_da_thanh_toan_pt'=>$da_thanh_toan_pt,
                        'ky_tt_xa_nhat'=>$ky_tt_xa_nhat,
                        'ky_tt_xa_nhi'=>$ky_tt_xa_nhi+24*60*60,
                        'check_gia_han'=>$check_gia_han,
                         'check_tt_gh'=>$check_tt_gh,
                        'check_tt_cc'=>$check_tt_cc,
                        'thoi_han_vay' =>$thoi_han_vay,
                        'is_qua_han' =>$is_qua_han,
                        'run_date'=>date('d-m-Y H:i:s')
                          ]
                       ];
                    $this->contract_model->update(
                        array("_id" => $c['_id']),
                        $data
                    );
               }
        

		return 'OK';

	}

	public function update_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//quyền chỉ kế toán sửa
		if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(
					"message" => "No have access right"
				)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data = $this->input->post();
		unset($data['type']);
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->transaction_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		$transaction = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if (isset($data['date_pay'])) {
			$data['date_pay'] = strtotime($data['date_pay'].date(' H:i:s'));
		}
		if (isset($data['date_bank'])) {
			$data['date_bank'] = strtotime($data['date_bank']);
		}
		if (isset($data['discounted_fee'])) {
			$data['discounted_fee'] = (float)$data['discounted_fee'];
			$other_fee = isset($transaction['other_fee']) ? $transaction['other_fee'] : 0;
			$reduced_fee = isset($transaction['reduced_fee']) ? $transaction['reduced_fee'] : 0;
			$data['total_deductible'] = $data['discounted_fee'] + $other_fee;
		}
		if (isset($data['other_fee'])) {
			$data['other_fee'] = (float)$data['other_fee'];
			$discounted_fee = isset($transaction['discounted_fee']) ? $transaction['discounted_fee'] : 0;
			$reduced_fee = isset($transaction['reduced_fee']) ? $transaction['reduced_fee'] : 0;
			$data['total_deductible'] = $data['other_fee'] + $discounted_fee;
		}
		if (isset($data['reduced_fee'])) {
			$data['reduced_fee'] = (float)$data['reduced_fee'];
			$other_fee = isset($transaction['other_fee']) ? $transaction['other_fee'] : 0;
			$discounted_fee = isset($transaction['discounted_fee']) ? $transaction['discounted_fee'] : 0;
			$data['total_deductible'] =  $other_fee + $discounted_fee;
		}
		if (isset($data['amount_actually_received'])) {
			$data['amount_actually_received'] = (float)$data['amount_actually_received'];
		}

		$data['updated_at'] = $this->createdAt;

		if (isset($data['type_t'])) {
			$data['type'] = (int)$data['type_t'];
		}
		if (isset($data['status'])) {
			$data['status'] = (int)$data['status'];
		}
		if ($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại transaction nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//  $this->log($data);
		$log = array(
			"type" => "transaction",
			"action" => 'update',
			"data_post" => $data,
			"transaction_id" => (string)$transaction_id,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
		unset($data['id']);

		unset($data['type_t']);
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update transaction success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function push_api_sms($data_post = [])
	{
		$url_sms = $this->config->item("URL_SMS");
		$accessKey = $this->config->item("ACCESS_KEY");
		$service = $url_sms;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $service);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	private function getTotalPaid($code_contract, $date_pay)
	{
		$dataDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		if (empty($dataDB)) {
			return 0;
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
			if (empty($fee['percent_advisory'])) {
				$fee['percent_advisory'] = 0;
			}
			if (empty($fee['percent_expertise'])) {
				$fee['percent_expertise'] = 0;
			}
			if (empty($fee['percent_interest_customer'])) {
				$fee['percent_interest_customer'] = 0;
			}
			if (empty($fee['percent_prepay_phase_1'])) {
				$fee['percent_prepay_phase_1'] = 0;
			}
			if (empty($fee['percent_prepay_phase_2'])) {
				$fee['percent_prepay_phase_2'] = 0;
			}
			if (empty($fee['percent_prepay_phase_3'])) {
				$fee['percent_prepay_phase_3'] = 0;
			}
			if (empty($fee['penalty_percent'])) {
				$fee['penalty_percent'] = 0;
			}
			if (empty($fee['penalty_amount'])) {
				$fee['penalty_amount'] = 0;
			}
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
		//$date_now = date('d-m-Y');
		$now = (empty($date_pay)) ? $date_pay : strtotime(date('Y-m-d') . ' 23:59:59');
		$all_contract_tempo = $this->contract_tempo_model->find_where(array('code_contract' => $dataDB['code_contract']));
		if (empty($all_contract_tempo)) {
			return array();
		}
		$so_ngay_vay = (isset($this->contract_model->get_phi_phat_cham_tra((string)$dataDB['_id'], $now)['so_ngay_vay'])) ? (int)$this->contract_model->get_phi_phat_cham_tra((string)$dataDB['_id'], $now)['so_ngay_vay'] : (int)$dataDB['loan_infor']['number_day_loan'];
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
				$goc_lai_1_ky = -pmt($lai_suat_ndt, $so_ky_vay, $tien_gian_ngan);
				$lai_ky = $lai_suat_ndt * $tien_goc_con;
				if ($so_ngay_chenh_lech >= 0 && $so_ngay_chenh_lech <= 3) {
					$tong_tien_lai_phi_tat_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$tong_tien_thanh_toan = $phi_tham_dinh + $phi_tu_van + $goc_lai_1_ky;
					$phi_thanh_toan_truoc_han = 0;
				} else if (($so_ngay_da_vay >= (2 * $so_ngay_vay / 3)) && $so_ngay_chenh_lech <= 0) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
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
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_3 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if (($so_ngay_da_vay >= ($so_ngay_vay / 3)) && ($so_ngay_da_vay <= (2 * $so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_2 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else if ($so_ngay_da_vay >= 0 && ($so_ngay_da_vay <= ($so_ngay_vay / 3 - 1))) {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
				} else {
					$tong_tien_lai_phi_tat_toan = ($phi_tham_dinh + $phi_tu_van + $lai_ky) / 30 * $so_ngay_vay_thuc_te;
					$phi_thanh_toan_truoc_han = $percent_prepay_phase_1 * $tien_gian_ngan;
					$tong_tien_thanh_toan = $tien_goc_con + $tong_tien_lai_phi_tat_toan + $phi_thanh_toan_truoc_han;
					$tien_phi_phat_tra_cham = $tien_goc_con * $phan_tram_phi_phat_tra_cham * $so_ngay_vay_thuc_te / 30;
					$tien_phi_phat_tra_cham = ($tien_phi_phat_tra_cham > $phi_phat_tra_cham) ? $tien_phi_phat_tra_cham : $phi_phat_tra_cham;
					$tong_tien_thanh_toan = $tong_tien_thanh_toan + $tien_phi_phat_tra_cham;
				}
			}

		}
		return array(
			'tong_tien_thanh_toan' => $tong_tien_thanh_toan,
			'phi_thanh_toan_truoc_han' => $phi_thanh_toan_truoc_han
		);
	}

	public function approve_contract_extension_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//quyền chỉ view kế toán
		if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(
					"message" => "No have access right"
				)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['transaction_id'] = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['transaction_id'])));
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Update
		$this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			array(
				"status" => (int)$this->dataPost['status'],
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail
			)
		);
		$date_pay = (isset($transaction['date_pay'])) ? $transaction['date_pay'] : $this->createdAt;
		if ((int)$this->dataPost['status'] == 1) {
			$inforDB = $this->contract_model->findOne(array("code_contract" => $transaction['code_contract']));
			$total_amount = floatval($transaction['total']) - floatval($inforDB['fee']['penalty_amount']);
			$total = $this->getTotalPaid($transaction['code_contract'], $date_pay);
			if (empty($total)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Hợp đồng không tồn tại kỳ thanh toán",
					'transaction_id' => '',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$phi_thanh_toan_truoc_han = $this->contract_model->get_phi_tat_toan_truoc_han($inforDB);
			$this->finishContract($transaction['code_contract'], $total_amount, $phi_thanh_toan_truoc_han, $date_pay);
			$this->tinhtoanBangLaiKy_giahan($transaction['code_contract'], $total_amount, $transaction['_id']);
			$this->tinhFeeExtend($transaction['code_contract'], $transaction['_id']);
			$this->contract_model->update(
				array("code_contract" => $transaction['code_contract']),
				array("status" => 23)
			);
			$url = 'transaction/upload?id=' . $this->dataPost['transaction_id'];
			$message = 'Duyệt thanh toán thành công';
		} else {
			$this->contract_model->update(
				array("code_contract" => $transaction['code_contract']),
				array("status" => 17)
			);
			$url = 'transaction';
			$message = 'Hủy thanh toán thành công';
		}
		//Write log
		$log = array(
			"type" => "transaction",
			"action" => 'update',
			"data_post" => $this->dataPost,
			"transaction_id" => $transaction['code_contract'],
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'msg' => $message,
			'url' => $url,
			"contract_id" => (string)$inforDB["_id"]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function approve_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//quyền chỉ view kế toán
		if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(
					"message" => "No have access right"
				)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['transaction_id'] = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['code_transaction_bank'] = $this->security->xss_clean($this->dataPost['code_transaction_bank']);
		$this->dataPost['bank'] = $this->security->xss_clean($this->dataPost['bank']);
		$this->dataPost['approve_note'] = $this->security->xss_clean($this->dataPost['approve_note']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['transaction_id'])));
		if (empty($transaction) ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
       
		if (!empty($this->dataPost['code_transaction_bank']) ) {
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $this->dataPost['code_transaction_bank'], "status" => array('$ne' => 3), "code" => array('$ne' => $transaction['code'])));


			if (!empty($transaction_ck_pt)) {
				foreach ($transaction_ck_pt as $key => $value) {
					if (date("Ymd", $value['date_pay']) != date("Ymd", $transaction['date_pay'])) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Phiếu thu đã tồn tại (khác ngày):"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}

					if (date("Ymd", $value['date_pay']) == date("Ymd", $transaction['date_pay']) && $value['code_contract'] == $transaction['code_contract']) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Phiếu thu đã tồn tại (trùng ngày):"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
		$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($transaction['code_contract']);
				$phi_gia_han = 0;
		$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));		
			$type_payment=isset($transaction['type_payment']) ? $transaction['type_payment'] : 1;
                if ($type_payment == 2 && $contractDB['status'] == 29)
                {
                	$phi_gia_han_origin= isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }
                 if($type_payment==0)
                {
                	$type_payment=1;

                }
           $transaction['type_payment']=$type_payment;
	    $date_pay = (isset($transaction['date_pay'])) ? strtotime(date("Y-m-d", $transaction['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');

		
		$arr_data=[
					'date_pay'=>$date_pay,
					'id_contract'=>(string)$contractDB['_id'],
				];
				$payment=$this->payment_model->get_payment($arr_data)['contract'];
				$so_tien_phi_cham_tra_phai_tra=(isset($payment['penalty_pay'])) ? $payment['penalty_pay'] : 0;
				$tat_toan_part_2=$this->payment_model->get_infor_tat_toan_part_2(['code_contract'=>$contractDB['code_contract'],'date_pay'=>$date_pay]);
				$so_tien_lai_phai_tra=(isset($tat_toan_part_2['lai_chua_tra_qua_han'])) ? $tat_toan_part_2['lai_chua_tra_qua_han'] : 0;
				$so_tien_phi_phai_tra=(isset($tat_toan_part_2['phi_chua_tra_qua_han'])) ? $tat_toan_part_2['phi_chua_tra_qua_han'] : 0;
		$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
		$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['phi_phat_sinh'];
		$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['so_ngay_phat_sinh'];
		$so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$transaction['_id'])['so_tien_phat_sinh'];



		if ((int)$this->dataPost['status'] == 1 || (int)$this->dataPost['status'] == 5) {
			if ($transaction['type'] == 3 ) {
				
				$this->sendEmailApprove_hcns($contractDB);
				$this->sendEmailApprove_users($contractDB, $this->dataPost['approve_note'], $transaction['code'], 'duyệt tất toán',$transaction['created_by']);

			} else if ($transaction['type'] == 4) {
			
				$this->sendEmailApprove_users($contractDB, $this->dataPost['approve_note'], $transaction['code'], 'duyệt thanh toán',$transaction['created_by']);
			}
			$url = $this->dataPost['transaction_id'];
			$message = 'Duyệt thanh toán thành công';

		} else {
			
			$url = 'transaction';
			$message = 'Hủy thanh toán thành công';
			$this->sendEmailApprove_users($contractDB, $this->dataPost['approve_note'], $transaction['code'], 'từ chối duyệt thanh toán',$transaction['created_by']);
		}
		
		//Update
		$update_tran=array(
				"code_transaction_bank" => $this->dataPost['code_transaction_bank'],
				"bank" => $this->dataPost['bank'],
				"approve_note" => $this->dataPost['approve_note'],
				"status" => (int)$this->dataPost['status'],
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
				"fee_delay_pay" => $phi_phat_tra_cham,
				"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
				"so_tien_phat_sinh"=>$so_tien_phat_sinh,
				"type_payment" => $type_payment,
			
			);
		if ($type_payment == 2 && $contractDB['status'] == 29)
		{
		$code_contract_parent_gh=(isset($contractDB['code_contract_parent_gh'])) ? $contractDB['code_contract_parent_gh'] : $contractDB['code_contract_disbursement'];
           $update_tran['code_contract_parent_gh']=$code_contract_parent_gh;
           $update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
           $update_tran['so_tien_lai_phai_tra']= (float)$so_tien_lai_phai_tra;
		   $update_tran['so_tien_phi_phai_tra']= (float)$so_tien_phi_phai_tra;
		}
		if ($type_payment == 3 && $contractDB['status'] == 31)
		{
          $code_contract_parent_cc=(isset($contractDB['code_contract_parent_cc'])) ? $contractDB['code_contract_parent_cc'] : $contractDB['code_contract_disbursement'];
           $update_tran['code_contract_parent_cc']=$code_contract_parent_cc;
           $update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
		}
		if ($type_payment==4)
		{
			$tien_chenh_lech_sau_tat_toan = 0;
			if ($transaction["total_deductible"] > 0) {
				$tien_chenh_lech_sau_tat_toan = $transaction["valid_amount"] - $transaction["amount_total"];
			} else {
				$tien_chenh_lech_sau_tat_toan = $transaction["total"] - $transaction["valid_amount"];
			}

			$update_tran["tien_chenh_lech_tat_toan"] = $tien_chenh_lech_sau_tat_toan;
			$this->contract_model->update(
				array("code_contract" => $contractDB['code_contract']),
				array("liquidation_info.different_amount_after_payment_finish" => $tien_chenh_lech_sau_tat_toan)
			);
		}
		if ((int)$this->dataPost['status'] == 1 || (int)$this->dataPost['status'] == 5) {
			$update_tran['approved_at']= $this->createdAt;
			$update_tran['approved_by']= $this->uemail;
		}
		
		$transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			$update_tran
			
		);
		$this->debt_recovery_one($transaction['code_contract']);
		$log = array(
			"type" => "transaction",
			"action" => 'approve_transaction',
			"data_post" => $this->dataPost,
			"transaction_id" => $transaction['code_contract'],
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$log_ksnb = array(
			"type" => "contract_ksnb",
			"action" => "Approve",
			"transaction_ksnb_id" => (string)$this->dataPost['transaction_id'],
			"old" => $transaction,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log_ksnb);
		$this->log_model->insert($log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'msg' => $message,
			'url' => $url,
			'data' => $transaction,
			'data_contract'=>$contractDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function sendEmailApprove_hcns($contract)
	{
		$data = array(
			'code' => "vfc_send_check_settlement",
			"customer_name" => $contract['customer_infor']['customer_name'],
			"code_contract" => $contract['code_contract'],
			"store_name" => $contract['store']['name'],
			"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
			"product" => $contract['loan_infor']['type_loan']['text'],
			"product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
			"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
			"note" => '',
			"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng gốc cuối kì",
			"phone_store" => ''
		);

		$data['email'] = 'hcns@tienngay.vn';
		$data['full_name'] = 'HCNS';
		$data['API_KEY'] = $this->config->item('API_KEY');
		$this->user_model->send_Email($data);
		return;
	}

	private function sendEmailApprove_users($contract, $note, $code_transaction, $type_approve,$email_user)
	{
		$users_store = $this->getUserbyStores_email($contract['store']['id']);
		$user_create= $this->user_model->findOne(array("email"=>$email_user,"type"=>"1","status"=>"active"));
		$full_name=(isset($user_create['full_name'])) ? $user_create['full_name'] : '';
		$data = array(
			'code' => "vfc_send_approve_transaction",
			"customer_name" => $contract['customer_infor']['customer_name'],
			"code_contract" => $contract['code_contract'],
			"store_name" => $contract['store']['name'],
			"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
			"product" => $contract['loan_infor']['type_loan']['text'],
			"product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
			"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
			"note" => $note,
			"created_by" => $email_user.' - '.$full_name,
			"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng gốc cuối kì",
			"type_approve" => $type_approve,
			"code_transaction" => $code_transaction
		);
		
        $group_role_user_create=$this->getGroupRole((string)$user_create['_id'] ) ;
        $user_ids_groups=array();
        if(in_array('thu-hoi-no', $group_role_user_create))
        {
        $store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
        $area = $this->area_model->findOne(array("code" => $store['code_area']));
        $tp_thn_id = array('60a5e0c05324a73f2e25d224');
        if($area['domain']['code']=="MB")
        {
        //tp thn miền bắc
		$tp_thn_id = array('60a5e0c05324a73f2e25d224');
	    }
	     if($area['domain']['code']=="MN")
        {
		//tp thn miền nam
		$tp_thn_id = array('60a5e0d45324a73eba244ca6');
	    }
		$user_ids_groups = $this->getUserGroupRole_email($tp_thn_id);
		array_push($user_ids_groups,$email_user);

	    }
	  
		$arr = array_merge($users_store, $user_ids_groups);
		$allusers = array_values($arr);	

		if (!empty($allusers)) {
			foreach ($allusers as $key => $value) {
				$data['email'] = $value;
				$data['full_name'] = $value;
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
			}
		}

		return;
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

	public function payment_all_contract_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		 $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $this->dataPost['code_contract']));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng đã khóa"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
        }
		$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $this->dataPost['code_contract'], "status" => ['$in'=>[1,5]],'type'=>array('$in'=>[3,4])));
		if (empty($data_transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($data_transaction))
			foreach ($data_transaction as $key => $value) {
				 
				//if (!empty($value['temporary_plan_contract_id'])) continue;
				$transaction = $value;

				$date_pay = (isset($value['date_pay'])) ? strtotime(date("Y-m-d", $value['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $value['created_at']) . '  23:59:59');
				$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));
				$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
				$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['phi_phat_sinh'];
				$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_ngay_phat_sinh'];
		       $so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_tien_phat_sinh'];
				
				$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($transaction['code_contract']);
				$phi_gia_han = 0;
				$type_payment=isset($transaction['type_payment']) ? $transaction['type_payment'] : 1;

                if($type_payment==2)
                {
                	$phi_gia_han_origin= isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }

                if($type_payment==0)
                {
                	$type_payment=1;
                }
                //tất toán hoặc cơ cấu 
				if ($transaction['type'] == 3 || $type_payment==3) {

					
                   $tien_thua_thanh_toan=0;
			       $transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
				$transaction['total'] = $transaction['total']+$tien_thua_thanh_toan ;
				  if($type_payment==2)
		           {
				// $transaction['total'] =$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   }
				 
					$phi_thanh_toan_truoc_han = $this->contract_model->get_phi_tat_toan_truoc_han($contractDB, $date_pay);

					//Get lãi NĐT đã trừ vào kỳ cuối (với các HĐ có coupon giảm lãi kỳ đầu trừ vào kỳ cuối)
					$tien_lai_da_tru_vao_ky_cuoi = 0;
					if ( date('Y-m-d', $date_pay) == date('Y-m-d', $contractDB['expire_date']) && $contractDB['interest_reduction']['isset_date_late'] == false ) {
						$tien_lai_da_tru_vao_ky_cuoi = !empty($contractDB['interest_reduction']['amount_interest_reduction']) ? $contractDB['interest_reduction']['amount_interest_reduction'] : 0;
					}

					//chia tất toán
					$this->cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, (int)$transaction['total'], $phi_thanh_toan_truoc_han, $phi_phat_tra_cham, $date_pay, $phi_phat_sinh, $phi_gia_han,$type_payment,(int)$transaction['total_deductible'], (string)$transaction['_id'], $tien_lai_da_tru_vao_ky_cuoi);
					//Update các kì trước kì tất toán
					$this->cap_nhat_tat_toan_cac_ki_truoc_do($contractDB, $phi_phat_tra_cham, $date_pay);
					//Update các kì tiếp theo
					// $this->cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB, $phi_phat_tra_cham, $date_pay);
					if($type_payment!=3)
					{
					$this->finishContract($transaction['code_contract'], (int)$transaction['total'], $phi_thanh_toan_truoc_han, $date_pay);
				    }
					
					
					$this->tinh_goc_lai_phi_transaction_tat_toan($transaction['_id'], $phi_phat_sinh);
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);

				} else if ($transaction['type'] == 4  && $type_payment!=3) {
				
                   $tien_thua_thanh_toan = 0;
                   //tiền thừa thanh toán
		           if($type_payment==2)
		           {
					$transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
				}
				
					$transaction['total'] = $transaction['total']+ $tien_thua_thanh_toan ;
				//chia tiền thiếu
		          if($type_payment==2)
		           {
				 $transaction['total'] =$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   }
				   //chuyển trạng thái dự kiến kỳ hoàn thành
					$this->finishTempoPlan($transaction['code_contract'], (int)$transaction['total'], $date_pay,(int)$transaction['total_deductible']);
					//chia bảng lãi kỳ
					$this->tinhtoanBangLaiKy($transaction['code_contract'], (int)$transaction['total'], (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$transaction, (int)$transaction['total_deductible']);
					//chia bảng lãi tháng
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);
				}
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;

				$update_tran=array(
						"updated_at" => $this->createdAt,
						"updated_by" => $this->uemail,
						"fee_delay_pay" => $phi_phat_tra_cham,
						"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
						"so_tien_phat_sinh"=>$so_tien_phat_sinh,
						"type_payment" => $type_payment,
						'ky_da_tt_gan_nhat'=>$ky_da_tt_gan_nhat
						
					);
				
				if($type_payment==2)
				{
				//trước 30/9  không tính phát sinh 
				if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59'))
	            {	
				 $update_tran['so_ngay_phat_sinh']=0;
                 $update_tran['so_tien_phat_sinh']=0;
                 }	
				$code_contract_parent_gh=(isset($contractDB['code_contract_parent_gh'])) ? $contractDB['code_contract_parent_gh'] : $contractDB['code_contract'];
		           $update_tran['code_contract_parent_gh']=$code_contract_parent_gh;
				}
				if($type_payment==3)
				{
		          $code_contract_parent_cc=(isset($contractDB['code_contract_parent_cc'])) ? $contractDB['code_contract_parent_cc'] : $contractDB['code_contract'];
		           $update_tran['code_contract_parent_cc']=$code_contract_parent_cc;
				}
				$update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $transaction['_id']),
					 $update_tran
					
				);
				

			}
			$this->debt_recovery_one($this->dataPost['code_contract']);
			$response = array(
					'status' => REST_Controller::HTTP_OK,
					'msg' => 'OK',
					'url' => '',
					'data'=>''
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;


	}
	public function get_con_lai_sau_thanh_toan($code_contract,$date_pay)
	{
		$ptData=$this->temporary_plan_contract_model->get_tien_phai_tra_hop_dong($code_contract,$date_pay);
		$dtData=$this->temporary_plan_contract_model->get_tien_da_tra_sau_thanh_toan($code_contract,$date_pay);
		$goc_phai_tra= !empty($ptData['tien_goc_phai_tra']) ? $ptData['tien_goc_phai_tra'] : 0;
		$lai_phai_tra=!empty($ptData['tien_lai_phai_tra']) ? $ptData['tien_lai_phai_tra'] : 0;
		$phi_phai_tra=!empty($ptData['tien_phi_phai_tra']) ? $ptData['tien_phi_phai_tra'] : 0;
		$cham_tra_phai_tra=!empty($ptData['tien_cham_tra_phai_tra']) ? $ptData['tien_cham_tra_phai_tra'] : 0;
		$goc_da_tra=!empty($dtData['tien_goc_da_tra']) ? $dtData['tien_goc_da_tra'] : 0;
		$lai_da_tra=!empty($dtData['tien_lai_da_tra']) ? $dtData['tien_lai_da_tra'] : 0;
		$phi_da_tra=!empty($dtData['tien_phi_da_tra']) ? $dtData['tien_phi_da_tra'] : 0;
		$cham_tra_da_tra=!empty($dtData['tien_cham_tra_da_tra']) ? $dtData['tien_cham_tra_da_tra'] : 0;

		$goc_con_lai=$goc_phai_tra-$goc_da_tra;
		$lai_con_lai=$lai_phai_tra-$lai_da_tra;
		$phi_con_lai=$phi_phai_tra-$phi_da_tra;
		$cham_tra_con_lai=$cham_tra_phai_tra-$cham_tra_da_tra;
		return ['goc_con_lai'=>$goc_con_lai,
		        'lai_con_lai'=>$lai_con_lai,
		        'phi_con_lai'=>$phi_con_lai,
		        'cham_tra_con_lai'=>$cham_tra_con_lai,
		       ];


	}
	//chia tiền thiếu
	public function chia_tien_thieu($money,$code_contract_parent_gh)
	{
		$amountRemain=$money;
		$transactionData = $this->transaction_model->find_where(array('code_contract_parent_gh' =>$code_contract_parent_gh,'type' => 4, "status" => ['$in'=>[1,5]]));
					if (!empty($transactionData)) {
			$so_thieu_da_tra_transaction=0;
						foreach ($transactionData as $key => $value) {
            $so_tien_thieu_con_lai=!empty($value['so_tien_thieu_con_lai']) ? $value['so_tien_thieu_con_lai'] : 0;
            $so_tien_thieu_da_chuyen=!empty($value['so_tien_thieu_da_chuyen']) ? $value['so_tien_thieu_da_chuyen'] : 0;
						

		if ($amountRemain > 0) {
					//tiền thiếu
					if ($so_tien_thieu_con_lai > 0) {
						if ($amountRemain >= $so_tien_thieu_con_lai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['so_tien_thieu_da_chuyen'] = $so_tien_thieu_da_chuyen + $so_tien_thieu_con_lai;

							$so_thieu_da_tra_transaction = $so_thieu_da_tra_transaction + $so_tien_thieu_con_lai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['so_tien_thieu_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $so_tien_thieu_con_lai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['so_tien_thieu_da_chuyen'] = $so_tien_thieu_da_chuyen + $amountRemain;

							$so_thieu_da_tra_transaction = $so_thieu_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['so_tien_thieu_con_lai'] = $so_tien_thieu_con_lai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->transaction_model->update(
							array("_id" => $value['_id']),
							$dataUpdate
						);
					}

				
				}

						}
					}
		return $amountRemain;
	}
    //chạy lại gia hạn / cơ cấu
	public function payment_all_contract_gh_cc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		 $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $this->dataPost['code_contract']));
		 //hợp đồng khóa thì không chạy lại
       if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng đã khóa"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
        }
		$last=(isset($this->dataPost['last'])) ? $this->dataPost['last'] : 0;
		//loại gia hạn / cơ cấu
		$type_gh_cc=(isset($this->dataPost['type_gh_cc'])) ? $this->dataPost['type_gh_cc'] : '';
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$this->dataPost['code_contract']));
		$ngay_giai_ngan =  strtotime('+10 day', $contractDB['disbursement_date']);
		$KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($contractDB['code_contract']);
		$ngay_gia_han=strtotime('+11 day', $KiPhaiThanhToanXaNhat);
		//lấy phiếu thu thanh toán hoặc tất toán theo gia hạn hoặc cơ cấu
		 if($type_gh_cc=="GH"  )
				{

			$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $this->dataPost['code_contract'], "status" => 1,'type'=>array('$in'=>[3,4])));

	   }else{
         $data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $this->dataPost['code_contract'], "status" => 1,'type'=>array('$in'=>[3,4])));

	   }
	
		if (empty($data_transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($data_transaction))
		{
			foreach ($data_transaction as $key => $value) {

				$transaction = $value;

				$date_pay = (isset($transaction['date_pay'])) ? strtotime(date("Y-m-d", $transaction['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');
				$arr_data=[
					'date_pay'=>$date_pay,
					'id_contract'=>(string)$contractDB['_id'],
				];
				$payment=$this->payment_model->get_payment($arr_data)['contract'];
				$so_tien_phi_cham_tra_phai_tra=(isset($payment['penalty_pay'])) ? $payment['penalty_pay'] : 0;
				$tat_toan_part_2=$this->payment_model->get_infor_tat_toan_part_2(['code_contract'=>$contractDB['code_contract'],'date_pay'=>$date_pay]);
				$so_tien_lai_phai_tra=(isset($tat_toan_part_2['lai_chua_tra_qua_han'])) ? $tat_toan_part_2['lai_chua_tra_qua_han'] : 0;
				$so_tien_phi_phai_tra=(isset($tat_toan_part_2['phi_chua_tra_qua_han'])) ? $tat_toan_part_2['phi_chua_tra_qua_han'] : 0;

				$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
				$phi_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['phi_phat_sinh'];
				$so_ngay_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_ngay_phat_sinh'];
				$so_tien_phat_sinh = $this->contract_model->get_phi_phat_sinh($contractDB, $date_pay, (string)$value['_id'])['so_tien_phat_sinh'];
				$phi_gia_han_da_tra = $this->transaction_model->get_phi_gia_han_da_tra($contractDB['code_contract']);
				$phi_gia_han = 0;
				$type_payment= 1;
                //phiếu thu cuối gắn là phiếu thu gia hạn
                if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {
                   $phi_phat_tra_cham=[];
	            }	
                if((count($data_transaction)-1)==$key && $last!=1 && $type_gh_cc=="GH")
                {
                	$type_payment=2;
                	$phi_gia_han_origin= isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
                	$phi_gia_han =$phi_gia_han_origin-$phi_gia_han_da_tra; 
                }
                //phiếu thu cuối gắn là phiếu thu cơ cấu
                  if((count($data_transaction)-1)==$key && $last!=1 && $type_gh_cc=="CC")
                {
                	$type_payment=3;
                	
                }
                //tất toán và cơ cấu
				if ($transaction['type'] == 3 || $type_payment==3) {

                   $tien_thua_thanh_toan=0;
			       $transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1));
			       //lấy tiền thừa
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
							          )
							
							);

						}
					}
					//cộng tiền thừa
				$money_tt = $transaction['total']+$tien_thua_thanh_toan;
                    //trừ tiền thiếu
				 $money_tt=$this->chia_tien_thieu($money_tt,$contractDB['code_contract_parent_gh']);
				   
			
					$phi_thanh_toan_truoc_han = $this->contract_model->get_phi_tat_toan_truoc_han($contractDB, $date_pay);
					$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
						"code_contract" => $codeContract
					));


					//Update lại kì hiện tại
					$this->cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, (int)$money_tt, $phi_thanh_toan_truoc_han, $phi_phat_tra_cham, $date_pay, $phi_phat_sinh, $phi_gia_han,$type_payment,(int)$transaction['total_deductible'], (string)$transaction['_id']);
					//Update các kì trước kì tất toán
					$this->cap_nhat_tat_toan_cac_ki_truoc_do($contractDB, $phi_phat_tra_cham, $date_pay);
					//Update các kì tiếp theo
					//$this->cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB, $phi_phat_tra_cham, $date_pay);
					if($type_payment!=3)
					{
					$this->finishContract($contractDB['code_contract'], (int)$money_tt, $phi_thanh_toan_truoc_han, $date_pay);
				    }
					
					//$this->tinhtoanBangLaiKy($contractDB['code_contract'],(int)$transaction['total'], $transDB['_id']);
					$this->tinh_goc_lai_phi_transaction_tat_toan($transaction['_id'], $phi_phat_sinh);
					//gen lãi tháng
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);

				} else if ($transaction['type'] == 4 && $type_payment!=3) {
					 //thanh toán
					 $tien_thua_thanh_toan = 0;
					 //lấy tiền thừa gia hạn 
	                 if($type_payment==2)
	                 {
	                 	$transactionData = $this->transaction_model->find_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1));
					if (!empty($transactionData)) {
						foreach ($transactionData as $key => $value) {

							if (isset($value['tien_thua_thanh_toan'])) {
								$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
							}
							$transDB = $this->transaction_model->findOneAndUpdate(
								array("_id" => $value['_id']),
								array("tien_thua_thanh_toan_con_lai" =>0,
									  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
										          )
										
										);

									}
								}
	                 }
                    
					$transaction['total'] = $transaction['total']+$tien_thua_thanh_toan;
					//chia tiền thiếu
			     	$transaction['total']=$this->chia_tien_thieu($transaction['total'],$contractDB['code_contract_parent_gh']);
				   //gán trạng thái hoàn thành 
					$this->finishTempoPlan($contractDB['code_contract'], (int)$transaction['total'],$date_pay,(int)$transaction['total_deductible']);
					//chia bảng lãi kỳ
					$this->tinhtoanBangLaiKy($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$transaction, (int)$transaction['total_deductible']);
					//tính lại bảng lãi tháng
					$this->tinhtoanBangLaiThang($contractDB['code_contract'], (int)$transaction['total'], (string)$transaction['_id']);
				}
				//lấy kỳ thanh toán gần nhất
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                 $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;
				$arr_update_tran=	array(
						"updated_at" => $this->createdAt,
						"updated_by" => $this->uemail,
						"fee_delay_pay" => $phi_phat_tra_cham,
						"code_contract" => $contractDB['code_contract'],
						"code_contract_disbursement" =>  $contractDB['code_contract_disbursement'],
						"type_payment" => $type_payment,
						"so_ngay_phat_sinh"=>$so_ngay_phat_sinh,
						"so_tien_phat_sinh"=>$so_tien_phat_sinh,
						"ky_da_tt_gan_nhat"=>$ky_da_tt_gan_nhat,
					);
				 
				if($type_gh_cc=="CC" )
				{
			    $arr_update_tran['code_contract_parent_cc']= $this->dataPost['code_contract_origin'];
			    $arr_update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
			    //trước 30/9 không tính phí phát sinh, chậm trả
			      if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {	
				 $arr_update_tran['so_ngay_phat_sinh']=0;
                 $arr_update_tran['so_tien_phat_sinh']=0;
                 $arr_update_tran['so_tien_phi_cham_tra_phai_tra']=0; 
                 }
			     }
			     if($type_gh_cc=="GH")
				{
					
			    $arr_update_tran['code_contract_parent_gh']= $this->dataPost['code_contract_origin'];
			    $arr_update_tran['so_tien_phi_cham_tra_phai_tra']= (float)$so_tien_phi_cham_tra_phai_tra;
			    $arr_update_tran['so_tien_lai_phai_tra']= (float)$so_tien_lai_phai_tra;
			    $arr_update_tran['so_tien_phi_phai_tra']= (float)$so_tien_phi_phai_tra;
			    //trước 30/9 không tính phí phát sinh, chậm trả
			    if($transaction['date_pay'] <= strtotime('2021-09-30  23:59:59') && $transaction['type'] != 3)
	            {	
				 $arr_update_tran['so_ngay_phat_sinh']=0;
                 $arr_update_tran['so_tien_phat_sinh']=0;
                 $arr_update_tran['so_tien_phi_cham_tra_phai_tra']=0; 
                 }
			     }
			     $arr_update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $transaction['_id']),
					$arr_update_tran
				
				);
				
			}
		}
		//cập nhật trường debt contract
			$this->debt_recovery_one($this->dataPost['code_contract']);
			   $response = array(
					'status' => REST_Controller::HTTP_OK,
					'msg' => 'OK',
					'url' => '',
					'data'=>$phi_gia_han
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;


	}
	//tạo tiền thừa gia hạn lưu vào transaction_extend_model
	public function generate_money_gh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$date_pay=$this->dataPost['disbursement_date'];
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$this->dataPost['code_contract']));
		$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract_parent_gh' => $this->dataPost['code_contract_origin'], "status" => 1,'type'=>4,'type_payment'=>2,'tien_thua_thanh_toan_con_lai'=>array('$gt'=>0)));
		if (empty($data_transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		foreach ($data_transaction as $key => $value) {
			$code=isset($value['code']) ? $value['code'] : '';
			$data_transaction_extend = $this->transaction_extend_model->findOne(array('code_parent' => $code,'code_contract'=>$value['code_contract']));
			if(empty($data_transaction_extend))
			{
				$insert_extend=	array(
                        "code_contract" => $value['code_contract'],
                        "code_contract_disbursement" => $value['code_contract_disbursement'],
                        "code_contract_parent_gh" => $value['code_contract_parent_gh'],
                        "code_parent" =>$code,
                        "total"=>$value['tien_thua_thanh_toan_con_lai'],
						"so_tien_lai_da_tra" => 0,
						"so_tien_phi_da_tra" => 0,
						"so_tien_goc_da_tra" => 0,
						"tien_phi_phat_sinh_da_tra" => 0,
						"fee_delay_pay" => array(),
						"tien_thua_tat_toan" => 0,
						"so_ngay_phat_sinh" => 0,
						"so_tien_phat_sinh" => 0,
						"tien_thua_thanh_toan" => $value['tien_thua_thanh_toan_con_lai'],
						"tien_thua_thanh_toan_da_tra" => 0,
						"tien_thua_thanh_toan_con_lai" => $value['tien_thua_thanh_toan_con_lai'],
						"fee_finish_contract" => 0,
						"so_tien_phi_cham_tra_da_tra" => 0,
						"so_tien_phi_gia_han_da_tra" => 0,
						"created_at" => $this->createdAt
					);

				$this->transaction_extend_model->insert($insert_extend);
				$log = array(
						"type" => "transaction",
						"action" => 'generate_money_extend_insert',
						"data_post" => $this->dataPost,
						"transaction_code" => (string)$value['code'],
						"email" => $this->uemail,
						"created_at" => $this->createdAt
					);
					$this->log_transaction_model->insert($log);
			}
			
		}
		$response = array(
					'status' => REST_Controller::HTTP_OK,
					'msg' => 'OK',
				
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
      
	}
	//tạo tiền thiếu gia hạn 
	public function generate_money_thieu_gh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$date_pay=$this->dataPost['disbursement_date'];
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$this->dataPost['code_contract']));
		if (empty($contractDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		 $tranDT = $this->transaction_model->findOne(array('code_contract' => $this->dataPost['code_contract'], "status" => 1,'type'=>4,"type_payment"=>2));
		 $cond = ['code_contract' => $contractDB['code_contract'] ];
		if ( empty($tranDT)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$tong_tien_thua_thanh_toan = $this->transaction_model->sum_where(array('code_contract' => $contractDB['code_contract'],'type' => 4, 'status' => 1,'type_payment'=>1),'$tien_thua_thanh_toan');
		$so_tien_giam_tru=!empty($tranDT['total_deductible']) ? $tranDT['total_deductible'] : 0;
		$so_tien_da_tra=!empty($tranDT['total']) ? $tranDT['total'] : 0;
		$so_tien_lai_phai_tra=!empty($tranDT['so_tien_lai_phai_tra']) ? $tranDT['so_tien_lai_phai_tra'] : 0;
		$so_tien_phi_phai_tra=!empty($tranDT['so_tien_phi_phai_tra']) ? $tranDT['so_tien_phi_phai_tra'] : 0;
		$so_tien_phi_phat_sinh_phai_tra=!empty($tranDT['phi_phat_sinh']) ? $tranDT['phi_phat_sinh'] : 0;
		$phi_gia_han=isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
		$so_tien_phi_cham_tra_phai_tra=!empty($tranDT['so_tien_phi_cham_tra_phai_tra']) ? $tranDT['so_tien_phi_cham_tra_phai_tra'] : 0;
	    $so_tien_thieu=($so_tien_lai_phai_tra+$so_tien_phi_phai_tra+$so_tien_phi_phat_sinh_phai_tra+$so_tien_phi_cham_tra_phai_tra+$phi_gia_han)-$so_tien_da_tra-$tong_tien_thua_thanh_toan-$so_tien_giam_tru;
	    //trước 30/9
	    if($tranDT['date_pay'] <= strtotime('2021-09-30  23:59:59'))
	    {
           $so_tien_thieu=($so_tien_lai_phai_tra+$so_tien_phi_phai_tra+$phi_gia_han)-$so_tien_da_tra-$so_tien_giam_tru-$tong_tien_thua_thanh_toan;
	    }
	    if($so_tien_thieu < 0)
	    {
	    	$so_tien_thieu=0;
	    }
	    	
	    $transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $tranDT['_id']),
			[
				"so_tien_thieu"=>$so_tien_thieu,
				"so_tien_thieu_da_chuyen"=>0,
				"so_tien_thieu_con_lai"=>$so_tien_thieu
			]
			
		);
		$response = array(
					'status' => REST_Controller::HTTP_OK,
					'msg' => 'OK',
				
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
      
	}
	//chia tiền thừa gia hạn
	public function payment_tien_thua_gh_cc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã phiếu ghi không thể trống",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$last=(isset($this->dataPost['last'])) ? $this->dataPost['last'] : 0;
		$type_gh_cc=(isset($this->dataPost['type_gh_cc'])) ? $this->dataPost['type_gh_cc'] : '';
		$contractDB = $this->contract_model->findOne(array('code_contract' =>$this->dataPost['code_contract']));
		
        $data_transaction = $this->transaction_extend_model->find_where_pay_all(array('code_contract_parent_gh' => $this->dataPost['code_contract_origin'],'tien_thua_thanh_toan_con_lai'=>array('$gt'=>0)));
		if (empty($data_transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu: ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		 
		if (!empty($data_transaction))
			foreach ($data_transaction as $key => $value) {
				if($value['date_pay'] > strtotime('2021-09-30  23:59:59'))
	           {
				$transaction = $value;
                  $tien_thua=isset($transaction['total']) ? $transaction['total'] : 0;
				$date_pay = (isset($contractDB['disbursement_date'])) ? strtotime(date("Y-m-d", $contractDB['disbursement_date']) . '  23:59:59') : strtotime(date("Y-m-d", $transaction['created_at']) . '  23:59:59');
			    
				$phi_phat_tra_cham =0;
				$phi_phat_sinh = 0;
				$so_ngay_phat_sinh = 0;
				$so_tien_phat_sinh = 0;
				$phi_gia_han_da_tra = 0;
				$phi_gia_han = 0;
				$type_payment=22;
                
               
					$money_total = $tien_thua;
				//hoàn thành các kỳ tương lai
					$this->finishTempoPlan($contractDB['code_contract'], $money_total,$date_pay,(int)$transaction['total_deductible']);
					//chia bảng lãi kỳ
					$this->tinhtoanBangLaiKy($contractDB['code_contract'], $money_total, (string)$transaction['_id'], $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$transaction, (int)$transaction['total_deductible']);
					//$this->tinhtoanBangLaiThang($contractDB['code_contract'], $money_total, '');
					$arr_update_tran=array();
				$arr_update_tran['code_contract']=$contractDB['code_contract'];
				$arr_update_tran['code_contract_disbursement']=$contractDB['code_contract_disbursement'];
				//Update
				$transDB = $this->transaction_extend_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					$arr_update_tran
				
				);
				}
				}
			$this->debt_recovery_one($this->dataPost['code_contract']);
		
			$response = array(
					'status' => REST_Controller::HTTP_OK,
					'msg' => 'OK',
					'url' => '',
					'data'=>$phi_gia_han
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;


	}

	public function get_detail_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$hotline = $this->hotline_model->findOne(array('type' => 1)); // số tổng đài
		$orders = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$orders = $this->order_model->find_where(array('transaction_code' => $transaction['code']));
		}
		$data = array();
		if (!empty($orders)) {
			foreach ($orders as $or) {
				$or['id'] = (string)$or['_id'];
				if (strpos($or['service_code'], 'PINCODE_') !== false) {
					if (isset($or['detail']['items'])) {
						foreach ($or['detail']['items'] as $item) {
							$data_i = array(
								'service_code' => $or['service_code'],
								'service_name' => $or['service_name'],
								'publisher_name' => $or['publisher_name'],
								'cardCode' => isset($item['cardCode']) ? $item['cardCode'] : '',
								'cardSerial' => isset($item['cardSerial']) ? $item['cardSerial'] : '',
								'expiryDate' => isset($item['expiryDate']) ? $item['expiryDate'] : '',
								'cardValue' => isset($item['cardValue']) ? $item['cardValue'] : '',
								'money' => $or['money'],
								'amount' => $or['amount'],
								'created_at' => $or['created_at'],
								'updated_at' => $or['updated_at'],
							);
							$data[] = $data_i;
						}
					}
				} else if (strpos($or['service_code'], 'TOPUP_') !== false) {
					$data_i = array(
						'service_code' => $or['service_code'],
						'service_name' => $or['service_name'],
						'publisher_name' => $or['publisher_name'],
						'detail' => $or['detail'],
						'money' => $or['money'],
						'amount' => $or['amount'],
						'created_at' => $or['created_at'],
						'updated_at' => $or['updated_at'],
					);
					$data[] = $data_i;
				} else {
					if (!isset($data[$or['detail']['customer_code']])) {
						$data[$or['detail']['customer_code']] = $or;
					} else {
						$data[$or['detail']['customer_code']]['money'] = $data[$or['detail']['customer_code']]['money'] + $or['money'];
					}
				}
			}
		}
		
		$count_billing = "";
		$store_id = $transaction["store"]["id"];
		$store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($store_id)));
		$code_address_store = !empty($store_info['code_address_store']) ? $store_info['code_address_store'] : "";
		$first_day_of_month = date('01-m-Y');
		$first_day_of_month = strtotime($first_day_of_month);
		$time["start"] = $first_day_of_month;
		$time["end"] = time();
		$count_transaction = $this->transaction_model->countTransaction($time, $store_id);
//		$count_transaction = 10;
		if ($count_transaction == 0) {
			$count_billing = "0001";
		} elseif ($count_transaction > 0 && ($count_transaction) < 10) {
			$count_billing = "000" . ($count_transaction);
		} elseif ($count_transaction >= 10 && $count_transaction < 100) {
			$count_billing = "00" . ($count_transaction);
		} elseif ($count_transaction >= 100 && $count_transaction < 1000) {
			$count_billing = "0" . ($count_transaction);
		} elseif ($count_transaction >= 1000 && $count_transaction < 10000) {
			$count_billing = $count_transaction;
		} 
		$mydate = getdate(date("U"));
		$year = substr($mydate['year'], -2);
		if (intval($mydate['mon']) < 10) {
			$mydate['mon'] = '0' . $mydate['mon'];
		}
		$code_billing = $code_address_store . $mydate['mon'] . $year . "-" . $count_billing;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'orderData' => $data,
			'transaction' => $transaction,
			'code_billing' => $code_billing,
			'hotline' => $hotline['phone'],
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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

	public function tinhtoanBangLaiKy($codeContract, $amount, $transId, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$tranData,$amount_giam_tru)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi kỳ
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		if($amount>0)
		{
		if($type_payment==1 )
		{
		$this->bangLaiKy_tinhtoan_tien_datra_conlai($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		$this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount_giam_tru, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
	    }
	  
	    if($type_payment==2 )
		{
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_gh($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount_giam_tru, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		}
		 if($type_payment==22)
		{
        $this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_thua($temps, $amount, $transId, $codeContract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment);
		}
	}
	}

	//chia thanh toán
	// 	Dư nợ giảm dần:  gốc ->lãi -> phí -> phí chậm trả  ->phí quá hạn 
	// Gốc cuối kỳ:  lãi -> phí -> phí chậm trả  ->phí quá hạn 
	// Dư nợ giảm dần:  lãi -> phí ->gốc -> phí chậm trả  ->phí quá hạn 
	// Gốc cuối kỳ:  lãi -> phí -> phí chậm trả  ->phí quá hạn 
	//$date_pay_tt ngày tất toán
	//$type_payment : 1 thanh toán,2 gia hạn , 3 cơ cấu
	//$date_pay ngày thanh toán phiếu thu 
	//$amountRemain số tiền khách đóng (có thể thay đổi khi chia)

    private function bangLaiKy_tinhtoan_tien_datra_conlai($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment,$date_pay_tt=0)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		//kiểu trả lãi : lãi hàng tháng gốc cuối kỳ/ dư nợ giảm dần
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		//lặp bảng lãi kỳ để chia tiền vào
		foreach ($temps as $key=>$temp) {
			if($date_pay_tt>0 && $temp['ngay_ky_tra']>$date_pay_tt)
			{
				break;
			}

			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}
			

			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			//kỳ đã thanh toán có chậm trả thì lưu vào
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				//phí phạt chậm trả
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}
            //Trước 1/5 Số tiền đóng < số tiền kỳ còn lại và ngày kỳ trả > ngày thanh toán mới phân bổ 
			if (strtotime(date('Y-m-d',$date_pay). ' 00:00:00') < strtotime('2021-05-01 00:00:00') && $amountRemain > 0) {
  
					if($amountRemain < ($goc_con_lai_ky_hien_tai + $lai_con_lai_ky_hien_tai+ $phi_con_lai_ky_hien_tai ) && $amountRemain !=$amount && $temp['status']==1 && strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') )
					{
						//phân bổ chậm trả
                      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
					}
			}else{
	        //Sau 1/5/2021: Ngày kỳ trả > ngày thanh toán phân bổ chậm trả
			    if ( strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') && $amountRemain > 0 ) {
				      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
			   }
			     
	     	}

           //trong config ngày 24/11/2020
			$date_ngay_t = strtotime($this->config->item("date_t_apply"));
			if ($contractDB['disbursement_date'] > $date_ngay_t || $type_interest==2 ) {


				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_goc_da_tra" => $so_goc_da_tra_transaction
						)
					);
				}
			
			


			} else {
				if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_goc_da_tra" => $so_goc_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền phí đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}


			}
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$phi_con_lai = (!empty($temp_1ky['tien_phi_1ky_con_lai'])) ? $temp_1ky['tien_phi_1ky_con_lai'] : 0;
			$goc_con_lai = (!empty($temp_1ky['tien_goc_1ky_con_lai'])) ? $temp_1ky['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai = (!empty($temp_1ky['tien_lai_1ky_con_lai'])) ? $temp_1ky['tien_lai_1ky_con_lai'] : 0;

			//kiểm tra lại số tiền còn lại để cập nhật lại trạng thái và số tiền đã thanh toán
			$tong_lai_phi_goc_con=$phi_con_lai+$goc_con_lai+$lai_con_lai;
			if($tong_lai_phi_goc_con <= $this->get_amout_limit_debt($date_pay,$temp_1ky['round_tien_tra_1_ky']))
			{
               $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>2   ) );
			}else{
                $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>1   )
			    );
			}
			


		} //kết thúc lặp
        //còn thừa đâu lại bổ vào chậm trả
		if ($amountRemain > 0 && $date_pay_tt==0) {
			$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
		}
		if ($amountRemain > 0 ) {
			$update = array();

			$update['tien_thua_thanh_toan'] = $amountRemain;
             $update['tien_thua_thanh_toan_da_tra'] = 0;
             $update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		}

	}

	//chia gia hạn
	// 	Dư nợ giảm dần:  lãi -> phí -> phí gia hạn -> phí chậm trả  ->phí quá hạn 
	//$type_payment : 1 thanh toán,2 gia hạn , 3 cơ cấu
	//$date_pay ngày thanh toán phiếu thu 
	//$amountRemain số tiền khách đóng (có thể thay đổi khi chia)
	private function bangLaiKy_tinhtoan_tien_datra_conlai_gh($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		//kiểu trả lãi : lãi hàng tháng gốc cuối kỳ/ dư nợ giảm dần
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		//lặp bảng lãi kỳ để chia gốc lãi phí , phí chậm trả
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			// lớn hơn 30/9 mới chia chậm trả
			 if($date_pay > strtotime('2021-09-30  23:59:59'))
	          {
			if ( $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}
		}

				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				 if($type_payment==2 && $amountRemain > 0 && $key == (count($temps)-1))
                {
                	$pghg=isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
				//phi gia han
				if ($amountRemain > 0 && $phi_gia_han >0 && $so_phi_gia_han_da_tra_transaction < $pghg ) {
                    if ($amountRemain >= $phi_gia_han) {
                      $so_phi_gia_han_da_tra_transaction=$phi_gia_han;
                      $amountRemain=$amountRemain-$phi_gia_han;
                    }else{
                      $so_phi_gia_han_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    $this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_gia_han_da_tra" => $so_phi_gia_han_da_tra_transaction
						)
					  );
				}
				
		        //phi cham tra type_payment >1
				if( $amountRemain > 0)
				{
				   if($date_pay > strtotime('2021-09-30  23:59:59'))
	               {
				$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
			       }
				}
				 //phi phát sinh type_payment>1
				if( $amountRemain > 0 )
				{
					  if($date_pay > strtotime('2021-09-30  23:59:59'))
	               {
					if ($amountRemain >= $phi_phat_sinh) {
                      $so_phi_phat_sinh_da_tra_transaction=$phi_phat_sinh;
                      $amountRemain=$amountRemain-$phi_phat_sinh;
                    }else{
                      $so_phi_phat_sinh_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    $this->transaction_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"phi_phat_sinh" => $phi_phat_sinh,
							"tien_phi_phat_sinh_da_tra" => $so_phi_phat_sinh_da_tra_transaction,
							"tien_phi_phat_sinh_con_lai" =>$phi_phat_sinh- $so_phi_phat_sinh_da_tra_transaction
						)
					  );
                   }
				}
			  }
			
			
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra)
			);


		}
         //còn lại phân bổ phí phạt
		if ($amountRemain > 0) {
			if($date_pay > strtotime('2021-09-30  23:59:59'))
	           {
			$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay);
		        }
		}
		if ($amountRemain > 0 ) {
			$update = array();

			$update['tien_thua_thanh_toan'] = $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = 0;
            $update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		}

	}

	//chia tiền thừa
	private function bangLaiKy_tinhtoan_tien_datra_conlai_tien_thua($temps, $amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		if ($amountRemain == 0) $amountRemain = $amount;
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}

             //trước 1/5
			if (strtotime(date('Y-m-d',$date_pay). ' 00:00:00') < strtotime('2021-05-01 00:00:00') && $amountRemain > 0) {
  
					if($amountRemain < ($goc_con_lai_ky_hien_tai + $lai_con_lai_ky_hien_tai+ $phi_con_lai_ky_hien_tai ) && $amountRemain !=$amount && $temp['status']==1 && strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') )
					{
                      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
					}
			}else{
	 
			    if ( strtotime(date('Y-m-d',$date_pay). ' 00:00:00') <  strtotime(date('Y-m-d',$temp['ngay_ky_tra']). ' 00:00:00') && $amountRemain > 0 ) {
				      $amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay,$type_payment);
			   }
			     
	     	}

				if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_extend_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
						)
					);
				}
				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
					$this->transaction_extend_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($transId)),
						array(
							"temporary_plan_contract_id" => $temp['_id'],
							"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
						)
					);
				}
				

			 
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra)
			);


		}

		
		if ($amountRemain > 0 ) {
			$update = array();
			$update['tien_thua_thanh_toan_con_lai'] = $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = $amount- $amountRemain;
             $update["date_pay"] =$contractDB['disbursement_date'];
			$this->transaction_extend_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
			$tranDB = $this->transaction_extend_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
			$tranDB_origin = $this->transaction_model->findOne(array('code' =>$tranDB['code_parent']));
			$update_tran_origin = array();
			
			$update_tran_origin['tien_thua_thanh_toan_da_tra'] =$tranDB_origin['tien_thua_thanh_toan_da_tra']+($amount- $amountRemain);
            $tien_thua_thanh_toan_con_lai=$tranDB_origin['tien_thua_thanh_toan']-$update_tran_origin['tien_thua_thanh_toan_da_tra']-($amount- $amountRemain);
            if($tien_thua_thanh_toan_con_lai<0)
            	$tien_thua_thanh_toan_con_lai=0;
			$update_tran_origin['tien_thua_thanh_toan_con_lai'] =$tien_thua_thanh_toan_con_lai ;
			
			$this->transaction_model->update(
				array("_id" => $tranDB_origin['_id']),
				$update_tran_origin
			);
		}else if($amountRemain == 0 ) {
			$update = array();
			$update['tien_thua_thanh_toan_con_lai'] = 0;
			$update['tien_thua_thanh_toan_da_tra'] = $amount;
            $update["date_pay"] =$contractDB['disbursement_date'];
			$this->transaction_extend_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
			$tranDB = $this->transaction_extend_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
			$tranDB_origin = $this->transaction_model->findOne(array('code' =>$tranDB['code_parent']));
			$update_tran_origin = array();
			
			$update_tran_origin['tien_thua_thanh_toan_da_tra'] =$tranDB_origin['tien_thua_thanh_toan_da_tra']+$amount;
			$update_tran_origin['tien_thua_thanh_toan_con_lai'] = 0;
			 
			$this->transaction_model->update(
				array("_id" => $tranDB_origin['_id']),
				$update_tran_origin
			);
		}

	}

	//chia giảm trừ
	//Gia hạn->trả trước hạn->quá hạn-> trả chậm-> phí ->Lãi->gốc
    //$type_payment : 1 thanh toán,2 gia hạn , 3 cơ cấu
	//$date_pay ngày thanh toán phiếu thu 
	//$amountRemain số tiền khách đóng (có thể thay đổi khi chia)
	private function bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($amount, $transId, $code_contract, $phi_phat_tra_cham, $phi_phat_sinh, $date_pay,$phi_gia_han,$type_payment)
	{
		$amountRemain = 0;
		$contractDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		if(empty($contractDB))
			return;
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$type_interest = !empty($contractDB['loan_infor']['type_interest']) ? $contractDB['loan_infor']['type_interest'] : "1";
		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;
		$so_phi_gia_han_da_tra_transaction = 0;
		$so_phi_cham_tra_da_tra_transaction = 0;
		$so_phi_phat_sinh_da_tra_transaction = 0;
		$so_phi_phat_cham_tra_da_tra_transaction = 0;
		$tien_phi_tat_toan_da_tra_transaction = 0;
		$tranData=$this->transaction_model->findOne(['_id'=>new MongoDB\BSON\ObjectId($transId)]);
        if(empty($tranData))
			return;
		 if($type_payment==2 )
		{
		$phi_gia_han =!empty($tranData['so_tien_phi_gia_han_con_lai']) ? $tranData['so_tien_phi_gia_han_con_lai'] : 0;
	    }

			$amountRemain = !empty($tranData['total_deductible']) ? (int)$tranData['total_deductible'] : 0;
		if ($amountRemain == 0)
			return ;

			if($type_payment==2)
				{
               //phi gia han
				if ($amountRemain > 0 && $phi_gia_han >0 && $so_phi_gia_han_da_tra_transaction < 200000 ) {
                    if ($amountRemain >= $phi_gia_han) {
                      $so_phi_gia_han_da_tra_transaction=$phi_gia_han;
                      $amountRemain=$amountRemain-$phi_gia_han;
                    }else{
                      $so_phi_gia_han_da_tra_transaction=$amountRemain;
                      $amountRemain=0;
                    }
                    
				}
			   }
				if($tranData['type']==3 || $type_payment>1)
				{
				if($tranData['type']==3)
				{
			        $tong_phi_tat_toan = !empty($tranData['so_tien_phi_tat_toan_phai_tra_tat_toan']) ? $tranData['so_tien_phi_tat_toan_phai_tra_tat_toan'] : 0;
					$phi_tat_toan_da_tra = !empty($tranData['fee_finish_contract']) ? $tranData['fee_finish_contract'] : 0;
					$phi_tat_toan_phai_tra = $tong_phi_tat_toan - $phi_tat_toan_da_tra;
					
					if ($amountRemain > 0  && $phi_tat_toan_phai_tra > 0) {
						if ($amountRemain >= $phi_tat_toan_phai_tra) {
							$tien_phi_tat_toan_da_tra_transaction = $phi_tat_toan_phai_tra;
							$amountRemain = $amountRemain - $phi_tat_toan_phai_tra;
						} else {
							$tien_phi_tat_toan_da_tra_transaction = $amountRemain;
							$amountRemain = 0;
						}
					

					}
				}


               //phi phát sinh type_payment>1
				if( $amountRemain > 0 )
				{
					
                    $tong_phi_phat_sinh = $this->transaction_model->get_tong_phi_phat_sinh($code_contract);
					$phi_phat_sinh_da_tra = $this->transaction_model->get_phi_phat_sinh_da_tra($code_contract);
					$phi_phat_sinh_phai_tra = $tong_phi_phat_sinh - $phi_phat_sinh_da_tra;
					$tien_phi_phat_sinh_da_tra = 0;
					if ($amountRemain > 0 && $phi_phat_sinh_phai_tra > 0) {
						if ($amountRemain >= $phi_phat_sinh_phai_tra) {
							$so_phi_phat_sinh_da_tra_transaction = $phi_phat_sinh_phai_tra;
							$amountRemain = $amountRemain - $phi_phat_sinh_phai_tra;
						} else {
							$so_phi_phat_sinh_da_tra_transaction = $amountRemain;
							$amountRemain = 0;
						}
						

					}
				}
			   }
			   $so_tien_phi_cham_tra_da_tra_transaction=0;
		        //phi cham tra type_payment >1
				if( $amountRemain > 0)
				{
				$truoc_phan_bo_cham_tra =$amountRemain;
				$amountRemain = $this->phan_bo_phi_phat($temps, $code_contract, $transId,"GT", $amountRemain, 0, $date_pay);
				 $so_tien_phi_cham_tra_da_tra_transaction=$truoc_phan_bo_cham_tra -$amountRemain;
				}
		foreach ($temps as $key=>$temp) {
			$dataUpdate = array();
			if ($amountRemain == 0 && $temp['status'] == 1) {
				$dataUpdate['da_thanh_toan'] = 0;
				if (!empty($dataUpdate)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate
					);
					break;
				}
			}


			//Tiền đã đóng kỳ hiện tại
			$goc_da_dong_ky_hien_tai = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;


			//Tiền còn lại phải đóng kỳ hiện tại
			$goc_con_lai_ky_hien_tai = !empty($temp['tien_goc_1ky_con_lai']) ? $temp['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;
			//if($amountRemain <= 0) break;
			$pp_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_tien'] : 0;
			$sn_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$temp['ky_tra']]['so_ngay'] : 0;
			$pp_cham_tra_con_lai_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_con_lai']) ? $temp['tien_phi_cham_tra_1ky_con_lai'] : 0;

			$pp_cham_tra_da_dong_ky_hien_tai = !empty($temp['tien_phi_cham_tra_1ky_da_tra']) ? $temp['tien_phi_cham_tra_1ky_da_tra'] : 0;
			if ($temp['status'] == 2 && $pp_tra_cham_ky_hien_tai > 0 && $pp_cham_tra_con_lai_ky_hien_tai == 0 && $pp_cham_tra_da_dong_ky_hien_tai == 0) {

				$dataUpdate_p = array();
				$dataUpdate_p['fee_delay_pay'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['tien_phi_cham_tra_1ky_con_lai'] = $pp_tra_cham_ky_hien_tai;
				$dataUpdate_p['so_ngay_cham_tra'] = $sn_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_p)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $temp['_id']),
						$dataUpdate_p
					);
				}
			}


				if ($amountRemain > 0) {
					//Phí
					if ($phi_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
						} else {
							//Update $phi_da_dong_ky_hien_tai
							$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

							$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

							//Update $phi_con_lai_ky_hien_tai
							$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
					
				}
					if ($amountRemain > 0) {
					//Lãi
					if ($lai_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
						} else {
							//Update $lai_da_dong_ky_hien_tai
							$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

							$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

							//Update $lai_con_lai_ky_hien_tai
							$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
							//Remain
							$amountRemain = 0;
						}
					}
					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}

					
				}
                if ($amountRemain > 0 && $type_interest==1) {
					//Gốc

					if ($goc_con_lai_ky_hien_tai > 0) {
						if ($amountRemain >= $goc_con_lai_ky_hien_tai) {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $goc_con_lai_ky_hien_tai;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $goc_con_lai_ky_hien_tai;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = 0;
							//Remain
							$amountRemain = $amountRemain - $goc_con_lai_ky_hien_tai;
						} else {
							//Update $goc_da_dong_ky_hien_tai
							$dataUpdate['tien_goc_1ky_da_tra'] = $goc_da_dong_ky_hien_tai + $amountRemain;

							$so_goc_da_tra_transaction = $so_goc_da_tra_transaction + $amountRemain;

							//Update $goc_con_lai_ky_hien_tai
							$dataUpdate['tien_goc_1ky_con_lai'] = $goc_con_lai_ky_hien_tai - $amountRemain;
							$amountRemain = 0;
						}
					}

					//Update DB
					if (!empty($dataUpdate)) {
						$this->temporary_plan_contract_model->update(
							array("_id" => $temp['_id']),
							$dataUpdate
						);
					}
				
				}
			 
			$temp_1ky = $this->temporary_plan_contract_model->findOne(array("_id" => $temp['_id']));
			$phi_da_tra = (!empty($temp_1ky['tien_phi_1ky_da_tra'])) ? $temp_1ky['tien_phi_1ky_da_tra'] : 0;
			$goc_da_tra = (!empty($temp_1ky['tien_goc_1ky_da_tra'])) ? $temp_1ky['tien_goc_1ky_da_tra'] : 0;
			$lai_da_tra = (!empty($temp_1ky['tien_lai_1ky_da_tra'])) ? $temp_1ky['tien_lai_1ky_da_tra'] : 0;
			$phi_con_lai = (!empty($temp_1ky['tien_phi_1ky_con_lai'])) ? $temp_1ky['tien_phi_1ky_con_lai'] : 0;
			$goc_con_lai = (!empty($temp_1ky['tien_goc_1ky_con_lai'])) ? $temp_1ky['tien_goc_1ky_con_lai'] : 0;
			$lai_con_lai = (!empty($temp_1ky['tien_lai_1ky_con_lai'])) ? $temp_1ky['tien_lai_1ky_con_lai'] : 0;
			
			$tong_lai_phi_goc_con=$phi_con_lai+$goc_con_lai+$lai_con_lai;
			if($tong_lai_phi_goc_con <= ($this->get_amout_limit_debt($date_pay,$temp_1ky['round_tien_tra_1_ky'])+(int)$tranData['total_deductible']) )
			{
               $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>2   ) );
			}else{
                $this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				array('da_thanh_toan' => $phi_da_tra + $goc_da_tra + $lai_da_tra,
			          'status' =>1   )
			    );
			}


		}
        $tien_thua_mien_giam=0;
		
		if ($amountRemain > 0 ) {
			$tien_thua_mien_giam=$amountRemain ;
			  $tien_thua_thanh_toan = !empty($tranData['tien_thua_thanh_toan']) ? $tranData['tien_thua_thanh_toan'] : 0;
			$update = array();

			$update['tien_thua_thanh_toan'] =$tien_thua_thanh_toan+ $amountRemain;
			$update['tien_thua_thanh_toan_da_tra'] = 0;
            $update['tien_thua_thanh_toan_con_lai'] = $tien_thua_thanh_toan+ $amountRemain;
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				$update
			);
		
		}
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				'chia_mien_giam'=>[
				"so_tien_phi_gia_han_da_tra" => $so_phi_gia_han_da_tra_transaction,
				"so_tien_phi_tat_toan_da_tra" =>$tien_phi_tat_toan_da_tra_transaction,
				"so_tien_phi_phat_sinh_da_tra" =>$so_phi_phat_sinh_da_tra_transaction,
				"so_tien_phi_cham_tra_da_tra"=>$so_tien_phi_cham_tra_da_tra_transaction,
				"so_tien_phi_da_tra"=>$so_phi_da_tra_transaction,
				"so_tien_lai_da_tra"=>$so_lai_da_tra_transaction,
				"so_tien_goc_da_tra"=>$so_goc_da_tra_transaction,
				"tien_thua_mien_giam"=>$tien_thua_mien_giam
			    ]
			)
		);


	}
	public function tinhtoanBangLaiKy_giahan($codeContract, $amount, $transId)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi kỳ
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		$this->bangLaiKy_tinhtoan_tien_datra_conlai_giahan($temps, $amount, $transId);
		//$this->bangLaiKy_tinhtoan_duno_thangtruoc($codeContract, $amount);
	}

	public function tinhtoanBangLaiThang($codeContract, $amount,$id_transaction)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		$tranDB = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($id_transaction)));
		$this->tinhtoan_tien_datra_conlai($temps, $amount,$tranDB);
		$this->tinhtoan_duno_thangtruoc($codeContract, $amount,$tranDB);
	}

	public function tinhtoanBangLaiThang_giahan($codeContract, $amount)
	{
		$amount = (float)$amount;
		//Tìm các bản ghi lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $codeContract
		));
		$this->tinhtoan_tien_datra_conlai_giahan($temps, $amount);
		//$this->tinhtoan_duno_thangtruoc_giahan($codeContract, $amount);
	}

	public function tinhtoanBangLaiThang_post()
	{
		// $this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']); // tong tien
		// $this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']); // tong tien
		// $this->dataPost['amount'] = (float)$this->dataPost['amount'];

		//Tìm các bản ghi lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $this->dataPost['code_contract']
		));
		var_dump($temps);
		//$this->tinhtoan_tien_datra_conlai($temps, $this->dataPost['amount']);
		//$this->tinhtoan_duno_thangtruoc($this->dataPost['code_contract'], $this->dataPost['amount']);
	}

	private function tinhtoan_tien_datra_conlai_giahan($temps, $amount)
	{
		$amountRemain = 0;

		foreach ($temps as $temp) {
			if ($amountRemain == 0) $amountRemain = $amount;
            
			//Tiền phải đóng tháng hiện tại
			$lai_phai_dong_thang_hien_tai = !empty($temp['tien_lai_1thang']) ? $temp['tien_lai_1thang'] : 0;
			$phi_phai_dong_thang_hien_tai = !empty($temp['tien_phi_1thang']) ? $temp['tien_phi_1thang'] : 0;

			//Tiền đã đóng tháng hiện tại
			$lai_da_dong_thang_hien_tai = !empty($temp['tien_lai_1thang_da_tra']) ? $temp['tien_lai_1thang_da_tra'] : 0;
			$phi_da_dong_thang_hien_tai = !empty($temp['tien_phi_1thang_da_tra']) ? $temp['tien_phi_1thang_da_tra'] : 0;

			//Tiền còn lại phải đóng tháng hiện tại
			$lai_con_lai_thang_hien_tai = !empty($temp['tien_lai_1thang_con_lai']) ? $temp['tien_lai_1thang_con_lai'] : 0;
			$phi_con_lai_thang_hien_tai = !empty($temp['tien_phi_1thang_con_lai']) ? $temp['tien_phi_1thang_con_lai'] : 0;

			$dataUpdate = array();
			if ($amountRemain <= 0) continue;
			//Lãi
			if ($lai_con_lai_thang_hien_tai > 0) {
				if ($amountRemain >= $lai_con_lai_thang_hien_tai) {
					//Update $lai_da_dong_thang_hien_tai
					$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $lai_con_lai_thang_hien_tai;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_lai_1thang_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_thang_hien_tai;
				} else {
					//Update $lai_da_dong_thang_hien_tai
					$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $amountRemain;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_lai_1thang_con_lai'] = $lai_con_lai_thang_hien_tai - $amountRemain;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_thang_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
			if ($amountRemain <= 0) continue;
			//Phí
			if ($phi_con_lai_thang_hien_tai > 0) {
				if ($amountRemain >= $phi_con_lai_thang_hien_tai) {
					$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai + $phi_con_lai_thang_hien_tai;
					//Update $lai_con_lai_thang_hien_tai
					$dataUpdate['tien_phi_1thang_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_con_lai_thang_hien_tai;
				} else {
					//Update $phi_da_dong_thang_hien_tai
					$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai + $amountRemain;
					//Update $phi_con_lai_thang_hien_tai
					$dataUpdate['tien_phi_1thang_con_lai'] = $phi_con_lai_thang_hien_tai - $amountRemain;
					$amountRemain = $amountRemain - $phi_con_lai_thang_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
		}
	}

	private function tinhtoan_tien_datra_conlai($temps, $amount,$tranDB)
	{
		$amountRemain = 0;
        $ck=0;
		foreach ($temps as $key =>$temp) {
			$dataUpdate = array();
	
			

			//Tiền đã đóng tháng hiện tại
			$goc_da_dong_thang_hien_tai = !empty($temp['tien_goc_1thang_da_tra']) ? $temp['tien_goc_1thang_da_tra'] : 0;
			$lai_da_dong_thang_hien_tai = !empty($temp['tien_lai_1thang_da_tra']) ? $temp['tien_lai_1thang_da_tra'] : 0;
			$phi_da_dong_thang_hien_tai = !empty($temp['tien_phi_1thang_da_tra']) ? $temp['tien_phi_1thang_da_tra'] : 0;

			$phi_phat_sinh_1thang_da_tra = !empty($temp['phi_phat_sinh_1thang_da_tra']) ? $temp['tien_goc_1thang_da_tra'] : 0;
			$phi_tat_toan_1thang_da_tra = !empty($temp['phi_tat_toan_1thang_da_tra']) ? $temp['phi_tat_toan_1thang_da_tra'] : 0;
			$phi_gia_han_1thang_da_tra = !empty($temp['phi_gia_han_1thang_da_tra']) ? $temp['phi_gia_han_1thang_da_tra'] : 0;
			$phi_cham_tra_1thang_da_tra = !empty($temp['phi_cham_tra_1thang_da_tra']) ? $temp['phi_cham_tra_1thang_da_tra'] : 0;

			
            $ck=0;
			$dataUpdate = array();
           if( $temp['time'] ==(string)date('m/Y',$tranDB['date_pay']) || (strtotime(date($temp['year'].'-'.$temp['month'].'-t').' 23:59:59') < $tranDB['date_pay'] && $key==(count($temps)-1) ))
            {
			$ck=1;
			if($ck==1)
			{
				$ck++;
			$dataUpdate['tien_goc_1thang_da_tra'] = $goc_da_dong_thang_hien_tai +$tranDB['so_tien_goc_da_tra'];
			$dataUpdate['tien_lai_1thang_da_tra'] = $lai_da_dong_thang_hien_tai + $tranDB['so_tien_lai_da_tra'];	
			$dataUpdate['tien_phi_1thang_da_tra'] = $phi_da_dong_thang_hien_tai +$tranDB['so_tien_phi_da_tra'];	

			$dataUpdate['phi_phat_sinh_1thang_da_tra'] = $phi_phat_sinh_1thang_da_tra +$tranDB['tien_phi_phat_sinh_da_tra'];	
			$dataUpdate['phi_tat_toan_1thang_da_tra'] = $phi_tat_toan_1thang_da_tra +$tranDB['fee_finish_contract'];	
			$dataUpdate['phi_gia_han_1thang_da_tra'] = $phi_gia_han_1thang_da_tra +$tranDB['so_tien_phi_gia_han_da_tra'];	
			$dataUpdate['phi_cham_tra_1thang_da_tra'] = $phi_cham_tra_1thang_da_tra +$tranDB['so_tien_phi_cham_tra_da_tra'];
			$dataUpdate['tien_thua_tat_toan'] = $tranDB['tien_thua_tat_toan'];	
			//Update DB
			if (!empty($dataUpdate)) {
				$this->tempo_contract_accounting_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
		  }
		  }
			
		
		  }
			
		  
		}
	

	private function tinhtoan_duno_thangtruoc($code_contract, $amount)
	{
		//Step 1: tìm lại các bảng lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$du_no_goc_thang_truoc_da_tra = 0;
		$du_no_lai_thang_truoc_da_tra = 0;
		$du_no_phi_thang_truoc_da_tra = 0;
        
		foreach ($temps as $key=>$temp) {
			$ck=0;
			if( $temp['time'] ==(string)date('m/Y',$tranDB['date_pay']) || (strtotime(date($temp['year'].'-'.$temp['month'].'-t').' 23:59:59') < $tranDB['date_pay'] && $key==(count($temps)-1) ))
            {
              $ck=$key+1;
            }
            if($ck==($key+1) )
            {
			   
			    $du_no_goc_thang_truoc_da_tra =  $temp['tien_goc_1thang_da_tra'];

				$dataUpdate['du_no_goc_thang_truoc_da_tra'] = $du_no_goc_thang_truoc_da_tra;
			
		       $du_no_lai_thang_truoc_da_tra =  $temp['tien_lai_1thang_da_tra'];
				$dataUpdate['du_no_lai_thang_truoc_da_tra'] = $du_no_lai_thang_truoc_da_tra;
			
			
               $du_no_phi_thang_truoc_da_tra =  $temp['tien_phi_1thang_da_tra'];
			
				$dataUpdate['du_no_phi_thang_truoc_da_tra'] = $du_no_phi_thang_truoc_da_tra;
			
			

			//Update DB
			if (empty($dataUpdate)) continue;
			$this->tempo_contract_accounting_model->update(
				array("_id" => $temp['_id']),
				$dataUpdate
			);
          }
		}
	}

	private function tinhtoan_duno_thangtruoc_giahan($code_contract, $amount)
	{
		//Step 1: tìm lại các bảng lãi tháng
		$temps = $this->tempo_contract_accounting_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$du_no_lai_thang_truoc_da_tra = 0;
		$du_no_phi_thang_truoc_da_tra = 0;

		foreach ($temps as $temp) {
			if ($temp['du_no_lai_thang_truoc'] > 0 && $du_no_lai_thang_truoc_da_tra > 0) {
				$dataUpdate['du_no_lai_thang_truoc'] = $temp['du_no_lai_thang_truoc'] + $temp['du_no_lai_thang_truoc_da_tra'] - $du_no_lai_thang_truoc_da_tra;
				$dataUpdate['du_no_lai_thang_truoc_da_tra'] = $du_no_lai_thang_truoc_da_tra;
			}
			$du_no_lai_thang_truoc_da_tra = $du_no_lai_thang_truoc_da_tra + $temp['tien_lai_1thang_da_tra'];

			if ($temp['du_no_phi_thang_truoc'] > 0 && $du_no_phi_thang_truoc_da_tra > 0) {
				$dataUpdate['du_no_phi_thang_truoc'] = $temp['du_no_phi_thang_truoc'] + $temp['du_no_phi_thang_truoc_da_tra'] - $du_no_phi_thang_truoc_da_tra;
				$dataUpdate['du_no_phi_thang_truoc_da_tra'] = $du_no_phi_thang_truoc_da_tra;
			}
			$du_no_phi_thang_truoc_da_tra = $du_no_phi_thang_truoc_da_tra + $temp['tien_phi_1thang_da_tra'];

			//Update DB
			if (empty($dataUpdate)) continue;
			$this->tempo_contract_accounting_model->update(
				array("_id" => $temp['_id']),
				$dataUpdate
			);

		}
	}

	public function tinhtoanBangLaiKy_post()
	{
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']); // tong tien
		$this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']); // tong tien
		$this->dataPost['amount'] = (float)$this->dataPost['amount'];

		//Tìm các bản ghi lãi tháng
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $this->dataPost['code_contract']
		));
		$this->bangLaiKy_tinhtoan_tien_datra_conlai($temps, $this->dataPost['amount']);
		//$this->bangLaiKy_tinhtoan_duno_thangtruoc($this->dataPost['code_contract'], $this->dataPost['amount']);
	}

	
	
    //phân bổ phí chậm trả
	private function phan_bo_phi_phat($temps, $code_contract, $transId, $phi_phat_tra_cham, $amountRemain, $phi_phat_sinh, $date_pay)
	{
		$temps_cham_tra = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));
		$ck_gtpp="";
        //phí chậm trả giảm trừ
		if($phi_phat_tra_cham=="GT")
		{
		
			$tranData=$this->transaction_model->findOne(['_id'=>new MongoDB\BSON\ObjectId($transId)]);	
			$so_tien_phi_cham_tra_da_tra=(isset($tranData['so_tien_phi_cham_tra_da_tra'])) ? $tranData['so_tien_phi_cham_tra_da_tra'] : 0;
			$phi_phat_tra_cham=0;
			$ck_gtpp="GT";
		}
		foreach ($temps_cham_tra as $chamtra) {
			$dataUpdate_pp = array();
			$dataUpdate_pp_t = array();
			$tinh_phi_phat_sinh = false;
			$so_ky_da_tra = 0;
			foreach ($temps as $glp) {
				if ($glp['ky_tra'] == $chamtra['ky_tra']) {
					//Tiền còn lại phải đóng kỳ hiện tại

					$goc_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_goc_1ky_con_lai']) ? $glp['tien_goc_1ky_con_lai'] : 0;
					$lai_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_lai_1ky_con_lai']) ? $glp['tien_lai_1ky_con_lai'] : 0;
					$phi_con_lai_ky_hien_tai_chamtra = !empty($glp['tien_phi_1ky_con_lai']) ? $glp['tien_phi_1ky_con_lai'] : 0;
				}
				if ($glp['status'] == 2)
					$so_ky_da_tra++;

			}
			if (count($temps) == $so_ky_da_tra) $tinh_phi_phat_sinh = true;

			$phi_phat_cham_tra_con_lai_ky_hien_tai = !empty($chamtra['tien_phi_cham_tra_1ky_con_lai']) ? $chamtra['tien_phi_cham_tra_1ky_con_lai'] : 0;
			$fee_delay_pay = !empty($chamtra['fee_delay_pay']) ? $chamtra['fee_delay_pay'] : 0;
			$phi_phat_cham_tra_da_dong_ky_hien_tai = !empty($chamtra['tien_phi_cham_tra_1ky_da_tra']) ? $chamtra['tien_phi_cham_tra_1ky_da_tra'] : 0;
			$phi_phat_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$chamtra['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$chamtra['ky_tra']]['so_tien'] : 0;
			$so_ngay_tra_cham_ky_hien_tai = (isset($phi_phat_tra_cham[$chamtra['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$chamtra['ky_tra']]['so_ngay'] : 0;

			if ($phi_phat_tra_cham_ky_hien_tai == 0)
				$phi_phat_tra_cham_ky_hien_tai = $phi_phat_cham_tra_con_lai_ky_hien_tai;
            //kỳ đã thanh toán chuyển 
			if ($chamtra['status'] == 2 && $phi_phat_tra_cham_ky_hien_tai > 0 && $phi_phat_cham_tra_con_lai_ky_hien_tai == 0 && $phi_phat_cham_tra_da_dong_ky_hien_tai == 0) {
				$dataUpdate_pp_t['fee_delay_pay'] = $phi_phat_tra_cham_ky_hien_tai;
				$dataUpdate_pp_t['tien_phi_cham_tra_1ky_con_lai'] = $phi_phat_tra_cham_ky_hien_tai;
				$dataUpdate_pp_t['so_ngay_cham_tra'] = $so_ngay_tra_cham_ky_hien_tai;
				if (!empty($dataUpdate_pp_t)) {
					$this->temporary_plan_contract_model->update(
						array("_id" => $chamtra['_id']),
						$dataUpdate_pp_t
					);
				}
			}
			//phí chậm trả == phí chậm trả đã đóng kỳ hiện tại =0
			if ($fee_delay_pay > 0 && $fee_delay_pay == $phi_phat_cham_tra_da_dong_ky_hien_tai) {
				$phi_phat_tra_cham_ky_hien_tai = 0;
			}
           $amountRemain_origin=$amountRemain;
			//phí phạt
			
			if ($amountRemain > 0 && $phi_phat_tra_cham_ky_hien_tai > 0 && $chamtra['fee_delay_pay'] > 0) {
				if ($amountRemain >= $phi_phat_tra_cham_ky_hien_tai) {
					//Update $phi_phat_cham_tra_da_dong_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_da_tra'] = $phi_phat_cham_tra_da_dong_ky_hien_tai + $phi_phat_tra_cham_ky_hien_tai;

					$so_phi_phat_cham_tra_da_tra_transaction = $so_phi_phat_cham_tra_da_tra_transaction + $phi_phat_tra_cham_ky_hien_tai;

					//Update $phi_phat_tra_cham_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_phat_tra_cham_ky_hien_tai;
				} else {
					//Update $phi_phat_da_dong_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_da_tra'] = $phi_phat_cham_tra_da_dong_ky_hien_tai + $amountRemain;

					$so_phi_phat_cham_tra_da_tra_transaction = $so_phi_phat_cham_tra_da_tra_transaction + $amountRemain;

					//Update $phi_phat_tra_cham_ky_hien_tai
					$dataUpdate_pp['tien_phi_cham_tra_1ky_con_lai'] = $phi_phat_tra_cham_ky_hien_tai - $amountRemain;
					$amountRemain = 0;
				}
                 
				//Update DB
				if (!empty($dataUpdate_pp)) {
					$dataUpdate_pp['phi_phat_tra_cham_ky_hien_tai']=$phi_phat_tra_cham_ky_hien_tai;
					$dataUpdate_pp['amountRemain']=$amountRemain;
					$dataUpdate_pp['amountRemain_origin']=$amountRemain_origin;
					$this->temporary_plan_contract_model->update(
						array("_id" => $chamtra['_id']),
						$dataUpdate_pp
					);
				}


				//Update số tiền cham tra đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
				if($so_phi_phat_cham_tra_da_tra_transaction>0 && $ck_gtpp=="")
				{

					$this->transaction_model->update(
					array("_id" => new MongoDB\BSON\ObjectId($transId)),
					array(
						"temporary_plan_contract_id" => $chamtra['_id'],
						"so_tien_phi_cham_tra_da_tra" =>$so_phi_phat_cham_tra_da_tra_transaction,
						"amountRemain" =>$amountRemain
					)
				);
			  }
			}
		}
		// if($ck_gtpp=="GT")
		// {
		// 	$tranData_now=$this->transaction_model->findOne(['_id'=>new MongoDB\BSON\ObjectId($transId)]);	
		// 	$so_phi_phat_cham_tra_da_tra_now=(isset($tranData_now['so_tien_phi_cham_tra_da_tra'])) ? $tranData_now['so_tien_phi_cham_tra_da_tra'] : 0;
		// 			$this->transaction_model->update(
		// 			array("_id" => new MongoDB\BSON\ObjectId($transId)),
		// 			array(
						
		// 				"so_tien_phi_cham_tra_da_tra" =>$so_tien_phi_cham_tra_da_tra +$so_phi_phat_cham_tra_da_tra_now
		// 			)
		// 		);
		// }

		return $amountRemain;
	}

	private function bangLaiKy_tinhtoan_tien_datra_conlai_giahan($temps, $amount, $transId)
	{
		$amountRemain = 0;

		$so_goc_da_tra_transaction = 0;
		$so_lai_da_tra_transaction = 0;
		$so_phi_da_tra_transaction = 0;

		foreach ($temps as $temp) {
			if ($amountRemain == 0) $amountRemain = $amount;

			//Tiền đã đóng kỳ hiện tại
			$lai_da_dong_ky_hien_tai = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;
			$phi_da_dong_ky_hien_tai = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;

			//Tiền còn lại phải đóng kỳ hiện tại
			$lai_con_lai_ky_hien_tai = !empty($temp['tien_lai_1ky_con_lai']) ? $temp['tien_lai_1ky_con_lai'] : 0;
			$phi_con_lai_ky_hien_tai = !empty($temp['tien_phi_1ky_con_lai']) ? $temp['tien_phi_1ky_con_lai'] : 0;

			$dataUpdate = array();

			if ($amountRemain <= 0) break;
			//Lãi
			if ($lai_con_lai_ky_hien_tai > 0) {
				if ($amountRemain >= $lai_con_lai_ky_hien_tai) {
					//Update $lai_da_dong_ky_hien_tai
					$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $lai_con_lai_ky_hien_tai;

					$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $lai_con_lai_ky_hien_tai;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_lai_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
				} else {
					//Update $lai_da_dong_ky_hien_tai
					$dataUpdate['tien_lai_1ky_da_tra'] = $lai_da_dong_ky_hien_tai + $amountRemain;

					$so_lai_da_tra_transaction = $so_lai_da_tra_transaction + $amountRemain;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_lai_1ky_con_lai'] = $lai_con_lai_ky_hien_tai - $amountRemain;
					//Remain
					$amountRemain = $amountRemain - $lai_con_lai_ky_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->temporary_plan_contract_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}

			//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				array(
					"so_tien_lai_da_tra" => $so_lai_da_tra_transaction
				)
			);

			if ($amountRemain <= 0) break;
			//Phí
			if ($phi_con_lai_ky_hien_tai > 0) {
				if ($amountRemain >= $phi_con_lai_ky_hien_tai) {
					$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $phi_con_lai_ky_hien_tai;

					$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $phi_con_lai_ky_hien_tai;

					//Update $lai_con_lai_ky_hien_tai
					$dataUpdate['tien_phi_1ky_con_lai'] = 0;
					//Remain
					$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
				} else {
					//Update $phi_da_dong_ky_hien_tai
					$dataUpdate['tien_phi_1ky_da_tra'] = $phi_da_dong_ky_hien_tai + $amountRemain;

					$so_phi_da_tra_transaction = $so_phi_da_tra_transaction + $amountRemain;

					//Update $phi_con_lai_ky_hien_tai
					$dataUpdate['tien_phi_1ky_con_lai'] = $phi_con_lai_ky_hien_tai - $amountRemain;
					$amountRemain = $amountRemain - $phi_con_lai_ky_hien_tai;
				}
			}
			//Update DB
			if (!empty($dataUpdate)) {
				$this->temporary_plan_contract_model->update(
					array("_id" => $temp['_id']),
					$dataUpdate
				);
			}
			//Update số tiền gốc đã đóng, số tiền lãi đã đóng, số tiền phí đã đóng ở bảng transaction
			$this->transaction_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($transId)),
				array(
					"so_tien_phi_da_tra" => $so_phi_da_tra_transaction
				)
			);
		}
	}

	private function bangLaiKy_tinhtoan_duno_thangtruoc($code_contract, $amount)
	{
		//Step 1: tìm lại các bảng lãi tháng
		$temps = $this->temporary_plan_contract_model->find_where_order_by(array(
			"code_contract" => $code_contract
		));

		$du_no_goc_ky_truoc_da_tra = 0;
		$du_no_lai_ky_truoc_da_tra = 0;
		$du_no_phi_ky_truoc_da_tra = 0;

		$temp_du_no_goc_ky_truoc = !empty($temp['du_no_goc_ky_truoc']) ? $temp['du_no_goc_ky_truoc'] : 0;
		$temp_du_no_goc_ky_truoc_da_tra = !empty($temp['du_no_goc_ky_truoc_da_tra']) ? $temp['du_no_goc_ky_truoc_da_tra'] : 0;
		$temp_tien_goc_1ky_da_tra = !empty($temp['tien_goc_1ky_da_tra']) ? $temp['tien_goc_1ky_da_tra'] : 0;

		$temp_du_no_lai_ky_truoc = !empty($temp['du_no_lai_ky_truoc']) ? $temp['du_no_lai_ky_truoc'] : 0;
		$temp_du_no_lai_ky_truoc_da_tra = !empty($temp['du_no_lai_ky_truoc_da_tra']) ? $temp['du_no_lai_ky_truoc_da_tra'] : 0;
		$temp_tien_lai_1ky_da_tra = !empty($temp['tien_lai_1ky_da_tra']) ? $temp['tien_lai_1ky_da_tra'] : 0;

		$temp_du_no_phi_ky_truoc = !empty($temp['du_no_phi_ky_truoc']) ? $temp['du_no_phi_ky_truoc'] : 0;
		$temp_du_no_phi_ky_truoc_da_tra = !empty($temp['du_no_phi_ky_truoc_da_tra']) ? $temp['du_no_phi_ky_truoc_da_tra'] : 0;
		$temp_tien_phi_1ky_da_tra = !empty($temp['tien_phi_1ky_da_tra']) ? $temp['tien_phi_1ky_da_tra'] : 0;

		foreach ($temps as $temp) {
			if ($temp_du_no_goc_ky_truoc > 0 && $du_no_goc_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_goc_ky_truoc'] = $temp_du_no_goc_ky_truoc + $temp_du_no_goc_ky_truoc_da_tra - $du_no_goc_ky_truoc_da_tra;
				$dataUpdate['du_no_goc_ky_truoc_da_tra'] = $du_no_goc_ky_truoc_da_tra;
			}
			$du_no_goc_ky_truoc_da_tra = $du_no_goc_ky_truoc_da_tra + $temp_tien_goc_1ky_da_tra;

			if ($temp_du_no_lai_ky_truoc > 0 && $du_no_lai_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_lai_ky_truoc'] = $temp_du_no_lai_ky_truoc + $temp_du_no_lai_ky_truoc_da_tra - $du_no_lai_ky_truoc_da_tra;
				$dataUpdate['du_no_lai_ky_truoc_da_tra'] = $du_no_lai_ky_truoc_da_tra;
			}
			$du_no_lai_ky_truoc_da_tra = $du_no_lai_ky_truoc_da_tra + $temp_tien_lai_1ky_da_tra;

			if ($temp_du_no_phi_ky_truoc > 0 && $du_no_phi_ky_truoc_da_tra > 0) {
				$dataUpdate['du_no_phi_ky_truoc'] = $temp_du_no_phi_ky_truoc + $temp_du_no_phi_ky_truoc_da_tra - $du_no_phi_ky_truoc_da_tra;
				$dataUpdate['du_no_phi_ky_truoc_da_tra'] = $du_no_phi_ky_truoc_da_tra;
			}
			$du_no_phi_ky_truoc_da_tra = $du_no_phi_ky_truoc_da_tra + $temp_tien_phi_1ky_da_tra;

			//Update DB
			if (empty($dataUpdate)) continue;
			$this->temporary_plan_contract_model->update(
				array("_id" => $temp['_id']),
				$dataUpdate
			);
		}
	}

	private function tinhFeeExtend($codeContract, $transaction_id)
	{
		//Tìm phí gia hạn trong contract
		$contractDB = $this->contract_model->findOne(array(
			'code_contract' => $codeContract
		));
		$fee_extend = !empty($contractDB) && !empty($contractDB['fee']) ? $contractDB['fee']['extend'] : 0;
		//Lưu vào bảng lãi kỳ
		$currentLaiKy = $this->contract_tempo_model->findOneOrderBy(
			array("code_contract" => $codeContract),
			array("ngay_ky_tra" => "DESC")
		);
		if (!empty($currentLaiKy)) {
			$this->contract_tempo_model->update(
				array("_id" => $currentLaiKy['_id']),
				array("fee_extend" => (float)$fee_extend)
			);
		}
		//Lưu vào bảng lãi tháng
		$time = date('m/Y', strtotime('now'));
		$this->tempo_contract_accounting_model->findOneAndUpdate(
			array("code_contract" => $codeContract, 'time' => $time),
			array('fee_extend' => (float)$fee_extend)
		);
		$this->transaction_model->update(
			array("_id" => $transaction_id),
			array("fee_extend" => (float)$fee_extend)
		);
	}

	private function tinhFeeFinishContract($codeContract, $feeFinishContract, $time, $date_pay)
	{
		//Bảng lãi tháng
		$this->tempo_contract_accounting_model->findOneAndUpdate(
			array("code_contract" => $codeContract,
				'time' => $time),
			array('fee_finish_contract' => $feeFinishContract)
		);
		//Bảng lãi kỳ
		$currentLaiKy = $this->contract_tempo_model->findOneOrderBy(
			array("code_contract" => $codeContract,
				"ngay_ky_tra" => array('gte' => $date_pay)),
			array("ngay_ky_tra" => "ESC")
		);
		if (!empty($currentLaiKy)) {
			$this->contract_tempo_model->update(
				array("_id" => $currentLaiKy['_id']),
				array("fee_finish_contract" => (float)$feeFinishContract)
			);
		}
	}

	private function get_goc_lai_phi_tat_toan_bang_ky($contractDB, $date_pay)
	{

		$code_contract = $contractDB['code_contract'];
		// $goc_lai_phi_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($code_contract, $date_pay);
		$goc_lai_phi_da_tra = $this->temporary_plan_contract_model->goc_lai_phi_da_tra($code_contract, $date_pay);
		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($code_contract, $date_pay);
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($code_contract, $date_pay);
		//Tiền gốc còn lại phải đóng
		
		$goc_phai_dong = $get_infor_tat_toan_part_1['goc_chua_tra_den_thoi_diem_dao_han'];
		$lai_phai_dong = $get_infor_tat_toan_part_1['lai_chua_tra_den_thoi_diem_hien_tai'] ;
		$phi_phai_dong = $get_infor_tat_toan_part_1['phi_chua_tra_den_thoi_diem_hien_tai'];
		$so_ngay_trong_ky_tinh_lai = $get_infor_tat_toan_part_1['so_ngay_trong_ky_tinh_lai'];
	     $lai_con_no_thuc_te =$get_infor_tat_toan_part_2['lai_con_no_thuc_te'];
         $phi_con_no_thuc_te =$get_infor_tat_toan_part_2['phi_con_no_thuc_te'];
		$data = array();
		$data['goc_phai_dong'] = $goc_phai_dong;
		$data['lai_phai_dong'] = $lai_phai_dong;
		$data['phi_phai_dong'] = $phi_phai_dong;
		$data['lai_con_no_thuc_te'] = $lai_con_no_thuc_te;
		$data['phi_con_no_thuc_te'] = $phi_con_no_thuc_te;
		$data['so_ngay_trong_ky_tinh_lai'] = $so_ngay_trong_ky_tinh_lai;

		return $data;
	}

	public function get_goc_lai_phi_tat_toan_bang_ky_post()
	{
		$post = $this->input->post();
		$code_contract = $post['code_contract'];
		$date_pay = (int)$post['date_pay'];

		$goc_lai_phi_da_tra = $this->temporary_plan_contract_model->goc_lai_phi_da_tra($code_contract, $date_pay);
		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($code_contract, $date_pay);
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($code_contract, $date_pay);
		//Tiền gốc còn lại phải đóng
		
		$goc_phai_dong = $get_infor_tat_toan_part_1['goc_chua_tra_den_thoi_diem_dao_han'];
		$lai_phai_dong = $get_infor_tat_toan_part_1['lai_chua_tra_den_thoi_diem_hien_tai'] ;
		$phi_phai_dong = $get_infor_tat_toan_part_1['phi_chua_tra_den_thoi_diem_hien_tai'];
	     $lai_con_no_thuc_te =$get_infor_tat_toan_part_2['lai_con_no_thuc_te'];
         $phi_con_no_thuc_te =$get_infor_tat_toan_part_2['phi_con_no_thuc_te'];
		$data = array();
		$data['goc_phai_dong'] = $goc_phai_dong;
		$data['lai_phai_dong'] = $lai_phai_dong;
		$data['phi_phai_dong'] = $phi_phai_dong;
		$data['lai_con_no_thuc_te'] = $lai_con_no_thuc_te;
		$data['phi_con_no_thuc_te'] = $phi_con_no_thuc_te;
		$data['post'] = $post;

		
		var_dump($data);
		die;
	
	}

	private function getFeeFinishContract($contractDB)
	{
		$tien_giai_ngan = (float)$contractDB['receiver_infor']['amount'];
		$percent_prepay_phase_1 = !empty($contractDB['fee']['percent_prepay_phase_1']) ? $contractDB['fee']['percent_prepay_phase_1'] : 0;
		$percent_prepay_phase_2 = !empty($contractDB['fee']['percent_prepay_phase_2']) ? $contractDB['fee']['percent_prepay_phase_2'] : 0;
		$percent_prepay_phase_3 = !empty($contractDB['fee']['percent_prepay_phase_3']) ? $contractDB['fee']['percent_prepay_phase_3'] : 0;

		$timestamp_tong_ngay_vay_du_kien = (float)$contractDB['loan_infor']['number_day_loan'] * 86400;
		$timestamp_thoi_diem_tat_toan = $this->createdAt;
		$so_ngay_tat_toan_som = round(($timestamp_tong_ngay_vay_du_kien - $timestamp_thoi_diem_tat_toan) / 86400) + 1;

		$so_ngay_phase_1 = round((float)$contractDB['loan_infor']['number_day_loan'] / 3);
		$so_ngay_phase_2 = round((float)$contractDB['loan_infor']['number_day_loan'] * 2 / 3);

		$feeFinishContract = 0;
		if ($so_ngay_tat_toan_som <= 3) {
			$feeFinishContract = 0;
		} //Nếu trước 1/3 thòi hạn vay = $percent_prepay_phase_1
		else if ($so_ngay_tat_toan_som <= $so_ngay_phase_1) {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_1;
		} //Nếu trước 2/3 thòi hạn vay = $percent_prepay_phase_2
		else if ($so_ngay_phase_1 < $so_ngay_tat_toan_som && $so_ngay_tat_toan_som <= $so_ngay_phase_2) {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_2;
		} //Còn lại = $percent_prepay_phase_3
		else {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_3;
		}
		return $feeFinishContract;
	}

	private function getFeeFinishContract_post($contractDB)
	{
		$tien_giai_ngan = (float)$contractDB['receiver_infor']['amount'];
		$percent_prepay_phase_1 = !empty($contractDB['fee']['percent_prepay_phase_1']) ? $contractDB['fee']['percent_prepay_phase_1'] : 0;
		$percent_prepay_phase_2 = !empty($contractDB['fee']['percent_prepay_phase_2']) ? $contractDB['fee']['percent_prepay_phase_2'] : 0;
		$percent_prepay_phase_3 = !empty($contractDB['fee']['percent_prepay_phase_3']) ? $contractDB['fee']['percent_prepay_phase_3'] : 0;

		$timestamp_tong_ngay_vay_du_kien = (float)$contractDB['loan_infor']['number_day_loan'] * 86400;
		$timestamp_thoi_diem_tat_toan = $this->createdAt;
		$so_ngay_tat_toan_som = round(($timestamp_tong_ngay_vay_du_kien - $timestamp_thoi_diem_tat_toan) / 86400) + 1;

		$so_ngay_phase_1 = round((float)$contractDB['loan_infor']['number_day_loan'] / 3);
		$so_ngay_phase_2 = round((float)$contractDB['loan_infor']['number_day_loan'] * 2 / 3);

		$feeFinishContract = 0;
		if ($so_ngay_tat_toan_som <= 3) {
			$feeFinishContract = 0;
		} //Nếu trước 1/3 thòi hạn vay = $percent_prepay_phase_1
		else if ($so_ngay_tat_toan_som <= $so_ngay_phase_1) {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_1;
		} //Nếu trước 2/3 thòi hạn vay = $percent_prepay_phase_2
		else if ($so_ngay_phase_1 < $so_ngay_tat_toan_som && $so_ngay_tat_toan_som <= $so_ngay_phase_2) {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_2;
		} //Còn lại = $percent_prepay_phase_3
		else {
			$feeFinishContract = $tien_giai_ngan * $percent_prepay_phase_3;
		}
		return $feeFinishContract;
	}

	private function tinh_goc_lai_phi_transaction_tat_toan($transId, $phi_phat_sinh)
	{
		//Update trans id
		$update = array();
		$update['so_tien_goc_da_tra'] = $this->so_tien_goc_da_tra_tat_toan;
		$update['so_tien_lai_da_tra'] = $this->so_tien_lai_da_tra_tat_toan;
		$update['so_tien_phi_da_tra'] = $this->so_tien_phi_da_tra_tat_toan;
		$update['tien_thua_tat_toan'] = $this->tien_thua_tat_toan;
		$update['fee_finish_contract'] = $this->so_tien_phi_tat_toan_da_tra;
		$update['phi_phat_sinh'] = $phi_phat_sinh;
		$update['tien_phi_phat_sinh_da_tra'] = $this->so_tien_phi_phat_sinh_da_tra;
		$update['so_tien_phi_cham_tra_da_tra'] = $this->so_tien_phi_cham_tra_da_tra;
		$update['so_tien_phi_gia_han_da_tra'] = $this->so_tien_phi_gia_han_da_tra;
		$update['tat_toan_phai_tra']=[
				'so_tien_goc_phai_tra_tat_toan' => $this->so_tien_goc_phai_tra_tat_toan,
				'so_tien_lai_phai_tra_tat_toan' => $this->so_tien_lai_phai_tra_tat_toan,
				'so_tien_phi_phai_tra_tat_toan' => $this->so_tien_phi_phai_tra_tat_toan,
				'so_tien_phi_cham_tra_phai_tra_tat_toan' => $this->so_tien_phi_cham_tra_phai_tra_tat_toan,
				'so_tien_phi_gia_han_phai_tra_tat_toan' => $this->so_tien_phi_gia_han_phai_tra_tat_toan,
				'so_tien_phi_phat_sinh_phai_tra_tat_toan' => $this->so_tien_phi_phat_sinh_phai_tra_tat_toan
	     ];
		$update['lai_con_no_thuc_te'] = $this->lai_con_no_thuc_te;
		$update['phi_con_no_thuc_te'] = $this->phi_con_no_thuc_te;
		$update['phai_tra_hop_dong']=[
				"so_tien_goc_phai_tra_hop_dong"=>$this->so_tien_goc_phai_tra_hop_dong,
			    "so_tien_lai_phai_tra_hop_dong"=>$this->so_tien_lai_phai_tra_hop_dong,
			    "so_tien_phi_phai_tra_hop_dong"=>$this->so_tien_phi_phai_tra_hop_dong,
			    "phi_gia_han_phai_tra_hop_dong"=>$this->phi_gia_han_phai_tra_hop_dong,
			    "phi_cham_tra_phai_tra_hop_dong"=>$this->phi_cham_tra_phai_tra_hop_dong,
			    "phi_tat_toan_phai_tra_hop_dong"=>$this->phi_tat_toan_phai_tra_hop_dong,
			    "phi_phat_sinh_phai_tra_hop_dong"=>$this->phi_phat_sinh_phai_tra_hop_dong
		];
		$this->transaction_model->update(
			array("_id" => $transId),
			$update
		);
	}
   public function update_tran_thanhtoan($contractDB,$date_pay_tt,$type_payment,$type_transaction)
   {
   	        $data_delete = array(
				"code_contract" =>  $contractDB['code_contract'],
			);
			$this->payment_model->delete_lai_ky_lai_thang($data_delete);
				$data_generate = array(
					"code_contract" => $contractDB['code_contract'],
					"investor_code" =>$contractDB['investor_code'],
					"disbursement_date" => $contractDB['disbursement_date'],
					"date_pay" => $date_pay_tt,
					"type_payment" => $type_payment,
					"type_transaction" => $type_transaction,
				);
			$this->generate_model->processGenerate($data_generate);
			
   	       $data_transaction =$this->transaction_model->find_where(array('code_contract'=>$contractDB['code_contract'],'status'=>1,'type'=>4,'type_payment'=>1));
   	      
		    if (!empty($data_transaction)) {
			foreach ($data_transaction as $key => $value) {
				 
			
			    $value['total']=$this->chia_tien_thieu($value['total'],$contractDB['code_contract_parent_gh']);
			    $date_pay = (isset($value['date_pay'])) ? strtotime(date("Y-m-d", $value['date_pay']) . '  23:59:59') : strtotime(date("Y-m-d", $value['created_at']) . '  23:59:59');
			    $date_pay_tt = (isset($date_pay_tt)) ? strtotime(date("Y-m-d", $date_pay_tt) . '  00:00:00') : 0;
			    $phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['arr_penaty'];
			   
			    if($value['date_pay'] <= strtotime('2021-09-30  23:59:59') && ( !empty($contractDB['type_gh']) || !empty($contractDB['type_cc'])))
	           {
				  $phi_phat_tra_cham=[];
			    }
				 $this->finishTempoPlan($value['code_contract'], (int)$value['total'], $date_pay,(int)$value['total_deductible']);
			     $temps_ = $this->temporary_plan_contract_model->find_where_order_by(array(
					"code_contract" => $contractDB['code_contract']
				));
               

				$this->bangLaiKy_tinhtoan_tien_datra_conlai($temps_, (float)$value['total'], (string)$value['_id'],$contractDB['code_contract'],$phi_phat_tra_cham, 0,$date_pay,0,(int)$value['type_payment'],$date_pay_tt);
				$this->bangLaiKy_tinhtoan_tien_datra_conlai_tien_giam_tru($value['total_deductible'],(string)$value['_id'], $contractDB['code_contract'], $phi_phat_tra_cham, 0,(int)$date_pay,0,$value['type_payment']);
				$dataKy_da_tt = $this->temporary_plan_contract_model->getKiDaThanhToanGanNhat($contractDB['code_contract']);
                $ky_da_tt_gan_nhat=isset($dataKy_da_tt[0]['ky_tra']) ? $dataKy_da_tt[0]['ky_tra'] : 0;
                $update_tran=array(
					'fee_delay_pay'=>$phi_phat_tra_cham,
					'date_pay_tt'=>$date_pay_tt,
					'ky_da_tt_gan_nhat'=>$ky_da_tt_gan_nhat,
					'stt'=> $key
						
					);
				
				$update_tran['con_lai_sau_thanh_toan']=$this->get_con_lai_sau_thanh_toan( $contractDB['code_contract'],$date_pay);
				//Update
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					 $update_tran
					
				);
				
				
			}
		}
   }
   //chia tất toán / cơ cấu
   // $feeFinishContract phí tất toán trước hạn
   //$type_payment loại thanh toán
   //$total_deductible tổng giảm trừ
   	private function cap_nhat_tat_toan_tai_ki_hien_tai($contractDB, $amount, $feeFinishContract,$phi_phat_tra_cham,$date_pay,$phi_phat_sinh,$phi_gia_han,$type_payment,$total_deductible,$transId,$amount_lai_tru_ky_cuoi = 0) {
	        $currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
         	if($type_payment==3)
				{
					//kiểm tra lại cơ cấu gốc để cập nhật lại amount_cc: số tiền thanh toán trong phiếu thu
					if($contractDB['type_cc']=='origin')
					{
                     $this->transaction_model->findOneAndUpdate(
					       array("_id" => new MongoDB\BSON\ObjectId($transId)),
					 ['type_payment'=>(int)$type_payment,'amount_cc'=>(float)$contractDB['structure_all'][1]['amount_money']]
					
				       );
					}else{
						$contractDB_origin = $this->contract_model->findOne(array('code_contract' =>$contractDB['code_contract_parent_cc']));
						
						$this->transaction_model->findOneAndUpdate(
					       array("_id" => new MongoDB\BSON\ObjectId($transId)),
					 ['type_payment'=>(int)$type_payment,'amount_cc'=>(float)$contractDB_origin['structure_all'][(int)$contractDB['type_cc']+1]['amount_money']]
					
				       );
					}
                }
         $arr_data=[
		'date_pay'=>$date_pay,
		'id_contract'=>(string)$contractDB['_id'],
		'code_contract'=>$contractDB['code_contract']

	    ];
	
	$tranData =$this->transaction_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($transId)));
	//chia lại số tiền phiếu thu thanh toán
	//< ngày tất toán thì chia còn lại đổ vào tiền thừa 
	$this->update_tran_thanhtoan($contractDB,$tranData['date_pay'],$type_payment, $tranData['type']);
 
	  $amount=$this->chia_tien_thieu( $amount,$contractDB['code_contract_parent_gh']);
	
   $goc_lai_phi_phai_tra = $this->get_goc_lai_phi_tat_toan_bang_ky($contractDB,$date_pay);
   //tổng tiền tất cả phiếu thu
    $ttptData=$this->transaction_model->get_tong_tien_phieu_thu($contractDB['code_contract']);
	// Lấy lãi, phí, gốc chưa trả
	$goc_lai_phi_con_lai_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($contractDB['code_contract']);
    //lấy kỳ trước tất toán
    $truoc_tat_toan_ky_tt=$this->temporary_plan_contract_model->get_tien_da_tra_truoc_tat_toan_ki_tt($contractDB['code_contract'],$date_pay);
    //tiền gốc đã trả các kỳ
    $truoc_tat_toan_all=$this->temporary_plan_contract_model->get_tien_da_tra_truoc_tat_toan($contractDB['code_contract'],$date_pay);
	$contract_pay=$this->payment_model->get_payment($arr_data)['contract'];

	$tien_thua_thanh_toan=0;
	//lấy tiền thừa phiếu thanh toán
	$transactionData = $this->transaction_model->find_where_pay_all(array('code_contract' => $contractDB['code_contract'],'type' => 4, "status" => ['$in'=>[1,5]]));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {

				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
				}
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					array("tien_thua_thanh_toan_con_lai" =>0,
						  "tien_thua_thanh_toan_da_tra" =>$value['tien_thua_thanh_toan']
				          )
				);

			}
		}
		$amount=$tranData['total']+$tien_thua_thanh_toan;
		//Cập nhật gốc + lãi + phí vào kì hiện tại
		//Gốc, lãi, phí của kì
		
		$tong_phi_cham_tra=0;

		$tong_phi_cham_tra = !empty($contract_pay['penalty_pay']) ? $contract_pay['penalty_pay'] : 0;

		$lai_con_no_thuc_te = $goc_lai_phi_phai_tra['lai_con_no_thuc_te'];
		$phi_con_no_thuc_te = $goc_lai_phi_phai_tra['phi_con_no_thuc_te'];

		$so_tien_goc_da_tra = 0;
		$so_tien_lai_da_tra = 0;
		$so_tien_phi_da_tra = 0;
		
		$phi_gia_han_da_tra = 0;
		$phi_cham_tra_da_tra = 0;
		$phi_tat_toan_da_tra = 0;
		$phi_phat_sinh_da_tra = 0;
		
		$tien_goc_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_goc_da_tra_truoc_tat_toan'];
		$tien_lai_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_lai_da_tra_truoc_tat_toan'];
		$tien_phi_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_phi_da_tra_truoc_tat_toan'];

$tien_cham_tra_da_tra_truoc_tat_toan=$truoc_tat_toan_ky_tt['tien_cham_tra_da_tra_truoc_tat_toan'];
    // trước 30/9 phiếu cơ cấu không có tất toán trước hạn , chậm trả và phát sinh
    if($tranData['date_pay'] <= strtotime('2021-09-30  23:59:59') && $type_payment==3)
    {
     $feeFinishContract=0;
     $tong_phi_cham_tra=0;
     $phi_phat_sinh=0;
    }	
    //các số tiền phải trả từ lúc giải ngân đến lúc tất toán
		$this->so_tien_goc_phai_tra_hop_dong = (int)$contractDB['loan_infor']['amount_money'];
    // nếu có lãi NĐT trừ vào kỳ cuối theo coupon => Tổng lãi phải trả của hợp đồng - lãi đã trừ vào kỳ cuối
    if ($amount_lai_tru_ky_cuoi > 0) {
		$this->so_tien_lai_phai_tra_hop_dong = $tien_lai_da_tra_truoc_tat_toan + $goc_lai_phi_phai_tra['lai_phai_dong'] - $amount_lai_tru_ky_cuoi;
	} else {
		$this->so_tien_lai_phai_tra_hop_dong = $tien_lai_da_tra_truoc_tat_toan+$goc_lai_phi_phai_tra['lai_phai_dong'];
	}

		$this->so_tien_phi_phai_tra_hop_dong = $tien_phi_da_tra_truoc_tat_toan+$goc_lai_phi_phai_tra['phi_phai_dong'];
		$this->phi_gia_han_phai_tra_hop_dong = $phi_gia_han;
		$this->phi_cham_tra_phai_tra_hop_dong =$tien_cham_tra_da_tra_truoc_tat_toan+ $tong_phi_cham_tra;
		$this->phi_tat_toan_phai_tra_hop_dong = $feeFinishContract;
		$this->phi_phat_sinh_phai_tra_hop_dong = $phi_phat_sinh;
		// nếu có lãi NĐT trừ vào kỳ cuối theo coupon => Lãi phải trả = lãi phải trả kỳ cuối (kỳ tất toán)
		if ($amount_lai_tru_ky_cuoi > 0) {
			$so_tien_lai_phai_tra = $goc_lai_phi_con_lai_chua_tra[0]['lai_chua_tra'];
		} else {
			$so_tien_lai_phai_tra =$this->so_tien_lai_phai_tra_hop_dong-$truoc_tat_toan_all['tien_lai_da_tra_truoc_tat_toan'];
		}
		$so_tien_phi_phai_tra = $this->so_tien_phi_phai_tra_hop_dong-$truoc_tat_toan_all['tien_phi_da_tra_truoc_tat_toan'];
        $so_tien_goc_phai_tra = $contractDB['loan_infor']['amount_money']-$truoc_tat_toan_all['tien_goc_da_tra_truoc_tat_toan'];
       $truoc_tat_toan_phieu_thu=$this->transaction_model->get_tien_da_tra_truoc_tat_toan($contractDB['code_contract'],$date_pay);
       // không chia lại amount va số liền lãi phải trả để khớp với số tiền lãi thực tế khách được giảm ở coupon giảm lãi
       if ($amount_lai_tru_ky_cuoi == 0) {
		   if(isset($truoc_tat_toan_phieu_thu['so_tien_lai_da_tra']) && $truoc_tat_toan_phieu_thu['so_tien_lai_da_tra'] >$this->so_tien_lai_phai_tra_hop_dong)
		   {
			   $so_tien_lai_phai_tra=0;
			   $amount=$amount+($truoc_tat_toan_phieu_thu['so_tien_lai_da_tra']-$this->so_tien_lai_phai_tra_hop_dong);
		   }
	   }

        if(isset($truoc_tat_toan_phieu_thu['so_tien_phi_da_tra']) && $truoc_tat_toan_phieu_thu['so_tien_phi_da_tra'] >$this->so_tien_phi_phai_tra_hop_dong)
       {
           $so_tien_phi_phai_tra=0;
           $amount=$amount+($truoc_tat_toan_phieu_thu['so_tien_phi_da_tra']-$this->so_tien_phi_phai_tra_hop_dong);
       }
       //cơ cấu thì gốc - số tiền cơ cấu
        if($type_payment==3)
        {
        	 $so_tien_goc_phai_tra =$so_tien_goc_phai_tra-$tranData['amount_cc'];
        	 if($so_tien_goc_phai_tra<0)
        	 	$so_tien_goc_phai_tra =0;
        }
		$date_ngay_t= strtotime($this->config->item("date_t_apply"));
		//phải trả
        $this->so_tien_goc_phai_tra_tat_toan = $so_tien_goc_phai_tra;
		$this->so_tien_lai_phai_tra_tat_toan = $so_tien_lai_phai_tra;
		$this->so_tien_phi_phai_tra_tat_toan= $so_tien_phi_phai_tra;
		$this->so_tien_phi_tat_toan_phai_tra_tat_toan = $feeFinishContract;
		$this->so_tien_phi_cham_tra_phai_tra_tat_toan = $tong_phi_cham_tra;
		$this->so_tien_phi_gia_han_phai_tra_tat_toan = $phi_gia_han;
		$this->so_tien_phi_phat_sinh_phai_tra_tat_toan = $phi_phat_sinh;
		$amount_miengiam=$total_deductible;

		if($amount_miengiam>0)
		{
		//==============chia miễn giảm===================
		//----------gia hạn-----------------------
		  $phi_gia_han_da_tra_miengiam=0;
           if($amount_miengiam >= $phi_gia_han) {
			$phi_gia_han_da_tra_miengiam = $phi_gia_han;
			$amount_miengiam = $amount_miengiam - $phi_gia_han;
		   }else 
		    if($amount_miengiam >0 && $phi_gia_han>0 )
		    {
		    	$phi_gia_han_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $phi_gia_han=$phi_gia_han-$phi_gia_han_da_tra_miengiam;
		//-------------------------------------------
        //----------tất toán----------------------- 
		     $phi_tat_toan_da_tra_miengiam=0;
		   if($amount_miengiam >= $feeFinishContract) {
			$phi_tat_toan_da_tra_miengiam = $feeFinishContract;
			$amount_miengiam = $amount_miengiam - $feeFinishContract;
		     }else 
		    if($amount_miengiam >0 && $feeFinishContract>0 )
		    {
		    	$phi_tat_toan_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $feeFinishContract=$feeFinishContract-$phi_tat_toan_da_tra_miengiam;
		//-------------------------------------------
		 //----------phát sinh----------------------- 
		     $phi_phat_sinh_da_tra_miengiam=0;    
		    if($amount_miengiam >= $phi_phat_sinh) {
			$phi_phat_sinh_da_tra_miengiam = $phi_phat_sinh;
			$amount_miengiam = $amount_miengiam - $phi_phat_sinh;
		   }else 
		    if($amount_miengiam >0 && $phi_phat_sinh>0 )
		    {
		    	$phi_phat_sinh_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $phi_phat_sinh=$phi_phat_sinh-$phi_phat_sinh_da_tra_miengiam;
		//-------------------------------------------
		  //----------chậm trả----------------------- 
		     $phi_cham_tra_da_tra_miengiam=0;    
		   if($amount_miengiam >= $tong_phi_cham_tra) {
			$phi_cham_tra_da_tra_miengiam = $tong_phi_cham_tra;
			$amount_miengiam = $amount_miengiam - $tong_phi_cham_tra;
		   }else 
		    if($amount_miengiam >0 && $tong_phi_cham_tra>0 )
		    {
		    	$phi_cham_tra_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $tong_phi_cham_tra=$tong_phi_cham_tra-$phi_cham_tra_da_tra_miengiam;
		//-------------------------------------------
		//----------phí----------------------- 
		     $so_tien_phi_da_tra_miengiam=0;    
		   if($amount_miengiam >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra_miengiam = $so_tien_phi_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_phi_phai_tra;
		  }else 
		    if($amount_miengiam >0 && $so_tien_phi_phai_tra>0 )
		    {
		    	$so_tien_phi_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_phi_phai_tra=$so_tien_phi_phai_tra-$so_tien_phi_da_tra_miengiam;
		//-------------------------------------------
		//----------lai----------------------- 
		     $so_tien_lai_da_tra_miengiam=0;    
			if($amount_miengiam >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra_miengiam = $so_tien_lai_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_lai_phai_tra;
		 }else 
		    if($amount_miengiam >0 && $so_tien_lai_phai_tra>0 )
		    {
		    	$so_tien_lai_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_lai_phai_tra=$so_tien_lai_phai_tra-$so_tien_lai_da_tra_miengiam;
		//-------------------------------------------
		 //----------gốc----------------------- 
		     $so_tien_goc_da_tra_miengiam=0;    
	    if($amount_miengiam >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra_miengiam = $so_tien_goc_phai_tra;
				$amount_miengiam = $amount_miengiam - $so_tien_goc_phai_tra;
		 }else 
		    if($amount_miengiam >0 && $so_tien_goc_phai_tra>0 )
		    {
		    	$so_tien_goc_da_tra_miengiam = $amount_miengiam;
                $amount_miengiam = 0;
		    }
		    $so_tien_goc_phai_tra=$so_tien_goc_phai_tra-$so_tien_goc_da_tra_miengiam;
		//-------------------------------------------

		    $this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				'chia_mien_giam'=>[
				"so_tien_phi_gia_han_da_tra" => $phi_gia_han_da_tra_miengiam,
				"so_tien_phi_tat_toan_da_tra" =>$phi_tat_toan_da_tra_miengiam,
				"so_tien_phi_phat_sinh_da_tra" =>$phi_phat_sinh_da_tra_miengiam,
				"so_tien_phi_cham_tra_da_tra"=>$phi_cham_tra_da_tra_miengiam,
				"so_tien_phi_da_tra"=>$so_tien_phi_da_tra_miengiam,
				"so_tien_lai_da_tra"=>$so_tien_lai_da_tra_miengiam,
				"so_tien_goc_da_tra"=>$so_tien_goc_da_tra_miengiam
			    ],
			    'goc_lai_phi_phai_tra'=>$goc_lai_phi_phai_tra

			    )
		    );
		//===============================================
		}

		// Chia tiền lãi các kỳ đầu đã trừ vào kỳ cuối
		if ($amount_lai_tru_ky_cuoi > 0) {
			$so_tien_lai_da_tra_tru_ky_cuoi = 0;
			if ($amount_lai_tru_ky_cuoi >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra_tru_ky_cuoi = $amount_lai_tru_ky_cuoi;
			} elseif ($amount_lai_tru_ky_cuoi > 0 && $so_tien_lai_phai_tra > 0) {
				$so_tien_lai_da_tra_tru_ky_cuoi = $amount_lai_tru_ky_cuoi;
			}
			$so_tien_lai_phai_tra = $so_tien_lai_phai_tra - $so_tien_lai_da_tra_tru_ky_cuoi;
		}

		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($transId)),
			array( 
				
			    'goc_lai_phi_phai_tra'=>$goc_lai_phi_phai_tra,
			    'tong_tien_tat_toan'=>$amount

			    )
		    );
		// $date_ngay_t 24/11/2020
		if($contractDB['disbursement_date'] > $date_ngay_t )
		{
			if($amount >= floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $so_tien_lai_phai_tra;
				$amount = $amount - $so_tien_lai_da_tra;
			}
			if($amount >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $so_tien_phi_phai_tra;
				$amount = $amount - $so_tien_phi_da_tra;
			}
			if($amount >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $so_tien_goc_phai_tra;
				$amount = $amount - $so_tien_goc_da_tra;
			}
			if($amount > 0)
		    {
		    if($so_tien_lai_da_tra < floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $amount;
				$amount = $amount-$so_tien_lai_da_tra;
			}
			if($so_tien_phi_da_tra <   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $amount;
				$amount = $amount-$so_tien_phi_da_tra;
			}
			if($so_tien_goc_da_tra <  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $amount;
				$amount = $amount-$so_tien_goc_da_tra;
			}
		    }
		}else{
			if($amount >=  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $so_tien_goc_phai_tra;
				$amount = $amount - $so_tien_goc_da_tra;
			}
			if($amount >=  floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $so_tien_lai_phai_tra;
				$amount = $amount - $so_tien_lai_da_tra;
			}
			if($amount >=   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $so_tien_phi_phai_tra;
				$amount = $amount - $so_tien_phi_da_tra;
			}
			if($amount > 0)
		    {
		    if($so_tien_goc_da_tra <  floor($so_tien_goc_phai_tra)) {
				$so_tien_goc_da_tra = $amount;
				$amount = $amount-$so_tien_goc_da_tra;
			}
		    if($so_tien_lai_da_tra < floor($so_tien_lai_phai_tra)) {
				$so_tien_lai_da_tra = $amount;
				$amount = $amount-$so_tien_lai_da_tra;
			}
			if($so_tien_phi_da_tra <   floor($so_tien_phi_phai_tra)) {
				$so_tien_phi_da_tra = $amount;
				$amount = $amount-$so_tien_phi_da_tra;
			}
			
		    }

		}
        if($amount >= $phi_gia_han) {
			$phi_gia_han_da_tra = $phi_gia_han;
			$amount = $amount - $phi_gia_han;
		}
		if($amount >= $tong_phi_cham_tra) {
			$phi_cham_tra_da_tra = $tong_phi_cham_tra;
			$amount = $amount - $tong_phi_cham_tra;
		}
		if($amount >= $phi_phat_sinh) {
			$phi_phat_sinh_da_tra = $phi_phat_sinh;
			$amount = $amount - $phi_phat_sinh;
		}
		if($amount >= $feeFinishContract) {
			$phi_tat_toan_da_tra = $feeFinishContract;
			$amount = $amount - $feeFinishContract;
		}
		if($amount > 0)
		{
			if($phi_gia_han_da_tra ==0 && $phi_gia_han>0)
			{
			$phi_gia_han_da_tra = $amount;
           $amount = $amount - $phi_gia_han_da_tra;
			}
			if($phi_cham_tra_da_tra ==0  && $tong_phi_cham_tra>0)
			{
			$phi_cham_tra_da_tra = $amount;
             $amount = $amount - $phi_cham_tra_da_tra;
			}
			if($phi_phat_sinh_da_tra ==0 && $phi_phat_sinh>0)
			{
			$phi_phat_sinh_da_tra = $amount;
           $amount = $amount - $phi_phat_sinh_da_tra;
			}
			if($phi_tat_toan_da_tra ==0 && $feeFinishContract>0 )
			{
			$phi_tat_toan_da_tra = $amount;
             $amount = $amount - $phi_tat_toan_da_tra;
			}
		}

		//Update bảng kì
		$update = array();
		$update['so_tien_goc_da_tra_tat_toan'] = $so_tien_goc_da_tra;
		$update['so_tien_lai_da_tra_tat_toan'] = $so_tien_lai_da_tra;
		$update['so_tien_phi_da_tra_tat_toan'] = $so_tien_phi_da_tra;

		$update['so_tien_phi_gia_han_da_tra_tat_toan'] = $phi_gia_han_da_tra;
		$update['so_tien_phi_cham_tra_da_tra_tat_toan'] = $phi_cham_tra_da_tra;
		$update['so_tien_phi_phat_sinh_da_tra_tat_toan'] = $phi_phat_sinh_da_tra;
		$update['fee_finish_contract'] = $phi_tat_toan_da_tra;
		$update['tien_thua_tat_toan'] = $amount;

		if($currentPlan[0]['status']==1)
		{
            
		    $update['fee_delay_pay']=$phi_phat_tra_cham_ky_hien_tai;
		    $update['so_ngay_cham_tra']=$so_ngay_tra_cham_ky_hien_tai;
		    $update['tien_phi_cham_tra_1ky_con_lai'] = 0;
            $update['tien_phi_cham_tra_1ky_da_tra'] =$phi_phat_tra_cham_ky_hien_tai;
		}
		

		$update['tien_goc_1ky_da_tra'] = $currentPlan[0]['tien_goc_1ky_phai_tra'];
		$update['tien_goc_1ky_con_lai'] = 0;
		$update['tien_lai_1ky_da_tra'] = $currentPlan[0]['tien_lai_1ky_phai_tra'];
		$update['tien_lai_1ky_con_lai'] = 0;
		$update['tien_phi_1ky_da_tra'] = $currentPlan[0]['tien_phi_1ky_phai_tra'];
		$update['tien_phi_1ky_con_lai'] = 0;

        
		$this->so_tien_goc_da_tra_tat_toan = $so_tien_goc_da_tra;
		$this->so_tien_lai_da_tra_tat_toan = $so_tien_lai_da_tra;
		$this->so_tien_phi_da_tra_tat_toan = $so_tien_phi_da_tra;

		$this->tien_thua_tat_toan = $amount;

		$this->so_tien_phi_tat_toan_da_tra = $phi_tat_toan_da_tra;
		$this->so_tien_phi_da_tra = $so_tien_phi_da_tra;
		$this->so_tien_phi_cham_tra_da_tra = $phi_cham_tra_da_tra;
		$this->so_tien_phi_gia_han_da_tra = $phi_gia_han_da_tra;
		$this->so_tien_phi_phat_sinh_da_tra = $phi_phat_sinh_da_tra;
      

		$this->lai_con_no_thuc_te = $lai_con_no_thuc_te;
		$this->phi_con_no_thuc_te = $phi_con_no_thuc_te;
		$this->temporary_plan_contract_model->update(
			array("_id" => $currentPlan[0]['_id']),
			$update
		);
	}



	private function getTongTienTatToan($codeContract, $date_pay)
	{
		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($codeContract, $date_pay);
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($codeContract, $date_pay);

		$du_no_con_lai = !empty($get_infor_tat_toan_part_1['du_no_con_lai']) ? $get_infor_tat_toan_part_1['du_no_con_lai'] : 0;
		$lai_con_no_thuc_te = !empty($get_infor_tat_toan_part_2['lai_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['lai_con_no_thuc_te'] : 0;
		$phi_con_no_thuc_te = !empty($get_infor_tat_toan_part_2['phi_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['phi_con_no_thuc_te'] : 0;

		return $du_no_con_lai + $lai_con_no_thuc_te + $phi_con_no_thuc_te;

	}



	private function cap_nhat_tat_toan_cac_ki_tiep_theo($contractDB,$phi_phat_tra_cham,$date_pay) {
		//Lấy thông tin kì hiện tại
		$currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
		if(!empty($currentPlan)) {

			//Lấy thông tin các kì tiếp theo
			$plansAfter = $this->temporary_plan_contract_model->getCurrentPlanAfter($contractDB['code_contract'], $currentPlan[0]['_id'],$date_pay);
			foreach($plansAfter as $plan) {
				//Gốc đã trả = Gốc phải trả
				//Gốc còn lại = 0
				//Lãi đã trả = Gốc phải trả
				//Lãi còn lại = 0
				//Phí đã trả = Gốc phải trả
				//Phí còn lại = 0
				
				$arr_update=array('tien_goc_1ky_da_tra' => $plan['tien_goc_1ky_phai_tra'],
						'tien_goc_1ky_con_lai' => 0,
						'tien_lai_1ky_da_tra' => $plan['tien_lai_1ky_phai_tra'],
						'tien_lai_1ky_con_lai' => 0,
						'tien_phi_1ky_da_tra' => $plan['tien_phi_1ky_phai_tra'],
						'tien_phi_1ky_con_lai' => 0,
					);

				//Update
				$this->temporary_plan_contract_model->update(
					array('_id' => $plan['_id']),
					$arr_update
				);
			}
		}

	}
    //cập nhật phí chậm trả các kỳ trước đó
	public function cap_nhat_tat_toan_cac_ki_truoc_do($contractDB,$phi_phat_tra_cham,$date_pay) {
		//Lấy thông tin kì hiện tại
		$currentPlan = $this->get_current_plan_tat_toan($contractDB['code_contract'],$date_pay);
		if(!empty($currentPlan)) {

			//Lấy thông tin các kì tiếp theo
			$plansAfter = $this->temporary_plan_contract_model->getCurrentPlanBefore($contractDB['code_contract'], $currentPlan[0]['_id'],$date_pay);
			foreach($plansAfter as $plan) {
				//Gốc đã trả = Gốc phải trả
				//Gốc còn lại = 0
				//Lãi đã trả = Gốc phải trả
				//Lãi còn lại = 0
				//Phí đã trả = Gốc phải trả
				//Phí còn lại = 0
			   $arr_update=	array();
				 if($plan['status']==1)
				{
					$phi_phat_tra_cham_ky_hien_tai=(isset($phi_phat_tra_cham[$plan['ky_tra']]['so_tien'])) ? $phi_phat_tra_cham[$plan['ky_tra']]['so_tien'] : 0;
					$so_ngay_tra_cham_ky_hien_tai=(isset($phi_phat_tra_cham[$plan['ky_tra']]['so_ngay'])) ? $phi_phat_tra_cham[$plan['ky_tra']]['so_ngay'] : 0;

					$arr_update['fee_delay_pay']=$phi_phat_tra_cham_ky_hien_tai;
					$arr_update['tien_phi_cham_tra_1ky_con_lai'] = 0;
                    $arr_update['tien_phi_cham_tra_1ky_da_tra'] =$phi_phat_tra_cham_ky_hien_tai;
                    $arr_update['so_ngay_cham_tra'] =$so_ngay_tra_cham_ky_hien_tai;
				}
				
				//Update
				if(!empty($arr_update))
				$this->temporary_plan_contract_model->update(
					array('_id' => $plan['_id']),
					$arr_update
				);
			}
		}

	}


	private function get_current_plan_tat_toan($code_contract, $date_pay = 0)
	{
		$date_pay = (isset($date_pay)) ? $date_pay : strtotime(date('Y-m-d') . ' 23:59:59');
		$currentPlan = array();
		//Get kì phải thanh toán xa nhất
		$ki_phai_thanh_toan_xa_nhat = $this->temporary_plan_contract_model->getKiPhaiThanhToanXaNhat($code_contract);
		//Khách đã quá hạn tất toán của HĐ
		if (!empty($ki_phai_thanh_toan_xa_nhat[0]['ngay_ky_tra']) && $date_pay > $ki_phai_thanh_toan_xa_nhat[0]['ngay_ky_tra']) {
			$currentPlan = $ki_phai_thanh_toan_xa_nhat;
		} //Lấy thông tin kì hiện tại
		else {
			$currentPlan = $this->temporary_plan_contract_model->getCurrentPlan_top($code_contract, $date_pay);
		}
		return $currentPlan;
	}

	public function process_update_approve_img_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['expertise'] = $this->security->xss_clean(!empty($this->dataPost['expertise']) ? $this->dataPost['expertise'] : array());
		$transaction = $this->transaction_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (!empty($transaction)) {
			$type_transaction = $transaction["type"];
		}

		$heyus = $this->hey_u_model->find_where(array('receipt_code' => $transaction['code']));
		$mic_tnds = $this->mic_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_tnds = $this->vbi_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_utv = $this->vbi_utv_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_sxh = $this->vbi_sxh_model->find_where(array('receipt_code' => $transaction['code']));

		if (!empty($heyus)) {
			foreach ($heyus as $heyu) {
				$this->hey_u_model->update(
					array("_id" => $heyu["_id"]),
					array(
						"status" => 2,
						'sent_approve_at' => $this->createdAt
					)
				);
			}
		}
		if (!empty($mic_tnds)) {
			foreach ($mic_tnds as $mic) {
				$this->mic_tnds_model->update(
					array("_id" => $mic["_id"]),
					array(
						"status" => 2,
						'sent_approve_at' => $this->createdAt
					)
				);
			}
		}
		if (!empty($vbi_tnds)) {
			foreach ($vbi_tnds as $vbi) {
				$this->vbi_tnds_model->update(
					array("_id" => $vbi["_id"]),
					array(
						"status" => 2,
						'sent_approve_at' => $this->createdAt
					)
				);
			}
		}
		if (!empty($vbi_utv)) {
			foreach ($vbi_utv as $vbi_u) {
				$this->vbi_utv_model->update(
					array("_id" => $vbi_u["_id"]),
					array(
						"status" => 2,
						'sent_approve_at' => $this->createdAt
					)
				);
			}
		}
		if (!empty($vbi_sxh)) {
			foreach ($vbi_sxh as $vbi_s) {
				$this->vbi_sxh_model->update(
					array("_id" => $vbi_s["_id"]),
					array(
						"status" => 2,
						'sent_approve_at' => $this->createdAt
					)
				);
			}
		}
		$arrUpdate = array("image_banking.image_expertise" => $this->dataPost['expertise'], "status" => 2, 'sent_approve_at' => $this->createdAt);
		$this->transaction_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			$arrUpdate
		);

		$this->dataPost['status'] = 2;

		$log = array(
			"type" => "contract",
			"action" => "KSNB_send_approve",
			"transaction_ksnb_id" => $this->dataPost['id'],
			"old" => $transaction,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'aaa' => $arrUpdate,
			'type' => $type_transaction,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}
	public function super_unique($array)
	{
		$result = array_map("unserialize", array_unique(array_map("serialize", $array)));

		foreach ($result as $key => $value)
		{
			if ( is_array($value) )
			{
				$result[$key] = $this->super_unique($value);
			}
		}
		return $result;
	}
	public function array_unique_multidimensional($input)
	{
		$serialized = array_map('serialize', $input);
		$unique = array_unique($serialized);
		return array_intersect_key($input, $unique);
	}
	public function get_report_invoice_store_by_day_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
		$total = !empty($this->dataPost['total']) ? $this->dataPost['total'] : "";
		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";
		$type_transaction = !empty($this->dataPost['type_transaction']) ? $this->dataPost['type_transaction'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : "";
		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		} else {
			$condition = array(
				'start' => strtotime(date("m/d/Y") . ' 00:00:00'),
				'end' => strtotime(date("m/d/Y") . ' 23:59:59')
			);
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($total)) {
			$condition['total'] = $total;
		}
		if (!empty($type_transaction)) {
			$condition['type_transaction'] = (int)$type_transaction;
		}
		if (!empty($status)) {
			if ($status != 'new') {
				$condition['status'] = (int)$status;
			} else {
				$condition['status'] = $status;
			}
		}
		
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}

		if (!$all) {
			// neu khong thuoc cac quyen all thi lay ra id store cua user hien tai
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (empty($code_store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['stores'] = (is_array($code_store)) ? $code_store : [$code_store];
		}
		$condition['type'] = 1;
		$pta_vta_not_yet_send_day = $this->pti_vta_bn_model->getPtaVtaSendDay($condition);
		$gic_plt_not_yet_send_day = $this->gic_plt_bn_model->getGicPltSendDay($condition);
		$gic_easy_not_yet_send_day = $this->gic_easy_bn_model->getGicEasySendDay($condition);
		$vbi_sxh_not_yet_send_day = $this->vbi_sxh_model->getVbiSxhSendDay($condition);
		$vbi_utv_not_yet_send_day = $this->vbi_utv_model->getVbiUtvSendDay($condition);
		$vbi_tnds_not_yet_send_day = $this->vbi_tnds_model->getVbiTndsSendDay($condition);
		$mic_tnds_not_yet_send_day = $this->mic_tnds_model->getMicTndsSendDay($condition);
		$heyu_not_yet_send_day = $this->hey_u_model->getSendDay($condition);
//		$transaction = $this->transaction_model->getTransactionStoreByDay($condition, $per_page);
		$transaction_in_time_sent_approve = $this->transaction_model->getTransactionInSentApproveByDay($condition, $per_page);
		$transaction_in = $this->transaction_model->getTransactionInByDay($condition, $per_page);
		$transaction_out = $this->transaction_model->getTransactionOutByDay($condition, $per_page);
		$condition['total_record'] = true;
		$total_result = $this->transaction_model->getTransactionStoreByDay($condition);
		$arr_pti_vta_not_yet_send_all = [];
		$arr_pti_vta_not_yet_send_day = [];
		$arr_gic_plt_not_yet_send_all = [];
		$arr_gic_plt_not_yet_send_day = [];
		$arr_gic_easy_not_yet_send_all = [];
		$arr_gic_easy_not_yet_send_day = [];
		$arr_vbi_sxh_not_yet_send_all = [];
		$arr_vbi_sxh_not_yet_send_day = [];
		$arr_vbi_utv_not_yet_send_all = [];
		$arr_vbi_utv_not_yet_send_day = [];
		$arr_vbi_tnds_not_yet_send_all = [];
		$arr_vbi_tnds_not_yet_send_day = [];
		$arr_mic_tnds_not_yet_send_all = [];
		$arr_mic_tnds_not_yet_send_day = [];
		$arr_heyu_not_yet_send_all = [];
		$arr_heyu_not_yet_send_day = [];
		$arr_data_detail_store_by_day = [];
		$arr_store = [];
		$arr_store_by_day = [];
		$data_export = [];
		if (!empty($pta_vta_not_yet_send_day)) {
			foreach ($pta_vta_not_yet_send_day as $key => $pti_vta_day) {
				if (!isset($pti_vta_day['store'])) {
					$pti_vta_day['store'] = '';
				}
				if (!isset($pti_vta_day['created_by'])) {
					$pti_vta_day['created_by'] = '';
				}
				$pti_vta_day['id'] = (string)$pti_vta_day['_id'];
				$user = $this->user_model->findOne(array('email' => $pti_vta_day['created_by']));
				$pti_vta_day['user_full_name'] = $user['full_name'];
				$pti_vta_day['type'] = 15;
				$store_pti_vta_day = array($pti_vta_day['store']['id']);
				$arr_store_by_day = array_merge($store_pti_vta_day, $arr_store_by_day);
				if ($pti_vta_day->status == 10) {
					array_push($arr_pti_vta_not_yet_send_day, $pti_vta_day);
				}
				$arr_data_detail_store_by_day[$pti_vta_day['store']['id']][$pti_vta_day['created_by']][] = $pti_vta_day;
			}
		}
		if (!empty($gic_plt_not_yet_send_day)) {
			foreach ($gic_plt_not_yet_send_day as $key => $gic_plt_day) {
				if (!isset($gic_plt_day['store'])) {
					$gic_plt_day['store'] = '';
				}
				if (!isset($gic_plt_day['created_by'])) {
					$gic_plt_day['created_by'] = '';
				}
				$gic_plt_day['id'] = (string)$gic_plt_day['_id'];
				$user = $this->user_model->findOne(array('email' => $gic_plt_day['created_by']));
				$gic_plt_day['user_full_name'] = $user['full_name'];
				$gic_plt_day['type'] = 14;
				$store_gic_plt_day = array($gic_plt_day['store']['id']);
				$arr_store_by_day = array_merge($store_gic_plt_day, $arr_store_by_day);
				if ($gic_plt_day->status == 10) {
					array_push($arr_gic_plt_not_yet_send_day, $gic_plt_day);
				}
				$arr_data_detail_store_by_day[$gic_plt_day['store']['id']][$gic_plt_day['created_by']][] = $gic_plt_day;
			}
		}
		if (!empty($gic_easy_not_yet_send_day)) {
			foreach ($gic_easy_not_yet_send_day as $key => $gic_easy_day) {
				if (!isset($gic_easy_day['store'])) {
					$gic_easy_day['store'] = '';
				}
				if (!isset($gic_easy_day['created_by'])) {
					$gic_easy_day['created_by'] = '';
				}
				$gic_easy_day['id'] = (string)$gic_easy_day['_id'];
				$user = $this->user_model->findOne(array('email' => $gic_easy_day['created_by']));
				$gic_easy_day['user_full_name'] = $user['full_name'];
				$gic_easy_day['type'] = 13;
				$store_gic_easy_day = array($gic_easy_day['store']['id']);
				$arr_store_by_day = array_merge($store_gic_easy_day, $arr_store_by_day);
				if ($gic_easy_day->status == 10) {
					array_push($arr_gic_easy_not_yet_send_day, $gic_easy_day);
				}
				$arr_data_detail_store_by_day[$gic_easy_day['store']['id']][$gic_easy_day['created_by']][] = $gic_easy_day;
			}
		}
		if (!empty($vbi_sxh_not_yet_send_day)) {
			foreach ($vbi_sxh_not_yet_send_day as $key => $vbi_sxh_day) {
				if (!isset($vbi_sxh_day['store'])) {
					$vbi_sxh_day['store'] = '';
				}
				if (!isset($vbi_sxh_day['created_by'])) {
					$vbi_sxh_day['created_by'] = '';
				}
				$vbi_sxh_day['id'] = (string)$vbi_sxh_day['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_sxh_day['created_by']));
				$vbi_sxh_day['user_full_name'] = $user['full_name'];
				$vbi_sxh_day['type'] = 12;
				$store_vbi_sxh_day = array($vbi_sxh_day['store']['id']);
				$arr_store_by_day = array_merge($store_vbi_sxh_day, $arr_store_by_day);
				if ($vbi_sxh_day->status == 10) {
					array_push($arr_vbi_sxh_not_yet_send_day, $vbi_sxh_day);
				}
				$arr_data_detail_store_by_day[$vbi_sxh_day['store']['id']][$vbi_sxh_day['created_by']][] = $vbi_sxh_day;
			}
		}
		if (!empty($vbi_utv_not_yet_send_day)) {
			foreach ($vbi_utv_not_yet_send_day as $key => $vbi_utv_day) {
				if (!isset($vbi_utv_day['store'])) {
					$vbi_utv_day['store'] = '';
				}
				if (!isset($vbi_utv_day['created_by'])) {
					$vbi_utv_day['created_by'] = '';
				}
				$vbi_utv_day['id'] = (string)$vbi_utv_day['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$vbi_utv_day['user_full_name'] = $user['full_name'];
				$vbi_utv_day['type'] = 11;
				$store_vbi_utv_day = array($vbi_utv_day['store']['id']);
				$arr_store_by_day = array_merge($store_vbi_utv_day, $arr_store_by_day);
				if ($vbi_utv_day->status == 10) {
					array_push($arr_vbi_utv_not_yet_send_day, $vbi_utv_day);
				}
				$arr_data_detail_store_by_day[$vbi_utv_day['store']['id']][$vbi_utv_day['created_by']][] = $vbi_utv_day;
			}
		}
		if (!empty($vbi_tnds_not_yet_send_day)) {
			foreach ($vbi_tnds_not_yet_send_day as $key => $vbi_tnds_day) {
				if (!isset($vbi_tnds_day['store'])) {
					$vbi_tnds_day['store'] = '';
				}
				if (!isset($vbi_tnds_day['created_by'])) {
					$vbi_tnds_day['created_by'] = '';
				}
				$vbi_tnds_day['id'] = (string)$vbi_tnds_day['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$vbi_tnds_day['user_full_name'] = $user['full_name'];
				$vbi_tnds_day['type'] = 10;
				$store_vbi_day = array($vbi_tnds_day['store']['id']);
				$arr_store_by_day = array_merge($store_vbi_day, $arr_store_by_day);
				if ($vbi_tnds_day->status == 10) {
					array_push($arr_vbi_tnds_not_yet_send_day, $vbi_tnds_day);
				}
				$arr_data_detail_store_by_day[$vbi_tnds_day['store']['id']][$vbi_tnds_day['created_by']][] = $vbi_tnds_day;
			}
		}
		if (!empty($mic_tnds_not_yet_send_day)) {
			foreach ($mic_tnds_not_yet_send_day as $key => $mic_tnds_day) {
				if (!isset($mic_tnds_day['store'])) {
					$mic_tnds_day['store'] = '';
				}
				if (!isset($mic_tnds_day['created_by'])) {
					$mic_tnds_day['created_by'] = '';
				}
				$mic_tnds_day['id'] = (string)$mic_tnds_day['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$mic_tnds_day['user_full_name'] = $user['full_name'];
				$mic_tnds_day['type'] = 8;
				$store_mic_day = array($mic_tnds_day['store']['id']);
				$arr_store_by_day = array_merge($store_mic_day, $arr_store_by_day);
				if ($mic_tnds_day->status == 10) {
					array_push($arr_mic_tnds_not_yet_send_day, $mic_tnds_day);
				}
				$arr_data_detail_store_by_day[$mic_tnds_day['store']['id']][$mic_tnds_day['created_by']][] = $mic_tnds_day;
			}
		}
		if (!empty($heyu_not_yet_send_day)) {
			foreach ($heyu_not_yet_send_day as $key => $heyu_day) {
				if (!isset($heyu_day['store'])) {
					$heyu_day['store'] = '';
				}
				if (!isset($heyu_day['created_by'])) {
					$heyu_day['created_by'] = '';
				}
				$heyu_day['id'] = (string)$heyu_day['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$heyu_day['user_full_name'] = $user['full_name'];
				$heyu_day['type'] = 7;
				$store_heyu_day = array($heyu_day['store']['id']);
				$arr_store_by_day = array_merge($store_heyu_day, $arr_store_by_day);
				if ($heyu_day->status == 10) {
					array_push($arr_heyu_not_yet_send_day, $heyu_day);
				}
				$arr_data_detail_store_by_day[$heyu_day['store']['id']][$heyu_day['created_by']][] = $heyu_day;
			}
		}
		$arr_return_amount = [];
		$arr_all_not_yet_send_transaction = [];
		$arr_all_in_transaction = [];
		$arr_all_out_transaction = [];
		$arr_return_amount_day_by_store = [];
		$arr_not_yet_send_day_store_transaction = [];
		$arr_in_day_store_transaction = [];
		$arr_out_day_store_transaction = [];
		if (!empty($transaction_in_time_sent_approve)) {
			foreach ($transaction_in_time_sent_approve as $key => $tran_time_sent) {
				if (!isset($tran_time_sent['store'])) {
					$tran_time_sent['store'] = '';
				}
				if (!isset($tran_time_sent['created_by'])) {
					$tran_time_sent['created_by'] = '';
				}
				$tran_time_sent['id'] = (string)$tran_time_sent['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$tran_time_sent['user_full_name'] = $user['full_name'];
				$store_tran_time_sent = array($tran_time_sent['store']['id']);
				$arr_store_by_day = array_merge($store_tran_time_sent, $arr_store_by_day);
				if ((date("d/m/Y", $tran_time_sent['sent_approve_at']) != date("d/m/Y", $tran_time_sent['created_at'])) && in_array($tran_time_sent['status'], [2])) {
					array_push($arr_in_day_store_transaction, $tran_time_sent);
					$arr_data_detail_store_by_day[$tran_time_sent['store']['id']][$tran_time_sent['created_by']][] = $tran_time_sent;
				}
				if ((date("d/m/Y", $tran_time_sent['sent_approve_at']) != date("d/m/Y", $tran_time_sent['created_at'])) && in_array($tran_time_sent['status'], [4,11])) {
					array_push($arr_not_yet_send_day_store_transaction, $tran_time_sent);
					$arr_data_detail_store_by_day[$tran_time_sent['store']['id']][$tran_time_sent['created_by']][] = $tran_time_sent;
				}
			}
		}
		if (!empty($transaction_in)) {
			foreach ($transaction_in as $key => $tran_in) {
				if (!isset($tran_in['store'])) {
					$tran_in['store'] = '';
				}
				if (!isset($tran_in['created_by'])) {
					$tran_in['created_by'] = '';
				}
				$tran_in['id'] = (string)$tran_in['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$tran_in['user_full_name'] = $user['full_name'];
				$store_tran_in = array($tran_in['store']['id']);
				$arr_store_by_day = array_merge($store_tran_in, $arr_store_by_day);
				
				if (in_array($tran_in->status, [2]) && (empty($tran_in->sent_approve_at))) {
					array_push($arr_in_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
				if (in_array($tran_in->status, [2])&& (date('d/m/Y', $tran_in->sent_approve_at) == date('d/m/Y', $tran_in->created_at))) {
					array_push($arr_in_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
				if (in_array($tran_in->status, [4])) {
					array_push($arr_not_yet_send_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
				if (in_array($tran_in->status, [11]) && (empty($tran_in->sent_approve_at))) {
					array_push($arr_not_yet_send_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
				if (in_array($tran_in->status, [11]) && (date('d/m/Y', $tran_in->sent_approve_at) == date('d/m/Y', $tran_in->created_at))) {
					array_push($arr_not_yet_send_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
				if ($tran_in->type == 1) {
					array_push($arr_out_day_store_transaction, $tran_in);
					$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				}
			}
		}
		if (!empty($transaction_out)) {
			foreach ($transaction_out as $key => $tran_out) {
				if (!isset($tran_out['store'])) {
					$tran_out['store'] = '';
				}
				if (!isset($tran_out['created_by'])) {
					$tran_out['created_by'] = '';
				}
				$tran_out['id'] = (string)$tran_out['_id'];
				$user = $this->user_model->findOne(array('email' => $vbi_utv_day['created_by']));
				$tran_out['user_full_name'] = $user['full_name'];
				$store_tran_out = array($tran_out['store']['id']);
				$arr_store_by_day = array_merge($store_tran_out, $arr_store_by_day);
				if ($tran_out->status == 1) {
					array_push($arr_out_day_store_transaction, $tran_out);
					$arr_data_detail_store_by_day[$tran_out['store']['id']][$tran_out['created_by']][] = $tran_out;
				}

				if ((date('d/m/Y', $tran_out['created_at']) != date('d/m/Y', $tran_out['approved_at'])) && in_array($tran_out['status'], [1,11])) {
					$arr_data_detail_store_by_day[$tran_out['store']['id']][$tran_out['created_by']][] = $tran_out;
				}
				if ((date('d/m/Y', $tran_out['created_at']) != date('d/m/Y', $tran_out['approved_at'])) && in_array($tran_out['status'], [11])) {
					array_push($arr_not_yet_send_day_store_transaction, $tran_out);
				}
			}
		}
		//Lọc các phần tử trùng trong mảng tiền thu chi tiết trong ngày và excel
		$detail_day = $this->super_unique($arr_data_detail_store_by_day);
		$arr_store_filter = [];
		if (!empty($arr_store)) {
			foreach (array_unique($arr_store) as $key => $arr_st) {
				array_push($arr_store_filter, $arr_st);
			}
			foreach ($arr_store_filter as $key => $arr_st) {
				if ($arr_st == '5ecbda18d6612b0fd70b891f' || $arr_st == '5e593293d6612b26016acaae' || $arr_st == '5eb8f2ebd6612b350f5932e3' || $arr_st == '') {
					unset($arr_store_filter[$key]);
				}
			}
		}
		$arr_store_filter_by_day = [];
		if (!empty($arr_store_by_day)) {
			foreach (array_unique($arr_store_by_day) as $key => $arr_st) {
				array_push($arr_store_filter_by_day, $arr_st);
			}
			foreach ($arr_store_filter_by_day as $key => $arr_st) {
				if ($arr_st == '5ecbda18d6612b0fd70b891f' || $arr_st == '5e593293d6612b26016acaae' || $arr_st == '5eb8f2ebd6612b350f5932e3' || $arr_st == '') {
					unset($arr_store_filter_by_day[$key]);
				}
			}
		}
		// Thống kê tiền thu theo ngày, lọc các bản ghi trùng.
		$contract_not_yet_send_day = $this->array_unique_multidimensional($arr_not_yet_send_day_store_transaction);
		$heyu_not_yet_send_day = $this->array_unique_multidimensional($arr_heyu_not_yet_send_day);
		$mic_tnds_not_yet_send_day = $this->array_unique_multidimensional($arr_mic_tnds_not_yet_send_day);
		$vbi_tnds_not_yet_send_day = $this->array_unique_multidimensional($arr_vbi_tnds_not_yet_send_day);
		$vbi_utv_not_yet_send_day = $this->array_unique_multidimensional($arr_vbi_utv_not_yet_send_day);
		$vbi_sxh_not_yet_send_day = $this->array_unique_multidimensional($arr_vbi_sxh_not_yet_send_day);
		$gic_easy_not_yet_send_day = $this->array_unique_multidimensional($arr_gic_easy_not_yet_send_day);
		$gic_plt_not_yet_send_day = $this->array_unique_multidimensional($arr_gic_plt_not_yet_send_day);
		$pta_vta_not_yet_send_day = $this->array_unique_multidimensional($arr_pti_vta_not_yet_send_day);
		$in_day = $this->array_unique_multidimensional($arr_in_day_store_transaction);
		$out_day = $this->array_unique_multidimensional($arr_out_day_store_transaction);
		$stor = 0;
		if (!empty($arr_store_filter_by_day))
			foreach ($arr_store_filter_by_day as $key => $value) {
				$stor++;
				$arr_code_day = get_values($transaction_in, 'store', $key, '', '', '', '', 'code', true, false);
				if (count($arr_code_day) <= 0)
					$arr_code_day = ['1'];
				$total_cod_contract_not_yet_send_day_by_store = sum_values_transaction($contract_not_yet_send_day, 'store', $value, '', '', '', '', 'total');
				$total_cod_heyu_not_yet_send_day_by_store = sum_values_transaction($heyu_not_yet_send_day, 'store', $value, '', '', '', '', 'money');
				$total_cod_mic_tnds_not_yet_send_day_by_store = sum_values_transaction($mic_tnds_not_yet_send_day, 'store', $value, '', '', '', '', 'mic_fee');
				$total_cod_vbi_tnds_not_yet_send_day_by_store = sum_values_transaction($vbi_tnds_not_yet_send_day, 'store', $value, '', '', '', '', 'fee');
				$total_cod_vbi_utv_not_yet_send_day_by_store = sum_values_transaction($vbi_utv_not_yet_send_day, 'store', $value, '', '', '', '', 'fee');
				$total_cod_vbi_sxh_not_yet_send_day_by_store = sum_values_transaction($vbi_sxh_not_yet_send_day, 'store', $value, '', '', '', '', 'fee');
				$total_cod_gic_easy_not_yet_send_day_by_store = sum_values_transaction($gic_easy_not_yet_send_day, 'store', $value, '', '', '', '', 'price');
				$total_cod_gic_plt_not_yet_send_day_by_store = sum_values_transaction($gic_plt_not_yet_send_day, 'store', $value, '', '', '', '', 'price');
				$total_cod_pti_vat_not_yet_send_day_by_store = sum_values_transaction($pta_vta_not_yet_send_day, 'store', $value, '', '', '', '', 'price');
				$total_cod_not_yet_send_day_by_store = $total_cod_pti_vat_not_yet_send_day_by_store + $total_cod_gic_plt_not_yet_send_day_by_store + $total_cod_gic_easy_not_yet_send_day_by_store + $total_cod_vbi_sxh_not_yet_send_day_by_store + $total_cod_vbi_utv_not_yet_send_day_by_store + $total_cod_vbi_tnds_not_yet_send_day_by_store + $total_cod_mic_tnds_not_yet_send_day_by_store + $total_cod_heyu_not_yet_send_day_by_store + $total_cod_contract_not_yet_send_day_by_store;
				$total_cod_in_day_by_store = sum_values_transaction($in_day, 'store', $value, '', '', '', '', 'total');
				$total_cod_out_day_by_store = sum_values_transaction($out_day, 'store', $value, '', '', '', '', 'total');
				$total_cod_day = $total_cod_not_yet_send_day_by_store + $total_cod_in_day_by_store + $total_cod_out_day_by_store;
				$arr_return_amount_day_by_store += [$stor => [
					'store' => $this->get_name_store_by_id($value),
					'total_cod_not_yet_send_day' => $total_cod_not_yet_send_day_by_store,
					'total_cod_in_day' => $total_cod_in_day_by_store,
					'total_cod_out_day' => $total_cod_out_day_by_store,
					'total_cod_day' => $total_cod_day
				]];
			}
		usort($arr_return_amount, function ($a, $b) {
			return $b['total_cod'] <=> $a['total_cod'];
		});
		usort($arr_return_amount_day_by_store, function ($a, $b) {
			return $b['total_cod_day'] <=> $a['total_cod_day'];
		});
		$arr_parent = [];
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'groupRoles' => $groupRoles,
			'total_result' => $total_result,
			'data' => $detail_day,
			'total_parent' => $arr_return_amount_day_by_store,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function get_all_name_store()
	{
		$store = $this->store_model->find_where_in('status', ['active', 'deactive']);
		if (!empty($store)) {
			foreach ($store as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		return $store;
	}

	private function get_name_store_by_id($id)
	{
		$storeData = $this->get_all_name_store();
		if (!empty($storeData)) {
			foreach ($storeData as $key => $storeDatum) {
				if ($id == $storeDatum['id']) {
					return $storeDatum['name'];
				}
			}
		}
	}

	public function get_all_kt_hey_u_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$email = !empty($this->dataPost['email']) ? $this->dataPost['email'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$tab = !empty($this->dataPost['tab']) ? $this->dataPost['tab'] : "";
		$code_transaction_bank = !empty($this->dataPost['code_transaction_bank']) ? $this->dataPost['code_transaction_bank'] : "";
		$code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
		$total_tran = 0;
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}

		if (!empty($tab)) {
			$condition['tab'] = $tab;
		}
		if (!empty($email)) {
			$condition['email'] = $email;
		}

		if (!empty($code_transaction_bank)) {
			$condition['code_transaction_bank'] = $code_transaction_bank;
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (in_array('quan-ly-khu-vuc', $groupRoles)) {
			$stores = $this->getStores_list($this->id);
			$condition['stores'] = $stores;
		}

		//$condition['type'] = array(7,8,10,11,12);
		$transaction = $this->transaction_model->getTransactionHeyU($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->transaction_model->getTransactionHeyU($condition);
		if (!empty($transaction)) {
			foreach ($transaction as $tran) {
				$tran['id'] = (string)$tran['_id'];
				if (!empty($tran['type'])) {
					if ($tran['status'] == 1) {
						$tran['progress'] = 'Thành công';
					} else if ($tran['status'] == 2) {
						$tran['progress'] = 'Đang chờ';
					} else if ($tran['status'] == 3) {
						$tran['progress'] = 'Đã hủy';
					} else {
						$tran['progress'] = 'Thất bại';
					}
				} else {
					$code = (isset($tran['code'])) ? $tran['code'] : "";
					$orders = $this->order_model->find_where(array('transaction_code' => $code));
					$tran['progress'] = 'Đang chờ';
					$check = false;
					$i = 0;
					foreach ($orders as $or) {
						if (isset($or['response_error']) && $or['status'] !== 'success') {
							$check = true;
						} else if ($or['status'] === 'success') {
							$i++;
						} else if ($or['status'] == 'failed') {
							$check = true;
						}
					}
					if ($check) {
						$tran['progress'] = 'Lỗi';
					} elseif ($i == count($orders)) {
						$tran['progress'] = 'Thành công';
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
			'groupRoles' => $groupRoles,
			'total' => $total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_detail_heyU_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$heyus = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$heyus = $this->hey_u_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($heyus)) {
			foreach ($heyus as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'heyUData' => $heyus,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_heyu_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//quyền chỉ view kế toán
		if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(
					"msg" => "Không có quyền truy cập"
				)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['transaction_id'] = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['code_transaction_bank'] = $this->security->xss_clean($this->dataPost['code_transaction_bank']);
		$this->dataPost['bank'] = $this->security->xss_clean($this->dataPost['bank']);
		$this->dataPost['approve_note'] = $this->security->xss_clean($this->dataPost['approve_note']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['transaction_id'])));
		$heyus = $this->hey_u_model->find_where(array('receipt_code' => $transaction['code']));
		$mic_tnds = $this->mic_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_tnds = $this->vbi_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_utv = $this->vbi_utv_model->find_where(array('receipt_code' => $transaction['code']));
		$vbi_sxh = $this->vbi_sxh_model->find_where(array('receipt_code' => $transaction['code']));
		$gic_easy = $this->gic_easy_bn_model->find_where(array('receipt_code' => $transaction['code']));
		$gic_plt = $this->gic_plt_bn_model->find_where(array('receipt_code' => $transaction['code']));
		$pti_vta = $this->pti_vta_bn_model->find_where(array('receipt_code' => $transaction['code'],'type_pti' => ['$in' => ["BN", "WEB"]]));
		$code_transaction_bank_clean = trim($this->dataPost['code_transaction_bank']);
		if (empty($transaction) || $transaction['status'] != 2) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'msg' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($this->dataPost['code_transaction_bank'])) {
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $code_transaction_bank_clean, "status" => array('$ne' => 3), "code" => array('$ne' => $transaction['code'])));
			if (!empty($transaction_ck_pt)) {
				foreach ($transaction_ck_pt as $key => $value) {
					if (date("Ymd", $value['created_at']) != date("Ymd", $transaction['created_at'])) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'msg' => "Đã tồn tại mã giao dịch ngân hàng khác ngày!"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
					if (date("Ymd", $value['created_at']) == date("Ymd", $transaction['created_at'])) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'msg' => "Đã tồn tại mã giao dịch ngân hàng cùng ngày!"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
		//Update transaction
		$transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			array(
				"code_transaction_bank" => trim($this->dataPost['code_transaction_bank']),
				"bank" => $this->dataPost['bank'],
				"approve_note" => $this->dataPost['approve_note'],
				"status" => (int)$this->dataPost['status'],
				"approved_at" => $this->createdAt,
				"approved_by" => $this->uemail,
			)
		);

		if ((int)$this->dataPost['status'] == 1) {
			if (!empty($heyus)) {
				foreach ($heyus as $heyu) {
					$this->hey_u_model->update(
						array("_id" => $heyu["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($mic_tnds)) {
				foreach ($mic_tnds as $mic) {
					$this->mic_tnds_model->update(
						array("_id" => $mic["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($vbi_tnds)) {
				foreach ($vbi_tnds as $vbi) {
					$this->vbi_tnds_model->update(
						array("_id" => $vbi["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($vbi_utv)) {
				foreach ($vbi_utv as $utv) {
					$this->vbi_utv_model->update(
						array("_id" => $utv["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($vbi_sxh)) {
				foreach ($vbi_sxh as $sxh) {
					$this->vbi_sxh_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($gic_easy)) {
				foreach ($gic_easy as $sxh) {
					$this->gic_easy_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($gic_plt)) {
				foreach ($gic_plt as $sxh) {
					$this->gic_plt_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 1
						)
					);
				}
			}
			if (!empty($pti_vta)) {
				foreach ($pti_vta as $sxh) {
					$ptiInsurance = $sxh;
					if ($ptiInsurance['type_pti'] == "WEB") {
						$NGAY_HL = date('d-m-Y', strtotime("+1 days"));
						$pti_vta = $this->insert_pti_vta($ptiInsurance['data_origin'], $NGAY_HL, $ptiInsurance['type_pti'], $ptiInsurance['code_pti_vta'], $ptiInsurance['number_item']);
						if ($pti_vta->success == true) {
							$pti = $pti_vta->data;
							$request = $pti_vta->request;
							$NGAY_KT = $pti_vta->NGAY_KT;
							$this->pti_vta_bn_model->update([
								"_id" => $sxh["_id"]
							], [
								'request' => $request,
								'NGAY_KT' => $NGAY_KT,
								'NGAY_HL' => $NGAY_HL,
								'pti_info' => $pti,
								'status' => 1,
								'money_tranfer' => $ptiInsurance['price'],
								'customer_info' => [
									'customer_name' => !empty($request->ten) ? $request->ten : '',
									'customer_phone' => !empty($request->phone) ? $request->phone : '',
									'card' => !empty($request->so_cmt) ? $request->so_cmt : '',
									'email' => !empty($request->email) ? $request->email : '',
									'birthday' => !empty($request->ngay_sinh) ? $request->ngay_sinh : ''
								]
							]);
						}
					} else {
						$this->pti_vta_bn_model->update([
							"_id" => $sxh["_id"]
						], [
							'status' => 1,
						]);
					}
				}
			}
			$url = $this->dataPost['transaction_id'];
			$message = 'Duyệt thanh toán thành công';
		} elseif ((int)$this->dataPost['status'] == 11) {
			if (!empty($heyus)) {
				foreach ($heyus as $heyu) {
					$this->hey_u_model->update(
						array("_id" => $heyu["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($mic_tnds)) {
				foreach ($mic_tnds as $mic) {
					$this->mic_tnds_model->update(
						array("_id" => $mic["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($vbi_tnds)) {
				foreach ($vbi_tnds as $vbi) {
					$this->vbi_tnds_model->update(
						array("_id" => $vbi["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($vbi_utv)) {
				foreach ($vbi_utv as $utv) {
					$this->vbi_utv_model->update(
						array("_id" => $utv["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($vbi_sxh)) {
				foreach ($vbi_sxh as $sxh) {
					$this->vbi_sxh_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($gic_easy)) {
				foreach ($gic_easy as $sxh) {
					$this->gic_easy_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($gic_plt)) {
				foreach ($gic_plt as $sxh) {
					$this->gic_plt_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			if (!empty($pti_vta)) {
				foreach ($pti_vta as $sxh) {
					$this->pti_vta_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 11
						)
					);
				}
			}
			$url = $this->dataPost['transaction_id'];
			$message = 'Phiếu thu đã được trả lại phòng giao dịch!';
			$arr_update_pending = [
				"transaction_id" => (string)$transaction['_id'],
				"code" => $transaction['code'],
				"total" => $transaction['total'],
				"type" => $transaction['type'],
				"payment_method" => $transaction['payment_method'],
				"store" => $transaction['store'],
				"status" => (int)$this->dataPost['status'],
				"note" => $this->dataPost['approve_note'],
				"created_by" => $transaction['created_by'],
				"created_at" => $transaction['created_at'],
				"tran_created_at" => $this->createdAt,
				"tran_created_by" => $this->uemail,
			];
			$transaction_pending_id = $this->transaction_pending_model->insertReturnId($arr_update_pending);
			$this->log_model->insert(
				[
					"type" => "transaction_pending",
					"action" => 'insert',
					"transaction_id" => (string)$transaction['_id'],
					"old" => $transaction,
					"new" => $arr_update_pending,
					"email" => $this->uemail,
					"created_at" => $this->createdAt
				]
			);

			$user_find = $this->user_model->findOne(['email' => $transaction['created_by'], 'status' => 'active']);
			$user_creat[] = (string)$user_find['_id'];
			$get_user_id_store = $this->getUserIdbyStores($transaction['store']['id']);
			$user_id_store_mix = array_merge($user_creat, $get_user_id_store);
			$user_id_store = array_unique($user_id_store_mix);
			$status = (int)$this->dataPost['status'];
			$note = "Kế toán trả về phiếu thu thanh toán";
			$link = "";
			if (!empty($transaction['type'])) {
				if ($transaction['type'] == 7) {
					$link = 'heyU?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Thanh toán Nạp tiền tài xế HeyU";
				} elseif ($transaction['type'] == 8) {
					$link = 'mic_tnds?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm Xe máy - MIC_TNDS";
				} elseif ($transaction['type'] == 10) {
					$link = 'vbi_tnds?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm Ô tô - VBI_TNDS";
				} elseif ($transaction['type'] == 11) {
					$link = 'baoHiemVbi/utv?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm VBI_Ung thư vú";
				} elseif ($transaction['type'] == 12) {
					$link = 'baoHiemVbi/sxh?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm VBI_Sốt xuất huyết";
				} elseif ($transaction['type'] == 13) {
					$link = 'gic_easy?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm GIC EASY";
				} elseif ($transaction['type'] == 14) {
					$link = 'gic_plt?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm GIC Phúc Lộc Thọ";
				} elseif ($transaction['type'] == 15) {
					$link = 'pti_vta?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán trả về phiếu thu Bảo hiểm PTI Vững Tâm An";
				} else {
					$link = 'https://cpanel.tienngay.vn/';
					$note = "";
				}
			}
			// Push notification về phòng giao dịch
			$this->push_notification_to_store($user_id_store, $transaction, $status, $note, $link);
			// Gửi email về phòng giao dịch
			$this->sendEmailReturnTransactionInsurance_store($transaction, $this->dataPost['approve_note'], $transaction['code'], 'Trả về phòng giao dịch');
		} elseif ((int)$this->dataPost['status'] == 3) {
			if (!empty($heyus)) {
				foreach ($heyus as $heyu) {
					$this->hey_u_model->update(
						array("_id" => $heyu["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($mic_tnds)) {
				foreach ($mic_tnds as $mic) {
					$this->mic_tnds_model->update(
						array("_id" => $mic["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($vbi_tnds)) {
				foreach ($vbi_tnds as $vbi) {
					$this->vbi_tnds_model->update(
						array("_id" => $vbi["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($vbi_utv)) {
				foreach ($vbi_utv as $utv) {
					$this->vbi_utv_model->update(
						array("_id" => $utv["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($vbi_sxh)) {
				foreach ($vbi_sxh as $sxh) {
					$this->vbi_sxh_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($gic_easy)) {
				foreach ($gic_easy as $sxh) {
					$this->gic_easy_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($gic_plt)) {
				foreach ($gic_plt as $sxh) {
					$this->gic_plt_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			if (!empty($pti_vta)) {
				foreach ($pti_vta as $sxh) {
					$this->pti_vta_bn_model->update(
						array("_id" => $sxh["_id"]),
						array(
							"status" => 3
						)
					);
				}
			}
			$url = $this->dataPost['transaction_id'];
			$message = 'Hủy giao dịch thành công!!';
			$user_find = $this->user_model->findOne(['email' => $transaction['created_by'], 'status' => 'active']);
			$user_creat[] = (string)$user_find['_id'];
			$get_user_id_store = $this->getUserIdbyStores($transaction['store']['id']);
			$user_id_store_mix = array_merge($user_creat, $get_user_id_store);
			$user_id_store = array_unique($user_id_store_mix);
			$status = (int)$this->dataPost['status'];
			$note = "Kế toán đã hủy phiếu thu thanh toán";
			$link = "";
			if (!empty($transaction['type'])) {
				if ($transaction['type'] == 7) {
					$link = 'heyU?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Thanh toán Nạp tiền tài xế HeyU";
				} elseif ($transaction['type'] == 8) {
					$link = 'mic_tnds?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm Xe máy - MIC_TNDS";
				} elseif ($transaction['type'] == 10) {
					$link = 'vbi_tnds?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm Ô tô - VBI_TNDS";
				} elseif ($transaction['type'] == 11) {
					$link = 'baoHiemVbi/utv?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm VBI_Ung thư vú";
				} elseif ($transaction['type'] == 12) {
					$link = 'baoHiemVbi/sxh?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm VBI_Sốt xuất huyết";
				} elseif ($transaction['type'] == 13) {
					$link = 'gic_easy?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm GIC EASY";
				} elseif ($transaction['type'] == 14) {
					$link = 'gic_plt?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm GIC Phúc Lộc Thọ";
				} elseif ($transaction['type'] == 15) {
					$link = 'pti_vta?tab=transaction&code_transaction='. $transaction['code'];
					$note = "Kế toán đã hủy phiếu thu Bảo hiểm PTI Vững Tâm An";
				} else {
					$link = 'https://cpanel.tienngay.vn/';
					$note = "";
				}
			}
			// Push notification về phòng giao dịch
			$this->push_notification_to_store($user_id_store, $transaction, $status, $note, $link);
		}
		//Write log
		$log = array(
			"type" => "transaction_insurance",
			"action" => 'approve',
			'old' => $transaction,
			'new' => $this->dataPost,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'msg' => $message,
			'url' => $url,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function return_transaction_store_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['transaction_id'] = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['approve_note'] = $this->security->xss_clean($this->dataPost['approve_note']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['transaction_id'])));

		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'msg' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));
		//Update transaction
		$transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			array(
				"approve_note" => $this->dataPost['approve_note'],
				"status" => (int)$this->dataPost['status'],
				"updated_at" => $this->createdAt,
				"updated_by" => $this->uemail,
				"approved_at" => $this->createdAt,
				"approved_by" => $this->uemail
			)
		);
		//Push notification
		$user_find = $this->user_model->findOne(['email' => $transaction['created_by'], 'status' => 'active']);
		$user_creat[] = (string)$user_find['_id'];
		$get_user_id_store = $this->getUserIdbyStores($transaction['store']['id']);
		$user_id_store_mix = array_merge($user_creat, $get_user_id_store);
		$user_id_store = array_unique($user_id_store_mix);
		$status = (int)$this->dataPost['status'];
		$note = "Kế toán trả về phiếu thu thanh toán";
		$link = "transaction?tab=return&fdate=&tdate=&code=". $transaction['code'];
		$this->push_notification_to_store($user_id_store, $transaction, $status, $note, $link);
		//Gửi email về PGD
		$this->sendEmailReturnTransaction_store($contractDB, $this->dataPost['approve_note'], $transaction, 'Trả về phòng giao dịch');
		$url = $this->dataPost['transaction_id'];
		$message = 'Phiếu thu đã được trả lại Phòng giao dịch!';
		$arr_update_pending = [
			"transaction_id" => (string)$transaction['_id'],
			"code" => $transaction['code'],
			"total" => $transaction['total'],
			"customer_name" => $transaction['customer_name'],
			"code_contract" => $transaction['code_contract'],
			"code_contract_disbursement" => $transaction['code_contract_disbursement'],
			"type" => $transaction['type'],
			"type_payment" => $transaction['type_payment'],
			"payment_method" => $transaction['payment_method'],
			"store" => $transaction['store'],
			"status" => (int)$this->dataPost['status'],
			"customer_bill_phone" => $transaction['customer_bill_phone'],
			"note" => $this->dataPost['approve_note'],
			"created_by" => $transaction['created_by'],
			"created_at" => $transaction['created_at'],
			"sent_approve_at" => $transaction['sent_approve_at'],
			"tran_created_at" => $this->createdAt,
			"tran_created_by" => $this->uemail,
		];
		//Insert bảng QL phiếu thu pending
		$transaction_pending_id = $this->transaction_pending_model->insertReturnId($arr_update_pending);
		$this->log_model->insert(
			[
				"type" => "transaction_pending",
				"action" => 'insert',
				"transaction_id" => (string)$transaction['_id'],
				"old" => $transaction,
				"new" => $arr_update_pending,
				"email" => $this->uemail,
				"created_at" => $this->createdAt
			]
		);
		//Write log
		$log = array(
			"type" => "transaction",
			"action" => 'update',
			"transaction_id" => (string)$this->dataPost['transaction_id'],
			"old" => $transaction,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$log_ksnb = array(
			"type" => "contract",
			"action" => "KT trả về",
			"transaction_ksnb_id" => (string)$this->dataPost['transaction_id'],
			"old" => $transaction,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log_ksnb);
		$this->log_model->insert($log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'msg' => $message,
			'url' => $url,
			'data' => $transaction
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_detail_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$mic_tnds = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$mic_tnds = $this->mic_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($mic_tnds)) {
			foreach ($mic_tnds as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'mic_tnds' => $mic_tnds,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_detail_vbi_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$vbi_tnds = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$vbi_tnds = $this->vbi_tnds_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($vbi_tnds)) {
			foreach ($vbi_tnds as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}
		
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'vbi_tnds' => $vbi_tnds,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
    public function get_transaction_cc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$transaction = $this->transaction_model->findOne(array('type_payment' => 3,"status"=>['$ne'=>3],'code_contract'=>$this->dataPost['code_contract']));
		
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} 
		
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_list_billing_utilities_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'utilities';
		$start = !empty($data['fdate']) ? $this->security->xss_clean($data['fdate']) : '';
		$end = !empty($data['tdate']) ? $this->security->xss_clean($data['tdate']) : '';
		$trading_code = !empty($data['trading_code']) ? $this->security->xss_clean($data['trading_code']) : '';
		$service_name = !empty($data['service_name']) ? $this->security->xss_clean($data['service_name']) : '';
		$publisher_name = !empty($data['publisher_name']) ? $this->security->xss_clean($data['publisher_name']) : '';
		$service_code = !empty($data['service_code']) ? $this->security->xss_clean($data['service_code']) : '';
		$code_transaction = !empty($data['code_transaction']) ? $this->security->xss_clean($data['code_transaction']) : '';
		$filter_by_store = !empty($data['filter_by_store']) ? $this->security->xss_clean($data['filter_by_store']) : '';
		$status = !empty($data['status']) ? $this->security->xss_clean($data['status']) : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($trading_code)) {
			$condition['trading_code'] = $trading_code;
		}
		if (!empty($code_transaction)) {
			$condition['code_transaction'] = $code_transaction;
		}
		if (!empty($service_name)) {
			$condition['service_name'] = $service_name;
		}
		if (!empty($publisher_name)) {
			$condition['publisher_name'] = $publisher_name;
		}
		if (!empty($service_code)) {
			$condition['service_code'] = $service_code;
		}
		if (!empty($status)) {
			$condition['status'] = $status;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}

		if (!$all) {
			// neu khong thuoc cac quyen all thi lay ra id cua store cua user hien tai
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (empty($filter_by_store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('hoi-so', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['filter_by_store'] = $filter_by_store;
		}

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 20;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

		if ($tab == 'utilities') {
			$result = $this->order_model->get_list_billing_utilities($condition, $per_page, $uriSegment);
			$total = $this->order_model->count_list_billing_utilities($condition);
		} else {
			$result = $this->transaction_model->list_transaction_billing_utilities($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_billing_utilities($condition);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result,
			'total' => $total,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_detail_vbi_utv_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$vbi_utv = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$vbi_utv = $this->vbi_utv_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($vbi_utv)) {
			foreach ($vbi_utv as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'vbi_utv' => $vbi_utv,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_detail_vbi_sxh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$vbi_sxh = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$vbi_sxh = $this->vbi_sxh_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($vbi_sxh)) {
			foreach ($vbi_sxh as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'vbi_sxh' => $vbi_sxh,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_detail_pti_vta_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		$pti_vta = array();
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$transaction['id'] = (string)$transaction['_id'];
			$user = $this->user_model->findOne(array('email' => $transaction['created_by']));
			$transaction['user_full_name'] = $user['full_name'];
			$pti_vta = $this->pti_vta_bn_model->find_where(array('receipt_code' => $transaction['code']));
		}
		if (!empty($pti_vta)) {
			foreach ($pti_vta as $or) {
				$or['id'] = (string)$or['_id'];
				if ((isset($or['response_error']) && $or['status'] !== 'success') || $or['status'] == 'failed') {
					$or['error'] = true;
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'pti_vta' => $pti_vta,
			'transaction' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function check_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = array();
		if (!empty($data['code_contract'])) {
			$condition['code_contract'] = $data['code_contract'];
		}
		if (!empty($data['code_transaction'])) {
			$condition['code_transaction'] = $data['code_transaction'];
		}
		$transaction = $this->transaction_model->checkTransaction($condition);
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại phiếu thu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $transaction,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function check_gach_no_tu_dong_post()
	{
		$data = $this->input->post();
		if (!empty($data['code_transaction_bank'])) {
			$code = $data['code_transaction_bank'];
			$bank_tran = $this->bank_transaction_model->findOne([
				'code' => $code
			]);
			if ($bank_tran) {
				$tran = $this->transaction_model->findOne([
					'_id' => new \MongoDB\BSON\ObjectId($bank_tran['transaction_code']->jsonSerialize()['$oid']),
					'status' => [
						'$ne' => 3
					]
				]);
				if ($tran) {
					$response = array(
						'status' => REST_Controller::HTTP_BAD_REQUEST,
						'data' => false,
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => true,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cancle_gach_no_tu_dong_post()
	{
		$data = $this->input->post();
		if (!empty($data['code_transaction_bank'])) {
			$code = $data['code_transaction_bank'];
			$bank_tran = $this->bank_transaction_model->findOne([
				'code' => $code
			]);
			if ($bank_tran) {
				$tran = $this->transaction_model->findOne([
					'_id' => new \MongoDB\BSON\ObjectId($bank_tran['transaction_code']->jsonSerialize()['$oid']),
					'status' => [
						'$ne' => 3
					]
				]);
				if ($tran) {
					$this->transaction_model->update([
						'_id' => new \MongoDB\BSON\ObjectId($bank_tran['transaction_code']->jsonSerialize()['$oid']),
						'status' => [
							'$ne' => 3
						]
					], [
						'status' => 3
					]);

					$response = array(
						'status' => REST_Controller::HTTP_OK
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_BAD_REQUEST
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function initAutoCodeBilling($store,$type)
	{
		$count_billing = "";
		$store_id = $store["id"];
		$store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($store_id)));
		$code_address_store = !empty($store_info['code_address_store']) ? $store_info['code_address_store'] : "";
		$first_day_of_month = date('01-m-Y');
		$first_day_of_month = strtotime($first_day_of_month);
		$time["start"] = $first_day_of_month;
		$time["end"] = time();
		$count_transaction = $this->transaction_model->countTransaction($time, $store_id);
		if ($count_transaction == 0) {
			$count_billing = "0001";
		} elseif ( ($count_transaction > 0) && ( ($count_transaction + 1) < 10 ) ) {
			$count_billing = "000" . ($count_transaction +1);
		} elseif ( ($count_transaction + 1 >= 10) && ( ($count_transaction + 1) < 100) ) {
			$count_billing = "00" . ($count_transaction +1);
		} elseif ( ($count_transaction + 1 >= 100) && ( ($count_transaction + 1) < 1000 ) ) {
			$count_billing = "0" . ($count_transaction +1);
		} elseif ( ($count_transaction + 1 >= 1000) && ( ($count_transaction + 1) < 10000) ) {
			$count_billing = $count_transaction + 1;
		} else {
			$count_billing = $count_transaction + 1;
		}
		$mydate = getdate(date("U"));
		$year = substr($mydate['year'], -2);
		if (intval($mydate['mon']) < 10) {
			$mydate['mon'] = '0' . $mydate['mon'];
		}
		if ($type == 3) {
			$code_billing = $code_address_store . $mydate['mon'] . $year . "-" . $count_billing . "TT";
		} else {
			$code_billing = $code_address_store . $mydate['mon'] . $year . "-" . $count_billing;
		}

		$response = array(
			'code_billing' => $code_billing,
		);
		return $response;
	}

	public function insert_pti_vta($data, $NGAY_HL,$type,$code, $number_item) {
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$fullname = !empty($data['fullname']) ? $data['fullname'] : '';
		$cmt = !empty($data['cmt']) ? $data['cmt'] : '';
		$relationship = !empty($data['relationship']) ? $data['relationship'] : '';
		$address = !empty($data['address']) ? $data['address'] : '';
		$id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
		$obj = !empty($data['obj']) ? $data['obj'] : '';
		$phone = !empty($data['phone']) ? $data['phone'] : '';
		$email = !empty($data['email']) ? $data['email'] : '';
		$birthday = !empty($data['birthday']) ? $data['birthday'] : '';
		$fullname_another = !empty($data['fullname_another']) ? $data['fullname_another'] : '';
		$birthday_another = !empty($data['birthday_another']) ? $data['birthday_another'] : '';
		$email_another = !empty($data['email_another']) ? $data['email_another'] : '';
		$cmt_another = !empty($data['cmt_another']) ? $data['cmt_another'] : '';
		$phone_another = !empty($data['phone_another']) ? $data['phone_another'] : '';
		$sel_ql = !empty($data['sel_ql']) ? $data['sel_ql'] : '';
		$sel_year = !empty($data['sel_year']) ? $data['sel_year'] : '';
		$price = !empty($data['price']) ? $data['price'] : 0;
		$btendn=$fullname;
		$bdiachidn=$address;
		$bemaildn=$email;
		$bphonedn=$phone;
		$bmathue=$cmt;
		$NgayYeuCauBh = $NGAY_HL;
		$NgayHieuLucBaoHiem = $NGAY_HL;
		if($sel_year=="1Y")
		{
			$so_thang_bh=12;
		}else if($sel_year=="6M")
		{
			$so_thang_bh=6;
		}else if($sel_year=="3M")
		{
			$so_thang_bh=3;
		}
		$NgayHieuLucBaoHiemDen = date('d-m-Y', strtotime($NgayHieuLucBaoHiem . ' + '.$so_thang_bh.' month'));

		$customer_name = (!empty($data['ten_kh'])) ? $data['ten_kh'] : '';
		$customer_BOD = (!empty($data['ngay_sinh'])) ? date("Y-m-d",$data['ngay_sinh']) : '';
		$customer_identify = (!empty($data['cmt'])) ? $data['cmt'] : '';
		$so_hd='TN'.str_pad((string)$number_item,7, '0', STR_PAD_LEFT).'/041/CN.1.14/'.date('Y');
		if($obj=='banthan')
		{
			$ten=$fullname;
			$ngay_sinh=$birthday;
			$email=$email;
			$phone=$phone;
			$so_cmt=$cmt;
		}else{
			$ten=$fullname_another;
			$ngay_sinh=$birthday_another;
			$email=$email_another;
			$phone=$phone_another;
			$so_cmt=$cmt_another;
		}

		if($sel_ql=="G1")
		{
			$ba1='20,000,000';
			$ba2='20,000,000';
			$ba3='30,000,000';
			$ba4='2,000,000';
			$ba5='2,000,000';
		}else if($sel_ql=="G2")
		{
			$ba1='40,000,000';
			$ba2='40,000,000';
			$ba3='60,000,000';
			$ba4='4,000,000';
			$ba5='4,000,000';
		}else if($sel_ql=="G3")
		{
			$ba1='60,000,000';
			$ba2='60,000,000';
			$ba3='90,000,000';
			$ba4='6,000,000';
			$ba5='6,000,000';
		}
		$dt_pti = array(
			'so_hd' => $so_hd
		, 'btendn' => $btendn
		, 'bdiachidn' => $bdiachidn
		, 'bemaildn' => $bemaildn
		, 'bphonedn' => $bphonedn
		, 'bmathue' => $bmathue
		, 'quan_he' => $relationship
		, 'ten' => $ten
		, 'ngay_sinh' => date('d-m-Y', strtotime($ngay_sinh))
		, 'so_cmt' => $so_cmt
		, 'email' => $email
		, 'phone' => $phone
		, 'phi_bh' => number_format($price)
		, 'so_thang_bh' => $so_thang_bh
		, 'ngay_hl' => $NgayHieuLucBaoHiem
		, 'ngay_kt' => $NgayHieuLucBaoHiemDen
		, 'ngay_in' => date('d/m/Y')
		, 'so_gcn' => $so_hd
		, 'ba1' => $ba1
		, 'ba2' => $ba2
		, "ba3" => $ba3
		, "ba4" => $ba4
		, 'ba5' => $ba5
		);
		// return  $province;
		$baohiem = new BaoHiemPTI();
		$res = $baohiem->call_api($dt_pti);

		if (!empty($res)) {
			if ($res['code']=="000") {

				$dt_re = array(
					'message' => 'Thành công',
					'data' => $res,
					'number_item'=>$number_item,
					'success' => true,
					'request'=>$dt_pti,
					'NGAY_KT'=>$NgayHieuLucBaoHiemDen
				);
				return json_decode(json_encode($dt_re));

			} else {
				$dt_re = array(
					'message' => 'Không thành công',
					'success' => false
				);
				return json_decode(json_encode($dt_re));
			}
		} else {

			$dt_re = array(
				'message' => "Kết nối đến PTI bị lỗi !",
				'success' => false
			);
			return json_decode(json_encode($dt_re));
		}
	}

	private function sendEmailApprove_store($contract, $note, $code_transaction, $type_approve)
	{
		$data = array(
			'code' => "vfc_send_approve_transaction",
			"customer_name" => $contract['customer_infor']['customer_name'],
			"code_contract" => $contract['code_contract'],
			"store_name" => $contract['store']['name'],
			"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
			"product" => $contract['loan_infor']['type_loan']['text'],
			"product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
			"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
			"note" => $note,
			"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng gốc cuối kì",
			"type_approve" => $type_approve,
			"code_transaction" => $code_transaction
		);
		$allusers = $this->getUserbyStores_email($contract['store']['id']);
		if (!empty($allusers)) {
			foreach ($allusers as $key => $value) {
				$data['email'] = $value;
				$data['full_name'] = $value;
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
			}
		}

		return;
	}

	/*
	* API Tự động gạch nợ cho khách hàng
	*/
	public function auto_payment_contract_post() {
		if (empty($this->dataPost['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contract = $this->contract_model->findOne([
			'code_contract' => !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : 0,
		]);


		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tìm thấy mã hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$code = $this->transaction_model->getNextTranCode($contract['code_contract']);
		$date_pay = '';
		if (!empty($this->dataPost['date_pay'])) {
			$date_pay = new DateTime($this->dataPost['date_pay']);
		}
		if(empty($this->uemail)) {
			$this->uemail = 'system';
		}
		//Write log
		$log = array(
			"type" => "contract",
			"action" => "payment",
			"data_post" => $this->dataPost,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);

		$paymentMethod = !empty($this->dataPost['payment_method']) ? $this->dataPost['payment_method'] : 0;
		if (!empty($this->dataPost['payment_method']) && $this->dataPost['payment_method'] == 3) {
			$paymentMethod = "momo_app";
		}

		$isInvalidContract = $this->checkInvalidContract($contract['code_contract']);

		$isGHCC = false;
		if (!empty($contract["code_contract_parent_gh"]) || !empty($contract["code_contract_parent_cc"])) {
			$isGHCC = true;
		}

		//trường hợp tất toán, kiểm tra tính hợp lệ của hợp đồng
		if (
			!empty($this->dataPost['type_pt'])
			&& $this->dataPost['type_pt'] == 3
			&& ($isInvalidContract["result"] || $isGHCC)
		) {
			$status = 2; // chờ kế toán duyệt
		} else {
			$status = 1; // kế toán đã approve
		}
		$data_transaction = [
			"amount_total" => isset($this->dataPost['amount_total']) ? $this->dataPost['amount_total'] : 0,// tổng số tiền phải thanh toán
			"valid_amount" => isset($this->dataPost['valid_amount']) ? $this->dataPost['valid_amount'] : 0,// tổng số tiền hợp lệ thanh toán
			"reduced_fee" => isset($this->dataPost['reduced_fee']) ? $this->dataPost['reduced_fee'] : 0,// tổng số tiền phí giảm ngân hàng
			"total_deductible" => isset($this->dataPost['total_deductible']) ? $this->dataPost['total_deductible'] : 0,// tổng số tiền giảm
			"discounted_fee" => isset($this->dataPost['discounted_fee']) ? $this->dataPost['discounted_fee'] : 0,// tổng số tiền phí giảm trừ
			"other_fee" => isset($this->dataPost['other_fee']) ? $this->dataPost['other_fee'] : 0,// tổng số tiền phí giảm khác
			"fee_reduction" => isset($this->dataPost['fee_reduction']) ? $this->dataPost['fee_reduction'] : 0,// tổng số tiền giảm
			"penalty_pay" => isset($this->dataPost['penalty_pay']) ? $this->dataPost['penalty_pay'] : 0,// tổng số tiền phạt

			'code_contract' => !empty($contract['code_contract']) ? $contract['code_contract'] : '',
			'code_contract_disbursement' => !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : '',
			'customer_name' => !empty($contract['customer_name']) ? $contract['customer_name'] : '',
			'total' => !empty($this->dataPost['total']) ? $this->dataPost['total'] : 0,
			'code' => $code,
			'type' => !empty($this->dataPost['type_pt']) ? (int)$this->dataPost['type_pt'] : 0, //3 tat toan. 4 thanh toan ky lai. 5 gia han hop dong
			'payment_method' => $paymentMethod, // 1:tiền mặt, 2: chuyển khoản, 3: momoApp
			'store' => !empty($contract['store']) ? $contract['store']:'',
			'date_pay' => $date_pay->getTimestamp(),
			'status' => $status,
			'customer_bill_phone' => !empty($contract['customer_infor']['customer_phone_number']) ? $contract['customer_infor']['customer_phone_number']:'',
			'customer_bill_name' => !empty($contract['customer_infor']['customer_name']) ? $contract['customer_infor']['customer_name']:'',
			'note' => !empty($this->dataPost['note']) ? $this->dataPost['note']:'',
			'bank' => !empty($this->dataPost['bank']) ?  $this->dataPost['bank']:'',
			'code_transaction_bank' => !empty($this->dataPost['code_transaction_bank']) ? $this->dataPost['code_transaction_bank']:'',
			'type_payment' => !empty($this->dataPost['type_payment']) ?  (int)$this->dataPost['type_payment']:'', // 1: thanh toán kỳ
			'created_by' => !empty($contract['customer_infor']['customer_phone_number']) ? $contract['customer_infor']['customer_phone_number']:'',
			'created_at' => time(),
			'code_transaction_bank' => !empty($this->dataPost['code_transaction_bank']) ?  $this->dataPost['code_transaction_bank']:'',
		];
		$transaction_id = $this->transaction_model->insertReturnId($data_transaction);
		if ($status == 2) { // trạng thái chờ kế toán duyệt, không cần phải chạy lại hàm tính toán.
			
			try {
				$emails = [];
				$id_ke_toan = array("5de726fcd6612b77824963b9");
				$user_ids_groups = $this->getUserGroupRole_email($id_ke_toan);
				$emails = $user_ids_groups;
				$dataEmail = array(
					"code" => "vfc_approve_final_settlement_transtraction",
					"transactionId" => $code,
					"name" => $data_transaction['customer_bill_name'],
					"paidAmount" => number_format($data_transaction['total']),
					"paidDate" => date("d-m-Y", $data_transaction['date_pay']),
					"created_by" => "System",
				);

				if ($data_transaction['payment_method'] == 1) {
					$dataEmail["paymentMethod"] = "Tiền mặt";
				} else if ($data_transaction['payment_method'] == 2) {
					$dataEmail["paymentMethod"] = "Chuyển khoản";
				} else {
					$dataEmail["paymentMethod"] = $data_transaction['payment_method'];
				}

				foreach ($emails as $key => $value) {
					$dataEmail['email'] = $value;
					$dataEmail['emailName'] = $value;
					$dataEmail['API_KEY'] = $this->config->item('API_KEY');
					$this->user_model->send_Email($dataEmail);
				}
			} catch (Exception $e) {
				//
			}
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Chờ kế toán duyệt',
				'transaction_id' => $transaction_id,
				'isInvalidContract' => $isInvalidContract["result"],
				'message2' => $isInvalidContract["message"],
				'transaction_code' => $code,
				'isGHCC' => $isGHCC,
			);
			//Write log
			$log = array(
				"type" => "transaction",
				"action" => 'payment',
				"data_post" => $this->dataPost,
				"transaction_id" => (string)$transaction_id,
				"email" => $this->uemail,
				"created_at" => $this->createdAt
			);
			$this->log_model->insert($log);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Gạch nợ thành công',
			'transaction_id' => $transaction_id,
			'transaction_code' => $code,
			'isGHCC' => $isGHCC,
		);
		//Write log
		$log = array(
			"type" => "transaction",
			"action" => 'payment',
			"data_post" => $this->dataPost,
			"transaction_id" => (string)$transaction_id,
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$this->log_model->insert($log);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	/**
	*
	* Check invalid contract
	* @param String $codeContract
	* @return boolean true: invalide, false: valid
	*/
	protected function checkInvalidContract($codeContract) {

		$c = $this->contract_model->findOne(array(
			"status"=>17,
			'debt.is_qua_han'=>0,
			'debt.so_ngay_cham_tra'=>['$gte'=>-5,'$lte'=>0],
			'code_contract_parent_gh' => array('$exists' => false),
			'code_contract_parent_cc' => array('$exists' => false),
			'code_contract'=>$codeContract
		));
		$conti = false;
    	if (!empty($c)) {
        	$trans = $this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'code_contract'=>$c['code_contract']));
        	$message = "";
            foreach ($trans as $key => $value_tran) {
                if(in_array($value_tran['status'], [2,4,11]) ) {
                    $conti = true;
                    $message = "case 1";
                    break;
                }
                $tong_thu = $value_tran['total'];
                $tong_chia = round(
                	$value_tran['so_tien_lai_da_tra']
                	+ $value_tran['so_tien_phi_da_tra']
                	+ $value_tran['so_tien_goc_da_tra']
                	+ $value_tran['so_tien_phi_cham_tra_da_tra']
                	+ $value_tran['tien_phi_phat_sinh_da_tra']
                	+ $value_tran['fee_finish_contract']
                	+ $value_tran['tien_thua_tat_toan']
                	+ $value_tran['so_tien_phi_gia_han_da_tra']
                );

                if(
                	strtotime(date('Y-m-d',$c['disbursement_date']). ' 00:00:00')
                	>
                	strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00')
                ) {
            		$conti = true;
            		$message = "case 2";
                    break;
          		}
          		if(
          			$value_tran['type']==4
          			&& ($value_tran['type_payment']==1 || !isset($value_tran['type_payment']))
          			&& $value_tran['tien_thua_thanh_toan'] > 0
          		) {
                    $conti = true;
                    $message = "case 3";
                    break;

          		}
          		if(
          			strpos(strtolower($value_tran['note']),'gia h')
          			&& is_string($value_tran['note'])
          			&& ($value_tran['type']==3 || $value_tran['type']==4)
          			&& $value_tran['status']==1
          			&& !isset($c['type_gh'])
          			&&  $c['status']!=33
          		) {
                    $conti = true;
                    $message = "case 4";
                    break;

          		}
          		if($value_tran['type']==3 && $c['status']!=19) {
                    $conti = true;
                    $message = "case 5";
                    break;
                }
          		if ($tong_thu != $tong_chia) {
                	$conti = true;
                	$message = "case 6";
                    break;
                }
				if($value_tran['tien_thua_tat_toan']>0) {
	                $conti = true;
	                $message = "case 7";
                    break;
				}
        	}
        	$result = ["result" => $conti, "message" => $message];
            return $result;
		}
		$message = "case 8";
		$result = ["result" => $conti, "message" => $message];
		return $result;
	}

	public function check_transactions_status_post() {
		$transactionIds = $this->dataPost['transactionIds'];
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Lấy thông tin thành công',
			'data' => [],
		);
		foreach ($transactionIds as $key => $value) {
			$transactionId = $value['contract_transaction_id'];
			$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($transactionId)));
			if (empty($transaction)) {
				continue;
			}
			$result = [
				'transactionId' => $transactionId,
				'status' => $transaction['status']
			];
			$response['data'][] = $result;
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function sendEmailMomo_post() {
		$code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : null;
		$payAmount = !empty($this->dataPost['payAmount']) ? $this->dataPost['payAmount'] : null;
		$transactionsNumber = !empty($this->dataPost['transactionsNumber']) ? $this->dataPost['transactionsNumber'] : null;
		$attachPath = !empty($this->dataPost['attachPath']) ? $this->dataPost['attachPath'] : null;
		$momoEmail = !empty($this->dataPost['momoEmail']) ? $this->dataPost['momoEmail'] : null;
		if ($code && $payAmount && $transactionsNumber && $attachPath && $momoEmail) {
			$id_ke_toan = array("5de726fcd6612b77824963b9");
			$emails = $this->getUserGroupRole_email($id_ke_toan);
			array_push($emails, $momoEmail);
			$dataEmail = array(
				"code" => "vfc_transaction_reconciliation_to_momo",
				"reconCode" => $code,
				"payAmount" => $payAmount,
				"transactionsNumber" => $transactionsNumber,
				"attachPath" => $attachPath,
			);
			foreach ($emails as $key => $value) {
				$dataEmail['API_KEY'] = $this->config->item('API_KEY');
				$dataEmail['email'] = $value;
				$this->user_model->send_Email($dataEmail);
			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Gửi email thành công",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_UNAUTHORIZED,
			'message' => "Gửi email thất bại"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function test_ctv_post()
	{
		echo '<pre>';
		print_r(12121);
		echo '</pre>';
	}

	private function sendEmailReturnTransaction_store($contract, $note, $transaction, $type_approve)
	{
		$data = array(
			'code' => "vfc_return_transaction",
			"customer_name" => $contract['customer_infor']['customer_name'],
			"code_contract" => $contract['code_contract'],
			"code_contract_disbursement" => $contract['code_contract_disbursement'],
			"store_name" => $contract['store']['name'],
			"amount_money" => !empty($contract['loan_infor']['amount_money']) ? number_format($contract['loan_infor']['amount_money']) : "0",
			"product" => $contract['loan_infor']['type_loan']['text'],
			"product_detail" => !empty($contract['loan_infor']['name_property']['text']) ? $contract['loan_infor']['name_property']['text'] : '',
			"number_day_loan" => (int)$contract['loan_infor']['number_day_loan'] / 30,
			"note" => $note,
			"type_interest" => (int)$contract['loan_infor']['type_interest'] == 1 ? "Dư nợ giảm dần" : "Lãi hàng tháng gốc cuối kì",
			"type_approve" => $type_approve,
			"code_transaction" => $transaction['code'],
			'url' => "https://cpanel.tienngay.vn/transaction?tab=return&fdate=&tdate=&code=".$transaction['code'],
		);
		$user_email_created[] = $transaction['created_by'];
		$user_email_stores = $this->getUserbyStores_email($contract['store']['id']);
		$allusers = array_merge($user_email_created, $user_email_stores);
		if (!empty($allusers)) {
			foreach ($allusers as $key => $value) {
				$data['email'] = $value;
				$data['full_name'] = $value;
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmailTest($data); //Hàm test gửi email, lưu ý khi đẩy live cần thay lại hàm phía trên.
			}
		}
		return;
	}

	public function sendEmailTest($dataPost)
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

	private function getRole($userId)
	{
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
		return $arr;
	}

	public function report_transaction_pending_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
		$total = !empty($this->dataPost['total']) ? $this->dataPost['total'] : "";
		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";
		$type_transaction = !empty($this->dataPost['type_transaction']) ? $this->dataPost['type_transaction'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : "";
		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate) . ' 00:00:00'),
				'end' => strtotime(trim($tdate) . ' 23:59:59')
			);
		}

		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($total)) {
			$condition['total'] = $total;
		}
		if (!empty($type_transaction)) {
			$condition['type_transaction'] = (int)$type_transaction;
		}
		if (!empty($status)) {
			if ($status != 'new') {
				$condition['status'] = (int)$status;
			} else {
				$condition['status'] = $status;
			}
		}

		$roles = $this->getRole($this->id);
		$all = false;
		if (in_array('van-hanh', $roles) || in_array('ke-toan', $roles) || $this->superadmin || in_array('giam-doc-kinh-doanh', $roles) || in_array('quan-ly-khu-vuc', $roles) || in_array('cua-hang-truong', $roles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $roles)) {
			$condition['created_by'] = $this->uemail;
		}

		if (!$all) {
			// neu khong thuoc cac quyen all thi lay ra id store cua user hien tai
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (empty($code_store)) {
			if (in_array('van-hanh', $roles) || in_array('ke-toan', $roles) || $this->superadmin || in_array('giam-doc-kinh-doanh', $roles) || in_array('quan-ly-khu-vuc', $roles) || in_array('cua-hang-truong', $roles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['stores'] = (is_array($code_store)) ? $code_store : [$code_store];
		}
		$condition['type'] = 1;

		$tran_pending = $this->transaction_pending_model->getTransactionPendingByDay($condition, $per_page);
		$condition['total_record'] = true;
		$total_result = $this->transaction_pending_model->getTransactionPendingByDay($condition);
		$arr_data_detail_store_by_day = [];
		$arr_store = [];
		$arr_store_by_day = [];

		$arr_return_amount = [];
		$arr_return_amount_day_by_store = [];
		$arr_all_not_yet_send_transaction = [];
		if (!empty($tran_pending)) {
			foreach ($tran_pending as $key => $tran_in) {
				if (!isset($tran_in['store'])) {
					$tran_in['store'] = '';
				}
				if (!isset($tran_in['created_by'])) {
					$tran_in['created_by'] = '';
				}
				$tran_in['id'] = (string)$tran_in['_id'];
				$user = $this->user_model->findOne(array('email' => $tran_in['created_by']));
				$tran_in['user_full_name'] = $user['full_name'];
				$store_tran_in = array($tran_in['store']['id']);
				$arr_store_by_day = array_merge($store_tran_in, $arr_store_by_day);
				$arr_store = array_merge($store_tran_in, $arr_store_by_day);
				$arr_data_detail_store_by_day[$tran_in['store']['id']][$tran_in['created_by']][] = $tran_in;
				array_push($arr_all_not_yet_send_transaction, $tran_in);
			}
		}

		//Lọc các phần tử trùng trong mảng tiền thu chi tiết trong ngày và excel
		$detail_day = $this->super_unique($arr_data_detail_store_by_day);

		$arr_store_filter = [];
		if (!empty($arr_store)) {
			foreach (array_unique($arr_store) as $key => $arr_st) {
				array_push($arr_store_filter, $arr_st);
			}
			// remove các transaction của PGD IT test, 6 Nguyễn Thị Thập,IT test.
			foreach ($arr_store_filter as $key => $arr_st) {
				if ($arr_st == '5ecbda18d6612b0fd70b891f' || $arr_st == '5e593293d6612b26016acaae' || $arr_st == '5eb8f2ebd6612b350f5932e3' || $arr_st == '') {
					unset($arr_store_filter[$key]);
				}
			}
		}

		$arr_store_filter_by_day = [];
		if (!empty($arr_store_by_day)) {
			foreach (array_unique($arr_store_by_day) as $key => $arr_st) {
				array_push($arr_store_filter_by_day, $arr_st);
			}
			foreach ($arr_store_filter_by_day as $key => $arr_st) {
				if ($arr_st == '5ecbda18d6612b0fd70b891f' || $arr_st == '5e593293d6612b26016acaae' || $arr_st == '5eb8f2ebd6612b350f5932e3' || $arr_st == '') {
					unset($arr_store_filter_by_day[$key]);
				}
			}
		}

		//Thống kê từ mốc go live tới hiện tại
		$sto = 0;
		if (!empty($arr_store_filter))
			foreach ($arr_store_filter as $key => $value) {
				$sto++;
				$arr_code = get_values($tran_pending, 'store', $key, '', '', '', '', 'code', true, false);
				if (count($arr_code) <= 0)
					$arr_code = ['1'];
				$total_cod_contract_not_yet_send = sum_values_transaction($arr_all_not_yet_send_transaction, 'store', $value, '', '', '', '', 'total');
				$total_cod_not_yet_send = $total_cod_contract_not_yet_send;
				$arr_return_amount += [$sto => [
					'store' => $this->get_name_store_by_id($value),
					'total_cod_not_yet_send' => $total_cod_not_yet_send,
				]];
			}
		// Thống kê tiền thu theo ngày, lọc các bản ghi trùng.
		$stor = 0;
		if (!empty($arr_store_filter_by_day))
			foreach ($arr_store_filter_by_day as $key => $value) {
				$stor++;

				$arr_code_day = get_values($tran_pending, 'store', $key, '', '', '', '', 'code', true, false);
				if (count($arr_code_day) <= 0)
					$arr_code_day = ['1'];
				$total_cod_in_day_by_store = sum_values_transaction($tran_pending, 'store', $value, '', '', '', '', 'total');
				$total_cod_day = $total_cod_in_day_by_store;

				$arr_return_amount_day_by_store += [$stor => [
					'store' => $this->get_name_store_by_id($value),
					'total_cod_not_yet_send_day' => $total_cod_in_day_by_store,
					'total_cod_day' => $total_cod_day
				]];
			}
		usort($arr_return_amount, function ($a, $b) {
			return $b['total_cod'] <=> $a['total_cod'];
		});
		usort($arr_return_amount_day_by_store, function ($a, $b) {
			return $b['total_cod_day'] <=> $a['total_cod_day'];
		});

		$arr_parent = [];
		foreach ($arr_return_amount as $item_amount) {
			if (!isset($item_amount['store'])) {
				$item_amount['store'] = '';
			}
			foreach ($arr_return_amount_day_by_store as $item_amount_day) {
				if (trim($item_amount_day['store']) == trim($item_amount['store'])) {
					$item_amount['store_child'] = $item_amount_day;
				}
			}
			$arr_parent[$item_amount['store']] = $item_amount;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'roles' => $roles,
			'total_result' => $total_result,
			'data' => $detail_day,
			'total_parent' => $arr_parent,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function sendEmailReturnTransactionInsurance_store($transaction, $note, $code_transaction, $type_approve)
	{
		$url = "";
		$type_transaction = "";
		$extension = "";
		if (!empty($transaction['type'])) {
			if ($transaction['type'] == 7) {
				$url = 'https://cpanel.tienngay.vn/heyU?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Thanh toán Nạp tiền tài xế HeyU";
			} elseif ($transaction['type'] == 8) {
				$url = 'https://cpanel.tienngay.vn/mic_tnds?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm Xe máy - MIC_TNDS";
			} elseif ($transaction['type'] == 10) {
				$url = 'https://cpanel.tienngay.vn/vbi_tnds?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm Ô tô - VBI_TNDS";
			} elseif ($transaction['type'] == 11) {
				$url = 'https://cpanel.tienngay.vn/baoHiemVbi/utv?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm VBI_Ung thư vú";
			} elseif ($transaction['type'] == 12) {
				$url = 'https://cpanel.tienngay.vn/baoHiemVbi/sxh?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm VBI_Sốt xuất huyết";
			} elseif ($transaction['type'] == 13) {
				$url = 'https://cpanel.tienngay.vn/gic_easy?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm GIC EASY";
			} elseif ($transaction['type'] == 14) {
				$url = 'https://cpanel.tienngay.vn/gic_plt?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm GIC Phúc Lộc Thọ";
			} elseif ($transaction['type'] == 15) {
				$url = 'https://cpanel.tienngay.vn/pti_vta?tab=transaction&code_transaction='. $transaction['code'];
				$type_transaction = "Bảo hiểm PTI Vững Tâm An";
			} else {
				$url = 'https://cpanel.tienngay.vn/';
				$type_transaction = "";
			}
		}
		$data = array(
			'code' => "vfc_return_transaction_insurance",
			"store_name" => $transaction['store']['name'],
			"total" => !empty($transaction['total']) ? number_format($transaction['total']) : "0",
			"note" => $note,
			"code_transaction" => $code_transaction,
			"type_transaction" => $type_transaction,
			"approved_at" => date('d/m/Y H:i:s', $this->createdAt),
			"approved_by" => $this->uemail,
			'url' => $url
		);
		$allusers = $this->getUserbyStores_email($transaction['store']['id']);
		if (!empty($allusers)) {
			foreach ($allusers as $key => $value) {
				$data['email'] = $value;
				$data['full_name'] = $value;
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmailTest($data); //Hàm test gửi email, lưu ý khi đẩy live cần thay lại hàm phía trên.
			}
		}
		return;
	}

	public function cancel_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//quyền chỉ view kế toán
		if (!in_array('600115aa5324a7cc9665c3d5', $this->roleAccessRights)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(
					"message" => "No have access right"
				)
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['transaction_id'] = $this->security->xss_clean($this->dataPost['transaction_id']);
		$this->dataPost['code_transaction_bank'] = $this->security->xss_clean($this->dataPost['code_transaction_bank']);
		$this->dataPost['bank'] = $this->security->xss_clean($this->dataPost['bank']);
		$this->dataPost['approve_note'] = $this->security->xss_clean($this->dataPost['approve_note']);
		$this->dataPost['status'] = $this->security->xss_clean($this->dataPost['status']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($this->dataPost['transaction_id'])));
		if (empty($transaction) ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Giao dịch không tồn tại trạng thái chờ",
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (!empty($this->dataPost['code_transaction_bank']) ) {
			$transaction_ck_pt = $this->transaction_model->find_where(array('code_transaction_bank' => $this->dataPost['code_transaction_bank'], "status" => array('$ne' => 3), "code" => array('$ne' => $transaction['code'])));

			if (!empty($transaction_ck_pt)) {
				foreach ($transaction_ck_pt as $key => $value) {
					if (date("Ymd", $value['date_pay']) != date("Ymd", $transaction['date_pay'])) {
						$response = array(
							'status' => REST_Controller::HTTP_BAD_REQUEST,
							'message' => "Phiếu thu đã tồn tại (khác ngày):"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}

					if (date("Ymd", $value['date_pay']) == date("Ymd", $transaction['date_pay']) && $value['code_contract'] == $transaction['code_contract']) {
						$response = array(
							'status' => REST_Controller::HTTP_BAD_REQUEST,
							'message' => "Phiếu thu đã tồn tại (trùng ngày):"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}
		$contractDB = $this->contract_model->findOne(array('code_contract' => $transaction['code_contract']));

		if ((int)$this->dataPost['status'] == 3) {
			$url = 'transaction';
			$message = 'Hủy phiếu thu thành công';
			$url = $this->dataPost['transaction_id'];
			$user_find = $this->user_model->findOne(['email' => $transaction['created_by'], 'status' => 'active']);
			$user_creat[] = (string)$user_find['_id'];
			$get_user_id_store = $this->getUserIdbyStores($transaction['store']['id']);
			$user_id_store_mix = array_merge($user_creat, $get_user_id_store);
			$user_id_store = array_unique($user_id_store_mix);
			$status = (int)$this->dataPost['status'];
			$note = "Kế toán đã hủy phiếu thu thanh toán";
			$link = "transaction?tab=all&code=". $transaction['code'];
			$this->push_notification_to_store($user_id_store, $transaction, $status, $note, $link);
		}
		//Update
		$update_tran=array(
			"code_transaction_bank" => $this->dataPost['code_transaction_bank'],
			"bank" => $this->dataPost['bank'],
			"approve_note" => $this->dataPost['approve_note'],
			"status" => (int)$this->dataPost['status'],
			"updated_at" => $this->createdAt,
			"updated_by" => $this->uemail,
		);
		$transDB = $this->transaction_model->findOneAndUpdate(
			array("_id" => $transaction['_id']),
			$update_tran
		);
		$log = array(
			"type" => "transaction",
			"action" => 'cancel_transaction',
			"old" => $transaction,
			"new" => $this->dataPost,
			"transaction_id" => $transaction['code_contract'],
			"email" => $this->uemail,
			"created_at" => $this->createdAt
		);
		$log_ksnb = array(
			"type" => "contract_ksnb",
			"action" => "Approve",
			"transaction_ksnb_id" => (string)$this->dataPost['transaction_id'],
			"old" => $transaction,
			"new" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_ksnb_model->insert($log_ksnb);
		$this->log_model->insert($log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'msg' => $message,
			'url' => $url,
			'data' => $transaction,
			'data_contract'=> $contractDB
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function push_notification_to_store($array_id_user, $trans, $status, $note, $link)
	{
		foreach ($array_id_user as $key => $id_user) {
			$user_creat = !empty($trans['customer_name']) ? $trans['customer_name'] : $trans['customer_bill_name'];
			if (!empty($id_user)) {
				$data_notification = [
					'action_id' => (string)$trans['_id'],
					'action' => 'return_transaction_contract',
					'detail' => $link,
					'title' => $user_creat . ' - ' . $trans['store']['name'],
					'note' => $note,
					'user_id' => $id_user,
					'status' => 1, //1: new, 2 : read, 3: block,
					'status_trans' => $status,
					'created_at' => $this->createdAt,
					"created_by" => $this->uemail
				];
				$code_contract = $trans['code_contract'];
				$code_contract_disbursement = $trans['code_contract_disbursement'];
				$customer_name = $trans['customer_name'];
				$this->notification_model->insertReturnId($data_notification);
				$device = $this->device_model->find_where(['user_id' => $id_user]);

				if (!empty($device) && $id_user == $device[0]['user_id']) {
					$badge = $this->get_count_notification_user($id_user);
					$fcm = new Fcm();
					$to = [];
					foreach ($device as $de) {
						$to[] = $de->device_token;
					}

					$fcm->setTitle($note);
					if (in_array($trans['type'],[3,4])) {
						$fcm->setMessage("HĐ: $code_contract_disbursement, KH: $customer_name");
						$click_action = "https://cpanel.tienngay.vn/transaction?tab=return&fdate=&tdate=&code=" . $trans['code'];
					} else {
						$total_trans = number_format($trans['total']);
						$fcm->setMessage("Nhân viên tạo phiếu: $user_creat, Số tiền: $total_trans VNĐ");
						if (!empty($trans['type'])) {
							if ($trans['type'] == 7) {
								$click_action = 'https://cpanel.tienngay.vn/heyU?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Thanh toán Nạp tiền tài xế HeyU";
							} elseif ($trans['type'] == 8) {
								$click_action = 'https://cpanel.tienngay.vn/mic_tnds?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm Xe máy - MIC_TNDS";
							} elseif ($trans['type'] == 10) {
								$click_action = 'https://cpanel.tienngay.vn/vbi_tnds?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm Ô tô - VBI_TNDS";
							} elseif ($trans['type'] == 11) {
								$click_action = 'https://cpanel.tienngay.vn/baoHiemVbi/utv?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm VBI_Ung thư vú";
							} elseif ($trans['type'] == 12) {
								$click_action = 'https://cpanel.tienngay.vn/baoHiemVbi/sxh?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm VBI_Sốt xuất huyết";
							} elseif ($trans['type'] == 13) {
								$click_action = 'https://cpanel.tienngay.vn/gic_easy?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm GIC EASY";
							} elseif ($trans['type'] == 14) {
								$click_action = 'https://cpanel.tienngay.vn/gic_plt?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm GIC Phúc Lộc Thọ";
							} elseif ($trans['type'] == 15) {
								$click_action = 'https://cpanel.tienngay.vn/pti_vta?tab=transaction&code_transaction='. $trans['code'];
								$type_transaction = "Bảo hiểm PTI Vững Tâm An";
							} else {
								$click_action = 'https://cpanel.tienngay.vn/';
								$type_transaction = "";
							}
						}
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

	private function getUserIdbyStores($storeId)
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
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	/**
    * Lấy thông tin chuyển khoản VPBank dành cho luồng Phiếu Thu Tiền Mặt
	*/
	public function bankPaymentDetail_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Người dùng chưa đăng nhập"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$transId = $this->security->xss_clean($this->dataPost['id']);
		$transaction = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($transId)));
		if (empty($transaction)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Không tồn phiếu thu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($transaction['code_contract'])) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		/**
		* Get VPBank Vitual Account Number
		*/
		$vpbank = new VPBank();
		$assignVan = $vpbank->assignVan($transaction['code_contract']);
		if (empty($assignVan['van'])) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Lấy tài khoản VAN thất bại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data = [
			'transaction' => $transaction
		];
		$data["bank_name"] = isset($assignVan["bankName"]) ? $assignVan["bankName"] : "";
		$data["master_account_name"] = isset($assignVan["masterAccountName"]) ? $assignVan["masterAccountName"] : "";
		$data["van"] = isset($assignVan["van"]) ? $assignVan["van"] : "";
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

    /**
    * Gửi email duyệt phiếu thu
    */
	public function sendEmailApproveTransaction_post() {
        $transactionId = !empty($this->dataPost['transactionId']) ? $this->dataPost['transactionId'] : null;
        $customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : null;
        $paidAmount = !empty($this->dataPost['paidAmount']) ? $this->dataPost['paidAmount'] : null;
        $paidDate = !empty($this->dataPost['paidDate']) ? $this->dataPost['paidDate'] : null;
        $paymentMethod = !empty($this->dataPost['paymentMethod']) ? $this->dataPost['paymentMethod'] : null;
        $message = !empty($this->dataPost['message']) ? $this->dataPost['message'] : null;
        $bank = !empty($this->dataPost['bank']) ? $this->dataPost['bank'] : null;
        $code_transaction_bank = !empty($this->dataPost['code_transaction_bank']) ? $this->dataPost['code_transaction_bank'] : null;

        if (!$transactionId || !$customer_name || !$paidAmount || !$paidDate || !$paymentMethod || !$message
            || !$bank || !$code_transaction_bank
        ) {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Gửi email thất bại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
		try {
			$emails = [];
			$id_ke_toan = array("5de726fcd6612b77824963b9");
			$user_ids_groups = $this->getUserGroupRole_email($id_ke_toan);
			$emails = $user_ids_groups;
			$dataEmail = array(
				"code" => "vfc_approve_gh_cc_transtraction",
				"transactionId" => $transactionId,
				"name" => $customer_name,
				"paidAmount" => $paidAmount,
				"paidDate" => $paidDate,
				"created_by" => "System",
                "message" => $message,
                "bank" => $bank,
                "code_transaction_bank" => $code_transaction_bank
			);

			if ($paymentMethod == 1) {
				$dataEmail["paymentMethod"] = "Tiền mặt";
			} else if ($paymentMethod == 2) {
				$dataEmail["paymentMethod"] = "Chuyển khoản";
			} else {
				$dataEmail["paymentMethod"] = $paymentMethod;
			}

			foreach ($emails as $key => $value) {
				$dataEmail['email'] = $value;
				$dataEmail['emailName'] = $value;
				$dataEmail['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($dataEmail);
			}
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Gửi email thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		} catch (Exception $e) {
			$response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Gửi email thất bại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		}

	}

	/**
    * Update pay_date và created_at phiếu thu tất toán về thời điểm sau thời gian thu phiếu thu thanh toán cuối cùng
    */
	public function updateTatToanTime_post() {
        $code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : null;
        if (!$code_contract ) {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "code_contract không được để trống."
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
		$condition = array(
			'code_contract' => $code_contract
		);
		$transactions = $this->transaction_model->find_where($condition);
		if (!empty($transactions)) {
			$createdAt = 0;
			$datePay = 0;
			$tatToanCode = null;
			foreach ($transactions as $key => $value) {
				// lấy ngày tạo và ngày thanh toán xa nhất của phiếu thu thanh toán ở trạng thái đã được kế toán duyệt
				if ($value["type"] == 4 && $value["status"] == 1) {
					if ($createdAt < $value["created_at"]) {
						$createdAt = $value["created_at"];
					}
					if ($datePay < $value["date_pay"]) {
						$datePay = $value["date_pay"];
					}
				}
				if ($value["type"] == 3 && $value["status"] == 1) {
					$tatToanCode = $value["code"];
				}
				
			}
		}
		if (empty($tatToanCode)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Hợp đồng không tồn tại phiếu thu tất toán ở trạng thái đã được kế toán duyệt"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($createdAt == 0 || $datePay == 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Hợp đồng không tồn tại phiếu thu thanh toán ở trạng thái đã được kế toán duyệt"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$update = $this->transaction_model->update(["code" => $tatToanCode], [
			"created_at" => (int)($createdAt + 5000), 
			"date_pay" => (int)($datePay + 5000)
		]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}
	// Reset lại toàn bộ hợp đồng không đủ điều kiện hưởng coupon giảm lãi trừ vào kỳ cuối.
	public function reset_coupon_interest_reduction_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$current_day = strtotime(date('Y-m-d') . ' 00:00:00');
		$contractData = $this->contract_model->find_where_select(array(
			'status' => 17,
			'loan_infor.code_coupon' => array('$nin' => array(' ','',null)),
			'expire_date' => array('$lt' => $current_day)
		),['_id','code_contract','status','disbursement_date','loan_infor.code_coupon','investor_code']);
		if (!empty($contractData)) {
			foreach ($contractData as $key => $contract) {
				$data_coupon = $this->coupon_model->findOne(array(
					"code" => $contract['loan_infor']['code_coupon']
				));
				if ($current_day > $contract['expire_date']) {
					if (!empty($data_coupon) && ($data_coupon['down_interest_on_month'] == 'active' || $data_coupon['is_reduction_interest'] == 'active')) {
						// Xóa bảng lãi kỳ, lãi tháng.
						$data_delete = array(
							"code_contract" => $contract['code_contract'],
						);
						$result_delete_and_update = $this->payment_model->delete_lai_ky_lai_thang($data_delete);
						if (!empty($result_delete_and_update['status']) && $result_delete_and_update['status'] == 200) {
							$data_generate = array(
								"code_contract" => $contract['code_contract'],
								"investor_code" =>$contract['investor_code'],
								"disbursement_date" => $contract['disbursement_date'],
								"interest_reduction" => 'deactive',
							);
							$result_generate = $this->generate_model->processGenerate($data_generate);
							if (!empty($result_generate['status']) && $result_generate['status'] == 200) {
								$result  = $this->allocation_model->payment_all_contract($data_generate);
							} else {
								$response = array(
									'status' => REST_Controller::HTTP_UNAUTHORIZED,
									'message' => "gen lãi không thành công"
								);
								$this->set_response($response, REST_Controller::HTTP_OK);
								return;
							}
						} else {
							$response = array(
								'status' => REST_Controller::HTTP_UNAUTHORIZED,
								'message' => "xóa không thành công"
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					}
				}
			}
		}
		echo "Updated!";
	}

	// Update chậm trả và lãi đã trừ kỳ cuối vào table contract
	public function update_date_late_and_interest_reduction_post()
	{
		$code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ?  $this->security->xss_clean($this->dataPost['code_contract']) : '';
		$code_coupon = !empty($this->security->xss_clean($this->dataPost['code_coupon'])) ?  $this->security->xss_clean($this->dataPost['code_coupon']) : '';
		$date_pay = !empty($this->security->xss_clean($this->dataPost['date_pay'])) ?  $this->security->xss_clean($this->dataPost['date_pay']) : '';
		$contractDB = $this->contract_model->find_one_select(['code_contract' => $code_contract],['_id']);
		$coupon_infor = $this->coupon_model->findOne(['code' => $code_coupon]);
		$temporary_plan_contracts = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
		$is_payment_slow = false;
		$reduced_profit = 0;
		$type_interest_reduction = '';
		if (!empty($coupon_infor)) {
			if (!empty($temporary_plan_contracts)) {
				// giam lai 03 thang dau
				if (isset($coupon_infor['is_reduction_interest']) && $coupon_infor['is_reduction_interest'] == "active") {
					$type_interest_reduction = '3';
					foreach ($temporary_plan_contracts as $key => $tempo) {
						if ($tempo['ky_tra'] == 1 || $tempo['ky_tra'] == 2 || $tempo['ky_tra'] == 3) {
							$reduced_profit += $tempo['lai_ky'];
						}
					}
				// giam lai 01 thang dau
				} elseif (isset($coupon_infor['down_interest_on_month']) && $coupon_infor['down_interest_on_month'] == "active") {
					$type_interest_reduction = '1';
					foreach ($temporary_plan_contracts as $key1 => $tempo1) {
						if ($tempo1['ky_tra'] == 1) {
							$reduced_profit += $tempo1['lai_ky'];
						}
					}
				}
				//fore bảng lãi kỳ để xác định chậm trả
				foreach ($temporary_plan_contracts as $temporary) {
					if ($temporary['so_ngay_cham_tra'] > 0) {
						$is_payment_slow = true;
						break;
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'amount_interest_reduction' => (!$is_payment_slow) ? $reduced_profit : 0,
		);
		if ($is_payment_slow) {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => true,
					'interest_reduction.amount_interest_reduction' => $response['amount_interest_reduction'],
					'interest_reduction.type_interest_reduction' => $type_interest_reduction,
					'interest_reduction.time' => $this->createdAt,
					'interest_reduction.by' => $this->uemail
				)
			);
		} else {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => false,
					'interest_reduction.amount_interest_reduction' => $response['amount_interest_reduction'],
					'interest_reduction.type_interest_reduction' => $type_interest_reduction,
					'interest_reduction.time' => $this->createdAt,
					'interest_reduction.by' => $this->uemail
					)
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	// check HĐ có phát sinh ngày chậm trả hay không (payment_all func chạy lại thanh toán cpanel)
	public function update_date_late_into_contract_post()
	{
		$code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ?  $this->security->xss_clean($this->dataPost['code_contract']) : '';
		$date_pay = !empty($this->security->xss_clean($this->dataPost['date_pay'])) ?  $this->security->xss_clean($this->dataPost['date_pay']) : '';
		$contractDB = $this->contract_model->find_one_select(['code_contract' => $code_contract],['_id']);
		$temporary_plan_contracts = $this->contract_tempo_model->find_where(['code_contract' => $code_contract]);
		$tempo_not_pay = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = strtotime(date('Y-m-d') . ' 23:59:59');
		$date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : '';
		$is_payment_slow = false;
		$time = 0;
		if (!empty($tempo_not_pay)) {
			if (!empty($date_pay)) {
				$time = intval(($date_pay - strtotime(date('Y-m-d', $date_pay_tempo) . ' 23:59:59')) / (24 * 60 * 60));
			} else {
				$time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo) . ' 23:59:59')) / (24 * 60 * 60));
			}
			if ($time > 3) {
				$is_payment_slow = true;
			}
		}

		if (!empty($temporary_plan_contracts)) {
			//fore bảng lãi kỳ để xác định chậm trả
			foreach ($temporary_plan_contracts as $temporary) {
				if ($temporary['so_ngay_cham_tra'] > 0) {
					$is_payment_slow = true;
					break;
				}
			}
		}
		if ($is_payment_slow) {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => true,
					'interest_reduction.time' => $this->createdAt,
					'interest_reduction.by' => $this->uemail
				)
			);
		} else {
			$this->contract_model->update(
				array('_id' => $contractDB['_id']),
				array(
					'interest_reduction.isset_date_late' => false,
					'interest_reduction.time' => $this->createdAt,
					'interest_reduction.by' => $this->uemail
				)
			);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Update date late success!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_one_wait_post()
	{
		$code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ?  $this->security->xss_clean($this->dataPost['code_contract']) : '';
		$transaction = $this->transaction_model->find_ond_tran_wait(['code_contract' => $code_contract]);
		$date_pay = '';
		if (!empty($transaction)) {
			$date_pay = !empty($transaction['date_pay']) ? $transaction['date_pay'] : '';
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'date_pay' => $date_pay
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function test1_post()
	{
		$data = $this->input->post();
		$code_contract = $data['code_contract'];
		$date_pay = $data['date_pay'];
		$truoc_tat_toan_phieu_thu=$this->transaction_model->get_tien_da_tra_truoc_tat_toan($code_contract,$date_pay);
		echo '<pre>';
		print_r($truoc_tat_toan_phieu_thu);
		echo '</pre>';

	}

}


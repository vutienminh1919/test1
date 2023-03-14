<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/HeyU.php';

use Restserver\Libraries\REST_Controller;

class Hey_u extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("hey_u_model");
		$this->load->model("log_hey_u_model");
		$this->load->model("history_heyu_model");
		$this->load->model('mic_tnds_model');
		$this->load->model("order_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$dataPost = $this->input->post();
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
				if ($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->name = $this->info['full_name'] ?? $this->info['email'];
					$this->phone = $this->info['phone_number'] ?? '';
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}

	public function recharge_the_driver_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_driver = !empty($data['code_driver']) ? $data['code_driver'] : '';
		$money = !empty($data['money']) ? trim(str_replace(array(',', '.',), '', $data['money'])) : '';
		$store_id = !empty($data['store']) ? $data['store'] : '';

		$storeUser = $this->role_model->get_store_user((string)$this->id);
		if (empty($storeUser)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bạn không phải nhân viên PGD"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($store_id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "PGD không thể trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($code_driver)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã tài xế không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($money)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($money < 100000 || $money > 1000000) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền nằm trong khoảng 100000 đến 1000000"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if ($money % 10000 != 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền phải là bội của 10000"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$heyU = new HeyU();
		$info_heyU = $heyU->find_user(['code' => $code_driver]);

		if ($info_heyU->code != 200) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $info_heyU->message
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$code = "HEYU_" . date('dmY') . '_' . uniqid();
		$param = [
			"code" => $code_driver,
			'amount' => (int)$money,
			'orderId' => $code
		];
		$result = $heyU->recharge($param);
		$dataLog = [
			'request' => $param,
			'response' => $result,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		];
		$this->log_hey_u_model->insert($dataLog);
		if ($result->code == 200) {
			$store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($store_id)]);
			$data = [
				'transaction_code' => $result->data->orderId,
				'code_driver' => $result->data->member->code,
				'name_driver' => $result->data->member->name,
				'money' => (string)$result->data->amount,
				'store' => [
					'name' => $store['name'],
					'id' => (string)$store['_id']
				],
				'status' => 10,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
			];
			$this->hey_u_model->insert($data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Nạp tiền thành công!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Nạp tiền thất bại!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_list_hey_u_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'heyU';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$code_driver_filter = !empty($data['code_driver_filter']) ? $this->security->xss_clean($data['code_driver_filter']) : '';
		$name_driver_filter = !empty($data['name_driver_filter']) ? $this->security->xss_clean($data['name_driver_filter']) : '';
		$code_transaction = !empty($data['code_transaction']) ? $this->security->xss_clean($data['code_transaction']) : '';
		$filter_by_store = !empty($data['filter_by_store']) ? $this->security->xss_clean($data['filter_by_store']) : '';
		$code_heyu = !empty($data['code_heyu']) ? $this->security->xss_clean($data['code_heyu']) : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($code_driver_filter)) {
			$condition['code_driver_filter'] = $code_driver_filter;
		}
		if (!empty($name_driver_filter)) {
			$condition['name_driver_filter'] = $name_driver_filter;
		}
		if (!empty($code_transaction)) {
			$condition['code_transaction'] = $code_transaction;
		}
		if (!empty($filter_by_store)) {
			$condition['filter_by_store'] = $filter_by_store;
		}
		if (!empty($code_heyu)) {
			$condition['code_heyu'] = $code_heyu;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
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
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['filter_by_store'] = $filter_by_store;
		}

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 20;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

		if ($tab == 'heyU') {
			$result = $this->hey_u_model->get_list_hey_u($condition, $per_page, $uriSegment);
			$total = $this->hey_u_model->count_list_hey_u($condition);
		} else {
			$result = $this->transaction_model->list_transaction_heyu($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_heyu($condition);
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

	public function get_hey_u_accounting_transfe_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = [];
		$data = $this->input->post();
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
		$heyU = $this->hey_u_model->get_hey_u_accounting_transfe($condition);
		$total_money = 0;
		foreach ($heyU as $value) {
			$total_money += (int)$value['money'];
		}
		$total = $this->hey_u_model->count_hey_u_accounting_transfe($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $heyU,
			'total' => $total,
			"total_money" => $total_money
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function create_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_heyu = !empty($data['code']) ? $data['code'] : '';
		$store_id = !empty($data['store']) ? $data['store'] : '';
		$store = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($store_id)]);
		$storeUser = $this->role_model->get_store_user((string)$this->id);
		if (empty($storeUser)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bạn không phải nhân viên PGD"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($code_heyu)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu gửi sang kế toán"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$check = [];
		foreach ($code_heyu as $item) {
			$heyU = $this->hey_u_model->findOne(['transaction_code' => $item]);
			array_push($check, $heyU['status']);
		}
		if (in_array(2, $check)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Gửi thất bại, tồn tại phiếu thu đã gửi duyệt!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$code = "PT_" . date('dmY') . '_' . uniqid();
		$money = 0;
		foreach ($code_heyu as $value) {
			$heyU = $this->hey_u_model->findOne(['transaction_code' => $value]);
			$this->hey_u_model->update(['_id' => $heyU['_id']], ['receipt_code' => $code, 'status' => 2]);
			$money += (int)$heyU['money'];
		}
		$data_transaction = [
			'code' => $code,
			'total' => (string)$money,
			'payment_method' => "1",
			'store' => [
				'name' => $store['name'],
				'id' => (string)$store['_id']
			],
			"customer_bill_name" => $this->name,
			"customer_bill_phone" => $this->phone,
			'type' => 7,
			'status' => 2,
			'code_coupon_cash' => $code_coupon,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		];
		$id_transaction = $this->transaction_model->insertReturnId($data_transaction);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Gửi yêu cầu thành công"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function detail_transaction_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$heyU = $this->hey_u_model->find_where(['receipt_code' => $code]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $heyU
		);
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

	public function get_history_heyU_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$condition = [];
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00') . '000',
				'end' => strtotime(trim($end) . ' 23:59:59') . '999'
			);
		}

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$history = $this->history_heyu_model->get_all($condition, $per_page, $uriSegment);
		foreach ($history as $value) {
			$heyU = $this->hey_u_model->findOne(['transaction_code' => $value['orderId']]);
			if (!empty($heyU)) {
				$value['status'] = $heyU['status'];
				$value['store'] = $heyU['store']['name'];
				$transation = $this->transaction_model->findOne(['code' => $heyU['receipt_code']]);
				if (!empty($transation)) {
					$value['transaction'] = $transation['code'];
					$value['store'] = $transation['store']['name'];
					$value['created_by'] = $transation['created_by'];
				}
			}
		}
		$total = $this->history_heyu_model->count_all($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $history,
			'total' => $total,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_total_pay_post()
	{
		$data = $this->input->post();
		$code_heyu = !empty($data['code']) ? $data['code'] : '';
		$total = 0;
		foreach ($code_heyu as $value) {
			$heyu = $this->hey_u_model->findOne(['transaction_code' => $value]);
			$total += (int)$heyu['money'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'total' => number_format($total) . " VND",
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_name_driver_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$code_driver = !empty($data['code_driver']) ? $data['code_driver'] : '';

		$heyU = new HeyU();
		$info_heyU = $heyU->find_user(['code' => $code_driver]);
		if ($info_heyU->code != 200) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tìm thấy!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'name' => $info_heyU->data->name,
				'message' => 'thanh cong'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function update_bo_sung_trang_thai_phieu_thu_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$transactions = $this->transaction_model->find_where(['type' => 7, 'status' => 1]);
		if (!empty($transactions)) {
			foreach ($transactions as $transaction) {
				$heyus = $this->hey_u_model->find_where(['receipt_code' => $transaction['code'], 'status' => 2]);
				if (!empty($heyus)) {
					foreach ($heyus as $heyu) {
						$this->hey_u_model->update(
							['_id' => $heyu['_id']],
							[
								'status' => 1,
								'note' => 'Bổ sung theo trạng thái transaction'
							]
						);
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_bo_sung_trang_thai_phieu_thu_mic_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$transactions = $this->transaction_model->find_where(['type' => 8, 'status' => 3]);

		if (!empty($transactions)) {
			foreach ($transactions as $transaction) {
				$mic_tnds = $this->mic_tnds_model->find_where(['receipt_code' => $transaction['code'], 'status' => 2]);
				if (!empty($mic_tnds)) {
					foreach ($mic_tnds as $mic) {
						$this->mic_tnds_model->update(
							['_id' => $mic['_id']],
							[
								'status' => 3,
								'note' => 'Bổ sung theo trạng thái transaction'
							]
						);
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_bo_sung_trang_thai_phieu_thu_thanh_toan_tien_ich_post()
	{
		$orders = $this->order_model->find_where(["status" => "success"]);
		if (!empty($orders)) {
			foreach ($orders as $order) {
				$transaction = $this->transaction_model->find_where(["code" => $order['transaction_code'], 'status' => array('$in' => array(3, "new"))]);
				if (!empty($transaction)) {
					foreach ($transaction as $tran) {
						$this->transaction_model->update(
							['_id' => $tran['_id']],
							[
								'status' => 1,
								'note' => 'Cập nhật trạng thái success từ bảng order'
							]
						);
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}

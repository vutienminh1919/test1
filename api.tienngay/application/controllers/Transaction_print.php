<?php
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Transaction_print extends REST_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('user_model');
		$this->load->model('store_model');
		$this->load->model('group_role_model');
		$this->load->model('transaction_model');
		$this->load->model('transaction_print_model');

		$this->load->model('role_model');

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
				if ( isset($this->dataPost['type']) && $this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ( isset($this->dataPost['type']) && $this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
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

	public function save_count_print_post() {
		$input = $this->input->post();

		$data = [];
		if ( !isset($input['user_print']) || $input['user_print'] == null ) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Không có người in',
			);
			return $this->set_response($response, REST_Controller::HTTP_OK);
		}
		if ( isset($input['code_transaction']) ) {
			$contract_data = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($input['code_transaction'])));
			if ($contract_data) {
				$store = $this->checkHelper($input['user_print'], $contract_data['store']);

				$data['code_transaction'] = $input['code_transaction'];
				$data['code_contract'] = $contract_data['code_contract'];
				$data['code_contract_disbursement'] = $contract_data['code_contract_disbursement'];
				$data['store'] = $store;
				$data['customer_name'] = $contract_data['customer_name'];
				$data['money'] = $contract_data['total'];
				$data['time_print'] = time();
				$data['user_print'] = $input['user_print'];
				if ($contract_data['store']['id'] != $store['id']) {
					$data['help_pgd_name'] = $contract_data['store'];
				}
			}
		}

		if (count($data) > 0) {
			$this->transaction_print_model->insert($data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Success',
			);
			return $this->set_response($response, REST_Controller::HTTP_OK);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Mã giao dịch không tồn tại',
			);
			return $this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function count_print_store_post() {
		$input = $this->input->post();

		$data = $this->transaction_print_model->get_by_store($input);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => $data
		);
		return $this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function count_print_contract_post() {
		$input = $this->input->post();

		$data = $this->transaction_print_model->get_by_store_and_contract($input);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => $data
		);
		return $this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_date_print_post() {
		$input = $this->input->post();
		$data = $this->transaction_print_model->find_condition($input);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => $data
		);
		return $this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function count_all_post() {
		$input = $this->input->post();

		$data = $this->transaction_print_model->count_all($input);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success',
			'data' => $data
		);
		return $this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function checkHelper($email, $storeInput) {
		$userInfo = $this->user_model->findOne(['email' => $email]);
		$userId = (string) $userInfo['_id'];
		$groupRoles = $this->role_model->find_where(array("status" => "active"));
		$arr = [];
		$checkStore = null;
		foreach($groupRoles as $groupRole) {
			if(empty($groupRole['users'])) continue;
			foreach($groupRole['users'] as $item) {
				if(key($item) == $userId) {
					if (isset($groupRole['stores']) && $groupRole['stores']) {
						foreach ($groupRole['stores'] as $itemStore) {
							if ($storeInput['id'] == key($itemStore)) {
								$checkStore = $storeInput;
							}
							array_push($arr, $itemStore);
						}
					}
				}
			}
		}
		if ( count($arr) == 1 ) {
			if ( is_null($checkStore) ) {
				$storeData = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId(key($arr[0])) ]);
				$store = [
					'id' => (string) $storeData['_id'],
					'name' => $storeData['name']
				];
			} else {
				$store = $checkStore;
			}
		} else {
			$store = [
				'id' => '00000',
				'name' => 'Thu hộ phòng'
			];
		}
		return $store;
	}

	public function check_helper_post() {
		$input = $this->input->post();

		$userInfo = $this->user_model->findOne(['email' => $input['email']]);
		$userId = (string) $userInfo['_id'];
		$groupRoles = $this->role_model->find_where(array("status" => "active"));
		$arr = [];
		foreach($groupRoles as $groupRole) {
			if(empty($groupRole['users'])) continue;
			foreach($groupRole['users'] as $item) {
				if(key($item) == $userId) {
					if ($groupRole['stores']) {
						foreach ($groupRole['stores'] as $itemStore) {
							array_push($arr, $itemStore);
						}
					}
				}
			}
		}
		return $this->set_response($arr, REST_Controller::HTTP_OK);
	}

	public function update_print_post() {
		$data = $this->transaction_print_model->find();
		foreach ($data as $key => $item) {
			$contract_data = $this->transaction_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($item['code_transaction'])));
			$this->transaction_print_model->update(
				array('_id' => new \MongoDB\BSON\ObjectId((string) $item['_id'])),
				array('money' => $contract_data['total'])
			);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Success'
		);
		return $this->set_response($response, REST_Controller::HTTP_OK);
	}

}

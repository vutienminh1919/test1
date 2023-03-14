<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;

class Debt_manager_app extends REST_Controller
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
		$this->load->model('contract_assign_debt_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('transaction_model');
		$this->load->model('call_debt_manager_model');
		$this->load->helper('lead_helper');
		$this->load->model('contract_debt_caller_model');
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
	}

	public function get_user_debt_post()
	{
		$role = $this->role_model->findOne(['slug' => 'phong-thu-hoi-no']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					$data[] = [
						'id' => $k,
						'email' => $i
					];
				}
			}
		}
		$res = [];
		foreach ($data as $d) {
			if ($d['email'] == "minhnd@tienngay.vn" || $d['email'] == "thuyetdv@tienngay.vn" || $d['email'] == 'dieplk@tienngay.vn') {
				continue;
			}
			array_push($res, $d);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $res
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	private function get_provice($provice)
	{
		$provinces = $this->province_model->find();
		foreach ($provinces as $key => $value) {
			if ($value->code == (string)$provice) {
				$provice = $value->name;
			}
		}
		return $provice;
	}

	private function get_district($provice, $district)
	{
		$districts = $this->district_model->find_where(array("parent_code" => (string)$provice));
		foreach ($districts as $key => $value) {
			if ($value->code == (string)$district) {
				$district = $value->name;
			}
		}
		return $district;
	}

	public function area_user_manager_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$id = !empty($data['id']) ? $data['id'] : '';
		if (!empty($id)) {
			$condition['id'] = $id;
		}
		$condition['status'] = 'active';
		$per_page = !empty($data['per_page']) ? $this->security->xss_clean($data['per_page']) : 30;
		$uriSegment = !empty($data['uriSegment']) ? $this->security->xss_clean($data['uriSegment']) : 0;
		$area = $this->area_debt_recovery_model->getAreaUser($condition, $per_page, $uriSegment);
		$total = $this->area_debt_recovery_model->getTotalAreaUser($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $area,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function create_radio_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$B1 = !empty($data['b1']) ? $this->security->xss_clean($data['b1']) : '';
		$B2 = !empty($data['b2']) ? $this->security->xss_clean($data['b2']) : '';
		$B3 = !empty($data['b3']) ? $this->security->xss_clean($data['b3']) : '';
		$B4 = !empty($data['b4']) ? $this->security->xss_clean($data['b4']) : '';
		$B5 = !empty($data['b5']) ? $this->security->xss_clean($data['b5']) : '';
		$B6 = !empty($data['b6']) ? $this->security->xss_clean($data['b6']) : '';
		$B7 = !empty($data['b7']) ? $this->security->xss_clean($data['b7']) : '';
		$B8 = !empty($data['b8']) ? $this->security->xss_clean($data['b8']) : '';
		$month = !empty($data['month']) ? $this->security->xss_clean($data['month']) : '';
		$year = !empty($data['year']) ? $this->security->xss_clean($data['year']) : '';
		if (empty($B1)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B1 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B2)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B2 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B3)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B3 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B4)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B4 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B5)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B5 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B6)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B6 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B7)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B7 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B8)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B8 không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($month)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tháng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($year)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Năm không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		$data = [
			'B1' => $B1,
			'B2' => $B2,
			'B3' => $B3,
			'B4' => $B4,
			'B5' => $B5,
			'B6' => $B6,
			'B7' => $B7,
			'B8' => $B8,
			'month' => (int)$month,
			'year' => (int)$year,
			'name' => $month . $year,
			'status' => 'active',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		];
		$this->radio_field_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thành công!',

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function assignForEmployee_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$province = !empty($data['province']) ? $this->security->xss_clean($data['province']) : '';
		$district = !empty($data['district']) ? $this->security->xss_clean($data['district']) : '';

		if (empty($user_id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "User không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($province)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã tỉnh không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($district)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã quận huyện không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (!empty($user_id) && !empty($district)) {
			$area = $this->debt_recovery_model->findOne(['user_id' => $user_id, 'district' => $district, 'status' => 'active']);
			if (!empty($area)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Khu vực đã được gán cho nhân viên "
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return false;
			} else {
				$area = $this->debt_recovery_model->findOne(['user_id' => $user_id, 'district' => $district, 'status' => 'block']);
				if (!empty($area)) {
					$this->debt_recovery_model->update(['_id' => $area['_id']], ['status' => 'active', 'updated_at' => $this->createdAt, 'province' => $province]);
					$this->log_model->insert(
						[
							"old" => $area,
							'new' => $data,
							'create_by' => $this->uemail,
							'created_at' => $this->createdAt
						]
					);
					$this->push_noti_app($user_id, $province, $district);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Gán quyền thành công",
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
				} else {
					$id = $this->debt_recovery_model->insertReturnId(array(
						'user_id' => (string)$user_id,
						'province' => $province,
						'district' => $district,
						'status' => 'active',
						'create_by' => $this->uemail,
						'created_at' => $this->createdAt
					));
					$this->log_model->insert(
						[
							'new' => $data,
							'create_by' => $this->uemail,
							'created_at' => $this->createdAt
						]
					);
					$this->push_noti_app($user_id, $province, $district);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Gán quyền thành công",
						'data' => [
							'province' => $province,
							'district' => $district,
							'user_id' => (string)$user_id,
						]
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
				}
			}
		}
	}

	private function get_count_notification_user($user_id)
	{
		$condition = [];
		$condition['user_id'] = $user_id;
		$condition['status'] = 1;
		$unRead = $this->notification_app_model->get_count_notification_user($condition);
		return $unRead;
	}

	private function push_noti_app($user_id, $province, $district)
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($user_id)]);
		$address = $this->get_provice_district($province, $district);
		$data_notification = [
			'action' => 'area',
			'user_id' => (string)$user_id,
			'phone_number' => (string)$user['phone_number'],
			'email' => (string)$user['email'],
			'province' => $province,
			'district' => $district,
			'status' => 1, //1: new, 2 : read, 3: block,
			'created_at' => $this->createdAt,
			"created_by" => $this->uemail,
			'message' => 'Bạn đã được phân khu vực, huyện ' . $address['district'] . ', ' . $address['provice']
		];
		$this->notification_app_model->insertReturnId($data_notification);
		$device = $this->device_model->find_where(['user_id' => (string)$user_id, 'status' => 'active']);
		if (!empty($device)) {
			$badge = $this->get_count_notification_user((string)$user_id);
			$fcm = new Fcm();
			$to = [];
			foreach ($device as $de) {
				$to[] = $de->device_token;
			}
			$fcm->setTitle('Hệ thống thông báo');
			$fcm->setMessage('Bạn đã được phân khu vực, huyện ' . $address['district'] . ', ' . $address['provice']);
			$fcm->setBadge((int)$badge);
			$message = $fcm->getMessage();
			$fcm->setType('debt');
			$data = $fcm->getData();
			$result = $fcm->sendMultipleTHN($to, $message, $data);
		}
	}

	public function get_provice_district($provice, $district)
	{
		$data = [];
		$provinces = $this->province_model->find();
		foreach ($provinces as $key => $value) {
			if ($value->code == (string)$provice) {
				$data['provice'] = $value->name;
			}
		}
		$districts = $this->district_model->find_where(array("parent_code" => (string)$provice));
		foreach ($districts as $key => $value) {
			if ($value->code == (string)$district) {
				$data['district'] = $value->name;
			}
		}
		if (empty($data['district'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã quận huyện không nằm trong tỉnh thành!"
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
			return false;
		} else {
			return $data;
		}
	}

	public function block_area_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$this->debt_recovery_model->update(['_id' => new  MongoDB\BSON\ObjectId((string)$id)], ['status' => 'block']);
		$area = $this->debt_recovery_model->findOne(['_id' => new  MongoDB\BSON\ObjectId((string)$id)]);
		$this->push_noti_block_area($area['user_id'], $area['province'], $area['district']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhật thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function push_noti_block_area($user_id, $province, $district)
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($user_id)]);
		$address = $this->get_provice_district($province, $district);
		$data_notification = [
			'action' => 'area',
			'user_id' => (string)$user_id,
			'phone_number' => (string)$user['phone_number'],
			'email' => (string)$user['email'],
			'province' => $province,
			'district' => $district,
			'status' => 1, //1: new, 2 : read, 3: block,
			'created_at' => $this->createdAt,
			"created_by" => $this->uemail,
			'message' => 'Bạn không còn được phân khu vực, huyện ' . $address['district'] . ', ' . $address['provice']
		];
		$this->notification_app_model->insertReturnId($data_notification);
		$device = $this->device_model->find_where(['user_id' => (string)$user_id, 'status' => 'active']);
		if (!empty($device)) {
			$badge = $this->get_count_notification_user((string)$user_id);
			$fcm = new Fcm();
			$to = [];
			foreach ($device as $de) {
				$to[] = $de->device_token;
			}
			$fcm->setTitle('Hệ thống thông báo');
			$fcm->setMessage('Bạn không còn đươc phân khu vực, huyện ' . $address['district'] . ', ' . $address['provice']);
			$fcm->setBadge((int)$badge);
			$message = $fcm->getMessage();
			$fcm->setType('debt');
			$data = $fcm->getData();
			$result = $fcm->sendMultipleTHN($to, $message, $data);
		}
	}

	public function get_district_from_province_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$districts = $this->district_model->find_where(array("parent_code" => (string)$id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $districts,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_radio_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$list = $this->radio_field_model->find_where(['status' => 'active']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $list,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function block_radio_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$this->radio_field_model->update(['_id' => new  MongoDB\BSON\ObjectId((string)$id)], ['status' => 'block']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhật thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function showRadio_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$radio = $this->radio_field_model->findOne(['_id' => new  MongoDB\BSON\ObjectId((string)$id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $radio,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function updateRadio_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$B1 = !empty($data['b1']) ? $this->security->xss_clean($data['b1']) : '';
		$B2 = !empty($data['b2']) ? $this->security->xss_clean($data['b2']) : '';
		$B3 = !empty($data['b3']) ? $this->security->xss_clean($data['b3']) : '';
		$B4 = !empty($data['b4']) ? $this->security->xss_clean($data['b4']) : '';
		$B5 = !empty($data['b5']) ? $this->security->xss_clean($data['b5']) : '';
		$B6 = !empty($data['b6']) ? $this->security->xss_clean($data['b6']) : '';
		$B7 = !empty($data['b7']) ? $this->security->xss_clean($data['b7']) : '';
		$B8 = !empty($data['b8']) ? $this->security->xss_clean($data['b8']) : '';
		if (empty($B1)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B1 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B2)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B2 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B3)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B3 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B4)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B4 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B5)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B5 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B6)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B6 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B7)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B7 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($B8)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "B8 không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}

		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "User không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		$data = [
			'B1' => $B1,
			'B2' => $B2,
			'B3' => $B3,
			'B4' => $B4,
			'B5' => $B5,
			'B6' => $B6,
			'B7' => $B7,
			'B8' => $B8,
			'updated_at' => $this->createdAt,
			'updated_by' => $this->uemail
		];
		$radio = $this->radio_field_model->findOne(['_id' => new  MongoDB\BSON\ObjectId((string)$id)]);
		$this->radio_field_model->update(['_id' => new  MongoDB\BSON\ObjectId((string)$id)], $data);
		$this->log_model->insert([
			'type' => 'radio_debt',
			'old' => $radio,
			'new' => $data,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $radio,
			'message' => 'Cập nhật thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_location_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$location = $this->tracking_location_model->get_location_user(['user_id' => new  MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $location,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function add_user_debt_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$id_contract = !empty($data['id_contract']) ? $this->security->xss_clean($data['id_contract']) : '';
		if (empty($user_id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "User không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($id_contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (!empty($id_contract)) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract)]);
			if (empty($contract)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã hợp đồng không tồn tại!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return false;
			}
		}
		if (!empty($user_id) && !empty($id_contract)) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract), 'user_debt' => (string)$user_id]);
			if (!empty($contract)) {
				$check_assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
				if (!empty($check_assign)) {
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã hợp đồng đã được gán cho nhân viên này!"
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return false;
				} else {
					$this->contract_model->update(['_id' => new  MongoDB\BSON\ObjectId($id_contract)], ['user_debt' => (string)$user_id]);
					$this->log_call_debt_model->insert(
						[
							"type" => "contract",
							"action" => "note_reminder",
							'old' => $contract['result_reminder'] ?? "",
							"contract_id" => (string)$contract['_id'],
							'new' => [
								'user_id' => $user_id,
							],
							'created_by' => $this->uemail,
							'created_at' => $this->createdAt
						]
					);
					$this->push_noti_assign($user_id, $id_contract);
					$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
					$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
					$assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
					if (empty($assign)) {
						$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, 'month' => date('m'), 'year' => date('Y')]);
						if (!empty($assign1)) {
							$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => $user_id]);
						} else {
							$this->contract_assign_debt_model->insert(
								[
									"contract_id" => $id_contract,
									"user_id" => $user_id,
									"pos" => $pos ? (int)round($pos) : 0,
									"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
									"time_due" => !empty($bucket) ? $bucket['time'] : 0,
									'month' => date('m'),
									'year' => date('Y'),
									'created_at' => $this->createdAt,
									'created_by' => $this->uemail
								]
							);
						}
					}
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Cập nhật thành công!'
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else {
				$contract1 = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract)]);
				$this->contract_model->update(['_id' => new  MongoDB\BSON\ObjectId($id_contract)], ['user_debt' => (string)$user_id]);
				$this->log_call_debt_model->insert(
					[
						"type" => "contract",
						"action" => "note_reminder",
						'old' => $contract1['result_reminder'] ?? '',
						"contract_id" => (string)$contract1['_id'],
						'new' => [
							'user_id' => $user_id,
						],
						'created_by' => $this->uemail,
						'created_at' => $this->createdAt
					]
				);
				//push noti
				$this->push_noti_assign($user_id, $id_contract);
				$pos = $this->lay_tien_goc_con_lai_chua_tra($contract1['code_contract']);
				$bucket = $this->lay_nhom_no_hop_dong($contract1['code_contract']);
				$assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
				if (empty($assign)) {
					$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, 'month' => date('m'), 'year' => date('Y')]);
					if (!empty($assign1)) {
						$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => $user_id]);
					} else {
						$this->contract_assign_debt_model->insert(
							[
								"contract_id" => $id_contract,
								"user_id" => $user_id,
								"pos" => $pos ? (int)round($pos) : 0,
								"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
								"time_due" => !empty($bucket) ? $bucket['time'] : 0,
								'month' => date('m'),
								'year' => date('Y'),
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail
							]
						);
					}
				}
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'Cập nhật thành công!'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

	}

	public function get_contract_user_debt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($this->dataPost['start']) ? strtotime(trim($this->security->xss_clean($data['start'])) . ' 00:00:00') : strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = !empty($this->dataPost['end']) ? strtotime(trim($this->security->xss_clean($data['end'])) . ' 23:59:59') : strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$id_card = !empty($data['id_card']) ? $data['id_card'] : "";
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : "";
		$customer_phone_number = !empty($data['customer_phone_number']) ? $data['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";
//		if (empty($user_id)) {
//			$response = array(
//				'status' => REST_Controller::HTTP_UNAUTHORIZED,
//				'message' => "User không được để trống!"
//			);
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return false;
//		}
		$condition = array(
			'start' => $start,
			'end' => $end
		);
		$area = $this->area_debt_recovery_model->find_where(array("user_id" => (string)$user_id, "status" => "active"));
//		if (empty($area)) {
//			$response = array(
//				'status' => REST_Controller::HTTP_FORBIDDEN,
//				"code" => 19,
//				'message' => "User chưa được phân quyền vùng hoạt đông"
//			);
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return false;
//		}
//		if (!empty($area)) {
//			$district_assign = array_unique(array_column((array)$area, 'district'));
//			$province_assign = array_unique(array_column((array)$area, 'province'));
//		}
//		if (!empty($province)) {
//			if (in_array($province, $province_assign) == 1) {
//				$condition['province'] = $province;
//			} else {
//				$response = array(
//					'status' => REST_Controller::HTTP_FORBIDDEN,
//					"code" => 20,
//					'message' => "User không có quyền tại vùng này"
//				);
//				$this->set_response($response, REST_Controller::HTTP_OK);
//				return false;
//			}
//		}
//		if (!empty($district)) {
//			$condition['district'] = array($district);
//		} else {
//			$condition['district'] = $district_assign;
//		}
		if (!empty($id_card)) {
			$condition['id_card'] = $id_card;
		}
		if (!empty($bucket)) {
			$condition['bucket'] = $bucket;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		$condition['reminder_now'] = "37";
		$user_debt = $this->get_user_debt();
		$condition['user_debt'] = [];
		foreach ($user_debt as $u) {
			if ($u['id'] == $user_id) {
				continue;
			}
			array_push($condition['user_debt'], $u['id']);
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$contract = $this->contract_debt_model->get_contract_field_debt($condition, $per_page, $uriSegment);
		$total = $this->contract_debt_model->get_contract_field_debt_total($condition);
		if (!empty($contract)) {
			foreach ($contract as $c) {
				if (!empty($c['user_debt'])) {
					$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($c['user_debt'])]);
					$c['user_debt'] = $user['email'];
				}
				$debt = $this->contract_debt_recovery_model->find_where(['contract_id' => (string)$c['_id'], 'user_id' => (string)$user_id]);
				if (empty($debt)) {
					$c['evaluate'] = 3;
				} else {
					if (!empty($debt[0]['evaluate'])) {
						if (!empty($debt[0]['evaluate'] == 1)) {
							$c['evaluate'] = 1;
						}
						if (!empty($debt[0]['evaluate'] == 2)) {
							$c['evaluate'] = 2;
						}
						if (!empty($debt[0]['evaluate'] == 4)) {
							$c['evaluate'] = 4;
						}
						if (!empty($debt[0]['evaluate'] == 5)) {
							$c['evaluate'] = 5;
						}
						if (!empty($debt[0]['evaluate'] == 6)) {
							$c['evaluate'] = 6;
						}
					} else {
						$c['evaluate'] = 3;
					}
				}
				$cond = array();
				if (isset($c['code_contract'])) {
					$cond = array(
						'code_contract' => $c['code_contract'],
						'status' => 1
					);
				}
				$c['debt'] = !empty($debt) ? $debt : '';
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				if (!empty($detail)) {
					$time = 0;
					$current_day = strtotime(date('m/d/Y'));
					$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
					$time = intval(($current_day - $datetime) / (24 * 60 * 60));
					if ($time <= 0) {
						$c['bucket'] = 'B0';
					} else if ($time >= 1 && $time <= 30) {
						$c['bucket'] = 'B1';
					} else if ($time > 30 && $time <= 60) {
						$c['bucket'] = 'B2';
					} else if ($time > 60 && $time <= 90) {
						$c['bucket'] = 'B3';
					} else if ($time > 90 && $time <= 120) {
						$c['bucket'] = 'B4';
					} else if ($time > 120 && $time <= 150) {
						$c['bucket'] = 'B5';
					} else if ($time > 150 && $time <= 180) {
						$c['bucket'] = 'B6';
					} else if ($time > 180 && $time <= 210) {
						$c['bucket'] = 'B7';
					} else if ($time > 210 && $time <= 270) {
						$c['bucket'] = 'B8';
					} else if ($time > 270 && $time <= 300) {
						$c['bucket'] = 'B9';
					} else if ($time > 300 && $time <= 330) {
						$c['bucket'] = 'B10';
					} else if ($time > 330 && $time <= 360) {
						$c['bucket'] = 'B11';
					} else {
						$c['bucket'] = 'B12';
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total,
			'district' => $district_assign,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function get_user_debt()
	{
		$role = $this->role_model->findOne(['slug' => 'phong-thu-hoi-no']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					$data[] = [
						'id' => $k,
						'email' => $i
					];
				}
			}
		}
		$res = [];
		foreach ($data as $d) {
			if ($d['email'] == "minhnd@tienngay.vn" || $d['email'] == "thuyetdv@tienngay.vn" || $d['email'] == 'dieplk@tienngay.vn') {
				continue;
			}
			array_push($res, $d);
		}

		return $res;
	}

	public function showContractDebt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getContractDebt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $this->security->xss_clean(trim($data['code_contract_disbursement'])) : '';
		$contract = $this->contract_model->findOne(['code_contract' => $code_contract_disbursement, 'status' => ['$gte' => 17]]);
		if (!empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $contract,
				'message' => 'thành công!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$contract1 = $this->contract_model->findOne(['code_contract_disbursement' => $code_contract_disbursement, 'status' => ['$gte' => 17]]);
			if (!empty($contract1)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => $contract1,
					'message' => 'thành công!'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
	}

	public function manager_add_user_debt_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$id_contract = !empty($data['id_contract']) ? $this->security->xss_clean($data['id_contract']) : '';
		$note = !empty($data['note']) ? $this->security->xss_clean($data['note']) : '';
		if (empty($user_id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "User không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($note)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ghi chú không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (empty($id_contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã hợp đồng không được để trống!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return false;
		}
		if (!empty($id_contract)) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract)]);
			if (empty($contract)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Mã hợp đồng không tồn tại!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return false;
			}
		}
		if (!empty($user_id) && !empty($id_contract)) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract), 'user_debt' => (string)$user_id]);
			if (!empty($contract)) {
				$check_assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
				if (!empty($check_assign)) {
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Mã hợp đồng đã được gán cho nhân viên trong tháng " . $dateNow['mon'] . " này!"
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return false;
				} else {
					$result_reminder = !empty($contract['result_reminder']) ? $contract['result_reminder'] : array();
					$note_reminder = array(
						"reminder" => "37",
						"note" => $note,
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
						'user_debt' => (string)$user_id,
					);
					$this->contract_model->update(['_id' => new  MongoDB\BSON\ObjectId($id_contract)], $arrUpdate);
					$this->log_call_debt_model->insert(
						[
							"type" => "contract",
							"action" => "note_reminder",
							"contract_id" => (string)$contract['_id'],
							'old' => $contract['result_reminder'] ?? "",
							'new' => [
								'user_id' => $user_id,
								'note_reminder' => $note_reminder,
							],
							"created_at" => $this->createdAt,
							"created_by" => $this->uemail
						]
					);
					$this->push_noti_assign($user_id, $id_contract);
					$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
					$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
					$assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
					if (empty($assign)) {
						$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, 'month' => date('m'), 'year' => date('Y')]);
						if (!empty($assign1)) {
							$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => $user_id]);
						} else {
							$this->contract_assign_debt_model->insert(
								[
									"contract_id" => $id_contract,
									"user_id" => $user_id,
									"pos" => $pos ? (int)round($pos) : 0,
									"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
									"time_due" => !empty($bucket) ? $bucket['time'] : 0,
									'month' => date('m'),
									'year' => date('Y'),
									'created_at' => $this->createdAt,
									'created_by' => $this->uemail
								]
							);
						}
					}
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Cập nhật thành công!'
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			} else {
				$contract1 = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_contract)]);
				$result_reminder = !empty($contract1['result_reminder']) ? $contract1['result_reminder'] : array();
				$note_reminder = array(
					"reminder" => "37",
					"note" => $note,
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
					'user_debt' => (string)$user_id,
				);
				$this->contract_model->update(['_id' => new  MongoDB\BSON\ObjectId($id_contract)], $arrUpdate);
				$this->log_call_debt_model->insert(
					[
						"type" => "contract",
						"action" => "note_reminder",
						"contract_id" => (string)$contract1['_id'],
						'old' => $contract1['result_reminder'] ?? "",
						'new' => [
							'user_id' => $user_id,
							'note_reminder' => $note_reminder,
						],
						"created_at" => $this->createdAt,
						"created_by" => $this->uemail
					]
				);
				$this->push_noti_assign($user_id, $id_contract);
				$pos = $this->lay_tien_goc_con_lai_chua_tra($contract1['code_contract']);
				$bucket = $this->lay_nhom_no_hop_dong($contract1['code_contract']);
				$assign = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, "user_id" => $user_id, 'month' => date('m'), 'year' => date('Y')]);
				if (empty($assign)) {
					$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => $id_contract, 'month' => date('m'), 'year' => date('Y')]);
					if (!empty($assign1)) {
						$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => $user_id]);
					} else {
						$this->contract_assign_debt_model->insert(
							[
								"contract_id" => $id_contract,
								"user_id" => $user_id,
								"pos" => $pos ? (int)round($pos) : 0,
								"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
								"time_due" => !empty($bucket) ? $bucket['time'] : 0,
								'month' => date('m'),
								'year' => date('Y'),
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail
							]
						);
					}
				}
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'Cập nhật thành công!'
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
	}

	private function push_noti_assign($user_id, $contract_id)
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($user_id)]);
		$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($contract_id)]);
		$code_contract = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : $contract['code_contract'];
		$data_notification = [
			'action' => 'assign_contract',
			'user_id' => (string)$user_id,
			'phone_number' => (string)$user['phone_number'],
			'email' => (string)$user['email'],
			'action_id' => (string)$contract_id,
			'status' => 1, //1: new, 2 : read, 3: block,
			'created_at' => $this->createdAt,
			"created_by" => $this->uemail,
			'message' => 'Hợp đồng cần thu hồi ' . $code_contract . ' vừa được chuyển cho bạn!'
		];
		$this->notification_app_model->insertReturnId($data_notification);
		$device = $this->device_model->find_where(['user_id' => (string)$user_id, 'status' => 'active']);
		if (!empty($device)) {
			$badge = $this->get_count_notification_user((string)$user_id);
			$fcm = new Fcm();
			$to = [];
			foreach ($device as $de) {
				$to[] = $de->device_token;
			}
			$fcm->setTitle('Hệ thống thông báo');
			$fcm->setMessage('Hợp đồng cần thu hồi ' . $code_contract . ' vừa được chuyển cho bạn!');
			$fcm->setBadge((int)$badge);
			$message = $fcm->getMessage();
			$fcm->setType('debt');
			$fcm->setContractId($contract_id);
			$data = $fcm->getData();
			$result = $fcm->sendMultipleTHN($to, $message, $data);
		}
	}

	public function manager_add_user_debt_all_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean($data['customer_name']) : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$email = !empty($data['email']) ? $this->security->xss_clean(trim($data['email'])) : '';
		$code = !empty($data['code']) ? $this->security->xss_clean(trim($data['code'])) : '';
		$contract = $this->getContractDebt($code_contract, $code);
		$user = $this->user_model->findOne(['email' => $email, 'status' => 'active']);
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Cập nhật thất bại!',
				'data2' => $code_contract
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			if (empty($user)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'User Khong dung!',
					'data2' => $code_contract
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				if (!empty($contract['user_debt'])) {
					if ((string)$user['_id'] == $contract['user_debt']) {
						$check_assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], "user_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
						if (!empty($check_assign)) {
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => "Mã hợp đồng đã được gán cho nhân viên này!"
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
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
							// update status bảng contract_deb_caller => Yêu cầu chuyển Field thành công
							$this->contract_debt_caller_model->update(['code_contract' => $contract['code_contract']], ['status' => 279]);
							$this->log_call_debt_model->insert(
								[
									"type" => "contract",
									"action" => "note_reminder",
									"contract_id" => (string)$contract['_id'],
									'old' => $contract['result_reminder'] ?? '',
									'new' => [
										'user_id' => !empty($user) ? (string)$user['_id'] : '',
										'note_reminder' => $note_reminder,
									],
									"created_at" => $this->createdAt,
									"created_by" => $this->uemail
								]
							);
							$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
							$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
							$assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], "user_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
							if (empty($assign)) {
								$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
								if (!empty($assign1)) {
									$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => (string)$user['_id']]);
								} else {
									$this->contract_assign_debt_model->insert(
										[
											"contract_id" => (string)$contract['_id'],
											"user_id" => (string)$user['_id'],
											"pos" => $pos ? (int)round($pos) : 0,
											"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
											"time_due" => !empty($bucket) ? $bucket['time'] : 0,
											'month' => date('m'),
											'year' => date('Y'),
											'created_at' => $this->createdAt,
											'created_by' => $this->uemail
										]
									);
								}
							}
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'Cập nhật thành công!',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
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
						// update status bảng contract_deb_caller => Yêu cầu chuyển Field thành công
						$this->contract_debt_caller_model->update(['code_contract' => $contract['code_contract']], ['status' => 279]);
						$this->log_call_debt_model->insert(
							[
								"type" => "contract",
								"action" => "note_reminder",
								"contract_id" => (string)$contract['_id'],
								'old' => $contract['result_reminder'] ?? '',
								'new' => [
									'user_id' => !empty($user) ? (string)$user['_id'] : '',
									'note_reminder' => $note_reminder,
								],
								"created_at" => $this->createdAt,
								"created_by" => $this->uemail
							]
						);
						$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
						$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
						$assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], "user_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
						if (empty($assign)) {
							$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
							if (!empty($assign1)) {
								$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => (string)$user['_id']]);
							} else {
								$this->contract_assign_debt_model->insert(
									[
										"contract_id" => (string)$contract['_id'],
										"user_id" => (string)$user['_id'],
										"pos" => $pos ? (int)round($pos) : 0,
										"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
										"time_due" => !empty($bucket) ? $bucket['time'] : 0,
										'month' => date('m'),
										'year' => date('Y'),
										'created_at' => $this->createdAt,
										'created_by' => $this->uemail
									]
								);
							}
						}
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Cập nhật thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
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
					// update status bảng contract_deb_caller => Yêu cầu chuyển Field thành công
					$this->contract_debt_caller_model->update(['code_contract' => $contract['code_contract']], ['status' => 279]);
					$this->log_call_debt_model->insert(
						[
							"type" => "contract",
							"action" => "note_reminder",
							"contract_id" => (string)$contract['_id'],
							'old' => $contract['result_reminder'] ?? '',
							'new' => [
								'user_id' => !empty($user) ? (string)$user['_id'] : '',
								'note_reminder' => $note_reminder,
							],
							"created_at" => $this->createdAt,
							"created_by" => $this->uemail
						]
					);
					$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
					$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
					$assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], "user_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
					if (empty($assign)) {
						$assign1 = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], 'month' => date('m'), 'year' => date('Y')]);
						if (!empty($assign1)) {
							$this->contract_assign_debt_model->update(['_id' => $assign1['_id']], ["user_id" => (string)$user['_id']]);
						} else {
							$this->contract_assign_debt_model->insert(
								[
									"contract_id" => (string)$contract['_id'],
									"user_id" => (string)$user['_id'],
									"pos" => $pos ? (int)round($pos) : 0,
									"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
									"time_due" => !empty($bucket) ? $bucket['time'] : 0,
									'month' => date('m'),
									'year' => date('Y'),
									'created_at' => $this->createdAt,
									'created_by' => $this->uemail
								]
							);
						}
					}
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Cập nhật thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
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
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean(trim($data['customer_name'])) : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$email = !empty($data['email']) ? $this->security->xss_clean(trim($data['email'])) : '';
		$contract = $this->contract_model->findOne(['code_contract_disbursement' => trim($code_contract)]);
		$user = $this->user_model->findOne(['email' => $email]);
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Ma hd ko dung!',
				'data2' => $code_contract
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			if (empty($user)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'user khong dung',
					'data2' => $code_contract
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				if (!empty($contract['user_debt'])) {
					$check_assign = $this->contract_assign_debt_model->findOne(["contract_id" => (string)$contract['_id'], "user_id" => (string)$user['_id'], 'month' => date('m'), 'year' => date('Y')]);
					if (!empty($check_assign)) {
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => "Mã hợp đồng đã được gán cho nhân viên này!"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Cập nhật thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				} else {
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Cập nhật thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}
	}

	public function contract_tempo_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$this->dataPost = $this->input->post();
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
		$condition = [];
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$condition['status'] = (int)$status;
		if (!empty($status_disbursement)) {
			$condition['status_disbursement'] = $status_disbursement;
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
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

		$contract = array();
		$contract = $this->contract_debt_model->excelContractByTime($condition);
		$total = $this->contract_debt_model->excelContractByTimeTotal($condition);

		if (!empty($contract)) {
			foreach ($contract as $key => $c) {
				$detail = $this->contract_tempo_model->getContract(['code_contract' => $c['code_contract']]);
				if (!empty($detail)) {
					$c['tien_tra_1_ky'] = $detail[0]['tien_tra_1_ky'];
				}
				if (!empty($detail) && $detail[0]['status'] == 1) {
					$current_day = strtotime(date('m/d/Y'));
					$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
					$time = intval(($current_day - $datetime) / (24 * 60 * 60));
					if ($time <= 0) {
						$c['bucket'] = 'B0';
					} else if ($time >= 1 && $time <= 30) {
						$c['bucket'] = 'B1';
					} else if ($time > 30 && $time <= 60) {
						$c['bucket'] = 'B2';
					} else if ($time > 60 && $time <= 90) {
						$c['bucket'] = 'B3';
					} else if ($time > 90 && $time <= 120) {
						$c['bucket'] = 'B4';
					} else if ($time > 120 && $time <= 150) {
						$c['bucket'] = 'B5';
					} else if ($time > 150 && $time <= 180) {
						$c['bucket'] = 'B6';
					} else if ($time > 180 && $time <= 210) {
						$c['bucket'] = 'B7';
					} else if ($time > 210 && $time <= 270) {
						$c['bucket'] = 'B8';
					} else if ($time > 270 && $time <= 300) {
						$c['bucket'] = 'B9';
					} else if ($time > 300 && $time <= 330) {
						$c['bucket'] = 'B10';
					} else if ($time > 330 && $time <= 360) {
						$c['bucket'] = 'B11';
					} else {
						$c['bucket'] = 'B12';
					}
				} else if (!empty($detail) && $detail[0]['status'] == 2) {
					$c['bucket'] = 'B0';
				} else {
					$c['bucket'] = '-';
				}
				$c['time'] = $time;
				if ($c['status'] == 19 || $c['status'] == 23)
					$c['time'] = '-';
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

	public function get_all_contract_debt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($this->dataPost['start']) ? strtotime(trim($this->security->xss_clean($data['start'])) . ' 00:00:00') : strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = !empty($this->dataPost['end']) ? strtotime(trim($this->security->xss_clean($data['end'])) . ' 23:59:59') : strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$id_card = !empty($data['id_card']) ? $data['id_card'] : "";
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : "";
		$customer_phone_number = !empty($data['customer_phone_number']) ? $data['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";
		$condition = array(
			'start' => $start,
			'end' => $end
		);
		if (!empty($user_id)) {
			$condition['user_id'] = $user_id;
		}
		if (!empty($id_card)) {
			$condition['id_card'] = $id_card;
		}
		if (!empty($bucket)) {
			$condition['bucket'] = $bucket;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

		$condition['reminder_now'] = "37";
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$contract = $this->contract_debt_model->get_all_contract_field_debt($condition, $per_page, $uriSegment);
		$total = $this->contract_debt_model->get_all_contract_field_debt_total($condition);
		if (!empty($contract)) {
			foreach ($contract as $c) {
				if ($c['status'] == 19) {
					$c['complete'] = true;
				}
				if (!empty($c['user_debt'])) {
					$transaction = $this->transaction_model->findOne(['date_pay' => ['$gte' => $start, '$lte' => $end], 'code_contract' => (string)$c['code_contract']]);
					$assign = $this->contract_assign_debt_model->findOne(['created_at' => ['$gte' => $start, '$lte' => $end], 'user_id' => $c['user_debt'], 'contract_id' => (string)$c['_id']]);
					$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($c['user_debt'])]);
					$c['user_debt'] = $user['email'];
					if (!empty($assign)) {
						$time = $this->lay_ngay_tre_hop_dong($c['code_contract']);
						if (!empty($assign['time_due']) && !empty($transaction)) {
							if (($time) < $assign['time_due']) {
								$c['complete'] = true;
							}
						}
					}
				}
				$debt = $this->contract_debt_recovery_model->find_where(['contract_id' => (string)$c['_id'], 'created_at' => ['$gte' => $start, '$lte' => $end]]);
				if (empty($debt)) {
					$c['evaluate'] = 3;
				} else {
					if (!empty($debt[0]['evaluate'])) {
						if (!empty($debt[0]['evaluate'] == 1)) {
							$c['evaluate'] = 1;
						}
						if (!empty($debt[0]['evaluate'] == 2)) {
							$c['evaluate'] = 2;
						}
						if (!empty($debt[0]['evaluate'] == 4)) {
							$c['evaluate'] = 4;
						}
						if (!empty($debt[0]['evaluate'] == 5)) {
							$c['evaluate'] = 5;
						}
						if (!empty($debt[0]['evaluate'] == 6)) {
							$c['evaluate'] = 6;
						}
					} else {
						$c['evaluate'] = 3;
					}
				}
				$c['debt_log'] = !empty($debt) ? $debt[0] : '';
				$bucket = $this->lay_nhom_no_hop_dong($c['code_contract']);
				$c['bucket'] = !empty($bucket) ? $bucket['bucket'] : '-';
				if ($c['bucket'] == "B0") {
					$transaction = $this->transaction_model->findOne(['date_pay' => ['$gte' => $start, '$lte' => $end], 'code_contract' => (string)$c['code_contract']]);
					if (!empty($transaction)) {
						$c['complete'] = true;
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function log_debt_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$debt = $this->contract_debt_recovery_model->find_where(['contract_id' => (string)$contract_id]);
		$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($contract_id)]);
		$code_contract = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : $contract['code_contract'];
		foreach ($debt as $d) {
			$d['code_contract'] = $code_contract;
		}
		$html = gen_html_call_history($debt);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $debt,
			'html' => $html,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function kpi_overview_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($this->dataPost['start']) ? strtotime(trim($this->security->xss_clean($data['start'])) . ' 00:00:00') : strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = !empty($this->dataPost['end']) ? strtotime(trim($this->security->xss_clean($data['end'])) . ' 23:59:59') : strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$user_id = !empty($data['user_id']) ? $this->security->xss_clean($data['user_id']) : '';
		$condition = array(
			'start' => $start,
			'end' => $end
		);
		if (!empty($user_id)) {
			$condition['user_id'] = $user_id;
		}
		$condition['reminder_now'] = "37";
		$contracts = $this->contract_debt_model->get_contract_user_field_debt($condition);
		$pos = 0;
		$da_xu_ly = 0;
		$da_gap = 0;
		$chua_gap = 0;
		$tong_tien_da_thu = 0;
		$chua_vieng_tham = 0;
		$da_thu_tien = 0;
		$hua_thanh_toan = 0;
		$da_thu_hoi_xe = 0;
		$tiep_tuc_tac_dong = 0;
		$mat_kha_nang_thanh_toan = 0;
		foreach ($contracts as $contract) {
			$assign = $this->contract_assign_debt_model->findOne(['contract_id' => (string)$contract['_id'], 'user_id' => $user_id, 'created_at' => ['$gte' => $start, '$lte' => $end]]);
			if (!empty($assign)) {
				$pos += $assign['pos'];
			}
			$debt = $this->contract_debt_recovery_model->find_where(['user_id' => (string)$user_id, 'contract_id' => (string)$contract['_id'], 'created_at' => ['$gte' => $start, '$lte' => $end]]);
			if (!empty($debt)) {
				foreach ($debt as $value) {
					if (!empty($value['evaluate'])) {
						if (!empty($value['amount_received'] && !empty($value['evaluate'] == 1))) {
							$tong_tien_da_thu += $value['amount_received'];
						}
					}
				}
				if (!empty($debt[0]['evaluate'])) {
					if ($debt[0]['evaluate'] == 1 || $debt[0]['evaluate'] == 4) {
						$da_xu_ly++;
					}
					if (!empty($debt[0]['evaluate'] == 1)) {
						$da_thu_tien++;
					}
					if (!empty($debt[0]['evaluate'] == 2)) {
						$hua_thanh_toan++;
					}
					if (!empty($debt[0]['evaluate'] == 4)) {
						$da_thu_hoi_xe++;
					}
					if (!empty($debt[0]['evaluate'] == 5)) {
						$tiep_tuc_tac_dong++;
					}
					if (!empty($debt[0]['evaluate'] == 6)) {
						$mat_kha_nang_thanh_toan++;
					}
				} else {
					$chua_vieng_tham++;
				}
				if (!empty($debt[0]['people'])) {
					if ($debt[0]['people'] !== 11) {
						$da_gap++;
					}
				}
			} else {
				$chua_vieng_tham++;
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => [
				'pos' => number_format(round($pos)),
				'da_gap' => $da_gap,
				'chua_gap' => count($contracts) - $da_gap,
				'da_xu_ly' => $da_xu_ly,
				'chua_xu_ly' => count($contracts) - $da_xu_ly,
				"tien_thu" => number_format(round($tong_tien_da_thu)),
				'da_thu_tien' => $da_thu_tien,
				'hua_thanh_toan' => $hua_thanh_toan,
				'da_thu_hoi_xe' => $da_thu_hoi_xe,
				'tiep_tuc_tac_dong' => $tiep_tuc_tac_dong,
				'mat_kha_nang_thanh_toan' => $mat_kha_nang_thanh_toan,
				'chua_vieng_tham' => $chua_vieng_tham
			],
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function push_noti_user_debt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$date = !empty($data['date']) ? $data['date'] : "";
		$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($contract_id)]);
		$code_contract = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : $contract['code_contract'];
		$user = $this->user_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($contract['user_debt'])]);
		$data_notification = [
			'action' => 'warning',
			'user_id' => (string)$user['_id'],
			'phone_number' => (string)$user['phone_number'],
			'email' => (string)$user['email'],
			'action_id' => (string)$contract_id,
			'status' => 1, //1: new, 2 : read, 3: block,
			'created_at' => $this->createdAt,
			"created_by" => $this->uemail,
			'message' => 'Hợp đồng ' . $code_contract . ' đã ' . $date . ' ngày chưa liên hệ. Yêu cầu liên hệ sớm!'
		];
		$this->notification_app_model->insertReturnId($data_notification);
		$device = $this->device_model->find_where(['user_id' => (string)$user['_id'], 'status' => 'active']);
		if (!empty($device)) {
			$badge = $this->get_count_notification_user((string)$user['_id']);
			$fcm = new Fcm();
			$to = [];
			foreach ($device as $de) {
				$to[] = $de->device_token;
			}
			$fcm->setTitle('Quản lý thông báo');
			$fcm->setMessage('Hợp đồng ' . $code_contract . ' đã ' . $date . ' ngày chưa liên hệ. Yêu cầu liên hệ sớm!');
			$fcm->setBadge((int)$badge);
			$message = $fcm->getMessage();
			$fcm->setType('debt');
			$fcm->setContractId($contract_id);
			$data = $fcm->getData();
			$result = $fcm->sendMultipleTHN($to, $message, $data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Gửi thông báo thành công!'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	private function lay_nhom_no_hop_dong($code_contract)
	{
		$data = [];
		$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 1]);
		if (!empty($detail)) {
			$time = 0;
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$data['time'] = $time;
			if ($time <= 0) {
				$data['bucket'] = 'B0';
			} else if ($time >= 1 && $time <= 30) {
				$data['bucket'] = 'B1';
			} else if ($time > 30 && $time <= 60) {
				$data['bucket'] = 'B2';
			} else if ($time > 60 && $time <= 90) {
				$data['bucket'] = 'B3';
			} else if ($time > 90 && $time <= 120) {
				$data['bucket'] = 'B4';
			} else if ($time > 120 && $time <= 150) {
				$data['bucket'] = 'B5';
			} else if ($time > 150 && $time <= 180) {
				$data['bucket'] = 'B6';
			} else if ($time > 180 && $time <= 210) {
				$data['bucket'] = 'B7';
			} else if ($time > 210 && $time <= 270) {
				$data['bucket'] = 'B8';
			} else if ($time > 270 && $time <= 300) {
				$data['bucket'] = 'B9';
			} else if ($time > 300 && $time <= 330) {
				$data['bucket'] = 'B10';
			} else if ($time > 330 && $time <= 360) {
				$data['bucket'] = 'B11';
			} else {
				$data['bucket'] = 'B12';
			}
		}
		return $data;
	}

	private function lay_tien_goc_con_lai_chua_tra($code_contract)
	{
		$detail = $this->contract_tempo_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		if (!empty($detail)) {
			$pos = 0;
			foreach ($detail as $value) {
				if (!empty($value['tien_goc_1ky'])) {
					$pos += (double)$value['tien_goc_1ky'];
				}
			}
		}
		return $pos;
	}

	private function lay_ngay_tre_hop_dong($code_contract)
	{
		$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $code_contract, 'status' => 1]);
		if (!empty($detail)) {
			$time = 0;
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			return $time;
		}

	}

	public function insert_time_due_contract_post()
	{
		$assign = $this->contract_assign_debt_model->find();
		foreach ($assign as $value) {
			if ($value['bucket'] == "B0") {
				$time_due = 0;
			} elseif ($value['bucket'] == "B1") {
				$time_due = 15;
			} elseif ($value['bucket'] == "B2") {
				$time_due = 45;
			} elseif ($value['bucket'] == "B3") {
				$time_due = 75;
			} elseif ($value['bucket'] == "B4") {
				$time_due = 115;
			} elseif ($value['bucket'] == "B5") {
				$time_due = 135;
			} elseif ($value['bucket'] == "B6") {
				$time_due = 165;
			} elseif ($value['bucket'] == "B7") {
				$time_due = 195;
			} elseif ($value['bucket'] == "B8") {
				$time_due = 240;
			} elseif ($value['bucket'] == "B9") {
				$time_due = 285;
			} elseif ($value['bucket'] == "B10") {
				$time_due = 315;
			} elseif ($value['bucket'] == "B11") {
				$time_due = 345;
			} elseif ($value['bucket'] == "B12") {
				$time_due = 375;
			}
			$this->contract_assign_debt_model->update(['_id' => $value['_id']], ['time_due' => $time_due]);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_log_call_debt_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$email = !empty($data['email']) ? $this->security->xss_clean($data['email']) : '';
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($email)) {
			$condition['email'] = $email;
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$log = $this->log_call_debt_model->get_log_call_debt($condition, $per_page, $uriSegment);
		$total = $this->log_call_debt_model->get_count_call_debt($condition);
		foreach ($log as $value) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($value['contract_id'])]);
			$value['code_contract'] = $contract['code_contract'] ?? '';
			$value['code_contract_disbursement'] = $contract['code_contract_disbursement'] ?? '';
			$value['customer_name'] = $contract['customer_infor']['customer_name'] ?? '';
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $log,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_user_debt_post()
	{
		$role = $this->role_model->findOne(['slug' => 'phong-thu-hoi-no']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					$data[] = [
						'id' => $k,
						'email' => $i
					];
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_contract_is_due_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
//		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = [];
		$store = !empty($data['store']) ? $this->security->xss_clean($data['store']) : '';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$customer_name = !empty($data['customer_name']) ? $this->security->xss_clean($data['customer_name']) : '';
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $this->security->xss_clean($data['code_contract_disbursement']) : '';
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($store)) {
			$condition['store'] = $store;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$contract = $this->contract_debt_model->contract_is_due($condition, $per_page, $uriSegment);
		foreach ($contract as $value) {
			$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $value['code_contract'], 'status' => 1]);
			$value['detail'] = $detail[0];
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
			$value['time'] = intval(($current_day - $datetime) / (24 * 60 * 60));
		}
		$total = $this->contract_debt_model->count_contract_is_due($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $contract,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_due_date_contract_post()
	{
		$data = $this->input->post();
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$assign = $this->contract_assign_debt_model->find_where(['created_at' => ['$gte' => $condition['start'], '$lte' => $condition['end']]]);
		foreach ($assign as $value) {
			$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($value['contract_id'])]);
			$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
			$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
			$this->contract_assign_debt_model->update(
				['_id' => $value['_id']],
				[
					'bucket_end_month' => $bucket['bucket'] ?? '-',
					'time_due_end_month' => $bucket['time'] ?? 0,
					'pos_end_month' => (int)$pos ?? 0,
					'updated_at' => date('d/m/Y H:m:s', $this->createdAt)
				]
			);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getContractDebt($code_contract_disbursement, $code_contract)
	{
		if (!empty($code_contract)) {
			$contract = $this->contract_model->findOne(['code_contract_disbursement' => trim($code_contract_disbursement), 'code_contract' => trim($code_contract), 'status' => ['$gte' => 17]]);
			if (!empty($contract)) {
				return $contract;
			}
		} else {
			$contract1 = $this->contract_model->findOne(['code_contract_disbursement' => trim($code_contract_disbursement), 'status' => ['$gte' => 17]]);
			if (!empty($contract)) {
				return $contract1;
			} else {
				$contract2 = $this->contract_model->findOne(['code_contract' => trim($code_contract_disbursement), 'status' => ['$gte' => 17]]);
				return $contract2;
			}
		}

	}

	public function assign_contract_to_call_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $this->security->xss_clean(trim($data['code_contract'])) : '';
		$email = !empty($data['email']) ? $this->security->xss_clean(trim($data['email'])) : '';
		$contract = $this->contract_debt_model->findOne(['code_contract' => trim($code_contract)]);
		$user = $this->user_model->findOne(['email' => trim($email), 'status' => 'active']);
		if (!empty($contract)) {
			$pos = $this->lay_tien_goc_con_lai_chua_tra($contract['code_contract']);
			$bucket = $this->lay_nhom_no_hop_dong($contract['code_contract']);
			$param = [
				"contract_id" => (string)$contract['_id'],
				'code_contract' => $code_contract,
				'code_contract_disbursement' => $contract['code_contract_disbursement'],
				'customer_infor' => $contract['customer_infor'],
				"call_id" => (string)$user['_id'],
				'call_email' => $email,
				"pos" => !empty($pos) ? round($pos) : 0,
				"bucket" => !empty($bucket) ? $bucket['bucket'] : '-',
				"time_due" => !empty($bucket) ? $bucket['time'] : 0,
				'month' => date('m'),
				'year' => date('Y'),
				'store_id' => $contract['store']['id'],
				'store_name' => $contract['store']['name'],
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
			];
			$this->call_debt_manager_model->insert($param);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Cập nhật thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Cập nhật thất bại!',
				'data' => $code_contract
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_list_call_assign_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : "";
		$customer_phone_number = !empty($data['customer_phone_number']) ? $data['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";
		$tab = !empty($data['tab']) ? $data['tab'] : "";
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

		$condition['user_id'] = (string)$this->id;
		$condition['month'] = date('m');
		$condition['year'] = date('Y');
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'call') {
			$contracts = $this->call_debt_manager_model->get_all_contract_call_assign($condition, $per_page, $uriSegment);
			$total = $this->call_debt_manager_model->get_all_contract_call_assign_total($condition);
		} else {
			$contracts = $this->call_debt_manager_model->get_all_contract_call_to_debt($condition, $per_page, $uriSegment);
			$total = $this->call_debt_manager_model->get_all_contract_call_to_debt_total($condition);
		}

		foreach ($contracts as $contract) {
			$contract['contract'] = $this->contract_debt_model->findOne(['code_contract' => $contract['code_contract']]);
			$detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $contract['code_contract'], 'status' => 1]);
			$contract['detail'] = $detail[0];
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
			$contract['time'] = intval(($current_day - $datetime) / (24 * 60 * 60));
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thành công!',
			'data' => $contracts,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function call_to_field_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$this->call_debt_manager_model->update(
			['contract_id' => $id],
			['status' => 37]
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_log_call_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";
		$email = !empty($data['email']) ? $data['email'] : "";
		$contract = $this->contract_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($contract_id)]);
		$logs = $this->log_call_debt_model->find_where(['contract_id' => $contract_id, 'created_by' => $email]);
		foreach ($logs as $log) {
			$log['code_contract'] = $contract['code_contract'];
		}
		$html = gen_html_debt_history($logs);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $logs,
			'html' => $html,
			'message' => 'thành công!'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function quan_ly_bao_cao_call_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition['month'] = date('m');
		$condition['year'] = date('Y');
		$list_user = $this->call_debt_manager_model->find_one_email($condition);
		$data = [];
		foreach ($list_user as $key => $user) {
			$contract_total = $this->call_debt_manager_model->find_where(['month' => date('m'), 'year' => date('Y'), 'call_email' => $user]);
			$contract_debt = $this->call_debt_manager_model->find_where(['month' => date('m'), 'year' => date('Y'), 'call_email' => $user, 'status' => 37]);
			$data[$key]['email'] = $user;
			$data[$key]['tong_hop_dong_giao'] = count($contract_total);
			$data[$key]['tong_hop_dong_chuyen_field'] = count($contract_debt);
			$data[$key]['dang_xu_ly'] = count($contract_total) - count($contract_debt);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function reminder_contract_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$contract_id = !empty($data['contract_id']) ? $data['contract_id'] : "";

		$log_old = $this->log_model->find_where(['contract_id' => $contract_id, 'action' => 'note_reminder']);
		$log_new = $this->log_call_debt_model->find_where(['contract_id' => $contract_id, 'action' => 'note_reminder']);
		$log_field = $this->contract_debt_recovery_model->find_where(['contract_id' => $contract_id]);
		$data = [];
		if (!empty($log_old)) {
			foreach ($log_old as $value) {
				array_push($data, $value);
			}
		}
		if (!empty($log_new)) {
			foreach ($log_new as $v) {
				array_push($data, $v);
			}
		}
		if (!empty($log_field)) {
			foreach ($log_field as $item) {
				array_push($data, $item);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => ($data),
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_evaluate_post()
	{
		$start = strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$contracts = $this->contract_assign_debt_model->find_where(['created_at' => ['$gte' => $start, '$lte' => $end]]);
		foreach ($contracts as $contract) {
			$debt = $this->contract_debt_recovery_model->find_where(['contract_id' => (string)$contract['contract_id'], 'created_at' => ['$gte' => $start, '$lte' => $end]]);
			if (!empty($debt)) {
				$this->contract_assign_debt_model->update(
					['_id' => $contract['_id']],
					['evaluate' => $debt[0]['evaluate']]
				);
			} else {
				$this->contract_assign_debt_model->update(
					['_id' => $contract['_id']],
					['evaluate' => 3]
				);
			}
		}
		return 'ok';
	}

	public function update_user_debt_post()
	{
		$start = strtotime(trim(date('Y-m-01')) . ' 00:00:00');
		$end = strtotime(trim(date('Y-m-t')) . ' 23:59:59');
		$contracts = $this->contract_assign_debt_model->find_where(['created_at' => ['$gte' => $start, '$lte' => $end]]);
		foreach ($contracts as $c) {
			$contract = $this->contract_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($c['contract_id'])]);
			if ($contract['user_debt'] != $c['user_id']) {
				$user = $this->user_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($contract['user_debt'])]);
				$this->contract_assign_debt_model->update(['_id' => $c['_id']],
					[
						'user_id' => (string)$user['_id'],
						'debt_field_email' => $user['email'],
						'debt_field_name' => $user['full_name'],
					]
				);
			}

		}
		return 'ok';
	}

	public function contract_tempo_debt_ho_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$this->dataPost = $this->input->post();
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$store_id = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$bucket = !empty($this->dataPost['bucket']) ? $this->dataPost['bucket'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : 17;
		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$id_card = !empty($this->dataPost['id_card']) ? $this->dataPost['id_card'] : "";
		$condition = [];
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$condition['status'] = (int)$status;

		if (!empty($store_id)) {
			$condition['store'] = $store_id;
		}

		if (!empty($bucket)) {
			$condition['bucket'] = $bucket;
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}

		if (!empty($id_card)) {
			$condition['id_card'] = $id_card;
		}
		$contract = array();
		$limit = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$offset = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$contract = $this->contract_debt_model->contract_tempo_debt_ho($condition, $limit, $offset);
		$total = $this->contract_debt_model->total_contract_tempo_debt_ho($condition);
		if (!empty($contract)) {
			foreach ($contract as $key => $c) {
				$so_ki_thanh_toan = $this->contract_tempo_model->count(['code_contract' => $c['code_contract'], 'status' => 2]);
				$c['so_ki_thanh_toan'] = $so_ki_thanh_toan;

				$log_da_duyet = $this->log_model->findOne([
					'contract_id' => (string)$c['_id'],
					'type' => 'contract',
					'action' => 'approve',
					'new.status' => '6'
				]);
				if (!empty($log_da_duyet)) {
					$c['nguoi_duyet'] = $log_da_duyet['created_by'];
				}
				$ngoai_le = [];
				for ($i = 1; $i <= 7; $i++) {
					if (!empty($c['expertise_infor']['exception' . $i . '_value'])) {
						foreach ($c['expertise_infor']['exception' . $i . '_value'][0] as $value) {
							array_push($ngoai_le, $value);
						}
					}
				}
				$c['ngoai_le'] = $ngoai_le;
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
}

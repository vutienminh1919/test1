<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Property_v2 extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('main_property_model');
		$this->load->model('property_log_model');
		$this->load->model('configuration_formality_model');
		$this->load->model('depreciation_model');
		$this->load->model('property_v2_model');
		$this->load->model('log_property_model');
		$this->load->helper('lead_helper');
		$this->load->model('property_v3_model');
		$this->load->model('main_approve_property_model');
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
					$this->uemail = $this->info['email'];
				}
			}
		}
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

	public function import_khau_hao_xe_may_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $data['loai_xe'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year']) ? $data['year'] : '';
		$phan_khuc = !empty($data['phan_khuc']) ? $data['phan_khuc'] : '';
		$giam_tru_tieu_chuan = !empty($data['giam_tru_tieu_chuan']) ? (string)intval($data['giam_tru_tieu_chuan']) : '0';
		$giam_tru_bien_tinh = !empty($data['giam_tru_bien_tinh']) ? (string)intval($data['giam_tru_bien_tinh']) : '0';
		$message = $this->validate_khau_hao_xe_may($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$depreciation = $this->depreciation_model->findOne(['code' => 'XM', 'type_property' => $loai_xe, 'slug_main_property' => slugify($hang_xe), 'year' => (int)$year, 'phan_khuc' => $phan_khuc]);
		if (!empty($depreciation)) {
			$this->depreciation_model->update(
				['_id' => $depreciation['_id']],
				[
					'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
					'khau_hao' => [
						[
							'name' => "Giảm trừ biển ngoại tỉnh",
							'slug' => "giam-tru-bien-ngoai_tinh",
							'price' => $giam_tru_bien_tinh,
						]
					]
				]
			);
		} else {
			$data = [
				'code' => 'XM',
				'type_property' => $loai_xe,
				'name_property' => $hang_xe,
				'slug_main_property' => slugify($hang_xe),
				'year' => (int)$year,
				'name' => $year . ' năm',
				'slug' => $year . '-nam',
				'phan_khuc' => $phan_khuc,
				'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
				'khau_hao' => [
					[
						'name' => "Giảm trừ biển ngoại tỉnh",
						'slug' => "giam-tru-bien-ngoai_tinh",
						'price' => $giam_tru_bien_tinh,
					]
				]
			];
			$this->depreciation_model->insert($data);
		}
		$main = $this->property_v2_model->findOne(['code' => 'XM']);
		$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'slug_name' => slugify($hang_xe)]);
		if (count($parent) > 0) {
			$list_id_parent = [];
			foreach ($parent as $value) {
				array_push($list_id_parent, (string)$value['_id']);
			}
			$property = $this->property_v2_model->find_where([
				'parent_id' => ['$in' => $list_id_parent],
				'type_property' => $loai_xe,
				'phan_khuc' => $phan_khuc,
				'year_property' => (string)(date('Y') - $year + 1)
			]);
			if (count($property) > 0) {
				foreach ($property as $p) {
					$this->property_v2_model->update(
						['_id' => $p['_id']],
						[
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'depreciations' => [
								[
									'name' => "Giảm trừ biển ngoại tỉnh",
									'slug' => "giam-tru-bien-ngoai_tinh",
									'price' => $giam_tru_bien_tinh,
								]
							]
						]
					);
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function validate_khau_hao_xe_may($data)
	{
		$message = [];
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year'])) {
			$message[] = 'Năm đang trống!';
		}
		if (empty($data['phan_khuc'])) {
			$message[] = 'Phân khúc xe đang trống!';
		}
		if (empty($data['giam_tru_tieu_chuan'])) {
			$message[] = 'Giảm trừ tiêu chuẩn đang trống!';
		}
		return $message;
	}

	public function import_khau_hao_oto_post()
	{
		$data = $this->input->post();
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year']) ? $data['year'] : '';
		$phan_khuc = !empty($data['phan_khuc']) ? $data['phan_khuc'] : '';
		$giam_tru_tieu_chuan = !empty($data['giam_tru_tieu_chuan']) ? (string)intval($data['giam_tru_tieu_chuan']) : '0';
		$giam_tru_bien_tinh = !empty($data['giam_tru_bien_tinh']) ? (string)intval($data['giam_tru_bien_tinh']) : '0';
		$giam_tru_xe_van_tai = !empty($data['giam_tru_xe_van_tai']) ? (string)intval($data['giam_tru_xe_van_tai']) : '0';
		$giam_tru_xe_cong_ty = !empty($data['giam_tru_xe_cong_ty']) ? (string)intval($data['giam_tru_xe_cong_ty']) : '0';
		$message = $this->validate_khau_hao_xe_may($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$depreciation = $this->depreciation_model->findOne(['code' => 'OTO', 'slug_main_property' => slugify($hang_xe), 'year' => (int)$year, 'phan_khuc' => $phan_khuc]);
		if (!empty($depreciation)) {
			$this->depreciation_model->update(
				['_id' => $depreciation['_id']],
				[
					'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
					'khau_hao' => [
						[
							'name' => "Giảm trừ biển ngoại tỉnh",
							'slug' => "giam-tru-bien-ngoai_tinh",
							'price' => $giam_tru_bien_tinh,
						],
						[
							'name' => "Giảm trừ xe vận tải",
							'slug' => "giam-tru-xe-van-tai",
							'price' => $giam_tru_xe_van_tai,
						],
						[
							'name' => "Giảm trừ xe công ty",
							'slug' => "giam-tru-xe-cong-ty",
							'price' => $giam_tru_xe_cong_ty,
						]
					],
				]
			);
		} else {
			$data = [
				'code' => 'OTO',
				'name_property' => $hang_xe,
				'slug_main_property' => slugify($hang_xe),
				'year' => (int)$year,
				'name' => $year . ' năm',
				'slug' => $year . '-nam',
				'phan_khuc' => $phan_khuc,
				'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
				'khau_hao' => [
					[
						'name' => "Giảm trừ biển ngoại tỉnh",
						'slug' => "giam-tru-bien-ngoai_tinh",
						'price' => $giam_tru_bien_tinh,
					],
					[
						'name' => "Giảm trừ xe vận tải",
						'slug' => "giam-tru-xe-van-tai",
						'price' => $giam_tru_xe_van_tai,
					],
					[
						'name' => "Giảm trừ xe công ty",
						'slug' => "giam-tru-xe-cong-ty",
						'price' => $giam_tru_xe_cong_ty,
					]
				],
			];
		}
		$main = $this->property_v2_model->findOne(['code' => 'OTO']);
		$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'slug_name' => slugify($hang_xe)]);
		if (count($parent) > 0) {
			$list_id_parent = [];
			foreach ($parent as $value) {
				array_push($list_id_parent, (string)$value['_id']);
			}
			$property = $this->property_v2_model->find_where([
				'parent_id' => ['$in' => $list_id_parent],
				'phan_khuc' => $phan_khuc,
				'year_property' => (string)(date('Y') - $year + 1)
			]);
			if (count($property) > 0) {
				foreach ($property as $p) {
					$this->property_v2_model->update(
						['_id' => $p['_id']],
						[
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'depreciations' => [
								[
									'name' => "Giảm trừ biển ngoại tỉnh",
									'slug' => "giam-tru-bien-ngoai_tinh",
									'price' => $giam_tru_bien_tinh,
								],
								[
									'name' => "Giảm trừ xe vận tải",
									'slug' => "giam-tru-xe-van-tai",
									'price' => $giam_tru_xe_van_tai,
								],
								[
									'name' => "Giảm trừ xe công ty",
									'slug' => "giam-tru-xe-cong-ty",
									'price' => $giam_tru_xe_cong_ty,
								]
							]
						]
					);
				}
			}
		}
		$this->depreciation_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function validate_khau_hao_oto($data)
	{
		$message = [];
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year'])) {
			$message[] = 'Năm đang trống!';
		}
		if (empty($data['phan_khuc'])) {
			$message[] = 'Phân khúc xe đang trống!';
		}
		if (empty($data['giam_tru_tieu_chuan'])) {
			$message[] = 'Giảm trừ tiêu chuẩn đang trống!';
		}
		return $message;
	}

	public function import_tai_san_xe_may_post()
	{
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $data['loai_xe'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year']) ? $data['year'] : '';
		$phan_khuc = !empty($data['phan_khuc']) ? $data['phan_khuc'] : '';
		$model = !empty($data['model']) ? $data['model'] : '';
		$price = !empty($data['price']) ? $data['price'] : '';
		$message = $this->validate_tai_san_xe_may($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$depreciation = $this->get_depreciations_xe_may($year, $loai_xe, $hang_xe, $phan_khuc);
		$property = $this->property_v2_model->findOne(['slug_name' => slugify($model), 'year_property' => (string)$year, 'type_property' => $loai_xe, 'phan_khuc' => $phan_khuc]);
		if (!empty($property)) {
			$this->property_v2_model->update(
				[
					'_id' => $property['_id']],
				[
					'name' => $model,
					'status' => 'active',
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'updated_at' => $this->createdAt,
					'updated_by' => $this->uemail,
					'str_name' => "Xe Máy " . $hang_xe . ' ' . $model . ' ' . $year,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				]
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$property_parent_id = $this->get_main_property_xe_may(slugify($hang_xe));
			if (!empty($property_parent_id)) {
				$data_insert = [
					'name' => $model,
					'slug_name' => slugify($model),
					'parent_id' => (string)$property_parent_id,
					'status' => 'active',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $year,
					'phan_khuc' => $phan_khuc,
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $loai_xe,
					'str_name' => "Xe Máy " . $hang_xe . ' ' . $model . ' ' . $year,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				];
				$this->property_v2_model->insert($data_insert);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				$main_xm = $this->property_v2_model->findOne(['code' => 'XM']);
				$parent_id = $this->property_v2_model->insertReturnId(
					[
						'name' => $hang_xe,
						"slug_name" => slugify($hang_xe),
						'parent_id' => (string)$main_xm['_id'],
						"status" => "active",
						'created_at' => $this->createdAt,
						'created_by' => $this->uemail,
						'str_name' => "Xe Máy " . $hang_xe
					]
				);
				$data_insert = [
					'name' => $model,
					'slug_name' => slugify($model),
					'parent_id' => (string)$parent_id,
					'status' => 'active',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $year,
					'phan_khuc' => $phan_khuc,
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $loai_xe,
					'str_name' => "Xe Máy " . $hang_xe . ' ' . $model . ' ' . $year,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				];
				$this->property_v2_model->insert($data_insert);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
	}

	public function validate_tai_san_xe_may($data)
	{
		$message = [];
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year'])) {
			$message[] = 'Năm đang trống!';
		}
		if (empty($data['phan_khuc'])) {
			$message[] = 'Phân khúc xe đang trống!';
		}
		if (empty($data['loai_xe'])) {
			$message[] = 'Loại xe đang trống!';
		}
		if (empty($data['model'])) {
			$message[] = 'Model xe đang trống!';
		}
		if (empty($data['price'])) {
			$message[] = 'Giá xe đang trống!';
		}
		return $message;
	}

	private function get_main_property_xe_may($main_property)
	{
		$main = $this->property_v2_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_moto as $value) {
			if (stripos($value['slug_name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	public function get_depreciations_xe_may($year, $loai_xe, $hang_xe, $phan_khuc)
	{
		$depreciation = [];
		$year_depreciation = ((int)date('Y') - (int)$year) + 1;
		$data_depreciation = $this->depreciation_model->findOne(['code' => 'XM', 'type_property' => $loai_xe, 'slug_main_property' => slugify($hang_xe), 'phan_khuc' => $phan_khuc, 'year' => (int)$year_depreciation]);
		if (!empty($data_depreciation)) {
			$depreciation['giam_tru_tieu_chuan'] = $data_depreciation['giam_tru_tieu_chuan'];
			$depreciation['khau_hao'] = $data_depreciation['khau_hao'];
		}
		return $depreciation;
	}

	private function get_main_property_oto($main_property)
	{
		$main = $this->property_v2_model->findOne(['code' => 'OTO', 'status' => 'active']);
		$main_oto = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_oto as $value) {
			if (stripos($value['slug_name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	public function get_depreciations_oto($year, $hang_xe, $phan_khuc)
	{
		$depreciation = [];
		$year_depreciation = ((int)date('Y') - (int)$year) + 1;
		$data_depreciation = $this->depreciation_model->findOne(['code' => 'OTO', 'slug_main_property' => slugify($hang_xe), 'phan_khuc' => $phan_khuc, 'year' => (int)$year_depreciation]);
		if (!empty($data_depreciation)) {
			$depreciation['giam_tru_tieu_chuan'] = $data_depreciation['giam_tru_tieu_chuan'];
			$depreciation['khau_hao'] = $data_depreciation['khau_hao'];
		}
		return $depreciation;
	}

	public function import_tai_san_oto_post()
	{
		$data = $this->input->post();
		$loai_xe = !empty($data['loai_xe']) ? $data['loai_xe'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$xuat_xu = !empty($data['xuat_xu']) ? $data['xuat_xu'] : '';
		$ban_xang_dau = !empty($data['ban_xang_dau']) ? $data['ban_xang_dau'] : '';
		$year = !empty($data['year']) ? $data['year'] : '';
		$phan_khuc = !empty($data['phan_khuc']) ? $data['phan_khuc'] : '';
		$model = !empty($data['model']) ? $data['model'] : '';
		$price = !empty($data['price']) ? $data['price'] : '';
		$message = $this->validate_tai_san_oto($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$depreciation = $this->get_depreciations_oto($year, $hang_xe, $phan_khuc);
		$property = $this->property_v2_model->findOne(['slug_name' => slugify($model), 'year_property' => (string)$year, 'type_property' => $loai_xe, 'phan_khuc' => $phan_khuc, 'slug_xuat_xu' => slugify($xuat_xu)]);
		if (strpos($model, $loai_xe) === false) {
			$str_name = "Ôtô " . $hang_xe . ' ' . $model . ' ' . $loai_xe . ' ' . $year . ' (' . $xuat_xu . ')';
		} else {
			$str_name = "Ôtô " . $hang_xe . ' ' . $model . ' ' . $year . ' (' . $xuat_xu . ')';
		}
		if (!empty($property)) {
			$this->property_v2_model->update(
				[
					'_id' => $property['_id']],
				[
					'name' => $model,
					'status' => 'active',
					'ban_xang_dau' => $ban_xang_dau,
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'updated_at' => $this->createdAt,
					'updated_by' => $this->uemail,
					'str_name' => $str_name,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				]
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$property_parent_id = $this->get_main_property_oto(slugify($hang_xe));
			if (!empty($property_parent_id)) {
				$data_insert = [
					'name' => $model,
					'slug_name' => slugify($model),
					'parent_id' => (string)$property_parent_id,
					'status' => 'active',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $year,
					'phan_khuc' => $phan_khuc,
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $loai_xe,
					'xuat_xu' => $xuat_xu,
					'slug_xuat_xu' => slugify($xuat_xu),
					'ban_xang_dau' => $ban_xang_dau,
					'str_name' => $str_name,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				];
				$this->property_v2_model->insert($data_insert);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				$main_oto = $this->property_v2_model->findOne(['code' => 'OTO']);
				$parent_id = $this->property_v2_model->insertReturnId(
					[
						'name' => $hang_xe,
						"slug_name" => slugify($hang_xe),
						'parent_id' => (string)$main_oto['_id'],
						"status" => "active",
						'created_at' => $this->createdAt,
						'created_by' => $this->uemail,
						'str_name' => "Ôtô " . $hang_xe
					]
				);
				$data_insert = [
					'name' => $model,
					'slug_name' => slugify($model),
					'parent_id' => (string)$parent_id,
					'status' => 'active',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $year,
					'phan_khuc' => $phan_khuc,
					'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $loai_xe,
					'xuat_xu' => $xuat_xu,
					'slug_xuat_xu' => slugify($xuat_xu),
					'ban_xang_dau' => $ban_xang_dau,
					'str_name' => "Ôtô " . $hang_xe . ' ' . $model . ' ' . $year,
					'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
					'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
				];
				$this->property_v2_model->insert($data_insert);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
	}


	public function validate_tai_san_oto($data)
	{
		$message = [];
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year'])) {
			$message[] = 'Năm đang trống!';
		}
		if (empty($data['phan_khuc'])) {
			$message[] = 'Phân khúc xe đang trống!';
		}
		if (empty($data['loai_xe'])) {
			$message[] = 'Loại xe đang trống!';
		}
		if (empty($data['model'])) {
			$message[] = 'Model xe đang trống!';
		}
		if (empty($data['price'])) {
			$message[] = 'Giá xe đang trống!';
		}
		return $message;
	}

	public function overview_post()
	{
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'tai-san';
		$property = !empty($data['property']) ? $data['property'] : 'XM';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'tai-san') {
			$phan_khuc_tai_san = !empty($data['phan_khuc_tai_san']) ? $data['phan_khuc_tai_san'] : '';
			$loai_xe_tai_san = !empty($data['loai_xe_tai_san']) ? $data['loai_xe_tai_san'] : '';
			$nam_san_xuat_tai_san = !empty($data['nam_san_xuat_tai_san']) ? $data['nam_san_xuat_tai_san'] : '';
			$hang_xe_tai_san = !empty($data['hang_xe_tai_san']) ? $data['hang_xe_tai_san'] : '';
			$model_tai_san = !empty($data['model_tai_san']) ? $data['model_tai_san'] : '';
			if (!empty($phan_khuc_tai_san)) {
				$condition['phan_khuc_tai_san'] = $phan_khuc_tai_san;
			}
			if (!empty($loai_xe_tai_san)) {
				$condition['loai_xe_tai_san'] = $loai_xe_tai_san;
			}
			if (!empty($nam_san_xuat_tai_san)) {
				$condition['nam_san_xuat_tai_san'] = $nam_san_xuat_tai_san;
			}
			if (!empty($hang_xe_tai_san)) {
				$condition['hang_xe_tai_san'] = $hang_xe_tai_san;
			}
			if (!empty($model_tai_san)) {
				$condition['model_tai_san'] = $model_tai_san;
			}
			$main = $this->main_property_model->findOne(['code' => $property, 'status' => 'active']);
			$main_property = $this->main_property_model->find_where(['parent_id' => (string)$main['_id']]);
			if (isset($main_property)) {
				$list_id_main = [];
				foreach ($main_property as $value) {
					$list_id_main[] = (string)$value['_id'];
				}
				$condition['list_id_main'] = $list_id_main;
			}
			$condition['status'] = 'active';
			if (count($main_property) > 0) {
				$data = $this->main_property_model->get_property_new($condition, $per_page, $uriSegment);
				foreach ($data as $value) {
					$main_data = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value['parent_id'])]);
					if (!empty($main_data)) {
						$value['main_data'] = $main_data['name'];
					}
					if ($property == 'XM') {
						if ($value['type_property'] == 1) {
							$value['dong_xe'] = 'Xe ga';
						} elseif ($value['type_property'] == 2) {
							$value['dong_xe'] = 'Xe số';
						} elseif ($value['type_property'] == 3) {
							$value['dong_xe'] = 'Xe côn';
						} elseif ($value['type_property'] == 4) {
							$value['dong_xe'] = 'Lithium';
						} else {
							$value['dong_xe'] = 'Ắc quy';
						}
					} else {
						$value['dong_xe'] = $value['type_property'];
					}
				}
				$total = $this->main_property_model->get_count_property_new($condition);
			} else {
				$data = [];
				$total = 0;
			}
		} elseif ($tab == 'khau-hao') {
			$hang_xe_khau_hao = !empty($data['hang_xe_khau_hao']) ? $data['hang_xe_khau_hao'] : '';
			$phan_khuc_khau_hao = !empty($data['phan_khuc_khau_hao']) ? $data['phan_khuc_khau_hao'] : '';
			if (!empty($hang_xe_khau_hao)) {
				$condition['hang_xe_khau_hao'] = $hang_xe_khau_hao;
			}
			if (!empty($phan_khuc_khau_hao)) {
				$condition['phan_khuc_khau_hao'] = $phan_khuc_khau_hao;
			}
			$condition['code'] = $property;
			$data = $this->depreciation_model->get_depreciation_new($condition, $per_page, $uriSegment);
			$total = $this->depreciation_model->get_count_depreciation_new($condition);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
			'total' => $total,
			'total_pending' => $total_pending_approve
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function detail_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$type = !empty($data['type_property']) ? $data['type_property'] : '';
		$property = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($type == 'XM') {
			if ($property['type_property'] == 1) {
				$property['dong_xe'] = 'Xe ga';
			} elseif ($property['type_property'] == 2) {
				$property['dong_xe'] = 'Xe số';
			} else {
				$property['dong_xe'] = 'Xe côn';
			}
		} elseif ($type == 'OTO') {
			$property['dong_xe'] = $property['type_property'];
		}
		$property['price'] = number_format($property['price']);

		$property['history'] = [];

		$history= $this->log_property_model->find_where_history(['data.main_property_id' => $id, 'type' => 'approved']);
		if($history) {
			foreach ($history as $item) {
				$created_at = $item->created_at;
					if ($item->data->type == 'update') {
						$type = 'Cập nhật';
						$price = number_format($item->data->old->price);
					} else {
						$type = 'Thêm mới';
						$price = number_format($item->data->price);
					}
				$array[] = [
					'price' => $price,
					'created_at' => date('d/m/Y, H:i:s',$created_at),
					'type_history' => $type
				];
			}
			$property['history'] = $array;

		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $property,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_main_depreciation_post()
	{
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$main = $this->depreciation_model->find_distinct_main(['code' => $code], 'slug_main_property');
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $main,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_main_property_post()
	{
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$parent = $this->property_v2_model->findOne(['code' => $code]);
		$main = $this->property_v2_model->find_where(['parent_id' => (string)$parent['_id'], 'status' => 'active']);
		foreach ($main as $value) {
			$value['id'] = (string)$value['_id'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $main,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_property_by_main_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$properties = $this->property_v2_model->find_where(['parent_id' => $id, 'status' => 'active']);
		$data = [];
		foreach ($properties as $key => $property) {
			$data[$property['slug_name']] = $property['name'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => array_unique($data),
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function delete_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$property = !empty($data['property']) ? $data['property'] : '';
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data = explode(',', $id);
		$data_log = [];
		$data_log['type'] = 'delete';
		$data_log['code'] = $property;
		$data_log['created_at'] = $this->createdAt;
		$data_log['created_by'] = $this->uemail;
		foreach ($data as $key => $item) {
			$property = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			$data_log['data'] = $property;
			$this->property_v2_model->delete(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			$this->log_property_model->insert($data_log);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data_log
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cron_update_tai_san_post()
	{
		$code = !empty($_POST['code']) ? $_POST['code'] : 'XM';
		$main = $this->property_v2_model->findOne(['code' => $code]);
		$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id']]);
		foreach ($parent as $value) {
			$properties = $this->property_v2_model->find_where(['parent_id' => (string)$value['_id']]);
			foreach ($properties as $property) {
				if ($code == 'XM') {
					$khau_hao = $this->depreciation_model->findOne([
						'code' => $code,
						'type_property' => $property['type_property'],
						'slug_main_property' => $value['slug_name'],
						'year' => (date('Y') - (int)$property['year_property']) + 1,
						'phan_khuc' => $property['phan_khuc'],
					]);
					if (!empty($khau_hao)) {
						$this->property_v2_model->update(['_id' => $property['_id']], [
							'giam_tru_tieu_chuan' => $khau_hao['giam_tru_tieu_chuan'],
							'depreciations' => $khau_hao['khau_hao'],
							'updated_at' => time()
						]);
					}
				} else {
					$khau_hao = $this->depreciation_model->findOne([
						'code' => $code,
						'slug_main_property' => $value['slug_name'],
						'year' => (date('Y') - (int)$property['year_property']) + 1,
						'phan_khuc' => $property['phan_khuc'],
					]);
					if (!empty($khau_hao)) {
						$this->property_v2_model->update(['_id' => $property['_id']], [
							'giam_tru_tieu_chuan' => $khau_hao['giam_tru_tieu_chuan'],
							'depreciations' => $khau_hao['khau_hao'],
							'updated_at' => time()
						]);
					}
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cap_nhat_ten_tai_san_oto_post()
	{
		$main = $this->property_v2_model->findOne(['code' => 'OTO']);
		$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id']]);
		foreach ($parent as $value) {
			$properties = $this->property_v2_model->find_where(['parent_id' => (string)$value['_id']]);
			foreach ($properties as $property) {
				if (strpos($property['name'], $property['type_property']) === false) {
					$str_name = "Ôtô " . $value['name'] . ' ' . $property['name'] . ' ' . $property['type_property'] . ' ' . $property['year_property'] . ' (' . $property['xuat_xu'] . ')';
				} else {
					$str_name = "Ôtô " . $value['name'] . ' ' . $property['name'] . ' ' . $property['year_property'] . ' (' . $property['xuat_xu'] . ')';
				}
				$this->property_v2_model->update(
					['_id' => $property['_id']],
					['str_name' => $str_name]
				);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function delete_khau_hao_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$property = !empty($data['property']) ? $data['property'] : '';
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data = explode(',', $id);
		$data_log = [];
		$data_log['type'] = 'delete_khau_hao';
		$data_log['code'] = $property;
		$data_log['created_at'] = $this->createdAt;
		$data_log['created_by'] = $this->uemail;
		foreach ($data as $key => $item) {
			$depreciation = $this->depreciation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			$data_log['data'][$key] = $depreciation;
			$this->depreciation_model->delete(['_id' => new \MongoDB\BSON\ObjectId($item)]);
		}
		$this->log_property_model->insert($data_log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data_log
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function cap_nhat_giam_tru_bien_ngoai_tinh_post()
	{
		$data = $this->input->post();
		$code = !empty($_POST['code']) ? $_POST['code'] : 'XM';
		$vi_tri = !empty($_POST['vi_tri']) ? (int)$_POST['vi_tri'] : 0;
		$name = !empty($_POST['name']) ? $_POST['name'] : '';
		if (empty($name)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "name dang trong"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$depreciation = $this->depreciation_model->find_where(['code' => $code]);
		foreach ($depreciation as $item) {
			$khau_hao = [];
			foreach ($item['khau_hao'] as $key => $value) {
				if ($key == $vi_tri) {
					$value['name'] = $name;
					$value['slug'] = slugify($name);
					$value['price'] = $value['price'];
					array_push($khau_hao, $value);
				} else {
					array_push($khau_hao, $value);
				}
			}

			$this->depreciation_model->update(['_id' => $item['_id']], ['khau_hao' => $khau_hao]);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_property_child_post()
	{
		$data = $this->input->post();
		$model = !empty($data['model']) ? $data['model'] : "";
		$property = $this->property_v2_model->find_where(['slug_name' => $model]);
		foreach ($property as $value) {
			$value['id'] = (string)$value['_id'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_data_property_child_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$property = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getPriceProperty_post()
	{
		$data = $this->input->post();
		$type_loan = !empty($data['type_loan']) ? $data['type_loan'] : "";
		$code_type_property = !empty($data['code_type_property']) ? $data['code_type_property'] : "";
		$loan_product = !empty($data['loan_product']) ? $data['loan_product'] : "";
		$property_id = !empty($data['property_id']) ? $data['property_id'] : "";
		$depreciation_price = !empty($data['depreciation_price']) ? $data['depreciation_price'] : "";
		$configuration_formality = $this->configuration_formality_model->find_where(array("code" => $type_loan));
		$percent = $configuration_formality[0]['percent'][$code_type_property];
		$propertyData = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($property_id)));

		$giam_tru_tieu_chuan = !empty($propertyData['giam_tru_tieu_chuan']) ? (int)$propertyData['giam_tru_tieu_chuan'] : 0;
		$price_tieu_chuan = (int)$propertyData['price'] - (int)$propertyData['price'] * $giam_tru_tieu_chuan / 100;
		$price = $price_tieu_chuan;
		if (!empty($depreciation_price)) {
			$length_depreciation = count($depreciation_price);
			for ($x = 0; $x < $length_depreciation; $x++) {
				$price -= (int)$propertyData['price'] * (int)$depreciation_price[$x] / 100;
			}
		}
		$amount_money = (int)$price * (int)$percent / 100;
//		if ($loan_product == 2) {
//			if ($amount_money >= 30000000) {
//				$amount_money = 30000000;
//			} elseif ($amount_money <= 3000000) {
//				$amount_money = 3000000;
//			}
//		} elseif ($loan_product == 3) {
//			if ($amount_money >= 15000000) {
//				$amount_money = 15000000;
//			} elseif ($amount_money <= 3000000) {
//				$amount_money = 3000000;
//			}
//		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => array(
				'gia_tri_tai_san' => number_format($price),
				'so_tien_co_the_vay' => $amount_money > 0 ? number_format($amount_money) : 0
			)
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_property_by_main_v2_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$properties = $this->property_v2_model->find_where(['parent_id' => $id, 'status' => 'active']);
		$data = [];
		foreach ($properties as $key => $property) {
			$data[$property['slug_name']] = $property['name'];
		}
		$data_new = array_unique($data);
		$result = [];
		foreach ($data_new as $key => $value) {
			$result[] = [
				'id' => $key,
				'name' => $value
			];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => ($result),
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_tai_san_post()
	{
		$code = $_POST['code'];
		$parent_id = $_POST['parent_id'];
		$id = $_POST['id'];
		$main = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($parent_id)]);
		$property = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($code == 'XM') {
			$khau_hao = $this->depreciation_model->findOne([
				'code' => $code,
				'type_property' => $property['type_property'],
				'slug_main_property' => $main['slug_name'],
				'year' => (date('Y') - (int)$property['year_property']) + 1,
				'phan_khuc' => $property['phan_khuc'],
			]);
			if (!empty($khau_hao)) {
				$this->property_v2_model->update(['_id' => $property['_id']], [
					'giam_tru_tieu_chuan' => $khau_hao['giam_tru_tieu_chuan'],
					'depreciations' => $khau_hao['khau_hao'],
					'updated_at' => time()
				]);
			}
		} else {
			$khau_hao = $this->depreciation_model->findOne([
				'code' => $code,
				'slug_main_property' => $main['slug_name'],
				'year' => (date('Y') - (int)$property['year_property']) + 1,
				'phan_khuc' => $property['phan_khuc'],
			]);
			if (!empty($khau_hao)) {
				$this->property_v2_model->update(['_id' => $property['_id']], [
					'giam_tru_tieu_chuan' => $khau_hao['giam_tru_tieu_chuan'],
					'depreciations' => $khau_hao['khau_hao'],
					'updated_at' => time()
				]);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $khau_hao
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_price_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$price = !empty($data['price']) ? $data['price'] : "";

		$property = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$main = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property['parent_id'])]);
		$main_parent = $this->property_v2_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($main['parent_id'])]);
		if ($main_parent['code'] == "XM") {
			$this->property_v3_model->insert(
				[
					'code' => 'XM',
					'status' => 1,
					'name' => $property['name'],
					'slug_name' => ($property['slug_name']),
					'status' => 1,
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $property['year_property'],
					'phan_khuc' => $property['phan_khuc'],
					'price' => trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $property['type_property'],
					'old' => $property,
					"main_property_id" => (string)$property['_id'],
					"car_company" => $main['name'],
					"type" => 'update'
				]
			);
		} else {
			$this->property_v3_model->insert(
				[
					'name' => $property['name'],
					'slug_name' => ($property['slug_name']),
					'status' => 1,
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'year_property' => $property['year_property'],
					'phan_khuc' => $property['phan_khuc'],
					'price' => trim(str_replace(array(',', '.',), '', $price)),
					'type_property' => $property['type_property'],
					'xuat_xu' => $property['xuat_xu'],
					'slug_xuat_xu' => slugify($property['name']),
					'ban_xang_dau' => $property['ban_xang_dau'],
					'code' => 'OTO',
					'old' => $property,
					"main_property_id" => (string)$property['_id'],
					"car_company" => $main['name'],
					"type" => 'update'
				]
			);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function excel_property_post()
	{
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'tai-san';
		$property = !empty($data['property']) ? $data['property'] : 'XM';
		if ($tab == 'tai-san') {
			$phan_khuc_tai_san = !empty($data['phan_khuc_tai_san']) ? $data['phan_khuc_tai_san'] : '';
			$loai_xe_tai_san = !empty($data['loai_xe_tai_san']) ? $data['loai_xe_tai_san'] : '';
			$nam_san_xuat_tai_san = !empty($data['nam_san_xuat_tai_san']) ? $data['nam_san_xuat_tai_san'] : '';
			$hang_xe_tai_san = !empty($data['hang_xe_tai_san']) ? $data['hang_xe_tai_san'] : '';
			$model_tai_san = !empty($data['model_tai_san']) ? $data['model_tai_san'] : '';
			if (!empty($phan_khuc_tai_san)) {
				$condition['phan_khuc_tai_san'] = $phan_khuc_tai_san;
			}
			if (!empty($loai_xe_tai_san)) {
				$condition['loai_xe_tai_san'] = $loai_xe_tai_san;
			}
			if (!empty($nam_san_xuat_tai_san)) {
				$condition['nam_san_xuat_tai_san'] = $nam_san_xuat_tai_san;
			}
			if (!empty($hang_xe_tai_san)) {
				$condition['hang_xe_tai_san'] = $hang_xe_tai_san;
			}
			if (!empty($model_tai_san)) {
				$condition['model_tai_san'] = $model_tai_san;
			}
			$main = $this->main_property_model->findOne(['code' => $property, 'status' => 'active']);
			$main_property = $this->main_property_model->find_where(['parent_id' => (string)$main['_id']]);
			if (isset($main_property)) {
				$list_id_main = [];
				foreach ($main_property as $value) {
					$list_id_main[] = (string)$value['_id'];
				}
				$condition['list_id_main'] = $list_id_main;
			}
//			$condition['status'] = 'active';
			if (count($main_property) > 0) {
				$data = $this->main_property_model->excel_property($condition);
				foreach ($data as $value) {
					$main_data = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value['parent_id'])]);
					if (!empty($main_data)) {
						$value['main_data'] = $main_data['name'];
					}
					if ($property == 'XM') {
						if ($value['type_property'] == 1) {
							$value['dong_xe'] = 'Xe ga';
						} elseif ($value['type_property'] == 2) {
							$value['dong_xe'] = 'Xe số';
						} elseif ($value['type_property'] == 3) {
							$value['dong_xe'] = 'Xe côn';
						} elseif ($value['type_property'] == 4) {
							$value['dong_xe'] = 'Lithium';
						} else {
							$value['dong_xe'] = 'Ắc quy';
						}
					} else {
						$value['dong_xe'] = $value['type_property'];
					}
				}
				$total = $this->main_property_model->get_count_property_new($condition);
			} else {
				$data = [];
				$total = 0;
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
			'total' => $total,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function insert_main_property_id_into_log_post()
	{
		$property_main = $this->property_v2_model->find_where(['status' => 'active', 'price' => ['$exists' => true]]);
		foreach ($property_main as $item) {
			$log = $this->log_property_model->findOne(['data.type' => 'create', 'data.str_name' => $item->str_name, 'data.main_property_id' => ['$exists' => false]]);
			$property_log = $this->log_property_model->update(['data.str_name' => $item->str_name], [
				'data.main_property_id' => (string)$item->_id
			]);
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',

		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function insert_khau_hao_xm_new_post()
	{
		$depreciation = $this->depreciation_model->find_where(['code' => 'XM']);

		foreach ($depreciation as $i) {
			$giam_tru_bien_tinh = $i['khau_hao'][0]['price'];
			$data = [
				[
					"name" => "Giảm trừ biển ngoại tỉnh",
					"slug" => "giam-tru-bien-ngoai-tinh",
					"price" => $giam_tru_bien_tinh
				],
				[
					"name" => "Giảm trừ xe dịch vụ",
					"slug" => "giam-tru-xe-dich-vu",
					"price" => "0"
				],
				[
					"name" => "Giảm trừ xe công ty",
					"slug" => "giam-tru-xe-cong-ty",
					"price" => "0"
				],
			];
			$this->depreciation_model->update(['_id' => new MongoDB\BSON\ObjectId($i->_id)], [
				'khau_hao' => $data
			]);
		}
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $depreciation
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

}

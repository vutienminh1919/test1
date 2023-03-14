<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Property_v3 extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('main_property_model');
		$this->load->model('main_approve_property_model');
		$this->load->model('property_log_model');
		$this->load->model('depreciation_property_model');
		$this->load->model('configuration_formality_model');
		$this->load->model('depreciation_model');
		$this->load->model('property_v2_model');
		$this->load->model('property_v3_model');
		$this->load->model('log_property_model');
		$this->load->helper('lead_helper');
		$this->load->model('depreciation_approve_model');
		$this->load->model('depreciation_model');
		$this->load->model('property_request_valuation_model');
		$this->load->model('group_role_model');
		$this->load->model('log_valuation_property_model');
		$this->load->model('log_approve_property_model');
		$this->load->model('user_model');
		$this->load->model('log_action_property_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->dataPost = $this->input->post();
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
				if ($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
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
		$giam_tru_dich_vu = !empty($data['giam_tru_dich_vu']) ? (string)intval($data['giam_tru_dich_vu']) : '0';
		$giam_tru_cong_ty = !empty($data['giam_tru_cong_ty']) ? (string)intval($data['giam_tru_cong_ty']) : '0';
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
		$depreciation_duplicate = $this->depreciation_approve_model->findOne([
			'code' => 'XM',
			'type_property' => $loai_xe,
			'slug_main_property' => slugify($hang_xe),
			'year' => (int)$year,
			'phan_khuc' => $phan_khuc,
			'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
			'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
			'giam_tru_dich_vu' => $giam_tru_dich_vu,
			'giam_tru_cong_ty' => $giam_tru_cong_ty,
			'status' => 1,
		]);
		$depreciation_v3 = $this->depreciation_approve_model->findOne([
			'code' => 'XM',
			'type_property' => $loai_xe,
			'slug_main_property' => slugify($hang_xe),
			'year' => (int)$year,
			'phan_khuc' => $phan_khuc,
			'status' => 1
		]);
		$depreciation = $this->depreciation_model->findOne(
			[
				'code' => 'XM',
				'type_property' => $loai_xe,
				'slug_main_property' => slugify($hang_xe),
				'year' => (int)$year,
				'phan_khuc' => $phan_khuc,
			]);
		if (empty($depreciation_duplicate)) {
			if (!empty($depreciation_v3)) {
				if ($depreciation_v3['giam_tru_tieu_chuan'] != $giam_tru_tieu_chuan) {
					$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
						'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
						'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
						'giam_tru_dich_vu' => $giam_tru_dich_vu,
						'giam_tru_cong_ty' => $giam_tru_cong_ty,
						'type' => 'update',
						'created_by' => $this->uemail,
						'created_at' => $this->createdAt,
					]);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					if (!empty($depreciation_v3['giam_tru_bien_tinh'])) {
						if ($depreciation_v3['giam_tru_bien_tinh'] != $giam_tru_bien_tinh) {
							$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
								'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
								'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
								'giam_tru_dich_vu' => $giam_tru_dich_vu,
								'giam_tru_cong_ty' => $giam_tru_cong_ty,
								'type' => 'update',
								'created_by' => $this->uemail,
								'created_at' => $this->createdAt,
							]);
							$response = array(
								'status' => REST_Controller::HTTP_OK,
								'message' => 'thành công',
							);
							$this->set_response($response, REST_Controller::HTTP_OK);
							return;
						}
					}
					if ($depreciation_v3['giam_tru_dich_vu'] != $giam_tru_dich_vu) {
						$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_dich_vu' => $giam_tru_dich_vu,
							'giam_tru_cong_ty' => $giam_tru_cong_ty,
							'type' => 'update',
							'created_by' => $this->uemail,
							'created_at' => $this->createdAt,
						]);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
					if ($depreciation_v3['giam_tru_cong_ty'] != $giam_tru_cong_ty) {
						$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_dich_vu' => $giam_tru_dich_vu,
							'giam_tru_cong_ty' => $giam_tru_cong_ty,
							'type' => 'update',
							'created_by' => $this->uemail,
							'created_at' => $this->createdAt,
						]);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			} else {
				if (!empty($depreciation)) {
					if ($depreciation['giam_tru_tieu_chuan'] != $giam_tru_tieu_chuan) {
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
							'status' => 1,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_dich_vu' => $giam_tru_dich_vu,
							'giam_tru_cong_ty' => $giam_tru_cong_ty,
							'old' => $depreciation,
							"main_depreciation_id" => (string)$depreciation['_id'],
							'type' => 'update',
							'created_by' => $this->uemail,
							'created_at' => $this->createdAt
						];
						$this->depreciation_approve_model->insert($data);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						if (!empty($depreciation['khau_hao'])) {
							foreach ($depreciation['khau_hao'] as $item) {
								if ($item['slug'] == 'giam-tru-bien-ngoai-tinh') {
									if ($item['price'] != $giam_tru_bien_tinh) {
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
											'status' => 1,
											'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
											'giam_tru_dich_vu' => $giam_tru_dich_vu,
											'giam_tru_cong_ty' => $giam_tru_cong_ty,
											'old' => $depreciation,
											"main_depreciation_id" => (string)$depreciation['_id'],
											'type' => 'update',
											'created_by' => $this->uemail,
											'created_at' => $this->createdAt
										];
										$this->depreciation_approve_model->insert($data);
										$response = array(
											'status' => REST_Controller::HTTP_OK,
											'message' => 'thành công',
										);
										$this->set_response($response, REST_Controller::HTTP_OK);
										return;
									}
								}
								if ($item['slug'] == 'giam-tru-xe-dich-vu') {
									if ($item['price'] != $giam_tru_dich_vu) {
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
											'status' => 1,
											'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
											'giam_tru_dich_vu' => $giam_tru_dich_vu,
											'giam_tru_cong_ty' => $giam_tru_cong_ty,
											'old' => $depreciation,
											"main_depreciation_id" => (string)$depreciation['_id'],
											'type' => 'update',
											'created_by' => $this->uemail,
											'created_at' => $this->createdAt
										];
										$this->depreciation_approve_model->insert($data);
										$response = array(
											'status' => REST_Controller::HTTP_OK,
											'message' => 'thành công',
										);
										$this->set_response($response, REST_Controller::HTTP_OK);
										return;
									}
								}
								if ($item['slug'] == 'giam-tru-xe-cong-ty') {
									if ($item['price'] != $giam_tru_cong_ty) {
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
											'status' => 1,
											'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
											'giam_tru_dich_vu' => $giam_tru_dich_vu,
											'giam_tru_cong_ty' => $giam_tru_cong_ty,
											'old' => $depreciation,
											"main_depreciation_id" => (string)$depreciation['_id'],
											'type' => 'update',
											'created_by' => $this->uemail,
											'created_at' => $this->createdAt
										];
										$this->depreciation_approve_model->insert($data);
										$response = array(
											'status' => REST_Controller::HTTP_OK,
											'message' => 'thành công',
										);
										$this->set_response($response, REST_Controller::HTTP_OK);
										return;
									}
								}
							}
						}
					}
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
						'status' => 1,
						'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
						'giam_tru_dich_vu' => $giam_tru_dich_vu,
						'giam_tru_cong_ty' => $giam_tru_cong_ty,
						'type' => 'create',
						'created_by' => $this->uemail,
						'created_at' => $this->createdAt
					];
					$this->depreciation_approve_model->insert($data);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công',
					);
				}
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
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
		$depreciation_duplicate = $this->depreciation_approve_model->findOne([
			'code' => 'OTO',
			'slug_main_property' => slugify($hang_xe),
			'year' => (int)$year,
			'phan_khuc' => $phan_khuc,
			'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
			'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
			'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
			'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty
		]);
		$depreciation_v3 = $this->depreciation_approve_model->findOne([
			'code' => 'OTO',
			'slug_main_property' => slugify($hang_xe),
			'year' => (int)$year,
			'phan_khuc' => $phan_khuc,
			'status' => 1
		]);

		$depreciation = $this->depreciation_model->findOne([
			'code' => 'OTO',
			'slug_main_property' => slugify($hang_xe),
			'year' => (int)$year,
			'phan_khuc' => $phan_khuc
		]);
		if (empty($depreciation_duplicate)) {
			if (!empty($depreciation_v3)) {
				if ($depreciation_v3['giam_tru_tieu_chuan'] != $giam_tru_tieu_chuan) {
					$this->depreciation_approve_model->update(['_id' => new \MongoDB\BSON\ObjectId($depreciation_v3['_id'])],
						[
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
							'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
							'type' => 'update',
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					if ($depreciation_v3['giam_tru_bien_tinh'] != $giam_tru_bien_tinh) {
						$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
							'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
							'type' => 'update',
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
					if ($depreciation_v3['giam_tru_xe_van_tai'] != $giam_tru_xe_van_tai) {
						$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
							'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
							'type' => 'update',
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
					if ($depreciation_v3['giam_tru_xe_cong_ty'] != $giam_tru_xe_cong_ty) {
						$this->depreciation_approve_model->update(['_id' => new MongoDB\BSON\ObjectId($depreciation_v3['_id'])], [
							'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
							'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
							'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
							'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
							'type' => 'update',
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}else{
			if (!empty($depreciation)) {
				if ($depreciation['giam_tru_tieu_chuan'] != $giam_tru_tieu_chuan) {
					$data = [
						'code' => 'OTO',
						'name_property' => $hang_xe,
						'slug_main_property' => slugify($hang_xe),
						'year' => (int)$year,
						'name' => $year . ' năm',
						'slug' => $year . '-nam',
						'phan_khuc' => $phan_khuc,
						'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
						'status' => 1,
						'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
						'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
						'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
						'old' => $depreciation,
						'main_depreciation_id' => $depreciation['_id'],
						'type' => 'update',
						'created_at' => $this->createdAt,
						'created_by' => $this->uemail,
					];
					$this->depreciation_approve_model->insert($data);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					if (!empty($depreciation['khau_hao'])) {
						foreach ($depreciation['khau_hao'] as $item) {
							if ($item['slug'] == 'giam-tru-bien-ngoai_tinh') {
								if ($item['price'] != $giam_tru_bien_tinh) {
									$data = [
										'code' => 'OTO',
										'name_property' => $hang_xe,
										'slug_main_property' => slugify($hang_xe),
										'year' => (int)$year,
										'name' => $year . ' năm',
										'slug' => $year . '-nam',
										'phan_khuc' => $phan_khuc,
										'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
										'status' => 1,
										'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
										'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
										'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
										'old' => $depreciation,
										'main_depreciation_id' => $depreciation['_id'],
										'type' => 'update',
										'created_at' => $this->createdAt,
										'created_by' => $this->uemail,
									];
									$this->depreciation_approve_model->insert($data);
									$response = array(
										'status' => REST_Controller::HTTP_OK,
										'message' => 'thành công!',
									);
									$this->set_response($response, REST_Controller::HTTP_OK);
									return;
								}
							}
							if ($item['slug'] == 'giam-tru-xe-van-tai') {
								if ($item['price'] != $giam_tru_xe_van_tai) {
									$data = [
										'code' => 'OTO',
										'name_property' => $hang_xe,
										'slug_main_property' => slugify($hang_xe),
										'year' => (int)$year,
										'name' => $year . ' năm',
										'slug' => $year . '-nam',
										'phan_khuc' => $phan_khuc,
										'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
										'status' => 1,
										'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
										'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
										'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
										'old' => $depreciation,
										'main_depreciation_id' => $depreciation['_id'],
										'type' => 'update',
										'created_at' => $this->createdAt,
										'created_by' => $this->uemail,
									];
									$this->depreciation_approve_model->insert($data);
									$response = array(
										'status' => REST_Controller::HTTP_OK,
										'message' => 'thành công!',
									);
									$this->set_response($response, REST_Controller::HTTP_OK);
									return;
								}
							}
							if ($item['slug'] == 'giam-tru-xe-cong-ty') {
								if ($item['price'] != $giam_tru_xe_cong_ty) {
									$data = [
										'code' => 'OTO',
										'name_property' => $hang_xe,
										'slug_main_property' => slugify($hang_xe),
										'year' => (int)$year,
										'name' => $year . ' năm',
										'slug' => $year . '-nam',
										'phan_khuc' => $phan_khuc,
										'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
										'status' => 1,
										'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
										'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
										'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
										'old' => $depreciation,
										'main_depreciation_id' => $depreciation['_id'],
										'type' => 'update',
										'created_at' => $this->createdAt,
										'created_by' => $this->uemail,
									];
									$this->depreciation_approve_model->insert($data);
									$response = array(
										'status' => REST_Controller::HTTP_OK,
										'message' => 'thành công!',
									);
									$this->set_response($response, REST_Controller::HTTP_OK);
									return;
								}
							}
						}
					}
				}
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
					'status' => 1,
					'giam_tru_bien_tinh' => $giam_tru_bien_tinh,
					'giam_tru_xe_van_tai' => $giam_tru_xe_van_tai,
					'giam_tru_xe_cong_ty' => $giam_tru_xe_cong_ty,
					'type' => 'create',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
				];
				$this->depreciation_approve_model->insert($data);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
			}
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
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
		$url = !empty($data['url']) ? $data['url'] : '';
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
		$count = 0;
		$property_v3 = $this->property_v3_model->findOne([
				'slug_name' => slugify($model),
				'year_property' => (string)$year,
				'type_property' => $loai_xe,
				'phan_khuc' => $phan_khuc,
				'status' => 1
			]
		);
		$property = $this->property_v2_model->findOne([
				'slug_name' => slugify($model),
				'year_property' => (string)$year,
				'type_property' => $loai_xe,
				'phan_khuc' => $phan_khuc,
			]
		);
		$property_v2_duplicate  =  $this->property_v2_model->findOne([
			'slug_name' => slugify($model),
			'year_property' => (string)$year,
			'type_property' => $loai_xe,
			'phan_khuc' => $phan_khuc,
			'price' => trim(str_replace(array(',', '.',), '', $price)),
		]);

		$property_duplicate = $this->property_v3_model->findOne([
			'slug_name' => slugify($model),
			'year_property' => (string)$year,
			'type_property' => $loai_xe,
			'phan_khuc' => $phan_khuc,
			'price' => trim(str_replace(array(',', '.',), '', $price)),
		]);
		if(empty($property_v2_duplicate)) {
			if (empty($property_duplicate)) {
				if (!empty($property_v3)) {
					$this->property_v3_model->update(['_id' => new MongoDB\BSON\ObjectId($property_v3['_id'])],
						[
							'type' => 'create',
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
					$count++;
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Thành công!',
					);
				} else {
					if (!empty($property)) {
						$this->property_v3_model->insert(
							[
								'code' => 'XM',
								'name' => $model,
								'slug_name' => slugify($model),
								'status' => 1,
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail,
								'year_property' => (string)$year,
								'phan_khuc' => $phan_khuc,
								'price' => trim(str_replace(array(',', '.',), '', $price)),
								'type_property' => $loai_xe,
								'old' => $property,
								"main_property_id" => (string)$property['_id'],
								"car_company" => $hang_xe,
								"type" => 'update'
							]
						);
						$count++;
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Thành công!',
						);
					} else {
						$data_insert = [
							'name' => $model,
							'slug_name' => slugify($model),
							'status' => 1,
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => (string)$year,
							'phan_khuc' => $phan_khuc,
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'type_property' => $loai_xe,
							'code' => 'XM',
							"car_company" => $hang_xe,
							"type" => 'create'
						];
						$this->property_v3_model->insert($data_insert);
						$count++;
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Thành công!',
						);
					}
				}
			}
		}else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Yêu cầu đã tồn tại",
				'key' => $data['key']
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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
		$main = $this->property_v3_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->property_v3_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_moto as $value) {
			if (stripos($value['slug_name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	private function get_main_property_xe_may_v2($main_property)
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

	public function get_depreciations_xe_may_approve($year, $loai_xe, $hang_xe, $phan_khuc)
	{
		$depreciation = [];
		$year_depreciation = ((int)date('Y') - (int)$year) + 1;
		$data_depreciation = $this->depreciation_approve_model->findOne(['code' => 'XM', 'type_property' => $loai_xe, 'slug_main_property' => slugify($hang_xe), 'phan_khuc' => $phan_khuc, 'year' => (int)$year_depreciation]);
		if (!empty($data_depreciation)) {
			$depreciation['giam_tru_tieu_chuan'] = $data_depreciation['giam_tru_tieu_chuan'];
			$depreciation['khau_hao'] = $data_depreciation['khau_hao'];
		}
		return $depreciation;
	}


	private function get_main_property_oto($main_property)
	{
		$main = $this->property_v3_model->findOne(['code' => 'OTO', 'status' => 'active']);
		$main_oto = $this->property_v3_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_oto as $value) {
			if (stripos($value['slug_name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	private function get_main_property_oto_v2($main_property)
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
		$property = $this->property_v2_model->findOne(['slug_name' => slugify($model), 'year_property' => (string)$year, 'type_property' => $loai_xe, 'phan_khuc' => $phan_khuc, 'slug_xuat_xu' => slugify($xuat_xu)]);
		$property_v2_duplicate = $this->property_v2_model->findOne([
				'slug_name' => slugify($model),
				'year_property' => (string)$year,
				'type_property' => $loai_xe,
				'phan_khuc' => $phan_khuc,
				'slug_xuat_xu' => slugify($xuat_xu),
				'price' => trim(str_replace(array(',', '.',), '', $price)),
				'ban_xang_dau' => $ban_xang_dau,]
		);
		$property_v3 = $this->property_v3_model->findOne([
			'slug_name' => slugify($model),
			'year_property' => (string)$year,
			'type_property' => $loai_xe,
			'phan_khuc' => $phan_khuc,
			'slug_xuat_xu' => slugify($xuat_xu),
			'status' => 1

		]);
		$property_duplicate = $this->property_v3_model->findOne([
			'slug_name' => slugify($model),
			'year_property' => (string)$year,
			'type_property' => $loai_xe,
			'phan_khuc' => $phan_khuc,
			'slug_xuat_xu' => slugify($xuat_xu),
			'price' => trim(str_replace(array(',', '.',), '', $price)),
			'ban_xang_dau' => $ban_xang_dau,
		]);
		if (empty($property_v2_duplicate)) {
			if (empty($property_duplicate)) {
				if (!empty($property_v3)) {
					$this->property_v3_model->update(['_id' => new MongoDB\BSON\ObjectId($property_v3['_id'])],
						[
							'type' => 'create',
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
						]);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'Thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					if (!empty($property)) {
						$this->property_v3_model->insert(
							[
								'name' => $model,
								'slug_name' => slugify($model),
								'status' => 1,
								'created_at' => $this->createdAt,
								'created_by' => $this->uemail,
								'year_property' => $year,
								'phan_khuc' => $phan_khuc,
								'price' => trim(str_replace(array(',', '.',), '', $price)),
								'type_property' => $loai_xe,
								'xuat_xu' => $xuat_xu,
								'slug_xuat_xu' => slugify($xuat_xu),
								'ban_xang_dau' => $ban_xang_dau,
								'code' => 'OTO',
								'old' => $property,
								"main_property_id" => (string)$property['_id'],
								"car_company" => $hang_xe,
								"type" => 'update'
							]
						);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$data_insert = [
							'name' => $model,
							'slug_name' => slugify($model),
							'status' => 1,
							'created_at' => $this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => $year,
							'phan_khuc' => $phan_khuc,
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'type_property' => $loai_xe,
							'xuat_xu' => $xuat_xu,
							'slug_xuat_xu' => slugify($xuat_xu),
							'ban_xang_dau' => $ban_xang_dau,
							'code' => 'OTO',
							"car_company" => $hang_xe,
							"type" => 'create'
						];
						$this->property_v3_model->insert($data_insert);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;

					}
				}
			}
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Yêu cầu đã tồn tại",
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
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
		$tab = !empty($data['tab']) ? $data['tab'] : '';
		$property = !empty($data['property']) ? $data['property'] : 'XM';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'phe-duyet-tai-san') {
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
			$condition['property'] = $property;
			$data = $this->main_approve_property_model->get_property_new($condition, $per_page, $uriSegment);
			foreach ($data as $value) {
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
			$total = $this->main_approve_property_model->get_count_property_new($condition);
		} elseif ($tab == 'phe-duyet-khau-hao') {
			$hang_xe_khau_hao = !empty($data['hang_xe_khau_hao']) ? $data['hang_xe_khau_hao'] : '';
			$phan_khuc_khau_hao = !empty($data['phan_khuc_khau_hao']) ? $data['phan_khuc_khau_hao'] : '';
			if (!empty($hang_xe_khau_hao)) {
				$condition['hang_xe_khau_hao'] = $hang_xe_khau_hao;
			}
			if (!empty($phan_khuc_khau_hao)) {
				$condition['phan_khuc_khau_hao'] = $phan_khuc_khau_hao;
			}
			$condition['code'] = $property;

			$data = $this->depreciation_approve_model->get_depreciation_new($condition, $per_page, $uriSegment);
			$total = $this->depreciation_approve_model->get_count_depreciation_new($condition);
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

	public function detail_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$type = !empty($data['type_property']) ? $data['type_property'] : '';
		$property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$main = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property['parent_id'])]);
//		var_dump($main);
//		die();
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
		$property['hang_xe'] = $main['name'];

		$property['price'] = number_format($property['price']);
		$property['new_price'] = number_format($property['new']['price']);
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
		$main = $this->depreciation_approve_model->find_distinct_main(['code' => $code], 'slug_main_property');
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
		$parent = $this->property_v3_model->findOne(['code' => $code]);
		$main = $this->property_v3_model->find_where(['parent_id' => (string)$parent['_id'], 'status' => 'active']);
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

	public function get_approve_property_by_main_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$properties = $this->property_v3_model->find_where(['parent_id' => $id]);
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

	public function cancel_approve_property_post()
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
		$data_log['type'] = 'cancel_approved';
		$data_log['code'] = $property;
		$data_log['created_at'] = $this->createdAt;
		$data_log['created_by'] = $this->uemail;
		foreach ($data as $key => $item) {
			$property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			if ($property['code'] == "XM") {
				$type = "Xe Máy";
			} else {
				$type = "Ô Tô";
			}
			$property['str_name'] = $type . ' ' . $property['car_company'] . ' ' . $property['name'] . ' ' . $property['year_property'];
			$property_valuated = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property['valuation_id'])]);
			$data_log['data'] = $property;
			$data_log['data_valuated'] = $property_valuated;
			$data_log['requested_by'] = !empty($property_valuated['created_by']) ? $property_valuated['created_by'] : $property['created_by'];
			$data_log['property'] = $property['str_name'];
			$this->property_v3_model->update(['_id' => new \MongoDB\BSON\ObjectId($item)],
				['status' => 3]
			);
			if (!empty($property_valuated)) {
				$this->property_request_valuation_model->update(['_id' => new \MongoDB\BSON\ObjectId($property['valuation_id'])],
					['status_valuation' => 6]
				);
			}
			$this->log_property_model->insert($data_log);
			$property_new = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property['valuation_id'])]);
			$data_log['property_id'] = (string)$property_valuated['_id'];
			$data_log['old'] = $property_valuated;
			$data_log['new'] = $property_new;
			$this->log_valuation($data_log);
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
		$main = $this->property_v3_model->findOne(['code' => $code]);
		$parent = $this->property_v3_model->find_where(['parent_id' => (string)$main['_id']]);
		foreach ($parent as $value) {
			$properties = $this->property_v3_model->find_where(['parent_id' => (string)$value['_id']]);
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
						$this->property_v3_model->update(['_id' => $property['_id']], [
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
						$this->property_v3_model->update(['_id' => $property['_id']], [
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
		$main = $this->property_v3_model->findOne(['code' => 'OTO']);
		$parent = $this->property_v3_model->find_where(['parent_id' => (string)$main['_id']]);
		foreach ($parent as $value) {
			$properties = $this->property_v3_model->find_where(['parent_id' => (string)$value['_id']]);
			foreach ($properties as $property) {
				if (strpos($property['name'], $property['type_property']) === false) {
					$str_name = "Ôtô " . $value['name'] . ' ' . $property['name'] . ' ' . $property['type_property'] . ' ' . $property['year_property'] . ' (' . $property['xuat_xu'] . ')';
				} else {
					$str_name = "Ôtô " . $value['name'] . ' ' . $property['name'] . ' ' . $property['year_property'] . ' (' . $property['xuat_xu'] . ')';
				}
				$this->property_v3_model->update(
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

	public function cancel_approve_khau_hao_post()
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
		$data_log['type'] = 'cancel';
		$data_log['code'] = $property;
		$data_log['created_at'] = $this->createdAt;
		$data_log['created_by'] = $this->uemail;
		foreach ($data as $key => $item) {
			$depreciation = $this->depreciation_approve_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			$data_log['data'][$key] = $depreciation;
			$data_log['code'] = 'depreciation';
			$this->depreciation_approve_model->update(['_id' => new \MongoDB\BSON\ObjectId($item)],
				['status' => 3]
			);
		}
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
		$property = $this->property_v3_model->find_where(['slug_name' => $model]);
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
		$property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
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
		$propertyData = $this->main_approve_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($property_id)));

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
		$properties = $this->property_v3_model->find_where(['parent_id' => $id, 'status' => 1]);
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
		$main = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($parent_id)]);
		$property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($code == 'XM') {
			$khau_hao = $this->depreciation_model->findOne([
				'code' => $code,
				'type_property' => $property['type_property'],
				'slug_main_property' => $main['slug_name'],
				'year' => (date('Y') - (int)$property['year_property']) + 1,
				'phan_khuc' => $property['phan_khuc'],
			]);
			if (!empty($khau_hao)) {
				$this->property_v3_model->update(['_id' => $property['_id']], [
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
				$this->property_v3_model->update(['_id' => $property['_id']], [
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

	public function approve_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$property = !empty($data['property']) ? $data['property'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';

		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($property == "XM") {
			foreach ($id as $i => $value) {
				$main_property_id = $this->approve_tai_san_xe_may($value);
				$this->property_v3_model->update(["_id" => new \MongoDB\BSON\ObjectId($value)], ['status' => 2]);
				$property_valuated_xm = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($value)]);
				$property_email = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property_valuated_xm['valuation_id'])]);
				// $this->send_email_approved_valuation_property($property_email);
				$this->property_request_valuation_model->update(["_id" => new MongoDB\BSON\ObjectId($property_valuated_xm['valuation_id'])], ['status_valuation' => 3]);
				if ($property_valuated_xm['code'] == "XM") {
					$type = "Xe Máy";
				} else {
					$type = "Ô Tô";
				}
				$property_valuated_xm['str_name'] = $type . ' ' . $property_valuated_xm['car_company'] . ' ' . $property_valuated_xm['name'] . ' ' . $property_valuated_xm['year_property'];
				$property_valuated_xm['main_property_id'] = !empty($property_valuated_xm['main_property_id']) ? $property_valuated_xm['main_property_id'] : (string)$main_property_id;
				$property_new = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property_valuated_xm['valuation_id'])]);
				$data_log = [
					'property_id' => (string)$property_email['_id'],
					'property_type' => 'XM',
					'old' => $property_email,
					'new' => $property_new,
					'code' => $property_valuated_xm['code'],
					'type' => 'approved',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'data' => $property_valuated_xm,
					'data_valuated' => $property_email,
					'requested_by' => !empty($property_email['created_by']) ? $property_email['created_by'] : $property_valuated_xm['created_by'],
					'property' => $property_valuated_xm['str_name']
				];
				$this->log_property_model->insert($data_log);
				$this->log_valuation($data_log);
			}
		} elseif ($property == "OTO") {
			foreach ($id as $i => $value) {
				$main_property_id = $this->approve_tai_san_oto($value);
				$this->property_v3_model->update(["_id" => new \MongoDB\BSON\ObjectId($value)], ['status' => 2]);
				$property_valuated_oto = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($value)]);
				$property_old = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property_valuated_oto['valuation_id'])]);
				$this->property_request_valuation_model->update(["_id" => new \MongoDB\BSON\ObjectId($property_valuated_oto['valuation_id'])], ['status_valuation' => 3]);
				if ($property_valuated_oto['code'] == "XM") {
					$type = "Xe Máy";
				} else {
					$type = "Ô Tô";
				}
				$property_valuated_oto['str_name'] = $type . ' ' . $property_valuated_oto['car_company'] . ' ' . $property_valuated_oto['name'] . ' ' . $property_valuated_oto['year_property'];
				$property_valuated_oto['main_property_id'] = !empty($property_valuated_oto['main_property_id']) ? $property_valuated_oto['main_property_id'] : (string)$main_property_id;
				$property_new = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($property_valuated_oto['valuation_id'])]);
				$data_log = [
					'property_id' => (string)$property_old['_id'],
					'property_type' => 'OTO',
					'old' => $property_old,
					'new' => $property_new,
					'code' => $property_valuated_oto['code'],
					'type' => 'approved',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail,
					'data' => $property_valuated_oto,
					'data_valuated' => $property_email,
					'requested_by' => !empty($property_email['created_by']) ? $property_email['created_by'] : $property_valuated_oto['created_by'],
					'property' => $property_valuated_oto['str_name']
				];
				$this->log_property_model->insert($data_log);
				$this->log_valuation($data_log);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//duyệt khấu hao
	public function approve_depreciation_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? (array)$data['id'] : '';
		$property = !empty($data['property']) ? $data['property'] : '';
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($property == 'XM') {
			foreach ($id as $i => $item) {
				$this->approve_khau_hao_tai_san_xm($item);
				$this->depreciation_approve_model->update(['_id' => new \MongoDB\BSON\ObjectId($item)], ['status' => 2]);
				$depreciation = $this->depreciation_approve_model->findOne(['_id' => new MongoDB\BSON\ObjectId($item), 'status' => 2]);
				$main = $this->property_v2_model->findOne(['code' => 'XM']);
				$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'slug_name' => slugify($depreciation['slug_main_property'])]);
				if (count($parent) > 0) {
					$list_id_parent = [];
					foreach ($parent as $value) {
						array_push($list_id_parent, (string)$value['_id']);
					}
					$property = $this->property_v2_model->find_where([
						'parent_id' => ['$in' => $list_id_parent],
						'type_property' => $depreciation['type_property'],
						'phan_khuc' => $depreciation['phan_khuc'],
						'year_property' => (string)(date('Y') - $depreciation['year'] + 1)
					]);
					$data_kh = [
						[
							'name' => "Giảm trừ biển ngoại tỉnh",
							'slug' => "giam-tru-bien-ngoai_tinh",
							'price' => $depreciation['giam_tru_bien_tinh'],
						],
						[
							"name" => "Giảm trừ xe dịch vụ",
							"slug" => "giam-tru-xe-dich-vu",
							"price" => $depreciation['giam_tru_dich_vu']
						],
						[
							"name" => "Giảm trừ xe công ty",
							"slug" => "giam-tru-xe-cong-ty",
							"price" => $depreciation['giam_tru_cong_ty']
						],
					];
					if (count($property) > 0) {
						foreach ($property as $p) {
							$this->property_v2_model->update(
								['_id' => $p['_id']],
								[
									'giam_tru_tieu_chuan' => $depreciation['giam_tru_tieu_chuan'],
									'depreciations' => $data_kh
								]
							);
						}
					}
				}
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
			}
		} elseif ($property == "OTO") {
			foreach ($id as $i => $item) {
				$this->approve_khau_hao_tai_san_oto($item);
				$this->depreciation_approve_model->update(['_id' => new \MongoDB\BSON\ObjectId($item)], ['status' => 2]);
				$depreciation = $this->depreciation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item), 'status' => 2]);
				$main = $this->property_v2_model->findOne(['code' => 'OTO']);
				$parent = $this->property_v2_model->find_where(['parent_id' => (string)$main['_id'], 'slug_name' => slugify($depreciation['slug_main_property'])]);
				if (count($parent) > 0) {
					$list_id_parent = [];
					foreach ($parent as $value) {
						array_push($list_id_parent, (string)$value['_id']);
					}
					$property = $this->property_v2_model->find_where([
						'parent_id' => ['$in' => $list_id_parent],
						'phan_khuc' => $$depreciation['phan_khuc'],
						'year_property' => (string)(date('Y') - $depreciation['year'] + 1)
					]);
					$data_kh = [
						[
							'name' => "Giảm trừ biển ngoại tỉnh",
							'slug' => "giam-tru-bien-ngoai_tinh",
							'price' => $depreciation['giam_tru_bien_tinh'],
						],
						[
							'name' => "Giảm trừ xe vận tải",
							'slug' => "giam-tru-xe-van-tai",
							'price' => $depreciation['giam_tru_xe_van_tai'],
						],
						[
							'name' => "Giảm trừ xe công ty",
							'slug' => "giam-tru-xe-cong-ty",
							'price' => $depreciation['giam_tru_xe_cong_ty'],
						]
					];
					if (count($property) > 0) {
						foreach ($property as $p) {
							$this->property_v2_model->update(
								['_id' => $p['_id']],
								[
									'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
									'depreciations' => $data_kh
								]
							);
						}
					}
				}
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
			}
		}
//		$data = explode(',', $id);
		$data_log = [];
		$data_log['type'] = 'approve_khau_hao';
		$data_log['code'] = $property;
		$data_log['created_at'] = $this->createdAt;
		$data_log['created_by'] = $this->uemail;
		foreach ($id as $key => $item) {
			$depreciation = $this->depreciation_approve_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($item)]);
			$data_log['data'][$key] = $depreciation;
			$this->depreciation_approve_model->update(['_id' => new \MongoDB\BSON\ObjectId($item)],
				['status' => 2]);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data_log
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function yeu_cau_dinh_gia_tai_san_post()
	{
		$data = $this->input->post();
		$type_xm_oto = !empty($data['type_xm_oto']) ? $data['type_xm_oto'] : '';
		$loai_xe_may = !empty($data['loai_xe_may']) ? $data['loai_xe_may'] : '';
		$loai_xe_oto = !empty($data['loai_xe_oto']) ? $data['loai_xe_oto'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year_property']) ? $data['year_property'] : '';
		$phan_khuc_oto = !empty($data['phan_khuc_oto']) ? $data['phan_khuc_oto'] : '';
		$phan_khuc_xm = !empty($data['phan_khuc_xm']) ? $data['phan_khuc_xm'] : '';
		$model = !empty($data['name']) ? $data['name'] : '';
		$xuat_xu = !empty($data['xuat_xu']) ? $data['xuat_xu'] : '';
		$ban_xang_dau = !empty($data['ban_xang_dau']) ? $data['ban_xang_dau'] : '';
		$description = !empty($data['description']) ? $data['description'] : '';
		$price_suggest = !empty($data['price_suggest']) ? $data['price_suggest'] : '';
		$image_tai_san = !empty($data['image_tai_san']) ? $data['image_tai_san'] : '';
		$image_dang_ky = !empty($data['image_dang_ky']) ? $data['image_dang_ky'] : '';
		$image_dang_kiem = !empty($data['image_dang_kiem']) ? $data['image_dang_kiem'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';


		if ($type_xm_oto == "XM") {
			$type = "Xe Máy";
		} else {
			$type = "Ô Tô";
		}
		$message = $this->validate_yeu_cau_dinh_gia_tai_san($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data_insert = [
			'type' => $type_xm_oto,
			'name' => $model,
			'hang_xe' => $hang_xe,
			'slug_name' => slugify($model),
			'status_valuation' => 1,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
			'year_property' => $year,
			'phan_khuc_oto' => $phan_khuc_oto,
			'phan_khuc_xm' => $phan_khuc_xm,
			'xuat_xu' => $xuat_xu,
			'ban_xang_dau' => $ban_xang_dau,
			'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
			'type_property_xm' => $loai_xe_may,
			'type_property_oto' => $loai_xe_oto,
			'str_name' => $type . ' ' . $hang_xe . ' ' . $model . ' ' . $year,
			'image_property' => $image_tai_san,
			'image_registration' => $image_dang_ky,
			'image_certificate' => $image_dang_kiem,
			'note' => "",
			'description' => $description,
			'price_suggest' => $price_suggest
		];
		$dataEmail = [
			'type' => $type_xm_oto,
			'name' => $model,
			'hang_xe' => $hang_xe,
			'slug_name' => slugify($model),
			'status' => 1,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
			'year_property' => $year,
			'phan_khuc' => $phan_khuc_oto,
			'xuat_xu' => $xuat_xu,
			'ban_xang_dau' => $ban_xang_dau,
			'type_property_xm' => $loai_xe_may,
			'type_property_oto' => $loai_xe_oto,
			'str_name' => $type . ' ' . $hang_xe . ' ' . $model . ' ' . $year,
			'image_property' => $image_tai_san,
			'image_registration' => $image_dang_ky,
			'image_certificate' => $image_dang_kiem,
			'note' => "",
			'url' => $url,
			'description' => $description,
			'price_suggest' => $price_suggest
		];
		$property_id = $this->property_request_valuation_model->insertReturnId($data_insert);
		$this->send_email_valuation($dataEmail);
		$data_log = [
			'id' => "",
			'property_id' => (String)$property_id,
			'type' => 'created',
			'code' => $data_insert['type'],
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
			'new' => $data_insert,
			'requested_by' => $data_insert['created_by'],
			'old' => [],
		];
		$this->log_valuation_property_model->insert($data_log);
		$this->log_property_model->insert($data_log);
		$this->log_valuation($data_log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function validate_yeu_cau_dinh_gia_tai_san($data)
	{
		$message = [];
		if (empty($data['type_xm_oto'])) {
			$message[] = 'Loại xe đang trống!';
		}
		if (empty($data['loai_xe_may']) && ($data['type_xm_oto'] == "XM")) {
			$message[] = 'Loại xe máy đang trống!';
		}
		if (empty($data['loai_xe_oto']) && ($data['type_xm_oto'] == "OTO")) {
			$message[] = 'Loại ô tô đang trống!';
		}
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year_property'])) {
			$message[] = 'Năm sản xuất tài sản đang trống!';
		}
		if (empty($data['phan_khuc_oto']) && ($data['type_xm_oto'] == "OTO")) {
			$message[] = 'Phân khúc xe ô tô đang trống!';
		}
		if (empty($data['name'])) {
			$message[] = 'Tên xe đang trống!';
		}
		if (empty($data['xuat_xu']) && ($data['type_xm_oto'] == "OTO")) {
			$message[] = 'Xuất xứ ô tô đang trống!';
		}
		if (empty($data['ban_xang_dau']) && ($data['type_xm_oto'] == "OTO")) {
			$message[] = 'Loại xăng/dầu đang trống!';
		}
//		if (empty($data['image_tai_san'])) {
//			$message[] = 'Ảnh tài sản đang trống!';
//		}
//		if (empty($data['image_dang_ky'])) {
//			$message[] = 'Ảnh đăng ký tài sản đang trống!';
//		}
		if (!empty($data['image_tai_san'])) {
			if (count($data['image_tai_san']) > 6) {
				$message[] = 'Ảnh tài sản tối đa chỉ 6 ảnh';
			}
		}
		if (!empty($data['image_dang_ky'])) {
			if (count($data['image_dang_ky']) > 2 || count($data['image_dang_ky']) <= 1) {
				$message[] = 'Ảnh đăng ký tài sản phải đủ 2 ảnh !';
			}
		}
//		if (empty($data['image_dang_kiem']) && $data['type_xm_oto'] == "OTO") {
//			$message[] = 'Ảnh đăng kiểm tài sản đang trống!';
//		}
		if (!empty($data['image_dang_kiem'])) {
			if ($data['type_xm_oto'] == "OTO" && count($data['image_dang_kiem']) <= 1) {
				$message[] = 'Ảnh đăng kiểm tài sản phải đủ 2 ảnh!';
			}
		}
		if (!empty($data['image_dang_kiem'])) {
			if ($data['type_xm_oto'] == "OTO" && count($data['image_dang_kiem']) > 2) {
				$message[] = 'Ảnh đăng kiểm tài sản phải đủ 2 ảnh!';
			}
		}
		return $message;
	}

	//trả về yêu cầu định giá tài sản
	public function feedback_note_dinh_gia_tai_san_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$note = !empty($data['note']) ? $data['note'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';
		$url_item = !empty($data['url_item']) ? $data['url_item'] : '';
		if (empty($note)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Note đang để trống",
			);
		}
		if (!empty($id)) {
			$property = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
			$this->property_request_valuation_model->update(['_id' => new \MongoDB\BSON\ObjectId($id)],
				[
					'note' => $note,
					'status_valuation' => 4
				]
			);
			$property_noted = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
			$dataEmail = [
				'type' => $property_noted['type'],
				'name' => $property_noted['name'],
				'hang_xe' => $property_noted['hang_xe'],
				'created_at' => $this->createdAt,
				'created_by' => $property_noted['created_by'],
				'year_property' => $property_noted['year_property'],
				'phan_khuc' => $property_noted['phan_khuc_oto'],
				'xuat_xu' => $property_noted['xuat_xu'],
				'ban_xang_dau' => $property_noted['ban_xang_dau'],
				'type_property_xm' => $property_noted['type_property_xm'],
				'type_property_oto' => $property_noted['type_property_oto'],
				'str_name' => $property_noted['str_name'],
				'note' => $property_noted['note'],
				'url' => $url,
				'url_item' => $url_item,
				'price_suggest' => $property_noted['price_suggest'],
				'description' => $property_noted['description'],
			];
			$this->send_email_feedback_property($dataEmail);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Thành công",
			);
		}
		$property_log = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$data_log = [
			'property_id' => $id,
			'old' => $property,
			'new' => $property_noted,
			'note' => $note,
			'type' => 'noted',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
			'data' => $property_log,
			'requested_by' => $property_log['created_by']
		];
		$this->log_valuation_property_model->insert($data_log);
		$this->log_property_model->insert($data_log);
		$this->log_valuation($data_log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function show_pending_valuation_property_post()
	{
		$data = $this->input->post();
		$type = !empty($data['type_xm_oto']) ? $data['type_xm_oto'] : '';
		$year = !empty($data['year']) ? $data['year'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$ten_tai_san = !empty($data['ten_tai_san']) ? $data['ten_tai_san'] : '';

		$user = !empty($data['user']) ? $data['user'] : '';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$condition = [];
		if (!empty($type)) {
			$condition['type'] = $type;
		}
		if (!empty($year)) {
			$condition['year_property'] = $year;
		}
		if (!empty($hang_xe)) {
			$condition['hang_xe'] = $hang_xe;
		}
		if (!empty($ten_tai_san)) {
			$condition['slug_name'] = slugify($ten_tai_san);
		}
		$user_lead_tdg = $this->get_role_truong_bo_phan_tham_dinh_gia();
		$user_bpdg = $this->get_role_bo_phan_dinh_gia();
		if (!in_array($user, $user_bpdg)) {
			$condition['user'] = $user;
		} else {
			$condition['user'] = "";
		}
		$property_pending_valuation = $this->property_request_valuation_model->get_pending_valuation_property($condition, $per_page, $uriSegment);
		$count_property_pending_valuation = $this->property_request_valuation_model->get_count_pending_valuation_property($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $property_pending_valuation,
			'total' => $count_property_pending_valuation
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function valuation_property_post()
	{
		$data = $this->input->post();
//		$type = !empty($data['type_xm_oto']) ? $data['type_xm_oto'] : '';
		$id = !empty($data['id']) ? $this->security->xss_clean($data['id']) : '';
		$price = !empty($data['price']) ? $data['price'] : '';
		$phan_khuc_xm = !empty($data['phan_khuc_xm']) ? $data['phan_khuc_xm'] : '';
//		$message = $this->validate_valuation_price_and_phan_khuc($data);
//		if (count($message) > 0) {
//			$response = array(
//				'status' => REST_Controller::HTTP_BAD_REQUEST,
//				'message' => $message[0],
//				'key' => $data['key']
//			);
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
//		}

		try {
			$property_valuated = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
			$this->property_request_valuation_model->update(['_id' => new \MongoDB\BSON\ObjectId($id)], [
				'price' => $price,
				'phan_khuc_xm' => $phan_khuc_xm,
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail,
				'valuation_by' => $this->uemail,
				'status_valuation' => 2,
			]);
			$property_log = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
			$data_log = [
				'property_id' => (string)$property_valuated['_id'],
				'old' => $property_valuated,
				'new' => $property_log,
				'price' => $price,
				'phan_khuc_xm' => $phan_khuc_xm,
				'type' => 'valuation',
				'code' => $property_log['type'],
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail,
				'data' => $property_log,
				'requested_by' => $property_log['created_by']
			];
			$this->log_property_model->insert($data_log);
			$this->log_valuation($data_log);
			$property_valuated = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
			if ($property_valuated['type'] == "XM") {
				$loai_xe = !empty($property_valuated['type_property_xm']) ? $property_valuated['type_property_xm'] : '';
				$phan_khuc = !empty($property_valuated['phan_khuc_xm']) ? $property_valuated['phan_khuc_xm'] : '';
				$hang_xe = !empty($property_valuated['hang_xe']) ? $property_valuated['hang_xe'] : '';
				$year = !empty($property_valuated['year_property']) ? $property_valuated['year_property'] : '';
				$model = !empty($property_valuated['name']) ? $property_valuated['name'] : '';
				$price = !empty($property_valuated['price']) ? $property_valuated['price'] : '';
				$property = $this->property_v2_model->findOne(['slug_name' => slugify($model), 'year_property' => (string)$year, 'type_property' => $loai_xe, 'phan_khuc' => $phan_khuc]);
				$property_duplicate = $this->property_v3_model->findOne([
					'slug_name' => slugify($model),
					'year_property' => (string)$year,
					'type_property' => $loai_xe,
					'phan_khuc' => $phan_khuc,
					'price' => $price,
					'parent_id' => (string)$property_parent_id_duplicate,
				]);
				if (empty($property_duplicate)) {
					if (!empty($property)) {
						$this->property_v3_model->insert(
							[
								'code' => 'XM',
								'status' => 1,
								'name' => $model,
								'slug_name' => slugify($model),
								'created_at' => $this->createdAt,
								'created_by' => $property_valuated['created_by'],
								'year_property' => (string)$year,
								'phan_khuc' => $phan_khuc,
								'price' => trim(str_replace(array(',', '.',), '', $price)),
								'type_property' => $loai_xe,
								'old' => $property,
								"car_company" => $hang_xe,
								"type" => 'update',
								'valuation_id' => (string)$property_valuated['_id']
							]
						);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$data_insert = [
							'name' => $model,
							'slug_name' => slugify($model),
							'status' => 1,
							'created_at' => $this->createdAt,
							'created_by' => $property_valuated['created_by'],
							'year_property' => (string)$year,
							'phan_khuc' => $phan_khuc,
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'type_property' => $loai_xe,
							'code' => 'XM',
							"car_company" => $hang_xe,
							"type" => 'create',
							'valuation_id' => (string)$property_valuated['_id']
						];
						$this->property_v3_model->insert($data_insert);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'Thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				} else {
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Yêu cầu đã tồn tại",
						'key' => $data['key']
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}

			} elseif ($property_valuated['type'] == "OTO") {
				$loai_xe = !empty($property_valuated['type_property_oto']) ? $property_valuated['type_property_oto'] : '';
				$hang_xe = !empty($property_valuated['hang_xe']) ? $property_valuated['hang_xe'] : '';
				$xuat_xu = !empty($property_valuated['xuat_xu']) ? $property_valuated['xuat_xu'] : '';
				$ban_xang_dau = !empty($property_valuated['ban_xang_dau']) ? $property_valuated['ban_xang_dau'] : '';
				$year = !empty($property_valuated['year_property']) ? $property_valuated['year_property'] : '';
				$phan_khuc = !empty($property_valuated['phan_khuc_oto']) ? $property_valuated['phan_khuc_oto'] : '';
				$model = !empty($property_valuated['name']) ? $property_valuated['name'] : '';
				$price = !empty($property_valuated['price']) ? $property_valuated['price'] : '';
				$depreciation = $this->get_depreciations_oto($year, $hang_xe, $phan_khuc);
				$property_parent_id_duplicate = $this->get_main_property_oto(slugify($hang_xe));
				$property = $this->property_v3_model->findOne(['slug_name' => slugify($model), 'year_property' => (string)$year, 'type_property' => $loai_xe, 'phan_khuc' => $phan_khuc, 'slug_xuat_xu' => slugify($xuat_xu)]);
				$property_duplicate = $this->property_v3_model->findOne([
					'slug_name' => slugify($model),
					'year_property' => (string)$year,
					'type_property' => $loai_xe,
					'phan_khuc' => $phan_khuc,
					'slug_xuat_xu' => slugify($xuat_xu),
					'price' => $price,
					'ban_xang_dau' => $ban_xang_dau,
					'parent_id' => (string)$property_parent_id_duplicate
				]);
				if (strpos($model, $loai_xe) === false) {
					$str_name = "Ôtô " . $hang_xe . ' ' . $model . ' ' . $loai_xe . ' ' . $year . ' (' . $xuat_xu . ')';
				} else {
					$str_name = "Ôtô " . $hang_xe . ' ' . $model . ' ' . $year . ' (' . $xuat_xu . ')';
				}
				if (empty($property_duplicate)) {
					if (!empty($property)) {
						$this->property_v3_model->insert(
							[
								'name' => $model,
								'slug_name' => slugify($model),
								'status' => 1,
								'created_at' => $this->createdAt,
								'created_by' => $property_valuated['created_by'],
								'year_property' => $year,
								'phan_khuc' => $phan_khuc,
								'price' => trim(str_replace(array(',', '.',), '', $price)),
								'type_property' => $loai_xe,
								'xuat_xu' => $xuat_xu,
								'slug_xuat_xu' => slugify($xuat_xu),
								'ban_xang_dau' => $ban_xang_dau,
								'code' => 'OTO',
								'old' => $property,
								"main_property_id" => (string)$property['_id'],
								"car_company" => $hang_xe,
								"type" => 'update',
								'valuation_id' => (string)$property_valuated['_id']
							]
						);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					} else {
						$data_insert = [
							'name' => $model,
							'slug_name' => slugify($model),
							'status' => 1,
							'created_at' => $this->createdAt,
							'created_by' => $property_valuated['created_by'],
							'year_property' => $year,
							'phan_khuc' => $phan_khuc,
							'price' => trim(str_replace(array(',', '.',), '', $price)),
							'type_property' => $loai_xe,
							'xuat_xu' => $xuat_xu,
							'slug_xuat_xu' => slugify($xuat_xu),
							'ban_xang_dau' => $ban_xang_dau,
							'code' => 'OTO',
							"car_company" => $hang_xe,
							"type" => 'create',
							'valuation_id' => (string)$property_valuated['_id']
						];
						$this->property_v3_model->insert($data_insert);
						$response = array(
							'status' => REST_Controller::HTTP_OK,
							'message' => 'thành công!',
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;

					}
				} else {
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Yêu cầu đã tồn tại",
						'key' => $data['key']
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}

			}
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Update Thành công",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;

		} catch (Exception $e) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => $e->getMessage(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function validate_valuation_price_and_phan_khuc($data)
	{
		$message = [];
		if (empty($data['price'])) {
			$message[] = 'Giá tài sản không để trống';
		}
		if ((int)$data['price'] < 0) {
			$message[] = 'Giá tài sản không phù hợp';
		}
		if (empty($data['phan_khuc_xm']) && $data['type_xm_oto']) {
			$message[] = 'Phân khúc tài sản xe máy không để trống';
		}
		return $message;
	}

	public function detail_valuation_property_post()
	{
		$user_lead_tham_dinh = $this->get_role_truong_bo_phan_tham_dinh_gia();
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$property = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($property['type'] == 'XM') {
			if ($property['type_property_xm'] == 1) {
				$property['dong_xe'] = 'Xe ga';
			} elseif ($property['type_property_xm'] == 2) {
				$property['dong_xe'] = 'Xe số';
			} elseif ($property['type_property_xm'] == 3) {
				$property['dong_xe'] = 'Xe côn';
			} elseif ($property['type_property_xm'] == 4) {
				$property['dong_xe'] = 'Lithium';
			} else {
				$property['dong_xe'] = 'Ắc quy';
			}
		} elseif ($property['type'] == 'OTO') {
			$property['dong_xe'] = $property['type_property_oto'];
		}
		$property['user_lead_tham_dinh'] = $user_lead_tham_dinh;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_role_truong_bo_phan_tham_dinh_gia()
	{
		$data = [];
		$user = $this->group_role_model->findOne(['slug' => 'truong-bo-phan-tham-dinh']);
		foreach ($user['users'] as $item) {
			foreach ($item as $i) {
				array_push($data, $i['email']);
			}
		}
		return $data;
	}

	public function get_role_truong_bo_phan_phe_duyet()
	{
		$data = [];
		$user = $this->group_role_model->findOne(['slug' => 'truong-bo-phan-phe-duyet']);
		foreach ($user['users'] as $item) {
			foreach ($item as $i) {
				array_push($data, $i['email']);
			}
		}
		return $data;
	}

	public function show_history_property_post()
	{
		$data = $this->input->post();

		$tab = !empty($data['tab']) ? $data['tab'] : 'tai-san';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$property = !empty($data['property']) ? $data['property'] : 'XM';
		$year = !empty($data['nam_san_xuat_tai_san']) ? $data['nam_san_xuat_tai_san'] : '';
		$phan_khuc = !empty($data['phan_khuc_tai_san']) ? $data['phan_khuc_tai_san'] : '';
		$name = !empty($data['model_tai_san']) ? $data['model_tai_san'] : '';
		$hang_xe = !empty($data['hang_xe_tai_san']) ? $data['hang_xe_tai_san'] : '';
		$type_property = !empty($data['loai_xe_tai_san']) ? $data['loai_xe_tai_san'] : '';

		$condition['code'] = $property;
		$condition['year'] = $year;
		$condition['phan_khuc'] = $phan_khuc;
		$condition['name'] = $name;
		$condition['hang_xe'] = $hang_xe;
		$condition['loai_xe'] = $type_property;
		$data = $this->log_property_model->get_history($condition, $per_page, $uriSegment);
		$total = $this->log_property_model->count_history($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data,
			'total' => $total
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//update định giá tài sản
	public function update_property_valuation_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';
		$url_item = !empty($data['url_item']) ? $data['url_item'] : '';
		$type_xm_oto = !empty($data['type_xm_oto']) ? $data['type_xm_oto'] : '';
		$loai_xe_may = !empty($data['loai_xe_may']) ? $data['loai_xe_may'] : '';
		$loai_xe_oto = !empty($data['loai_xe_oto']) ? $data['loai_xe_oto'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year_property']) ? $data['year_property'] : '';
		$phan_khuc = !empty($data['phan_khuc']) ? $data['phan_khuc'] : '';
		$model = !empty($data['name']) ? $data['name'] : '';
		$xuat_xu = !empty($data['xuat_xu']) ? $data['xuat_xu'] : '';
		$ban_xang_dau = !empty($data['ban_xang_dau']) ? $data['ban_xang_dau'] : '';
		$price_suggest = !empty($data['price_suggest']) ? $data['price_suggest'] : '';
		$description = !empty($data['description']) ? $data['description'] : '';
		$img_tai_san = !empty($data['img_tai_san']) ? $data['img_tai_san'] : '';
		$img_dang_ky = !empty($data['img_dang_ky']) ? $data['img_dang_ky'] : '';
		$img_dang_kiem = !empty($data['img_dang_kiem']) ? $data['img_dang_kiem'] : '';
		$property_update = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($type_xm_oto == "XM") {
			$type = "Xe Máy";
		} elseif ($type_xm_oto == "OTO") {
			$type = "Ô Tô";
		}
		$message = $this->validate_update_valuation_property($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$property_old = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$data_old = [
			'name' => $property_old['name'],
			'hang_xe' => $property_old['hang_xe'],
			'slug_name' => slugify($property_old['slug_name']),
			'status_valuation' => $property_old['status_valuation'],
			'created_at' => $property_old['created_at'],
			'created_by' => $property_old['created_by'],
			'year_property' => $property_old['year_property'],
			'phan_khuc_oto' => $property_old['phan_khuc_oto'],
			'xuat_xu' => $property_old['xuat_xu'],
			'ban_xang_dau' => $property_old['ban_xang_dau'],
			'type_property_xm' => $property_old['type_property_xm'],
			'type_property_oto' => $property_old['type_property_oto'],
			'str_name' => $property_old['str_name'],
			'image_property' => $property_old['image_property'],
			'image_registration' => $property_old['image_registration'],
			'image_certificate' => $property_old['image_certificate'],
			'price_suggest' => $property_old['price_suggest'],
			'description' => $property_old['description'],
		];
		$dataPost = [
			'name' => $model,
			'hang_xe' => $hang_xe,
			'slug_name' => slugify($model),
			'status_valuation' => 1,
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
			'year_property' => $year,
			'phan_khuc_oto' => $phan_khuc,
			'xuat_xu' => $xuat_xu,
			'ban_xang_dau' => $ban_xang_dau,
			'type_property_xm' => $loai_xe_may,
			'type_property_oto' => $loai_xe_oto,
			'str_name' => $type . ' ' . $hang_xe . ' ' . $model . ' ' . $year,
			'image_property' => $img_tai_san,
			'image_registration' => $img_dang_ky,
			'image_certificate' => $img_dang_kiem,
			'price_suggest' => $price_suggest,
			'description' => $description,
		];
		$this->property_request_valuation_model->update(['_id' => new MongoDB\BSON\ObjectId($id)], $dataPost);
		$data_log = [
			'property_id' => $id,
			'old' => $data_old,
			'new' => $dataPost,
			'type' => 'updated',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
		];
		$this->log_valuation($data_log);
		$property_valuation = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$dataEmail = [
			'type' => $property_valuation['type'],
			'name' => $property_valuation['name'],
			'hang_xe' => $property_valuation['hang_xe'],
			'slug_name' => slugify($model),
			'status_valuation' => 1,
			'created_at' => $this->createdAt,
			'created_by' => $property_valuation['created_by'],
			'year_property' => $property_valuation['year_property'],
			'phan_khuc' => $property_valuation['phan_khuc_oto'],
			'xuat_xu' => $property_valuation['xuat_xu'],
			'ban_xang_dau' => $property_valuation['ban_xang_dau'],
			'price' => (string)trim(str_replace(array(',', '.',), '', $price_suggest)),
			'type_property_xm' => $property_valuation['type_property_xm'],
			'type_property_oto' => $property_valuation['type_property_oto'],
			'str_name' => $type . ' ' . $hang_xe . ' ' . $model . ' ' . $year,
			'image_property' => $img_tai_san,
			'image_registration' => $img_dang_ky,
			'image_certificate' => $img_dang_kiem,
			'note' => $property_valuation['note'],
			'url' => $url,
			'url_item' => $url_item,
			'description' => $property_valuation['description'],
			'price_suggest' => $property_valuation['price_suggest']
		];
		$this->send_email_valuation($dataEmail);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function validate_update_valuation_property($data)
	{
		$message = [];
		if (empty($data['type_xm_oto'])) {
			$message[] = 'Loại xe đang trống!';
		}
		if (empty($data['loai_xe_may']) && $data['type_xm_oto' == "XM"]) {
			$message[] = 'Loại xe máy đang trống!';
		}
		if (empty($data['loai_o_to']) && $data['type_xm_oto' == "OTO"]) {
			$message[] = 'Loại ô tô đang trống!';
		}
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year_property'])) {
			$message[] = 'Năm sản xuất tài sản đang trống!';
		}
		if (empty($data['phan_khuc_oto']) && $data['type_xm_oto' == "OTO"]) {
			$message[] = 'Phân khúc xe ô tô đang trống!';
		}
		if (empty($data['name'])) {
			$message[] = 'Tên xe đang trống!';
		}
		if (empty($data['xuat_xu']) && $data['type_xm_oto'] == "OTO") {
			$message[] = 'Xuất xứ ô tô đang trống!';
		}
		if (empty($data['ban_xang_dau']) && $data['type_xm_oto'] == "OTO") {
			$message[] = 'Loại xăng/dầu đang trống!';
		}
//		if (empty($data['img_tai_san'])) {
//			$message[] = 'Ảnh tài sản đang trống!';
//		}
//		if (empty($data['img_dang_ky'])) {
//			$message[] = 'Ảnh đăng ký tài sản đang trống!';
//		}
		if (!empty($data['img_dang_ky'])) {
			if (count($data['img_dang_ky']) > 2 || count($data['img_dang_ky']) <= 1) {
				$message[] = 'Ảnh đăng ký tài sản phải đủ 2 ảnh !';
			}
		}
//		if (empty($data['img_dang_kiem']) && $data['type_xm_oto'] == "OTO") {
//			$message[] = 'Ảnh đăng kiểm tài sản đang trống!';
//		}
		if (!empty($data['img_dang_kiem'])) {
			if ($data['type_xm_oto'] == "OTO" && count($data['img_dang_kiem']) <= 1) {
				$message[] = 'Ảnh đăng kiểm tài sản phải đủ 2 ảnh!';
			}
		}
		if ($data['img_dang_kiem']) {
			if ($data['type_xm_oto'] == "OTO" && count($data['img_dang_kiem']) > 2) {
				$message[] = 'Ảnh đăng kiểm tài sản phải đủ 2 ảnh!';
			}
		}
		if (!empty($data['img_tai_san'])) {
			if (count($data['img_tai_san']) > 6) {
				$message[] = 'Ảnh tài sản tối đa chỉ 6 ảnh';
			}
		}
		return $message;
	}


	public function update_property_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';
		$url_item = !empty($data['url_item']) ? $data['url_item'] : '';
		$type_xm_oto = !empty($data['type_xm_oto']) ? $data['type_xm_oto'] : '';
		$loai_xe_may = !empty($data['loai_xe_may']) ? $data['loai_xe_may'] : '';
		$loai_xe_oto = !empty($data['loai_xe_oto']) ? $data['loai_xe_oto'] : '';
		$hang_xe = !empty($data['hang_xe']) ? $data['hang_xe'] : '';
		$year = !empty($data['year_property']) ? $data['year_property'] : '';
		$phan_khuc_oto = !empty($data['phan_khuc_oto']) ? $data['phan_khuc_oto'] : '';
		$phan_khuc_xm = !empty($data['phan_khuc_xm']) ? $data['phan_khuc_xm'] : '';
		$model = !empty($data['name']) ? $data['name'] : '';
		$xuat_xu = !empty($data['xuat_xu']) ? $data['xuat_xu'] : '';
		$ban_xang_dau = !empty($data['ban_xang_dau']) ? $data['ban_xang_dau'] : '';
		$message = $this->validate_update_property($data);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => $message[0],
				'key' => $data['key']
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$property_update = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		if ($type_xm_oto == "XM") {
			$type = "Xe Máy";
		} elseif ($type_xm_oto == "OTO") {
			$type = "Ô Tô";
		}
		$this->property_request_valuation_model->update(['_id' => new MongoDB\BSON\ObjectId($id)],
			[
				'name' => $model,
				'hang_xe' => $hang_xe,
				'slug_name' => slugify($model),
				'updated_at' => $this->createdAt,
				'year_property' => $year,
				'phan_khuc_oto' => $phan_khuc_oto,
				'phan_khuc_xm' => $phan_khuc_xm,
				'xuat_xu' => $xuat_xu,
				'ban_xang_dau' => $ban_xang_dau,
				'type_property_xm' => $loai_xe_may,
				'type_property_oto' => $loai_xe_oto,
				'note' => '',
				'str_name' => $type . ' ' . $hang_xe . ' ' . $model . ' ' . $year,
			]
		);
		$property_new = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$data_log = [
			'property_id' => (string)$property_update['_id'],
			'old' => $property_update,
			'new' => $property_new,
			'type' => 'updated',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
		];
		$this->log_valuation($data_log);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function validate_update_property($data)
	{
		$message = [];
		if (empty($data['type_xm_oto'])) {
			$message[] = 'Loại xe đang trống!';
		}
		if (empty($data['loai_xe_may']) && $data['type_xm_oto' == "XM"]) {
			$message[] = 'Loại xe máy đang trống!';
		}
		if (empty($data['loai_o_to']) && $data['type_xm_oto' == "OTO"]) {
			$message[] = 'Loại ô tô đang trống!';
		}
		if (empty($data['hang_xe'])) {
			$message[] = 'Hãng xe đang trống!';
		}
		if (empty($data['year_property'])) {
			$message[] = 'Năm sản xuất tài sản đang trống!';
		}
		if (empty($data['phan_khuc_oto']) && $data['type_xm_oto' == "OTO"]) {
			$message[] = 'Phân khúc xe ô tô đang trống!';
		}
		if (empty($data['phan_khuc_xm']) && $data['type_xm_oto' == "XM"]) {
			$message[] = 'Phân khúc xe máy đang trống!';
		}
		if (empty($data['name'])) {
			$message[] = 'Tên xe đang trống!';
		}
		if (empty($data['xuat_xu']) && $data['type_xm_oto'] == "OTO") {
			$message[] = 'Xuất xứ ô tô đang trống!';
		}
		if (empty($data['ban_xang_dau']) && $data['type_xm_oto'] == "OTO") {
			$message[] = 'Loại xăng/dầu đang trống!';
		}
		return $message;

	}


	public function send_email_valuation($data)
	{

		if ($data['type'] == 'XM') {
			$type_xm_oto = 'Xe máy';
		} elseif ($data['type'] == "OTO") {
			$type_xm_oto = 'Ô tô';
		}
		$name = $data['name'];
		$hang_xe = $data['hang_xe'];
		$year = $data['year_property'];
		$phan_khuc = $data['phan_khuc'];
		$xuat_xu = $data['xuat_xu'];
		$ban_xang_dau = $data['ban_xang_dau'];
		if ($data['type'] == 'XM') {
			if ($data['type_property_xm'] == 1) {
				$type_property_xm = 'Xe ga';
			} elseif ($data['type_property_xm'] == 2) {
				$type_property_xm = 'Xe số';
			} elseif ($data['type_property_xm'] == 3) {
				$type_property_xm = 'Xe côn';
			} elseif ($data['type_property_xm'] == 4) {
				$type_property_xm = 'Lithium';
			} else {
				$type_property_xm = 'Ắc quy';
			}
		} elseif ($data['type'] == 'OTO') {
			$type_property_xm = '';
		}
		$price_suggest = $data['price_suggest'];
		$description = $data['description'];
		$type_property_oto = $data['type_property_oto'];
		$dong_xe_may = $data['dong_xe'];
		$str_name = $data['str_name'];
		$code = 'email_property_valuation_notification';
		$url = $data['url'];
		$createdBy = $data['created_by'];
//		$emailDG = $this->get_role_bo_phan_dinh_gia();
		try {
			$dataEmail = [
				'url' => $url,
				'code' => $code,
				'type_xm_oto' => $type_xm_oto,
				'name' => $str_name,
				'hang_xe' => $hang_xe,
				'year' => $year,
				'type_property_xm' => $type_property_xm,
				'type_property_oto' => $type_property_oto,
				'phan_khuc' => $phan_khuc,
				'xuat_xu' => $xuat_xu,
				'ban_xang_dau' => $ban_xang_dau,
				'created_by' => $createdBy,
				'price_suggest' => $price_suggest,
				'description' => $description
			];
			$user_email = [
				'dinhgia@tienngay.vn'
			];

			// $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				if ($value == "ngochtm@tienngay.vn") {
					continue;
				}
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
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

	public function get_role_bo_phan_dinh_gia()
	{
		$data = [];
		$user = $this->group_role_model->findOne(['slug' => 'bo-phan-dinh-gia']);
		foreach ($user['users'] as $item) {
			foreach ($item as $i) {
				array_push($data, $i['email']);
			}
		}
		return $data;
	}

	public function send_email_feedback_property($data)
	{
		if ($data['type'] == 'XM') {
			$type_xm_oto = 'Xe máy';
		} elseif ($data['type'] == "OTO") {
			$type_xm_oto = 'Ô tô';
		}
		$note = $data['note'];
		$name = $data['name'];
		$hang_xe = $data['hang_xe'];
		$year = $data['year_property'];
		$phan_khuc = $data['phan_khuc'];
		$xuat_xu = $data['xuat_xu'];
		$ban_xang_dau = $data['ban_xang_dau'];
		$price_suggest = $data['price_suggest'];
		$description = $data['description'];
		if ($data['type'] == 'XM') {
			if ($data['type_property_xm'] == 1) {
				$type_property_xm = 'Xe ga';
			} elseif ($data['type_property_xm'] == 2) {
				$type_property_xm = 'Xe số';
			} elseif ($data['type_property_xm'] == 3) {
				$type_property_xm = 'Xe côn';
			} elseif ($data['type_property_xm'] == 4) {
				$type_property_xm = 'Lithium';
			} else {
				$type_property_xm = 'Ắc quy';
			}
		} elseif ($data['type'] == 'OTO') {
			$type_property_xm = '';
		}
		$type_property_oto = $data['type_property_oto'];
		$dong_xe_may = $data['dong_xe'];
		$str_name = $data['str_name'];
		$code = 'email_sendback_valuation_property';
		$url = $data['url'];
		$url_item = $data['url_item'];
		$note = $data['note'];
		$createdBy = $data['created_by'];
		$emailDG = $this->get_role_bo_phan_dinh_gia();
		try {
			$dataEmail = [
				'url' => $url,
				'code' => $code,
				'type_xm_oto' => $type_xm_oto,
				'name' => $str_name,
				'hang_xe' => $hang_xe,
				'year' => $year,
				'type_property_xm' => $type_property_xm,
				'type_property_oto' => $type_property_oto,
				'phan_khuc' => $phan_khuc,
				'xuat_xu' => $xuat_xu,
				'ban_xang_dau' => $ban_xang_dau,
				'user' => $createdBy,
				'url_detail_property' => $url_item,
				'note' => $note,
				'price_suggest' => $price_suggest,
				'description' => $description
			];
			$user_email = [
				$createdBy
			];

			// $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				if ($value == "ngochtm@tienngay.vn") {
					continue;
				}
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
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

	public function send_email_approved_valuation_property($data)
	{
		if ($data['type'] == 'XM') {
			$type_xm_oto = 'Xe máy';
		} elseif ($data['type'] == "OTO") {
			$type_xm_oto = 'Ô tô';
		}
		$name = $data['name'];
		$hang_xe = $data['hang_xe'];
		$year = $data['year_property'];
		$phan_khuc = $data['phan_khuc_oto'];
		$xuat_xu = $data['xuat_xu'];
		$ban_xang_dau = $data['ban_xang_dau'];
		if ($data['type'] == 'XM') {
			if ($data['type_property_xm'] == 1) {
				$type_property_xm = 'Xe ga';
			} elseif ($data['type_property_xm'] == 2) {
				$type_property_xm = 'Xe số';
			} elseif ($data['type_property_xm'] == 3) {
				$type_property_xm = 'Xe côn';
			} elseif ($data['type_property_xm'] == 4) {
				$type_property_xm = 'Lithium';
			} else {
				$type_property_xm = 'Ắc quy';
			}
		} elseif ($data['type'] == 'OTO') {
			$type_property_xm = '';
		}
		$type_property_oto = $data['type_property_oto'];
		$dong_xe_may = $data['dong_xe'];
		$str_name = $data['str_name'];
		$code = 'email_approved_valuation_property';
		$url = $data['url'];
		$url_item = $data['url_item'];
		$note = $data['note'];
		$createdBy = $data['created_by'];
		$emailDG = $this->get_role_bo_phan_dinh_gia();
		try {
			$dataEmail = [
				'url' => $url,
				'code' => $code,
				'type_xm_oto' => $type_xm_oto,
				'name' => $str_name,
				'hang_xe' => $hang_xe,
				'year' => $year,
				'type_property_xm' => $type_property_xm,
				'type_property_oto' => $type_property_oto,
				'phan_khuc' => $phan_khuc,
				'xuat_xu' => $xuat_xu,
				'ban_xang_dau' => $ban_xang_dau,
				'user' => $createdBy,
			];
			$user_email = [
				$createdBy
			];

			// $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				if ($value == "ngochtm@tienngay.vn") {
					continue;
				}
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
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

	//hủy định giá tài sản
	public function cancel_property_valuation_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$url_item = !empty($data['url_item']) ? $data['url_item'] : '';
		$url = !empty($data['url']) ? $data['url'] : '';
		$code = 'email_sendback_valuation_property';
		$property = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$this->property_request_valuation_model->update(['_id' => new \MongoDB\BSON\ObjectId($id)],
			[
				'status_valuation' => 5
			]
		);
		$property_cancel = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$data_log = [
			'property_id' => $id,
			'old' => $property,
			'new' => $property_cancel,
			'type' => 'cancelled',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
		];
		$this->log_valuation($data_log);
		$dataEmail = [
			'url' => $url,
			'code' => $code,
			'type' => $property_cancel['type'],
			'name' => $property_cancel['str_name'],
			'hang_xe' => $property_cancel['hang_xe'],
			'year_property' => $property_cancel['year_property'],
			'type_property_xm' => $property_cancel['type_property_xm'],
			'type_property_oto' => $property_cancel['type_property_oto'],
			'phan_khuc' => $property_cancel['phan_khuc_oto'],
			'xuat_xu' => $property_cancel['xuat_xu'],
			'ban_xang_dau' => $property_cancel['ban_xang_dau'],
			'created_by' => $property_cancel['created_by'],
			'url_detail_property' => $url_item,
			'note' => $property_cancel['note'],
			'description' => $property_cancel['description'],
			'price_suggest' => $property_cancel['price_suggest']
		];
		$this->send_email_cancel_valuation_property($dataEmail);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function send_email_cancel_valuation_property($data)
	{
		if ($data['type'] == 'XM') {
			$type_xm_oto = 'Xe máy';
		} elseif ($data['type'] == "OTO") {
			$type_xm_oto = 'Ô tô';
		}
		$note = $data['note'];
		$name = $data['name'];
		$hang_xe = $data['hang_xe'];
		$year = $data['year_property'];
		$phan_khuc = $data['phan_khuc'];
		$xuat_xu = $data['xuat_xu'];
		$ban_xang_dau = $data['ban_xang_dau'];
		if ($data['type'] == 'XM') {
			if ($data['type_property_xm'] == 1) {
				$type_property_xm = 'Xe ga';
			} elseif ($data['type_property_xm'] == 2) {
				$type_property_xm = 'Xe số';
			} elseif ($data['type_property_xm'] == 3) {
				$type_property_xm = 'Xe côn';
			} elseif ($data['type_property_xm'] == 4) {
				$type_property_xm = 'Lithium';
			} else {
				$type_property_xm = 'Ắc quy';
			}
		} elseif ($data['type'] == 'OTO') {
			$type_property_xm = '';
		}
		$type_property_oto = $data['type_property_oto'];
		$description = $data['description'];
		$price_suggest = $data['price_suggest'];
		$str_name = $data['name'];
		$code = 'email_cancel_valuation_property';
		$url = $data['url'];
		$url_item = $data['url_detail_property'];
		$note = $data['note'];
		$createdBy = $data['created_by'];
		try {
			$dataEmail = [
				'url' => $url,
				'code' => $code,
				'type_xm_oto' => $type_xm_oto,
				'name' => $str_name,
				'hang_xe' => $hang_xe,
				'year' => $year,
				'type_property_xm' => $type_property_xm,
				'type_property_oto' => $type_property_oto,
				'phan_khuc' => $phan_khuc,
				'xuat_xu' => $xuat_xu,
				'ban_xang_dau' => $ban_xang_dau,
				'user' => $createdBy,
				'url_detail_property' => $url_item,
				'note' => $note,
				'description' => $description,
				'price_suggest' => $price_suggest
			];
			$user_email = [
				$createdBy
			];

			// $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				if ($value == "ngochtm@tienngay.vn") {
					continue;
				}
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
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

	public function get_count_total_pending_property_post()
	{
		$data = $this->input->post();
		$property = !empty($data['property']) ? $data['property'] : 'XM';

		$condition['status'] = 1;
		$condition['property'] = $property;
		$count = $this->main_approve_property_model->count_approve_pending_property($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $count
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_count_total_pending_khau_hao_post()
	{
		$data = $this->input->post();
		$property = !empty($data['property']) ? $data['property'] : 'XM';
		$condition['code'] = $property;
		$condition['status'] = 1;
		$count = $this->depreciation_approve_model->get_count_pending_depreciation($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'message' => 'ok',
			'data' => $count
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function approve_tai_san_xe_may($id_main_pending_property)
	{
		$main_pending_property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_main_pending_property)]);

		if ($main_pending_property['status'] == 1) {
			$loai_xe = $main_pending_property['type_property'];
			$hang_xe = $main_pending_property['car_company'];
			$year = $main_pending_property['year_property'];
			$phan_khuc = $main_pending_property['phan_khuc'];
			$model = $main_pending_property['name'];
			$price = $main_pending_property['price'];

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
			} else {
				$property_parent_id = $this->get_main_property_xe_may_v2(slugify($hang_xe));
				if (!empty($property_parent_id)) {
					$data_insert = [
						'name' => $model,
						'slug_name' => slugify($model),
						'parent_id' => (string)$property_parent_id,
						'status' => 'active',
						'created_at' => $this->createdAt,
						'created_by' => $main_pending_property['created_by'],
						'year_property' => $year,
						'phan_khuc' => $phan_khuc,
						'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
						'type_property' => $loai_xe,
						'str_name' => "Xe Máy " . $hang_xe . ' ' . $model . ' ' . $year,
						'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
						'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
					];
					return $main_property_id = $this->property_v2_model->insertReturnId($data_insert);
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
						'created_by' => $main_pending_property['created_by'],
						'year_property' => $year,
						'phan_khuc' => $phan_khuc,
						'price' => (string)trim(str_replace(array(',', '.',), '', $price)),
						'type_property' => $loai_xe,
						'str_name' => "Xe Máy " . $hang_xe . ' ' . $model . ' ' . $year,
						'giam_tru_tieu_chuan' => !empty($depreciation['giam_tru_tieu_chuan']) ? $depreciation['giam_tru_tieu_chuan'] : '',
						'depreciations' => !empty($depreciation['khau_hao']) ? $depreciation['khau_hao'] : []
					];
					 return $main_property_id = $this->property_v2_model->insertReturnId($data_insert);
				}
			}
		}
	}

	public function approve_tai_san_oto($id_main_pending_property)
	{
		$main_pending_property = $this->property_v3_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id_main_pending_property)]);
		if ($main_pending_property['status'] == 1) {
			$loai_xe = $main_pending_property['type_property'];
			$hang_xe = $main_pending_property['car_company'];
			$xuat_xu = $main_pending_property['xuat_xu'];
			$ban_xang_dau = $main_pending_property['ban_xang_dau'];
			$year = $main_pending_property['year_property'];
			$phan_khuc = $main_pending_property['phan_khuc'];
			$model = $main_pending_property['name'];
			$price = $main_pending_property['price'];

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
			} else {
				$property_parent_id = $this->get_main_property_oto_v2(slugify($hang_xe));
				if (!empty($property_parent_id)) {
					$data_insert = [
						'name' => $model,
						'slug_name' => slugify($model),
						'parent_id' => (string)$property_parent_id,
						'status' => 'active',
						'created_at' => $this->createdAt,
						'created_by' => $main_pending_property['created_by'],
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
					return $main_property_id = $this->property_v2_model->insertReturnId($data_insert);
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
						'created_by' => $main_pending_property['created_by'],
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
					return $main_property_id = $this->property_v2_model->insertReturnId($data_insert);
				}
			}
		}
	}

	public function approve_khau_hao_tai_san_xm($id)
	{
//		$data = $this->input->post();
//		$id = $data['id'];
		$main_pending_depreciation = $this->depreciation_approve_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$loai_xe = $main_pending_depreciation['type_property'];
		$hang_xe = $main_pending_depreciation['name_property'];
		$year = $main_pending_depreciation['year'];
		$phan_khuc = $main_pending_depreciation['phan_khuc'];
		$giam_tru_tieu_chuan = $main_pending_depreciation['giam_tru_tieu_chuan'];
		$giam_tru_bien_tinh = $main_pending_depreciation['giam_tru_bien_tinh'];
		$giam_tru_dich_vu = $main_pending_depreciation['giam_tru_dich_vu'];
		$giam_tru_cong_ty = $main_pending_depreciation['giam_tru_cong_ty'];

		$depreciation = $this->depreciation_model->findOne(['code' => 'XM', 'type_property' => $loai_xe, 'slug_main_property' => slugify($hang_xe), 'year' => (int)$year, 'phan_khuc' => $phan_khuc]);
		if (!empty($depreciation)) {
			$this->depreciation_model->update(
				['_id' => $depreciation['_id']],
				[
					'giam_tru_tieu_chuan' => $giam_tru_tieu_chuan,
					'khau_hao' => [
						[
							'name' => "Giảm trừ biển ngoại tỉnh",
							'slug' => "giam-tru-bien-ngoai-tinh",
							'price' => $giam_tru_bien_tinh,
						],
						[
							'name' => "Giảm trừ xe dịch vụ",
							'slug' => "giam-tru-xe-dich-vu",
							'price' => $giam_tru_dich_vu,
						],
						[
							'name' => "Giảm trừ xe công ty",
							'slug' => "giam-tru-xe-cong-ty",
							'price' => $giam_tru_cong_ty,
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
						'slug' => "giam-tru-bien-ngoai-tinh",
						'price' => $giam_tru_bien_tinh,
					],
					[
						'name' => "Giảm trừ xe dịch vụ",
						'slug' => "giam-tru-xe-dich-vu",
						'price' => $giam_tru_dich_vu,
					],
					[
						'name' => "Giảm trừ xe công ty",
						'slug' => "giam-tru-xe-cong-ty",
						'price' => $giam_tru_cong_ty,
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
								],
								[
									'name' => "Giảm trừ xe dịch vụ",
									'slug' => "giam-tru-xe-dich-vu",
									'price' => $giam_tru_bien_tinh,
								],
								[
									'name' => "Giảm trừ xe công ty",
									'slug' => "giam-tru-xe-cong-ty",
									'price' => $giam_tru_bien_tinh,
								]
							]
						]
					);
				}
			}
		}
	}

	public function approve_khau_hao_tai_san_oto($id)
	{
		$main_pending_depreciation = $this->depreciation_approve_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$hang_xe = $main_pending_depreciation['name_property'];
		$year = $main_pending_depreciation['year'];
		$phan_khuc = $main_pending_depreciation['phan_khuc'];
		$giam_tru_tieu_chuan = $main_pending_depreciation['giam_tru_tieu_chuan'];
		$giam_tru_bien_tinh = $main_pending_depreciation['giam_tru_bien_tinh'];
		$giam_tru_xe_van_tai = $main_pending_depreciation['giam_tru_xe_van_tai'];
		$giam_tru_xe_cong_ty = $main_pending_depreciation['giam_tru_xe_cong_ty'];
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
			$this->depreciation_model->insert($data);
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
	}

	public function send_email_import_property_post()
	{
		$data = $this->input->post();
		$code = 'email_import_property';
		$url = $data['url'];
		$tab = $data['tab'];
		$count = $data['count'];
		$property = $data['property'];

		$userTBPPD = $this->get_role_truong_bo_phan_phe_duyet();

		try {
			$dataEmail = [
				'code' => $code,
				'url' => $url,
				'count_property' => $count,
				'tab' => $tab,
				'property' => $property
			];
			$user_email = $userTBPPD;

			// $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				if ($value == "ngochtm@tienngay.vn") {
					continue;
				}
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
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

	//lưu log action vào db
	public function log_valuation($data) {
		$property_id = !empty($data['property_id']) ? $data['property_id'] : "";
		$property_type = !empty($data['property_type']) ? $data['property_type'] : "";
		$type = !empty($data['type']) ? $data['type'] : "";
		$old = !empty($data['old']) ? $data['old'] : "";
		$new = !empty($data['new']) ? $data['new'] : "";
		$note = !empty($data['note']) ? $data['note'] : "";
		$price = !empty($data['price']) ? $data['price'] : "";
		$comment = !empty($data['comment']) ? $data['comment'] : "";
		$phan_khuc_xm = !empty($data['phan_khuc_xm']) ? $data['phan_khuc_xm'] : "";
		$status_valuation = !empty($data['status_valuation']) ? $data['status_valuation'] : "";
		$created_by = !empty($data['created_by']) ? $data['created_by'] : "";
		$created_at = !empty($data['created_at']) ? $data['created_at'] : "";


		$data_insert = [
			'property_id' => $property_id,
			'property_type' => $property_type,
			'type' => $type,
			'old' => $old,
			'new' => $new,
			'price' => $price,
			'phan_khuc_xm' => $phan_khuc_xm,
			'status_valuation' => $status_valuation,
			'note'	=> $note,
			'comment' => $comment,
			'created_by' => $created_by,
			'created_at' => $created_at,
		];
		$insert_id = $this->log_action_property_model->insert($data_insert);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $insert_id,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function log_action_post() {
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";

		$log_history = $this->log_action_property_model->getLogHistory(['property_id' => $id]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $log_history,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//comment định giá tài sản
	public function comment_valuation_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$comment = !empty($data['comment']) ? $data['comment'] : '';
		if (empty($comment)) {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => "Comment không để trống",
			);
		}
		$property = $this->property_request_valuation_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
		$data_log = [
			'property_id' => (string)$property['_id'],
			'comment' => $comment,
			'type' => 'comment',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail,
		];
		$this->log_valuation($data_log);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}


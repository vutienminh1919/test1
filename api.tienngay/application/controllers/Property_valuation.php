<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Property_valuation extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('main_property_model');
		$this->load->model('property_log_model');
		$this->load->model('depreciation_property_model');
		$this->load->model('configuration_formality_model');
		$this->load->model('depreciation_model');
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
					$this->uemail = $this->info['email'];
				}
			}
		}
	}

	public function get_list_motorcycle_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$vehicles = !empty($data['vehicles']) ? $data['vehicles'] : "";
		$name_property = !empty($data['name_property']) ? $data['name_property'] : "";
		$main = $this->main_property_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		$list_id_main_moto = [];
		foreach ($main_moto as $value) {
			$list_id_main_moto[] = (string)$value['_id'];
		}
		if (!empty($vehicles)) {
			$condition['list_id_main_moto'] = (array)$vehicles;
		} else {
			$condition['list_id_main_moto'] = $list_id_main_moto;
		}

		if (!empty($name_property)) {
			$condition['name_property'] = $name_property;
		}
		$condition['status'] = 'active';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$data = $this->main_property_model->get_list_moto($condition, $per_page, $uriSegment);
		$total = $this->main_property_model->get_count_list_moto($condition);
		foreach ($data as $value) {
			$main_data = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value['parent_id'])]);
			if (!empty($main_data)) {
				$value['main_data'] = $main_data['name'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_oto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$vehicles = !empty($data['vehicles']) ? $data['vehicles'] : "";
		$name_property = !empty($data['name_property']) ? $data['name_property'] : "";
		$main = $this->main_property_model->findOne(['code' => 'OTO', 'status' => 'active']);
		$main_oto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		$list_id_main_oto = [];
		foreach ($main_oto as $value) {
			$list_id_main_oto[] = (string)$value['_id'];
		}
		if (!empty($vehicles)) {
			$condition['list_id_main_oto'] = (array)$vehicles;
		} else {
			$condition['list_id_main_oto'] = $list_id_main_oto;
		}

		if (!empty($name_property)) {
			$condition['name_property'] = $name_property;
		}
		$condition['status'] = 'active';
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$data = $this->main_property_model->get_list_oto($condition, $per_page, $uriSegment);
		foreach ($data as $value) {
			$main_data = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value['parent_id'])]);
			if (!empty($main_data)) {
				$value['main_data'] = $main_data['name'];
			}
		}
		$total = $this->main_property_model->get_count_list_oto($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_main_oto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$main = $this->main_property_model->findOne(['code' => 'OTO', 'status' => 'active']);
		$main_oto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $main_oto,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_main_motorcycle_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$main = $this->main_property_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $main_moto,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function import_list_property_moto_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$main_property = !empty($data['main_property']) ? $this->security->xss_clean(trim($data['main_property'])) : '';
		$name_property = !empty($data['name_property']) ? $this->security->xss_clean(trim($data['name_property'])) : '';
		$full_name = !empty($data['full_name']) ? $this->security->xss_clean(trim($data['full_name'])) : '';
		$price = !empty($data['price']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['price']))) : '';
		$year = !empty($data['year']) ? $this->security->xss_clean(trim($data['year'])) : '';
		$type_property = !empty($data['type_property']) ? $this->security->xss_clean(trim($data['type_property'])) : '';
		if (!empty($full_name)) {
			$property = $this->main_property_model->findOne(['str_name' => "Xe Máy " . $full_name, 'year_property' => $year, 'name' => $name_property]);
			if (!empty($property)) {
				$depreciation = $this->get_depreciations($year, $main_property, $type_property);
				$this->main_property_model->update(
					[
						'_id' => $property['_id']],
					[
						'str_name' => "Xe Máy " . $full_name,
						'name' => $name_property,
						'year_property' => $year,
						'price' => (string)$price,
						'type_property' => $type_property,
						'updated_at' => (string)$this->createdAt,
						'updated_by' => $this->uemail,
						'depreciations' => $depreciation
					]
				);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
//			$property_parent = $this->main_property_model->findOne(['name' => $main_property]);
				$property_parent_id = $this->get_main_property_moto($main_property);
				if (!empty($property_parent_id)) {
					$depreciation = $this->get_depreciations($year, $main_property, $type_property);
					$this->main_property_model->insert(
						[
							'name' => $name_property,
							'code' => '',
							'parent_id' => $property_parent_id,
							'status' => 'active',
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => $year,
							'price' => (string)$price,
							'type_property' => $type_property,
							'str_name' => "Xe Máy " . $full_name,
							'depreciations' => $depreciation
						]
					);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					$main_xm = $this->main_property_model->findOne(['code' => 'XM']);
					$property_parent_id = $this->main_property_model->insertReturnId(
						[
							'name' => $main_property,
							"code" => '',
							'parent_id' => (string)$main_xm['_id'],
							"status" => "active",
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'price' => '',
							'str_name' => "Xe Máy " . $main_property
						]
					);
					$depreciation = $this->get_depreciations($year, $main_property, $type_property);
					$this->main_property_model->insert(
						[
							'name' => $name_property,
							'code' => '',
							'parent_id' => (string)$property_parent_id,
							'status' => 'active',
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => $year,
							'price' => (string)$price,
							'type_property' => $type_property,
							'str_name' => "Xe Máy " . $full_name,
							'depreciations' => $depreciation
						]
					);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function check_tai_san_lay_khau_hao($main)
	{
		$honda = "HonDa";
		$yamaha = "Yamaha";
		$piaggio = "Piaggio";
		$suzuki = "Suzuki";
		$sym = "SYM";
		$ducati = "Ducati";
		$BENELLI = "BENELLI";
		$kawasaki = "Kawasaki";
		$peugeot = "Peugeot";
		if (stripos($main, $honda) !== false) {
			$result = $honda;
		} elseif (stripos($main, $yamaha) !== false) {
			$result = $yamaha;
		} elseif (stripos($main, $piaggio) !== false) {
			$result = $piaggio;
		} elseif (stripos($main, $suzuki) !== false) {
			$result = $suzuki;
		} elseif (stripos($main, $sym) !== false) {
			$result = $sym;
		} elseif (stripos($main, $ducati) !== false) {
			$result = $ducati;
		} elseif (stripos($main, $BENELLI) !== false) {
			$result = $BENELLI;
		} elseif (stripos($main, $kawasaki) !== false) {
			$result = $kawasaki;
		} elseif (stripos($main, $peugeot) !== false) {
			$result = $peugeot;
		} else {
			$result = '';
		}
		return $result;
	}

	public function import_depreciation_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$type_property = !empty($data['type_property']) ? $this->security->xss_clean(trim($data['type_property'])) : '';
		$main_property = !empty($data['main_property']) ? $this->security->xss_clean(trim($data['main_property'])) : '';
		$year = !empty($data['year']) ? $this->security->xss_clean(trim($data['year'])) : '';
		$depreciation = !empty($data['depreciation']) ? $this->security->xss_clean((int)trim($data['depreciation'])) : '';
		$main_tai_san = $this->get_main_depreciation_moto($main_property);
		$data_depreciation = $this->depreciation_model->findOne(['type_property' => $type_property, 'main_property' => $main_tai_san, 'year' => $year]);
		if (!empty($main_property)) {
			if (!empty($data_depreciation)) {
				$this->depreciation_model->update(
					['_id' => $data_depreciation['_id']],
					[
						'depreciation' => (string)$depreciation,
						'updated_at' => $this->createdAt,
						'updated_by' => $this->uemail
					]
				);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				$this->depreciation_model->insert([
					'type_property' => $type_property,
					'main_property' => $main_property,
					'year' => $year,
					'depreciation' => $depreciation,
					'name' => $year . ' năm',
					'slug' => $year . '-nam',
					'created_at' => $this->createdAt,
					'created_by' => $this->uemail
				]);
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
				'message' => 'thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_depreciations($year, $main_property, $type_property)
	{
		$depreciation = [];
		$year_depreciation = (int)date('Y') - (int)$year;
//		$check_name_property = $this->check_tai_san_lay_khau_hao($main_property);
		$check_name_property = $this->get_main_depreciation_moto($main_property);
		if (!empty($check_name_property)) {
			for ($i = 1; $i <= $year_depreciation; $i++) {
				$data_depreciation = $this->depreciation_model->findOne(['main_property' => $check_name_property, 'type_property' => $type_property, 'year' => (string)$i]);
				if (!empty($data_depreciation)) {
					$depreciation[] = [
						"name" => $data_depreciation['name'],
						"slug" => $data_depreciation['slug'],
						"price" => $data_depreciation['depreciation'],
					];
				}
			}
			if ($year_depreciation > 11) {
				$data_depreciation = $this->depreciation_model->findOne(['main_property' => $check_name_property, 'type_property' => $type_property, 'year' => "11"]);
				for ($i = 12; $i <= $year_depreciation; $i++) {
					$depreciation[] = [
						"name" => $i . " năm",
						"slug" => $i . "-nam",
						"price" => (string)((int)$data_depreciation['depreciation'] + 10),
					];
				}
			}
		}
		return $depreciation;
	}

	public function import_list_property_oto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code = !empty($data['code']) ? $this->security->xss_clean(trim($data['code'])) : '';
		$main_property = !empty($data['main_property']) ? $this->security->xss_clean(trim($data['main_property'])) : '';
		$type_property = !empty($data['type_property']) ? $this->security->xss_clean(trim($data['type_property'])) : '';
		$full_name = !empty($data['full_name']) ? $this->security->xss_clean(trim($data['full_name'])) : '';
		$price = !empty($data['price']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['price']))) : '';
		$year = !empty($data['year']) ? $this->security->xss_clean(trim($data['year'])) : '';
		$bo_may = !empty($data['bo_may']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['bo_may']))) : '0';
		$tai_nan_nhe = !empty($data['tai_nan_nhe']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['tai_nan_nhe']))) : '0';
		$tai_nan_nang = !empty($data['tai_nan_nang']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['tai_nan_nang']))) : '0';
		$tai_nan_cua = !empty($data['tai_nan_cua']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['tai_nan_cua']))) : '0';
		$xuoc_son = !empty($data['xuoc_son']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['xuoc_son']))) : '0';
		$den_xuoc_nhe = !empty($data['den_xuoc_nhe']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['den_xuoc_nhe']))) : '0';
		$den_xuoc_sau = !empty($data['den_xuoc_sau']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['den_xuoc_sau']))) : '0';
		$noi_that_xau = !empty($data['noi_that_xau']) ? $this->security->xss_clean(trim(str_replace(array(',', '.',), '', $data['noi_that_xau']))) : '0';
		if (!empty($full_name)) {
			if (stripos($full_name, $year) === false) {
				$property = $this->main_property_model->findOne(['str_name' => "Ô tô " . $full_name . ' ' . $year, 'year_property' => $year]);
			} else {
				$property = $this->main_property_model->findOne(['str_name' => "Ô tô " . $full_name, 'year_property' => $year]);
			}

			$depreciation = [
				0 => [
					"name" => "Bổ máy",
					"slug" => "bo-may",
					"price" => $bo_may
				],
				1 => [
					"name" => "Tai nạn phần đầu, đuôi (bị nhẹ)",
					"slug" => "tai-nan-phan-dau-duoi-bi-nhe",
					"price" => $tai_nan_nhe
				],
				2 => [
					"name" => "Tai nạn phần đầu, đuôi (bị nặng)",
					"slug" => "tai-nan-phan-dau-duoi-bi-nang",
					"price" => $tai_nan_nang
				],
				3 => [
					"name" => "Tai nạn hai bên cửa",
					"slug" => "tai-nan-hai-ben-cua",
					"price" => $tai_nan_cua
				],
				4 => [
					"name" => "Xước sơn, sơn xấu quá nửa xe",
					"slug" => "xuoc-son-son-xau-qua-nua-xe",
					"price" => $xuoc_son
				],
				5 => [
					"name" => "Đèn Pha xước nhẹ, ố vàng",
					"slug" => "den-pha-xuoc-nhe-o-vang",
					"price" => $den_xuoc_nhe
				],
				6 => [
					"name" => "Đèn xước sâu, vỡ phải thay",
					"slug" => "den-xuoc-sau-vo-phai-thay",
					"price" => $den_xuoc_sau
				],
				7 => [
					"name" => "Nội thất  xấu quá 50%",
					"slug" => "noi-that-xau-qua-50",
					"price" => $noi_that_xau
				],
			];

			if (!empty($property)) {
				$this->main_property_model->update(
					[
						'_id' => $property['_id']],
					[
						'code' => $code,
						'year_property' => $year,
						'price' => (string)$price,
						'type_property' => $type_property,
						'updated_at' => (string)$this->createdAt,
						'updated_by' => $this->uemail,
						'depreciations' => $depreciation
					]
				);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'thành công!',
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
//			$property_parent = $this->main_property_model->findOne(['name' => $main_property]);
				$property_parent_id = $this->get_main_property_oto($main_property);
				if (!empty($property_parent_id)) {
					$this->main_property_model->insert(
						[
							'name' => $full_name,
							'code' => $code,
							'parent_id' => $property_parent_id,
							'status' => 'active',
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => $year,
							'price' => (string)$price,
							'type_property' => $type_property,
							'str_name' => stripos($full_name, $year) === false ? "Ô tô " . $full_name . ' ' . $year : "Ô tô " . $full_name,
							'depreciations' => $depreciation
						]
					);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					$main_oto = $this->main_property_model->findOne(['code' => 'OTO']);
					$property_parent_id = $this->main_property_model->insertReturnId(
						[
							'name' => $main_property,
							"code" => '',
							'parent_id' => (string)$main_oto['_id'],
							"status" => "active",
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'price' => '',
							'str_name' => "Ôtô " . $main_property
						]
					);
					$this->main_property_model->insert(
						[
							'name' => $full_name,
							'code' => $code,
							'parent_id' => (string)$property_parent_id,
							'status' => 'active',
							'created_at' => (string)$this->createdAt,
							'created_by' => $this->uemail,
							'year_property' => $year,
							'price' => (string)$price,
							'type_property' => $type_property,
							'str_name' => stripos($full_name, $year) === false ? "Ô tô " . $full_name . ' ' . $year : "Ô tô " . $full_name,
							'depreciations' => $depreciation
						]
					);
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => 'thành công!',
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => 'thành công!',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_detai_property_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$property = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$main = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($property['parent_id'])]);
		$property['main'] = $main['name'];
		$property['price'] = number_format($property['price']);
		if (!empty($property['type_property'])) {
			if ($property['type_property'] == 1) {
				$property['type_property'] = "Xe ga";
			} elseif ($property['type_property'] == 2) {
				$property['type_property'] = "Xe số";
			} elseif ($property['type_property'] == 3) {
				$property['type_property'] = "Xe côn";
			} else {
				$property['type_property'] = $property['type_property'];
			}
		} else {
			$property['type_property'] = '';
		}
		if (!empty($property['depreciations'])) {
			foreach ($property['depreciations'] as $value) {
				$value['price'] = number_format($value['price']);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function block_property_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$property = $this->main_property_model->update(['_id' => new MongoDB\BSON\ObjectId($id)], ['status' => 'block']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function data_depreciations_moto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$vehicles = !empty($data['vehicles']) ? $this->security->xss_clean(($data['vehicles'])) : '';
		$type_property = !empty($data['type_property']) ? $this->security->xss_clean(($data['type_property'])) : '';
		$condition = [];
		if (!empty($vehicles)) {
			$condition['vehicles'] = $vehicles;
		}
		if (!empty($type_property)) {
			$condition['type_property'] = $type_property;
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$data = $this->depreciation_model->get_list_depreciation($condition, $per_page, $uriSegment);
		$total = $this->depreciation_model->get_count_depreciation($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_depreciations_moto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$depreciation = !empty($data['depreciation']) ? $this->security->xss_clean((int)($data['depreciation'])) : '';
		if (empty($depreciation) || (double)$depreciation <= 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Khấu hao không thể trống hoặc giá trị lớn hơn 0',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->depreciation_model->update(['_id' => new MongoDB\BSON\ObjectId($id)], ['depreciation' => (string)$depreciation, 'updated_at' => $this->createdAt, 'updated_by' => $this->uemail]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhật thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function get_main_property_moto($main_property)
	{
		$main = $this->main_property_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_moto as $value) {
			if (stripos($value['name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	private function get_main_property_oto($main_property)
	{
		$main = $this->main_property_model->findOne(['code' => 'OTO', 'status' => 'active']);
		$main_oto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		foreach ($main_oto as $value) {
			if (stripos($value['name'], $main_property) !== false) {
				return (string)$value['_id'];
			}
		}
	}

	private function get_main_depreciation_moto($main_property)
	{
		$main = $this->depreciation_model->find_one_name_main();
		foreach ($main as $value) {
			if (stripos($value, $main_property) !== false) {
				return $value;
			}
		}
	}

	public function get_depreciation_moto_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$depreciation = $this->depreciation_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		if ($depreciation['type_property'] == 1) {
			$depreciation['type'] = "Xe ga";
		} elseif ($depreciation['type_property'] == 2) {
			$depreciation['type'] = "Xe số";
		} else {
			$depreciation['type'] = "Xe côn";
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $depreciation
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_configuration_formality_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data_cc = $this->configuration_formality_model->findOne(['code' => 'CC']);
		$data_dkx = $this->configuration_formality_model->findOne(['code' => 'DKX']);
		$data_tc = $this->configuration_formality_model->findOne(['code' => 'TC']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => [
				'cc' => $data_cc,
				'dkx' => $data_dkx,
				'tc' => $data_tc
			]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_hinh_thuc_cc_or_dkx_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$data = $this->configuration_formality_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_hinh_thuc_tc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$data_tc = $this->configuration_formality_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data_tc
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_hinh_thuc_cc_or_dkx_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$xm = !empty($data['xm']) ? $this->security->xss_clean(trim($data['xm'])) : '';
		$oto = !empty($data['oto']) ? $this->security->xss_clean(trim($data['oto'])) : '';
		if (empty($xm) || (double)$xm <= 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Khấu hao xe máy không thể trống hoặc là 1 giá trị lớn hơn 0',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		if (empty($oto) || (double)$oto <= 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Khấu hao ô tô không thể trống hoặc là 1 giá trị lớn hơn 0',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->configuration_formality_model->update(['_id' => new MongoDB\BSON\ObjectId($id)], ['percent.XM' => (double)$xm, 'percent.OTO' => (double)$oto, 'updated_at' => $this->createdAt, 'updated_by' => $this->uemail]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhật thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_hinh_thuc_tc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $this->security->xss_clean(trim($data['id'])) : '';
		$tc = !empty($data['tc']) ? $this->security->xss_clean(trim($data['tc'])) : '';
		if (empty($tc) || (double)$tc <= 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Khấu hao tín chấp không thể trống hoặc là 1 giá trị lớn hơn 0',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->configuration_formality_model->update(['_id' => new MongoDB\BSON\ObjectId($id)], ['percent.TC' => (double)$tc, 'updated_at' => $this->createdAt, 'updated_by' => $this->uemail]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Cập nhật thành công!',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_list_main_depreciation_moto_post()
	{
		$main = $this->depreciation_model->find_one_name_main();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $main
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_depreciation_moto_auto_post()
	{
		$main = $this->main_property_model->findOne(['code' => 'XM', 'status' => 'active']);
		$main_moto = $this->main_property_model->find_where(['parent_id' => (string)$main['_id'], 'status' => 'active']);
		$list_id_main_moto = [];
		foreach ($main_moto as $value) {
			$list_id_main_moto[] = (string)$value['_id'];
		}
		$condition['list_id_main_moto'] = $list_id_main_moto;
		$condition['status'] = 'active';
		$data = $this->main_property_model->get_list_moto_for_update_depreciation($condition);
		foreach ($data as $value) {
			$main = $this->main_property_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value['parent_id']), 'status' => 'active']);
			if (!empty($value['year_property']) && !empty($main) && !empty($value['type_property'])){
				$depreciation = $this->get_depreciations($value['year_property'], $main['name'], $value['type_property']);
				$this->main_property_model->update(
					[
						'_id' => $value['_id']],
					[
						'depreciations' => $depreciation
					]
				);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}

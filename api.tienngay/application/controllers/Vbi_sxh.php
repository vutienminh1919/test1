<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/BaoHiemVbi.php';

use Restserver\Libraries\REST_Controller;

class Vbi_sxh extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("vbi_sxh_model");
		$this->load->model("log_bh_vbi_model");
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
					$this->name = $this->info['full_name'];
					$this->phone = $this->info['phone_number'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
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

	public function get_list_vbi_sxh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'vbi_sxh';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$code = !empty($data['code']) ? $this->security->xss_clean($data['code']) : '';
		$filter_by_store = !empty($data['filter_by_store']) ? $this->security->xss_clean($data['filter_by_store']) : '';
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($customer_phone)) {
			$condition['customer_phone'] = $customer_phone;
		}
		if (!empty($code)) {
			$condition['code'] = $code;
		}
		if (!empty($filter_by_store)) {
			$condition['filter_by_store'] = $filter_by_store;
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

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'vbi_sxh') {
			$result = $this->vbi_sxh_model->get_list_vbi_sxh($condition, $per_page, $uriSegment);
			$total = $this->vbi_sxh_model->get_count_vbi_sxh($condition);
		} else {
			$result = $this->transaction_model->list_transaction_vbi_sxh($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_vbi_sxh($condition);
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


	public function fees_apply_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$message = $this->validation_form_vbi($data);
		if (!empty($message)) {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
		$NGAY_HL = date('Ymd');
		$NGAY_KT = date('Ymd', strtotime("+1 year"));
		$nsd = $this->config->item("nsd_vbi_tnds");
		$so_id_dtac = md5(uniqid(time()));
		$raw = "nsd=$nsd&so_id_dtac=$so_id_dtac&nv=CN.9&ten=" . $data['ten_chu_hd'] . "&dia_chi=" . $data['diachi_chu_hd'] . "&ngay_sinh=" . date('Ymd', strtotime($data['ngaysinh_chu_hd'])) . "&gioi_tinh=" . $data['gioi_tinh_chu_hd'] . "&cmt=" . $data['cmt_chu_hd'];
		$key = $this->config->item("private_key_vbi");
		$signature = $this->create_signature($raw, $key);

		//nv sxh CN.9
		$param = [
			"dtac_key" => $this->config->item("VBI_CODE"),
			"nsd" => $nsd,
			"so_id_dtac" => $so_id_dtac,
			"nv" => "CN.9",
			"ten" => $data['ten_chu_hd'],
			"dchi" => $data['diachi_chu_hd'],
			"ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_chu_hd'])),
			"gioi_tinh" => $data['gioi_tinh_chu_hd'],
			"cmt" => $data['cmt_chu_hd'],
			"d_thoai" => $data['sdt_chu_hd'],
			"email" => $data['email_chu_hd'],
			"mst" => "",
			"trang_thai_tt" => "D",
			"gcns" => [
				0 => [
					"so_id_dt_dtac" => $so_id_dtac,
					"goi_bh" => $data['goi_bao_hiem'],
					"ten" => $data['ten_nguoi_bh'],
					"dchi" => $data['diachi_nguoi_bh'],
					"ngay_sinh" => date('Ymd', strtotime($data['ngaysinh_nguoi_bh'])),
					"gioi_tinh" => $data['gioi_tinh_nguoi_bh'],
					"cmt" => !empty($data['cmt_nguoi_bh']) ? $data['cmt_nguoi_bh'] : $data['cmt_chu_hd'],
					"cmt_ngay_cap" => !empty($data['cmt_ngay_cap_nguoi_bh']) ? date('Ymd', strtotime($data['cmt_ngay_cap_nguoi_bh'])) : '',
					"cmt_noi_cap" => !empty($data['cmt_noi_cap_nguoi_bh']) ? $data['cmt_noi_cap_nguoi_bh'] : '',
					"d_thoai" => $data['sdt_nguoi_bh'],
					"email" => $data['email_nguoi_bh'],
					"ngay_hl" => $NGAY_HL,
					"ngay_kt" => $NGAY_KT,
					"moi_qh" => $data['moi_quan_he'],
				]
			],
			"signature" => $signature
		];
		$vbi = new BaoHiemVbi();
		$result = $vbi->tao_don_bh_sxh($param);
		$this->log_bh_vbi_model->insert(
			[
				'type' => 'sxh',
				'request' => $param,
				'response' => $result,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
			]
		);
		if ($result->response_code == '00') {
			$store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($data['id_pgd'])]);
			 $ma_cty = $this->check_store_tcv_dong_bac((string)$store['_id']);
            
			$code = "VBI_SXH_" . date("dmY") . "_" . uniqid();
			$insert = [
				'code' => $code,
				'fee' => $result->tong_phi,
				"goi_bh" => $data['goi_bao_hiem'],
				'NGAY_HL' => $NGAY_HL,
				'NGAY_KT' => $NGAY_KT,
				'customer_info' => [
					'customer_name' => $data['ten_chu_hd'],
					'customer_phone' => $data['sdt_chu_hd'],
					'address' => $data['diachi_chu_hd'],
					"cmt" => $data['cmt_chu_hd'],
					"email" => $data['email_chu_hd'],
					'ngay_sinh' => strtotime($data['ngaysinh_chu_hd'])
				],
				'store' => [
					'id' => (string)$store['_id'],
					'name' => $store['name']
				],
				'vbi_sxh' => $result,
				'status' => 10,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
				,'company_code' => $ma_cty
			];
			$this->vbi_sxh_model->insert($insert);
			$result = [
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Thành công!',
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		} else {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => ['Có lỗi xảy ra, liên hệ IT để hỗ trợ!']
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
	}

	private function create_signature($raw, $key)
	{
		$signature = hash_hmac('sha256', $raw, $key);
		return $signature;
	}

	public function validation_form_vbi($data)
	{
		if (empty($data['ten_chu_hd'])) {
			$response[] = "Tên chủ hợp đồng không để trống!";
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_chu_hd'])) {
			$response[] = "Tên chủ hợp đồng không chứa kí tự số hoặc kí tự đặc biệt!";
		}
		if (empty($data['email_chu_hd'])) {
			$response[] = "Email chủ hợp đồng không để trống!";
		}

		if (!filter_var($data['email_chu_hd'], FILTER_VALIDATE_EMAIL)) {
			$response[] = "Email chủ hợp đồng không đúng định dạng!";
		}

		if (empty($data['diachi_chu_hd'])) {
			$response[] = "Địa chỉ chủ hợp đồng không để trống!";
		}

		if (empty($data['ngaysinh_chu_hd'])) {
			$response[] = "Ngày sinh chủ hợp đồng không để trống!";
		}

		if (empty($data['gioi_tinh_chu_hd'])) {
			$response[] = "Giới tính chủ hợp đồng không để trống!";
		}

		if (empty($data['cmt_chu_hd'])) {
			$response[] = "CMT chủ hợp đồng không để trống!";
		}

		if (strlen($data['cmt_chu_hd']) < 9) {
			$response[] = "CMT chủ hợp đồng tối thiểu 9 kí tự!";
		}
		if (!filter_var($data['cmt_chu_hd'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "CMT chủ hợp đồng phải dạng số!";
		}

		if (empty($data['sdt_chu_hd'])) {
			$response[] = "Số điện thoại chủ hợp đồng không để trống!";
		}
		if (!filter_var($data['sdt_chu_hd'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "Số điện thoại chủ hợp đồng phải dạng số!";
		}
		if (strlen($data['sdt_chu_hd']) < 9) {
			$response[] = "Số điện thoại chủ hợp đồng tối thiểu 9 kí tự!";
		}

		if (empty($data['ten_nguoi_bh'])) {
			$response[] = "Tên người được bảo hiểm không để trống!";
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten_nguoi_bh'])) {
			$response[] = "Tên người được bảo hiểm không chứa kí tự số hoặc kí tự đặc biệt!";
		}
		if (empty($data['email_nguoi_bh'])) {
			$response[] = "Email người được bảo hiểm không để trống!";
		}

		if (!filter_var($data['email_nguoi_bh'], FILTER_VALIDATE_EMAIL)) {
			$response[] = "Email người được bảo hiểm không đúng định dạng!";
		}

		if (empty($data['diachi_nguoi_bh'])) {
			$response[] = "Địa chỉ người được bảo hiểm không để trống!";
		}

		if (empty($data['ngaysinh_nguoi_bh'])) {
			$response[] = "Ngày sinh người được bảo hiểm không để trống!";
		}
		$diff = date_diff(date_create(), date_create($data['ngaysinh_nguoi_bh']));
		$age = $diff->format('%Y');
		if ($age < 1 || $age > 70) {
			$response[] = "Độ tuổi người được bảo hiểm không hợp lệ phải từ 1 đến 70 tuổi!";
		}
		if (empty($data['gioi_tinh_nguoi_bh'])) {
			$response[] = "Giới tính người được bảo hiểm không để trống!";
		}

//		if (empty($data['cmt_nguoi_bh'])) {
//			$response[] = "CMT người được bảo hiểm không để trống!";
//		}
		if (!empty($data['cmt_nguoi_bh'])) {
			if (strlen($data['cmt_nguoi_bh']) < 9) {
				$response[] = "CMT người được bảo hiểm tối thiểu 9 kí tự!";
			}
			if (!filter_var($data['cmt_nguoi_bh'], FILTER_VALIDATE_FLOAT)) {
				$response[] = "CMT người được bảo hiểm phải dạng số!";
			}
		}


		if (empty($data['sdt_nguoi_bh'])) {
			$response[] = "Số điện thoại người được bảo hiểm không để trống!";
		}
		if (!filter_var($data['sdt_nguoi_bh'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "Số điện thoại người được bảo hiểm phải dạng số!";
		}
		if (strlen($data['sdt_nguoi_bh']) < 9) {
			$response[] = "Số điện thoại người được bảo hiểm tối thiểu 9 kí tự!";
		}
		if (empty($data['goi_bao_hiem'])) {
			$response[] = "Gói bảo hiểm đang trống!";
		}
//		if (empty($data['cmt_ngay_cap_nguoi_bh'])) {
//			$response[] = "Ngày cấp CMT người được bảo hiểm không để trống!";
//		}
//		if (empty($data['cmt_noi_cap_nguoi_bh'])) {
//			$response[] = "Nơi cấp CMT người được bảo hiểm không để trống!";
//		}
		if (empty($data['price'])) {
			$response[] = "Số tiền không để trống";
		}

		return $response;
	}

	public function check_gioi_tinh_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$ngay_sinh = !empty($data['ngaysinh_nguoi_bh']) ? $data['ngaysinh_nguoi_bh'] : '';
		$gioi_tinh_nguoi_bh = !empty($data['gioi_tinh_nguoi_bh']) ? $data['gioi_tinh_nguoi_bh'] : '';
		$diff = date_diff(date_create(), date_create($ngay_sinh));
		$age = $diff->format('%Y');
		if (empty($age)) {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Độ tuổi không hợp lệ!'
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		} else {
			if ($age < 1 || $age > 70) {
				$result = [
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'Độ tuổi người được bảo hiểm tối thiểu 1 tuổi và tối đa 70 tuổi!'
				];
				$this->set_response($result, REST_Controller::HTTP_OK);
				return;
			} else {
				$list_bh = $this->lay_danh_sach_bao_hiem_sxh();
				$goi_bh = [];
				foreach ($list_bh as $value) {
					if ($value->tu_tuoi <= $age && $value->toi_tuoi >= $age && $value->gioi_tinh == $gioi_tinh_nguoi_bh) {
						array_push($goi_bh, $value);
					}
				}
				$result = [
					'status' => REST_Controller::HTTP_OK,
					'data' => $goi_bh,
					'age' => $age,
					'message' => 'Thanh cong!'
				];
				$this->set_response($result, REST_Controller::HTTP_OK);
				return;
			}

		}

	}

	private function lay_danh_sach_bao_hiem_sxh()
	{
		$vbi = new BaoHiemVbi();
		$data = $vbi->danh_sach_bh_sxh();
		return $data->goi_bh;
	}

	public function get_price_goi_bh_post()
	{
		$data = $this->input->post();
		$goi_bao_hiem = !empty($data['goi_bao_hiem']) ? $data['goi_bao_hiem'] : '';
		$ngay_sinh = !empty($data['ngaysinh_nguoi_bh']) ? $data['ngaysinh_nguoi_bh'] : '';
		$gioi_tinh_nguoi_bh = !empty($data['gioi_tinh_nguoi_bh']) ? $data['gioi_tinh_nguoi_bh'] : '';
		$diff = date_diff(date_create(), date_create($ngay_sinh));
		$age = $diff->format('%Y');
		if (empty($ngay_sinh)) {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Ngày sinh không để trống!'
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($age)) {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Độ tuổi không hợp lệ!'
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		} else {
			if ($age < 1 || $age > 70) {
				$result = [
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => 'Độ tuổi người được bảo hiểm tối thiểu 1 tuổi và tối đa 70 tuổi!'
				];
				$this->set_response($result, REST_Controller::HTTP_OK);
				return;
			} else {
				$list_bh = $this->lay_danh_sach_bao_hiem_sxh();
				foreach ($list_bh as $value) {
					if ($value->tu_tuoi <= $age && $value->toi_tuoi >= $age && $goi_bao_hiem == $value->ma && $value->gioi_tinh == $gioi_tinh_nguoi_bh) {
						$price = $value->phi_bh;
					}
				}
				$result = [
					'status' => REST_Controller::HTTP_OK,
					'price' => number_format($price),
					'message' => 'Thanh cong!'
				];
				$this->set_response($result, REST_Controller::HTTP_OK);
				return;
			}
		}
	}

	public function get_vbi_sxh_accounting_transfe_post()
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
		$vbi = $this->vbi_sxh_model->get_vbi_sxh_accounting_transfe($condition);
		$total_money = 0;
		foreach ($vbi as $value) {
			$total_money += (int)$value['fee'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $vbi,
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
		$code_vbi = !empty($data['code']) ? $data['code'] : '';
		$store_id = !empty($data['store']) ? $data['store'] : '';
		$store = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($store_id)]);
		$storeUser = $this->role_model->get_store_user((string)$this->id);
		 $code_coupon = !empty($data['code_coupon']) ? $data['code_coupon'] : '';
		if (empty($storeUser)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bạn không phải nhân viên PGD"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($code_vbi)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không có dữ liệu gửi sang kế toán"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$code = "PT_" . date('dmY') . '_' . uniqid();
		$money = 0;
		foreach ($code_vbi as $value) {
			$vbi_sxh = $this->vbi_sxh_model->findOne(['code' => $value]);
			$this->vbi_sxh_model->update(['_id' => $vbi_sxh['_id']], ['receipt_code' => $code, 'status' => 2]);
			$money += (int)$vbi_sxh['fee'];
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
			'type' => 12,
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
		$vbi = $this->vbi_sxh_model->find_where(['receipt_code' => $code]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $vbi
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_total_pay_post()
	{
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : '';
		$total = 0;
		foreach ($code as $value) {
			$vbi = $this->vbi_sxh_model->findOne(['code' => $value]);
			$total += (int)$vbi['fee'];
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'total' => number_format($total) . " VND",
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function check_store_tcv_dong_bac($id_pgd)
	{
		$role = $this->role_model->findOne(['slug' => 'cong-ty-cpcn-tcv-dong-bac']);
		$id_store = [];
		if (count($role['stores']) > 0) {
			foreach ($role['stores'] as $store) {
				foreach ($store as $key => $value) {
					$id_store[] = $key;
				}
			}
		}
		if (in_array($id_pgd, $id_store)) {
			return 'TCVĐB';
		}
		return 'TCV';
	}

}

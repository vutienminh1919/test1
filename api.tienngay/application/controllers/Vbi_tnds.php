<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Vbi_tnds_oto.php';

use Restserver\Libraries\REST_Controller;

class Vbi_tnds extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("vbi_tnds_model");
		$this->load->model("log_vbi_tnds_model");
		$this->load->model("danh_muc_xe_vbi_model");
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

	public function create_bill_vbi_tnds_post()
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
		$NGAY_HL = date('d/m/Y');
		$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		$param = [
			// do bieu phi co dinh nen value mặc định
			'vbi_api_common_key' => 'hnrDFeFgbf46FDF899jDb3489G',
			// do bieu phi co dinh nen value mặc định
			'doi_tac' => 'MOBILE',
			'bieu_phi' => 'VBI_CU',
			"nhom" => "xc.2.1",
			"so_id_dtac" => "0",
			"so_id_vbi" => "0",
			"nv" => "xc.2.1",
			"nsd" => $this->config->item("nsd_vbi_tnds"),
			"TEN" => $data['ten'],
			"DCHI" => $data['diachi'],
			"doi_tuong" => "cn",
			"ngay_sinh" => $data['ngaysinh'],
			"gioi_tinh" => $data['gioi_tinh'],
			"cmt" => $data['cmt'],
			"moi_qh" => '',
			"d_thoai" => $data['sdt'],
			"fax" => '',
			"dai_dien" => "",
			"cvu_dai_dien" => "",
			"email" => $data['email'],
			"TVV" => "",
			"MST" => "",
			"bien_xe" => $data['bien_xe'],
			"so_khung" => "",
			"so_may" => "",
			"noi_nhan" => $data['diachi'],
			"hang_xe" => $data['hang_xe'],
			"hieu_xe" => $data['hieu_xe'],
			"nhom_xe" => $data['nhom_xe'],
			"nam_sx" => $data['nam_sx'],
			"loai_xe" => "",
			"so_cho" => $data['so_cho'],
			"ttai" => $data['trong_tai'],
			"md_sd" => "K",
			"dkbs" => "",
			"gtri_xe" => ($data['gia_tri_xe']),
			"list_dk" => [
				0 => [
					"tien_bh" => "",
					"loai" => "BN",
					"lh_nv" => "XC.2.1",
					"mien_thuong" => "",
					"ktru" => "",
					"ngay_hl" => $NGAY_HL,
					"ngay_kt" => $NGAY_KT,
					"tl_phi_yc" => '0'

				],
			],
		];
		$vbi = new Vbi_tnds_oto();
		$result = $vbi->tinh_phi($param);
		$this->log_vbi_tnds_model->insert(
			[
				'type' => 'tinh_phi',
				'request' => $param,
				'response' => $result,
				'created_at' => $this->createdAt,
				'cearted_by' => $this->uemail
			]
		);
		if ($result->response_code == '00') {
			$result = [
				'status' => REST_Controller::HTTP_OK,
				'tong_phi' => number_format($result->resultlist[0]->phi),
				'message' => "Thành công"
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		} else {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => ['Tính phí không thành công!']
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function fees_apply_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$NGAY_HL = date('d/m/Y');
		$NGAY_KT = date('d/m/Y', strtotime("+1 year"));
		$param = [
			'vbi_api_common_key' => $this->config->item("vbi_api_common_key"),
			'doi_tac' => $this->config->item("doi_tac"),
			'bieu_phi' => 'VBI_CU',
			"nhom" => "xc.2.1",
			"so_id_dtac" => "0",
			"so_id_vbi" => "0",
			"nv" => "xc.2.1",
			"nsd" => $this->config->item("nsd_vbi_tnds"),
			"TEN" => $data['ten'],
			"DCHI" => $data['diachi'],
			"doi_tuong" => "cn",
			"ngay_sinh" => $data['ngaysinh'],
			"gioi_tinh" => $data['gioi_tinh'],
			"cmt" => $data['cmt'],
			"moi_qh" => '',
			"d_thoai" => $data['sdt'],
			"fax" => '',
			"dai_dien" => "",
			"cvu_dai_dien" => "",
			"email" => $data['email'],
			"TVV" => "",
			"MST" => "",
			"bien_xe" => $data['bien_xe'],
			"so_khung" => "",
			"so_may" => "",
			"noi_nhan" => $data['diachi'],
			"hang_xe" => $data['hang_xe'],
			"hieu_xe" => $data['hieu_xe'],
			"nhom_xe" => $data['nhom_xe'],
			"nam_sx" => $data['nam_sx'],
			"loai_xe" => "",
			"so_cho" => $data['so_cho'],
			"ttai" => $data['trong_tai'],
			"md_sd" => "K",
			"dkbs" => "",
			'trang_thai' => 'D',
			"gtri_xe" => ($data['gia_tri_xe']),
			"list_dk" => [
				0 => [
					"tien_bh" => trim(str_replace(array(',', '.',), '', $data['price'])),
					"loai" => "BN",
					"lh_nv" => "XC.2.1",
					"mien_thuong" => "",
					"ktru" => "",
					"ngay_hl" => $NGAY_HL,
					"ngay_kt" => $NGAY_KT,
					"tl_phi_yc" => '0'

				],
			],
		];
		$vbi = new Vbi_tnds_oto();
//		$phi_bh = $vbi->tinh_phi($param);
//		$phi = $result->resultlist[0]->phi;
		$result = $vbi->tao_don($param);
		$this->log_vbi_tnds_model->insert(
			[
				'type' => 'tao_don',
				'request' => $param,
				'response' => $result,
				'created_at' => $this->createdAt,
				'cearted_by' => $this->uemail
			]
		);
		if ($result->response_code == '00') {
			$store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($data['id_pgd'])]);
			 $ma_cty = $this->check_store_tcv_dong_bac((string)$store['_id']);
           
			$code = "VBI_TNDS_" . date("dmY") . "_" . time();
			$insert = [
				'code' => $code,
				'fee' => $result->tong_phi,
				'NGAY_HL' => $NGAY_HL,
				'NGAY_KT' => $NGAY_KT,
				'customer_info' => [
					'customer_name' => $data['ten'],
					'customer_phone' => $data['sdt'],
					'address' => $data['diachi'],
					"cmt" => $data['cmt'],
					"email" => $data['email'],
					"bien_xe" => $data['bien_xe'],
					"hang_xe" => $data['hang_xe'],
					"hieu_xe" => $data['hieu_xe'],
					"nhom_xe" => $data['nhom_xe'],
					"nam_sx" => $data['nam_sx'],
					"gtri_xe" => $data['gia_tri_xe'],
				],
				'store' => [
					'id' => (string)$store['_id'],
					'name' => $store['name']
				],
				'vbi_tnds' => $result,
				'status' => 10,
				'created_at' => $this->createdAt,
				'created_by' => $this->uemail
				,'company_code' => $ma_cty
				
			];
			$this->vbi_tnds_model->insert($insert);
			$result = [
				'status' => REST_Controller::HTTP_OK,
				'message' => 'Thành công!',
				'data' => $result->resultlist
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		} else {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => ['Bán bảo hiểm không thành công!']
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function validation_form_vbi($data)
	{
		if (empty($data['ten'])) {
			$response[] = "Tên không để trống!";
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{4,255}$/", $data['ten'])) {
			$response[] = "Tên không chứa kí tự số hoặc kí tự đặc biệt!";
		}
		if (empty($data['email'])) {
			$response[] = "Email không để trống!";
		}

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$response[] = "Email không đúng định dạng!";
		}

		if (empty($data['diachi'])) {
			$response[] = "Địa chỉ không để trống!";
		}

		if (empty($data['ngaysinh'])) {
			$response[] = "Ngày sinh không để trống!";
		}

		if (empty($data['gioi_tinh'])) {
			$response[] = "Giới tính không để trống!";
		}

		if (empty($data['cmt'])) {
			$response[] = "CMT không để trống!";
		}

		if (strlen($data['cmt']) < 9) {
			$response[] = "CMT tối thiểu 9 kí tự!";
		}
		if (!filter_var($data['cmt'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "CMT phải dạng số!";
		}
		if (empty($data['sdt'])) {
			$response[] = "Số điện thoại không để trống!";
		}
		if (!filter_var($data['sdt'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "Số điện thoại phải dạng số!";
		}
		if (strlen($data['sdt']) < 9) {
			$response[] = "Số điện thoại tối thiểu 9 kí tự!";
		}
		if (empty($data['bien_xe'])) {
			$response[] = "Biển xe không để trống!";
		}
		if (strlen($data['bien_xe']) < 7) {
			$response[] = "Biển số xe tối thiểu 7 kí tự!";
		}
		if (!preg_match("/^[A-Z0-9]{7,10}$/", $data['bien_xe'])) {
			$response[] = "Biển số xe không đúng định dạng, không chứa khoảng trắng và kí tự đặc biệt!";
		}
		if (empty($data['hang_xe'])) {
			$response[] = "Hãng xe không để trống!";
		}
		if (empty($data['hieu_xe'])) {
			$response[] = "Hiệu xe không để trống!";
		}

		if (empty($data['nhom_xe'])) {
			$response[] = "Nhóm xe không để trống!";
		}

		if (empty($data['nam_sx'])) {
			$response[] = "Năm sản xuất không để trống!";
		}

		if (empty($data['so_cho'])) {
			$response[] = "Số chỗ không để trống!";
		}
		if (!filter_var($data['so_cho'], FILTER_VALIDATE_INT)) {
			$response[] = "Số chỗ phải dạng số!";
		}
		if (empty($data['trong_tai'])) {
			$response[] = "Trọng tải không để trống!";
		}
		if (!filter_var($data['trong_tai'], FILTER_VALIDATE_FLOAT)) {
			$response[] = "Trọng tải phải dạng số!";
		}
		if (empty($data['gia_tri_xe'])) {
			$response[] = "Giá trị xe không để trống!";
		}
		return $response;
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

	public function get_vbi_tnds_accounting_transfe_post()
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
		$vbi = $this->vbi_tnds_model->get_vbi_tnds_accounting_transfe($condition);
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
		$loai_khach = !empty($data['loai_khach']) ? $data['loai_khach'] : '';
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
			$vbi_tnds = $this->vbi_tnds_model->findOne(['code' => $value]);
			$this->vbi_tnds_model->update(['_id' => $vbi_tnds['_id']], ['receipt_code' => $code, 'status' => 2]);
			$money += (int)$vbi_tnds['fee'];
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
			'type' => 10,
			'status' => 2,
			'code_coupon_cash' => $code_coupon,
			'loai_khach' => $loai_khach,
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
		$vbi = $this->vbi_tnds_model->find_where(['receipt_code' => $code]);
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
			$vbi = $this->vbi_tnds_model->findOne(['code' => $value]);
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

	public function get_list_vbi_tnds_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$tab = !empty($data['tab']) ? $data['tab'] : 'vbi_tnds';
		$start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
		$end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
		$customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
		$code = !empty($data['code']) ? $this->security->xss_clean($data['code']) : '';
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
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		if ($tab == 'vbi_tnds') {
			$result = $this->vbi_tnds_model->get_list_vbi_tnds($condition, $per_page, $uriSegment);
			$total = $this->vbi_tnds_model->get_count_vbi_tnds($condition);
		} else {
			$result = $this->transaction_model->list_transaction_vbi_tnds($condition, $per_page, $uriSegment);
			$total = $this->transaction_model->total_list_transaction_vbi_tnds($condition);
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

	public function get_danh_muc_xe_post()
	{
		$data = $this->input->post();
		$option = !empty($data['option']) ? $data['option'] : '';
		$data = [];
		$danh_muc_xe = $this->danh_muc_xe_vbi_model->find_where(['Nhom' => $option]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $danh_muc_xe,
			'message' => 'thanh cong'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_year_post()
	{
		$start = 2010;
		$end = date('Y');
		$year = (int)$end - $start;
		$data = [];
		for ($i = 0; $i <= $year; $i++) {
			$data[($start + $i)] = $start + $i;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function create_danh_muc_xe_post()
	{
		$vbi = new Vbi_tnds_oto();
		$result = $vbi->danh_muc_xe();
		$data = $result->resultlist;
		foreach ($data as $value) {
			foreach ($value as $key => $item) {
				$insert[$key] = $item;
			}
			$insert['created_at'] = $this->createdAt;
			$insert['created_by'] = 'superadmin@tienngay.vn';
			$this->danh_muc_xe_vbi_model->insert($insert);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
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

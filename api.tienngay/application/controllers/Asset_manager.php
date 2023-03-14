<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Asset_manager extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_model');
		$this->load->model('mic_model');
		$this->load->model('gic_easy_model');
		$this->load->model('gic_plt_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_gic_model');
		$this->load->model('log_mic_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
		$this->load->model("notification_app_model");
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("sms_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('coupon_model');
		$this->load->model('verify_identify_contract_model');
		$this->load->model('device_model');
		$this->load->helper('lead_helper');
		$this->load->model('vbi_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('asset_management_model');
		$this->load->model('contract_debt_model');
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

	public function get_all_asset_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
		$asset_code = !empty($data['asset_code']) ? $data['asset_code'] : '';
		$so_khung = !empty($data['so_khung']) ? $data['so_khung'] : '';
		$so_may = !empty($data['so_may']) ? $data['so_may'] : '';
		$type_asset = !empty($data['type_asset']) ? $data['type_asset'] : '';
		$text = !empty($data['text']) ? $data['text'] : '';
		$condition = [];
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($text)) {
			$condition['text'] = trim($text);
		}
		if (!empty($asset_code)) {
			$condition['asset_code'] = trim($asset_code);
		}
		if (!empty($so_khung)) {
			$condition['so_khung'] = trim($so_khung);
		}
		if (!empty($so_may)) {
			$condition['so_may'] = trim($so_may);
		}
		if (!empty($type_asset)) {
			$condition['type_asset'] = (is_array($type_asset)) ? $type_asset : [$type_asset];
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$data = $this->asset_management_model->get_all_list_asset($condition, $per_page, $uriSegment);
		$total = $this->asset_management_model->get_count_all_asset($condition);
		foreach ($data as $value) {
			$contract = $this->contract_model->find_where(['asset_code' => $value['asset_code']]);
			$value['so_hd_lien_quan'] = count($contract);
			if (!empty($contract)) {
				foreach ($contract as $item) {
					$value['contract'][] =
						[
							'contract_id' => (string)$item['_id'],
							'code_contract' => !empty($item['code_contract_disbursement']) ? $item['code_contract_disbursement'] : $item['code_contract'],
							'houseHold_address' => $item['houseHold_address'],
							'loan_infor' => $item['loan_infor'],
							'status' => $item['status'],

						];
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'total' => $total,
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function test_post()
	{
		$contract = $this->contract_model->findOne(['code_contract' => '000001342']);
		$asset = $this->asset_management_model->findOne(['asset_code' => '00000005']);
		$image = [];
		foreach ($asset['image'] as $key => $value) {
			$image[$key] = $value;
		}
		$driver_license = [];
		foreach ($contract['image_accurecy']['driver_license'] as $k => $value) {
			$driver_license[$k] = $value;
		}
		$a = (object)array_merge($driver_license, $image);
		$this->asset_management_model->update(['asset_code' => '00000005'], ['image' => (object)array_merge($driver_license, $image)]);
	}

	public function get_image_asset_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : '';
		$data = $this->asset_management_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'message' => "thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function add_new_asset_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$response = $this->check_validate_form($data);
		if (!empty($response)) {
			$html = [];
			foreach ($response as $value) {
				array_push($html, $value['message']);
			}
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $html
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
		$type = !empty($data['loai_xe']) ? ($data['loai_xe']) : '';
		$name_customer = !empty($data['name_customer']) ? trim($data['name_customer']) : '';
		$address = !empty($data['address']) ? trim($data['address']) : '';
		$ten_tai_san = !empty($data['product']) ? trim($data['product']) : '';
		$nhan_hieu = !empty($data['nhan_hieu']) ? trim($data['nhan_hieu']) : '';
		$model = !empty($data['model']) ? trim($data['model']) : '';
		$bien_so = !empty($data['bien_so']) ? strtoupper(trim(str_replace(array('.', '-', ' '), '', $data['bien_so']))) : '';
		$so_khung = !empty($data['so_khung']) ? strtoupper(trim($data['so_khung'])) : '';
		$so_may = !empty($data['so_may']) ? strtoupper(trim($data['so_may'])) : '';
		$so_dang_ki = !empty($data['so_dang_ki']) ? trim($data['so_dang_ki']) : '';
		$ngay_cap = !empty($data['ngay_cap']) ? strtotime(trim($data['ngay_cap'])) : '';
		$note = !empty($data['note']) ? trim($data['note']) : '';
		$image = !empty($data['image']) ? $data['image'] : '';
		$number_asset = $this->initNumberAssetCode();
		$asset_code = $this->create_asset_code($number_asset);
		$param = [
			'customer_name' => $name_customer,
			'number_asset' => $number_asset,
			'asset_code' => $asset_code,
			'type' => $type,
			'product' => $ten_tai_san,
			'bien_so_xe' => $bien_so,
			'nhan_hieu' => $nhan_hieu,
			'model' => $model,
			'so_khung' => $so_khung,
			'so_may' => $so_may,
			'dia_chi' => $address,
			"so_dang_ki" => $so_dang_ki,
			'ngay_cap' => $ngay_cap,
			'image' => $image,
			'status' => 'active',
			'created_at' => $this->createdAt,
			'created_by' => $this->uemail
		];
		$this->asset_management_model->insert($param);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function initNumberAssetCode()
	{
		$maxNumber = $this->asset_management_model->getMaxNumberAsset();
		$maxNumberContract = !empty($maxNumber[0]['number_asset']) ? (int)$maxNumber[0]['number_asset'] + 1 : 1;
		return $maxNumberContract;
	}

	private function create_asset_code($number_asset)
	{
		$lengStr = strlen($number_asset);
		switch ($lengStr) {
			case 1:
				$asset_code = "0000000" . $number_asset;
				break;
			case 2:
				$asset_code = "000000" . $number_asset;
				break;
			case 3:
				$asset_code = "00000" . $number_asset;
				break;
			case 4:
				$asset_code = "0000" . $number_asset;
				break;
			case 5:
				$asset_code = "000" . $number_asset;
				break;
			case 6:
				$asset_code = "00" . $number_asset;
				break;
			case 7:
				$asset_code = "0" . $number_asset;
				break;
			default:
				$asset_code = (string)$number_asset;
		}

		return $asset_code;
	}

	private function check_validate_form($data)
	{
		if (empty($data['name_customer'])) {
			$response[] = array(
				'message' => "Tên khách hàng không để trống!"
			);
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{3,255}$/", $data['name_customer'])) {
			$response[] = array(
				'message' => "Tên khách hàng không đúng định dạng hoặc không chứa kí tự số!"
			);
		}
		if (empty($data['address'])) {
			$response[] = array(
				'message' => "Địa chỉ không để trống!"
			);
		}

		if (empty($data['product'])) {
			$response[] = array(
				'message' => "Sản phẩm không để trống!"
			);
		}
		if (empty($data['nhan_hieu'])) {
			$response[] = array(
				'message' => "Nhãn hiệu không để trống!"
			);
		}
		if (empty($data['model'])) {
			$response[] = array(
				'message' => "Model không để trống!"
			);
		}
		if (empty($data['bien_so'])) {
			$response[] = array(
				'message' => "Biển số không để trống!"
			);
		}
		if (!preg_match("/^[A-Z0-9]{7,9}$/", $data['bien_so'])) {
			$response[] = array(
				'message' => "Biển số xe không đúng định dạng, không chứa khoảng trắng và kí tự đặc biệt! VD:30L18888"
			);
		}
		if (empty($data['so_khung'])) {
			$response[] = array(
				'message' => "Số khung không để trống!"
			);
		}
		if (!preg_match("/^[a-zA-Z0-9]{5,30}$/", $data['so_khung'])) {
			$response[] = array(
				'message' => "Số khung không đúng định dạng, không chứa khoảng trắng và kí tự đặc biệt!"
			);
		}
		if (empty($data['so_may'])) {
			$response[] = array(
				'message' => "Số máy không để trống!"
			);
		}
		if (!preg_match("/^[a-zA-Z0-9]{5,30}$/", $data['so_may'])) {
			$response[] = array(
				'message' => "Số máy không đúng định dạng, không chứa khoảng trắng và kí tự đặc biệt!"
			);
		}
		if (empty($data['so_dang_ki'])) {
			$response[] = array(
				'message' => "Số đăng kí không để trống!"
			);
		}
		if (!preg_match("/^[0-9]{5,30}$/", $data['so_dang_ki'])) {
			$response[] = array(
				'message' => "Số đăng kí phải kí tự số, không chứa khoảng trắng và kí tự đặc biệt!"
			);
		}
		if (empty($data['ngay_cap'])) {
			$response[] = array(
				'message' => "Ngày cấp không để trống!"
			);
		}
		if (empty($data['image'])) {
			$response[] = array(
				'message' => "Hình ảnh không để trống!"
			);
		}
		$asset1 = $this->asset_management_model->findOne(['so_khung' => $data['so_khung'], 'status' => 'active']);
		if (!empty($asset1)) {
			$response[] = array(
				'message' => "Số khung đã tồn tại!"
			);
		}

		$asset2 = $this->asset_management_model->findOne(['so_may' => $data['so_may'], 'status' => 'active']);
		if (!empty($asset2)) {
			$response[] = array(
				'message' => "Số máy đã tồn tại!"
			);
		}
		return $response;
	}

	public function add_tin_chap_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$message = $this->validate_form_nha_dat($data);
		if (!empty($message)) {
			$result = [
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message
			];
			$this->set_response($result, REST_Controller::HTTP_OK);
			return;
		}
		$number_asset = $this->initNumberAssetCode();
		$asset_code = $this->create_asset_code($number_asset);
		$data['number_asset'] = $number_asset;
		$data['asset_code'] = $asset_code;
		$data['type'] = 'NĐ';
		$data['product'] = 'Quyền sử dụng đất';
		$data['status'] = 'active';
		$data['created_at'] = $this->createdAt;
		$data['created_by'] = $this->uemail;
		$this->asset_management_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function validate_form_nha_dat($data)
	{
		if (empty($data['customer_name'])) {
			$message[] = "Tên khách hàng không để trống!";
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{3,255}$/", $data['customer_name'])) {
			$message[] = "Tên khách hàng không đúng định dạng hoặc không chứa kí tự số!";
		}
		if (empty($data['nam_sinh'])) {
			$message[] = "Năm sinh khách hàng không để trống!";
		}
		if (!filter_var($data['nam_sinh'], FILTER_VALIDATE_INT)) {
			$message[] = "Năm sinh phải dạng số";
		}
		if (strlen($data['nam_sinh']) != 4) {
			$message[] = "Năm sinh phải 4 kí tự!";
		}
		if (empty($data['cmt'])) {
			$message[] = "CMT không để trống!";
		}
		if (!filter_var($data['cmt'], FILTER_VALIDATE_INT)) {
			$message[] = "CMT phải dạng số";
		}
		if (strlen($data['cmt']) < 9) {
			$message[] = "CMT tối thiểu 9 kí tự!";
		}
		if (empty($data['dia_chi'])) {
			$message[] = "Địa chỉ không để trống!";
		}
		if (empty($data['nguoi_lien_quan'])) {
			$message[] = "Người liên quan không để trống!";
		}
		if (!preg_match("/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀẾỂưăạảấầẩẫậắằẳẵặẹẻẽềếểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ ]{3,255}$/", $data['nguoi_lien_quan'])) {
			$message[] = "Tên người liên quan không đúng định dạng hoặc không chứa kí tự số!";
		}
		if (empty($data['nam_sinh_nguoi_lien_quan'])) {
			$message[] = "Năm sinh người liên quan không để trống!";
		}
		if (!filter_var($data['nam_sinh_nguoi_lien_quan'], FILTER_VALIDATE_INT)) {
			$message[] = "Năm sinh người liên quan phải dạng số";
		}
		if (strlen($data['nam_sinh_nguoi_lien_quan']) != 4) {
			$message[] = "Năm sinh người liên quan phải 4 kí tự!";
		}
		if (empty($data['cmt_nguoi_lien_quan'])) {
			$message[] = "CMT người liên quan không để trống!";
		}
		if (!filter_var($data['cmt_nguoi_lien_quan'], FILTER_VALIDATE_INT)) {
			$message[] = "CMT người liên quan phải dạng số";
		}
		if (strlen($data['cmt']) < 9) {
			$message[] = "CMT người liên quan tối thiểu 9 kí tự!";
		}
		if (empty($data['dia_chi_nguoi_lien_quan'])) {
			$message[] = "Địa chỉ người liên quan không để trống!";
		}
		if (empty($data['thua_dat_so'])) {
			$message[] = "Số sổ không để trống!";
		}
		if (empty($data['dia_chi_nha_dat'])) {
			$message[] = "Địa chỉ nhà đất không để trống!";
		}
		if (empty($data['hinh_thuc_su_dung'])) {
			$message[] = "Hình thức sử dụng nhà đất không để trống!";
		}
		if (empty($data['muc_dich_su_dung'])) {
			$message[] = "Mục đích sử dụng không để trống!";
		}
		if (empty($data['thoi_han_su_dung_dat'])) {
			$message[] = "Thời hạn sử dụng không để trống!";
		}
		if (empty($data['loai_nha_o'])) {
			$message[] = "Loại nhà ở không để trống!";
		}
		if (empty($data['dien_tich_nha_o'])) {
			$message[] = "Diện tích nhà ở không để trống!";
		}
		if (empty($data['ket_cau_nha_o'])) {
			$message[] = "Kết cấu nhà ở không để trống!";
		}
		if (empty($data['cap_nha_o'])) {
			$message[] = "Cấp nhà ở không để trống!";
		}
		if (empty($data['so_tang_nha_o'])) {
			$message[] = "Số tầng nhà không để trống!";
		}
		if (empty($data['thoi_gian_song'])) {
			$message[] = "Thời gian sống không để trống!";
		}
		if (empty($data['image'])) {
			$message[] = "Hình ảnh không để trống!";
		}
		return $message;
	}


	public function check_asset_post()
	{
		$data = $this->input->post();
		$so_khung = !empty($data['so_khung']) ? $data['so_khung'] : '';
		$so_may = !empty($data['so_may']) ? $data['so_may'] : '';
		$asset1 = $this->asset_management_model->findOne(['so_khung' => strtoupper($so_khung)]);
		$asset2 = $this->asset_management_model->findOne(['so_may' => strtoupper($so_may)]);
		$data = [];
		if (!empty($asset1)) {
			$contract1 = $this->contract_model->find_where(['asset_code' => $asset1['asset_code'], 'status' => 17]);
			if (!empty($contract1)) {
				foreach ($contract1 as $value) {
					$data[] = [
						'id' => (string)$value['_id'],
						'code_contract' => $value['code_contract']
					];
				}
			}
		}
		if (!empty($asset2)) {
			$contract2 = $this->contract_model->find_where(['asset_code' => $asset2['asset_code'], 'status' => 17]);
			if (!empty($contract2)) {
				foreach ($contract2 as $item) {
					$data[] = [
						'id' => (string)$item['_id'],
						'code_contract' => $item['code_contract']
					];
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => array_unique($data),
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function check_asset_estate_post() {
		$data = $this->input->post();
		$thua_dat_so = !empty($data['thua-dat-so']) ? $data['thua-dat-so'] : '';
		$asset = $this->asset_management_model->findOne(['thua_dat_so' => $thua_dat_so]);
		$data = [];

		if (!empty($asset)) {
			$contract = $this->contract_model->find_where(['asset_code' => $asset['asset_code'], 'status' => 17]);
			if (!empty($contract)) {
				foreach ($contract as $item) {
					$data[] = [
						'id' => (string)$item['_id'],
						'code_contract' => $item['code_contract']
					];
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => array_unique($data),
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
}

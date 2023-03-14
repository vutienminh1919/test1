<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
// error_reporting(-1);
// ini_set('display_errors', 1);
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;
class Report_kt extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model("report_trich_lap_du_phong_model");
		$this->load->model("report_trich_lap_du_phong_pgd_model");
		$this->load->model("report_detail_bad_debt_model");
		$this->load->model("report_history_contract_model");
		$this->load->model("report_history_transaction_model");
		$this->load->model("report_real_revenue_model");
		$this->load->model("java_report_model");
		$this->load->model('role_model');
		$this->load->model('area_model');
		$this->load->model('store_model');
		$this->load->model('bank_transaction_model');
		$this->load->model('transaction_model');
		$this->load->model('contract_model');

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

	public function report_tldp_post() {
		$input = $this->input->post();
		$condition = [];

		if ( isset($input['store']) ) {
			$condition['store.id'] = $input['store'];
		}

		if ( isset($input['dept']) ) {
			$dept = explode(",", $input['dept']);
			$dept_run = [];
			foreach ($dept as $item) {
				$dept_run[] = (int) $item;
			}
			$condition['group_dept'] = [
				'$in' => $dept_run
			];
		}

		if ( isset($input['reset']) ) {
			$this->java_report_model->update("report_prevent_bad_debt");
		}

		$data = $this->report_trich_lap_du_phong_model->find($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_tldp_pgd_post() {
		$input = $this->input->post();
		$condition = [];

		if ( isset($input['dept']) ) {
			$dept = explode(",", $input['dept']);
			$dept_run = [];
			foreach ($dept as $item) {
				$dept_run[] = (int) $item;
			}
			$condition['group_dept'] = [
				'$in' => $dept_run
			];
		}

		if ( isset($input['reset']) ) {
			$this->java_report_model->update("report_prevent_bad_debt_pgd");
		}

		$data = $this->report_trich_lap_du_phong_pgd_model->find($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_detail_dept_post() {
		$input = $this->input->post();

		if ( isset($input['reset']) ) {
			$this->java_report_model->update("report_detail_bad_dept");
		}

		$condition["nhom_no"] = [
			'$ne' => null
		];

		if ( isset($input['store']) ) {
			$condition['store.id'] = $input['store'];
		}

		if ( isset($input['dept']) ) {
			$dept = explode(",", $input['dept']);
			$dept_run = [];
			foreach ($dept as $item) {
				$dept_run[] = (int) $item;
			}
			$condition['nhom_no'] = [
				'$in' => $dept_run
			];
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->report_detail_bad_debt_model->find($condition, $per_page, $uriSegment);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_gach_no_tu_dong_post() {
		$input = $this->input->post();
		$condition = [];
		// Condition
		if ( isset($input['bank']) ) {
			$condition['bank'] = $input['bank'];
		}
		if ( isset($input['code']) ) {
			$condition['code'] = $input['code'];
		}
		if ( isset($input['contract_code']) ) {
			$condition['contract_code'] = $input['contract_code'];
		}
		if ( isset($input['status']) ) {
			$condition['status'] = $input['status'] == '1' ? true : false;
		}
		if ( isset($input['fromdate']) || isset($input['todate']) ) {
			if ( isset($input['fromdate']) ) {
				$condition['date']['$gte'] = strtotime($input['fromdate']. ' 00:00:00');
			}
			if ( isset($input['todate']) ) {
				$condition['date']['$lte'] = strtotime($input['todate']. " 23:59:59");
			}
		}
		// Query
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->bank_transaction_model->find($condition, $per_page, $uriSegment);
		$result = [];
		foreach ($data as $item) {
			if (isset( $item['transaction_code'] )) {
				$tranId = $item['transaction_code']->jsonSerialize()['$oid'];
				$item['transaction'] = $this->transaction_model->findOne([
					'_id' => new \MongoDB\BSON\ObjectId($tranId)
				]);
			}
			$result[] = $item;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function action_transaction_change_post() {
		$error = '';
		$input = $this->input->post();
		$data = $this->bank_transaction_model->update([
			'_id' => new \MongoDB\BSON\ObjectId($input['id'])
		], [
			'contract_code' => $input['ma_phieu_ghi'],
			'ma_hop_dong' => $input['ma_hop_dong'],
			'ten_khach_hang' => $input['ten_khach_hang'],
			'ghi_chu' => $input['ghi_chu'],
		]);
		if ($data) {
			// Lấy data
			$bankData = $this->bank_transaction_model->findOne([
				'_id' => new \MongoDB\BSON\ObjectId($input['id'])
			]);
			// Tạo phiếu thu
			$contract = $this->contract_model->findOne([
				'code_contract' => $input['ma_phieu_ghi']
			]);
			// Check mã ngân hàng
			$checkTran = $this->transaction_model->findOne([
				'code_transaction_bank' => $bankData['code'],
			]);
			if ($checkTran) {
				$error = 'Đã tồn tại phiếu thu chứa mã ngân hàng';
			}
			if (empty($contract)) {
				$error = "Mã hợp đồng không tồn tại";
			}
			if ( !empty($contract) && !$checkTran ) {
				$code = 'PT_' . date("Ymd") . '_' . uniqid();
				$data_transaction = [
					'code_contract' => $contract['code_contract'] ?? '',
					'code_contract_disbursement' => $contract['code_contract_disbursement'] ?? '',
					'customer_name' => $contract['customer_name'] ?? '',
					'total' => $bankData['money'] ?? 0,
					'code' => $code,
					'type' => 4,
					'payment_method' => 2,
					'store' => $contract['store'] ?? '',
					'date_pay' => time(),
					'status' => 4,
					'customer_bill_phone' => $contract['customer_infor']['customer_phone_number'] ?? '',
					'customer_bill_name' => $bankData['ten_khach_hang'],
					'note' => $bankData['ghi_chu'],
					'bank' => $bankData['bank'],
					'code_transaction_bank' => $bankData['code'],
					'type_payment' => 1,
					'created_by' => 'system',
					'created_at' => time()
				];
				$tranid = $this->transaction_model->insertReturnId($data_transaction);
				$this->bank_transaction_model->update([
					'_id' => new \MongoDB\BSON\ObjectId($input['id'])
				], [
					'transaction_code' => $tranid,
				]);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => (string) $tranid,
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return ;
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_BAD_REQUEST,
			'message' => $error
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function export_gach_no_tu_dong_post() {
		$input = $this->input->post();
		$condition = [];
		// Condition
		if ( isset($input['bank']) ) {
			$condition['bank'] = $input['bank'];
		}
		if ( isset($input['code']) ) {
			$condition['code'] = $input['code'];
		}
		if ( isset($input['contract_code']) ) {
			$condition['contract_code'] = $input['contract_code'];
		}
		if ( isset($input['status']) ) {
			$condition['status'] = $input['status'] == '1' ? true : false;
		}
		if ( isset($input['fromdate']) || isset($input['todate']) ) {
			if ( isset($input['fromdate']) ) {
				$condition['date']['$gte'] = strtotime($input['fromdate']. ' 00:00:00');
			}
			if ( isset($input['todate']) ) {
				$condition['date']['$lte'] = strtotime($input['todate']. " 23:59:59");
			}
		}
		$data = $this->bank_transaction_model->find_where($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_gach_no_tu_dong_count_all_post() {
		$condition = [];
		$data = $this->bank_transaction_model->count($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_detail_dept_all_post() {
		$input = $this->input->post();

		if ( isset($input['reset']) ) {
			$this->java_report_model->update("report_detail_bad_dept");
		}

		$condition["nhom_no"] = [
			'$ne' => null
		];

		if ( isset($input['store']) ) {
			$condition['store.id'] = $input['store'];
		}

		if ( isset($input['dept']) ) {
			$dept = explode(",", $input['dept']);
			$dept_run = [];
			foreach ($dept as $item) {
				$dept_run[] = (int) $item;
			}
			$condition['nhom_no'] = [
				'$in' => $dept_run
			];
		}

		$data = $this->report_detail_bad_debt_model->findAll($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_detail_dept_count_all_post() {
		$input = $this->input->post();
		$condition["nhom_no"] = [
			'$ne' => null
		];
		if ( isset($input['store']) ) {
			$condition['store.id'] = $input['store'];
		}

		if ( isset($input['dept']) ) {
			$dept = explode(",", $input['dept']);
			$dept_run = [];
			foreach ($dept as $item) {
				$dept_run[] = (int) $item;
			}
			$condition['nhom_no'] = [
				'$in' => $dept_run
			];
		}
		$data = $this->report_detail_bad_debt_model->count($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_java_post() {
		$data = $this->java_report_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function report_thu_hoi_no_store_post() {
		$input = $this->input->post();
		$data = [];
		if (isset($input['store'])) {
			$fromdate = $input['fromdate'] ? $input['fromdate'] : '';
			$todate = $input['todate'] ? $input['todate'] : '';
			$store = $input['store'];
			$data['du_no_giai_ngan'] = $this->report_detail_bad_debt_model->get_count_tien_vay([
				'store' => $store,
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			$data['du_no_cho_vay'] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
				'trang_thai' => 'cho_vay',
				'store' => $store,
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			for ($i = 0; $i <= 8; $i++) {
				$data['nhom_no']['nhom_'. $i] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
					'trang_thai' => 'cho_vay',
					'nhom_no' => $i,
					'store' => $store,
					'fromdate' => $fromdate,
					'todate' => $todate
				]);
			}
			$data['tong_du_no_xau'] = 0;
			for ($i = 4; $i <= 8; $i++) {
				$data['tong_du_no_xau'] += $data['nhom_no']['nhom_'. $i];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK); 
	}

	public function report_thu_hoi_no_detail_post() {
		$input = $this->input->post();
		$data = [];
		if (isset($input['area'])) {
			$area = $input['area'];
			$fromdate = $input['fromdate'] ? $input['fromdate'] : '';
			$todate = $input['todate'] ? $input['todate'] : '';
			$data['du_no_giai_ngan'] = $this->report_detail_bad_debt_model->get_count_tien_vay([
				'vung_mien' => strtoupper($area),
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			$data['du_no_cho_vay'] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
				'trang_thai' => 'cho_vay',
				'vung_mien' => strtoupper($area),
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			for ($i = 0; $i <= 8; $i++) {
				$data['nhom_no']['nhom_'. $i] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
					'trang_thai' => 'cho_vay',
					'nhom_no' => $i,
					'vung_mien' => strtoupper($area),
					'fromdate' => $fromdate,
					'todate' => $todate
				]);
			}
			$data['tong_du_no_xau'] = 0;
			for ($i = 4; $i <= 8; $i++) {
				$data['tong_du_no_xau'] += $data['nhom_no']['nhom_'. $i];
			}
			$temp = $this->report_detail_bad_debt_model->get_thn_store([
				'trang_thai' => 'cho_vay',
				'vung_mien' => strtoupper($area),
				'fromdate' => $fromdate,
				'todate' => $todate
			]);

			foreach ($temp as $item) {
				if ($item->_id->nhom_no === 0) {
					$data['store'][$item->_id->store->id]['nhom_0'] = $item->count;
				} else if ($item->_id->nhom_no <= 8 && $item->_id->nhom_no !== null) {
					$data['store'][$item->_id->store->id]['nhom_'. $item->_id->nhom_no] = $item->count;
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK); 
	}

	public function report_thu_hoi_no_area_post() {
		$input = $this->input->post();
		$fromdate = $input['fromdate'] ? $input['fromdate'] : '';
		$todate = $input['todate'] ? $input['todate'] : '';

		$data = [];
		// Vfc
		$data['vfc']['du_no_giai_ngan'] = $this->report_detail_bad_debt_model->get_count_tien_vay([]);
		$data['vfc']['du_no_cho_vay'] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
			'trang_thai' => 'cho_vay',
			'fromdate' => $fromdate,
			'todate' => $todate
		]);
		for ($i = 0; $i <= 8; $i++) {
			$data['vfc']['nhom_no']['nhom_'. $i] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
				'trang_thai' => 'cho_vay',
				'nhom_no' => $i,
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
		}
		$data['vfc']['tong_du_no_xau'] = 0;
		for ($i = 4; $i <= 8; $i++) {
			$data['vfc']['tong_du_no_xau'] += $data['vfc']['nhom_no']['nhom_'. $i];
		}

		$arrArea = [
			'mb',
			'mn',
			'vmc'
		];

		foreach ($arrArea as $area) {
			$data[$area]['du_no_giai_ngan'] = $this->report_detail_bad_debt_model->get_count_tien_vay([
				'vung_mien' => strtoupper($area),
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			$data[$area]['du_no_cho_vay'] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
				'trang_thai' => 'cho_vay',
				'vung_mien' => strtoupper($area),
				'fromdate' => $fromdate,
				'todate' => $todate
			]);
			for ($i = 0; $i <= 8; $i++) {
				$data[$area]['nhom_no']['nhom_'. $i] = $this->report_detail_bad_debt_model->get_count_du_no_dang_cho_vay([
					'trang_thai' => 'cho_vay',
					'nhom_no' => $i,
					'vung_mien' => strtoupper($area),
					'fromdate' => $fromdate,
					'todate' => $todate
				]);
			}
			$data[$area]['tong_du_no_xau'] = 0;
			for ($i = 4; $i <= 8; $i++) {
				$data[$area]['tong_du_no_xau'] += $data[$area]['nhom_no']['nhom_'. $i];
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK); 
	}

	public function report_thu_hoi_no_area_store_post() {
		$input = $this->input->post();
		$data = [];
		if (isset($input['area'])) {
			$area = $input['area'];
			if ($area == 'vmc') {
				$storeData = $this->store_model->find_where([
					'code_area' => "KV_MK",
					'status' => 'active',
				]);
				$data = $storeData;
			} else {
				$areaData = $this->area_model->find_where([
					'domain.code' => strtoupper($area),
					'region.code' => [
						'$ne' => 'VMC'
					]
				]);
				$arrArea = [];
				foreach ($areaData as $item) {
					if (isset($item['code'])) {
						$arrArea[] = $item['code'];
					}
				}
				$storeData = $this->store_model->find_where([
					'code_area' => [
						'$in' => $arrArea
					],
					'status' => 'active',
				]);
				$data = $storeData;
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK); 
	}

	public function report_history_contract_count_all_post() {
		//Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$so_dien_thoai = !empty($this->dataPost['so_dien_thoai']) ? $this->dataPost['so_dien_thoai'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$san_pham_vay = !empty($this->dataPost['san_pham_vay']) ? $this->dataPost['san_pham_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}
		
		$data = $this->report_history_contract_model->count($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_history_contract_all_post() {
		//Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$so_dien_thoai = !empty($this->dataPost['so_dien_thoai']) ? $this->dataPost['so_dien_thoai'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$san_pham_vay = !empty($this->dataPost['san_pham_vay']) ? $this->dataPost['san_pham_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}
		// Query
		$data = $this->report_history_contract_model->find_where($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_history_contract_post() {
		//Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$so_dien_thoai = !empty($this->dataPost['so_dien_thoai']) ? $this->dataPost['so_dien_thoai'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$san_pham_vay = !empty($this->dataPost['san_pham_vay']) ? $this->dataPost['san_pham_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_giai_ngan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_giai_ngan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}
		// Query
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->report_history_contract_model->find($condition, $per_page, $uriSegment);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function java_report_run_post() {
		$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		$this->java_report_model->update($name);
		$this->set_response("Success", REST_Controller::HTTP_OK);
		return ;
	}

	public function java_report_reset_post() {
		$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		$this->java_report_model->delete($name);
		$this->set_response("Success", REST_Controller::HTTP_OK);
		return ;
	}

	public function report_history_transaction_count_all_post() {
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		$data = $this->report_history_transaction_model->count($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

		public function report_history_transaction_sum_post() {
		// Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}

		// Count
		$report = $this->report_history_transaction_model->sum_data($condition);
		$data['tien_goc_da_thu_hoi'] = $report[0]['tien_goc_da_thu_hoi'];
		$data['tien_lai_da_thu_hoi'] = $report[0]['tien_lai_da_thu_hoi'];
		$data['tien_phi_da_thu_hoi'] = $report[0]['tien_phi_da_thu_hoi'];
		$data['tien_phi_gia_han_da_thu_hoi'] = $report[0]['tien_phi_gia_han_da_thu_hoi'];
		$data['tien_phi_cham_tra_da_thu_hoi'] = $report[0]['tien_phi_cham_tra_da_thu_hoi'];
		$data['tien_phi_truoc_han_da_thu_hoi'] = $report[0]['tien_phi_truoc_han_da_thu_hoi'];
		$data['tien_phi_qua_han_da_thu_hoi'] = $report[0]['tien_phi_qua_han_da_thu_hoi'];
		$data['tong_thu_hoi_thuc_te'] = $report[0]['tong_thu_hoi_thuc_te'];
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_history_transaction_post() {
		// Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}

		// Query
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->report_history_transaction_model->find($condition, $per_page, $uriSegment);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_history_transaction_excel_post() {
		// Filter
		$fromdate = !empty($this->dataPost['fromdate']) ? $this->dataPost['fromdate'] : 0;
		$todate = !empty($this->dataPost['todate']) ? $this->dataPost['todate'] : 0;
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;

		$condition = [];
		if ($fromdate > 0 && $todate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate
			];
		}
		if ($todate > 0 && $fromdate == 0) {
			$condition['ngay_thanh_toan'] = [
				'$lte' => (int) $todate
			];
		}
		if ($todate > 0 && $fromdate > 0) {
			$condition['ngay_thanh_toan'] = [
				'$gte' => (int) $fromdate,
				'$lte' => (int) $todate
			];
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		// Query
		$data = $this->report_history_transaction_model->find_where($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}
	public function report_log_phieu_thu_post()
	{
	
		
			$ct = $this->contract_model->find_where(array('status'=>array('$gte'=>17)));
		$data=[];
		$tong_chia=0;
		$tong_thu=0;
			foreach ($ct as $key => $value) {
				$tong_thu=0;
				$tran = $this->transaction_model->find_where(array('status'=>1,'type'=>['$in'=>[3,4]],'code_contract'=>$value['code_contract']));
				$count_tt=0;
				$tien_thua_ct=0;
				  $date_pay_last=0;
          $total_last=0;
				foreach ($tran as $key1 => $value_tran) {
					if($value_tran['type']==3)
						$count_tt++;
					$fee_reduction = (!empty($value_tran['total_deductible'])) ? (int)$value_tran['total_deductible'] : 0;
					$tong_thu+=$value_tran['total']+$value_tran['total_deductible'];
					if($value_tran['type']==3){
					
				    }else if($value_tran['type']==4){
				    	if($count_tt>0)
				    	{
				    		array_push($data, ['name'=>'HĐ có phiếu thu tất toán < Thanh toán','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Ngày thanh toán của phiếu thu tất toán < thanh toán",'date'=>$value_tran['date_pay']]);
				    		
				    	}
                     
				    }
					
					if(strtotime(date('Y-m-d',$value['disbursement_date']). ' 00:00:00')>strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00'))
					{
						array_push($data, ['name'=>'Ngày thanh toán < ngày giải ngân','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Ngày thanh toán phiếu thu < ngày giải ngân của hợp đồng",'date'=>$value_tran['date_pay']]);
                  
						}
                $ct_origin = $this->contract_model->findOne(array('code_contract'=>$value['code_contract_parent_gh']));
					if(!empty($ct_origin) && !empty($value['code_contract_parent_gh']))
					{
						if($value['status']==33 && $value_tran['so_tien_goc_da_tra']>0)
						{
							array_push($data, ['name'=>'Gia hạn lỗi','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Gia hạn có chia vào gốc",'date'=>$value_tran['date_pay']]);
                     
                       }
					}

					if($value_tran['type']==4 && $date_pay_last>0 && $note_last == $value_tran['note'] && $total_last==$value_tran['total'] && $value_tran['created_by']=="system")
					{
						array_push($data, ['name'=>'Trùng phiếu thu duyệt tự động','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Duyệt tự động 2 phiếu thu trùng nhau",'date'=>$value_tran['date_pay']]);
					   
					}
          $note_last=$value_tran['note'];
           $total_last=$value_tran['total'];
          
					if($value_tran['type']==3 && $value['status']!=19)
					{
						array_push($data, ['name'=>'HĐ chưa chuyển TT tất toán','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Hợp đồng có phiếu thu tất toán mà trạng thái hợp đồng không phải là tất toán",'date'=>$value_tran['date_pay']]);
						
					}
					if($value_tran['type_payment']==2 && $value['status']!=33)
					{
						array_push($data, ['name'=>'HĐ chưa chuyển TT gia hạn','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Hợp đồng có phiếu thu gia hạn mà trạng thái hợp đồng không phải là gia hạn",'date'=>$value_tran['date_pay']]);
						
					}
					if($value_tran['type_payment']==3 && $value['status']!=34)
					{
						array_push($data, ['name'=>'HĐ chưa chuyển TT cơ cấu','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Hợp đồng có phiếu thu cơ cấu mà trạng thái hợp đồng không phải là cơ cấu",'date'=>$value_tran['date_pay']]);
						
					}
					if(strpos(strtolower($value_tran['note']),'gia h') && is_string($value_tran['note']) && ($value_tran['type']==3 || $value_tran['type']==4) && $value_tran['status']==1 && !isset($value['type_gh']) &&  $value['status']!=33 &&  $value['status']!=19 )
					{
							
                         array_push($data, ['name'=>'HĐ nghi ngờ gia hạn cơ cấu','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Trong note của phiếu thu có chữ gia hạn hoặc cơ cấu",'date'=>$value_tran['date_pay']]);
						

					}
					if($value_tran['total_deductible']!=($value_tran['discounted_fee']+$value_tran['other_fee']+$value_tran['reduced_fee']) )
					{
							
                         array_push($data, ['name'=>'Tổng miễn giảm khác miễn giảm thành phần','code_contract'=>$value['code_contract'],'code'=>$value_tran['code'],'type'=>$value_tran['type'],'note'=>"Miễn giảm tổng ".$value_tran['total_deductible']." khác ".($value_tran['discounted_fee']+$value_tran['other_fee'])." (".$value_tran['discounted_fee']." phí giảm trừ + ".$value_tran['other_fee']." phí khác) ".$value_tran['reduced_fee']."  phí ngân hàng",'date'=>$value_tran['date_pay']]);
						

					}
					if($value_tran['type']==3 )
					{
					$tong_phai_tra=round($value_tran['phai_tra_hop_dong']['so_tien_goc_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['so_tien_lai_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['so_tien_phi_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_gia_han_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_cham_tra_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong'] );
				    }
                

				}
			    if($value['status']==19 &&  $tong_thu<$tong_phai_tra)
			    {
			    	 array_push($data, ['name'=>'Tất toán thiếu','code_contract'=>$value['code_contract'],'code'=>'','type'=>'','note'=>"Tổng thu hợp đồng ". $tong_thu." < ".$tong_phai_tra." tổng phải trả hợp đồng",'date'=>$value['created_at']]);
			    }
				
				  if(($value['code_contract_parent_gh']!="" || $value['code_contract_parent_cc']!="") &&  $value['loan_infor']['amount_money']==0)
           {
           	 array_push($data, ['name'=>'Hợp đồng GH/CC tiền vay =0','code_contract'=>$value['code_contract'],'code'=>'','type'=>'','note'=>"Số tiền vay trong hợp đồng gia hạn =0 => gia hạn trước đó đã tất toán",'date'=>$value['created_at']]);
            
           }
			    

				if($count_tt>1)
				{
					 array_push($data, ['name'=>'Hợp đồng 2 PT tất toán','code_contract'=>$value['code_contract'],'code'=>'','type'=>'','note'=>"Hợp đồng có 2 phiếu thu tất toán",'date'=>$value['created_at']]);
					
				}
			  
			}
			$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
			
		}


	public function report_real_revenue_count_all_post() {
		$thang_bao_cao = !empty($this->dataPost['thang_bao_cao']) ? $this->dataPost['thang_bao_cao'] : mktime(0,0,0,date('n')-1,1,date('Y'));
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($thang_bao_cao != '') {
			$condition['thang_bao_cao'] = (int) $thang_bao_cao;
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}

		$data = $this->report_real_revenue_model->count($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_real_revenue_all_post() {
		$thang_bao_cao = !empty($this->dataPost['thang_bao_cao']) ? $this->dataPost['thang_bao_cao'] : mktime(0,0,0,date('n')-1,1,date('Y'));
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($thang_bao_cao != '') {
			$condition['thang_bao_cao'] = (int) $thang_bao_cao;
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}

		// Query
		$data = $this->report_real_revenue_model->find_where($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function report_real_revenue_post() {
		$thang_bao_cao = !empty($this->dataPost['thang_bao_cao']) ? $this->dataPost['thang_bao_cao'] : mktime(0,0,0,date('n')-1,1,date('Y'));
		$ma_phieu_ghi = !empty($this->dataPost['ma_phieu_ghi']) ? $this->dataPost['ma_phieu_ghi'] : 0;
		$ma_hop_dong = !empty($this->dataPost['ma_hop_dong']) ? $this->dataPost['ma_hop_dong'] : 0;
		$ten_khach_hang = !empty($this->dataPost['ten_khach_hang']) ? $this->dataPost['ten_khach_hang'] : 0;
		$hinh_thuc_vay = !empty($this->dataPost['hinh_thuc_vay']) ? $this->dataPost['hinh_thuc_vay'] : 0;
		$phong_giao_dich = !empty($this->dataPost['phong_giao_dich']) ? $this->dataPost['phong_giao_dich'] : 0;

		$condition = [];
		if ($thang_bao_cao != '') {
			$condition['thang_bao_cao'] = (int) $thang_bao_cao;
		}
		if ($ma_phieu_ghi != '') {
			$condition['ma_phieu_ghi'] = $ma_phieu_ghi;
		}
		if ($ma_hop_dong != '') {
			$condition['ma_hop_dong'] = $ma_hop_dong;
		}
		if ($ten_khach_hang != '') {
			$condition['ten_nguoi_vay'] = $ten_khach_hang;
		}
		if ($phong_giao_dich != '') {
			$condition['store.id'] = $phong_giao_dich;
		}
		if ($hinh_thuc_vay != '') {
			$condition['hinh_thuc_tra_lai'] = $hinh_thuc_vay;
		}
		// Query
		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->report_real_revenue_model->find($condition, $per_page, $uriSegment);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

}

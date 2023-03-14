<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class ExportExcel extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('contract_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_hs_model');
		$this->load->model('group_role_model');
		$this->load->model('store_model');
		$this->load->model('log_contract_tempo_model');
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
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;


	public function selectExcelExport_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$contract = $this->contract_model->find_select_export();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function get_user_hs_post()
	{
		$data = [];
		$role = $this->role_model->findOne(['slug' => 'hoi-so']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_user_kt_post()
	{
		$data = [];
		$role = $this->role_model->findOne(['slug' => 'ke-toan']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_user_cskh_post()
	{
		$data = [];
		$role = $this->role_model->findOne(['slug' => 'cham-soc-khach-hang']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_user_asm_post()
	{
		$data = [];
		$data1 = [];
		$data2 = [];
		$role = $this->role_model->findOne(['slug' => 'qlkv-mien-tay']);
		$role1 = $this->role_model->findOne(['slug' => 'qlkv-mien-nam']);
		$role2 = $this->role_model->findOne(['slug' => 'qlkv-mien-bac']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		foreach ($role1['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data1, $i);
				}
			}
		}
		foreach ($role2['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data2, $i);
				}
			}
		}

		$result = array_merge($data, $data1, $data2);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function get_user_cht()
	{
		$data = [];
		$role = $this->role_model->findOne(['slug' => 'cua-hang-truong']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		return $data;
	}


	public function export_report_hs_day_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = array();
		$data = $this->input->post();


		$day_check = strtotime('-15 day', strtotime(date('Y-m-d')));

		$start = !empty($data['start']) ? date($data['start'], $day_check) : date('Y-m-d', $day_check);
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d');

		$change_time = !empty($data['change_time']) ? $data['change_time'] : "";
		$stores_ad = !empty($data['stores_ad']) ? $data['stores_ad'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";


		if (!empty($data['customer_form_hs'])) {
			$customer_form_hs = $data['customer_form_hs'];
		}
//		if ($data['customer_form_hs'] == ""){
//			unset($customer_form_hs);
//		}

//		$flag1 = [];
//		$flag2 = [];
//		$flag3 = [];
//		$stores = $this->store_model->find();
//
//		foreach ($stores as $store){
//			if ($store->code_province_store == "KV_HN1" && $store->code_province_store == "KV_HN2"){
//				array_push($flag1,$store->name);
//			}
//			if ($store->code_province_store == "KV_HCM1" && $store->code_province_store == "KV_HCM2"){
//				array_push($flag2,$store->name);
//			}
//			if ($store->code_province_store == "KV_MK" ){
//				array_push($flag3,$store->name);
//			}
//		}

		if ($area == "Hà Nội") {
			$flag1 = ["246 La Thành", "79 Tây Đằng", "71 Lê Thanh Nghị", "494 Trần Cung", "26 Vạn Phúc", "264 Xã Đàn", "28 Phan Huy Ích", "44 Lĩnh Nam", "01 Mỹ Đình", "48 La Thành", "310 Phan Trọng Tuệ", "30 Nguyễn Thái Học", "81 Nguyễn Trãi", "518 Xã Đàn", "79 Hưng Đạo", "281 Ngô Gia Tự", "901 Giải Phóng"];
		}
		if ($area == "Tp Hồ Chí Minh") {
			$flag2 = ["316 Nguyễn Sơn", "550 Nguyễn Văn Khối", "138 Phan Đăng Lưu", "286 Bình Tiên", "267 Âu Cơ", "131 Hiệp Bình", "412 Cách Mạng Tháng 8", "81 Liêu Bình Hương", "28 Đỗ Xuân Hợp", "246 Nguyễn An Ninh", "133 Lê Văn Việt", "662 Lê Văn Khương", "2/1A Phan Văn Hớn", "63 Đường 26 tháng 3"];
		}
		if ($area == "Mekong") {
			$flag3 = ["63 Đường 26 tháng 3", "1797 Trần Hưng Đạo", "308 Đường 30/4"];
		}


		if (!empty($stores_ad)) {
			$condition['stores_ad'] = $stores_ad;
		}


		if ($change_time == "Từ ngày đến ngày") {
			$day = strtotime('-1 day', strtotime(date('Y-m-d')));
			$start = date('Y-m-d', $day);
			$end = date('Y-m-d');
		}
		if ($change_time == "Ngày hôm nay") {
			$start = date('Y-m-d');
			$end = date('Y-m-d');
		}
		if ($change_time == "Tuần") {
			$week = strtotime('-9 day', strtotime(date('Y-m-d')));
			$start = date('Y-m-d', $week);
			$end = date('Y-m-d');
		}
		if ($change_time == "Tháng") {
			$month = strtotime('-1 month', strtotime(date('Y-m-d')));
			$start = date('Y-m-d', $month);
			$end = date('Y-m-d');
		}
		if ($change_time == "Quý") {
			$precious = strtotime('-3 month', strtotime(date('Y-m-d')));
			$start = date('Y-m-d', $precious);
			$end = date('Y-m-d');
		}
		if ($change_time == "Năm") {
			$year = strtotime('-12 month', strtotime(date('Y-m-d')));
			$start = date('Y-m-d', $year);
			$end = date('Y-m-d');
		}


		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'),
			);
		}

//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$list_code_contract = $this->contract_model->find_select_log($condition);

		$arr = [];
		$code_contract = [];
		foreach ($list_code_contract as $key => $item) {
			array_push($code_contract, $item['code_contract']);
		}

//		$code_contract = ['000007445','000007446','000007447','000007448','000007449','000007450','000007452','000007455','000007456','000007457','000007458','000007459','000007464','000007467','000007468','000007469'];

		for ($i = 0; $i < count($code_contract); $i++) {
			if (!empty($code_contract[$i])) {

				$data_log = $this->log_hs_model->find_where_in($code_contract[$i]);

				for ($j = 0; $j < count($data_log); $j++) {
					if (!empty($flag1)) {
						if ((in_array($data_log[$j]['old']['store']['name'], $flag1)) != true) {
							break;
						}
					}
					if (!empty($flag2)) {
						if ((in_array($data_log[$j]['old']['store']['name'], $flag2)) != true) {
							break;
						}
					}
					if (!empty($flag3)) {
						if ((in_array($data_log[$j]['old']['store']['name'], $flag3)) != true) {
							break;
						}
					}

					$result['email'] = !empty($data_log[$j]['created_by']) ? $data_log[$j]['created_by'] : "";


					if (!empty($customer_form_hs)) {
						$result['customer_form_hs'] = $customer_form_hs;
					}
					if (!empty($stores_ad)) {
						$result['stores_ad'] = $stores_ad;
					}

					$result['code_contract'] = !empty($data_log[$j]['old']['code_contract']) ? $data_log[$j]['old']['code_contract'] : "";
					$result['pgd'] = !empty($data_log[$j]['old']['store']['name']) ? $data_log[$j]['old']['store']['name'] : "";
					$result['customer_name'] = !empty($data_log[$j]['old']['customer_infor']['customer_name']) ? $data_log[$j]['old']['customer_infor']['customer_name'] : "";
					$result['customer_identify'] = !empty($data_log[$j]['old']['customer_infor']['customer_identify']) ? $data_log[$j]['old']['customer_infor']['customer_identify'] : "";
					$result['loan_product'] = !empty($data_log[$j]['old']['loan_infor']['loan_product']['text']) ? $data_log[$j]['old']['loan_infor']['loan_product']['text'] : "";
					$result['amount_money'] = !empty($data_log[$j]['old']['loan_infor']['amount_money']) ? $data_log[$j]['old']['loan_infor']['amount_money'] : "";
					$result['count_return'] = $this->log_hs_model->find_where_in_count($code_contract[$i]);

					$result['status'] = !empty($data_log[$j]['old']['status']) ? $data_log[$j]['old']['status'] : "";
					$result['status_new'] = !empty($data_log[$j]['new']['status']) ? $data_log[$j]['new']['status'] : "";
					$result['created_at'] = !empty($data_log[$j]['created_at']) ? $data_log[$j]['created_at'] : "";
					$result['contract_id'] = !empty($data_log[$j]['contract_id']) ? $data_log[$j]['contract_id'] : "";

					$result['exception1_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception1_value']) ? $data_log[$j]['old']['expertise_infor']['exception1_value'] : "";
					$result['exception2_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception2_value']) ? $data_log[$j]['old']['expertise_infor']['exception2_value'] : "";
					$result['exception3_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception3_value']) ? $data_log[$j]['old']['expertise_infor']['exception3_value'] : "";
					$result['exception4_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception4_value']) ? $data_log[$j]['old']['expertise_infor']['exception4_value'] : "";
					$result['exception5_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception5_value']) ? $data_log[$j]['old']['expertise_infor']['exception5_value'] : "";
					$result['exception6_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception6_value']) ? $data_log[$j]['old']['expertise_infor']['exception6_value'] : "";
					$result['exception7_value'] = !empty($data_log[$j]['old']['expertise_infor']['exception7_value']) ? $data_log[$j]['old']['expertise_infor']['exception7_value'] : "";


					$result['error_code'] = !empty($data_log[$j]['new']['error_code']) ? $data_log[$j]['new']['error_code'] : "";
					$result['reason'] = !empty($data_log[$j]['new']['reason']) ? $data_log[$j]['new']['reason'] : "";
					$result['lead_cancel1_C1'] = !empty($data_log[$j]['new']['lead_cancel1_C1']) ? $data_log[$j]['new']['lead_cancel1_C1'] : "";
					$result['lead_cancel1_C2'] = !empty($data_log[$j]['new']['lead_cancel1_C2']) ? $data_log[$j]['new']['lead_cancel1_C2'] : "";
					$result['lead_cancel1_C3'] = !empty($data_log[$j]['new']['lead_cancel1_C3']) ? $data_log[$j]['new']['lead_cancel1_C3'] : "";
					$result['lead_cancel1_C4'] = !empty($data_log[$j]['new']['lead_cancel1_C4']) ? $data_log[$j]['new']['lead_cancel1_C4'] : "";
					$result['lead_cancel1_C5'] = !empty($data_log[$j]['new']['lead_cancel1_C5']) ? $data_log[$j]['new']['lead_cancel1_C5'] : "";
					$result['lead_cancel1_C6'] = !empty($data_log[$j]['new']['lead_cancel1_C6']) ? $data_log[$j]['new']['lead_cancel1_C6'] : "";
					$result['lead_cancel1_C7'] = !empty($data_log[$j]['new']['lead_cancel1_C7']) ? $data_log[$j]['new']['lead_cancel1_C7'] : "";

					$result['new_amount_loan'] = !empty($data_log[$j]['new']['amount_loan']) ? $data_log[$j]['new']['amount_loan'] : "";

					$result['exception1_value_detail'] = !empty($data_log[$j]['new']['exception1_value_detail']) ? $data_log[$j]['new']['exception1_value_detail'] : "";
					$result['exception2_value_detail'] = !empty($data_log[$j]['new']['exception2_value_detail']) ? $data_log[$j]['new']['exception2_value_detail'] : "";
					$result['exception3_value_detail'] = !empty($data_log[$j]['new']['exception3_value_detail']) ? $data_log[$j]['new']['exception3_value_detail'] : "";
					$result['exception4_value_detail'] = !empty($data_log[$j]['new']['exception4_value_detail']) ? $data_log[$j]['new']['exception4_value_detail'] : "";
					$result['exception5_value_detail'] = !empty($data_log[$j]['new']['exception5_value_detail']) ? $data_log[$j]['new']['exception5_value_detail'] : "";
					$result['exception6_value_detail'] = !empty($data_log[$j]['new']['exception6_value_detail']) ? $data_log[$j]['new']['exception6_value_detail'] : "";
					$result['exception7_value_detail'] = !empty($data_log[$j]['new']['exception7_value_detail']) ? $data_log[$j]['new']['exception7_value_detail'] : "";

					array_push($arr, $result);
				}

			}

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function getGroupRole_asm_post()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'quan-ly-khu-vuc'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {
				foreach ($groupRole['users'][0] as $value) {
					array_push($arr, $value->email);
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function export_kt_post()
	{

		$condition = array();
		$data = $this->input->post();

//		$day_check = strtotime('-10 day', strtotime(date('Y-m-d')));

		$start = !empty($data['start']) ? $data['start'] : date('Y-m-d');
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d');
		if (!empty($data['customer_form_hs'])) {
			$customer_form_hs = $data['customer_form_hs'];
		}

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'),
			);
		}

		$list_code_contract = $this->contract_model->find_select_log_kt($condition);

		$arr = [];
		$code_contract = [];
		foreach ($list_code_contract as $key => $item) {
			array_push($code_contract, $item['code_contract']);
		}

//		$code_contract = ["000007785","000007786","000007787","000007788","000007789","000007790","000007791","000007792","000007793","000007794","000007795","000007796",
//			"000007797","000007798","000007799","000007800","000007801","000007802","000007803","000007804","000007805","000007806","000007807","000007808","000007809",
//			"000007810","000007811","000007812","000007813","000007814","000007815","000007816","000007817","000007818","000007819","000007820","000007824","000007826","000007827",
//			"000007828","000007829","000007830","000007831","000007832","000007825","000007833","000007835","000007836","000007838","000007839","000007840","000007837","000007834",
//			"000007841","000007842","000007843","000007844","000007845","000007846","000007848","000007849","000007850","000007851","000007852","000007853","000007854","000007855",
//			"000007856","000007857","000007858","000007859","000007860","000007847","000007861","000007862","000007863","000007864","000007865","000007866","000007867","000007868",
//			"000007869","000007870","000007871","000007872","000007873","000007874","000007875","000007876","000007877","000007878","000007879","000007880","000007881","000007882",
//			"000007884","000007885","000007886","000007887","000007883","000007888","000007889","000007890","000007891","000007892","000007893","000007894","000007895","000007896",
//			"000007897","000007898","000007899","000007900","000007902","000007903","000007904","000007901","000007906","000007905","000007907","000007908",
//
//			"000007909","000007910","000007911","000007912","000007913","000007914","000007915","000007916","000007917","000007918","000007919","000007920","000007921","000007922",
//			"000007923","000007924","000007925","000007926","000007927","000007928","000007929","000007930","000007931","000007932","000007933","000007934","000007935","000007936",
//			"000007937","000007938","000007939","000007940","000007941","000007942","000007943","000007944","000007945","000007946","000007947","000007948","000007949","000007950",
//			"000007951","000007952","000007953","000007954","000007955","000007956","000007957","000007958","000007959","000007960","000007961","000007962","000007963","000007964",
//			"000007965","000007966","000007967","000007968","000007969","000007970","000007971","000007972","000007973","000007975","000007976","000007974","000007977","000007978",
//			"000007979","000007980","000007981","000007982","000007983","000007984","000007985","000007986","000007987","000007988","000007989","000007990","000007991","000007992",
//			"000007993","000007994",
//
//			"000007753","000007755","000007756","000007757","000007758","000007759","000007745","000007760","000007670","000007754","000007761","000007681","000007762","000007763",
//			"000007764","000007765","000007766","000007767","000007768","000007769","000007770","000007771","000007772","000007773","000007774","000007775","000007777","000007778",
//			"000007779","000007780","000007781","000007782","000007783","000007784","000007776"
//			];
//		$code_contract = [
//			"0000010401", "0000010399" , "0000010402"
//			];

		$list_kt = $this->get_user_kt();

		for ($i = 0; $i < count($code_contract); $i++) {

			if (!empty($code_contract[$i])) {

				$data_log = $this->log_hs_model->find_where_in_kt($code_contract[$i]);

				for ($j = 0; $j < count($data_log); $j++) {

					$result['email'] = !empty($data_log[$j]['created_by']) ? $data_log[$j]['created_by'] : "";

					$result['code_contract'] = !empty($data_log[$j]['old']['code_contract']) ? $data_log[$j]['old']['code_contract'] : "";
					$result['pgd'] = !empty($data_log[$j]['old']['store']['name']) ? $data_log[$j]['old']['store']['name'] : "";
					$result['customer_name'] = !empty($data_log[$j]['old']['customer_infor']['customer_name']) ? $data_log[$j]['old']['customer_infor']['customer_name'] : "";
					$result['amount_loan'] = !empty($data_log[$j]['old']['loan_infor']['amount_loan']) ? $data_log[$j]['old']['loan_infor']['amount_loan'] : "";
					$result['count_return'] = $this->log_hs_model->find_where_in_count_kt($code_contract[$i]);

					$result['status'] = !empty($data_log[$j]['old']['status']) ? $data_log[$j]['old']['status'] : "";
					$result['status_new'] = !empty($data_log[$j]['new']['status']) ? $data_log[$j]['new']['status'] : "";
					$result['created_at'] = !empty($data_log[$j]['created_at']) ? $data_log[$j]['created_at'] : "";
					if (!empty($customer_form_hs)) {
						$result['customer_form_hs'] = $customer_form_hs;
					}

					$result['note'] = !empty($data_log[$j]['new']['note']) ? $data_log[$j]['new']['note'] : "";
					$result['new_amount_loan'] = !empty($data_log[$j]['new']['amount_loan']) ? $data_log[$j]['new']['amount_loan'] : "";

					if ($result['status_new'] == 3 && !in_array($result['email'], $list_kt)) {
						continue;
					}

					array_push($arr, $result);
				}
//
//				$check_gn = $this->log_model->findOne(array("old.code_contract" => $code_contract[$i], 'new.status' => 17));
//				if (!empty($check_gn)){
//					$result['email'] = !empty($check_gn['created_by']) ? $check_gn['created_by'] : "";
//
//					$result['code_contract'] = !empty($check_gn['old']['code_contract']) ? $check_gn['old']['code_contract'] : "";
//					$result['pgd'] = !empty($check_gn['old']['store']['name']) ? $check_gn['old']['store']['name'] : "";
//					$result['customer_name'] = !empty($check_gn['old']['customer_infor']['customer_name']) ? $check_gn['old']['customer_infor']['customer_name'] : "";
//					$result['amount_loan'] = !empty($check_gn['old']['loan_infor']['amount_loan']) ? $check_gn['old']['loan_infor']['amount_loan'] : "";
//					$result['count_return'] = $this->log_hs_model->find_where_in_count_kt($code_contract[$i]);
//
//					$result['status'] = !empty($check_gn['old']['status']) ? $check_gn['old']['status'] : "";
//					$result['status_new'] = !empty($check_gn['new']['status']) ? $check_gn['new']['status'] : "";
//					$result['created_at'] = !empty($check_gn['created_at']) ? $check_gn['created_at'] : "";
//					if (!empty($customer_form_hs)){
//						$result['customer_form_hs'] = $customer_form_hs;
//					}
//
//					$result['note'] = !empty($check_gn['new']['note']) ? $check_gn['new']['note'] : "";
//					$result['new_amount_loan'] = !empty($check_gn['new']['amount_loan']) ? $check_gn['new']['amount_loan'] : "";
//					array_push($arr, $result);
//				}
//				unset($check_gn);

			}

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function get_user_kt()
	{
		$data = [];
		$role = $this->role_model->findOne(['slug' => 'ke-toan']);
		foreach ($role['users'] as $user) {
			foreach ($user as $k => $v) {
				foreach ($v as $i) {
					array_push($data, $i);
				}
			}
		}
		return $data;
	}


}

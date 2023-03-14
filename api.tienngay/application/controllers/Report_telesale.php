<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Report_telesale extends REST_Controller
{
	public function __construct()
	{

		parent::__construct();
		$this->load->model("lead_model");
		$this->load->model("dashboard_model");
		$this->load->model('contract_model');
		$this->load->model('log_lead_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model("store_model");
		$this->load->model("group_role_model");
		$this->load->model('district_model');
		$this->load->model('ward_model');
		$this->load->helper('lead_helper');
		$this->load->model('province_model');
		$this->load->model('recording_model');
		$this->load->model("landing_page_model");
		$this->load->model("lead_extra_model");
		$this->load->model('cskh_del_model');
		$this->load->model('cskh_insert_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_accesstrade_model');
		$this->load->model("notification_model");
		$this->load->model("order_model");
		$this->load->model("area_model");
		$this->load->model("createkpi_telesale_model");
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
					// "is_superadmin" => 1
				);
				//Web
				if ($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				unset($this->dataPost['type']);
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}


	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;



	public function get_all_nangsuatlaodong_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = [];

		$this->dataPost = $this->input->post();
		$telesale = !empty($this->dataPost['telesale']) ? $this->dataPost['telesale'] : "";

		$list_telesale = $this->getGroupRole_telesale();

		if ($telesale != "") {
			$list_telesale = array($telesale);
		}

		$day = (int)date('d');
		$ngay_truoc = $day - 1;
		$ngay_bat_dau = '2018-11-01';
		$condition_old = [];
		$condition = [];

			$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-$ngay_truoc 17:30:00");
			$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d H:i:s");

			if (!empty($fdate)) {
				$condition['fdate'] = strtotime(trim($fdate));
			}
			if (!empty($tdate)) {
				$condition['tdate'] = strtotime(trim($tdate));
			}

		for ($i = 0; $i < count($list_telesale); $i++) {

			$condition["cskh"] = $list_telesale[$i];

			$count_hd_giaingan = 0;
			$count_lead_ton_cu = 0;
			$count_lead_xu_ly_ton_cu = 0;
			$count_total_lead_phan_cong = 0;
			$count_total_lead_xu_ly = 0;
			$total_lead_money = 0;
			$avg_sum_ti_le_xuly = 0;
			$avg_sum_ti_le_convert = 0;
			//nhan vien
			$data[$i]["nhanvien"] = $list_telesale[$i];

			//Lead phân công

			$count_lead = $this->lead_model->find_where_count($condition);
			if (!empty($count_lead)) {
				$data[$i]["lead_phan_cong"] = $count_lead;
			} else {
				$data[$i]["lead_phan_cong"] = 0;
			}

			//lead phan cong ton cu
			$condition_old['cskh'] = $list_telesale[$i];
			if(empty($this->dataPost['fdate']) && empty($this->dataPost['tdate'])) {
			$condition_old['fdate'] = strtotime(trim($ngay_bat_dau));
			$condition_old['tdate'] = strtotime(trim(date("y-m-d H:i:s")));
			}else {
			$condition_old['fdate'] = strtotime(trim($this->dataPost['fdate']));
			$condition_old['tdate'] = strtotime(trim($this->dataPost['tdate']));
			}

//			$count_lead_ton_cu = $this->lead_model->find_where_count_phan_bo_ton_cu($condition_old);
//			if (!empty($count_lead_ton_cu)) {
//				$data[$i]["lead_phan_cong_ton_cu"] = $count_lead_ton_cu;
//			} else {
//				$data[$i]["lead_phan_cong_ton_cu"] = 0;
//			}

			//tong lead phan cong
			$count_tong_lead_phan_cong = $count_lead + $count_lead_ton_cu;
			$data[$i]['tong_lead_phan_cong'] = $count_tong_lead_phan_cong;

			//Lead đang xử lý
			$count_lead_xu_ly = $this->lead_model->find_where_count_xu_ly($condition);
			if (!empty($count_lead_xu_ly)) {
				$data[$i]["lead_xu_ly"] = $count_lead_xu_ly;
			} else {
				$data[$i]["lead_xu_ly"] = 0;
			}

			//lead xu ly ton cu
//			$count_lead_xu_ly_ton_cu = $this->lead_model->find_where_count_xu_ly_ton_cu($condition_old);
//			if (!empty($count_lead_xu_ly_ton_cu)) {
//				$data[$i]["lead_xu_ly_ton_cu"] = $count_lead_xu_ly_ton_cu;
//			} else {
//				$data[$i]["lead_xu_ly_ton_cu"] = 0;
//			}

			//tong lead xu ly
			$count_tong_lead_xu_ly = $count_lead_xu_ly + $count_lead_xu_ly_ton_cu;
			$data[$i]['tong_lead_xu_ly'] = $count_tong_lead_xu_ly;

			//Lead QLF
			$count_lead_qlf = $this->lead_model->find_where_leadQlf_count($condition);
			if (!empty($count_lead_qlf)) {
				$data[$i]["lead_qlf"] = $count_lead_qlf;
			} else {
				$data[$i]["lead_qlf"] = 0;
			}
			// ti le xu ly
			$sum_ti_le_xuly = 0;
			if (!empty($count_lead_xu_ly) && !empty($count_lead) && $count_lead != 0) {
				$ti_le_xu_ly = number_format((($count_lead_xu_ly / $count_lead) * 100), 2) . "%";
				$data[$i]['ti_le_xu_ly'] = $ti_le_xu_ly;
			}

			//hd giải ngân
			$lead = $this->lead_model->get_lead_report($condition);
			if (!empty($lead)) {
				foreach ($lead as $item) {
					$contract = $this->contract_model->findOne(array('customer_infor.customer_phone_number' => $item['phone_number']));
					if (!empty($contract)) {
						if (($contract['status'] >= 17 && $contract['status'] < 35) || $contract['status'] >= 37) {
							$count_hd_giaingan++;
						}
					}
				}
			}
			$data[$i]["count_hd_giaingan"] = $count_hd_giaingan;

			//ti le convert
			$ti_le_convert = 0;
			$sum_ti_le_convert = 0;
			if (!empty($count_hd_giaingan) && !empty($count_lead_qlf) && $count_lead_qlf != 0) {
				$ti_le_convert = number_format((($count_hd_giaingan / $count_lead_qlf) * 100), 2) . "%";
				$data[$i]['ti_le_convert'] = $ti_le_convert;
			}
			//ti le qlf
			$ti_le_qlf = 0;
			if (!empty($count_lead) && !empty($count_lead_qlf) && $count_lead != 0) {
				$ti_le_qlf = number_format((($count_lead_qlf / $count_lead) * 100), 2) . "%";
				$data[$i]['ti_le_qlf'] = $ti_le_qlf;
			}

//			//tổng tiền gn
			$arr = [];
			$lead = $this->lead_model->get_lead_report($condition);
			if (!empty($lead)) {
				foreach ($lead as $item) {
					$contract = $this->contract_model->findOne(array(
						'customer_infor.customer_phone_number' => $item['phone_number']
					));
					if (!empty($contract)) {
						if (($contract['status'] >= 17 && $contract['status'] < 35) || $contract['status'] >= 37) {
							$total_lead_money += $contract['loan_infor']['amount_money'];
						}
					}
				}
				$data[$i]['total_tien_giaingan'] = $total_lead_money;
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'list_telesale' => $list_telesale
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function getGroupRole_telesale()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'telesales'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						if (!in_array($item['email'], $arr)) {
							array_push($arr, $item['email']);

						}

					}

				}
			}
		}
		return array_unique($arr);
	}

	public function getGroupRole_telesale_post()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'telesales'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						if (!in_array($item['email'], $arr)) {
							array_push($arr, $item['email']);

						}

					}

				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => array_unique($arr)

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_tilechuyendoi_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = [];
		$this->dataPost = $this->input->post();

		$area = !empty($this->dataPost['area']) ? trim($this->dataPost['area']) : "";
		$store = !empty($this->dataPost['store']) ? trim($this->dataPost['store']) : "";

		$stores_all = $this->store_model->find_where_order_by(array('status' => 'active'));

		if ($area != "") {
			$stores = [];
			foreach ($stores_all as $item) {
				if ($area == $item["code_area"]) {
					array_push($stores, $item);
				}
			}
		}
		if ($store != "") {
			$stores = [];

			foreach ($stores_all as $item) {
				if ($store == $item['name']) {
					array_push($stores, $item);
				}
			}
		}

		if ($area == "" && $store == "") {
			$stores = $stores_all;
		}
		for ($i = 0; $i < count($stores); $i++) {
			$condition = [];
			$count_hd_giaingan = 0;

			$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-d 00:00:00");
			$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d H:i:s");

			if (!empty($fdate)) {
				$condition['fdate'] = strtotime(trim($fdate));
			}
			if (!empty($tdate)) {
				$condition['tdate'] = strtotime(trim($tdate));
			}

			//
			$data[$i]["name"] = $stores[$i]['name'];
			$data[$i]["code_area"] = $this->name_code_area($stores[$i]['code_area']);

			//Lead QLF
			$condition['id_PDG'] = (string)$stores[$i]["_id"];
			$count_pgd = $this->lead_model->find_where_lead_count_pgd($condition);
			if (!empty($count_pgd)) {
				$data[$i]["lead_qlf"] = $count_pgd;
			}

			//HĐ giải ngân
			$lead_pgd = $this->lead_model->find_where_lead_pgd($condition);
			if (!empty($lead_pgd)) {
				foreach ($lead_pgd as $item) {
					$contract = $this->contract_model->findOne(array('customer_infor.customer_phone_number' => $item['phone_number']));
					if (!empty($contract)) {
						if (($contract['status'] >= 17 && $contract['status'] < 35) || $contract['status'] >= 37) {
							$count_hd_giaingan++;
						}

					}
				}
			}
			$data[$i]["count_hd_giaingan"] = $count_hd_giaingan;

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function name_code_area($item)
	{
		$code_area = "";
		if ($item == "KV_HN1") {
			$code_area = "Hà Nội 1";
		} elseif ($item == "KV_HN2") {
			$code_area = "Hà Nội 2";
		} elseif ($item == "KV_HCM1") {
			$code_area = "Hồ Chí Minh 1";
		} elseif ($item == "KV_HCM2") {
			$code_area = "Hồ Chí Minh 2";
		} elseif ($item == "KV_QN") {
			$code_area = "Quảng Ninh";
		} elseif ($item == "KV_MK") {
			$code_area = "MeKong";
		} elseif ($item == "KV_MT1") {
			$code_area = "Thanh Hóa";
		} elseif ($item == "Priority") {
			$code_area = "Priority";
		}

		return $code_area;

	}

	public function get_all_baocao_tonghop_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = [];
		$data_gn = [];
		$data_amount = [];
		$this->dataPost = $this->input->post();

		$condition_time = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01 00:00:00");
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d H:i:s");

		if (!empty($fdate)) {
			$condition_time['fdate'] = strtotime(trim($fdate));
		}
		if (!empty($tdate)) {
			$condition_time['tdate'] = strtotime(trim($tdate));
		}

		$lead_ve = $this->lead_model->find_count($condition_time);

		$tls_xl = $this->lead_model->find_count_xl($condition_time);

		//Check lead theo khu vực
//		$stores_all = $this->store_model->find_where_order_by(array('status' => 'active'));

		$area = $this->area_model->find_where_in('status', ['active', 'deactive']);
		$arr = [];
		$arr_title = [];
		foreach ($area as $value) {
			$check = [];
			$check['code'] = $value['code'];
			array_push($arr_title, $value['title']);
			$get_pgd = $this->store_model->where_code_area($check);

			if (empty($get_pgd)) {
				$get_pgd = array();
			}

			$arr += [$value['code'] => $get_pgd];


		}

		foreach ($arr as $key => $item) {
			$count_lead_qlf = 0;
			$count_hd_giaingan = 0;
			$amount = 0;
			foreach ($item as $c) {
				$condition = [];

				$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01 00:00:00");
				$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d H:i:s");

				if (!empty($fdate)) {
					$condition['fdate'] = strtotime(trim($fdate));
				}
				if (!empty($tdate)) {
					$condition['tdate'] = strtotime(trim($tdate));
				}

				$condition["id_PDG"] = (string)$c["_id"];

				$count_pgd = $this->lead_model->find_where_lead_count_pgd($condition);

				$count_lead_qlf += $count_pgd;

				//HĐ giải ngân
				$lead_pgd = $this->lead_model->find_where_lead_pgd($condition);
				if (!empty($lead_pgd)) {
					foreach ($lead_pgd as $item) {
						$contract = $this->contract_model->findOne(array('customer_infor.customer_phone_number' => $item['phone_number']));
						if (!empty($contract)) {
							if (($contract['status'] >= 17 && $contract['status'] < 35) || $contract['status'] >= 37) {
								$count_hd_giaingan++;
								$amount += $contract['loan_infor']['amount_loan'];
							}

						}
					}
				}


			}
			$name_store = $this->area_model->findOne(["code" => $key]);
			$data += [$name_store['title'] => $count_lead_qlf];
			$data_gn += [$name_store['title'] => $count_hd_giaingan];
			$data_amount += [$name_store['title'] => $amount];
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $data,
			'data_gn' => $data_gn,
			"lead_ve" => $lead_ve,
			"tls_xl" => $tls_xl,
			"title" => $arr_title,
			'data_amount' => $data_amount
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function create_kpis_post()
	{
		$this->dataPost = $this->input->post();

		$this->dataPost['amount_money'] = $this->security->xss_clean($this->dataPost['amount_money']);
		$this->dataPost['created_at'] = $this->createdAt;
		$this->createkpi_telesale_model->insert($this->dataPost);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_all_monney_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = [];

		$fdate = date("y-m-01");
		$tdate = date("y-m-d");

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}

		$result = $this->createkpi_telesale_model->find_where_get($condition);

		if (empty($result)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success",
			'data' => $result[0]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function index_reportMkt_post()
	{

		$this->dataPost = $this->input->post();

		$condition = [];
		$condition['status_sale'] = "2";

		$condition_check = [];

		$count_hd_giaingan_accesstrade = 0;
		$price_hd_giaingan_accesstrade = 0;

		$count_hd_giaingan_masoffer = 0;
		$price_hd_giaingan_masoffer = 0;

		$count_hd_giaingan_jeff = 0;
		$price_hd_giaingan_jeff = 0;

		$count_hd_giaingan_toss = 0;
		$price_hd_giaingan_toss = 0;

		$count_hd_giaingan_dinos = 0;
		$price_hd_giaingan_dinos = 0;

		$count_hd_giaingan_crezu = 0;
		$price_hd_giaingan_crezu = 0;


		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date("y-m-01 00:00:00");
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date("y-m-d H:i:s");
		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate));
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate));
		}

		//accesstrade
		$condition['utm_source'] = "accesstrade";
		$accesstrade_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($accesstrade_leadQLF)) {
			foreach ($accesstrade_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_accesstrade++;
							$price_hd_giaingan_accesstrade += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		//masoffer
		$condition['utm_source'] = "masoffer";

		$masoffer_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($masoffer_leadQLF)) {
			foreach ($masoffer_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_masoffer++;
							$price_hd_giaingan_masoffer += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		//jeff
		$condition['utm_source'] = "jeff";

		$jeff_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($jeff_leadQLF)) {
			foreach ($jeff_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_jeff++;
							$price_hd_giaingan_jeff += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		//toss
		$condition['utm_source'] = "Toss";

		$toss_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($toss_leadQLF)) {
			foreach ($toss_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_toss++;
							$price_hd_giaingan_toss += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		//Dinos
		$condition['utm_source'] = "Dinos";

		$dinos_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($dinos_leadQLF)) {
			foreach ($dinos_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_dinos++;
							$price_hd_giaingan_dinos += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		//Dinos
		$condition['utm_source'] = "Crezu";

		$crezu_leadQLF = $this->lead_model->find_leadQLF($condition);

		if (!empty($crezu_leadQLF)) {
			foreach ($crezu_leadQLF as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						if (($contract[0]['status'] >= 17 && $contract[0]['status'] < 35) || $contract[0]['status'] >= 37) {
							$count_hd_giaingan_crezu++;
							$price_hd_giaingan_crezu += $contract[0]['loan_infor']['amount_money'];
						}
					}
				}

			}
		}

		$data = [
			"accesstrade" => [
				"leadQLF" => count($accesstrade_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_accesstrade,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_accesstrade
			],
			"masoffer" => [
				"leadQLF" => count($masoffer_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_masoffer,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_masoffer
			],
			"jeff" => [
				"leadQLF" => count($jeff_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_jeff,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_jeff
			],
			"toss" => [
				"leadQLF" => count($toss_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_toss,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_toss
			],
			"dinos" => [
				"leadQLF" => count($dinos_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_dinos,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_dinos
			],
			"crezu" => [
				"leadQLF" => count($crezu_leadQLF),
				"so_hop_dong_giai_ngan" => $count_hd_giaingan_crezu,
				"tong_so_tien_giai_ngan_hd" => $price_hd_giaingan_crezu
			]
		];

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function index_accesstrade_post()
	{

		$this->dataPost = $this->input->post();

		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$status_sale = !empty($this->dataPost['status_sale']) ? $this->dataPost['status_sale'] : "";
		$utm_source = !empty($this->dataPost['utm_source']) ? $this->dataPost['utm_source'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate));
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate));
		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;
		$data = $this->lead_model->find_lead_mkt($condition, $per_page, $uriSegment);

		if (!empty($data)) {
			foreach ($data as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						$item->status_hd = $contract[0]['status'];
						$item->amount_money = $contract[0]['loan_infor']['amount_money'];
					}


				}
				if (!empty($item['id_PDG'])) {
					$store_name = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($item['id_PDG'])));

					if (!empty($store_name)) {
						$item->store_name = $store_name['name'];
					}
				}
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success",
			'data' => !empty($data) ? $data : []
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function index_accesstrade_count_post()
	{

		$this->dataPost = $this->input->post();

		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$status_sale = !empty($this->dataPost['status_sale']) ? $this->dataPost['status_sale'] : "";
		$utm_source = !empty($this->dataPost['utm_source']) ? $this->dataPost['utm_source'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate));
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate));
		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}

		$count = $this->lead_model->find_lead_mkt_count($condition);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success",
			'data' => !empty($count) ? $count : 0
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function index_accesstrade_excel_post()
	{

		$this->dataPost = $this->input->post();

		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$status_sale = !empty($this->dataPost['status_sale']) ? $this->dataPost['status_sale'] : "";
		$utm_source = !empty($this->dataPost['utm_source']) ? $this->dataPost['utm_source'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate));
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate));
		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}

		$data = $this->lead_model->find_lead_mkt_excel($condition);

		if (!empty($data)) {
			foreach ($data as $item) {
				if (!empty($item['phone_number'])) {
					$condition_check['customer_phone_number'] = $item['phone_number'];
					$contract = $this->contract_model->findOne_where($condition_check);
					if (!empty($contract[0])) {
						$item->status_hd = $contract[0]['status'];
						$item->amount_money = $contract[0]['loan_infor']['amount_money'];
					}
				}
				if (!empty($item['id_PDG'])) {
					$store_name = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($item['id_PDG'])));

					if (!empty($store_name)) {
						$item->store_name = $store_name['name'];
					}
				}
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create success",
			'data' => !empty($data) ? $data : []
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


}

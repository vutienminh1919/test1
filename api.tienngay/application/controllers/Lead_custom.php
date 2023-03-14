<?php
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';
use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
class Lead_custom extends REST_Controller
{
	public function __construct($config = 'rest')
	{
		parent::__construct($config);
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
		$this->load->model("device_model");
		$this->load->model('log_lead_pgd_model');
		$this->load->model('auto_lead_pgd_model');
		$this->load->model('collaborator_model');
		$this->load->model('account_bank_model');
		$this->load->model('transaction_model');
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

	public function get_lead_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";
		$status_sale = !empty($data['status_sale']) ? $data['status_sale'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$phone_number = !empty($data['phone_number']) ? $data['phone_number'] : "";
		$status_pgd = !empty($data['status_pgd']) ? $data['status_pgd'] : "";
		$cvkd = !empty($data['cvkd']) ? $data['cvkd'] : "";
		$source_pgd = !empty($data['source_pgd']) ? $data['source_pgd'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles) ) {
			$all = true;
		}



		$stores = array();
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => $stores,
					'total' => 0,
					'groupRoles' => $groupRoles,
					'stores' => $stores
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			if (in_array('hoi-so', $groupRoles)){
				//61945bd9b5987f1710347a65 - BPD01
				$stores = ["61945bd9b5987f1710347a65"];
			}


			$condition['code_store'] = $stores;

		}
		if (!empty($code_store)) {
			$condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($phone_number)) {
			$condition['phone_number'] = $phone_number;
		}
		if (!empty($status_pgd)) {
			$condition['status_pgd'] = $status_pgd;
		}
		if (!empty($cvkd)) {
			$condition['cvkd'] = trim($cvkd);
		}
		if (!empty($source_pgd)) {
			$condition['source_pgd'] = trim($source_pgd);
		}
		if (!empty($area)) {
			$id_PDG = $this->getStoresbyprovi($area);
			$condition['id_PDG'] = $id_PDG;
		}
		$condition['is_cvkd']=0;

		if((in_array('giao-dich-vien', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)) || (in_array('hoi-so', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)))
		{
            $condition['is_cvkd']=1;
            $condition['cvkd']=$this->uemail;
		}
		//var_dump($condition); die;
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 4000;

		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$leads = $this->lead_model->getByRole_pgd_list($condition, $per_page, $uriSegment);
        $condition['total']=1;
		$leadTotal = $this->lead_model->getByRole_pgd_list($condition);
		foreach ($leadTotal as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}

		$leadTotalMkt = 0;
		for ($i = 0; $i < count($leadTotal); $i++) {
			$leadTotalMkt += (int)$leadTotal[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		foreach ($leads as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}


		$condition['total'] = true;
		$total = $this->lead_model->getByRole_pgd($condition);
		foreach ($leads as $key => $value) {
			$contract = $this->contract_model->findOne(array('customer_infor.id_lead' => (string)$value['_id']));
			if (!empty($contract)) {
				$value['status_contract'] = $contract['status'];
				$value['id_contract'] = (string)$contract['_id'];
			}
		}
		$leadTotalMkt1 = 0;
		for ($i = 0; $i < count($leads); $i++) {
			$leadTotalMkt1 += (int)$leads[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads,
			'total' => $leadTotal,
			'groupRoles' => $groupRoles,
			'stores' => $stores,
			"leadTotalMkt" => $leadTotalMkt,
			"leadTotalMkt1" => $leadTotalMkt1
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_lead_mkt_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";
		$status_sale = !empty($data['status_sale']) ? $data['status_sale'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$phone_number = !empty($data['phone_number']) ? $data['phone_number'] : "";
		$status_pgd = !empty($data['status_pgd']) ? $data['status_pgd'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		}

		if (!empty($code_store)) {
			$condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
		}

		$stores = array();
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => $stores,
					'total' => 0,
					'groupRoles' => $groupRoles,
					'stores' => $stores
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			$condition['code_store'] = $stores;

		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($phone_number)) {
			$condition['phone_number'] = $phone_number;
		}
		if (!empty($status_pgd)) {
			$condition['status_pgd'] = $status_pgd;
		}
		if (!empty($area)) {
			$id_PDG = $this->getStoresbyprovi($area);
			$condition['id_PDG'] = $id_PDG;
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 4000;

		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$leads = $this->lead_model->getByRole_mkt_pgd($condition, $per_page, $uriSegment);

		$leadTotal = $this->lead_model->getByRole_mkt_pgd($condition);
		foreach ($leadTotal as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}

		$leadTotalMkt = 0;
		for ($i = 0; $i < count($leadTotal); $i++) {
			$leadTotalMkt += (int)$leadTotal[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		foreach ($leads as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}


		$condition['total'] = true;
		$total = $this->lead_model->getByRole_pgd($condition);
		foreach ($leads as $key => $value) {
			$contract = $this->contract_model->findOne(array('customer_infor.id_lead' => (string)$value['_id']));
			if (!empty($contract)) {
				$value['status_contract'] = $contract['status'];
			}
		}
		$leadTotalMkt1 = 0;
		for ($i = 0; $i < count($leads); $i++) {
			$leadTotalMkt1 += (int)$leads[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads,
			'total' => $total,
			'groupRoles' => $groupRoles,
			'stores' => $stores,
			"leadTotalMkt" => $leadTotalMkt,
			"leadTotalMkt1" => $leadTotalMkt1
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_lead_log_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$cdate = !empty($data['cdate']) ? $data['cdate'] : '';
		$udate = !empty($data['udate']) ? $data['udate'] : '';

		$status_sale_fist = !empty($data['status_sale_fist']) ? $data['status_sale_fist'] : "";
		$phone_number = !empty($data['phone_number']) ? $data['phone_number'] : "";
		$status_sale_last = !empty($data['status_sale_last']) ? $data['status_sale_last'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";

		if (!empty($cdate)) {
			$condition = array(
				'cstart' => strtotime(trim($cdate) . ' 00:00:00'),
				'cend' => strtotime(trim($cdate) . ' 23:59:59')
			);
		}
		if (!empty($udate)) {

			$condition['ustart'] = strtotime(trim($udate) . ' 00:00:00');
			$condition['uend'] = strtotime(trim($udate) . ' 23:59:59');
		}
		if (!empty($status_sale_fist)) {
			$condition['status_sale_fist'] = $status_sale_fist;
		}
		if (!empty($status_sale_last)) {
			$condition['status_sale_last'] = $status_sale_last;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($phone_number)) {
			$condition['phone_number'] = $phone_number;
		}
		$leads = $this->log_lead_model->getByRole($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_lead_pt_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$condition1 = array();
		$condition2 = array();
		$condition3 = array();
		$condition4 = array();
		$condition5 = array();
		$condition6 = array();
		$condition10 = array();
		$condition11 = array();
		$condition12 = array();
		$condition13 = array();
		$condition14 = array();
		$condition15 = array();
		$condition16 = array();

		$start = !empty($data['condition']['start']) ? $data['condition']['start'] : "";
		$end = !empty($data['condition']['end']) ? $data['condition']['end'] : "";
		$sdt = !empty($data['condition']['sdt']) ? $data['condition']['sdt'] : "";
		$source = !empty($data['condition']['source']) ? $data['condition']['source'] : "";
		$fullname = !empty($data['condition']['fullname']) ? $data['condition']['fullname'] : "";
		$cskh = !empty($data['condition']['cskh']) ? $data['condition']['cskh'] : "";
		$status_sale = !empty($data['condition']['status_sale']) ? $data['condition']['status_sale'] : "";
		$priority = !empty($data['condition']['priority']) ? $data['condition']['priority'] : "";
		if (!empty($start) && !empty($end)) {
			$condition1 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition2 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition3 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition4 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition5 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition6 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition10 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition11 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition12 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition13 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition14 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition15 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
			$condition16 = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
		}

		if (empty($start) && empty($end)) {
			$condition16 = [
				"sdt" => $sdt,
			];

		} elseif (empty($start)) {
			$condition16 = [
				"end" => $end,
				"sdt" => $sdt,
			];

		} elseif (empty($end)) {
			$condition16 = [
				"start" => $start,
				"sdt" => $sdt,
			];
		}

		if (!empty($sdt)) {
			$condition1['sdt'] = $sdt;
			$condition2['sdt'] = $sdt;
			$condition3['sdt'] = $sdt;
			$condition4['sdt'] = $sdt;
			$condition5['sdt'] = $sdt;
			$condition6['sdt'] = $sdt;
			$condition10['sdt'] = $sdt;
			$condition11['sdt'] = $sdt;
			$condition12['sdt'] = $sdt;
			$condition13['sdt'] = $sdt;
			$condition14['sdt'] = $sdt;
			$condition15['sdt'] = $sdt;
			$condition16['sdt'] = $sdt;
		}
		if (!empty($source)) {
			$condition1['source'] = $source;
			$condition2['source'] = $source;
			$condition3['source'] = $source;
			$condition4['source'] = $source;
			$condition5['source'] = $source;
			$condition6['source'] = $source;
			$condition10['source'] = $source;
			$condition11['source'] = $source;
			$condition12['source'] = $source;
			$condition13['source'] = $source;
			$condition14['source'] = $source;
			$condition15['source'] = $source;
		}
		if (!empty($fullname)) {
			$condition1['fullname'] = $fullname;
			$condition2['fullname'] = $fullname;
			$condition3['fullname'] = $fullname;
			$condition4['fullname'] = $fullname;
			$condition5['fullname'] = $fullname;
			$condition6['fullname'] = $fullname;
			$condition10['fullname'] = $fullname;
			$condition11['fullname'] = $fullname;
			$condition12['fullname'] = $fullname;
			$condition13['fullname'] = $fullname;
			$condition14['fullname'] = $fullname;
			$condition15['fullname'] = $fullname;
		}
		if (!empty($cskh)) {
			$condition1['cskh'] = $cskh;
			$condition2['cskh'] = $cskh;
			$condition3['cskh'] = $cskh;
			$condition4['cskh'] = $cskh;
			$condition5['cskh'] = $cskh;
			$condition6['cskh'] = $cskh;
			$condition10['cskh'] = $cskh;
			$condition11['cskh'] = $cskh;
			$condition12['cskh'] = $cskh;
			$condition13['cskh'] = $cskh;
			$condition14['cskh'] = $cskh;
			$condition15['cskh'] = $cskh;
		}
		if (!empty($status_sale)) {
			$condition1['status_sale'] = $status_sale;
			$condition2['status_sale'] = $status_sale;
			$condition3['status_sale'] = $status_sale;
			$condition4['status_sale'] = $status_sale;
			$condition5['status_sale'] = $status_sale;
			$condition6['status_sale'] = $status_sale;
			$condition10['status_sale'] = $status_sale;
			$condition11['status_sale'] = $status_sale;
			$condition12['status_sale'] = $status_sale;
			$condition13['status_sale'] = $status_sale;
			$condition14['status_sale'] = $status_sale;
			$condition15['status_sale'] = $status_sale;
		}

		if (!empty($priority)) {
			$condition1['priority'] = $priority;
			$condition2['priority'] = $priority;
			$condition3['priority'] = $priority;
			$condition4['priority'] = $priority;
			$condition5['priority'] = $priority;
			$condition6['priority'] = $priority;
			$condition10['priority'] = $priority;
			$condition11['priority'] = $priority;
			$condition12['priority'] = $priority;
			$condition13['priority'] = $priority;
			$condition14['priority'] = $priority;
			$condition15['priority'] = $priority;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$condition1['tab1'] = '-';
		$condition2['tab2'] = '-';
		$condition3['tab3']['email'] = $this->uemail;
		$condition3['tab3']['groupRoles'] = $groupRoles;
		$condition4['tab4'] = '-';
		$condition5['tab5'] = '-';
		$condition6['tab6']['groupRoles'] = $groupRoles;
		$condition6['tab6']['email'] = $this->uemail;
		$condition10['tab10'] = '-';
		$condition11['tab11'] = '-';
		$condition12['tab12'] = '-';
		$condition13['tab13']['email'] = $this->uemail;
		$condition13['tab13']['groupRoles'] = $groupRoles;
		$condition14['tab14']['email'] = $this->uemail;
		$condition14['tab14']['groupRoles'] = $groupRoles;
		$condition15['tab15']['email'] = $this->uemail;
		$condition15['tab15']['groupRoles'] = $groupRoles;


		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		$per_page1 = !empty($this->input->post()['per_page1']) ? $this->input->post()['per_page1'] : 4000;
		$uriSegment1 = !empty($this->input->post()['uriSegment1']) ? $this->input->post()['uriSegment1'] : 0;

		$per_page2 = !empty($this->input->post()['per_page2']) ? $this->input->post()['per_page2'] : 30;
		$uriSegment2 = !empty($this->input->post()['uriSegment2']) ? $this->input->post()['uriSegment2'] : 0;

		$per_page3 = !empty($this->input->post()['per_page3']) ? $this->input->post()['per_page3'] : 30;
		$uriSegment3 = !empty($this->input->post()['uriSegment3']) ? $this->input->post()['uriSegment3'] : 0;

		$per_page4 = !empty($this->input->post()['per_page4']) ? $this->input->post()['per_page4'] : 5;
		$uriSegment4 = !empty($this->input->post()['uriSegment4']) ? $this->input->post()['uriSegment4'] : 0;

		$per_page5 = !empty($this->input->post()['per_page5']) ? $this->input->post()['per_page5'] : 30;
		$uriSegment5 = !empty($this->input->post()['uriSegment5']) ? $this->input->post()['uriSegment5'] : 0;

		$per_page6 = !empty($this->input->post()['per_page6']) ? $this->input->post()['per_page6'] : 30;
		$uriSegment6 = !empty($this->input->post()['uriSegment6']) ? $this->input->post()['uriSegment6'] : 0;

		$per_page10 = !empty($this->input->post()['per_page10']) ? $this->input->post()['per_page10'] : 30;
		$uriSegment10 = !empty($this->input->post()['uriSegment10']) ? $this->input->post()['uriSegment10'] : 0;

		$per_page11 = !empty($this->input->post()['per_page11']) ? $this->input->post()['per_page11'] : 30;
		$uriSegment11 = !empty($this->input->post()['uriSegment11']) ? $this->input->post()['uriSegment11'] : 0;

		$per_page12 = !empty($this->input->post()['per_page12']) ? $this->input->post()['per_page12'] : 30;
		$uriSegment12 = !empty($this->input->post()['uriSegment12']) ? $this->input->post()['uriSegment12'] : 0;
		$per_page13 = !empty($this->input->post()['per_page13']) ? $this->input->post()['per_page13'] : 30;
		$uriSegment13 = !empty($this->input->post()['uriSegment13']) ? $this->input->post()['uriSegment13'] : 0;

		$per_page14 = !empty($this->input->post()['per_page14']) ? $this->input->post()['per_page14'] : 30;
		$uriSegment14 = !empty($this->input->post()['uriSegment14']) ? $this->input->post()['uriSegment14'] : 0;

		$per_page15 = !empty($this->input->post()['per_page15']) ? $this->input->post()['per_page15'] : 30;
		$uriSegment15 = !empty($this->input->post()['uriSegment15']) ? $this->input->post()['uriSegment15'] : 0;

		$per_page16 = !empty($this->input->post()['per_page16']) ? $this->input->post()['per_page16'] : 30;
		$uriSegment16 = !empty($this->input->post()['uriSegment16']) ? $this->input->post()['uriSegment16'] : 0;


		$leads1 = $this->lead_model->getLead_pt($condition1, $per_page1, $uriSegment1);

		foreach ($leads1 as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}
		$users_telesale = $this->get_email_tls();
		$tbp_telesales_telesales = $this->get_email_tbp_telesale();
		if (in_array($this->uemail, $users_telesale) && in_array($this->uemail, $tbp_telesales_telesales) !== true) {
			$condition4['email_cskh'] = $this->uemail;
			$condition5['email_cskh'] = $this->uemail;
			$condition10['email_cskh'] = $this->uemail;
			$condition11['email_cskh'] = $this->uemail;
		}

		$leads2 = $this->lead_model->getLead_pt($condition2, $per_page2, $uriSegment2);
		$leads3 = $this->lead_model->getLead_pt($condition3, $per_page3, $uriSegment3);
		$leads4 = $this->lead_model->getLead_pt($condition4, $per_page4, $uriSegment4);
		$leads5 = $this->lead_model->getLead_pt($condition5, $per_page5, $uriSegment5);
		$leads6 = $this->lead_model->getLead_pt($condition6, $per_page6, $uriSegment6);
		$leads10 = $this->lead_model->getLead_pt($condition10, $per_page10, $uriSegment10);
		$leads11 = $this->lead_model->getLead_pt($condition11, $per_page11, $uriSegment11);
		$leads12 = $this->lead_model->getLead_test($condition12, $per_page12, $uriSegment12);
		$leads13 = $this->lead_model->getLead_pt($condition13, $per_page13, $uriSegment13);
		$leads14 = $this->lead_model->getLead_pt($condition14, $per_page14, $uriSegment14);
		$leads15 = $this->lead_model->getLead_pt($condition15, $per_page15, $uriSegment15);
		$leads16 = $this->recording_model->get_missed_call($condition16,$per_page16, $uriSegment16);


		$condition1['total'] = true;
		$condition2['total'] = true;
		$condition3['total'] = true;
		$condition4['total'] = true;
		$condition5['total'] = true;
		$condition6['total'] = true;
		$condition10['total'] = true;
		$condition11['total'] = true;
		$condition12['total'] = true;
		$condition13['total'] = true;
		$condition14['total'] = true;
		$condition15['total'] = true;
		$condition16['total'] = true;


		$leads1_total = $this->lead_model->getLead_pt($condition1);
		$leads2_total = $this->lead_model->getLead_pt($condition2);
		$leads3_total = $this->lead_model->getLead_pt($condition3);
		$leads4_total = $this->lead_model->getLead_pt($condition4);
		$leads5_total = $this->lead_model->getLead_pt($condition5);
		$leads6_total = $this->lead_model->getLead_pt($condition6);
		$leads10_total = $this->lead_model->getLead_pt($condition10);
		$leads11_total = $this->lead_model->getLead_pt($condition11);
		$leads12_total = $this->lead_model->getLead_pt($condition12);
		$leads13_total = $this->lead_model->getLead_pt($condition13);
		$leads14_total = $this->lead_model->getLead_pt($condition14);
		$leads15_total = $this->lead_model->getLead_pt($condition15);
		$leads16_total = $this->recording_model->get_count_miss_call($condition16);



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data1' => $leads1,
			'total1' => $leads1_total,
			'data2' => $leads2,
			'total2' => $leads2_total,
			'data3' => $leads3,
			'total3' => $leads3_total,
			'data4' => $leads4,
			'total4' => $leads4_total,
			'data5' => $leads5,
			'total5' => $leads5_total,
			'data6' => $leads6,
			'total6' => $leads6_total,
			'data10' => $leads10,
			'total10' => $leads10_total,
			'data11' => $leads11,
			'total11' => $leads11_total,
			'data12' => $leads12,
			'total12' => $leads12_total,
			'data13' => $leads13,
			'total13' => $leads13_total,
			'data14' => $leads14,
			'total14' => $leads14_total,
			'data15' => $leads15,
			'total15' => $leads15_total,
			'data16' => $leads16,
			'total16' => $leads16_total,
			'groupRoles' => $groupRoles
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_lead_export_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
//		$sdt = !empty($data['sdt']) ? $data['sdt'] : "";
//		$fullname = !empty($data['fullname']) ? $data['fullname'] : "";
//		$cskh = !empty($data['cskh']) ? $data['cskh'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start)),
				'end' => strtotime(trim($end))
			);
		}
		$leads = $this->lead_model->getAllLeadExcel($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		];
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function get_list_lead_mkt_export_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$leads = $this->lead_model->getListLeadMKTExport($condition);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_cskh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		//5ea1b6d2d6612b65473f2b68 marketing
		//5ea1b6abd6612b6dd20de539 thu-hoi-no
		//5ea1b686d6612bdf6c0422af telesales
		$leads = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af'));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_user_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		//5ea1b6d2d6612b65473f2b68 marketing
		//5ea1b6abd6612b6dd20de539 thu-hoi-no
		//5ea1b686d6612bdf6c0422af telesales
		$leads = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af', '5de726a8d6612b6f2b431749', '5de726e4d6612b6f2c310c78', '5ea1b6abd6612b6dd20de539', '5de72198d6612b4076140606', '5de726c9d6612b6f2a617ef5'));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$lead = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));

		if (empty($lead)) return;

		if (!empty($lead)) {
            $id_pgd=$lead['id_PDG'];
            $cvkd=[];
            if (!empty($id_pgd)) {
		    $users_by_role = $this->getUserGroupRole_auto(array('5de726e4d6612b6f2c310c78','5de726c9d6612b6f2a617ef5','61945bd9b5987f1710347a65'));
			$users_by_store = $this->getUserbyStores($id_pgd);

			$data_user = array_intersect($users_by_store, $users_by_role);

			if ($lead['id_PDG'] == "61945bd9b5987f1710347a65"){
				$data_user = $users_by_store;
			}

			foreach ($data_user as $key => $value) {
				$user = $this->user_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($value)));
				$groupRoles = $this->getGroupRole($value);
				if(!in_array('telesales', $groupRoles) && (in_array('cua-hang-truong', $groupRoles)  || in_array('giao-dich-vien', $groupRoles) || in_array('hoi-so', $groupRoles)))
				array_push($cvkd,$user['email']);
			}
			$lead['data_cvkd']= $cvkd;
		   }
			$lead['phone_number'] = encrypt(convert_zero_phone($lead['phone_number']));

		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function update_chage_cvkd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
		if (empty($leadDB)) return;
		if (empty($data['cvkd'])) return;
		$id = $data['id'];
		$this->log_lead_all($data);
		$leadDB['id']=$id;

		unset($data['id']);
		//Update lead
		$this->lead_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$this->cong_tru_lead_pgd($leadDB,$data['cvkd'],$leadDB['cvkd']);
       $response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update lead success",
			'data' => $data,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}
    private function getUserGroupRole_auto($GroupIds)
	{
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = key($item);
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}


	public function get_all_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";


		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}
		if (!empty($code_store)) {
			$condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
		}
		$stores = array();
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => $stores,
					'total' => 0,
					'groupRoles' => $groupRoles,
					'stores' => $stores
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			$condition['code_store'] = $stores;

		}


		if (!empty($condition)) {
			$leads = $this->lead_model->getByRole_pgd_excel($condition);
		} else {
			$leads = $this->lead_model->find();
		}
		foreach ($leads as $key => $value) {
			$contract = $this->contract_model->find_where(array('customer_infor.id_lead' => (string)$value['_id']));
			if (!empty($contract)) {
				$value['status_contract'] = $contract['status'];
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function search_mkt_general_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date("Y-m-d");
		$end = !empty($data['end']) ? $data['end'] : date("Y-m-d");

		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$channels = !empty($data['channels']) ? $data['channels'] : "";

		$area = !empty($data['area']) ? $data['area'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) ),
				'end' => strtotime(trim($end) )
			);
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		if (!empty($code_store)) {
			$condition['code_store'] = $code_store;
		}
		if (!empty($source)) {
			$condition['source'] = $source;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($area)) {
			$condition['area'] = $area;
		}
		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_report_general($condition);
			$lead_log = $this->lead_model->getByRole_mkt($condition);
		} else {
			$lead = $this->lead_model->find();
		}

		$cond = array();
		$arr_data_QC_nguon = lead_nguon();
		$arr_data_QC_SOU = [];
		$arr_data_QC_CAM = [];
		$arr_return_QC_SOU = [];
		$arr_return_QC_CAM = [];
		$arr_return_QC_nguon = [];
		$arr_phone = [];
		$arr_lead = [];
		$arr_lead_log = [];
		$html = "";
		if (!empty($lead)) {

			foreach ($lead as $key => $value) {
				if (!isset($value['utm_source'])) {
					$value['utm_source'] = '';
				}
				if (!isset($value['utm_campaign'])) {
					$value['utm_campaign'] = '';
				}

				$arr_data_QC_SOU += [$key => (isset($value['utm_source'])) ? $value['utm_source'] : ''];
				$arr_data_QC_CAM += [$key => (isset($value['utm_campaign'])) ? $value['utm_campaign'] : ''];
				$arr_lead += [$key => (string)$value['_id']];

				$contract = $this->contract_model->find_where(array('customer_infor.id_lead' => (string)$value['_id']));

				if (!empty($contract)) {

					foreach ($contract as $key1 => $value1) {
						if ($value1['status'] >= 17) {
							$value['debt'] = $value1['loan_infor']['amount_money'];

							$value['contract_disbursement'] = 1;
						}
					}
				}


			}
		}
		if (!empty($lead_log)) {
			$n_l = 0;
			$n_utm = 0;
			foreach ($lead_log as $key => $value) {
				// if (isset($value['lead_data']['qualified']) && $value['lead_data']['qualified'] == '1') {

				// 	$value['lead_data']['utm_source'] = (isset($value['old_data']['utm_source'])) ? $value['old_data']['utm_source'] : '-';
				// 	$value['lead_data']['utm_campaign'] = (isset($value['old_data']['utm_campaign'])) ? $value['old_data']['utm_campaign'] : '-';

				// 	if (!isset($value['old_data']['qualified'])) {
				$n_utm++;
				$arr_data_QC_SOU += [count($arr_data_QC_SOU) + $n_utm => $value['utm_source']];

				$arr_data_QC_CAM += [count($arr_data_QC_CAM) + $n_utm => $value['utm_campaign']];

				// 		$n_l++;
				// 		$arr_lead_log += [$n_l => $value['lead_data']];
				// 	} else if (isset($value['old_data']['qualified']) && $value['old_data']['qualified'] != $value['lead_data']['qualified']) {
				// 		$n_utm++;
				// 		$arr_data_QC_SOU += [count($arr_data_QC_SOU) + $n_utm => $value['lead_data']['utm_source']];

				// 		$arr_data_QC_CAM += [count($arr_data_QC_CAM) + $n_utm => $value['lead_data']['utm_campaign']];

				$n_l++;
				$arr_lead_log += [$n_l => $value];

			}
		}

		// 	}

		// }


		$ng = 0;
		if (!empty($arr_data_QC_nguon))
			foreach (array_unique($arr_data_QC_nguon) as $key => $value) {
				$ng++;

				$arr_phone = get_values($lead, 'source', $key, '', '', '', '', 'phone_number', true, false);

				if (count($arr_phone) <= 0)
					$arr_phone = ['1'];

				$arr_return_QC_nguon += [$ng => [
					'source' => $value,
					'total_lead_all' => count_values($lead, 'source', $key, '', '', '', ''),
					'total_lead_qualified' => count_values($arr_lead_log, 'source', $key, '', '', '', ''),
					'total_contract_disbursement' => count_values($lead, 'contract_disbursement', '1', 'source', $key, '', ''),
					'total_debt' => sum_values($lead, 'source', $key, '', '', '', '', 'debt'),

				]];

			}
		//print_r( $arr_return_QC_nguon ); die;
		$sou = 0;
		if (!empty($arr_data_QC_SOU))
			foreach (array_unique($arr_data_QC_SOU) as $key => $value) {

				$sou++;
				$arr_phone = get_values($lead, 'utm_source', $value, '', '', '', '', 'phone_number', true, false);

				if (empty($arr_phone))
					$arr_phone = ['1'];

				$arr_return_QC_SOU += [$sou => [
					'utm_source' => $value,
					'total_lead_all' => count_values($lead, 'utm_source', $value, 'source', '1', '', ''),
					'total_lead_qualified' => count_values($arr_lead_log, 'utm_source', $value, '', '', '', ''),
					'total_contract_disbursement' => count_values($lead, 'utm_source', $value, 'contract_disbursement', '1', '', ''),
					'total_debt' => sum_values($lead, 'utm_source', $value, '', '', '', '', 'debt'),
				]];
			}

		$cam = 0;
		if (!empty($arr_data_QC_SOU) && !empty($arr_data_QC_CAM))
			foreach (array_unique($arr_data_QC_SOU) as $key1 => $value1) {
				foreach (array_unique($arr_data_QC_CAM) as $key => $value) {

					$arr_phone = get_values($lead, 'utm_campaign', $value, 'utm_source', $value1, '', '', 'phone_number', true, false);
					if (!empty($arr_phone)) {

						$cam++;
						$arr_return_QC_CAM += [$cam => [
							'utm_campaign' => $value,
							'utm_source' => $value1,
							'total_lead_all' => count_values($lead, 'utm_campaign', $value, 'utm_source', $value1, 'source', '1'),
							'total_lead_qualified' => count_values($arr_lead_log, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
							'total_contract_disbursement' => count_values($lead, 'utm_campaign', $value, 'utm_source', $value1, 'contract_disbursement', '1'),
							'total_debt' => sum_values($lead, 'utm_source', $value1, 'utm_campaign', $value, '', '', 'debt'),

						]];
					}

				}
			}

		//var_dump($arr_return_QC_CAM) ; die;
		$html = gen_html_QC($arr_return_QC_nguon, $arr_return_QC_SOU, $arr_return_QC_CAM);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_recording_html_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$lead = $this->lead_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($id)));
		$recording = $this->recording_model->getByRole_lead(array('phone_number' => $lead['phone_number']));
		$html = gen_html_recording($recording);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'html' => $html,
			'data' => $lead['phone_number']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function mkt_lead_cancel_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date("Y-m-d");
		$end = !empty($data['end']) ? $data['end'] : date("Y-m-d");

		$source = !empty($data['source']) ? $data['source'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";

		$area = !empty($data['area']) ? $data['area'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;

		if (!empty($source)) {
			$condition['source'] = $source;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($area)) {
			$condition['area'] = $area;
		}
		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_lead_cancel_static($condition);
			$lead_log = $this->log_lead_model->getByRole($condition);
		} else {
			$lead = $this->lead_model->find();
		}

		$arr_data_QC_SOU = [];
		$arr_data_QC_CAM = [];
		$arr_return_QC_SOU = [];
		$arr_return_QC_CAM = [];
		$total_lead_cancel = 0;
		$total_lead = 0;
		$arr_data_QC_SOU_q = [];
		$arr_lead_log_cancel = [];
		$arr_lead_log = [];
		$arr_lead_cancel = [];
		$arr_data_QC_CAM_q = [];
		$arr_return_QC_SOU_q = [];
		$arr_return_QC_CAM_q = [];
		$total_lead_cancel_q = 0;
		$total_lead_q = 0;
		$html_q = "";
		$html = "";
		if (!empty($lead)) {
			$n = 0;
			foreach ($lead as $key => $value) {
				if (!empty($value['reason_cancel'])) {
					if (isset($value['qualified']) && $value['qualified'] == "1") {

					} else {
						$n++;
						if (!empty($value['utm_source']))
							$arr_data_QC_SOU += [$key => $value['utm_source']];

						if (!empty($value['utm_campaign']))
							$arr_data_QC_CAM += [$key => $value['utm_campaign']];

						$arr_lead_cancel += [$n => $value];
					}
				}
			}

			if (!empty($lead_log)) {
				$n_l = 0;
				$n_t = 0;
				foreach ($lead_log as $key => $value) {
					$ck = false;
					if (!empty($value['lead_data']['reason_cancel']) && empty($value['old_data']['reason_cancel'])) {
						if (isset($value['lead_data']['qualified']) && $value['lead_data']['qualified'] == '1') {
							$value['utm_source'] = "-";
							if (isset($value['old_data']['utm_source']))
								$value['utm_source'] = $value['old_data']['utm_source'];

							$value['utm_campaign'] = "-";
							if (isset($value['old_data']['utm_campaign']))
								$value['utm_campaign'] = $value['old_data']['utm_campaign'];

							$value['reason_cancel'] = $value['lead_data']['reason_cancel'];
							if (!isset($value['old_data']['qualified'])) {
								$n_l++;
								$ck = true;

							} else if (isset($value['old_data']['qualified']) && $value['old_data']['qualified'] != $value['lead_data']['qualified']) {
								$n_l++;
								$ck = true;

							}
						}
						if ($ck) {

							$arr_data_QC_SOU_q += [$n_l => $value['utm_source']];
							$arr_data_QC_CAM_q += [$n_l => $value['utm_campaign']];
							$arr_lead_log_cancel += [$n_l => $value['lead_data']];

						}

					}
					if (isset($value['lead_data']['qualified']) && $value['lead_data']['qualified'] == '1') {
						$n_t++;
						$arr_lead_log += [$n_t => $value['lead_data']];
					}

				}
			}

			$sou = 0;
			if (!empty($arr_data_QC_SOU))
				foreach (array_unique($arr_data_QC_SOU) as $key => $value) {
					$sou++;

					$arr_return_QC_SOU += [$sou => [
						'utm_source' => $value,
						'total_lead_cancel' => count_values($arr_lead_cancel, '', '', '', '', '', ''),
						'total_lead' => count_values($lead, 'utm_source', $value, '', '', '', ''),
						'lead_cancel' => count_values($arr_lead_cancel, 'utm_source', $value, '', '', '', '')
					]];
				}
			$sou_q = 0;
			if (!empty($arr_data_QC_SOU_q))
				foreach (array_unique($arr_data_QC_SOU_q) as $key => $value) {
					$sou_q++;
					if (count_values($arr_lead_log, 'utm_source', $value, '', '', '', '') > 0) {
						$arr_return_QC_SOU_q += [$sou_q => [
							'utm_source' => $value,
							'total_lead_cancel' => count_values($arr_lead_log_cancel, '', '', '', '', '', ''),
							'total_lead' => count_values($arr_lead_log, 'utm_source', $value, '', '', '', ''),
							'lead_cancel' => count_values($arr_lead_log_cancel, 'utm_source', $value, '', '', '', '')
						]];
					}
				}
			$cam = 0;
			if (!empty($arr_data_QC_SOU) && !empty($arr_data_QC_CAM))
				foreach (array_unique($arr_data_QC_SOU) as $key1 => $value1) {
					foreach (array_unique($arr_data_QC_CAM) as $key => $value) {
						$cam++;
						if (count_values($arr_lead_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', '') > 0) {
							$arr_return_QC_CAM += [$cam => [
								'utm_campaign' => $value,
								'utm_source' => $value1,
								'total_lead_cancel' => count_values($arr_lead_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
								'total_lead' => count_values($lead, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
								'lead_cancel' => count_values($arr_lead_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', '')

							]];
						}
					}

				}

			$cam_q = 0;
			if (!empty($arr_data_QC_SOU_q) && !empty($arr_data_QC_CAM_q))
				foreach (array_unique($arr_data_QC_SOU_q) as $key1 => $value1) {
					foreach (array_unique($arr_data_QC_CAM_q) as $key => $value) {
						$cam_q++;
						if (count_values($arr_lead_log_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', '') > 0) {
							$arr_return_QC_CAM_q += [$cam_q => [
								'utm_campaign' => $value,
								'utm_source' => $value1,
								'total_lead_cancel' => count_values($arr_lead_log_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
								'total_lead' => count_values($arr_lead_log, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
								'lead_cancel' => count_values($arr_lead_log_cancel, 'utm_campaign', $value, 'utm_source', $value1, '', '')

							]];
						}
					}
				}
		}

		//var_dump($arr_return_QC_CAM) ; die;
		$html = gen_html_lead_cancel($arr_return_QC_SOU, $arr_return_QC_CAM);
		$html_q = gen_html_lead_cancel_q($arr_return_QC_SOU_q, $arr_return_QC_CAM_q);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,
			'data_q' => $html_q
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function mkt_lead_cancel_static_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date("Y-m-d");
		$end = !empty($data['end']) ? $data['end'] : date("Y-m-d");

		$source = !empty($data['source']) ? $data['source'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array()
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (!empty($source)) {
			$condition['source'] = $source;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($area)) {
			$condition['area'] = $area;
		}
		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_lead_cancel_static($condition);
			$lead_log = $this->log_lead_model->getByRole($condition);
		} else {
			$lead = $this->lead_model->find();
		}


		$arr_data_reason = reason();
		$arr_return_reason = [];
		$arr_return_reason_q = [];
		$total_lead_cancel = 0;
		$total_lead = 0;
		$arr_lead = [];
		$arr_lead_log = [];
		$total_lead_cancel_q = 0;
		$total_lead_q = 0;
		$arr_lead_log_cancel = [];
		$html_q = "";
		$html = "";
		if (!empty($lead)) {

			foreach ($lead as $key => $value) {
				if (isset($value['qualified']) && $value['qualified'] == "1") {

				} else {

					if (!empty($value['reason_cancel']))
						$total_lead_cancel++;
					$total_lead++;
				}

			}
		}
		if (!empty($lead_log)) {
			$n_l = 0;
			$n_t = 0;
			foreach ($lead_log as $key => $value) {
				$ck = false;
				$value['lead_data']['utm_source'] = (!empty($value['old_data']['utm_source'])) ? $value['old_data']['utm_source'] : '-';
				$value['lead_data']['utm_campaign'] = (!empty($value['old_data']['utm_campaign'])) ? $value['old_data']['utm_campaign'] : '-';
				if (!empty($value['lead_data']['reason_cancel']) && empty($value['old_data']['reason_cancel'])) {
					if (isset($value['lead_data']['qualified']) && $value['lead_data']['qualified'] == '1') {


						if (!isset($value['old_data']['qualified'])) {
							$n_l++;
							$ck = true;

						} else if (isset($value['old_data']['qualified']) && $value['old_data']['qualified'] != $value['lead_data']['qualified']) {
							$n_l++;
							$ck = true;

						}
					}
					if ($ck) {

						$total_lead_cancel_q++;
						$arr_lead_log_cancel += [$n_l => $value['lead_data']];

					}

				}
				if (isset($value['lead_data']['qualified']) && $value['lead_data']['qualified'] == '1') {

					$n_t++;
					$arr_lead_log += [$n_t => $value['lead_data']];
					$total_lead_q++;
				}

			}
		}


		$sou = 0;
		if (!empty($lead))
			foreach ($lead as $key => $value) {
				$sou++;
				$lead_cancel = count_values($lead, 'reason_cancel', $key, 'status', '2', 'qualified', '2');
				if (isset($value['reason_cancel']) && !empty($value['reason_cancel']) && $lead_cancel > 0) {
					$utm_source = (!empty($value['utm_source'])) ? $value['utm_source'] : '-';
					$utm_campaign = (!empty($value['utm_campaign'])) ? $value['utm_campaign'] : '-';

					$arr_return_reason += [$sou => [
						'reason' => reason($value['reason_cancel']),
						'utm_source' => $utm_source,
						'utm_campaign' => $utm_campaign,
						'total_lead_cancel' => $total_lead_cancel,
						'total_lead' => $total_lead,
						'lead_cancel' => count_values($lead, 'reason_cancel', $key, 'status', '2', 'qualified', '2')
					]];
				}
			}
		$sou_q = 0;
		if (!empty($arr_lead_log))
			foreach ($arr_lead_log as $key => $value) {
				$sou_q++;
				$lead_cancel = count_values($arr_lead_log, 'reason_cancel', $key, 'status', '2', 'qualified', '1');
				if (isset($value['reason_cancel']) && !empty($value['reason_cancel']) && $lead_cancel > 0) {
					$utm_source = (!empty($value['utm_source'])) ? $value['utm_source'] : '-';
					$utm_campaign = (!empty($value['utm_campaign'])) ? $value['utm_campaign'] : '-';
					$arr_return_reason_q += [$sou_q => [
						'reason' => reason($value['reason_cancel']),
						'utm_source' => $utm_source,
						'utm_campaign' => $utm_campaign,
						'total_lead_cancel' => $total_lead_cancel_q,
						'total_lead' => $total_lead_q,
						'lead_cancel' => count_values($arr_lead_log, 'reason_cancel', $key, 'status', '2', 'qualified', '1')
					]];
				}
			}
		// var_dump($arr_return_reason); die;
		$html = gen_html_lead_reason($arr_return_reason);
		$html_q = gen_html_lead_reason_q($arr_return_reason_q);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,
			'data_q' => $html_q
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function lead_cancel_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-d', strtotime(' -1 day'));
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d', strtotime(' -1 day'));


		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		if (!empty($condition)) {
			// $lead = $this->log_lead_model->getByRole_old($condition);
			$lead_last = $this->log_lead_model->getByRole($condition);
		} else {
			$lead = $this->lead_model->find();
		}


		$arr_data_reason = reason();
		$arr_return_reason = [];
		$total_lead_cancel = 0;
		$total_lead = 0;
		$lead_new = [];
		$html = "";

		$n = 0;
		// if (!empty($lead)) {
		//     foreach ($lead as $key=>$value) {
		//         $n++;
		//    $value=$value['old_data'];
		//    $lead_new+=[$n=>$value];
		//   if(!empty($value['reason_cancel']) )
		//       $total_lead_cancel++;
		//       $total_lead++;
		//     }
		// }
		if (!empty($lead_last)) {
			foreach ($lead_last as $key => $value) {
				$n++;
				$value = $value['lead_data'];
				$lead_new += [$n => $value];
				if (!empty($value['reason_cancel']))
					$total_lead_cancel++;
				$total_lead++;
			}
		}
		$sou = 0;
		if (!empty($arr_data_reason))
			foreach (array_unique($arr_data_reason) as $key => $value) {
				$sou++;
				if (count_values($lead_new, 'reason_cancel', $key, 'status', '2', 'status_sale', '4') > 0)
					$arr_return_reason += [$sou => [
						'reason' => $value,
						'total_lead_cancel' => $total_lead_cancel,
						'total_lead' => $total_lead,
						'lead_cancel' => count_values($lead_new, 'reason_cancel', $key, 'status', '2', 'status_sale', '4')
					]];
			}

		$html = gen_html_report_lead_reason($arr_return_reason);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function lead_tsl_daily_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-d', strtotime(' -1 day'));
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d', strtotime(' -1 day'));


		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
			$condition_last_all = array(
				'start' => strtotime(trim($start) . ' 23:59:59' . " -1 day")
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_lead_cancel_static($condition);
			$lead_last = $this->log_lead_model->getByRole($condition);
			$lead_old = $this->lead_model->mkt_lead_cancel_static($condition_last_all);
		} else {
			$lead = $this->lead_model->find();
		}
		$daily = $start . '-' . $end;
		$arr_return_sale = [];
		$arr_return_daily = [];
		$html = "";
		$n = 0;


		$arr_return_sale = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af'));

		if (!empty($arr_return_sale)) {
			foreach ($arr_return_sale as $key1 => $value1) {
				foreach ($value1 as $key => $value) {

					$condition['cskh'] = $value['email'];
					$total_call = $this->recording_model->countByRole($condition);
					$qualifined = 0;
					$kh_old = 0;
					$kh_new = 0;
					if (!empty($lead_last)) {
						foreach ($lead_last as $key1 => $value1) {
							$value1 = $value1['lead_data'];
							$value2 = $value1['old_data'];
							//old
							if (isset($value2['cskh']) && isset($value2['status_sale']) && $value2['cskh'] == $value2['email'] && in_array($value1['status_sale'], array('1')) && $value2['created_at'] < strtotime(date('Y-m-d') . ' 00:00:00'))
								$kh_old++;

							//qualifined
							if (isset($value1['cskh']) && isset($value1['status_sale']) && $value1['cskh'] == $value['email'] && in_array($value1['status_sale'], array('2', '9')))
								$qualifined++;
						}
					}
					if (!empty($lead_old)) {
						foreach ($lead_old as $key1 => $value1) {
							//old
							if (isset($value1['cskh']) && isset($value1['status_sale']) && $value1['cskh'] == $value['email'])
								$kh_old++;
						}
					}
					if (!empty($lead)) {
						foreach ($lead as $key1 => $value1) {
							//new
							if (isset($value1['cskh']) && isset($value1['status_sale']) && $value1['cskh'] == $value['email'])
								$kh_new++;
							//qualifined
							if (isset($value1['cskh']) && isset($value1['status_sale']) && $value1['cskh'] == $value['email'] && in_array($value1['status_sale'], array('2', '9')))
								$qualifined++;
						}
					}

					$arr_return_daily += [$key => [
						'cskh' => $value['email'],
						'kh_new' => $kh_new,
						'kh_old' => $kh_old,
						'qualifined' => $qualifined,
						'total_call' => $total_call
					]];
				}
			}
		}

		$html = gen_html_tsl_daily($arr_return_daily);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function lead_call_statistics_daily_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-d');
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d');
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		if (!empty($condition)) {
			$recording = $this->recording_model->getByRole($condition);
		} else {
			$recording = $this->recording_model->find();
		}
		$timestamp = strtotime($start);
		$start = date('d-m-Y', $timestamp);
		$timestamp = strtotime($end);
		$end = date('d-m-Y', $timestamp);
		$daily = $start . '<br>' . $end;
		$arr_return_sale = [];
		$arr_return_daily = [];
		$html = "";
		$call_in = 0;
		$time_call_in = 0;
		$call_out = 0;
		$time_call_out = 0;
		$call_internal = 0;
		$time_call_internal = 0;
		$call_ok = 0;
		$time_call_ok = 0;
		if (!empty($recording)) {
			foreach ($recording as $key => $value) {
				//gọi vào
				if (isset($value['direction']) && $value['direction'] == 'inbound') {
					$call_in++;
					$time_call_in += $value['billDuration'];
				}
				//gọi ra
				if (isset($value['direction']) && $value['direction'] == 'outbound') {
					$call_out++;
					$time_call_out += $value['billDuration'];
				}
				//gọi nội bộ
				if (isset($value['fromNumber']) && isset($value['fromNumber']) && $value['direction'] == 'local') {
					$call_internal++;
					$time_call_internal += $value['billDuration'];
				}
				//gọi thành công
				if (isset($value['billDuration']) && $value['billDuration'] > 0) {
					$call_ok++;
					$time_call_ok += $value['billDuration'];
				}

			}

			$arr_return_daily += [1 => [
				'daily' => $daily,
				'call' => $call_in + $call_out + $call_internal,
				'call_in' => $call_in,
				'call_out' => $call_out,
				'call_internal' => $call_internal,
				'call_ok' => $call_ok,
				'time' => $time_call_in + $time_call_out + $time_call_internal,
				'time_call_in' => $time_call_in,
				'time_call_out' => $time_call_out,
				'time_call_internal' => $time_call_internal,

			]];

		}


		$html = gen_html_lead_call_statistics_daily($arr_return_daily);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function lead_daily_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-d', strtotime(' -1 day'));
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d', strtotime(' -1 day'));


		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
			$condition_last = array(
				'start' => strtotime(trim($start) . ' 00:00:00' . " -1 day"),
				'end' => strtotime(trim($end) . ' 23:59:59' . " -1 day")
			);
			$condition_last_all = array(
				'start' => strtotime(trim($start) . ' 23:59:59' . " -1 day")
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}

		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_lead_cancel_static($condition);
			$lead_last = $this->log_lead_model->getByRole($condition_last);
			$lead_all = $this->lead_model->mkt_lead_cancel_static($condition_last_all);
			$lead_last_all = $this->log_lead_model->getByRole_old($condition_last_all);
			$lead_now_all = $this->log_lead_model->getByRole($condition);

		} else {

		}
		$timestamp = strtotime($start);
		$start = date('d-m-Y', $timestamp);
		$timestamp = strtotime($end);
		$end = date('d-m-Y', $timestamp);

		$daily = $start;
		$arr_return_sale = [];
		$arr_return_daily = [];
		$html = "";
		$cham_soc_tiep = 0;
		$chua_nghe_may = 0;
		$chuyen_ve_pgd = 0;
		$lead_cham_soc_tiep = 0;
		$lead_chua_nghe_may = 0;
		$lead_huy = 0;
		$lead_ton = 0;
		$lead_pgd_divide_xu_ly = 0;
		$lead_xu_ly = 0;
		$lead_fanpage = 0;
		$lead_digital = 0;
		$lead_tongdai_add_ngoai = 0;
		$trung_binh_tts_ngay = 0;
		if (!empty($lead_last)) {
			$lead_last = unique_multidim_array($lead_last, 'lead_data', 'phone_number');
			foreach ($lead_last as $key => $value) {
				$value = $value['lead_data'];
				//Lead chăm sóc tiếp(chưa liên lạc được, chưa tư vấn được, đang suy nghĩ)
				if ((isset($value['status_sale']) && in_array($value['status_sale'], array('3', '7', '5')))) {
					$cham_soc_tiep++;

				}
				//Lead chưa nghe máy(chưa liên lạc được)
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('3'))) {
					$chua_nghe_may++;

				}
			}
		}
		if (!empty($lead_all)) {
			foreach ($lead_all as $key => $value) {
				//Lead chăm sóc tiếp(chưa xử lý)
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('1'))) {
					$cham_soc_tiep++;
					$lead_ton++;

				}
			}
		}
		if (!empty($lead_last_all)) {
			foreach ($lead_last_all as $key => $value) {
				$value = $value['old_data'];
				//Lead chăm sóc tiếp(chưa xử lý)
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('1'))) {
					$cham_soc_tiep++;
					$lead_ton++;

				}
				//Lead chưa nghe máy(chưa liên lạc được)
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('3'))) {
					$chua_nghe_may++;

				}
			}
		}
		if (!empty($lead)) {
			foreach ($lead as $key => $value) {
				//Lead digital
				if (isset($value['source']) && $value['source'] == '1') {
					$lead_digital++;

				}
				//Lead tổng đài + lead tự kiếm
				if (isset($value['source']) && in_array($value['source'], array('2', '3'))) {
					$lead_tongdai_add_ngoai++;

				}

			}
		}
		if (!empty($lead_now_all)) {
			foreach ($lead_now_all as $key => $value) {
				$value = $value['lead_data'];
				//Lead  lead xử lý
				if (isset($value['status_sale']) && $value['status_sale'] > '1') {
					$lead_xu_ly++;

				}
				//Lead chuyển về PGD
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('2'))) {
					$chuyen_ve_pgd++;

				}
				//Lead lead_cham_soc_tiep
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('3', '7', '5'))) {
					$lead_cham_soc_tiep++;

				}
				//Lead chưa nghe máy
				if (isset($value['status_sale']) && in_array($value['status_sale'], array('3'))) {
					$lead_chua_nghe_may++;

				}
				//Lead hủy
				if (isset($value['status']) && in_array($value['status'], array('2'))) {
					$lead_huy++;

				}


			}
		}
		$time_call = 0;
		$total_tls = 0;
		$total_call = 0;
		$recording = $this->recording_model->getByRole($condition);
		foreach ($recording as $key => $value) {
			$total_call++;


		}
		$arr_return_sale = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af'));

		if (!empty($arr_return_sale)) {
			foreach ($arr_return_sale as $key1 => $value1) {
				foreach ($value1 as $key => $value) {
					$total_tls++;
				}
			}
		}
		//Trung bình/TTS/Ngày: Tổng thời gian gọi của tất cả TLS/Số TLS/số ngày filter
		$trung_binh_tts_ngay = ($total_tls > 0) ? number_format($total_call / $total_tls, 2, '.', '') : 0;
		$arr_return_daily += [1 => [
			'daily' => $daily,
			'cham_soc_tiep' => $cham_soc_tiep,
			'chua_nghe_may' => $chua_nghe_may,
			'chuyen_ve_pgd' => $chuyen_ve_pgd,
			'lead_cham_soc_tiep' => $lead_cham_soc_tiep,
			'lead_chua_nghe_may' => $lead_chua_nghe_may,
			'lead_huy' => $lead_huy,
			'lead_ton' => $lead_ton,
			'lead_pgd_divide_xu_ly' => ((int)$lead_xu_ly > 0) ? round(((int)$chuyen_ve_pgd / (int)$lead_xu_ly) * 100) : 0,
			'lead_digital' => $lead_digital,
			'lead_tongdai_add_ngoai' => $lead_tongdai_add_ngoai,
			'total_call' => $total_call,
			'trung_binh_tts_ngay' => $trung_binh_tts_ngay,


		]];


		$html = gen_html_lead_daily($arr_return_daily);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function mkt_lead_digital_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date("Y-m-d");
		$end = !empty($data['end']) ? $data['end'] : date("Y-m-d");

		$source = !empty($data['source']) ? $data['source'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}
		if (!empty($code_store)) {
			$condition['code_store'] = $code_store;
		}
		if (!empty($source)) {
			$condition['source'] = $source;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($area)) {
			$condition['area'] = $area;
		}
		$condition['source'] = '1';
		if (!empty($condition)) {
			$lead = $this->lead_model->mkt_lead_cancel_static($condition);
		} else {
			$lead = $this->lead_model->find_where(array('source' => '1'));
		}
		if (!empty($lead)) {
			foreach ($lead as $key => $value) {
				if (isset($value['phone_number'])) {
					$count_lead_sdt = $this->lead_model->count(array('phone_number' => $value['phone_number']));
					$value['sdt_trung'] = $count_lead_sdt - 1;
				}
				if (isset($value['ip'])) {
					$count_lead_ip = $this->lead_model->count(array('ip' => $value['ip']));
					$value['ip_trung'] = $count_lead_ip - 1;
				}

				if (isset($value['hk_district'])) {
					$district = $this->district_model->findOne(array("code" => $value['hk_district']));
					if (!empty($district)) {
						//  var_dump($district); die;
						$value['hk_district'] = $district['name'];
					} else {
						$value['hk_district'] = '';
					}
				}

			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function mkt_lead_full_info_digital_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : date("Y-m-d");
		$end = !empty($data['end']) ? $data['end'] : date("Y-m-d");

		$source = !empty($data['source']) ? $data['source'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$source = !empty($data['source']) ? $data['source'] : "";
		$isExport = !empty($data['isExport']) ? $data['isExport'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles)) {
			$all = true;
		}
		if (!empty($code_store)) {
			$condition['code_store'] = $code_store;
		}
		if (!empty($source)) {
			$condition['source'] = $source;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($area)) {
			$condition['area'] = $area;
		}
		if (!empty($isExport)) {
			$condition['isExport'] = $isExport;
		}
		$condition['source'] = '1';
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		if (!empty($condition)) {
			$lead = $this->lead_model->getByRole($condition, $per_page, $uriSegment);
			$total = $this->lead_model->getByRole_total($condition);

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_status_pgd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
		if (empty($leadDB)) return;
		$id = $data['id'];
		unset($data['id']);
		$this->lead_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update lead success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
		if (empty($leadDB)) return;
		$id = $data['id'];
		if(!empty($data["id_PDG"]))
		{
		$store_info = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data["id_PDG"])));
		$store_name = "";
		$user_stores = array();
		$user_asm = array();

			if (!empty($data["id_PDG"])) {
				$user_stores = $this->get_user_store_post((string)$store_info["_id"]);
				$user_asm = $this->get_user_asm_post((string)$store_info["_id"]);
			}
			$user_all = array_merge($user_stores, $user_asm);

			if (!empty($store_info)) {
				$store_name = $store_info["name"];
			}
	   }

		$data['updated_at'] = (int)$data['updated_at'];
		$data['phone_number'] = $leadDB['phone_number'];
		if (!empty($data['thoi_gian_khach_hen'])){
			$data['thoi_gian_khach_hen'] = strtotime($data['thoi_gian_khach_hen']);
			$data['status_thoi_gian_khach_hen'] = "1";
			if (!empty($leadDB['thoi_gian_khach_hen']) && $leadDB['thoi_gian_khach_hen'] == $data['thoi_gian_khach_hen']){
				$data['status_thoi_gian_khach_hen'] = "2";
			}
		}
		if ($leadDB['source'] == "VM") {
			$data['source'] = "VM";
		}
		if (isset($leadDB['created_at'])) {
			$data['created_at'] = $leadDB['created_at'];
		}

		if (isset($leadDB['qualified'])) {
			if ($data['qualified'] != $leadDB['qualified'] && $data['status_sale'] != "2" && $data['qualified'] == "1") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Chuyển đổi lead qualified cần chuyển trạng thái hẹn đến phòng giao dịch",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {
			if (isset($data['qualified']) && $data['qualified'] == "1" && $data['status_sale'] != "2") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Chuyển đổi lead qualified cần chuyển trạng thái hẹn đến phòng giao dịch",
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

		}
		if (isset($data['status_sale']) && ($data['status_sale'] == "2" || $data['status_sale'] == "9") && !isset($leadDB['office_at'])) {
			$data['office_at'] = $this->createdAt;
		}

		$start = date('Y-m-d');
		$end = date('Y-m-d');

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'),
				"id" => $id
			);
			$lead_log = $this->log_lead_model->findOne_dk($condition);
//			if (empty($lead_log)) {
//				$this->log_lead($data);
//			} else {
//				$this->log_lead_model->update(
//					array("_id" => $lead_log['_id']),
//					array('lead_data' => $data)
//				);
//			}

			$this->log_lead($data);
		}


		$this->log_lead_all($data);
         if($data['status_sale'] == "2" && !empty($data["id_PDG"]) )
        {
        	if(!empty($leadDB['cskh_taivay']))
        	{
               $data['status_pgd']="";
               $data['reason_return']="";
               $data['reason_cancel_pgd']="";
               $data['reason_process']="";
        	}
        }


		//Update lead
		$this->lead_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);

		if (isset($data['assign_to_cskh']) && $data['assign_to_cskh'] == 1) {
			$lead_new = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
			$email_cskh = $lead_new['cskh'];
			if ($lead_new['status_sale'] == 1 && !empty($lead_new['cskh'])) {
				$this->push_notification_to_cskh($email_cskh,$lead_new);
			}
		}

		$groupRoles = $this->getGroupRole($this->id);

		if($data['status_sale'] == "2" && !empty($data["id_PDG"]) && in_array("telesales",$groupRoles))
        {
        	if (empty($leadDB['cvkd'])){
				$this->auto_lead_pgd($data);
			}

        }

		 $dataSocket = array();

		 if ((!empty($user_all) && empty($leadDB['id_PDG'])) || ((!empty($user_all) && $data["id_PDG"] != $leadDB['id_PDG']))) {
			 foreach ($user_all as $user) {
				 if (in_array($user, $user_asm) == true) {
					 $data_notification = [
						 "action_id" => (string)$id,
						 "action" => "lead",
						 "title" => "PGD " . $store_name . " đã nhận được 01 Lead inhouse",
						 "detail" => "lead_custom/list_transfe_office",
						 "note" => $data["fullname"] . ", " . hide_phone($data["phone_number"]),
						 "user_id" => $user,
						 "store_name" => $store_name,
						 "lead_name" => $data["fullname"],
						 "lead_phone_number" => $data["phone_number"],
						 "status" => 1, //1: new, 2: read, 3: block.
						 "position" => "asm",
						 "lead_status" => $data["status_sale"],
						 "created_at" => $this->createdAt,
						 "created_by" => $this->uemail
					 ];
					 $this->notification_model->insertReturnId($data_notification);
					 $find_user_asm = $this->user_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($user)));
					 $email_asm = $find_user_asm['email'];
					 $data_send = array(
						 'code' => "lead_inhouse",
						 "customer_name" => $leadDB['fullname'],
						 "store_name" => $store_name,
						 "note" => !empty($leadDB['tls_note']) ? $leadDB['tls_note'] : "",
						 "API_KEY" => $this->config->item('API_KEY'),
						 "email" => $email_asm,
						 "full_name" => !empty($leadDB['full_name']) ? $leadDB['full_name'] : ""
					 );
					 $this->user_model->send_Email($data_send);
				 } elseif (in_array($user, $user_stores) == true) {
					 $data_notification = [
						 "action_id" => (string)$id,
						 "action" => "lead",
						 "title" => "PGD " . $store_name . " đã nhận được 01 Lead inhouse",
						 "detail" => "lead_custom/list_transfe_office",
						 "note" => $data["fullname"] . ", " . hide_phone($data["phone_number"]),
						 "user_id" => $user,
						 "store_name" => $store_name,
						 "lead_name" => $data["fullname"],
						 "lead_phone_number" => $data["phone_number"],
						 "status" => 1, //1: new, 2: read, 3: block.
						 "position" => "cvkd",
						 "lead_status" => $data["status_sale"],
						 "created_at" => $this->createdAt,
						 "created_by" => $this->uemail
					 ];
					 $this->notification_model->insertReturnId($data_notification);
					 $find_user_gdv = $this->user_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($user)));
					 $email_gdv = $find_user_gdv['email'];
					 $data_send = array(
						 'code' => "lead_inhouse",
						 "customer_name" => $leadDB['fullname'],
						 "store_name" => $store_name,
						 "note" => !empty($leadDB['tls_note']) ? $leadDB['tls_note'] : "",
						 "API_KEY" => $this->config->item('API_KEY'),
						 "email" => $email_gdv,
						 "full_name" => !empty($leadDB['full_name']) ? $leadDB['full_name'] : ""
					 );
					 $this->user_model->send_Email($data_send);
				 }
			 }

			 $dataSendStore = array(
				 "status" => $data["status_sale"],
				 "action_id" => (string)$id,
				 "action" => "lead",
				 "detail" => "lead_custom/list_transfe_office",
				 "title" => "PGD " . $store_name . " đã nhận được 01 Lead inhouse",
				 "note" => $data["fullname"] . ", " . hide_phone($data["phone_number"]),
				 "users" => $user,
				 "created_at" => $this->createdAt,
				 '1' => 1
			 );
			 $dataSocket['approve'] = $dataSendStore;


			 $dataLead = array(
				 "status" => $data["status_sale"],
				 "action_id" => (string)$id,
				 "action" => "lead",
				 "detail" => "lead_custom/list_transfe_office",
				 "title" => "PGD " . $store_name . " đã nhận được 01 Lead inhouse",
				 "note" => $data["fullname"] . ", " . hide_phone($data["phone_number"]),
				 "users" => $user,
				 "created_at" => $this->createdAt
			 );
			 $dataSocket["status"] = $dataLead;
			 $this->transferSocket($dataSocket);
		 }
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update lead success",
			'data' => $data,
			'dataSocket' => $dataSocket
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	 public function cong_tru_lead_pgd($data,$cvkd,$cvkd_old)
	{
		$id_lead=$data['id'];
		$id_pgd=$data['id_PDG'];
		if(empty($id_lead) || empty($id_pgd))
			return;
		$month=date('m');
		$year=date('Y');
	   	$total_lead=0;
	   	$lead_processing=0;
	   	    $cht_id = array(
					'5ea1b686d6612bdf6c0422af' //telesale

			);
			$allusers = $this->getUserbyStores($id_pgd);
			$user_ids_groups = $this->getUserIDGroupRole($cht_id);
			$data_user = array_diff($allusers, $user_ids_groups);

	      foreach ($data_user as $key => $value) {

	      	$user = $this->user_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value)]);
	      	$store = $this->store_model->findOne(["_id" => new MongoDB\BSON\ObjectId($id_pgd)]);
	      	$ckau=$this->auto_lead_pgd_model->findOne(['store.id'=>$id_pgd,'month'=>$month,'year'=>$year,'cvkd'=>$user['email']]);
	      	if(empty($ckau))
	      	{
	      	$data_in_auto=[
	      		'store'=>['id'=>(string)$store['_id'],'name'=>$store['name'],'code_area'=>$store['code_area']],
	      		'cvkd'=>$user['email'],
	      		'month'=>$month,
	      		'year'=>$year,
	      		'total_lead'=>0,
	      		'lead_processing'=>0,
	      		'lead_processed'=>0,
	      		'lead_cancel'=>0,
	      		'created_at'=>$this->createdAt,
	      		'created_by'=>'system',
	      	];
	      	$this->auto_lead_pgd_model->insert($data_in_auto);
	       }
              }
              if(!empty($cvkd))
              {
			$user_pgd=$this->auto_lead_pgd_model->get_user_pgd(['store.id'=>$id_pgd,'month'=>$month,'year'=>$year,'cvkd'=>$cvkd])[0];

		     if(empty($user_pgd))
			   return;

		        $total_lead=$user_pgd['total_lead']+1;

               	$data_up_au=[
					'total_lead'=>(int)$total_lead,

				];
				$this->auto_lead_pgd_model->update(
						array("_id" => $user_pgd['_id']),
						$data_up_au
					);

                $data['type_chage']='+';
                $data['cvkd']=$user_pgd_old['cvkd'];
				$data['total_lead']=$total_lead;
				$this->log_lead_pgd($data);
			}
			 if(!empty($cvkd_old))
              {
			$user_pgd_old=$this->auto_lead_pgd_model->get_user_pgd(['store.id'=>$id_pgd,'month'=>$month,'year'=>$year,'cvkd'=>$cvkd_old])[0];

		     if(empty($user_pgd_old))
			   return;

		        $total_lead_old=$user_pgd_old['total_lead']-1;

               	$data_up_au_old=[
					'total_lead'=>(int)$total_lead_old,

				];
				$this->auto_lead_pgd_model->update(
						array("_id" => $user_pgd_old['_id']),
						$data_up_au_old
					);

                $data['type_chage']='-';
                $data['cvkd']=$user_pgd_old['cvkd'];
				$data['total_lead']=$total_lead;
				$this->log_lead_pgd($data);
			}


		        return;

	}
    public function auto_lead_pgd($data)
	{
		$id_lead=$data['id'];
		$id_pgd=$data['id_PDG'];
		if(empty($id_lead) || empty($id_pgd))
			return;
		$month=date('m');
		$year=date('Y');
	   	$total_lead=0;
	   	$lead_processing=0;
	   	$cht_id = array(
				'5ea1b686d6612bdf6c0422af' //telesale
			);
			$allusers = $this->getUserbyStores($id_pgd);
			$user_ids_groups = $this->getUserIDGroupRole($cht_id);
			$data_user = array_diff($allusers, $user_ids_groups);
        // var_dump($data_user); die;
          foreach ($data_user as $key => $value) {

          	$user = $this->user_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value)]);
          	$store = $this->store_model->findOne(["_id" => new MongoDB\BSON\ObjectId($id_pgd)]);
          	$ckau=$this->auto_lead_pgd_model->findOne(['store.id'=>$id_pgd,'month'=>$month,'year'=>$year,'cvkd'=>$user['email']]);

			$groupRoles = $this->getGroupRole($value);

          	if(empty($ckau))
          	{
          	$data_in_auto=[
          		'store'=>['id'=>(string)$store['_id'],'name'=>$store['name'],'code_area'=>$store['code_area']],
          		'cvkd'=>$user['email'],
          		'month'=>$month,
          		'year'=>$year,
          		'total_lead'=>0,
          		'lead_processing'=>0,
          		'lead_processed'=>0,
          		'lead_cancel'=>0,
          		'created_at'=>$this->createdAt,
          		'created_by'=>'system',
          	];
          	if(!in_array('telesales', $groupRoles) && (in_array('cua-hang-truong', $groupRoles)  || in_array('giao-dich-vien', $groupRoles)))
          	$this->auto_lead_pgd_model->insert($data_in_auto);
           }

          }
		$user_pgd=$this->auto_lead_pgd_model->get_user_pgd(['store.id'=>$id_pgd,'month'=>$month,'year'=>$year])[0];

	     if(empty($user_pgd))
		   return;

	        $total_lead=$user_pgd['total_lead']+1;

           	$data_up_au=[
				'total_lead'=>(int)$total_lead,
			];
			$this->auto_lead_pgd_model->update(
					array("_id" => $user_pgd['_id']),
					$data_up_au
				);
            $data_div=[
            	'cvkd'=>$user_pgd['cvkd'],
            ];
             $data['cvkd']=$user_pgd['cvkd'];
			$data['total_lead']=$total_lead;
			$data['type_chage']='+';
			$this->log_lead_pgd($data);
			$this->lead_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($id_lead)),
				$data_div
			);

	        return;

	}
	public function update_off_at_post()
	{

		$data_lead = $this->lead_model->find();
		foreach ($data_lead as $key => $value) {
			if ($value['status_sale'] == "2" && !isset($leadDB['office_at'])) {

				$this->lead_model->update(
					array("_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])),
					array("office_at" => (int)$value['created_at'])
				);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "OK"
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function create_lead_pgd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['office_at'] = $this->createdAt;
		$data['created_at'] = $this->createdAt;

		$lead = $this->lead_model->findOne_create(array("phone_number" => $data['phone_number']), array('5', '6', '10', '11', '12', '13', '14', '15', '16', '17', '18'));

		$stores = $this->getStores($this->id);
		$data['id_PDG'] = isset($stores[0]) ? $stores[0] : '';
		if (empty($lead)) {
			$groupRoles = $this->getGroupRole($this->id);

		if (in_array('giao-dich-vien', $groupRoles)) {
			$data['cvkd'] =$data['created_by'];
			$id_l=$this->lead_model->insertReturnId($data);
			$data_l['id']=(string)$id_l;
			$data_l=$this->lead_model->findOne(['_id'=> $id_l]);
			$this->cong_tru_lead_pgd($data_l,$data['cvkd'],'');
		}else{
		  $id_l=$this->lead_model->insertReturnId($data);
		  $data_l=$this->lead_model->findOne(['_id'=> $id_l]);
		  $data_l['id']=(string)$id_l;
		  $this->auto_lead_pgd($data_l);
		}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Tạo mới thành công",
				'data' => $data
			);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tạo mới thất bại,lead trong các trạng thái chưa xử lý dứt điểm",
				'data' => $data
			);

		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_pgd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->lead_model->find();

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "OK",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_check_phone_source_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();

		$current_time = $this->createdAt;

		$dataCheck = $this->lead_model->find_one_check_phone($data['phone_number_source']);

		$condition = [];
		$condition['customer_phone_number'] = $data['phone_number_source'];


		$check_khtv = $this->contract_model->findOne_code_contract($data['phone_number_source']);

		if (!empty($check_khtv)) {

			$check_transaction = $this->transaction_model->findOne(["code_contract" => $check_khtv[0]['code_contract'], 'type' => 3, 'status' => 1]);

			if (!empty($check_transaction)) {

				$so_ngay_check = intval(($current_time - $check_transaction['date_pay']) / (24 * 60 * 60));
				if ($so_ngay_check > 7) {
					$response = array(
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Không thành công",
						'data' => $dataCheck[0],

					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {
					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Thành công",
						'data' => $dataCheck[0],
						'time' => $so_ngay_check
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				}
			}
		}


		if (!empty($dataCheck[0])) {

			$so_ngay_tt = intval(($current_time - $dataCheck[0]['created_at']) / (24 * 60 * 60));

			if (!empty($dataCheck[0]['source'])) {

				if ($dataCheck[0]['source'] == "9" || $dataCheck[0]['source'] == "11" || $dataCheck[0]['source'] == "10") {

					if ($so_ngay_tt > 180) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Không thành công",
							'data' => $dataCheck[0],

						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}

				if ($dataCheck[0]['source'] == "8") {
					if ($so_ngay_tt > 90) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Không thành công",
							'data' => $dataCheck[0],

						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}

			}

			if ($dataCheck[0]['source'] != "8" && $dataCheck[0]['source'] != "9" && $dataCheck[0]['source'] != "11" && $dataCheck[0]['source'] != "10") {

				if ($dataCheck[0]['source'] == "16") {

					if ($so_ngay_tt > 180) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Không thành công",
							'data' => $dataCheck[0],
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}

				if ($dataCheck[0]['source'] != "16") {
					if ($so_ngay_tt > 90) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Không thành công",
							'data' => $dataCheck[0],

						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Thành công",
				'data' => $dataCheck[0],
				'time' => $so_ngay_tt
			);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không thành công",
				'data' => $dataCheck[0],
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_check_identify_source_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$dataCheck = $this->lead_model->findOne(['customer_identity_card' => $data['customer_identify']]);
		if (!empty($dataCheck)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Thành công",
				'data' => $dataCheck
			);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không thành công",
				'data' => $dataCheck
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function get_one_lead_pgd_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$dataCheck = $this->input->post();

		$condition = !empty($dataCheck['phone_number']) ? $dataCheck['phone_number'] : array();

		$data = $this->lead_model->findOne(['phone_number' => $condition]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "OK",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function create_lead_post()
	{
		date_default_timezone_set("Asia/Ho_Chi_Minh");
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['created_at'] = $this->createdAt;
		$lead = $this->lead_model->findOne_create(array("phone_number" => $data['phone_number']), array('5', '6', '10', '11', '12', '13', '14', '15', '16', '17', '18'));
		if (empty($lead)) {
			$id_return = $this->lead_model->insertReturnId($data);
			$lead_new = $this->lead_model->findOne(array("_id" => $id_return));
			$email_cskh = $lead_new['cskh'];
			// if ($lead_new['status_sale'] == 1 && !empty($lead_new['cskh'])) {
			// 	$this->push_notification_to_cskh($email_cskh,$lead_new);
			// }
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Tạo mới thành công",
				'data' => $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		    return;

		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tạo mới thất bại,lead trong các trạng thái chưa xử lý dứt điểm",
				'data' => $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		    return;

		}


	}

	public function import_lead_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['created_at'] =$this->createdAt;
		$data['created_by'] =$this->uemail;
		$condition=array("phone_number" => $data['phone_number']);
		 $condition['created_at'] = array(
                '$gte' => strtotime("-30 day",$this->createdAt) ,
                '$lte' =>$this->createdAt
            );
		$lead = $this->lead_model->findOne($condition);
		if (empty($lead)) {
			$this->lead_model->insert($data);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Import lead success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function updateStatusCall($old, $new)
	{
		//Check status
		if ($old['status'] == $new['status']) return;
		$dashboard = $this->dashboard_model->find();
		$countNotCall = $dashboard[0]['lead_customer']['not_call'];
		$countCalled = $dashboard[0]['lead_customer']['called'];
		$dataUpdate = array();
		//Old = chưa gọi và New = đã gọi
		if ($old['status'] == 1) {
			$dataUpdate = array(
				"lead_customer.not_call" => $countNotCall - 1,
				"lead_customer.called" => $countCalled + 1
			);
		} //Old = đã gọi và New = chưa gọi
		else {
			$dataUpdate = array(
				"lead_customer.called" => $countCalled - 1,
				"lead_customer.not_call" => $countNotCall + 1
			);
		}
		$this->dashboard_model->update(
			array("_id" => $dashboard[0]['_id']),
			$dataUpdate
		);
	}

	private function updateConfirmDisburse($old, $new)
	{
		if (empty($new['reason_2']) || $old['reason_2'] == $new['reason_2']) return;
		$dashboard = $this->dashboard_model->find();
		$count = $dashboard[0]['lead_customer']['confirm_disburse'];
		//Old = đã chốt và new = không chốt
		if ($old['reason_2'] == 2 || $old['reason_2'] == 3) {
			$count--;
		} else {
			$count++;
		}
		$this->dashboard_model->update(
			array("_id" => $dashboard[0]['_id']),
			array("lead_customer.confirm_disburse" => $count)
		);
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

	//báo cáo tổng hợp
	public function mkt_report_general_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$lead = array();
		$lead = $this->lead_model->find_where(array("status" => array('$ne' => 100)));

		$arr_data_QC_nguon = lead_nguon();
		$arr_data_QC_SOU = [];
		$arr_data_QC_CAM = [];
		$arr_return_QC_SOU = [];
		$arr_return_QC_CAM = [];
		$arr_return_QC_nguon = [];
		$arr_phone = [];
		$html = "";
		if (!empty($lead)) {

			foreach ($lead as $key => $value) {
				if (!empty($value['utm_source']))
					$arr_data_QC_SOU += [$key => $value['utm_source']];
				if (!empty($value['utm_campaign']))
					$arr_data_QC_CAM += [$key => $value['utm_campaign']];
			}
			$ng = 0;
			if (!empty($arr_data_QC_nguon))
				foreach (array_unique($arr_data_QC_nguon) as $key => $value) {
					$ng++;

					$arr_phone = get_values($lead, 'source', $key, '', '', '', '', 'phone_number', true, false);

					if (empty($arr_phone))
						$arr_phone = ['1'];
					$arr_return_QC_nguon += [$ng => [
						'source' => $value,
						'total_lead_all' => count_values($lead, 'source', $key, '', '', '', ''),
						'total_lead_qualified' => count_values($lead, 'source', $key, 'qualified', '1', '', ''),
						'total_contract_disbursement' => $this->contract_model->count_in("customer_infor.customer_phone_number", $arr_phone),
						'total_debt' => 0

					]];
				}

			$sou = 0;
			if (!empty($arr_data_QC_SOU))
				foreach (array_unique($arr_data_QC_SOU) as $key => $value) {
					$sou++;
					$arr_phone = get_values($lead, 'utm_source', $value, '', '', '', '', 'phone_number', true, false);

					if (!empty($arr_phone))
						$arr_phone = ['1'];
					$arr_return_QC_SOU += [$sou => [
						'utm_source' => $value,
						'total_lead_all' => count_values($lead, 'utm_source', $value, '', '', '', ''),
						'total_lead_qualified' => count_values($lead, 'utm_source', $value, 'qualified', '1', '', ''),
						'total_contract_disbursement' => $this->contract_model->count_in("customer_infor.customer_phone_number", $arr_phone),
						'total_debt' => 0
					]];
				}
			//   var_dump($arr_data_QC_SOU) ; die;
			$cam = 0;
			if (!empty($arr_data_QC_SOU) && !empty($arr_data_QC_CAM))
				foreach (array_unique($arr_data_QC_SOU) as $key1 => $value1) {
					foreach (array_unique($arr_data_QC_CAM) as $key => $value) {

						$arr_phone = get_values($lead, 'utm_campaign', $value, 'utm_source', $value1, '', '', 'phone_number', true, false);
						if (!empty($arr_phone)) {
							$arr_phone = ['1'];
							$cam++;
							$arr_return_QC_CAM += [$cam => [
								'utm_campaign' => $value,
								'utm_source' => $value1,
								'total_lead_all' => count_values($lead, 'utm_campaign', $value, 'utm_source', $value1, '', ''),
								'total_lead_qualified' => count_values($lead, 'utm_campaign', $value, 'qualified', '1', 'utm_source', $value1),
								'total_contract_disbursement' => $this->contract_model->count_in("customer_infor.customer_phone_number", $arr_phone),
								'total_debt' => 0,

							]];
						}
					}
				}

			$html = gen_html_QC($arr_return_QC_nguon, $arr_return_QC_SOU, $arr_return_QC_CAM);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $html
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}
    private function getUserIDGroupRole($GroupIds)
	{
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = key($item);
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}
	private function getUserGroupRole($GroupIds)
	{
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		//5ea1b6d2d6612b65473f2b68 marketing
		//5ea1b6abd6612b6dd20de539 thu-hoi-no
		//5ea1b686d6612bdf6c0422af telesales
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			// if(isset(($groups['users']))) {
			foreach ($groups['users'] as $item) {
				$arr[] = $item;
			}
		}


		return $arr;
	}

	private function getStoresbyprovi($provi_id)
	{
		$stores = $this->store_model->find_where(array("status" => "active", "province_id" => $provi_id));
		$rStores = array();
		if (count($stores) > 0) {
			foreach ($stores as $store) {
				if (!empty($store['province_id'])) {
					array_push($rStores, (string)$store['_id']);

				}
			}
		}
		return $rStores;
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

	private function getUserbyStores($storeId)
	{
			$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['stores']) && count($role['stores']) == 1) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if (in_array($storeId, $arrStores) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['users'] as $key => $item) {
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	public function log_lead($data)
	{
		$id = !empty($data['id']) ? $data['id'] : "";
		$lead = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$lead['id'] = (string)$lead['_id'];
		unset($lead['_id']);
		$data['phone_number'] = $lead['phone_number'];

		if ($data['status_sale'] == $lead['status_sale']){
			$lead['tls_note'] = $data['tls_note'];
		}

		$dataInser = array(
			"lead_data" => $data,
			"old_data" => $lead,
			"type" => 'lead',

		);
		$this->log_lead_model->insert($dataInser);
	}

	public function log_lead_all($data)
	{
		$id = !empty($data['id']) ? $data['id'] : "";
		$lead = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$lead['id'] = (string)$lead['_id'];
		unset($lead['_id']);
		$data['phone_number'] = $lead['phone_number'];
		$dataInser = array(
			"lead_data" => $data,
			"old_data" => $lead,
			"type" => 'lead'

		);
		$this->log_model->insert($dataInser);
	}
    public function log_lead_pgd($data)
	{
		$id = !empty($data['id']) ? $data['id'] : "";
		$lead = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$lead['id'] = (string)$lead['_id'];
		unset($lead['_id']);
		$data['phone_number'] = $lead['phone_number'];
		$dataInser = array(
			"old" => $data,
			"new" => $lead,
			"cvkd" => $data['cvkd'],
			"type_chage" => $data['type_chage'],
			"type" => 'chia_lead_pgd'

		);
		$this->log_lead_pgd_model->insert($dataInser);
	}
	public function get_lead_log_html_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$lead = $this->lead_model->findOne(array('_id' => new \MongoDB\BSON\ObjectId($id)));
		$lead_log = $this->log_lead_model->leadLogHistory(array("phone_number" => $lead["phone_number"]));
		$group_role = $this->getGroupRole_gdv();
		$group_role_tls = $this->getGroupRole_tls();
		$html = gen_html_lead_history($lead_log, $group_role, $group_role_tls);
		$response = array(
			"status" => REST_Controller::HTTP_OK,
			"html" => $html,
			"data" => $lead["phone_number"]
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function lead_cancel_daily_post()
	{
		date_default_timezone_set("Asia/Ho_Chi_Minh");
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = !empty($data["condition"]) ? $data["condition"] : '';
		$start = !empty($data["start"]) ? $data["start"] : date('Y-m-d');
		$end = !empty($data["end"]) ? $data["end"] : date('Y-m-d');
		if (!empty($start) && !empty($end)) {
			$condition = array(
				"start" => strtotime(trim($start)),
				"end" => strtotime(trim($end))
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array("super-admin", $groupRoles) || $this->superadmin || in_array("van-hanh", $groupRoles) || in_array("hoi-so", $groupRoles) || in_array("ke-toan", $groupRoles) || in_array("marketing", $groupRoles)) {
			$all = true;
		}
		if (!empty($condition)) {
			$lead_log = $this->lead_model->getLeadLogCancel($condition);
		} else {
			$lead_log = $this->lead_model->find();
		}


		$timestamp = strtotime($start);
		$start = date('d-m-Y H:i:s', $timestamp);
		$timestamp = strtotime($end);
		$end = date('d-m-Y H:i:s', $timestamp);
		$daily = 'Từ ' . $start . ' đến ' . $end;

		$arr_data_reason = reason();
		$arr_return_reason = [];
		$total_lead_cancel = 0;
		$total_lead = 0;
		$lead_new = [];
		$html = "";

		$n = 0;

		if (!empty($lead_log)) {
			foreach ($lead_log as $key => $value) {
				$n++;
				$lead_new += [$n => $value];
				if (!empty($value['reason_cancel'])) ;
				$total_lead_cancel++;
				$total_lead++;
			}
		}
		$sou = 0;

		if (!empty($arr_data_reason))
			foreach (array_unique($arr_data_reason) as $key => $value) {
				$sou++;
				if (count_values($lead_new, 'reason_cancel', $key, 'status', 2) > 0)
					$arr_return_reason += [$sou => [
						'daily' => $daily,
						'reason' => $value,
						'total_lead_cancel' => $total_lead_cancel,
						'total_lead' => $total_lead,
						'lead_cancel' => count_values($lead_new, 'reason_cancel', $key, 'status', 2)
					]];
			}

		$html = gen_html_report_lead_reason_cancel_daily($arr_return_reason);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $html
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function create_lead_extra_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		$this->lead_extra_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Tạo mới thành công",

		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function update_cskh_lead_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['created_at'] = $this->createdAt;

		$end = strtotime('+1 day', strtotime(date('Y-m-d', (int)$this->createdAt)));
		$start = strtotime('-15 day', strtotime(date('Y-m-d', (int)$this->createdAt)));

		$start1 = date('Y-m-d', $start);
		$end1 = date('Y-m-d', $end);

		if (!empty($start1) && !empty($end1)) {
			$condition = array(
				'start' => strtotime(trim($start1)),
				'end' => strtotime(trim($end1))
			);
		}


		$data['list_homedy'] = "tuanhva@tienngay.vn,huyenntt@tienngay.vn,loanntp@tienngay.vn";


		$this->cskh_insert_model->insert($data);

		$data1 = $this->lead_model->find_date($condition);

		$leadDB1 = [];

		if (!empty($data1)) {
			foreach ($data1 as $value) {
				if (empty($value['cskh']) || $value['cskh'] == "" || $value == "undefined"){
					array_push($leadDB1, $value);
				}
			}
		}
		$count = 0;
		$list_cskh = explode(',', $data['list_cskh']);
		if (count($leadDB1) != 0 && count($list_cskh) != 0) {
			for ($i = 0, $j = 0; $i < count($leadDB1), $j < count($list_cskh); $i++, $j++) {

				$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($leadDB1[$i]['_id'])), array('cskh' => $list_cskh[$j], 'updated_at' => $this->createdAt, 'updated_by' => $data['updated_by']));
				$count++;

				if ($j == count($list_cskh) - 1) {
					$j = -1;

				}
				if ($count == count($leadDB1)) {
					break;
				}
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update lead success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}



	public function update_lead_cskh_day_post()
	{

		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$start = date('Y-m-d ');
		$end = date('Y-m-d');

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 6:00:00'),
				'end' => strtotime(trim($end) . ' 17:30:00'));
		}

		$data = $this->lead_model->find_date_day($condition);

		$cskh_total = $this->cskh_insert_model->find_date_day($condition);

		if (!empty($cskh_total)) {
			$data2 = [];
			foreach ($cskh_total as $item) {
				array_push($data2, $item['list_cskh']);
			}
			$list_cskh_1 = implode(",", $data2);
		}

		if (!empty($list_cskh_1)) {
			$cskh = explode(',', $list_cskh_1);
		}

		$leadDB1 = [];

		$arr1 = [];
		$total1 = [];

		if (!empty($data)) {
			foreach ($data as $value) {
				if (empty($value['cskh']) || $value['cskh'] == "" || $value['cskh'] == "undefined") {
					array_push($leadDB1, $value);
				}
				if (!empty($value['cskh'])) {
					foreach ($cskh as $key => $item) {
						if (!in_array($item, $arr1, true)) {
							array_push($arr1, $item);
							array_push($total1, 0);
						}

						if ($value['cskh'] == $item) {
							$condition['cskh'] = $value['cskh'];
							$count1 = $this->lead_model->find_date_day_count($condition);
							array_push($arr1, $value['cskh']);
							array_push($total1, $count1);
							break;
						}
					}
				}

			}
		}

		$arr = array_combine($arr1, $total1);

//		asort($arr);

		$cskh_del = $this->cskh_del_model->find_cskh_del_day($condition);

		if (!empty($cskh_del)) {
			$cskh_del1 = [];
			foreach ($cskh_del as $item) {
				array_push($cskh_del1, $item['list_cskh_del']);
			}
			$list_cskh_del = implode(",", $cskh_del1);
		}

		if (!empty($list_cskh_del)) {
			$list_cskh_del_1 = explode(',', $list_cskh_del);
		}

		$list_cskh = [];
		foreach ($arr as $key => $value) {
			array_push($list_cskh, $key);
		}

		$count = 0;

		if (!empty($list_cskh_del_1)) {
			for ($i = count($list_cskh); $i >= 0; $i--) {
				for ($j = 0; $j < count($list_cskh_del_1); $j++) {
					if ($list_cskh[$i] == $list_cskh_del_1[$j]) {
						array_splice($list_cskh, $i, 1);
					}
				}
			}
		}

		if (!empty($list_cskh)){
			$condition['list_cskh'] = $list_cskh;
		}
		$customer_cskh = $this->lead_model->find_one($condition);

		if (!empty($customer_cskh[0]->cskh)){
			foreach ($list_cskh as $key => $list){
				if ($list == $customer_cskh[0]->cskh){
					$stt = $key+1;
				}
			}
		}

		if (empty($stt) || $stt == count($list_cskh)){
			$stt = 0;
		}


		if (count($leadDB1) != 0 && count($list_cskh) != 0) {
			for ($i = 0, $j = $stt; $i < count($leadDB1), $j < count($list_cskh); $i++, $j++) {

				$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($leadDB1[$i]['_id'])), array('cskh' => $list_cskh[$j], 'updated_at' => $this->createdAt));

				$count++;

				if ($j == count($list_cskh) - 1) {
					$j = -1;
				}

				if ($count == count($leadDB1)) {
					break;
				}

			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update lead success",

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}




	public function insert_cskh_del_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		$data['created_at'] = $this->createdAt;

		$this->cskh_del_model->insert($data);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data
		);


		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_cskh_lead_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$start = date('Y-m-d');
		$end = date('Y-m-d');

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 6:00:00'),
				'end' => strtotime(trim($end) . ' 17:30:00'));
		}


		$data = $this->cskh_insert_model->get_find_cskh($condition);

		if (!empty($data)) {
			$data1 = [];
			foreach ($data as $item) {
				array_push($data1, $item['list_cskh']);
			}
			$list_cskh = implode(",", $data1);
		}


		if (!empty($list_cskh)) {
			$list_cskh_1 = explode(',', $list_cskh);
		}

		$cskh_del = $this->cskh_del_model->find_cskh_del_day($condition);

		if (!empty($cskh_del)) {
			$cskh_del1 = [];
			foreach ($cskh_del as $item) {
				array_push($cskh_del1, $item['list_cskh_del']);
			}
			$list_cskh_del = implode(",", $cskh_del1);
		}

		if (!empty($list_cskh_del)) {
			$list_cskh_del_1 = explode(',', $list_cskh_del);
		}

		if (!empty($list_cskh_del_1)) {
			for ($i = count($list_cskh_1); $i >= 0; $i--) {
				for ($j = 0; $j < count($list_cskh_del_1); $j++) {
					if ($list_cskh_1[$i] == $list_cskh_del_1[$j]) {
						array_splice($list_cskh_1, $i, 1);
					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => array_unique($list_cskh_1)
		);


		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function get_lead_excel_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$condition = !empty($data['condition']) ? $data['condition'] : array();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$area = !empty($data['area']) ? $data['area'] : "";
		$code_store = !empty($data['code_store']) ? $data['code_store'] : "";
		$status_sale = !empty($data['status_sale']) ? $data['status_sale'] : "";
		$utm_source = !empty($data['utm_source']) ? $data['utm_source'] : "";
		$utm_campaign = !empty($data['utm_campaign']) ? $data['utm_campaign'] : "";
		$phone_number = !empty($data['phone_number']) ? $data['phone_number'] : "";
		$status_pgd = !empty($data['status_pgd']) ? $data['status_pgd'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('marketing', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		}

		if (!empty($code_store)) {
			$condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
		}

		$stores = array();
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'data' => $stores,
					'total' => 0,
					'groupRoles' => $groupRoles,
					'stores' => $stores
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}

			$condition['code_store'] = $stores;

		}
		if (!empty($status_sale)) {
			$condition['status_sale'] = $status_sale;
		}
		if (!empty($utm_source)) {
			$condition['utm_source'] = $utm_source;
		}
		if (!empty($utm_campaign)) {
			$condition['utm_campaign'] = $utm_campaign;
		}
		if (!empty($phone_number)) {
			$condition['phone_number'] = $phone_number;
		}
		if (!empty($status_pgd)) {
			$condition['status_pgd'] = $status_pgd;
		}
		if (!empty($area)) {
			$id_PDG = $this->getStoresbyprovi($area);
			$condition['id_PDG'] = $id_PDG;
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 4000;

		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$leads = $this->lead_model->getByRole_pgd($condition, $per_page, $uriSegment);

		$leadTotal = $this->lead_model->getByRole_pgd($condition);
		foreach ($leadTotal as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}

		$leadTotalMkt = 0;
		for ($i = 0; $i < count($leadTotal); $i++) {
			$leadTotalMkt += (int)$leadTotal[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		foreach ($leads as $value) {
			$id_lead = (string)$value->_id;
			$contractInfo = $this->contract_model->find_one_select(['customer_infor.id_lead' => $id_lead], ['status', 'loan_infor.amount_loan']);
			$value->contractInfo = $contractInfo;
		}


		$condition['total'] = true;
		$total = $this->lead_model->getByRole_pgd($condition);
		foreach ($leads as $key => $value) {
			$contract = $this->contract_model->findOne(array('customer_infor.id_lead' => (string)$value['_id']));
			if (!empty($contract)) {
				$value['status_contract'] = $contract['status'];
			}
		}
		$leadTotalMkt1 = 0;
		for ($i = 0; $i < count($leads); $i++) {
			$leadTotalMkt1 += (int)$leads[$i]['contractInfo']['loan_infor']['amount_loan'];
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $leads,
			'total' => $total,
			'groupRoles' => $groupRoles,
			'stores' => $stores,
			"leadTotalMkt" => $leadTotalMkt,
			"leadTotalMkt1" => $leadTotalMkt1
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function update_getAllListAT_post()
	{

		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$this->dataPost = $this->input->post();
		$this->dataPost['id'] = !empty($this->security->xss_clean($this->dataPost['id'])) ? $this->security->xss_clean($this->dataPost['id']) : "";


		$leadData = $this->lead_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));


		if ($leadData['utm_source'] == "accesstrade" || $leadData['utm_source'] == "google") {

			if ($leadData['status_sale'] == 2) {
				$data1 = array(
					"transaction_id" => !empty($leadData['_id']) ? (string)$leadData['_id'] : "",

					"status" => 1,

					"items" => []

				);

				$data_string = json_encode($data1);

				$ch = curl_init('https://api.accesstrade.vn/v1/postbacks/conversions');

				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_HTTPHEADER, array(

					'Content-Type: application/json',

					'Authorization: Token fn1-vtdKGhR3afT1eJ3qw3XS9N3yv78K'

				));
				$result = curl_exec($ch);
//				var_dump($result);

				//insert log
				$this->log_accesstrade_model->insert($data1);
			}


			if ($leadData['status_sale'] == 19) {

				foreach (reason() as $key => $item) {
					if ($key == (int)$leadData['reason_cancel']) {
						$lead_cancel1_C = $item;
					}
				}

				$data1 = array(
					"transaction_id" => !empty($leadData['_id']) ? (string)$leadData['_id'] : "",
					"status" => 2,
					"rejected_reason" => $lead_cancel1_C,
					"items" => []

				);

				$data_string = json_encode($data1);

				$ch = curl_init('https://api.accesstrade.vn/v1/postbacks/conversions');

				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_HTTPHEADER, array(

					'Content-Type: application/json',

					'Authorization: Token fn1-vtdKGhR3afT1eJ3qw3XS9N3yv78K'

				));

				$result = curl_exec($ch);
//				var_dump($result);

				//insert log
				$this->log_accesstrade_model->insert($data1);

			}
		}

		if ($leadData['utm_source'] == "masoffer") {

			if ($leadData['status_sale'] == 2) {
				$api_key = "9Tprs9wMJ4q2Q7lB";
				$transaction_id_masoffer = (string)$leadData['_id'];
				$click_id_masoffer = !empty($leadData['click_id_masoffer']) ? $leadData['click_id_masoffer'] : "";

				$url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=forced_update&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=1&product_category_id=CPQL";

				$ch = curl_init($url);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

				curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

				$result = curl_exec($ch);

				curl_close($ch);

				echo $result;


			}


			if ($leadData['status_sale'] == "19") {

//				foreach (reason() as $key => $item) {
//					if ($key == (int)$leadData['reason_cancel']) {
//						$lead_cancel1_C = $key;
//					}
//				}

				$api_key = "9Tprs9wMJ4q2Q7lB";
				$transaction_id_masoffer = (string)$leadData['_id'];
				$click_id_masoffer = !empty($leadData['click_id_masoffer']) ? $leadData['click_id_masoffer'] : "";

				$url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=cpl_standard_postback&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=-1&status_message=cancel";

				$ch = curl_init($url);

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

				curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

				$result = curl_exec($ch);

				curl_close($ch);

				echo $result;



			}
		}

		if ($leadData['utm_source'] == "Dinos"){
			if ($leadData['status_sale'] == "2") {
				$click_id_dinos = !empty($leadData['click_id_dinos']) ? $leadData['click_id_dinos'] : "";
				$status = "pending";
				$this->api_dinos($click_id_dinos, $status);
			}

		}

	}

	private function api_dinos($click_id, $status){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.dinos.vn/api/v1/post_back_campaign_redirect?click_id=$click_id&status=$status",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;


	}

	private function transferSocket($data)
	{
		$version = new Version2X($this->config->item("IP_SOCKET_SERVER"));
		$dataNotify = $data["status"];
		if (!empty($data['approve'])) {
			$dataApprove['res'] = $data['approve'];
		}
		try {
			$client = new Client($version);
			$client->initialize();
			$client->emit('notify_status', $dataNotify);
			if (!empty($dataApprove)) {
				$client->emit('notify_approve', $dataApprove);
			}
			$client->close();
		} catch (Exception $e) {

		}
	}

	public function get_user_store_post($store_id)
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		$i = 0;
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && !empty($role['stores'])) {
				$data[$i]['users'] = $role['users'];
				$data[$i]['stores'] = $role['stores'];
				$i++;
			}
		}
		foreach ($data as $da) {
			foreach ($da['stores'] as $d) {
				$storeId = [];
				foreach ($d as $k => $v) {
					array_push($storeId, $k);
				}
			if (in_array($store_id, $storeId) == true) {
				if (count($da['stores']) > 1) {
						continue;
					}
					$user_id = [];
				foreach ($da['users'] as $ds) {
					foreach ($ds as $k => $v) {
							array_push($user_id, $k);
						}
					}
				}
			}
		}
		return $user_id;
	}
	private function get_user_asm_post($id)
	{

		$roles = $this->role_model->findAsm();
		$data = [];
		$i = 0;
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && !empty($role['stores'])) {
				$data[$i]['users'] = $role['users'];
				$data[$i]['stores'] = $role['stores'];
				$i++;
			}
		}
		foreach ($data as $da) {
			foreach ($da['stores'] as $d) {
				$storeId = [];
				$storeName = [];
				foreach ($d as $k => $v) {
					array_push($storeId, $k);
					array_push($storeName, $v['name']);
				}
				if (in_array($id, $storeId) == true) {
					$user_id = [];
					foreach ($da['users'] as $d) {
						foreach ($d as $k => $v) {
							array_push($user_id, $k);
						}
					}
				}

			}
		}
		return $user_id;
	}
		public function get_email_tbp_telesale()
	{
		$user_tbp_tls = $this->group_role_model->findOne(array('slug' => 'tbp-cskh'));
		$tbp_tls = [];
		foreach ($user_tbp_tls['users'] as $key => $users) {
			foreach ($users as $k => $user ) {
				foreach ($user as $value) {
					$tbp_tls[] = $value;
				}
			}
		}
		return $tbp_tls;
	}

	public function get_email_tls()
	{
		$user_tls = $this->group_role_model->findOne(array('slug' => 'telesales'));

		$tls = [];
		foreach ($user_tls as $users) {
			foreach ($users as $user) {
				foreach ($user as $value) {
					foreach ($value as $v) {
						$tls[] = $v;
					}
				}
			}
		}
		return $tls;
	}

	public function get_count_all_200_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";

		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

		$contract_count = $this->contract_model->getCountByRole_200($condition);

		if (empty($contract_count)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract_count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;



	}

	public function get_all_200_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$customer_name = !empty($this->dataPost['customer_name']) ? $this->dataPost['customer_name'] : "";
		$customer_phone_number = !empty($this->dataPost['customer_phone_number']) ? $this->dataPost['customer_phone_number'] : "";
		$customer_identify = !empty($this->dataPost['customer_identify']) ? $this->dataPost['customer_identify'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";

		if (!empty($customer_name)) {
			$condition['customer_name'] = $customer_name;
		}
		if (!empty($customer_phone_number)) {
			$condition['customer_phone_number'] = $customer_phone_number;
		}
		if (!empty($customer_identify)) {
			$condition['customer_identify'] = $customer_identify;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$contract = $this->contract_model->getByRole_200($condition, $per_page, $uriSegment);

		if (empty($contract)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function update_presenter_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['presenter_date'] = strtotime($this->security->xss_clean($this->dataPost['presenter_date']));
		$this->dataPost['presenter_money'] = $this->security->xss_clean($this->dataPost['presenter_money']);
		$this->dataPost['presenter_buttoan'] = $this->security->xss_clean($this->dataPost['presenter_buttoan']);
		$this->dataPost['img_approve'] = $this->security->xss_clean($this->dataPost['img_approve']);


		if (empty($this->dataPost['presenter_date'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày thanh toán không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['presenter_money'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['presenter_buttoan'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bút toán không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$check = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;


		unset($this->dataPost['id']);

		$this->contract_model->update(array("_id" => $check['_id']), ["customer_infor.presenter_date" => $this->dataPost['presenter_date'], "customer_infor.presenter_money" => $this->dataPost['presenter_money'], "customer_infor.presenter_buttoan" => $this->dataPost['presenter_buttoan'], "customer_infor.img_approve" => $this->dataPost['img_approve'], "customer_infor.status_presenter" => 1 ]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update  success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function get_check_phone_ngt_post()
	{
		$data = $this->input->post();
		$status = "Chưa có khoản vay, thanh toán điện nước với VFC";
		$dataCheck = $this->contract_model->findOne(['customer_infor.customer_phone_number' => $data['phone_number']]);
		if (!empty($dataCheck)){
			$status = "Đã có khoản vay với VFC";
		}
		$check = $this->order_model->findOne(['customer_bill_phone' => $data['phone_number']]);

		if (!empty($check)){
			if (!empty($response->error_code) && $response->error_code == "00"){
				$status = "Đã có hóa đơn thanh toán điện nước";
			}
		}

		if (!empty($dataCheck)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Thành công",
				'data' => $status
			);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không thành công",
				'data' => $status
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function lead_qualified_TS_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-1');
		$end = !empty($data['end']) ? $data['end'] : date('Y-m-d');
		$reason_cancel = !empty($data['reason_cancel']) ? $data['reason_cancel'] : "";
		$status = !empty($data['status']) ? $data['status'] : "";
		$tab = !empty($data['tab']) ? $data['tab'] : "";
		$sdt = !empty($data['sdt']) ? $data['sdt'] : "";
		$source = !empty($data['source']) ? $data['source'] : "";
		$fullname = !empty($data['fullname']) ? $data['fullname'] : "";
		$condition = array();
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		if (!empty($reason_cancel)) {
			$condition["reason_cancel"] = $reason_cancel;
		}
		if (!empty($status)) {
			$condition["status"] = $status;
		}
		if (!empty($tab)) {
			$condition["tab"] = $tab;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		//5de72198d6612b4076140606 super admin
		//5de726a8d6612b6f2b431749 Van hanh
		//5de726c9d6612b6f2a617ef5 CHT
		//5de726e4d6612b6f2c310c78 GDV
		//5de726fcd6612b77824963b9 Ke Toan
		//5def671dd6612b75532960c5 Hoi so
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('tbp-telesale', $groupRoles)) {
			$all = true;
		}

		if (!empty($tab) && in_array($tab, ['list_not_qualified','list_qualified'])) {
			$per_page = !empty($data['per_page']) ? $data['per_page'] : 4000;
			$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
			if (!empty($sdt)) {
				$condition["sdt"] = $sdt;
			}
			if (!empty($source)) {
				$condition["source"] = $source;
			}
			if (!empty($fullname)) {
				$condition["fullname"] = $fullname;
			}
			if (!empty($tab)) {
				$condition["tab"] = $tab;
			}
			$leads = $this->lead_model->getLeadTS($condition, $per_page, $uriSegment);
			$condition['total'] = true;
			$leads_total = $this->lead_model->getLeadTS($condition);
		}

		if (!empty($condition["tab"]) && $condition["tab"] == "not_qualified") {
			$lead_last = $this->lead_model->getLeadNotQualifiedTS($condition);
		} else if (!empty($condition["tab"]) && $condition["tab"] == "qualified") {
			$lead_last = $this->lead_model->getLeadQualifiedTS($condition);
		}


		$arr_data_reason = reason();
		$arr_data_status_sale = lead_status();

		$arr_return_reason = [];
		$arr_return_reason_cancel_qualified = [];
		$total_lead_cancel = 0;
		$total_status_sale = 0;
		$total_lead_cancel_qualified = 0;
		$total_lead = 0;
		$lead_new = [];
		$lead_cancel = [];
		if (!empty($condition["tab"]) && $condition["tab"] == "not_qualified") {
			$n = 0;
			if (!empty($lead_last)) {
				foreach ($lead_last as $key => $value) {
					$n++;
					$lead_new += [$n => $value];
					if (!empty($value['reason_cancel']))
						$total_lead_cancel++;
					$total_lead++;
				}
			}
			$sou = 0;
			if (!empty($arr_data_reason))
				foreach (array_unique($arr_data_reason) as $key1 => $value) {
					$sou++;
					if (count_values($lead_new, 'reason_cancel', $key1, 'status', '2', 'status_sale', '19') > 0)
						$arr_return_reason += [$sou => [
							'key_reason' => $key1,
							'reason' => $value,
							'total_lead_cancel' => $total_lead_cancel,
							'total_lead' => $total_lead,
							'lead_cancel' => count_values($lead_new, 'reason_cancel', $key1, 'status', '2', 'status_sale', '19')
						]];
				}

		} else {
			$m = 0;
			if (!empty($lead_last)) {
				foreach ($lead_last as $key2 => $value_lead_q) {
					$m++;
					$lead_new += [$m => $value_lead_q];
					if (!empty($value_lead_q['status_sale']))
						$total_status_sale++;
					if (!empty($value_lead_q['status_sale']) && $value_lead_q['status_sale'] == "19") {
						$lead_cancel += [$m => $value_lead_q];
						$total_lead_cancel++;
					}
					$total_lead++;
				}
			}
			$stt = 0;
			if (!empty($arr_data_status_sale))
				foreach (array_unique($arr_data_status_sale) as $key3 => $value) {
					$stt++;
					if (count_values($lead_new, 'status_sale', $key3, '', '', 'source', 'TS') > 0)
						$arr_return_reason += [$stt => [
							'key_status' => $key3,
							'status' => $value,
							'total_status_sale' => $total_status_sale,
							'total_lead' => $total_lead,
							'status_sale' => count_values($lead_new, 'status_sale', $key3, '', '', 'source', 'TS')
						]];
				}
			$cancel = 0;
			if (!empty($arr_data_reason))
				foreach (array_unique($arr_data_reason) as $key4 => $value) {
					$cancel++;
					if (count_values($lead_cancel, 'reason_cancel', $key4, 'status', '2', 'status_sale', '19') > 0)
						$arr_return_reason_cancel_qualified += [$cancel => [
							'key_reason' => $key4,
							'reason' => $value,
							'total_lead_cancel' => $total_lead_cancel,
							'total_lead' => $total_lead,
							'lead_cancel' => count_values($lead_cancel, 'reason_cancel', $key4, 'status', '2', 'status_sale', '19')
						]];
				}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr_return_reason,
			'qualified_cancel' => $arr_return_reason_cancel_qualified,
			'leadsData' => $leads,
			'leads_total' => $leads_total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function restore_lead_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['id_lead'] = $this->security->xss_clean($this->dataPost['id_lead']);
		$lead = $this->lead_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id_lead'])));

		if (!empty($lead)) {
			$this->lead_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id_lead'])),
				array(
					"status_sale" => "1"
				)
			);
			$message = "Khôi phục lead thành công!";
		}

		$log = array(
			"type" => "lead",
			"action" => "update_status_lead",
			"id_lead" => $this->dataPost['id'],
			"restore_at" => $this->createdAt,
			"restore_by" => $this->uemail
		);
		$this->log_lead_model->insert($log);
		$this->log_model->insert($log);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead,
			'msg' => $message
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function push_notification_to_cskh($email_cskh, $lead)
	{
		$user_cskh = $this->user_model->findOne(array('email' => (string)$email_cskh));
		$user_id_cskh = (string)$user_cskh['_id'];
		$lead_id = (string)$lead["_id"];
		$lead_fullname = $lead["fullname"];
		$lead_phone_number = $lead["phone_number"];
		if (!empty($user_cskh)) {
			$data_notification = [
				'action_id' => $lead_id,
				'action' => 'lead',
				"detail" => "lead_custom?fdate=&tdate=&sdt=$lead_phone_number&tab=4",
				"title" => "Bạn đã nhận được 01 Lead!",
				"note" => "Họ tên:". $lead_fullname.", SĐT: ".$lead_phone_number,
				'user_id' => $user_id_cskh,
				"lead_name" => $lead_fullname,
				"lead_phone_number" => $lead_phone_number,
				'status' => 1, //1: new, 2 : read, 3: block,
				'created_at' => $this->createdAt,
				"created_by" => $this->uemail
			];
			$this->notification_model->insertReturnId($data_notification);
			$device = $this->device_model->find_where(array('user_id' => $user_id_cskh));
			if (!empty($device)) {
				$fcm = new Fcm();
				$to = [];
				foreach ($device as $de) {
				$to[] = $de->device_token;
				}
				$fcm->setTitle('Bạn đã nhận được 01 Lead! ');
				$fcm->setMessage("Họ tên: $lead_fullname, SĐT: $lead_phone_number");

				$click_action =  'https://cpanel.tienngay.vn/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
//				$click_action =  'https://sandboxcpanel.tienngay.vn/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
//				$click_action =  'http://localhost/tienngay/cpanel.tienngay/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
				$fcm->setClickAction($click_action);

				$fcm->setLeadId($lead_id);
				$data = $fcm->getDataLead($lead_phone_number);
				$fcm->setPayload($data);
				$message = $fcm->getMessage();
				$result = $fcm->sendToTopicCpanel($to, $message, $message);
			}
		}
	}

	public function back_delete_post(){

		$condition = [];
		$start = date('Y-m-d');
		$end = date('Y-m-d');

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'));
		}

		$return = $this->cskh_del_model->delete_desc($condition);

		if (!empty($return)){
			$this->cskh_del_model->delete(['_id' => new MongoDB\BSON\ObjectId((string)$return[0]['_id'])]);

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Thành công"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Không có dữ liệu"
			);
			$this->set_response($response, REST_Controller::HTTP_BAD_REQUEST);
			return;
		}






	}


	// public function get_price_ctv_post(){

	// 	$this->dataPost = $this->input->post();
	// 	$condition = [];

	// 	$key = !empty($this->dataPost['key']) ? $this->dataPost['key'] : "";
	// 	$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
	// 	$tdate = !empty($this->dataPost['tdate']) ? strtotime($this->dataPost['tdate'] . "23.59.59") : "";
	// 	$fdate = !empty($this->dataPost['fdate']) ? strtotime($this->dataPost['fdate'] . "00.00.00") : "";


	// 	if (!empty($key)) {
	// 		$condition['key'] = $key;
	// 	}
	// 	if (!empty($phone)) {
	// 		$condition['phone'] = $phone;
	// 	}
	// 	if (!empty($tdate)) {
	// 		$condition['tdate'] = $tdate;
	// 	}
	// 	if (!empty($fdate)) {
	// 		$condition['fdate'] = $fdate;
	// 	}

	// 	$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
	// 	$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;


	// 	$lead = $this->lead_model->getByRole_price_ctv($condition, $per_page, $uriSegment);


	// 	if (empty($lead)) {
	// 		return;
	// 	}

	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'data' => $lead
	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;

	// }

	// public function get_count_price_ctv_post(){

	// 	$this->dataPost = $this->input->post();
	// 	$condition = [];

	// 	$key = !empty($this->dataPost['key']) ? $this->dataPost['key'] : "";
	// 	$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
	// 	$tdate = !empty($this->dataPost['tdate']) ? strtotime($this->dataPost['tdate']) : "";
	// 	$fdate = !empty($this->dataPost['fdate']) ? strtotime($this->dataPost['fdate']) : "";


	// 	if (!empty($key)) {
	// 		$condition['key'] = $key;
	// 	}
	// 	if (!empty($phone)) {
	// 		$condition['phone'] = $phone;
	// 	}
	// 	if (!empty($tdate)) {
	// 		$condition['tdate'] = $tdate;
	// 	}
	// 	if (!empty($fdate)) {
	// 		$condition['fdate'] = $fdate;
	// 	}

	// 	$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
	// 	$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

	// 	$lead = $this->lead_model->getByRole_count_ctv($condition, $per_page, $uriSegment);

	// 	if (empty($lead)) {
	// 		return;
	// 	}

	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'data' => $lead
	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;

	// }

	// public function get_collaborator_post(){

	// 	$this->dataPost = $this->input->post();

	// 	$collaborator = $this->collaborator_model->findOne(['_id' => new MongoDB\BSON\ObjectId($this->dataPost['code_ctv'])]);

	// 	if (empty($collaborator)) {
	// 		return;
	// 	}

	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'data' => $collaborator
	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;

	// }

	// public function account_bank_post(){

	// 	$this->dataPost = $this->input->post();

	// 	$account_bank = $this->account_bank_model->findOne(['user_id' => $this->dataPost['code_ctv']]);

	// 	if (empty($account_bank)) {
	// 		return;
	// 	}

	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'data' => $account_bank
	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;
	// }

	// public function get_one_bankPrice_post(){

	// 	$this->dataPost = $this->input->post();

	// 	$lead = $this->lead_model->findOne(['_id' => new MongoDB\BSON\ObjectId($this->dataPost['id'])]);

	// 	if (empty($lead)) {
	// 		return;
	// 	}

	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'data' => $lead
	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;

	// }

	// public function process_update_bankCTV_post(){

	// 	$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
	// 	$this->dataPost['his_money'] = $this->security->xss_clean($this->dataPost['his_money']);
	// 	$this->dataPost['date_pay'] = strtotime($this->security->xss_clean($this->dataPost['date_pay']));
	// 	$this->dataPost['his_key'] = $this->security->xss_clean($this->dataPost['his_key']);

	// 	$check_lead = $this->lead_model->findOne(["_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])]);
	// 	$account_bank = $this->account_bank_model->findOne(['user_id' => $check_lead['ctv_code']]);

	// 	//Validate
	// 	if (empty($this->dataPost['his_money']) || $this->dataPost['his_money'] == "") {
	// 		$response = array(
	// 			'status' => REST_Controller::HTTP_UNAUTHORIZED,
	// 			'message' => "Số tiền thanh toán không được để trống"
	// 		);
	// 		$this->set_response($response, REST_Controller::HTTP_OK);
	// 		return;
	// 	}
	// 	if (empty($this->dataPost['date_pay'])) {
	// 		$response = array(
	// 			'status' => REST_Controller::HTTP_UNAUTHORIZED,
	// 			'message' => "Ngày thanh toán không được để trống"
	// 		);
	// 		$this->set_response($response, REST_Controller::HTTP_OK);
	// 		return;
	// 	}
	// 	if (empty($this->dataPost['his_key'])) {
	// 		$response = array(
	// 			'status' => REST_Controller::HTTP_UNAUTHORIZED,
	// 			'message' => "Mã giao dịch không được để trống"
	// 		);
	// 		$this->set_response($response, REST_Controller::HTTP_OK);
	// 		return;
	// 	}



	// 	if (empty($check_lead->account_bank_core)){
	// 		$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id']) ), ["his_money" => $this->dataPost['his_money'], "date_pay" => $this->dataPost['date_pay'], "his_key" => $this->dataPost['his_key'], "account_bank_core" => $account_bank]);
	// 	} else {
	// 		$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id']) ), ["his_money" => $this->dataPost['his_money'], "date_pay" => $this->dataPost['date_pay'], "his_key" => $this->dataPost['his_key']]);
	// 	}


	// 	$response = array(
	// 		'status' => REST_Controller::HTTP_OK,
	// 		'message' => "Update success"

	// 	);
	// 	$this->set_response($response, REST_Controller::HTTP_OK);
	// 	return;


	// }


	public function get_price_ctv_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$key = !empty($this->dataPost['key']) ? $this->dataPost['key'] : "";
		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? strtotime($this->dataPost['tdate'] . "23.59.59") : "";
		$fdate = !empty($this->dataPost['fdate']) ? strtotime($this->dataPost['fdate'] . "00.00.00") : "";


		if (!empty($key)) {
			$condition['key'] = $key;
		}
		if (!empty($phone)) {
			$condition['phone'] = $phone;
		}
		if (!empty($tdate)) {
			$condition['tdate'] = $tdate;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = $fdate;
		}

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;


		$lead = $this->lead_model->getByRole_price_ctv($condition, $per_page, $uriSegment);


		if (empty($lead)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_count_price_ctv_post(){

		$this->dataPost = $this->input->post();
		$condition = [];

		$key = !empty($this->dataPost['key']) ? $this->dataPost['key'] : "";
		$phone = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? strtotime($this->dataPost['tdate']) : "";
		$fdate = !empty($this->dataPost['fdate']) ? strtotime($this->dataPost['fdate']) : "";


		if (!empty($key)) {
			$condition['key'] = $key;
		}
		if (!empty($phone)) {
			$condition['phone'] = $phone;
		}
		if (!empty($tdate)) {
			$condition['tdate'] = $tdate;
		}
		if (!empty($fdate)) {
			$condition['fdate'] = $fdate;
		}

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$lead = $this->lead_model->getByRole_count_ctv($condition, $per_page, $uriSegment);

		if (empty($lead)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_collaborator_post(){

		$this->dataPost = $this->input->post();

		$collaborator = $this->collaborator_model->findOne(['_id' => new MongoDB\BSON\ObjectId($this->dataPost['code_ctv'])]);

		if (empty($collaborator)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $collaborator
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function account_bank_post(){

		$this->dataPost = $this->input->post();

		$account_bank = $this->account_bank_model->findOne(['user_id' => $this->dataPost['code_ctv']]);

		if (empty($account_bank)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $account_bank
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_one_bankPrice_post(){

		$this->dataPost = $this->input->post();

		$lead = $this->lead_model->findOne(['_id' => new MongoDB\BSON\ObjectId($this->dataPost['id'])]);

		if (empty($lead)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function process_update_bankCTV_post(){

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['his_money'] = $this->security->xss_clean($this->dataPost['his_money']);
		$this->dataPost['date_pay'] = strtotime($this->security->xss_clean($this->dataPost['date_pay']));
		$this->dataPost['his_key'] = $this->security->xss_clean($this->dataPost['his_key']);

		$check_lead = $this->lead_model->findOne(["_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])]);
		$account_bank = $this->account_bank_model->findOne(['user_id' => $check_lead['ctv_code']]);

		//Validate
		if (empty($this->dataPost['his_money']) || $this->dataPost['his_money'] == "") {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tiền thanh toán không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['date_pay'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày thanh toán không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['his_key'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã giao dịch không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}



		if (empty($check_lead->account_bank_core)){
			$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id']) ), ["his_money" => $this->dataPost['his_money'], "date_pay" => $this->dataPost['date_pay'], "his_key" => $this->dataPost['his_key'], "account_bank_core" => $account_bank]);
		} else {
			$this->lead_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id']) ), ["his_money" => $this->dataPost['his_money'], "date_pay" => $this->dataPost['date_pay'], "his_key" => $this->dataPost['his_key']]);
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	private function getGroupRole_gdv()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'giao-dich-vien'));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						foreach ($item as $e){
							array_push($arr, $e);

						}
					}

				}
			}
		}
		return $arr;
	}
	private function getGroupRole_tls()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'telesales'));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						foreach ($item as $e){
							array_push($arr, $e);

						}
					}

				}
			}
		}
		return $arr;
	}




}

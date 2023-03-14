<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;
class Report_contract_loan extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_easy_model');
		$this->load->model('gic_plt_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('report_contract_loan_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->helper('lead_helper');
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
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;
		public function run_post()
			{
				// $flag = notify_token($this->flag_login);
				// if ($flag == false) return;
				date_default_timezone_set('Asia/Ho_Chi_Minh');
				$this->dataPost = $this->input->post()['condition'];
				$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
				
				$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 1000000;
				$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

				$contract = array();
                // die;
				$contract = $this->contract_model->getContractLoan(array(), $condition, $per_page, $uriSegment);
               
                 
				if (!empty($contract)) {
				
					$this->report_contract_loan_model->drop_collection();
			
					foreach ($contract as $key => $c) {
						$cond = array();
						$c['investor_name'] = "";
						if (isset($c['investor_code'])) {
							$investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
							$c['investor_name'] = $investors['name'];
						}
						if (isset($c['code_contract'])) {
							$cond = array(
								'code_contract' => $c['code_contract']
								
							);
						}
						$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
					
							$tien_goc_con = 0;
							$loan_money = 0;
							$c['detail'] = $detail[0];
							foreach ($detail as $de) {

								$tien_goc_con = $tien_goc_con + $de['tien_goc_con'];
								$loan_money = $loan_money + $de['tien_tra_1_ky'];
								
							}
							
							$c['tien_goc_con'] = $tien_goc_con;
							$c['loan_money'] = $loan_money;  

						$time = 0;
						if (!empty($c['detail']) && $c['detail']['status'] == 1) {
							$current_day = strtotime(date('m/d/Y'));
							$datetime = !empty($c['detail']['ngay_ky_tra']) ? intval($c['detail']['ngay_ky_tra']) : $current_day;
							$time = intval(($current_day - $datetime) / (24 * 60 * 60));
							if ($time <= 0) {
								$c['bucket'] = 'B0';
							} else if ($time >= 1 && $time <= 30) {
								$c['bucket'] = 'B1';
							} else if ($time > 30 && $time <= 60) {
								$c['bucket'] = 'B2';
							} else if ($time > 60 && $time <= 90) {
								$c['bucket'] = 'B3';
							} else if ($time > 90 && $time <= 120) {
								$c['bucket'] = 'B4';
							} else if ($time > 120 && $time <= 150) {
								$c['bucket'] = 'B5';
							} else if ($time > 150 && $time <= 180) {
								$c['bucket'] = 'B6';
							} else if ($time > 180 && $time <= 210) {
								$c['bucket'] = 'B7';
							} else if ($time > 210 && $time <= 270) {
								$c['bucket'] = 'B8';
							} else if ($time > 270 && $time <= 300) {
								$c['bucket'] = 'B9';
							} else if ($time > 300 && $time <= 330) {
								$c['bucket'] = 'B10';
							} else if ($time > 330 && $time <= 360) {
								$c['bucket'] = 'B11';
							} else {
								$c['bucket'] = 'B12';
							}
						} else if (!empty($c['detail']) && $c['status'] == 2) {
							$c['bucket'] = 'B0';
						} else {
							$c['bucket'] = '-';
						}
						$c['time'] = $time;
						if ($c['status'] == 19 || $c['status'] == 23)
							$c['time'] = '-';
                      
                      $data_rp=array(
                      	'STT'=>$key+1,
                      	'code_contract_disbursement'=>(isset($c['code_contract_disbursement'])) ? $c['code_contract_disbursement'] : '',
                      	'code_contract'=>(isset($c['code_contract'])) ? $c['code_contract'] : '',
                      	'customer_name'=>(isset($c['customer_infor']['customer_name'])) ?$c['customer_infor']['customer_name'] : '',
                      	'current_stay'=>(isset($c['current_address']['current_stay'])) ?$c['current_address']['current_stay'] : '',
                      	'current_ward_name'=>(isset($c['current_address']['ward_name'])) ? $c['current_address']['ward_name'] : '',
                      	'current_district_name'=>(isset($c['current_address']['district_name'])) ? $c['current_address']['district_name'] : '',
                      	'current_province_name'=>(isset($c['current_address']['province_name'])) ? $c['current_address']['province_name'] : '',
                      	'address_household'=>(isset($c['houseHold_address']['address_household'])) ? $c['houseHold_address']['address_household'] : '',	
                      	'household_ward_name'=>(isset($c['houseHold_address']['ward_name'])) ? $c['houseHold_address']['ward_name'] : '',	
                      	'household_district_name'=>(isset($c['houseHold_address']['district_name'])) ?$c['houseHold_address']['district_name'] : '',	
                      	'type_loan'=>(isset($c['loan_infor']['type_loan']['text'])) ? $c['loan_infor']['type_loan']['text'] : '',	
                      	'store'=>(isset($c['store']['name'])) ?$c['store']['name'] : '',	
                      	'amount_money'=>(isset($c['loan_infor']['amount_money'])) ?$c['loan_infor']['amount_money'] : '',	
                      	'loan_money'=>(isset($c['loan_money'])) ?$c['loan_money'] : '',	
                      	'time'=>(isset($time)) ? $time : '',	
                      	'bucket'=>(isset($c['bucket'])) ?$c['bucket'] : '',	
                      	'total_goc_con'=>(isset($c['tien_goc_con'])) ?$c['tien_goc_con'] : '',

                      );
                       $this->report_contract_loan_model->insert($data_rp);
					}

				}


				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => $contract,
					
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
    
}
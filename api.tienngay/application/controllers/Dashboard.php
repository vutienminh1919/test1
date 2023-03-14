<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Dashboard extends REST_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model("dashboard_model");
		$this->load->model('contract_model');
		$this->load->model("role_model");
		$this->load->model("lead_model");
		$this->load->model("store_model");
		$this->load->model("order_model");
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
					'_id'=>new \MongoDB\BSON\ObjectId($token->id),
					'email'=>$token->email,
					"status" => "active"
				);
				//Web
				if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1){
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

	}
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

	public function synchronized_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$contract =  new Contract_model();
    	$lead = new Lead_model();
    	$store = new Store_model();
    	$utilities = new Order_model();
		$data_dashboard = array();
		//contract
		$data_dashboard['contract']['contract_total']  = $contract->count(array("created_at"=>array('$ne'=>0)));
		$data_dashboard['contract']['contract_disbursed']  = $contract->count(array("status"=>17));
		$data_dashboard['contract']['contract_waiting_disbursement']  = $contract->count(array("status"=>15));
		$data_dashboard['contract']['contract_pending']  = $contract->count(array("status"=>array('$in'=>array(2,5,21))));
		$data_dashboard['contract']['contract_cancel']  = $contract->count(array("status"=>3));
//    	$contracts  = $contract->find_where(array("status"=>array('$in'=>array(17,19,20,21,22,23))));
//    	$total = array();
//    	foreach ($contracts as $key=>$object){
//				$total[] = (int)$object->loan_infor->amount_money;
//		}
//		$data_dashboard['contract']['total_amount_money'] = array_sum($total);

		//customer
		$data_dashboard['customer']['total']  = $lead->count();
		$data_dashboard['customer']['not_call']  = $lead->count(array('status_sale'=>'1'));
		$data_dashboard['customer']['called']  = $lead->count(array('status_sale'=>array('$nin'=>array('1'))));
		$data_dashboard['customer']['disbursement']  = $lead->count(array('status_sale'=>array('$in'=>array('2','9'))));

		//type_loan
		$data_dashboard['type_loan']['oto'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'OTO'));
		$data_dashboard['type_loan']['xm'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'XM'));
		$data_dashboard['type_loan']['car_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'OTO'));
		$data_dashboard['type_loan']['motorbike_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'XM'));

		//store
		$data_dashboard['store']['hn']= $store->count(array('code_province_store'=>'HN'));
		$data_dashboard['store']['hcm']= $store->count(array('code_province_store'=>'HCM'));
		$data_dashboard['store']['other']= $store->count(array('code_province_store'=>array('$nin'=>array('HN','HCM'))));

		//utilities
		$data_dashboard['utilities']['electric'] = $utilities->count(array("service_code"=>"BILL_ELECTRIC"));
		$data_dashboard['utilities']['water'] = $utilities->count(array("service_code"=>"BILL_WATER"));
		$data_dashboard['utilities']['finance'] = $utilities->count(array("service_code"=>"BILL_FINANCE"));

		//utilities_receipts
		$data_dashboard['utilities_receipts']['total_payment']= $utilities->sum_where(array('status'=>"success"),'$money');
		$data_dashboard['utilities_receipts']['bill_electric_payment']=  $utilities->sum_where(array('status'=>"success",'service_code'=>"BILL_ELECTRIC"),'$money');
		$data_dashboard['utilities_receipts']['bill_water_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_WATER"),'$money');
		$data_dashboard['utilities_receipts']['bill_finance_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_FINANCE"),'$money');

		$data_dashboard["status"] = "active";
		$dashboard = new Dashboard_model();
		$dashboard->remove();
		$dashboard->insert($data_dashboard);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message'=>'Create new collection dashboard',
			'data'=>$data_dashboard
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//function update dash board input type(contract, customer,store,utilities), data = 1 oject need update
	public function update_dash_board_post(){ //is
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$type = $this->dataPost['data_type'];
		$data = $this->dataPost['data']; // id của các collection type truyền vào
		switch ($type) {
			case "contract":
				$this->process_update_contract($data);
				break;
			case "customer":
				$this->process_update_customer($data);
				break;
			case "store":
				$this->process_update_store($data);
				break;
			case "utilities":
				$this->process_update_utilities($data);
				break;
			default:
				echo "Your favorite color is neither red, blue, nor green!";
		}
	}

	//function update field contract in dash board record input is object contract
	private function process_update_contract($data){
		$contract =  new Contract_model();
		$contract_data = $contract->findOne(array('_id'=>new MongoDB\BSON\ObjectId($data)));
		$dash_board = new Dashboard_model();
		$dash_board_data = $dash_board->findOne(array('status'=>'active'));
//		var_dump((int)$contract_data['loan_infor']['amount_money']);die();
		$update = array(
			"contract"=>array(
				"contract_disbursed" => $dash_board_data['contract']['contract_disbursed'],
				"total_amount_money" => $dash_board_data['contract']['total_amount_money'],
				"contract_waiting_disbursement" => $dash_board_data['contract']['contract_waiting_disbursement'],
				"contract_pending" => $dash_board_data['contract']['contract_pending'],
				"contract_cancel" =>  $dash_board_data['contract']['contract_cancel'],
				"contract_total" => $dash_board_data['contract']['contract_total']
			),
			"type_loan"=>array(
				"oto"=> $dash_board_data['type_loan']['oto'],
				"xm"=> $dash_board_data['type_loan']['xm'],
				"car_registration"=> $dash_board_data['type_loan']['car_registration'],
				"motorbike_registration"=> $dash_board_data['type_loan']['motorbike_registration'],
			)
		);
		if($contract_data['status'] == 17 or $contract_data['status'] == 19 or $contract_data['status'] == 20 or $contract_data['status'] == 21 or $contract_data['status'] == 22 or $contract_data['status']== 23){
			$update['contract']['contract_disbursed'] = $dash_board_data['contract']['contract_disbursed'] +1;
			$update['contract']['total_amount_money'] = $dash_board_data['contract']['total_amount_money'] + (int)$contract_data['loan_infor']['amount_money'];
			$update['contract']['contract_waiting_disbursement'] = $dash_board_data['contract']['contract_waiting_disbursement'] - 1;
		}
		if($contract_data['status'] == 15){
			$update['contract']['contract_waiting_disbursement'] = $dash_board_data['contract']['contract_waiting_disbursement'] +1;
			$update['contract']['contract_pending'] = $dash_board_data['contract']['contract_pending'] - 1;
		}
		if($contract_data['status'] == 5){
			$update['contract']['contract_pending'] = $dash_board_data['contract']['contract_pending'] +1;
		}
		if($contract_data['status'] == 3){
			$update['contract']['contract_cancel'] = $dash_board_data['contract']['contract_cancel'] +1;
		}
		if($contract_data['status'] == 1){
			$update['contract']['contract_total']= $dash_board_data['contract']['contract_total'] +1;
			//update type loan
			if($contract_data['loan_infor']['type_loan']['id'] == "5da82ed2a104d435e3b8ae65" && $contract_data['loan_infor']['type_property']['code'] == "XM"){
				$update['type_loan']['xm'] = $dash_board_data['type_loan']['xm'] + 1;
			}
			if($contract_data['loan_infor']['type_loan']['id'] == "5da82ed2a104d435e3b8ae65" && $contract_data['loan_infor']['type_property']['code'] == "OTO"){
				$update['type_loan']['oto'] = $dash_board_data['type_loan']['oto'] + 1;
			}
			if($contract_data['loan_infor']['type_loan']['id'] == "5da82ee7a104d435e3b8ae66" && $contract_data['loan_infor']['type_property']['code'] == "OTO"){
				$update['type_loan']['car_registration'] = $dash_board_data['type_loan']['car_registration'] + 1;
			}
			if($contract_data['loan_infor']['type_loan']['id'] == "5da82ee7a104d435e3b8ae66" && $contract_data['loan_infor']['type_property']['code'] == "OTO"){
				$update['type_loan']['motorbike_registration'] = $dash_board_data['type_loan']['motorbike_registration'] + 1;
			}
		}
		$dash_board_id = $dash_board_data['_id'];
		$result = $dash_board->update(array('_id'=>$dash_board_id),$update);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update dash board success",
			'data' =>  $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	// function update field customer in dash board record input is object customer
	private function process_update_customer($data){
		$customer = new Lead_model();
		$customer_data = $customer->findOne(array('_id'=>new MongoDB\BSON\ObjectId($data)));
		$dash_board = new Dashboard_model();
		$dash_board_data = $dash_board->findOne(array('status'=>'active'));
		$update = array(
			"customer"=>array(
				"total"=>$dash_board_data['customer']['total'],
				"not_call"=>$dash_board_data['customer']['not_call'],
				"called"=>$dash_board_data['customer']['called'],
				"disbursement"=>$dash_board_data['customer']['disbursement']
			)
		);
		if($customer_data['status_sale'] == 1){
			$update['customer']['total'] = $dash_board_data['customer']['total'] + 1;
			$update['customer']['not_call'] = $dash_board_data['customer']['not_call'] + 1;
		}
		if($customer_data['status_sale'] != 1){
			if($customer_data['status_sale'] == 2 or $customer_data['status_sale'] == 9){
				$update['customer']['disbursement'] = $dash_board_data['customer']['disbursement'] + 1;
			}
			$update['customer']['called'] = $dash_board_data['customer']['called'] + 1;
			$update['customer']['not_call'] = $dash_board_data['customer']['not_call'] - 1;
		}
		$dash_board_id = $dash_board_data['_id'];
		$result = $dash_board->update(array('_id'=>$dash_board_id),$update);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update dash board success",
			'data' =>  $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	//function update field store in dash board record input is object store
	private function process_update_store($data){
		$store = new Store_model();
		$store_data = $store->findOne(array('_id'=>new MongoDB\BSON\ObjectId($data)));
		$dash_board = new Dashboard_model();
		$dash_board_data = $dash_board->findOne(array('status'=>'active'));
		$update = array(
			"store"=>array(
				"hn"=>$dash_board_data['store']['hn'],
				"hcm"=>$dash_board_data['store']['hcm'],
				"orther"=>$dash_board_data['store']['orther'],
			)
		);
		if($store_data['code_province_store'] == "HN"){
			$update['store']['hn'] = $dash_board_data['store']['hn'] + 1;
		}
		if($store_data['code_province_store'] == "HCM"){
			$update['store']['hcm'] = $dash_board_data['store']['hcm'] + 1;
		}
		if($store_data['code_province_store'] != "HN" && $store_data['code_province_store'] != "HCM"){
			$update['store']['orther'] = $dash_board_data['store']['orther'] + 1;
		}
		$dash_board_id = $dash_board_data['_id'];
		$result = $dash_board->update(array('_id'=>$dash_board_id),$update);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update dash board success",
			'data' =>  $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	//function update field utilities in dash board record input is object utilities
	private function process_update_utilities($data){
		$orders = new Order_model();
		$orders_data = $orders->findOne(array('_id'=>new MongoDB\BSON\ObjectId($data)));
		$dash_board = new Dashboard_model();
		$dash_board_data = $dash_board->findOne(array('status'=>'active'));
		$update = array(
			"utilities"=>array(
				"electric"=>$dash_board_data['utilities']['electric'],
				"water"=>$dash_board_data['utilities']['water'],
				"finance"=>$dash_board_data['utilities']['finance']
			),
			"utilities_receipts"=>array(
				"total_payment"=> $dash_board_data['utilities_receipts']['total_payment'],
				"bill_electric_payment"=>$dash_board_data['utilities_receipts']['bill_electric_payment'],
				"bill_water_payment"=>$dash_board_data['utilities_receipts']['bill_water_payment'],
				"bill_finance_payment"=>$dash_board_data['utilities_receipts']['bill_finance_payment'],
			)
		);
		if($orders_data['service_code'] == "BILL_ELECTRIC"){
			$update['utilities']['electric'] = $dash_board_data['utilities']['electric'] + 1;
			$update['utilities_receipts']['total_payment'] = $dash_board_data['utilities_receipts']['total_payment'] + $orders_data['money'];
			$update['utilities_receipts']['electric'] = $dash_board_data['utilities']['electric'] + $orders_data['money'];
		}
		if($orders_data['service_code'] == "BILL_WATER"){
			$update['utilities']['water'] = $dash_board_data['utilities']['water'] + 1;
			$update['utilities_receipts']['total_payment'] = $dash_board_data['utilities_receipts']['total_payment'] + $orders_data['money'];
			$update['utilities_receipts']['water'] = $dash_board_data['utilities']['water'] + $orders_data['money'];
		}
		if($orders_data['service_code'] == "BILL_FINANCE"){
			$update['utilities']['finance'] = $dash_board_data['utilities']['finance'] + 1;
			$update['utilities_receipts']['total_payment'] = $dash_board_data['utilities_receipts']['total_payment'] + $orders_data['money'];
			$update['utilities_receipts']['finance'] = $dash_board_data['utilities']['water'] + $orders_data['finance'];
		}

		$dash_board_id = $dash_board_data['_id'];
		$result = $dash_board->update(array('_id'=>$dash_board_id),$update);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update dash board success",
			'data' =>  $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function update_total_post(){
		$contract =  new Contract_model();
		$contracts  = $contract->find_where(array("status"=>array('$in'=>array(17,19,20,21,22,23))));
		$total = array();
		foreach ($contracts as $key=>$object){
			$total[] = (int)$object->loan_infor->amount_money;
		}
		$result = $this->dashboard_model->update(array('status'=>'active'),
						array('contract.total_amount_money'=>array_sum($total))
						);
		if($result){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Update dash board success",
				'data' =>  $result,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Update dash board false",
			);
			$this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}

	public function getData_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data_dashboard = $this->dashboard_model->findOne(array('status'=>'active'));
		$lead = new Lead_model();
		$store = new Store_model();
		$utilities = new Order_model();
		$contract =  new Contract_model();
//customer
		$data_dashboard['customer']['total']  = $lead->count();
		$data_dashboard['customer']['not_call']  = $lead->count(array('status_sale'=>'1'));
		$data_dashboard['customer']['called']  = $lead->count(array('status_sale'=>array('$nin'=>array('1'))));
		$data_dashboard['customer']['disbursement']  = $lead->count(array('status_sale'=>array('$in'=>array('2','9'))));

		//type_loan
		$data_dashboard['type_loan']['oto'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'OTO'));
		$data_dashboard['type_loan']['xm'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'XM'));
		$data_dashboard['type_loan']['car_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'OTO'));
		$data_dashboard['type_loan']['motorbike_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'XM'));

		//store
		$data_dashboard['store']['hn']= $store->count(array('code_province_store'=>'HN'));
		$data_dashboard['store']['hcm']= $store->count(array('code_province_store'=>'HCM'));
		$data_dashboard['store']['other']= $store->count(array('code_province_store'=>array('$nin'=>array('HN','HCM'))));

		//utilities
		$data_dashboard['utilities']['electric'] = $utilities->count(array("service_code"=>"BILL_ELECTRIC"));
		$data_dashboard['utilities']['water'] = $utilities->count(array("service_code"=>"BILL_WATER"));
		$data_dashboard['utilities']['finance'] = $utilities->count(array("service_code"=>"BILL_FINANCE"));

		//utilities_receipts
		$data_dashboard['utilities_receipts']['total_payment']= $utilities->sum_where(array('status'=>"success"),'$money');
		$data_dashboard['utilities_receipts']['bill_electric_payment']=  $utilities->sum_where(array('status'=>"success",'service_code'=>"BILL_ELECTRIC"),'$money');
		$data_dashboard['utilities_receipts']['bill_water_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_WATER"),'$money');
		$data_dashboard['utilities_receipts']['bill_finance_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_FINANCE"),'$money');
		if($data_dashboard){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_dashboard,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$this->synchronized_post();
		}

	}

	public function search_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
		}
		$contract =  new Contract_model();
		$lead = new Lead_model();
		$store = new Store_model();
		$utilities = new Order_model();
		$data_dashboard = array();
		//contract
		$data_dashboard['contract']['contract_total']  = $contract->count(array("created_at"=>$condition));
		$data_dashboard['contract']['contract_disbursed']  = $contract->count(array("status"=>17,"created_at"=>$condition));
		$data_dashboard['contract']['contract_waiting_disbursement']  = $contract->count(array("status"=>15,"created_at"=>$condition));
		$data_dashboard['contract']['contract_pending']  = $contract->count(array("status"=>array('$in'=>array(2,5,21)),"created_at"=>$condition));
		$data_dashboard['contract']['contract_cancel']  = $contract->count(array("status"=>3,"created_at"=>$condition));
		$contracts  = $contract->find_where(array("status"=>array('$in'=>array(17,19,20,21,22,23)),"created_at"=>$condition));
		$total = array();
		foreach ($contracts as $key=>$object){
			$total[] = (int)$object->loan_infor->amount_money;
		}
		$data_dashboard['contract']['total_amount_money'] = array_sum($total);

		//customer
		$data_dashboard['customer']['total']  = $lead->count(array("created_at"=>$condition));
		$data_dashboard['customer']['not_call']  = $lead->count(array('status_sale'=>'1',"created_at"=>$condition));
		$data_dashboard['customer']['called']  = $lead->count(array('status_sale'=>array('$nin'=>array('1')),"created_at"=>$condition));
		$data_dashboard['customer']['disbursement']  = $lead->count(array('status_sale'=>array('$in'=>array('2','9')),"created_at"=>$condition));

		//type_loan
		$data_dashboard['type_loan']['oto'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'OTO',"created_at"=>$condition));
		$data_dashboard['type_loan']['xm'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ed2a104d435e3b8ae65', 'loan_infor.type_property.code'=>'XM',"created_at"=>$condition));
		$data_dashboard['type_loan']['car_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'OTO',"created_at"=>$condition));
		$data_dashboard['type_loan']['motorbike_registration'] = $contract->count(array('loan_infor.type_loan.id'=>'5da82ee7a104d435e3b8ae66', 'loan_infor.type_property.code'=>'XM', "created_at"=>$condition));

		//store
		$data_dashboard['store']['hn']= $store->count(array('code_province_store'=>'HN','status'=>'active'));
		$data_dashboard['store']['hcm']= $store->count(array('code_province_store'=>'HCM','status'=>'active'));
		$data_dashboard['store']['other']= $store->count(array('code_province_store'=>array('$nin'=>array('HN','HCM')),'status'=>'active'));

		//utilities
		$data_dashboard['utilities']['electric'] = $utilities->count(array("service_code"=>"BILL_ELECTRIC","created_at"=>$condition));
		$data_dashboard['utilities']['water'] = $utilities->count(array("service_code"=>"BILL_WATER","created_at"=>$condition));
		$data_dashboard['utilities']['finance'] = $utilities->count(array("service_code"=>"BILL_FINANCE","created_at"=>$condition));

		//utilities_receipts
		$data_dashboard['utilities_receipts']['total_payment']= $utilities->sum_where(array('status'=>"success","created_at"=>$condition),'$money');
		$data_dashboard['utilities_receipts']['bill_electric_payment']=  $utilities->sum_where(array('status'=>"success",'service_code'=>"BILL_ELECTRIC","created_at"=>$condition),'$money');
		$data_dashboard['utilities_receipts']['bill_water_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_WATER","created_at"=>$condition),'$money');
		$data_dashboard['utilities_receipts']['bill_finance_payment']=  $utilities->sum_where(array('status'=>"success", 'service_code'=>"BILL_FINANCE","created_at"=>$condition),'$money');
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' =>  $data_dashboard,
			'cond' =>$condition
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_contract_header_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost['stores_id'] = $this->security->xss_clean($this->dataPost['stores_id']);
		$this->dataPost['group_roles'] = $this->security->xss_clean($this->dataPost['group_roles']);
		//Count hợp đồng mới, status = 1
		$countNewContract = $this->countNewContract($this->dataPost['stores_id'], $this->dataPost['group_roles']);
		//Count waiting disbument = chờ giải ngân. status = 15

		//Count waiting payment = chờ thanh toán = hợp đồng đã đc giải ngân thành công và tiền đã về tay khách. status = 17

		//Count nợ đến hạn

		//Count nợ quá hạn
	}

	public function get_dashboard_infor_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$dashboard = $this->dashboard_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dashboard
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}




}

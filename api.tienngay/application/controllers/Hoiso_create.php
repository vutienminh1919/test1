<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Hoiso_create extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('vbi_model');
		$this->load->model('warehouse_model');
		$this->load->model('contract_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_contract_model');
		$this->load->model('car_storage_model');
		$this->load->model('hoiso_model');
		$this->load->model('email_template_model');
		$this->load->model('email_history_model');
		$url_gic = "http://bancasuat.gic.vn";
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


	public function create_hs_storage_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');

//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$data = $this->input->post();
		$data['created_at'] = $this->createdAt;
		$data['check'] = 1;

		$check_contract = $this->hoiso_model->find_where_check('id_oid',array($data['id_oid']));

		if (empty($check_contract)){
			$this->hoiso_model->insert($data);

			$this->insert_log($data);

			$this->sendEmail_pgd($data['id_oid'], $data['user']['full_name']);

		}

		if (!empty($check_contract[0]['check']) && $check_contract[0]['check'] == 2){
			$this->hoiso_model->insert($data);

			$this->insert_log($data);

			$this->sendEmail_pgd($data['id_oid'], $data['user']['full_name']);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "thành công",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function insert_log($data){
		/**
		 * Save log to json file
		 */
		$insertLogNew = [
			"type" => "contract",
			"action" => "Bắt đầu tiếp nhận xử lý hồ sơ",
			"contract_id" => $data['id_oid'],
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail,
		];
		$log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
		$this->log_model->insert($insertLogNew);
		$log['log_id'] = $log_id;
		$this->insert_log_file($log, $data['id_oid']);
		/**
		 * ----------------------
		 */




	}

	private function sendEmail_pgd($id_contract, $full_name)
	{

		$dataContract = $this->contract_model->find_one_select(['_id' => new \MongoDB\BSON\ObjectId($id_contract)], ['code_contract_disbursement', 'created_by', 'store.id']);

		$data = array(
			'code' => "vfc_send_email_xlhs",
			'code_contract_disbursement' => $dataContract['code_contract_disbursement'],
			'url' => "https://lms.tienngay.vn/pawn/detail?id=" . $id_contract,
			'time' => date("H:i:s d/m/Y", $this->createdAt),
			'full_name' => $full_name,
		);

		$user = [];
		array_push($user, $dataContract['created_by']);

		$emailPgd = $this->getEmailPGD($dataContract['store']['id']);

		$list_email_cht = $this->list_email_cht();

		if (!empty($emailPgd)){
			foreach ($emailPgd as $value){
				if (in_array($value, $list_email_cht)){
					array_push($user, $value);
				}
			}
		}
		$user_end = array_unique($user);
		foreach ($user_end as $item) {
				$data['email'] = "$item";
				$data['email_show'] = "$item";
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmail($data);
		}
		return;
	}

	public function get_create_at_hs_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$condition['contract_id'] = $data['contract_id'];

		$return = $this->hoiso_model->find_where("id_oid", array($condition['contract_id']));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "thành công",
			'data' => $return
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function get_create_at_hs_all_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$check = $data['contract_id'];
		$return = $this->hoiso_model->find_where("id_oid", [$check]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "thành công",
			'data' => $return
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function update_check_hs_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$check = $data['id_oid'];

		$return = $this->hoiso_model->find_where("id_oid", [$check]);


		foreach ($return as $value){

			$this->hoiso_model->update(array("_id" => new MongoDB\BSON\ObjectId($value['_id'])), array('check' => 2));

		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "thành công",
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function insert_log_file($value, $contract_id){

		$fp = fopen($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json', "a");

		if(!empty($fp)){

			$arrayData = $this->readFileJson($contract_id);

			if(empty($arrayData)){
				$arrayData = [];
			}
			array_push($arrayData, $value);

			$this->saveFileJson($arrayData, $contract_id);

		}
	}

	function saveFileJson($arrayData , $contract_id){
		$dataJson = json_encode($arrayData);
		file_put_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json',$dataJson);
	}
	function readFileJson($contract_id){
		$data = file_get_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json');
		return json_decode($data,true);
	}

	public function getEmailPGD($storeId)
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
								if($item[key($item)]['email'] == "ngochtm@tienngay.vn"){
									continue;
								}
								array_push($roleAllUsers, $item[key($item)]['email']);
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}



	public function list_email_cht()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "cua-hang-truong")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e){
							array_push($data,$e);
						}

					}
				}
			}
		}
		return $data;
	}

	public function sendEmail($dataPost)
	{
		$email_template = $this->email_template_model->findOne(array('code' => $dataPost['code'], 'status' => 'active'));

		$domain = $this->config->item('sendgrid_domain');
		$from = 'support@tienngay.vn';
		$from_name = $email_template['from_name'];
		$subject = $email_template['subject'];
		$message = $this->getEmailStr($email_template['message'], $dataPost);
		$status = 'active';
		$data = array(
			"code" => $dataPost['code'],
			"from" => $from,
			"from_name" => $from_name,
			"to" => $dataPost['email'],
			"subject" => $subject,
			"email_domain" => $domain,
			"status" => $status,
			"message" => $message,
			"created_at" => (int)$this->createdAt
		);
		$this->email_history_model->insert($data);
		return;
	}
	public function getEmailStr($emailTemplate, $filter)
	{
		foreach ($filter as $key => $value) {
			$emailTemplate = str_replace("{" . $key . "}", $value, $emailTemplate);
		}
		return $emailTemplate;
	}


}

?>

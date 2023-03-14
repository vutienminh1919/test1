<?php
/**
 * Created by PhpStorm.
 * User: phanc
 * Date: 5/6/2020
 * Time: 11:21 AM
 */
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class BadDebt  extends REST_Controller
{
	public function __construct($config = 'rest') {
		parent::__construct($config);
		$this->load->model("baddebt_model");
		$this->load->model("contract_model");
		$this->load->model("transaction_model");
        $this->load->model('user_model');
        $this->load->model('role_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
        $this->load->helper('lead_helper');
		$this->dataPost = $this->input->post();
		//Check secret_key
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
					"status" => "active",
					// "is_superadmin" => 1
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

	private $createdAt, $dataPost;

	public function create_post() {
		$id = $this->dataPost["id"];
		if(!empty($id)){
			$cond = array("id"=>$id);
			$count = $this->baddebt_model->count($cond);
		}
		if($count == 0){
			$baddebt = $this->baddebt_model->insert($this->dataPost);
		}
		if(isset($baddebt)){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Create success",
				'data' => $this->dataPost,
				'check' => 1
			);
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Create false",
				'data' => $this->dataPost,
				'check' => 0
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$input = $this->input->post();
		$per_page = !empty($input['per_page']) ? $input['per_page'] : 30;
		$uriSegment = !empty($input['uriSegment']) ? $input['uriSegment'] : 0;
		$condition = array();
		if (!empty($input['start']) && !empty($input['end'])) {
			$condition = array(
				'start' => strtotime(trim($input['start']).' 00:00:00'),
				'end' => strtotime(trim($input['end']).' 23:59:59')
			);
		}
		if(!empty($input['name']) ){
			$condition['name'] = $input['name'];
		}
		if(!empty($input['identify']) ){
			$condition['identify'] = $input['identify'];
		}
		if(!empty($input['number_phone']) ){
			$condition['number_phone'] = $input['number_phone'];
		}
		if(!empty($input['code_contract']) ){
			$condition['code_contract'] = $input['code_contract'];
		}
		if(!empty($input['date_maturity']) ){
			$condition['date_maturity'] = $input['date_maturity'];
		}
//		$baddebt = $this->baddebt_model->getAll();
		$baddebt = $this->baddebt_model->getPagination($per_page, $uriSegment, $condition);
		$total = $this->baddebt_model->getTotal($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $baddebt,
			'count' => $total,
			'condition'=> $condition
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function call_customer_post(){
		$input = $this->dataPost;
		$_id =  $this->security->xss_clean($input['id']);
		if(empty($_id)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'ID can not empty'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$old = $this->baddebt_model->findOneAndupdate(
				array("_id" => new MongoDB\BSON\ObjectId($_id)),
				$this->dataPost
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Update success",
				'old' => $old
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}
	public function do_note_reminder_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if(empty($this->dataPost['contract_id']) || empty($this->dataPost['result_reminder'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$this->dataPost['result_reminder'] = $this->security->xss_clean($this->dataPost['result_reminder']);
		$this->dataPost['note'] = $this->security->xss_clean($this->dataPost['note']);
		$contract = $this->baddebt_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])));
		if(empty($contract)) return;
		//Update status contract
		$result_reminder = !empty($contract['result_reminder']) ? $contract['result_reminder'] : array();
		$note_reminder = array(
			"reminder" => $this->dataPost['result_reminder'],
			"note" =>   $this->dataPost['note'],
			"payment_date"=>strtotime($this->dataPost['payment_date']),
			"amount_payment_appointment"=>$this->dataPost['amount_payment_appointment'],
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$dataPush = array();
		array_push($dataPush, $note_reminder);
		foreach($result_reminder as $key => $value){
			array_push($dataPush, $value);
		}
		$arrUpdate = array(
			'result_reminder' => $dataPush,
			'result_remind_baddebt'=>$this->dataPost['result_reminder'],
			'payment_date'=>strtotime($this->dataPost['payment_date']),
			'amount_payment_appointment'=>$this->dataPost['amount_payment_appointment'],
			'note' => $this->dataPost['note'],
			'update_at'=>strtotime(date("Y/m/d"))
		);
		$this->baddebt_model->update( array("_id" => $contract['_id']),$arrUpdate);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Note success',
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function getDetail_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		if(empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Data'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$contract = $this->baddebt_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if(empty($contract)) return;
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class TempoContract extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('investor_model');
		$this->load->model('fee_loan_model');
		$this->load->model('log_model');
		$this->load->model('contract_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('log_contract_tempo_model');
		$this->load->model('temporary_plan_contract_model');

		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->dataPost = $this->input->post();
		$headers = $this->input->request_headers();
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id' => new \MongoDB\BSON\ObjectId($token->id),
					'email' => $token->email,
					"status" => "active"
				);
				//Web
				$type = !empty($this->dataPost['type']) ? $this->dataPost['type'] : 1;
				if ($type == 1) $this->app_login['token_web'] = $headers_item;
				if ($type == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}

	public function get_tempoContract_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$code_contract = $_POST['code_contract'];
		$data = $this->temporary_plan_contract_model->find_where(['code_contract'=>$code_contract]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' =>$data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


}

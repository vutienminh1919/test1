<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Company_storage extends REST_Controller
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
		$this->load->model('car_storage_model');
		$this->load->model('company_storage_model');

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

	public function create_company_storage_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$data['created_at'] = (int)$data['created_at'];

		foreach ($data['expertise_infor']['company_storage'] as $value){

			$data1 = [
				'company_name'=> $value['company_name'],
				'company_name_other'=> $value['company_name_other'],
				'company_debt'=> $value['company_debt'],
				'company_finalization'=> $value['company_finalization'],
				'company_borrowing'=> $value['company_borrowing'],
				'company_out_of_date'=> $value['company_out_of_date'],
				'check_phone'=> $data['customer_infor']['customer_phone_number'],
			];
			if ($value['company_name'] != ""){
				$this->company_storage_model->insert($data1);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Tạo mới thành công",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


	public function get_all_company_storage_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		$data = $this->company_storage_model->find_where('check_phone',array($data['check_phone']));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function insert_company_storage_post(){

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$data['created_at'] = $this->createdAt;
		$this->company_storage_model->insert($data);

		$result = $this->company_storage_model->findOne(['created_at'=>$data['created_at']]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Tạo mới thành công",
			'data' => $result
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function delete_company_post(){
		$data = $this->input->post();

		$result = $this->company_storage_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
			array(
				"check_phone" => "del",
			)
		);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Xóa thành công",
			'data'=> $result
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}

?>

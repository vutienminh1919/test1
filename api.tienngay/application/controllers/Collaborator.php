<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Collaborator extends REST_Controller
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
		$this->load->model('collaborator_model');
		$this->load->model('store_model');
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

	public function create_collaborator_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$data = $this->input->post();
		$data['created_at'] = (int)$data['created_at'];

		$collaborator_p = $this->collaborator_model->findOne(['ctv_phone'=> $data['ctv_phone']]);

		if (!empty($data)){
			$resNumberCode = $this->initNumberContractCode();

//			$code_area = $data['user']['stores'][count($data['user']['stores'])-1]['code_area'];


				$find_code_area = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($data['user']['stores'][count($data['user']['stores'])-1]['store_id'])]);

				if (!empty($find_code_area)) {
					$code_area = $find_code_area['code_area'];
				}


			$data['ctv_code'] = $code_area . ".00" . $resNumberCode['number_code_ctv'];
			$data['number_code_ctv'] = $resNumberCode['number_code_ctv'];
		}

		$data['stores'] = $data['user']['stores'][count($data['user']['stores'])-1]['store_id'];
		$data['created_by'] = $this->uemail;

		if (empty($collaborator_p) ) {

			$this->collaborator_model->insert($data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Tạo mới thành công",
				'data' => $data
			);
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tạo mới thất bại đã tồn tại CTV mã hoặc phone",
				'data' => $data
			);
		}
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_collaborator_model_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = [];
		$this->dataPost = $this->input->post();

		$store_id = $this->dataPost['user']['stores'][count($this->dataPost['user']['stores'])-1]['store_id'];

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('giao-dich-vien', $groupRoles) || in_array('cua-hang-truong', $groupRoles)) {
			$all = true;
		}
		if ($all) {
			$condition['created_by'] = $this->uemail;

			$condition['phone_introduce'] = !empty($this->info['phone_number']) ? $this->info['phone_number'] : "";
		} else {
			$condition['check_flag'] = "1";
		}

		$data = $this->collaborator_model->getByRole($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thành công",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
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

	public function get_one_post(){

		$data = $this->input->post();
		$collaborator = $this->collaborator_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
		if (empty($collaborator)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $collaborator
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}

	public function update_post(){

		$this->dataPost = $this->input->post();
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['ctv_code'] = $this->security->xss_clean($this->dataPost['ctv_code']);
		$this->dataPost['ctv_name'] = $this->security->xss_clean($this->dataPost['ctv_name']);
		$this->dataPost['ctv_phone'] = $this->security->xss_clean($this->dataPost['ctv_phone']);
		$this->dataPost['ctv_job'] = $this->security->xss_clean($this->dataPost['ctv_job']);
		$this->dataPost['ctv_bank_name'] = $this->security->xss_clean($this->dataPost['ctv_bank_name']);
		$this->dataPost['ctv_bank'] = $this->security->xss_clean($this->dataPost['ctv_bank']);
		$this->dataPost['user'] = $this->security->xss_clean($this->dataPost['user']);

		//Validate
		if (empty($this->dataPost['ctv_code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã CTV không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['ctv_name'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tên CTV không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['ctv_phone'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "SĐT CTV không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['ctv_bank_name'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tên ngân hàng không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['ctv_bank'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số tài khoản không được để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		$this->collaborator_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])), ["ctv_name" => $this->dataPost['ctv_name'], "ctv_phone" => $this->dataPost['ctv_phone'], "ctv_job" => $this->dataPost['ctv_job'], "ctv_bank_name" => $this->dataPost['ctv_bank_name'], "ctv_bank" => $this->dataPost['ctv_bank'], "updated_at" => $this->createdAt]);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update borrowed success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function cron_code_ctv_post(){

		$list_ctv = $this->collaborator_model->find_notNumberCode();

		if (!empty($list_ctv)){
			foreach ($list_ctv as $value){

					$resNumberCode = $value->number_code_ctv;

//					$code_area = $value->user->stores[count($value->user->stores)-1]->code_area;

					$find_code_area = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($value->user->stores[count($value->user->stores)-1]->store_id)]);

					if (!empty($find_code_area)) {
						$code_area = $find_code_area['code_area'];
					}

					$data['ctv_code'] = $code_area . ".00" . $resNumberCode;

					$this->collaborator_model->update(array("_id" => $value['_id']), ['ctv_code' => $data['ctv_code']]);

			}
		}

		echo "ok";
	}

	private function initNumberContractCode()
	{
		$maxNumber = $this->collaborator_model->getMaxNumberCodeCTV();
		$maxNumberCodeCTV = !empty($maxNumber[0]['number_code_ctv']) ? (float)$maxNumber[0]['number_code_ctv'] + 1 : 1;
		$res = array(
			"number_code_ctv" => $maxNumberCodeCTV
		);
		return $res;
	}



}

?>

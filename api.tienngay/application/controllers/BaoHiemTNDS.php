<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class BaoHiemTNDS extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('mic_tnds_model');
		$this->load->model('vbi_tnds_model');
		$this->load->model('log_mic_tnds_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("contract_model");
		$this->load->model("contract_tnds_model");
		$this->load->model("log_mic_tnds_model");
		$this->load->model("log_vbi_tnds_model");
		$this->load->model("main_property_model");
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
					$this->name = $this->info['full_name'];
					$this->phone = $this->info['phone_number'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
	}

	public function get_list_tnds_post()
	{

		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = array();
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$type_tnds = !empty($data['type_tnds']) ? $data['type_tnds'] : "";
		$full_name = !empty($data['full_name']) ? $data['full_name'] : "";
		$phone = !empty($data['phone']) ? $data['phone'] : "";
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}
		$condition['type_tnds'] = $type_tnds;

		if (!empty($phone)) {
			$condition['phone'] = $phone;
		}
		if (!empty($full_name)) {
			$condition['full_name'] = $full_name;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$tnds = $this->contract_tnds_model->getTnds($condition, $per_page, $uriSegment);
		$condition['count'] = 1;
		$total = $this->contract_tnds_model->getTnds($condition, $per_page, $uriSegment);


		if (!empty($tnds)) {
			foreach ($tnds as $t) {
				$t['id'] = (string)$t['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $tnds,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
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

	

	public function log_mic_tnds($request, $response, $type, $code)
	{
		if ($response->STATUS == TRUE) {
			$response1 = json_decode(json_encode($response));
			$response_data = [
				'TRXID' => $response1->TRXID,
				'TRXDATETIME' => $response1->TRXDATETIME,
				'STATUS' => $response1->STATUS,
				'GCN' => $response1->GCN,
				'SO_ID' => $response1->SO_ID,
				'PHI' => $response1->PHI,
				'FILE' => $response1->FILE,
				'ERRORINFO' => $response1->ERRORINFO,
			];
		}
		$dataInser = array(
			"type" => $type,
			'code' => $code,
			"response_data" => !empty($response_data) ? $response_data : $response,
			"request_data" => $request,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail,
		);
		$this->log_mic_tnds_model->insert($dataInser);
	}

	public function check_store_tcv_dong_bac($id_pgd)
	{
		$role = $this->role_model->findOne(['slug' => 'cong-ty-cpcn-tcv-dong-bac']);
		$id_store = [];
		if (count($role['stores']) > 0) {
			foreach ($role['stores'] as $store) {
				foreach ($store as $key => $value) {
					$id_store[] = $key;
				}
			}
		}
		if (in_array($id_pgd, $id_store)) {
			return 'TCVĐB';
		}
		return 'TCV';
	}
}

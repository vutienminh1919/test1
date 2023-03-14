<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');

require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class SmsMegadoc extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('contract_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model("group_role_model");
		$this->load->model("sms_model");
		$this->load->helper('lead_helper');
		$this->load->model("store_model");
		$this->load->model('province_model');
		$this->load->model('district_model');
		$this->load->model('ward_model');
		$this->load->model('log_megadoc_model');
		$this->load->model('sms_megadoc_model');
		$this->load->helper('download_helper');
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


	}
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info, $superadmin;

	public function get_all_sms_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->security->xss_clean($this->dataPost['fdate'])) ? $this->security->xss_clean($this->dataPost['fdate']) : '';
		$tdate = !empty($this->security->xss_clean($this->dataPost['tdate'])) ? $this->security->xss_clean($this->dataPost['tdate']) : '';
		$code_contract_disbursement = !empty($this->security->xss_clean($this->dataPost['code_contract_disbursement'])) ? $this->security->xss_clean($this->dataPost['code_contract_disbursement']) : '';
		$code_contract = !empty($this->security->xss_clean($this->dataPost['code_contract'])) ? $this->security->xss_clean($this->dataPost['code_contract']) : '';
		$customer_name = !empty($this->security->xss_clean($this->dataPost['customer_name'])) ? $this->security->xss_clean($this->dataPost['customer_name']) : '';
		$customer_phone = !empty($this->security->xss_clean($this->dataPost['customer_phone'])) ? $this->security->xss_clean($this->dataPost['customer_phone']) : '';
		$type_sms = !empty($this->security->xss_clean($this->dataPost['type_sms'])) ? $this->security->xss_clean($this->dataPost['type_sms']) : '';
		$type_document = !empty($this->security->xss_clean($this->dataPost['type_document'])) ? $this->security->xss_clean($this->dataPost['type_document']) : '';
		$status_sms = !empty($this->security->xss_clean($this->dataPost['status_sms'])) ? $this->security->xss_clean($this->dataPost['status_sms']) : '';
		$store = !empty($this->security->xss_clean($this->dataPost['store'])) ? $this->security->xss_clean($this->dataPost['store']) : '';

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition['start'] = strtotime(trim($fdate) . ' 00:00:00');
			$condition['end'] = strtotime(trim($tdate) . ' 23:59:59');
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = trim($code_contract);
		}
		if (!empty($customer_name)) {
			$condition['customer_name'] = trim($customer_name);
		}
		if (!empty($customer_phone)) {
			$condition['customer_phone'] = trim($customer_phone);
		}
		if (!empty($type_sms)) {
			$condition['type_sms'] = $type_sms;
		}
		if (!empty($type_document)) {
			$condition['type_document'] = $type_document;
		}
		if (!empty($status_sms)) {
			$condition['status_sms'] = $status_sms;
		}
		if (!empty($store)) {
			$condition['stores'] = is_array($store) ? $store : array($store);
		}
		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_BAD_REQUEST,
					'data' => array(),
					'groupRoles' => array(),
					'total' => 0,
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;
		}
		if (empty($store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles)|| in_array('cua-hang-truong', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
		} else {
			$condition['stores'] = (is_array($store)) ? $store : [$store];
		}
		$sms_all = $this->sms_megadoc_model->get_all_sms(array(), $condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->sms_megadoc_model->get_all_sms(array(), $condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $sms_all,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function getStores($id_user)
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$array_id_store = array();
		if (!empty($roles)) {
			foreach ($roles as $role) {
				if (!empty($role['users']) && count($role['users']) > 0) {
					$array_id_user = array();
					foreach ($role['users'] as $user) {
						array_push($array_id_user, key($user));
					}
				}
				if (in_array($id_user, $array_id_user)) {
					if (!empty($role['stores'])) {
						foreach ($role['stores'] as $store) {
							array_push($array_id_store, key($store));
						}
					}
				}
			}
		}
		return array_unique($array_id_store);
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


	
}

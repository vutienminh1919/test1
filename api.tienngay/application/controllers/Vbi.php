<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Vbi extends REST_Controller
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

	public function get_all_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$condition = array();
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('quan-ly-khu-vuc', $groupRoles) || in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'total' => $total
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;

		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		if (isset($data['isEasy'])) {
			$condition['isEasy'] = $data['isEasy'];
		}
		$vbi = $this->vbi_model->getVbi($condition, $per_page, $uriSegment);
		$total = $this->vbi_model->getVbi_total($condition);

		if (!empty($vbi)) {
			foreach ($vbi as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $vbi,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_utv_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = array();
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('quan-ly-khu-vuc', $groupRoles) ||in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'total' => $total
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;

		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		if (isset($data['isEasy'])) {
			$condition['isEasy'] = $data['isEasy'];
		}
		$vbi = $this->vbi_model->getVbi_utv($condition, $per_page, $uriSegment);
		$total = $this->vbi_model->getVbi_total($condition);

		if (!empty($vbi)) {
			foreach ($vbi as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $vbi,
			'total' => $total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_sxh_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = array();
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		$groupRoles = $this->getGroupRole($this->id);
		$all = false;
		if (in_array('quan-ly-khu-vuc', $groupRoles) ||in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
			$all = true;
		} else if (in_array('giao-dich-vien', $groupRoles)) {
			$condition['created_by'] = $this->uemail;
		}
		if (!$all) {
			$stores = $this->getStores($this->id);
			if (empty($stores)) {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => array(),
					'total' => $total
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			$condition['stores'] = $stores;

		}
		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
		if (isset($data['isEasy'])) {
			$condition['isEasy'] = $data['isEasy'];
		}
		$vbi = $this->vbi_model->getVbi_sxh($condition, $per_page, $uriSegment);
		$total = $this->vbi_model->getVbi_total($condition);

		if (!empty($vbi)) {
			foreach ($vbi as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $vbi,
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

}

?>

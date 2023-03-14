<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Dashboard_thn extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('menu_model');
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_model');
		$this->load->model('mic_model');
		$this->load->model('gic_easy_model');
		$this->load->model('gic_plt_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_gic_model');
		$this->load->model('log_mic_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
		$this->load->model("notification_app_model");
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("sms_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('coupon_model');
		$this->load->model('verify_identify_contract_model');
		$this->load->model('device_model');
		$this->load->helper('lead_helper');
		$this->load->model('vbi_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('asset_management_model');
		$this->load->model('thongbao_model');
		$this->load->model('borrowed_model');
		$this->load->model('log_borrowed_model');
		$this->load->model('borrowed_noti_model');
		$this->load->model('file_return_model');
		$this->load->model('log_file_return_model');
		$this->load->model('log_sendfile_model');
		$this->load->model('log_fileManager_model');
		$this->load->model('file_manager_model');
		$this->load->model('email_template_model');
		$this->load->model('email_history_model');
		$this->load->model('group_role_thn_model');
		$this->load->model('recording_model');
		$this->load->model('kpi_thn_model');
		$this->load->model('contract_debt_caller_model');
		$this->load->model('contract_assign_debt_model');
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('debt_contract_model');
		$this->load->model('kpi_month_model');
		$this->load->model('kpi_thn_commission_model');
		$this->load->model('report_commission_thn_model');
		$this->load->model('province_model');

		date_default_timezone_set('Asia/Ho_Chi_Minh');

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

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;


	public function groupRoles_get_all_post()
	{

		$groupRoles = $this->group_role_thn_model->find_where(array("status" => "active"));

		if (!empty($groupRoles)) {
			foreach ($groupRoles as $g) {
				$g['group_role_id'] = (string)$g['_id'];
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $groupRoles
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}

	public function create_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		if (empty($name)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Name can not empty"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Count by name
		$count = $this->group_role_thn_model->count(array("name" => $name));
		if ($count > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Name already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->group_role_thn_model->insert($this->dataPost);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create role success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$role = $this->group_role_thn_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (empty($role)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Role is not exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $role
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function update_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		if (empty($name)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Name can not empty"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$role_id = !empty($this->dataPost['role_id']) ? $this->dataPost['role_id'] : "";

		unset($this->dataPost['role_id']);
		$this->group_role_thn_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($role_id)),
			$this->dataPost
		);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update role success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function delete_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->group_role_thn_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
			array("status" => "deactive",)
		);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Delete role success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function report_recording_post()
	{

		$condition = [];

		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('Y-m-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');
		$get_call = !empty($this->dataPost['get_call']) ? $this->dataPost['get_call'] : "";
		$email_thn = !empty($this->dataPost['email_thn']) ? $this->dataPost['email_thn'] : "";
		$hangupCause = !empty($this->dataPost['hangupCause']) ? $this->dataPost['hangupCause'] : "";

		$condition['fdate'] = strtotime(trim($start) . ' 00:00:00') * 1000;
		$condition['tdate'] = strtotime(trim($end) . ' 23:59:59') * 1000;

		if (!empty($get_call)) {
			$condition['get_call'] = $get_call;
		}
		if (!empty($email_thn)) {
			$condition['email_thn'] = $email_thn;
		}
		if (!empty($hangupCause)) {
			$condition['hangupCause_search'] = $hangupCause;
		}

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$groupRoles = $this->getGroupRole($this->id);
		$user = [];
		$list_user = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call_mb = $this->call_thn_mb();
			$list_user = $this->user_model->find_where_paginate($user_call_mb);
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call_mn = $this->call_thn_mn();
			$list_user = $this->user_model->find_where_paginate($user_call_mn);
		}


		foreach ($list_user as $item) {
			array_push($user, $item['email']);
		}
		$condition["email"] = $user;

		$record = $this->recording_model->find_select($user, $get = 1);
		foreach ($record as $value) {
			$this->recording_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$value->_id)),
				["billDuration" => (int)$value['billDuration']]
			);
		}

		//recording-data
		$list_recording = $this->recording_model->get_where_thn($condition, $per_page, $uriSegment);

		//Không nghe máy
		$condition["hangupCause"] = "NO_USER_RESPONSE";
		$NO_USER_RESPONSE = $this->recording_model->get_where_thn_dashboard($condition);

		//Thuê bao
		$condition["hangupCause"] = "USER_BUSY";
		$USER_BUSY = $this->recording_model->get_where_thn_dashboard($condition);

		//Nghe máy
		$condition["hangupCause"] = "NORMAL_CLEARING";
		$NORMAL_CLEARING = $this->recording_model->get_where_thn_dashboard($condition);

		//Người gọi tắt
		$condition["hangupCause"] = "ORIGINATOR_CANCEL";
		$ORIGINATOR_CANCEL = $this->recording_model->get_where_thn_dashboard($condition);

		if (!empty($condition['hangupCause_search'])) {
			if ($condition['hangupCause_search'] == "NO_USER_RESPONSE") {
				$USER_BUSY = 0;
				$NORMAL_CLEARING = 0;
				$ORIGINATOR_CANCEL = 0;
			} elseif ($condition['hangupCause_search'] == "USER_BUSY") {
				$NO_USER_RESPONSE = 0;
				$NORMAL_CLEARING = 0;
				$ORIGINATOR_CANCEL = 0;
			} elseif ($condition['hangupCause_search'] == "NORMAL_CLEARING") {
				$NO_USER_RESPONSE = 0;
				$USER_BUSY = 0;
				$ORIGINATOR_CANCEL = 0;
			} elseif ($condition['hangupCause_search'] == "ORIGINATOR_CANCEL") {
				$NO_USER_RESPONSE = 0;
				$USER_BUSY = 0;
				$NORMAL_CLEARING = 0;
			} else {
				$NO_USER_RESPONSE = 0;
				$USER_BUSY = 0;
				$NORMAL_CLEARING = 0;
				$ORIGINATOR_CANCEL = 0;
			}

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $list_recording,
			'NO_USER_RESPONSE' => !empty($NO_USER_RESPONSE) ? $NO_USER_RESPONSE : 0,
			'USER_BUSY' => !empty($USER_BUSY) ? $USER_BUSY : 0,
			'NORMAL_CLEARING' => !empty($NORMAL_CLEARING) ? $NORMAL_CLEARING : 0,
			'ORIGINATOR_CANCEL' => !empty($ORIGINATOR_CANCEL) ? $ORIGINATOR_CANCEL : 0,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function export_recording_post()
	{

		$condition = [];

		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('Y-m-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');
		$get_call = !empty($this->dataPost['get_call']) ? $this->dataPost['get_call'] : "";
		$email_thn = !empty($this->dataPost['email_thn']) ? $this->dataPost['email_thn'] : "";
		$hangupCause = !empty($this->dataPost['hangupCause']) ? $this->dataPost['hangupCause'] : "";

		$condition['fdate'] = strtotime(trim($start) . ' 00:00:00') * 1000;
		$condition['tdate'] = strtotime(trim($end) . ' 23:59:59') * 1000;

		if (!empty($get_call)) {
			$condition['get_call'] = $get_call;
		}
		if (!empty($email_thn)) {
			$condition['email_thn'] = $email_thn;
		}
		if (!empty($hangupCause)) {
			$condition['hangupCause_search'] = $hangupCause;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$user = [];
		$list_user = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call_mb = $this->call_thn_mb();
			$list_user = $this->user_model->find_where_paginate($user_call_mb);
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call_mn = $this->call_thn_mn();
			$list_user = $this->user_model->find_where_paginate($user_call_mn);
		}

		foreach ($list_user as $item) {
			array_push($user, $item['email']);
		}
		$condition["email"] = $user;

		//recording-data

		$list_recording = $this->recording_model->get_where_thn_excel($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $list_recording,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function count_user_report_recording_post()
	{

		$condition = [];

		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('Y-m-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');
		$get_call = !empty($this->dataPost['get_call']) ? $this->dataPost['get_call'] : "";
		$email_thn = !empty($this->dataPost['email_thn']) ? $this->dataPost['email_thn'] : "";
		$hangupCause = !empty($this->dataPost['hangupCause']) ? $this->dataPost['hangupCause'] : "";

		$condition['fdate'] = strtotime(trim($start) . ' 00:00:00') * 1000;
		$condition['tdate'] = strtotime(trim($end) . ' 23:59:59') * 1000;

		if (!empty($get_call)) {
			$condition['get_call'] = $get_call;
		}
		if (!empty($email_thn)) {
			$condition['email_thn'] = $email_thn;
		}
		if (!empty($hangupCause)) {
			$condition['hangupCause_search'] = $hangupCause;
		}

		$groupRoles = $this->getGroupRole($this->id);
		$user = [];
		$list_user = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call_mb = $this->call_thn_mb();
			$list_user = $this->user_model->find_where_paginate($user_call_mb);
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call_mn = $this->call_thn_mn();
			$list_user = $this->user_model->find_where_paginate($user_call_mn);
		}

		foreach ($list_user as $item) {
			array_push($user, $item['email']);
		}
		$condition["email"] = $user;

		//recording-data
		$count = $this->recording_model->get_where_thn_count($condition);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => !empty($count) ? $count : 0
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

	public function call_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "call-thu-hoi-no-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function call_thn_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "call-thu-hoi-no-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_field_mien_bac()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-field-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_field_mien_nam()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead_field_mien_nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_call_mien_nam()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-call-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_call_mien_bac()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-call-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function tbp_thn_mien_nam()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "tbp-thn-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function tbp_thn_mien_bac()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "tbp-thn-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}


	public function field_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function field_thn_mb_b4()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-bac-b4")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function field_thn_mn_b4()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-nam-b4")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function field_thn_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "field-thu-hoi-no-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_thn_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function lead_thn_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "lead-thn-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);

						}

					}
				}
			}
		}
		return $data;
	}

	public function cron_int_billDuration_post()
	{

		$list_mb = $this->call_thn_mb();
		$list_mn = $this->call_thn_mn();
		$list = array_merge($list_mb, $list_mn);

		$record = $this->recording_model->find_select($list);

		foreach ($record as $value) {
			$this->recording_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$value->_id)),
				["billDuration" => (int)$value['billDuration']]
			);
		}

	}

	public function create_kpi_thn_post()
	{

		$groupRoles = $this->getGroupRole($this->id);
		$user_call = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}

		if (!empty($user_call)) {
			foreach ($user_call as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "Call";

				$data_arr['kpi'] = [
					"B0" => [
						'pos' => 0,
						'kpis' => 4,
						'ts_bucket' => 60
					],
					"B1" => [
						'pos' => 0,
						'kpis' => 25,
						'ts_bucket' => 40
					],
					"B2" => [
						'pos' => 0,
						'kpis' => 50,
						'ts_bucket' => 40
					],
					"B3" => [
						'pos' => 0,
						'kpis' => 60,
						'ts_bucket' => 55
					]
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], "email_thn" => $u, 'position' => 'Call'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		if (!empty($user_field)) {
			foreach ($user_field as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "Field";

				$data_arr['kpi'] = [
					"B1" => [
						'pos' => 0,
						'kpis' => 35,
						'ts_bucket' => 35
					],
					"B2" => [
						'pos' => 0,
						'kpis' => 49,
						'ts_bucket' => 35
					],
					"B3" => [
						'pos' => 0,
						'kpis' => 59,
						'ts_bucket' => 30
					],
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], "email_thn" => $u, 'position' => 'Field'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		if (!empty($user_field_b4)) {
			foreach ($user_field_b4 as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "Field_b4";

				$data_arr['kpi'] = [
					"B4" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 100
					],
					"B5" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 100
					],
					"B6" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 100
					],
					"B7" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 100
					],
					"B8" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 100
					],
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], "email_thn" => $u, 'position' => 'Field_b4'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		if (!empty($tp_thn)) {
			foreach ($tp_thn as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "TBP_THN";

				$data_arr['kpi'] = [
					"B0" => [
						'pos' => 0,
						'kpis' => 4,
						'ts_bucket' => 30
					],
					"B1" => [
						'pos' => 0,
						'kpis' => 30,
						'ts_bucket' => 20
					],
					"B2" => [
						'pos' => 0,
						'kpis' => 50,
						'ts_bucket' => 15
					],
					"B3" => [
						'pos' => 0,
						'kpis' => 60,
						'ts_bucket' => 15
					],
					"B4" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 20
					],
					"B5" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 20
					],
					"B6" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 20
					],
					"B7" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 20
					],
					"B8" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 20
					],
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], "email_thn" => $u, 'position' => 'TBP_THN'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		if (!empty($lead_call)) {

			foreach ($lead_call as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "Lead_Call";

				$data_arr['kpi'] = [
					"B0" => [
						'pos' => 0,
						'kpis' => 4,
						'ts_bucket' => 30
					],
					"B1" => [
						'pos' => 0,
						'kpis' => 25,
						'ts_bucket' => 25
					],
					"B2" => [
						'pos' => 0,
						'kpis' => 50,
						'ts_bucket' => 20
					],
					"B3" => [
						'pos' => 0,
						'kpis' => 60,
						'ts_bucket' => 15
					],
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], 'position' => 'Lead_Call'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		if (!empty($lead_field)) {
			foreach ($lead_field as $key => $u) {

				$data_arr = array();
				$data_arr['month'] = date('m', strtotime($this->dataPost['start']));
				$data_arr['year'] = date('Y', strtotime($this->dataPost['start']));
				$data_arr['email_thn'] = $u;
				$data_arr['position'] = "Lead_Field";

				$data_arr['kpi'] = [
					"B1" => [
						'pos' => 0,
						'kpis' => 35,
						'ts_bucket' => 30
					],
					"B2" => [
						'pos' => 0,
						'kpis' => 50,
						'ts_bucket' => 25
					],
					"B3" => [
						'pos' => 0,
						'kpis' => 60,
						'ts_bucket' => 20
					],
					"B4" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 25
					],
					"B5" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 25
					],
					"B6" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 25
					],
					"B7" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 25
					],
					"B8" => [
						'pos' => 0,
						'kpis' => 8,
						'ts_bucket' => 25
					],
				];

				$data_arr['created_at'] = $this->createdAt;

				$kpi = $this->kpi_thn_model->findOne(array("month" => $data_arr['month'], "year" => $data_arr['year'], "email_thn" => $u, 'position' => 'Lead_Field'));

				if (empty($kpi)) {
					$this->kpi_thn_model->insert($data_arr);
				}
			}

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create kpi success",
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_all_list_thn_post()
	{
		$cdcm = new Contract_debt_caller_model();
		$cadm = new Contract_assign_debt_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$user_call = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}

		$email_thn = array_merge($user_call, $user_field, $user_field_b4, $lead_call, $lead_field);
		$condition['email_thn'] = array('$in' => $email_thn);
		//Update Pos_call
		if (!empty($user_call)) {
			foreach ($user_call as $item) {

				for ($i = 0; $i < 4; $i++) {
					$bucket = 'B' . $i;
					$pos = $cdcm->sum_where(['year' => $year, 'month' => $month, 'debt_caller_email' => $item, 'status' => ['$in' => [2, 36]], 'bucket_old' => $bucket], '$pos_du_no');

					$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $item, 'position' => 'Call']);

					if (!empty($kpi)) {
						$kpi["kpi"][$bucket]['pos'] = (int)$pos;

						$this->kpi_thn_model->update(
							array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
							$kpi
						);
					}

				}
			}
		}

		//Update Field
		if (!empty($user_field)) {
			foreach ($user_field as $item) {
				for ($i = 1; $i < 4; $i++) {
					$bucket = 'B' . $i;
					$pos = $cadm->sum_where(['year' => $year, 'month' => $month, 'debt_field_email' => $item, 'status' => ['$in' => [2]], 'bucket_old' => $bucket], '$pos_du_no');

					$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $item, 'position' => 'Field']);

					if (!empty($kpi)) {
						$kpi["kpi"][$bucket]['pos'] = (int)$pos;

						$this->kpi_thn_model->update(
							array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
							$kpi
						);
					}

				}
			}
		}
		//Update Field_B4
		if (!empty($user_field_b4)) {
			foreach ($user_field_b4 as $item) {
				for ($i = 4; $i < 9; $i++) {
					$bucket = 'B' . $i;
					$pos = $cadm->sum_where(['year' => $year, 'month' => $month, 'debt_field_email' => $item, 'status' => ['$in' => [2]], 'bucket_old' => $bucket], '$pos_du_no');

					$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $item, 'position' => 'Field_b4']);
					if (!empty($kpi)) {
						$kpi["kpi"][$bucket]['pos'] = (int)$pos;

						$this->kpi_thn_model->update(
							array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
							$kpi
						);
					}

				}
			}
		}

		//Update TBP_THN
		if (!empty($tp_thn)) {
			for ($i = 0; $i < 9; $i++) {
				$bucket = 'B' . $i;
				$pos_field = $cadm->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $email_thn]  , 'bucket_old' => $bucket], '$pos_du_no');
				$pos_call = $cdcm->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $email_thn] ,'bucket_old' => $bucket], '$pos_du_no');

				$pos = $pos_call + $pos_field;

				$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $tp_thn[0]]);

				if (!empty($kpi)) {
					$kpi["kpi"][$bucket]['pos'] = (int)$pos;

					$this->kpi_thn_model->update(
						array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
						$kpi
					);
				}
			}
		}

		//Update lead_call
		if (!empty($lead_call)) {

			for ($i = 0; $i < 4; $i++) {
				$bucket = 'B' . $i;
				$pos = $cdcm->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $email_thn] ,'bucket_old' => $bucket], '$pos_du_no');

				$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $lead_call[0], 'position' => 'Lead_Call']);

				if (!empty($kpi)) {
					$kpi["kpi"][$bucket]['pos'] = (int)$pos;

					$this->kpi_thn_model->update(
						array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
						$kpi
					);
				}
			}
		}

		//Update Lead_Field
		if (!empty($lead_field)) {
			for ($i = 1; $i < 9; $i++) {

				$bucket = 'B' . $i;

				$pos = $cadm->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $email_thn] ,'bucket_old' => $bucket], '$pos_du_no');

				$kpi = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $lead_field[0], 'position' => 'Lead_Field']);

				if (!empty($kpi)) {
					$kpi["kpi"][$bucket]['pos'] = (int)$pos;

					$this->kpi_thn_model->update(
						array("_id" => new MongoDB\BSON\ObjectId((string)$kpi['_id'])),
						$kpi
					);
				}
			}
		}


		$condition['position'] = 'Call';
		$call_thn = $this->kpi_thn_model->find_where($condition);

		$condition['position'] = 'Field';
		$field = $this->kpi_thn_model->find_where($condition);

		$condition['position'] = 'Field_b4';
		$field_b4 = $this->kpi_thn_model->find_where($condition);

		$condition['email_thn'] = $lead_call[0];
		$condition['position'] = 'Lead_Call';
		$leader_call = $this->kpi_thn_model->find_where($condition);

		$condition['email_thn'] = ['$in' => $lead_field];
		$condition['position'] = 'Lead_Field';

		$leader_field = $this->kpi_thn_model->find_where($condition);

		$condition['email_thn'] = $tp_thn[0];
		$condition['position'] = 'TBP_THN';
		$tbp_thn = $this->kpi_thn_model->find_where($condition);


		if (!empty($call_thn)) {
			foreach ($call_thn as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		if (!empty($field)) {
			foreach ($field as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		if (!empty($field_b4)) {
			foreach ($field_b4 as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		if (!empty($tbp_thn)) {
			foreach ($tbp_thn as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		if (!empty($leader_call)) {
			foreach ($leader_call as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		if (!empty($leader_field)) {
			foreach ($leader_field as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'call_thn' => $call_thn,
			'field' => $field,
			'field_b4' => $field_b4,
			'tbp_thn' => $tbp_thn,
			'leader_call' => $leader_call,
			'leader_field' => $leader_field,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}

	public function update_kpi_thn_post()
	{

		$id = !empty($this->dataPost['id']) ? $this->dataPost['id'] : "";
		$kpi = $this->kpi_thn_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));

		if (isset($data['updated_at'])) {
			$data['updated_at'] = (int)$data['updated_at'];
		}

		unset($data['id']);

		if (isset($this->dataPost["field"])) {
			$kpi["kpi"][$this->dataPost['bucket']][$this->dataPost['field']] = (int)$this->dataPost["value"];
		}

		$this->kpi_thn_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$kpi
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update kpi success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_thn_hoahong_post()
	{
		$id = !empty($this->dataPost['id']) ? $this->dataPost['id'] : "";
		$kpi = $this->kpi_thn_commission_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));


		if (isset($this->dataPost["field"])) {
			$kpi["commision"][$this->dataPost['item']][$this->dataPost['field']] = (int)$this->dataPost["value"];
		}
		$kpi['updated_at'] = $this->createdAt;
		$kpi['updated_by'] = $this->uemail;
		$this->kpi_thn_commission_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$kpi
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update kpi success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	function lastday($month = '', $year = '')
	{
		if (empty($month)) {
			$month = date('m');
		}
		if (empty($year)) {
			$year = date('Y');
		}
		$result = strtotime("{$year}-{$month}-01");
		$result = strtotime('-1 second', strtotime('+1 month', $result));
		return date('d', $result);
	}

	public function view_dashboard_thn_manager_post()
	{

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}

		$list_user_field = array_merge($user_field, $user_field_b4);

		$all_arr = array_merge($user_call, $user_field, $user_field_b4, $lead_call, $lead_field);

		//Tổng dư nợ được giao
		$tong_du_no_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call]], '$pos_du_no');
		$tong_du_no_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field]], '$pos_du_no');
		$tong_du_no_duoc_giao = $tong_du_no_duoc_giao_call + $tong_du_no_duoc_giao_field;

		//Tổng dư nợ B0 - B3
		$tong_du_no_duoc_giao_call_b1b3 = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'bucket_old' => ['$in' => ['B0',"B1", "B2", "B3"]], 'debt_caller_email' => ['$in' => $user_call]], '$pos_du_no');
		$tong_du_no_duoc_giao_field_b1b3 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'bucket_old' => ['$in' => ['B0',"B1", "B2", "B3"]], 'debt_field_email' => ['$in' => $list_user_field]], '$pos_du_no');
		$tong_du_no_duoc_giao_b1b3 = $tong_du_no_duoc_giao_call_b1b3 + $tong_du_no_duoc_giao_field_b1b3;

		//Tổng dư nợ B4+
		$tong_du_no_duoc_giao_field_b4 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'bucket_old' => ['$in' => ["B4", "B5", "B6", "B7", "B8"]], 'debt_field_email' => ['$in' => $list_user_field]], '$pos_du_no');

		//Tổng dư nợ thu được
		$arr_code_contract_call = [];
		$arr_code_contract_field = [];
		$code_contract_hd_call = $this->contract_debt_caller_model->select_where($year, $month, $user_call);
		foreach ($code_contract_hd_call as $key => $value) {
			$arr_code_contract_call += [$value['code_contract'] => $value['pos_du_no']];
		}

		$tong_du_no_thu_duoc_call = $this->total_du_no($arr_code_contract_call, $temporary_plan_contract, $transaction, $condition_day);

		$bucket = ["B1", "B2", "B3"];
		$code_contract_hd_field = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);
		foreach ($code_contract_hd_field as $value) {
			$arr_code_contract_field += [$value['code_contract'] => $value['pos_du_no']];
		}

		$tong_du_no_thu_duoc_field_b1b3 = $this->total_du_no($arr_code_contract_field, $temporary_plan_contract, $transaction, $condition_day);

		//Nhóm field B+4
		$arr_field_b4 = [];
		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];
		$code_contract_hd_field_b4 = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);

		$tong_du_no_thu_duoc_field_b4 = $this->total_transaction($code_contract_hd_field_b4, $transaction, $condition_day, $temporary_plan_contract);

		$tong_du_no_thu_duoc = $tong_du_no_thu_duoc_call + $tong_du_no_thu_duoc_field_b1b3 + $tong_du_no_thu_duoc_field_b4;


		//Tổng dư nợ đã giao ***
		//Nhóm Call
		$tong_du_no_duoc_giao_b0b3_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'bucket_old' => ['$in' => ["B0", "B1", "B2", "B3"]], 'debt_caller_email' => ['$in' => $user_call]], '$pos_du_no');

		$bucket = ['B0', 'B1', 'B2', 'B3'];
		$code_contract_b0b3 = $this->contract_debt_caller_model->select_where($year, $month, $user_call, $bucket);
		$arr_call = [];
		foreach ($code_contract_b0b3 as $value) {
			$arr_call += [$value['code_contract'] => $value['pos_du_no']];
		}

		$tong_du_no_thu_duoc_b0b3 = $this->total_du_no($arr_call, $temporary_plan_contract, $transaction, $condition_day);

		//Tổng thực thu theo nhóm nợ

		$total_thuc_thu = [];

		for ($i = 0; $i < 9; $i++) {

			$bucket = ["B$i"];
			$code_contract_call = $this->contract_debt_caller_model->select_where($year, $month, $user_call, $bucket);
			$code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);

			$arr = array_merge($code_contract_call, $code_contract_field);

			$arr_pos = [];
			foreach ($arr as $value) {
				$arr_pos += [$value['code_contract'] => $value['pos_du_no']];
			}
			$total_du_no = 0;

			if ($i < 4) {
				$total_du_no = $this->total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day);
			}
			if ($i >= 4) {

				$total_du_no = $this->total_transaction($arr, $transaction, $condition_day, $temporary_plan_contract);
			}
			$total_thuc_thu += ["B$i" => $total_du_no];

		}

		//Tổng tiền thu được
		$arr_all = [];
		$arr_all_b0b3 = array_merge($code_contract_b0b3, $code_contract_hd_field);

		$tong_tien_thu_duoc_b0b3 = $this->total_transaction($arr_all_b0b3, $transaction, $condition_day, $temporary_plan_contract);

		$tong_tien_thu_duoc = $tong_tien_thu_duoc_b0b3 + $tong_du_no_thu_duoc_field_b4;

		//////
		/// Tỉ lệ hoàn thành Kpi

		$kpi_call = [];
		$kpi_call_top = [];
		foreach ($user_call as $key => $value) {
			//Email
			$kpi_call[$key]['email'] = $value;
			//Kpi_user
			$kpi_user = 0;

			$kpi_call[$key]['kpis'] = $this->total_kpi_user($kpi_user, $year, $month, $value, $contract_call, $temporary_plan_contract, $transaction, $condition_day);

			$kpi_call_top += [$value => $kpi_call[$key]['kpis']];
		}
		asort($kpi_call_top);


		$kpi_field = [];
		$kpi_field_top = [];
		foreach ($user_field as $key => $value) {
			//Email
			$kpi_field[$key]['email'] = $value;
			//Kpi_user
			$kpi_user = 0;
			$kpi_field[$key]['kpis'] = $this->total_kpi_user_field($kpi_user, $year, $month, $value, $contract_field, $temporary_plan_contract, $transaction, $condition_day);
			$kpi_field_top += [$value => $kpi_field[$key]['kpis']];

		}
		asort($kpi_field_top);

		$kpi_field_b4 = [];
		$kpi_field_top_b4 = [];

		foreach ($user_field_b4 as $key => $value) {
			//Email
			$kpi_field_b4[$key]['email'] = $value;
			//Kpi_user

			//BUCKET B4-B8
			$kpi_field_b4[$key]['kpis'] = $this->total_kpi_user_field_b4($year, $month, $value, $contract_field, $transaction, $condition_day, $temporary_plan_contract);
			$kpi_field_top_b4 += [$value => $kpi_field_b4[$key]['kpis']];
		}
		asort($kpi_field_top_b4);

		//Kpi toàn phòng
		$kpi_tp_thn = 0;
		$kpi_tp_thn = $this->total_kpi_tp_thn($contract_call, $contract_field, $year, $month, $tp_thn, $user_call, $user_field, $temporary_plan_contract, $transaction, $condition_day, $kpi_tp_thn, $list_user_field);

		//Tổng tiền hoa hồng
		$tong_tien_hoa_hong = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $this->uemail), '$money');

		//Hoa hồng nhân viên
		$arr_call_price = [];
		$arr_call_price_top = [];
		foreach ($user_call as $key => $value) {
			//Email
			$arr_call_price[$key]['email'] = $value;

			//Price_user
			$arr_call_price[$key]['price'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');

			$arr_call_price_top += [$value => $arr_call_price[$key]['price']];
		}
		asort($arr_call_price_top);

		$arr_field_price = [];
		$arr_field_price_top = [];
		foreach ($list_user_field as $key => $value) {
			//Email
			$arr_field_price[$key]['email'] = $value;

			//Price_user
			$arr_field_price[$key]['price'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');

			$arr_field_price_top += [$value => $arr_field_price[$key]['price']];
		}
		asort($arr_field_price_top);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'tong_du_no_duoc_giao' => !empty($tong_du_no_duoc_giao) ? $tong_du_no_duoc_giao : 0,
			'tong_du_no_duoc_giao_b1b3' => !empty($tong_du_no_duoc_giao_b1b3) ? $tong_du_no_duoc_giao_b1b3 : 0,
			'tong_du_no_duoc_giao_field_b4' => !empty($tong_du_no_duoc_giao_field_b4) ? $tong_du_no_duoc_giao_field_b4 : 0,
			'tong_du_no_thu_duoc' => !empty($tong_du_no_thu_duoc) ? $tong_du_no_thu_duoc : 0,
			'tong_tien_thu_duoc' => !empty($tong_tien_thu_duoc) ? $tong_tien_thu_duoc : 0,

			'tong_du_no_duoc_giao_b0b3' => !empty($tong_du_no_duoc_giao_b0b3_call) ? $tong_du_no_duoc_giao_b0b3_call : 0,
			'tong_du_no_thu_duoc_b0b3' => !empty($tong_du_no_thu_duoc_b0b3) ? $tong_du_no_thu_duoc_b0b3 : 0,
			'tong_du_no_duoc_giao_field_b1b3' => !empty($tong_du_no_duoc_giao_field_b1b3) ? $tong_du_no_duoc_giao_field_b1b3 : 0,
			'tong_du_no_thu_duoc_field_b1b3' => !empty($tong_du_no_thu_duoc_field_b1b3) ? $tong_du_no_thu_duoc_field_b1b3 : 0,

			'tong_du_no_thu_duoc_field_b4' => !empty($tong_du_no_thu_duoc_field_b4) ? $tong_du_no_thu_duoc_field_b4 : 0,

			'total_thuc_thu' => !empty($total_thuc_thu) ? $total_thuc_thu : [],

			'kpi_call' => !empty($kpi_call) ? $kpi_call : [],
			'kpi_call_top' => !empty($kpi_call_top) ? $kpi_call_top : [],
			'kpi_field' => !empty($kpi_field) ? $kpi_field : [],
			'kpi_field_top' => !empty($kpi_field_top) ? $kpi_field_top : [],
			'kpi_field_b4' => !empty($kpi_field_b4) ? $kpi_field_b4 : [],
			'kpi_field_top_b4' => !empty($kpi_field_top_b4) ? $kpi_field_top_b4 : [],
			'kpi_tp_thn' => !empty($kpi_tp_thn) ? $kpi_tp_thn : 0,

			'all_arr' => $all_arr,
			'tong_tien_hoa_hong' => $tong_tien_hoa_hong,

			'arr_call_price' => $arr_call_price,
			'arr_call_price_top' => $arr_call_price_top,
			'arr_field_price' => $arr_field_price,
			'arr_field_price_top' => $arr_field_price_top,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function total_kpi_user($kpi_user, $year, $month, $value, $contract_call, $temporary_plan_contract, $transaction, $condition_day)
	{
		for ($i = 0; $i < 4; $i++) {

			$bucket = "B$i";

			//HD
			$count_hd = $this->contract_debt_caller_model->select_where_count($year, $month, $value, [$bucket]);

			//BOM POS
			$du_no_goc_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => $value, 'bucket_old' => $bucket], '$pos_du_no');

			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);

			//Target KPI
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			$arr_code_contract_user_du_no = [];
			$arr_code_contract_user_tran = [];

			$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, [$value], [$bucket]);

			foreach ($arr_code_contract_user as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			foreach ($arr_code_contract_user as $item) {
				array_push($arr_code_contract_user_tran, $item['code_contract']);
			}

			//RS POS
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//REAL AMOUNT
			$so_tien_thu_duoc_theo_ky_thanh_toan = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => ['$in' => $arr_code_contract_user_tran], 'status' => 1), '$total');

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao_call != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao_call) * 100;
			}

			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));


			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;


			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}

			$kpi_user += $weight_kpi_complete_rate;


		}

		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$data = [
			'email' => $value,
			'kpi' => $kpi_user,
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}


		return $kpi_user;


	}

	public function total_kpi_user_field($kpi_user, $year, $month, $value, $contract_field, $temporary_plan_contract, $transaction, $condition_day)
	{

		for ($i = 1; $i < 4; $i++) {

			$bucket = "B$i";

			//HD
			$count_hd = $this->contract_assign_debt_model->select_where_count($year, $month, $value, [$bucket]);

			//BOM POS
			$du_no_goc_duoc_giao_call = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $value, 'bucket_old' => $bucket], '$pos_du_no');


			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);

			//Target KPI
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			$arr_code_contract_user_du_no = [];
			$arr_code_contract_user_tran = [];

			$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, [$value], [$bucket]);

			foreach ($arr_code_contract_user as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			foreach ($arr_code_contract_user as $item) {
				array_push($arr_code_contract_user_tran, $item['code_contract']);
			}

			//RS POS
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//REAL AMOUNT
			$so_tien_thu_duoc_theo_ky_thanh_toan = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => ['$in' => $arr_code_contract_user_tran], 'status' => 1), '$total');

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao_call != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao_call) * 100;
			}
			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;

			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}

			$kpi_user += $weight_kpi_complete_rate;

		}

		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$data = [
			'email' => $value,
			'kpi' => $kpi_user,
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $kpi_user;
	}

	public function total_kpi_user_field_b4($year, $month, $value, $contract_field, $transaction, $condition_day, $temporary_plan_contract)
	{

		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];
		$count_hd = $this->contract_assign_debt_model->select_where_count($year, $month, $value, $bucket);

		//BOM POS
		$du_no_goc_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $value, 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

		$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);

		//Target KPI
		$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['kpis'] : 0;

		$arr_code_contract_user_tran = [];
		$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, [$value], $bucket);

//		foreach ($arr_code_contract_user as $item) {
//			array_push($arr_code_contract_user_tran, $item['code_contract']);
//		}
//
//		//RS POS

		$so_tien_thu_duoc_theo_ky_thanh_toan = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

		//RESOLVED = RS POS/BOM POS
		$resolved = 0;
		if ($du_no_goc_duoc_giao_field != 0) {
			$resolved = ($so_tien_thu_duoc_theo_ky_thanh_toan / $du_no_goc_duoc_giao_field) * 100;
		}
		//UNRESOLVED
		$un_resolved = 100 - $resolved;

		//COMPLETION ACCORDING TO KP
		$completion = (2 - ($un_resolved / $target_kpi));

		if ($completion > 1.5) {
			$completion = 1.5;
		} elseif ($completion < 0) {
			$completion = 0;
		}
		$completion = $completion * 100;

		//DISTRIBUTION WEIGHT
		$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['ts_bucket'] : 0;

		//weight kpi complete rate
		$weight_kpi_complete_rate = 0;
		if ($distribution_weight != 0) {
			$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
		}

		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);

		$data = [
			'email' => $value,
			'kpi' => $weight_kpi_complete_rate,
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $weight_kpi_complete_rate;

	}

	public function total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day)
	{
		$total_du_no = 0;

		foreach ($arr_pos as $key => $value) {

			$tien_1_ky_phai_tra = $temporary_plan_contract->find_where_select(['code_contract' => $key]);

			if (!empty($tien_1_ky_phai_tra)) {

				if (date('d', $tien_1_ky_phai_tra[0]['ngay_ky_tra']) >= 26) {
					$condition_day['$lte'] = strtotime(date("Y-m-d", $condition_day['$lte']) . " +5 day");
				}

//				$tong_tien_thu_maphieughi = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => $key, 'status' => 1), '$total');

				$tong_tien_thu_maphieughi = 0;
				$total_where = $this->transaction_model->find_where_select(['date_pay' => $condition_day, 'status' => 1, 'code_contract' => $key], ['total']);
				if(!empty($total_where)){
					foreach ($total_where as $item){
						$tong_tien_thu_maphieughi += (int)$item['total'];
					}
				}

				$check_total_gh_cc = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => $key, 'status' => 1, 'type_payment' => ['$in' => [2,3]]), '$total');

				if (($tong_tien_thu_maphieughi + 12000 >= $tien_1_ky_phai_tra[0]['tien_tra_1_ky']) || $check_total_gh_cc > 0) {

					$total_du_no += $value;
				}
				if (date('d', $tien_1_ky_phai_tra[0]['ngay_ky_tra']) >= 26) {
					$condition_day['$lte'] = strtotime(date("Y-m-d", $condition_day['$lte']) . " -5 day");
				}
			}
		}
		return $total_du_no;
	}

	public function total_kpi_tp_thn($contract_call, $contract_field, $year, $month, $tp_thn, $user_call, $user_field, $temporary_plan_contract, $transaction, $condition_day, $kpi_tp_thn, $list_user_field)
	{
		for ($i = 0; $i < 4; $i++) {

			$bucket = "B$i";

			//BOM POS
			$du_no_goc_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call], 'bucket_old' => $bucket], '$pos_du_no');
			$du_no_goc_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field], 'bucket_old' => $bucket], '$pos_du_no');

			$du_no_goc_duoc_giao = $du_no_goc_duoc_giao_call + $du_no_goc_duoc_giao_field;

			//target_kpi
			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $tp_thn[0]]);
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			//RsPos
			$arr_code_contract_user_du_no = [];
			$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, $user_call, [$bucket]);
			$arr_code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, [$bucket]);
			$arr_call_field = array_merge($arr_code_contract_user, $arr_code_contract_field);
			foreach ($arr_call_field as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao) * 100;
			}

			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;

			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}
			$kpi_tp_thn += $weight_kpi_complete_rate;


		}


		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];

		$du_no_goc_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field] , 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

		//target_kpi
		$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $tp_thn[0]]);
		$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['kpis'] : 0;

		//RsPos
		$arr_code_contract_user_du_no = [];
		$arr_code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);

//		//RS POS
//		$so_tien_thu_duoc_theo_ky_thanh_toan = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => ['$in' => $arr_code_contract_user_du_no], 'status' => 1), '$total');
		$so_tien_thu_duoc_theo_ky_thanh_toan = $this->total_transaction($arr_code_contract_field, $transaction, $condition_day, $temporary_plan_contract);
		//RESOLVED = RS POS/BOM POS
		$resolved = 0;
		if ($du_no_goc_duoc_giao_field != 0) {
			$resolved = ($so_tien_thu_duoc_theo_ky_thanh_toan / $du_no_goc_duoc_giao_field) * 100;
		}

		//UNRESOLVED
		$un_resolved = 100 - $resolved;

		//COMPLETION ACCORDING TO KP
		$completion = (2 - ($un_resolved / $target_kpi));

		if ($completion > 1.5) {
			$completion = 1.5;
		} elseif ($completion < 0) {
			$completion = 0;
		}
		$completion = $completion * 100;

		//DISTRIBUTION WEIGHT
		$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['ts_bucket'] : 0;

		//weight kpi complete rate
		$weight_kpi_complete_rate = 0;
		if ($distribution_weight != 0) {
			$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
		}

		$kpi_tp_thn += $weight_kpi_complete_rate;


		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $tp_thn[0]]);

		$data = [
			'email' => $tp_thn[0],
			'kpi' => $kpi_tp_thn,
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $kpi_tp_thn;
	}

	public function cron_pos_post()
	{
		//chạy convert dữ liệu sang trường khác
		$pos = $this->contract_debt_caller_model->find();

		foreach ($pos as $value) {
			$this->contract_debt_caller_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$value->_id)),
				["pos_du_no" => (int)$value['root_debt']]
			);
		}
	}

	public function cron_debt_contract_post()
	{

		$contractData = $this->contract_model->find_where_select(array('status' => ['$in' => [17]]), ['code_contract', 'debt','original_debt']);
		$data = [];

		foreach ($contractData as $value) {
			$data['month'] = date('m');
			$data['year'] = date('Y');
			$data['code_contract'] = $value['code_contract'];
			$data['debt'] = $value['debt'];
			$data['original_debt'] = $value['original_debt'];
			$data['created_at'] = $this->createdAt;

			$this->debt_contract_model->insert($data);
		}
		echo "cron_ok";
	}

	public function call_update_pos_dau_ky_post()
	{
		$contract_call = $this->contract_debt_caller_model->find_where(['year' => date('Y'), 'month' => date('m')]);

		if (!empty($contract_call)) {
			foreach ($contract_call as $value) {

				$pos_dau_ky = $this->debt_contract_model->findOne(['year' => date('Y'), 'month' => date('m'),'code_contract' => $value['code_contract']]);

				$this->contract_debt_caller_model->update(
					array("_id" => new MongoDB\BSON\ObjectId((string)$value->_id)),
					["pos_du_no" => (int)$pos_dau_ky['original_debt']['du_no_goc_con_lai'], 'bucket_old' => get_bucket($pos_dau_ky['debt']['so_ngay_cham_tra'])]
				);
			}
		}


	}

	public function field_update_pos_dau_ky_post()
	{
		$contract_field = $this->contract_assign_debt_model->find_where(['year' => date('Y'), 'month' => date('m')]);

		foreach ($contract_field as $value) {

			$pos_dau_ky = $this->debt_contract_model->findOne(['year' => date('Y'), 'month' => date('m'),'code_contract' => $value['code_contract']]);

			$this->contract_assign_debt_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$value->_id)),
				["pos_du_no" => (int)$pos_dau_ky['original_debt']['du_no_goc_con_lai'], 'bucket_old' => get_bucket($pos_dau_ky['debt']['so_ngay_cham_tra'])]
			);
		}

	}


	public function kpi_call_thn_post($contract_call, $year, $month, $user_thn, $temporary_plan_contract, $transaction, $condition_day, $kpi_user)
	{

		for ($i = 0; $i < 4; $i++) {

			$bucket = "B$i";

			//BOM POS
			$du_no_goc_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => $user_thn, 'bucket_old' => $bucket], '$pos_du_no');

			//target_kpi
			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $user_thn]);
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			//RsPos
			$arr_code_contract_user_du_no = [];
			$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, [$user_thn], [$bucket]);

			foreach ($arr_code_contract_user as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao_call != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao_call) * 100;
			}

			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;

			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}
			$kpi_user += $weight_kpi_complete_rate;
		}

		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $user_thn]);
		$data = [
			'email' => $user_thn,
			'kpi' => $kpi_user,
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $kpi_user;

	}

	public function kpi_lead_call_thn($contract_call, $year, $month, $user_call, $user_lead_thn, $temporary_plan_contract, $transaction, $condition_day, $kpi_tp_thn)
	{
		for ($i = 0; $i < 4; $i++) {

			$bucket = "B$i";

			//BOM POS
			$du_no_goc_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call], 'bucket_old' => $bucket], '$pos_du_no');

			//target_kpi
			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $user_lead_thn, 'position' => 'Lead_Call']);
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			//RsPos
			$arr_code_contract_user_du_no = [];
			$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, $user_call, [$bucket]);

			foreach ($arr_code_contract_user as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao_call != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao_call) * 100;
			}

			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;

			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}
			$kpi_tp_thn += $weight_kpi_complete_rate;
		}


		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $user_lead_thn, 'kpi_lead' => "1"]);
		$data = [
			'email' => $user_lead_thn,
			'kpi' => $kpi_tp_thn,
			'kpi_lead' => "1",
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $kpi_tp_thn;
	}

	public function kpi_lead_field_thn($contract_field, $year, $month, $list_user_field, $user_lead_field, $temporary_plan_contract, $transaction, $condition_day, $user_field, $kpi_tp_thn = 0)
	{

		for ($i = 1; $i < 9; $i++) {

			$bucket = "B$i";

			//BOM POS
			$du_no_goc_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field], 'bucket_old' => $bucket], '$pos_du_no');

			$du_no_goc_duoc_giao = $du_no_goc_duoc_giao_field;

			//target_kpi
			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $user_lead_field]);
			$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['kpis'] : 0;

			//RsPos
			$arr_code_contract_user_du_no = [];
			$arr_code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $user_field, [$bucket]);
			$arr_call_field = $arr_code_contract_field;
			foreach ($arr_call_field as $item) {
				$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
			}
			$tong_du_no_goc_da_thu = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($du_no_goc_duoc_giao != 0) {
				$resolved = ($tong_du_no_goc_da_thu / $du_no_goc_duoc_giao) * 100;
			}

			//UNRESOLVED
			$un_resolved = 100 - $resolved;

			//COMPLETION ACCORDING TO KP
			$completion = (2 - ($un_resolved / $target_kpi));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$completion = $completion * 100;

			//DISTRIBUTION WEIGHT
			$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket]['ts_bucket'] : 0;

			//weight kpi complete rate
			$weight_kpi_complete_rate = 0;
			if ($distribution_weight != 0) {
				$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
			}
			$kpi_tp_thn += $weight_kpi_complete_rate;
		}


		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];

		$du_no_goc_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

		//target_kpi
		$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $user_lead_field]);
		$target_kpi = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['kpis'] : 0;

		//RsPos
		$arr_code_contract_user_du_no = [];
		$arr_code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);


//		//RS POS
//		$so_tien_thu_duoc_theo_ky_thanh_toan = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => ['$in' => $arr_code_contract_user_du_no], 'status' => 1), '$total');
		$so_tien_thu_duoc_theo_ky_thanh_toan = $this->total_transaction($arr_code_contract_field, $transaction, $condition_day, $temporary_plan_contract);
		//RESOLVED = RS POS/BOM POS
		$resolved = 0;
		if ($du_no_goc_duoc_giao_field != 0) {
			$resolved = ($so_tien_thu_duoc_theo_ky_thanh_toan / $du_no_goc_duoc_giao_field) * 100;
		}

		//UNRESOLVED
		$un_resolved = 100 - $resolved;

		//COMPLETION ACCORDING TO KP
		$completion = (2 - ($un_resolved / $target_kpi));

		if ($completion > 1.5) {
			$completion = 1.5;
		} elseif ($completion < 0) {
			$completion = 0;
		}
		$completion = $completion * 100;

		//DISTRIBUTION WEIGHT
		$distribution_weight = !empty($kpi_thn_user) ? $kpi_thn_user['kpi'][$bucket[0]]['ts_bucket'] : 0;

		//weight kpi complete rate
		$weight_kpi_complete_rate = 0;
		if ($distribution_weight != 0) {
			$weight_kpi_complete_rate = ($completion * $distribution_weight) / 100;
		}

		$kpi_tp_thn += $weight_kpi_complete_rate;

		$check_kpi = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $user_lead_field, "kpi_lead" => "1"]);
		$data = [
			'email' => $user_lead_field,
			'kpi' => $kpi_tp_thn,
			"kpi_lead" => "1",
			'month' => date('m'),
			'year' => date('Y'),
			'created_at' => $this->createdAt,
		];

		if (empty($check_kpi)) {
			$this->kpi_month_model->insert($data);
		} else {
			$this->kpi_month_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_kpi['_id'])),
				$data
			);
		}

		return $kpi_tp_thn;
	}


	public function get_all_month_kpi_post()
	{

		$search_thn = !empty($_POST['search_thn']) ? $_POST['search_thn'] : '';
		$kpi_lead = !empty($_POST['kpi_lead']) ? $_POST['kpi_lead'] : '';

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($search_thn)) {
			$created_by = $search_thn;
		} else {
			$created_by = $this->uemail;
		}

		if ($kpi_lead == '1') {
			$data = $this->kpi_month_model->find_where(['year' => date('Y'), 'email' => $created_by, 'kpi_lead' => '1']);
		} else {
			$data = $this->kpi_month_model->find_where(['year' => date('Y'), 'email' => $created_by]);

		}

		if (empty($data)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function check_user_call_field_post()
	{

		$user_call_mb = $this->call_thn_mb();
		$user_call_mn = $this->call_thn_mn();

		$user_call = array_merge($user_call_mb, $user_call_mn);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => !empty($user_call) ? $user_call : []

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function check_user_call_field_lead_post()
	{
		$user_call_mb = $this->lead_call_mien_bac();
		$user_call_mn = $this->lead_call_mien_nam();

		$user_call = array_merge($user_call_mb, $user_call_mn);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => !empty($user_call) ? $user_call : []

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function view_dashboard_thn_nhanvien_call_post()
	{

		$contract_call = new Contract_debt_caller_model();
		$user_thn = $this->uemail;
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);
		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		//Tổng dư nợ được giao
		$tong_du_no_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => $user_thn], '$pos_du_no');

		//Tổng dư nợ thu được
		$arr_code_contract_call = [];
		$code_contract_hd_call = $this->contract_debt_caller_model->select_where($year, $month, [$user_thn]);
		foreach ($code_contract_hd_call as $key => $value) {
			$arr_code_contract_call += [$value['code_contract'] => $value['pos_du_no']];
		}

		$tong_du_no_thu_duoc_call = $this->total_du_no($arr_code_contract_call, $temporary_plan_contract, $transaction, $condition_day);

		//Tổng thực thu theo nhóm nợ
		$total_thuc_thu = [];

		for ($i = 0; $i < 4; $i++) {

			$bucket = ["B$i"];
			$code_contract_call = $this->contract_debt_caller_model->select_where($year, $month, [$user_thn], $bucket);

			$arr_pos = [];
			foreach ($code_contract_call as $value) {
				$arr_pos += [$value['code_contract'] => $value['pos_du_no']];
			}
			$total_du_no = 0;

			if ($i < 4) {
				$total_du_no = $this->total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day);
			}

			$total_thuc_thu += ["B$i" => $total_du_no];

		}

		$kpi_user = 0;
		$kpi_user = $this->kpi_call_thn_post($contract_call, $year, $month, $user_thn, $temporary_plan_contract, $transaction, $condition_day, $kpi_user);

		//tong_tien_hoa_hong
		$tong_tien_hoa_hong = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $this->uemail), '$money');


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'tong_du_no_duoc_giao_call' => $tong_du_no_duoc_giao_call,
			'tong_du_no_thu_duoc_call' => $tong_du_no_thu_duoc_call,
			'total_thuc_thu' => $total_thuc_thu,
			'kpi_user' => $kpi_user,
			'tong_tien_hoa_hong' => $tong_tien_hoa_hong

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function view_dashboard_thn_nhanvien_field_post()
	{
		$contract_field = new Contract_assign_debt_model();
		$user_thn = $this->uemail;
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);
		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		//Tổng dư nợ được giao
		$tong_du_no_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $user_thn], '$pos_du_no');

		//Tổng dư nợ B1-B3
		$tong_du_no_duoc_giao_field_b1b3 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $user_thn, 'bucket_old' => ['$in' => ['B1', 'B2', 'B3']]], '$pos_du_no');

		//Tổng dư nợ B4+
		$tong_du_no_duoc_giao_field_b4 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $user_thn, 'bucket_old' => ['$in' => ['B4', 'B5', 'B6', 'B7', 'B8']]], '$pos_du_no');

		//Tổng dư nợ thu được
		$arr_code_contract_field = [];
		$bucket = ["B1", "B2", "B3"];
		$code_contract_hd_field = $this->contract_assign_debt_model->select_where($year, $month, [$user_thn], $bucket);
		foreach ($code_contract_hd_field as $value) {
			$arr_code_contract_field += [$value['code_contract'] => $value['pos_du_no']];
		}

		$tong_du_no_thu_duoc_field_b1b3 = $this->total_du_no($arr_code_contract_field, $temporary_plan_contract, $transaction, $condition_day);

		//Nhóm field B+4
		$arr_field_b4 = [];
		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];
		$code_contract_hd_field_b4 = $this->contract_assign_debt_model->select_where($year, $month, [$user_thn], $bucket);

//		$tong_du_no_thu_duoc_field_b4 = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => ['$in' => $arr_field_b4], 'status' => 1), '$total');
		$tong_du_no_thu_duoc_field_b4 = $this->total_transaction($code_contract_hd_field_b4, $transaction, $condition_day, $temporary_plan_contract);

		$tong_du_no_thu_duoc = $tong_du_no_thu_duoc_field_b1b3 + $tong_du_no_thu_duoc_field_b4;

		//Tổng thực thu theo nhóm nợ

		$total_thuc_thu = [];

		for ($i = 1; $i < 9; $i++) {

			$bucket = ["B$i"];
			$code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, [$user_thn], $bucket);

			$arr = $code_contract_field;

			$arr_pos = [];
			foreach ($arr as $value) {
				$arr_pos += [$value['code_contract'] => $value['pos_du_no']];
			}
			$total_du_no = 0;

			if ($i < 4) {
				$total_du_no = $this->total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day);
			}
			if ($i >= 4) {

				$total_du_no = $this->total_transaction($arr, $transaction, $condition_day, $temporary_plan_contract);
			}
			$total_thuc_thu += ["B$i" => $total_du_no];

		}

		$kpi_b1b3 = $this->total_kpi_user_field($kpi_user = 0, $year, $month, $user_thn, $contract_field, $temporary_plan_contract, $transaction, $condition_day);

		$kpi_b4 = $this->total_kpi_user_field_b4($year, $month, $user_thn, $contract_field, $transaction, $condition_day, $temporary_plan_contract);

		$kpi = $kpi_b1b3 + $kpi_b4;

		//tong_tien_hoa_hong
		$tong_tien_hoa_hong = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $this->uemail), '$money');

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'tong_du_no_duoc_giao_field' => $tong_du_no_duoc_giao_field,
			'tong_du_no_duoc_giao_field_b1b3' => $tong_du_no_duoc_giao_field_b1b3,
			'tong_du_no_duoc_giao_field_b4' => $tong_du_no_duoc_giao_field_b4,
			'tong_du_no_thu_duoc' => $tong_du_no_thu_duoc,
			'tong_du_no_thu_duoc_field_b1b3' => $tong_du_no_thu_duoc_field_b1b3,
			'tong_du_no_thu_duoc_field_b4' => $tong_du_no_thu_duoc_field_b4,
			'total_thuc_thu' => $total_thuc_thu,
			'kpi' => $kpi,
			'tong_tien_hoa_hong' => $tong_tien_hoa_hong


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function view_dashboard_lead_call_post()
	{

		$contract_call = new Contract_debt_caller_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();
		$user_call_mb = $this->lead_call_mien_bac();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$user_call = [];
		if (!empty($user_call_mb) && in_array($this->uemail, $user_call_mb)) {
			$user_call = $this->call_thn_mb();
		} else {
			$user_call = $this->call_thn_mn();
		}
		//Tổng dư nợ được giao
		$tong_du_no_duoc_giao_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call]], '$pos_du_no');

		//Tổng dư nợ thu được
		$arr_code_contract_call = [];
		$code_contract_hd_call = $this->contract_debt_caller_model->select_where($year, $month, $user_call);
		foreach ($code_contract_hd_call as $key => $value) {
			$arr_code_contract_call += [$value['code_contract'] => $value['pos_du_no']];
		}
		$tong_du_no_thu_duoc_call = $this->total_du_no($arr_code_contract_call, $temporary_plan_contract, $transaction, $condition_day);

		//Tổng dư nợ đã giao

		$arr_du_no_giao = [];

		foreach ($user_call as $u) {

			$tong_du_no_duoc_giao_user = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => $u], '$pos_du_no');

			//Tổng dư nợ thu được
			$arr_code_contract_call_user = [];
			$code_contract_hd_call = $this->contract_debt_caller_model->select_where($year, $month, [$u]);
			foreach ($code_contract_hd_call as $key => $value) {
				$arr_code_contract_call_user += [$value['code_contract'] => $value['pos_du_no']];
			}
			$tong_du_no_thu_duoc_call_user = $this->total_du_no($arr_code_contract_call_user, $temporary_plan_contract, $transaction, $condition_day);

			$du_no_chua_thu_duoc = $tong_du_no_duoc_giao_user - $tong_du_no_thu_duoc_call_user;

			$arr_du_no_giao += [$u => ["tong_du_no_duoc_giao_user" => $tong_du_no_duoc_giao_user,
				"tong_du_no_thu_duoc_call_user" => $tong_du_no_thu_duoc_call_user,
				"du_no_chua_thu_duoc" => $du_no_chua_thu_duoc]];

		}

		//Tỉ lệ hoàn thành Kpi
		$kpi_call = [];
		$kpi_call_top = [];
		foreach ($user_call as $key => $value) {
			//Email
			$kpi_call[$key]['email'] = $value;
			//Kpi_user
			$kpi_user = 0;

			$kpi_call[$key]['kpis'] = $this->total_kpi_user($kpi_user, $year, $month, $value, $contract_call, $temporary_plan_contract, $transaction, $condition_day);

			$kpi_call_top += [$value => $kpi_call[$key]['kpis']];
		}
		asort($kpi_call_top);


		//Tổng thực thu theo nhóm nợ
		$total_thuc_thu = [];

		for ($i = 0; $i < 4; $i++) {

			$bucket = ["B$i"];
			$code_contract_call = $this->contract_debt_caller_model->select_where($year, $month, $user_call, $bucket);

			$arr_pos = [];
			foreach ($code_contract_call as $value) {
				$arr_pos += [$value['code_contract'] => $value['pos_du_no']];
			}
			$total_du_no = 0;

			if ($i < 4) {
				$total_du_no = $this->total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day);
			}

			$total_thuc_thu += ["B$i" => $total_du_no];

		}

		//Kpi_lead
		$kpi_lead_call = $this->kpi_lead_call_thn($contract_call, $year, $month, $user_call, $this->uemail, $temporary_plan_contract, $transaction, $condition_day, $kpi_tp_thn = 0);


		//tong_tien_hoa_hong
		$tong_tien_hoa_hong = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $this->uemail), '$money');

		$arr_call_price = [];
		$arr_call_price_top = [];
		foreach ($user_call as $key => $value) {
			//Email
			$arr_call_price[$key]['email'] = $value;

			//Price_user
			$arr_call_price[$key]['price'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');

			$arr_call_price_top += [$value => $arr_call_price[$key]['price']];
		}
		asort($arr_call_price_top);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'tong_du_no_duoc_giao_call' => $tong_du_no_duoc_giao_call,
			'tong_du_no_thu_duoc_call' => $tong_du_no_thu_duoc_call,
			'arr_du_no_giao' => $arr_du_no_giao,
			'kpi_call' => !empty($kpi_call) ? $kpi_call : [],
			'kpi_call_top' => !empty($kpi_call_top) ? $kpi_call_top : [],
			'total_thuc_thu' => !empty($total_thuc_thu) ? $total_thuc_thu : [],
			'kpi_lead_call' => $kpi_lead_call,
			'tong_tien_hoa_hong' => $tong_tien_hoa_hong,
			'arr_call_price' => $arr_call_price,
			'arr_call_price_top' => $arr_call_price_top,

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function view_dashboard_lead_field_post()
	{

		$contract_field = new Contract_assign_debt_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$user_field_mb = $this->lead_field_mien_bac();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		if (!empty($user_field_mb) && in_array($this->uemail, $user_field_mb)) {
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
		} else {
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
		}
		$user_all_field = array_merge($user_field, $user_field_b4);

		//Tổng dư nợ được giao
		$tong_du_no_duoc_giao_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $user_all_field]], '$pos_du_no');

		//Tổng dư nợ được giao B1-B3
		$tong_du_no_duoc_giao_field_b1b3 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $user_all_field], 'bucket_old' => ['$in' => ['B1', 'B2', 'B3']]], '$pos_du_no');

		//Tổng dư nợ được giao B4+
		$tong_du_no_duoc_giao_field_b4 = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $user_all_field], 'bucket_old' => ['$in' => ['B4', 'B5', 'B6', 'B7', 'B8']]], '$pos_du_no');


		//Tổng dư nợ thu được
		$arr_code_contract_field = [];
		$bucket = ["B1", "B2", "B3"];
		$code_contract_hd_field = $this->contract_assign_debt_model->select_where($year, $month, $user_all_field, $bucket);
		foreach ($code_contract_hd_field as $value) {
			$arr_code_contract_field += [$value['code_contract'] => $value['pos_du_no']];
		}
		$tong_du_no_thu_duoc_field_b1b3 = $this->total_du_no($arr_code_contract_field, $temporary_plan_contract, $transaction, $condition_day);

		//Nhóm field B+4
		$arr_field_b4 = [];
		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];
		$code_contract_hd_field_b4 = $this->contract_assign_debt_model->select_where($year, $month, $user_all_field, $bucket);

		$tong_du_no_thu_duoc_field_b4 = $this->total_transaction($code_contract_hd_field_b4, $transaction, $condition_day, $temporary_plan_contract);


		$tong_du_no_thu_duoc = $tong_du_no_thu_duoc_field_b1b3 + $tong_du_no_thu_duoc_field_b4;

		//Tổng dư nợ đã giao Field B1-B8
		$arr_du_no_giao = [];

		foreach ($user_all_field as $u) {

			$tong_du_no_duoc_giao_user = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $u, 'bucket_old' => ['$in' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8']]], '$pos_du_no');

			//Tổng dư nợ thu được
			$arr_code_contract_call_user = [];
			$code_contract_hd_call = $this->contract_assign_debt_model->select_where($year, $month, [$u], ['B1', 'B2', 'B3']);
			foreach ($code_contract_hd_call as $key => $value) {
				$arr_code_contract_call_user += [$value['code_contract'] => $value['pos_du_no']];
			}
			$tong_du_no_thu_duoc_fieldb1b3 = $this->total_du_no($arr_code_contract_call_user, $temporary_plan_contract, $transaction, $condition_day);


			$arr_field_b4 = [];
			$code_contract_hd_field_b4 = $this->contract_assign_debt_model->select_where($year, $month, [$u], ['B4', 'B5', 'B6', 'B7', 'B8']);

			$tong_du_no_thu_duoc_field_b4 = $this->total_transaction($code_contract_hd_field_b4, $transaction, $condition_day, $temporary_plan_contract);

			$tong_du_no_thu_duoc_call_user = $tong_du_no_thu_duoc_fieldb1b3 + $tong_du_no_thu_duoc_field_b4;

			$du_no_chua_thu_duoc = $tong_du_no_duoc_giao_user - $tong_du_no_thu_duoc_call_user;

			$arr_du_no_giao += [$u => ["tong_du_no_duoc_giao_user" => $tong_du_no_duoc_giao_user,
				"tong_du_no_thu_duoc_field_user" => $tong_du_no_thu_duoc_call_user,
				"du_no_chua_thu_duoc" => $du_no_chua_thu_duoc]];

		}

		//Tỉ lệ hoàn thành KPI
		$kpi_field = [];
		$kpi_field_top = [];
		foreach ($user_field as $key => $value) {
			//Email
			$kpi_field[$key]['email'] = $value;
			//Kpi_user
			$kpi_user = 0;
			$kpi_field[$key]['kpis'] = $this->total_kpi_user_field($kpi_user, $year, $month, $value, $contract_field, $temporary_plan_contract, $transaction, $condition_day);
			$kpi_field_top += [$value => $kpi_field[$key]['kpis']];

		}
		asort($kpi_field_top);

		$kpi_field_b4 = [];
		$kpi_field_top_b4 = [];

		foreach ($user_field_b4 as $key => $value) {
			//Email
			$kpi_field_b4[$key]['email'] = $value;
			//Kpi_user

			//BUCKET B4-B8
			$kpi_field_b4[$key]['kpis'] = $this->total_kpi_user_field_b4($year, $month, $value, $contract_field, $transaction, $condition_day, $temporary_plan_contract);
			$kpi_field_top_b4 += [$value => $kpi_field_b4[$key]['kpis']];
		}
		asort($kpi_field_top_b4);

		//Tổng thực thu theo nhóm nợ
		$total_thuc_thu = [];
		for ($i = 1; $i < 9; $i++) {

			$bucket = ["B$i"];
			$code_contract_field = $this->contract_assign_debt_model->select_where($year, $month, $user_all_field, $bucket);

			$arr_pos = [];
			foreach ($code_contract_field as $value) {
				$arr_pos += [$value['code_contract'] => $value['pos_du_no']];
			}
			$total_du_no = 0;

			if ($i < 4) {
				$total_du_no = $this->total_du_no($arr_pos, $temporary_plan_contract, $transaction, $condition_day);
			}
			if ($i >= 4) {

				$total_du_no = $this->total_transaction($code_contract_field, $transaction, $condition_day, $temporary_plan_contract);
			}
			$total_thuc_thu += ["B$i" => $total_du_no];

		}

		//Kpi_lead_field
		$kpi_lead_field = $this->kpi_lead_field_thn($contract_field, $year, $month, $user_all_field, $this->uemail, $temporary_plan_contract, $transaction, $condition_day, $user_field, $kpi_tp_thn = 0);

		//tong_tien_hoa_hong
		$tong_tien_hoa_hong = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $this->uemail), '$money');

		$arr_field_price = [];
		$arr_field_price_top = [];
		foreach ($user_all_field as $key => $value) {
			//Email
			$arr_field_price[$key]['email'] = $value;

			//Price_user
			$arr_field_price[$key]['price'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');

			$arr_field_price_top += [$value => $arr_field_price[$key]['price']];
		}
		asort($arr_field_price_top);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'tong_du_no_duoc_giao_field' => $tong_du_no_duoc_giao_field,
			'tong_du_no_duoc_giao_field_b1b3' => $tong_du_no_duoc_giao_field_b1b3,
			'tong_du_no_duoc_giao_field_b4' => $tong_du_no_duoc_giao_field_b4,
			'tong_du_no_thu_duoc' => $tong_du_no_thu_duoc,
			'arr_du_no_giao_b1b3' => $arr_du_no_giao,

			'kpi_field' => !empty($kpi_field) ? $kpi_field : [],
			'kpi_field_top' => !empty($kpi_field_top) ? $kpi_field_top : [],
			'kpi_field_b4' => !empty($kpi_field_b4) ? $kpi_field_b4 : [],
			'kpi_field_top_b4' => !empty($kpi_field_top_b4) ? $kpi_field_top_b4 : [],
			'total_thuc_thu' => !empty($total_thuc_thu) ? $total_thuc_thu : [],
			'kpi_lead_field' => $kpi_lead_field,

			'tong_tien_hoa_hong' => $tong_tien_hoa_hong,
			'arr_field_price' => $arr_field_price,
			'arr_field_price_top' => $arr_field_price_top


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function total_transaction($code_contract_hd_call, $transaction, $condition_day, $temporary_plan_contract)
	{

		$total_du_no = 0;
		$arr_pos_filed = [];
		foreach ($code_contract_hd_call as $value) {
			array_push($arr_pos_filed, $value['code_contract']);
		}

		foreach ($arr_pos_filed as $code_contract) {

			$tien_1_ky_phai_tra = $temporary_plan_contract->find_where_select(['code_contract' => $code_contract]);

			if (!empty($tien_1_ky_phai_tra)) {

				if (date('d', $tien_1_ky_phai_tra[0]['ngay_ky_tra']) >= 26) {
					$condition_day['$lte'] = strtotime(date("Y-m-d", $condition_day['$lte']) . " +5 day");
				}

//				$total_du_no += $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => $code_contract, 'status' => 1), '$total');
				$tong_tien_thu_maphieughi = 0;
				$total_where = $this->transaction_model->find_where_select(['date_pay' => $condition_day, 'status' => 1, 'code_contract' => $code_contract], ['total']);
				if(!empty($total_where)){
					foreach ($total_where as $item){
						$tong_tien_thu_maphieughi += (int)$item['total'];
					}
				}
				$total_du_no += $tong_tien_thu_maphieughi;

				if (date('d', $tien_1_ky_phai_tra[0]['ngay_ky_tra']) >= 26) {
					$condition_day['$lte'] = strtotime(date("Y-m-d", $condition_day['$lte']) . " -5 day");
				}
			}

		}

		return $total_du_no;

	}

	public function setup_hh_thn_post()
	{

		$data = $this->kpi_thn_commission_model->find();

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => !empty($data[0]) ? $data[0] : []

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function getGroupRole_thn()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'thu-hoi-no'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						foreach ($item as $e) {
							array_push($arr, $e);

						}
					}

				}
			}
		}
		return $arr;
	}

	public function cron_commission_thn_post()
	{

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$list_user_thn = $this->getGroupRole_thn();

		$user_call = array_merge($this->call_thn_mb(), $this->call_thn_mn());
		$user_field = array_merge($this->field_thn_mb(), $this->field_thn_mn());
		$user_field_b4 = array_merge($this->field_thn_mb_b4(), $this->field_thn_mn_b4());
		$lead_call = array_merge($this->lead_call_mien_bac(), $this->lead_call_mien_nam());
		$lead_field = array_merge($this->lead_field_mien_bac(), $this->lead_field_mien_nam());
		$tp_thn = array_merge($this->tbp_thn_mien_nam(), $this->tbp_thn_mien_bac());


		$contract_field = new Contract_assign_debt_model();
		$contract_call = new Contract_debt_caller_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$contract = new Contract_model();

		foreach ($list_user_thn as $value) {
			if (in_array($value, $tp_thn) && !in_array($value, $lead_field) && !in_array($value, $lead_call)) {
				$this->commission_tp_thn($value, $month, $year, $condition_day);
			}
			if (in_array($value, $user_call) && !in_array($value, $lead_call)) {
				$this->commission_user_call($value, $month, $year, $condition_day, $contract_call, $transaction, $contract);
			}


			if (in_array($value, $user_field) && !in_array($value, $lead_field)) {
				$this->commission_user_field($value, $month, $year, $condition_day, $contract_call, $transaction, $contract);
			}
			if (in_array($value, $user_field_b4) && !in_array($value, $lead_field)) {
				$this->commission_user_field_b4($value, $month, $year, $condition_day, $contract_call, $transaction, $contract);
			}

			if (in_array($value, $lead_call)) {
				$this->commission_user_lead_call($value, $month, $year, $condition_day, $contract_call, $transaction, $contract);
			}
			if (in_array($value, $lead_field)) {
				$this->commission_user_lead_field($value, $month, $year, $condition_day, $contract_call, $transaction, $contract);
			}


		}


		echo "cronJob_ok";
	}

	public function commission_tp_thn($value, $month, $year, $condition_day)
	{

		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$money = $this->money_tp_thn($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);


	}

	public function commission_user_call($value, $month, $year, $condition_day, $contract_call, $transaction, $contract)
	{

		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$money = $this->money_user_call($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);

		//Create contest
		//Tất toán ô tô - xe máy
		$bucket = ['B0','B1','B2', 'B3'];
		$this->insert_contest_thn($year, $month, $value, $bucket, $transaction, $contract, $condition_day, 'Call');

	}

	public function commission_user_lead_call($value, $month, $year, $condition_day, $contract_call, $transaction, $contract)
	{
		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value,'kpi_lead' => '1']);
		$money = $this->money_user_lead_call($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);

		//Create contest
		//Tất toán ô tô - xe máy
		$bucket = ['B0','B1','B2', 'B3'];
		$this->insert_contest_thn($year, $month, $value, $bucket, $transaction, $contract, $condition_day, 'Call');
	}

	public function commission_user_field($value, $month, $year, $condition_day, $contract_call, $transaction, $contract)
	{
		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$money = $this->money_user_field_b1b3($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);

		//Create contest
		//Tất toán ô tô - xe máy
		$bucket = ['B1','B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8'];
		$this->insert_contest_thn($year, $month, $value, $bucket, $transaction, $contract, $condition_day, 'Field');

	}

	public function commission_user_lead_field($value, $month, $year, $condition_day, $contract_call, $transaction, $contract)
	{
		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value , 'kpi_lead' => '1']);
		$money = $this->money_user_lead_field($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);

		//Create contest
		//Tất toán ô tô - xe máy
		$bucket = ['B1','B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8'];
		$this->insert_contest_thn($year, $month, $value, $bucket, $transaction, $contract, $condition_day, 'Field');
	}

	public function commission_user_field_b4($value, $month, $year, $condition_day, $contract_call, $transaction, $contract)
	{
		//Target KPI
		$kpi_thn_user = $this->kpi_month_model->findOne(['year' => $year, 'month' => $month, 'email' => $value]);
		$money = $this->money_user_field_b4($kpi_thn_user['kpi']);

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'kpi' => $kpi_thn_user,
			'money_kpi' => true,
			'created_at' => $this->createdAt,
		];

		$this->insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user);

		//Create contest
		//Tất toán ô tô - xe máy
		$bucket = ['B4', 'B5', 'B6', 'B7', 'B8'];
		$this->insert_contest_thn($year, $month, $value, $bucket, $transaction, $contract, $condition_day, 'Field');
	}

	public function insert_contest_thn($year, $month, $value, $bucket = [], $transaction, $contract, $condition_day, $role)
	{
		if ($role == 'Call') {
			$code_contract_hd_call = $this->contract_debt_caller_model->select_where($year, $month, [$value], $bucket);
		} elseif ($role == 'Field') {
			$code_contract_hd_call = $this->contract_assign_debt_model->select_where($year, $month, [$value], $bucket);

		}


		if (!empty($code_contract_hd_call)) {
			foreach ($code_contract_hd_call as $item) {

				$money = 0;
				//Check miễn giảm + tất toán

				$check_rule = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => $item['code_contract'],'status' => 1), '$total_deductible');


				if (($check_rule == 0) && ($item['bucket_old'] == 'B2' || $item['bucket_old'] == 'B3' || $item['bucket_old'] == 'B4' || $item['bucket_old'] == 'B5' || $item['bucket_old'] == 'B6' || $item['bucket_old'] == 'B7' || $item['bucket_old'] == 'B8')) {

					$check_contract = $this->contract_model->findOne_select_thn($item['code_contract']);

					if (!empty($check_contract) && ($check_contract[0]['loan_infor']['type_property']['code'] == 'XM' || $check_contract[0]['loan_infor']['type_property']['code'] == 'OTO')) {
						if ($check_contract[0]['loan_infor']['type_property']['code'] == 'XM') {
							$money = 300000;
						}
						if($role == 'Call' && $check_contract[0]['loan_infor']['type_property']['code'] == 'OTO' && ($item['bucket_old'] == 'B2' || $item['bucket_old'] == 'B3')){
							$money = 500000;
						}
						if ($check_contract[0]['loan_infor']['type_property']['code'] == 'OTO' && ($item['bucket_old'] == 'B2' || $item['bucket_old'] == 'B3')) {
							$money = 1000000;
						}
						if ($check_contract[0]['loan_infor']['type_property']['code'] == 'OTO' && ($item['bucket_old'] == 'B4' || $item['bucket_old'] == 'B5' || $item['bucket_old'] == 'B6' || $item['bucket_old'] == 'B7' || $item['bucket_old'] == 'B8')) {
							$money = 1000000;
						}

						$this->insert_contest($year, $month, $value, $money, $check_contract, $item['code_contract'], $item['bucket_old'], 1);

					}
				}

				//Tổng phí phạt
				$tong_phi_phat = 0;
				$tong_phi_phat = $transaction->sum_where(array('date_pay' => $condition_day, 'code_contract' => $item['code_contract'], 'status' => 1), '$so_tien_phi_cham_tra_da_tra');


				if($tong_phi_phat > 0) {
					$tien_hoa_hong = 0;
					if ($role == 'Call') {
						if ($item['bucket_old'] == "B0" || $item['bucket_old'] == "B1") {
							$tien_hoa_hong = $tong_phi_phat * 0.1;
						} elseif ($item['bucket_old'] == "B2" || $item['bucket_old'] == "B3") {
							$tien_hoa_hong = $tong_phi_phat * 0.15;
						}
					}
					if ($role == 'Field') {
						if ($item['bucket_old'] == "B2" || $item['bucket_old'] == "B3" || $item['bucket_old'] == "B1") {
							$tien_hoa_hong = $tong_phi_phat * 0.2;
						} elseif ($item['bucket_old'] == "B4" || $item['bucket_old'] == "B5" || $item['bucket_old'] == "B6" || $item['bucket_old'] == "B7" || $item['bucket_old'] == "B8") {
							$tien_hoa_hong = $tong_phi_phat * 0.3;
						}
					}
					$this->insert_contest($year, $month, $value, $tien_hoa_hong, 'tong_phi_phat', $item['code_contract'], $item['bucket_old'], $tong_phi_phat);
				}


				//Bucket Rollback
				if ($item['bucket_old'] == 'B2' || $item['bucket_old'] == 'B3' || $item['bucket_old'] == "B1") {
					$price_rollback = 0;
					$so_ngay_cham_tra_old = $this->debt_contract_model->findOne(['year' => $year, 'month' => $month, 'code_contract' => $item['code_contract']]);

					$so_ngay_cham_tra = $this->contract_model->findOne_select_debt(['code_contract' => $item['code_contract']]);

					if (!empty($so_ngay_cham_tra_old) && !empty($so_ngay_cham_tra)) {
						$bucket_old = trim(get_bucket($so_ngay_cham_tra_old['debt']['so_ngay_cham_tra']), 'B');
						$bucket = trim(get_bucket($so_ngay_cham_tra['debt']['so_ngay_cham_tra']), 'B');

						$n = $bucket_old - $bucket;

						if ($role == 'Call') {
							$price_rollback = 40000 * $n;
						}
						if ($role == 'Field') {
							$price_rollback = 50000 * $n;
						}

						if ($so_ngay_cham_tra['status'] == 19){
							$price_rollback = 0;
						}
						if ($price_rollback > 0){
							$this->insert_contest($year, $month, $value, $price_rollback, 'rollback', $item['code_contract'], $item['bucket_old'], $n);

						}

					}
				}

			}
		}

	}

	public function insert_commission_thn($year, $month, $value, $data, $money, $kpi_thn_user)
	{
		$check_money_kpi = $this->report_commission_thn_model->findOne(['year' => $year, 'month' => $month, 'email' => $value, 'money_kpi' => true]);
		if (empty($check_money_kpi)) {
			$this->report_commission_thn_model->insert($data);
		} else {
			$this->report_commission_thn_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_money_kpi['_id'])),
				['money' => $money, 'kpi' => $kpi_thn_user]
			);
		}
	}

	public function insert_contest($year, $month, $value, $money = 0, $check_contract, $code_contract, $bucket, $tong_phi_phat = 0)
	{

		$data = [
			'year' => $year,
			'month' => $month,
			'email' => $value,
			'money' => $money,
			'currency' => $check_contract,
			'bucket' => $bucket,
			'code_contract' => $code_contract,
			'created_at' => $this->createdAt,
			'total_money' => $tong_phi_phat
		];

		$check_money_kpi = $this->report_commission_thn_model->findOne(['year' => $year, 'month' => $month, 'email' => $value, 'currency' => $check_contract, 'code_contract' => $code_contract]);
		if (empty($check_money_kpi)) {
			$this->report_commission_thn_model->insert($data);
		} else {
			$this->report_commission_thn_model->update(
				array("_id" => new MongoDB\BSON\ObjectId((string)$check_money_kpi['_id'])),
				['money' => $money]
			);
		}

	}


	private function money_tp_thn($kpi)
	{
		$money = 0;
		if ($kpi >= 70 && $kpi < 75) {
			$money = 3000000;
		} elseif ($kpi >= 75 && $kpi < 80) {
			$money = 5000000;
		} elseif ($kpi >= 80 && $kpi < 90) {
			$money = 7000000;
		} elseif ($kpi >= 90 && $kpi < 100) {
			$money = 9000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 13000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 15000000;
		} elseif ($kpi >= 120 && $kpi <= 150) {
			$money = 20000000;
		} elseif ($kpi > 150) {
			$money = 25000000;
		}
		return $money;
	}

	private function money_user_call($kpi)
	{
		$money = 0;
		if ($kpi >= 75 && $kpi < 80) {
			$money = 1000000;
		} elseif ($kpi >= 80 && $kpi < 85) {
			$money = 2000000;
		} elseif ($kpi >= 85 && $kpi < 90) {
			$money = 3000000;
		} elseif ($kpi >= 90 && $kpi < 95) {
			$money = 4000000;
		} elseif ($kpi >= 95 && $kpi < 100) {
			$money = 5000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 6000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 7000000;
		} elseif ($kpi >= 120 && $kpi < 135) {
			$money = 9000000;
		} elseif ($kpi >= 135 && $kpi <= 150) {
			$money = 11000000;
		}
		return $money;
	}

	private function money_user_lead_call($kpi)
	{
		$money = 0;
		if ($kpi >= 75 && $kpi < 80) {
			$money = 1000000;
		} elseif ($kpi >= 80 && $kpi < 85) {
			$money = 2000000;
		} elseif ($kpi >= 85 && $kpi < 90) {
			$money = 3000000;
		} elseif ($kpi >= 90 && $kpi < 95) {
			$money = 4000000;
		} elseif ($kpi >= 95 && $kpi < 100) {
			$money = 5000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 7000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 9000000;
		} elseif ($kpi >= 120 && $kpi < 135) {
			$money = 11000000;
		} elseif ($kpi >= 135 && $kpi <= 150) {
			$money = 13000000;
		}
		return $money;
	}

	private function money_user_field_b1b3($kpi)
	{
		$money = 0;
		if ($kpi >= 75 && $kpi < 80) {
			$money = 1000000;
		} elseif ($kpi >= 80 && $kpi < 85) {
			$money = 2000000;
		} elseif ($kpi >= 85 && $kpi < 90) {
			$money = 3000000;
		} elseif ($kpi >= 90 && $kpi < 100) {
			$money = 5000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 7000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 9000000;
		} elseif ($kpi >= 120 && $kpi < 150) {
			$money = 13000000;
		} elseif ($kpi >= 150) {
			$money = 17000000;
		}
		return $money;
	}

	private function money_user_field_b4($kpi)
	{
		$money = 0;
		if ($kpi >= 70 && $kpi < 75) {
			$money = 2000000;
		} elseif ($kpi >= 75 && $kpi < 80) {
			$money = 3000000;
		} elseif ($kpi >= 80 && $kpi < 85) {
			$money = 4000000;
		} elseif ($kpi >= 85 && $kpi < 90) {
			$money = 5000000;
		} elseif ($kpi >= 90 && $kpi < 95) {
			$money = 7000000;
		} elseif ($kpi >= 95 && $kpi < 100) {
			$money = 8000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 11000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 13000000;
		} elseif ($kpi >= 120 && $kpi < 150) {
			$money = 15000000;
		} elseif ($kpi >= 150) {
			$money = 20000000;
		}
		return $money;
	}

	private function money_user_lead_field($kpi)
	{
		$money = 0;
		if ($kpi >= 70 && $kpi < 75) {
			$money = 2000000;
		} elseif ($kpi >= 75 && $kpi < 80) {
			$money = 3000000;
		} elseif ($kpi >= 80 && $kpi < 85) {
			$money = 4000000;
		} elseif ($kpi >= 85 && $kpi < 90) {
			$money = 5000000;
		} elseif ($kpi >= 90 && $kpi < 95) {
			$money = 6000000;
		} elseif ($kpi >= 95 && $kpi < 100) {
			$money = 8000000;
		} elseif ($kpi >= 100 && $kpi < 110) {
			$money = 11000000;
		} elseif ($kpi >= 110 && $kpi < 120) {
			$money = 15000000;
		} elseif ($kpi >= 120 && $kpi <= 150) {
			$money = 17000000;
		}
		return $money;
	}


	public function export_data_call_thn_post()
	{

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
		}


		$data = [];
		$i = 0;
		foreach ($user_call as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;



			for ($i = 0; $i < 4; $i++) {

				//Bucket
				$data[$key]['bucket'][$i]['bucket'] = "B$i";

				//HD
				$data[$key]["bucket"][$i]['HĐ'] = $contract_call->select_where_count($year, $month, $value, ["B$i"]);

				//BOM_POS
				$data[$key]["bucket"][$i]['BOM_POS'] = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => $value, 'bucket_old' => "B$i"], '$pos_du_no');


				//Target KPI
				$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
				$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['kpis'] : 0;

				//RS_POS
				$arr_code_contract_user_du_no = [];

				$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, [$value], ["B$i"]);

				foreach ($arr_code_contract_user as $item) {
					$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
				}

				$data[$key]["bucket"][$i]['RS_POS'] = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

				//REAL AMOUNT

				$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

				//RESOLVED = RS POS/BOM POS
				$resolved = 0;
				if ($data[$key]["bucket"][$i]['BOM_POS'] != 0) {
					$data[$key]["bucket"][$i]['RESOLVED'] = ($data[$key]["bucket"][$i]['RS_POS'] / $data[$key]["bucket"][$i]['BOM_POS']) * 100;
				}

				//UNRESOLVED
				$data[$key]["bucket"][$i]['UNRESOLVED'] = 100 - $data[$key]["bucket"][$i]['RESOLVED'];

				//COMPLETION ACCORDING TO KPI
				$completion = (2 - ($data[$key]["bucket"][$i]['UNRESOLVED'] / $data[$key]["bucket"][$i]['target_kpi']));

				if ($completion > 1.5) {
					$completion = 1.5;
				} elseif ($completion < 0) {
					$completion = 0;
				}
				$data[$key]["bucket"][$i]['completion'] = $completion * 100;

				//DISTRIBUTION WEIGHT
				$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['ts_bucket'] : 0;

				//WEIGHT KPI COMPLETE RATE
				$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = 0;
				if ($data[$key]["bucket"][$i]['distribution_weight'] != 0) {
					$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = ($data[$key]["bucket"][$i]['completion'] * $data[$key]["bucket"][$i]['distribution_weight']) / 100;
				}

				$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"][$i]['weight_kpi_complete_rate'];

			}

			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');;


		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function export_kpi_leader_call_b0b3_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_lead_call = $this->lead_call_mien_bac();
			$user_call = $this->call_thn_mb();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_lead_call = $this->lead_call_mien_nam();
			$user_call = $this->call_thn_mn();
		}


		$data = [];
		$i = 0;
		foreach ($user_lead_call as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;



			for ($i = 0; $i < 4; $i++) {

				//Bucket
				$data[$key]['bucket'][$i]['bucket'] = "B$i";

				//HD
				$data[$key]["bucket"][$i]['HĐ'] = $contract_call->select_where_count($year, $month, ['$in' => $user_call], ["B$i"]);

				//BOM_POS
				$data[$key]["bucket"][$i]['BOM_POS'] = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call], 'bucket_old' => "B$i"], '$pos_du_no');

				//Target KPI
				$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value,  "position" => "Lead_Call"]);
				$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['kpis'] : 0;

				//RS_POS
				$arr_code_contract_user_du_no = [];

				$arr_code_contract_user = $this->contract_debt_caller_model->select_where($year, $month, $user_call, ["B$i"]);

				foreach ($arr_code_contract_user as $item) {
					$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
				}

				$data[$key]["bucket"][$i]['RS_POS'] = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

				//REAL AMOUNT

				$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

				//RESOLVED = RS POS/BOM POS
				$resolved = 0;
				if ($data[$key]["bucket"][$i]['BOM_POS'] != 0) {
					$data[$key]["bucket"][$i]['RESOLVED'] = ($data[$key]["bucket"][$i]['RS_POS'] / $data[$key]["bucket"][$i]['BOM_POS']) * 100;
				}

				//UNRESOLVED
				$data[$key]["bucket"][$i]['UNRESOLVED'] = 100 - $data[$key]["bucket"][$i]['RESOLVED'];

				//COMPLETION ACCORDING TO KPI
				$completion = (2 - ($data[$key]["bucket"][$i]['UNRESOLVED'] / $data[$key]["bucket"][$i]['target_kpi']));

				if ($completion > 1.5) {
					$completion = 1.5;
				} elseif ($completion < 0) {
					$completion = 0;
				}
				$data[$key]["bucket"][$i]['completion'] = $completion * 100;

				//DISTRIBUTION WEIGHT
				$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['ts_bucket'] : 0;

				//WEIGHT KPI COMPLETE RATE
				$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = 0;
				if ($data[$key]["bucket"][$i]['distribution_weight'] != 0) {
					$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = ($data[$key]["bucket"][$i]['completion'] * $data[$key]["bucket"][$i]['distribution_weight']) / 100;
				}

				$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"][$i]['weight_kpi_complete_rate'];

			}

			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');


		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}


	public function export_kpi_field_b1b3_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_field = $this->field_thn_mb();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_field = $this->field_thn_mn();

		}


		$data = [];
		foreach ($user_field as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;


			for ($i = 1; $i < 4; $i++) {

				//Bucket
				$data[$key]['bucket'][$i]['bucket'] = "B$i";

				//HD
				$data[$key]["bucket"][$i]['HĐ'] = $contract_field->select_where_count($year, $month, $value, ["B$i"]);

				//BOM_POS
				$data[$key]["bucket"][$i]['BOM_POS'] = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $value, 'bucket_old' => "B$i"], '$pos_du_no');

				//Target KPI
				$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
				$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['kpis'] : 0;

				//RS_POS
				$arr_code_contract_user_du_no = [];

				$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, [$value], ["B$i"]);

				foreach ($arr_code_contract_user as $item) {
					$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
				}

				$data[$key]["bucket"][$i]['RS_POS'] = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

				//REAL AMOUNT
				$arr_code_contract_user_tran = [];
				foreach ($arr_code_contract_user as $item) {
					array_push($arr_code_contract_user_tran, $item['code_contract']);
				}
				$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

				//RESOLVED = RS POS/BOM POS
				$resolved = 0;
				if ($data[$key]["bucket"][$i]['BOM_POS'] != 0) {
					$data[$key]["bucket"][$i]['RESOLVED'] = ($data[$key]["bucket"][$i]['RS_POS'] / $data[$key]["bucket"][$i]['BOM_POS']) * 100;
				}

				//UNRESOLVED
				$data[$key]["bucket"][$i]['UNRESOLVED'] = 100 - $data[$key]["bucket"][$i]['RESOLVED'];

				//COMPLETION ACCORDING TO KPI
				$completion = (2 - ($data[$key]["bucket"][$i]['UNRESOLVED'] / $data[$key]["bucket"][$i]['target_kpi']));

				if ($completion > 1.5) {
					$completion = 1.5;
				} elseif ($completion < 0) {
					$completion = 0;
				}
				$data[$key]["bucket"][$i]['completion'] = $completion * 100;

				//DISTRIBUTION WEIGHT
				$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['ts_bucket'] : 0;

				//WEIGHT KPI COMPLETE RATE
				$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = 0;
				if ($data[$key]["bucket"][$i]['distribution_weight'] != 0) {
					$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = ($data[$key]["bucket"][$i]['completion'] * $data[$key]["bucket"][$i]['distribution_weight']) / 100;
				}

				$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"][$i]['weight_kpi_complete_rate'];

			}

			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');


		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function export_kpi_field_b4_post(){


		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_field_b4 = $this->field_thn_mb_b4();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_field_b4 = $this->field_thn_mn_b4();

		}

		$data = [];
		foreach ($user_field_b4 as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;

			$bucket = ['B4','B5','B6','B7','B8'];
			//Bucket
			$data[$key]['bucket']['bucket'] = "B4 - B8";

			//HD
			$data[$key]["bucket"]['HĐ'] = $contract_field->select_where_count($year, $month, $value, $bucket);

			//BOM_POS
			$data[$key]["bucket"]['BOM_POS'] = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => $value, 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

			//Target KPI
			$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
			$data[$key]["bucket"]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['kpis'] : 0;

			//RS_POS
			$arr_code_contract_user_tran = [];

			$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, [$value], $bucket);

			foreach ($arr_code_contract_user as $item) {
				array_push($arr_code_contract_user_tran, $item['code_contract']);
			}

			$data[$key]["bucket"]['RS_POS'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

			//REAL AMOUNT
			$data[$key]["bucket"]['REAL_AMOUNT'] = $data[$key]["bucket"]['RS_POS'];

			//RESOLVED = RS POS/BOM POS
			$resolved = 0;
			if ($data[$key]["bucket"]['BOM_POS'] != 0) {
				$data[$key]["bucket"]['RESOLVED'] = ($data[$key]["bucket"]['RS_POS'] / $data[$key]["bucket"]['BOM_POS']) * 100;
			}

			//UNRESOLVED
			$data[$key]["bucket"]['UNRESOLVED'] = 100 - $data[$key]["bucket"]['RESOLVED'];

			//COMPLETION ACCORDING TO KPI
			$completion = (2 - ($data[$key]["bucket"]['UNRESOLVED'] / $data[$key]["bucket"]['target_kpi']));

			if ($completion > 1.5) {
				$completion = 1.5;
			} elseif ($completion < 0) {
				$completion = 0;
			}
			$data[$key]["bucket"]['completion'] = $completion * 100;

			//DISTRIBUTION WEIGHT
			$data[$key]["bucket"]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['ts_bucket'] : 0;

			//WEIGHT KPI COMPLETE RATE
			$data[$key]["bucket"]['weight_kpi_complete_rate'] = 0;
			if ($data[$key]["bucket"]['distribution_weight'] != 0) {
				$data[$key]["bucket"]['weight_kpi_complete_rate'] = ($data[$key]["bucket"]['completion'] * $data[$key]["bucket"]['distribution_weight']) / 100;
			}

			$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"]['weight_kpi_complete_rate'];


			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');
		}




		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;



	}

	public function export_kpi_leader_field_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$lead_field = $this->lead_field_mien_bac();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$lead_field = $this->lead_field_mien_nam();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
		}

		$data = [];

		foreach ($lead_field as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;


			for ($i = 1; $i < 5; $i++) {

				if ($i<4){
					//Bucket
					$data[$key]['bucket'][$i]['bucket'] = "B$i";

					//HD
					$data[$key]["bucket"][$i]['HĐ'] = $contract_field->select_where_count($year, $month, ['$in' => $user_field], ["B$i"]);

					//BOM_POS
					$data[$key]["bucket"][$i]['BOM_POS'] = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $user_field], 'bucket_old' => "B$i"], '$pos_du_no');

					//Target KPI
					$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
					$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['kpis'] : 0;

					//RS_POS
					$arr_code_contract_user_du_no = [];

					$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, $user_field, ["B$i"]);

					foreach ($arr_code_contract_user as $item) {
						$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
					}

					$data[$key]["bucket"][$i]['RS_POS'] = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

					//REAL AMOUNT

					$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

				} else {

					$bucket = ['B4','B5','B6','B7','B8'];
					//Bucket
					$data[$key]['bucket'][$i]['bucket'] = "B4 - B8";

					//HD
					$data[$key]["bucket"][$i]['HĐ'] = $contract_field->select_where_count($year, $month, ['$in' => $user_field_b4], $bucket);

					//BOM_POS
					$data[$key]["bucket"][$i]['BOM_POS'] = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $user_field_b4], 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

					//Target KPI
					$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
					$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['kpis'] : 0;

					//RS_POS
					$arr_code_contract_user_tran = [];

					$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, $user_field_b4, $bucket);


					$data[$key]["bucket"][$i]['RS_POS'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

					//REAL AMOUNT
					$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $data[$key]["bucket"][$i]['RS_POS'];

				}

				//RESOLVED = RS POS/BOM POS
				$resolved = 0;
				if ($data[$key]["bucket"][$i]['BOM_POS'] != 0) {
					$data[$key]["bucket"][$i]['RESOLVED'] = ($data[$key]["bucket"][$i]['RS_POS'] / $data[$key]["bucket"][$i]['BOM_POS']) * 100;
				}

				//UNRESOLVED
				$data[$key]["bucket"][$i]['UNRESOLVED'] = 100 - $data[$key]["bucket"][$i]['RESOLVED'];

				//COMPLETION ACCORDING TO KPI
				$completion = (2 - ($data[$key]["bucket"][$i]['UNRESOLVED'] / $data[$key]["bucket"][$i]['target_kpi']));

				if ($completion > 1.5) {
					$completion = 1.5;
				} elseif ($completion < 0) {
					$completion = 0;
				}
				$data[$key]["bucket"][$i]['completion'] = $completion * 100;

				//DISTRIBUTION WEIGHT
				if ($i<4){
					$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['ts_bucket'] : 0;
				} else {
					$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['ts_bucket'] : 0;
				}

				//WEIGHT KPI COMPLETE RATE
				$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = 0;
				if ($data[$key]["bucket"][$i]['distribution_weight'] != 0) {
					$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = ($data[$key]["bucket"][$i]['completion'] * $data[$key]["bucket"][$i]['distribution_weight']) / 100;
				}

				$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"][$i]['weight_kpi_complete_rate'];

			}


			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');


		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function export_kpi_thn_all_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}

		$list_user_field = array_merge($user_field, $user_field_b4);

		$all_arr = array_merge($user_call, $user_field, $user_field_b4, $lead_call, $lead_field);

		$data = [];

		foreach ($tp_thn as $key => $value) {

			$SUMMARY_OF_PERFORMANCE_KPI = 0;

			$data[$key]['tc'] = $value;


			for ($i = 0; $i < 5; $i++) {

				if ($i<4){
					//Bucket
					$data[$key]['bucket'][$i]['bucket'] = "B$i";

					//HD
					$count_field = $contract_field->select_where_count($year, $month, ['$in' => $all_arr], ["B$i"]);
					$count_call = $contract_call->select_where_count($year, $month, ['$in' => $all_arr], ["B$i"]);
					$data[$key]["bucket"][$i]['HĐ'] = $count_field + $count_call;

					//BOM_POS
					$pos_field = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field], 'bucket_old' => "B$i"], '$pos_du_no');
					$pos_call = $contract_call->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2, 36]], 'debt_caller_email' => ['$in' => $user_call], 'bucket_old' => "B$i"], '$pos_du_no');
					$data[$key]["bucket"][$i]['BOM_POS'] = $pos_field + $pos_call;

					//Target KPI
					$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
					$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['kpis'] : 0;

					//RS_POS
					$arr_code_contract_user_du_no = [];
					$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, ["B$i"]);
					foreach ($arr_code_contract_user as $item) {
						$arr_code_contract_user_du_no += [$item['code_contract'] => $item['pos_du_no']];
					}
					$du_no_field = $this->total_du_no($arr_code_contract_user_du_no, $temporary_plan_contract, $transaction, $condition_day);

					$arr_code_contract_user_du_no_call = [];
					$arr_code_contract_user_call = $this->contract_debt_caller_model->select_where($year, $month, $user_call, ["B$i"]);
					foreach ($arr_code_contract_user_call as $item) {
						$arr_code_contract_user_du_no_call += [$item['code_contract'] => $item['pos_du_no']];
					}
					$du_no_call = $this->total_du_no($arr_code_contract_user_du_no_call, $temporary_plan_contract, $transaction, $condition_day);

					$data[$key]["bucket"][$i]['RS_POS'] = $du_no_field + $du_no_call;

					//REAL AMOUNT
					$arr_all = array_merge($arr_code_contract_user, $arr_code_contract_user_call);

					$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $this->total_transaction($arr_all, $transaction, $condition_day, $temporary_plan_contract);

				} else {

					$bucket = ['B4','B5','B6','B7','B8'];
					//Bucket
					$data[$key]['bucket'][$i]['bucket'] = "B4 - B8";

					//HD
					$data[$key]["bucket"][$i]['HĐ'] = $contract_field->select_where_count($year, $month, ['$in' => $list_user_field], $bucket);

					//BOM_POS
					$data[$key]["bucket"][$i]['BOM_POS'] = $contract_field->sum_where(['year' => $year, 'month' => $month, 'status' => ['$in' => [2]], 'debt_field_email' => ['$in' => $list_user_field], 'bucket_old' => ['$in' => $bucket]], '$pos_du_no');

					//Target KPI
					$kpi_thn_user = $this->kpi_thn_model->findOne(['year' => $year, 'month' => $month, 'email_thn' => $value]);
					$data[$key]["bucket"][$i]['target_kpi'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['kpis'] : 0;

					//RS_POS
					$arr_code_contract_user_tran = [];

					$arr_code_contract_user = $this->contract_assign_debt_model->select_where($year, $month, $list_user_field, $bucket);

					foreach ($arr_code_contract_user as $item) {
						array_push($arr_code_contract_user_tran, $item['code_contract']);
					}

					$data[$key]["bucket"][$i]['RS_POS'] = $this->total_transaction($arr_code_contract_user, $transaction, $condition_day, $temporary_plan_contract);

					//REAL AMOUNT
					$data[$key]["bucket"][$i]['REAL_AMOUNT'] = $data[$key]["bucket"][$i]['RS_POS'];

				}

				//RESOLVED = RS POS/BOM POS
				$resolved = 0;
				if ($data[$key]["bucket"][$i]['BOM_POS'] != 0) {
					$data[$key]["bucket"][$i]['RESOLVED'] = ($data[$key]["bucket"][$i]['RS_POS'] / $data[$key]["bucket"][$i]['BOM_POS']) * 100;
				}

				//UNRESOLVED
				$data[$key]["bucket"][$i]['UNRESOLVED'] = 100 - $data[$key]["bucket"][$i]['RESOLVED'];

				//COMPLETION ACCORDING TO KPI
				$completion = (2 - ($data[$key]["bucket"][$i]['UNRESOLVED'] / $data[$key]["bucket"][$i]['target_kpi']));

				if ($completion > 1.5) {
					$completion = 1.5;
				} elseif ($completion < 0) {
					$completion = 0;
				}
				$data[$key]["bucket"][$i]['completion'] = $completion * 100;

				//DISTRIBUTION WEIGHT
				if ($i<4){
					$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B$i"]['ts_bucket'] : 0;
				} else {
					$data[$key]["bucket"][$i]['distribution_weight'] = !empty($kpi_thn_user) ? $kpi_thn_user['kpi']["B4"]['ts_bucket'] : 0;
				}

				//WEIGHT KPI COMPLETE RATE
				$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = 0;
				if ($data[$key]["bucket"][$i]['distribution_weight'] != 0) {
					$data[$key]["bucket"][$i]['weight_kpi_complete_rate'] = ($data[$key]["bucket"][$i]['completion'] * $data[$key]["bucket"][$i]['distribution_weight']) / 100;
				}

				$SUMMARY_OF_PERFORMANCE_KPI += $data[$key]["bucket"][$i]['weight_kpi_complete_rate'];

			}


			$data[$key]['SUMMARY_OF_PERFORMANCE_KPI'] = $SUMMARY_OF_PERFORMANCE_KPI;
			$data[$key]['BONUS'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value), '$money');


		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function export_contest_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();


		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}


		$data = [];
		$data_field = [];
		$data_field_b4 = [];
		foreach ($user_call as $key => $value){
			//Email
			$data[$key]['email'] = $value;

			//Chức vụ
			$data[$key]['chuc_vu'] = "Call B0 - B3";

			//Bộ phận
			$data[$key]['bo_phan'] = "Call";

			//Tổng thu phí phạt
			$data[$key]['tong_thu_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => 'tong_phi_phat'), '$total_money');

			//Thưởng Phí Phạt
			$data[$key]['thuong_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'tong_phi_phat'), '$money');

			//Bucket rollback
			$data[$key]['so_ky_phi_thu'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$total_money');

			//Thưởng rollback
			$data[$key]['thuong_rollback'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$money');

			//Tất toán hợp đồng xe máy
			$data[$key]['tat_toan_hd_xm'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$total_money');

			//Tổng tiền tất toán xe máy
			$data[$key]['thuong_tt_xemay'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$money');

			//Tất toán hđ ô tô
			$data[$key]['tat_toan_hd_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$total_money');

			//Tổng tiền tất toán ô tô
			$data[$key]['thuong_tt_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$money');

			//Tổng thưởng contest
			$data[$key]['total_price'] = $data[$key]['thuong_phi_phat'] + $data[$key]['thuong_rollback'] + $data[$key]['thuong_tt_xemay'] + $data[$key]['thuong_tt_oto'];

		}

		foreach ($user_field as $key => $value){
			//Email
			$data_field[$key]['email'] = $value;

			//Chức vụ
			$data_field[$key]['chuc_vu'] = "Field B1 - B3";

			//Bộ phận
			$data_field[$key]['bo_phan'] = "Field";

			//Tổng thu phí phạt
			$data_field[$key]['tong_thu_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => 'tong_phi_phat'), '$total_money');

			//Thưởng Phí Phạt
			$data_field[$key]['thuong_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'tong_phi_phat'), '$money');

			//Bucket rollback
			$data_field[$key]['so_ky_phi_thu'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$total_money');

			//Thưởng rollback
			$data_field[$key]['thuong_rollback'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$money');

			//Tất toán hợp đồng xe máy
			$data_field[$key]['tat_toan_hd_xm'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$total_money');

			//Tổng tiền tất toán xe máy
			$data_field[$key]['thuong_tt_xemay'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$money');

			//Tất toán hđ ô tô
			$data_field[$key]['tat_toan_hd_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$total_money');

			//Tổng tiền tất toán ô tô
			$data_field[$key]['thuong_tt_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$money');

			//Tổng thưởng contest
			$data_field[$key]['total_price'] = $data_field[$key]['thuong_phi_phat'] + $data_field[$key]['thuong_rollback'] + $data_field[$key]['thuong_tt_xemay'] + $data_field[$key]['thuong_tt_oto'];

		}

		foreach ($user_field_b4 as $key => $value){
			//Email
			$data_field_b4[$key]['email'] = $value;

			//Chức vụ
			$data_field_b4[$key]['chuc_vu'] = "Field 4+";

			//Bộ phận
			$data_field_b4[$key]['bo_phan'] = "Field";

			//Tổng thu phí phạt
			$data_field_b4[$key]['tong_thu_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => 'tong_phi_phat'), '$total_money');

			//Thưởng Phí Phạt
			$data_field_b4[$key]['thuong_phi_phat'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'tong_phi_phat'), '$money');

			//Bucket rollback
			$data_field_b4[$key]['so_ky_phi_thu'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$total_money');

			//Thưởng rollback
			$data_field_b4[$key]['thuong_rollback'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency' => 'rollback'), '$money');

			//Tất toán hợp đồng xe máy
			$data_field_b4[$key]['tat_toan_hd_xm'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$total_money');

			//Tổng tiền tất toán xe máy
			$data_field_b4[$key]['thuong_tt_xemay'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'XM'), '$money');

			//Tất toán hđ ô tô
			$data_field_b4[$key]['tat_toan_hd_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$total_money');

			//Tổng tiền tất toán ô tô
			$data_field_b4[$key]['thuong_tt_oto'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value, 'currency.loan_infor.type_property.code' => 'OTO'), '$money');

			//Tổng thưởng contest
			$data_field_b4[$key]['total_price'] = $data_field_b4[$key]['thuong_phi_phat'] + $data_field_b4[$key]['thuong_rollback'] + $data_field_b4[$key]['thuong_tt_xemay'] + $data_field_b4[$key]['thuong_tt_oto'];

		}

		$data_merge = array_merge($data, $data_field, $data_field_b4);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data_merge,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function export_tong_thuong_thang_post(){

		$contract_call = new Contract_debt_caller_model();
		$contract_field = new Contract_assign_debt_model();
		$contract = new Contract_model();
		$kpi_thn = new Kpi_thn_model();
		$transaction = new Transaction_model();
		$temporary_plan_contract = new Temporary_plan_contract_model();
		$report_commission_thn = new Report_commission_thn_model();


		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		$condition['month'] = $month;
		$condition['year'] = $year;

		$groupRoles = $this->getGroupRole($this->id);
		$list_user_field = [];
		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$user_call = $this->call_thn_mb();
			$user_field = $this->field_thn_mb();
			$user_field_b4 = $this->field_thn_mb_b4();
			$lead_call = $this->lead_call_mien_bac();
			$lead_field = $this->lead_field_mien_bac();
			$tp_thn = $this->tbp_thn_mien_bac();

		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$user_call = $this->call_thn_mn();
			$user_field = $this->field_thn_mn();
			$user_field_b4 = $this->field_thn_mn_b4();
			$lead_call = $this->lead_call_mien_nam();
			$lead_field = $this->lead_field_mien_nam();
			$tp_thn = $this->tbp_thn_mien_nam();
		}

		$data_call = [];
		$data_field = [];
		$data_field_b4 = [];
		$data_lead_call = [];
		$data_lead_field = [];
		$data_tp_thn = [];
		foreach ($user_call as $key => $value){
			//Team
			$data_call[$key]['team'] = "Call";

			//Email
			$data_call[$key]['email'] = $value;

			//Bộ phận
			$data_call[$key]['chuc_vu'] = "Call B0 - B3";

			//Kết quả hoàn thành Kpi
			$data_call[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Tổng thưởng contest
			$money_currency = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => ['$in' => ['tong_phi_phat', 'rollback']]), '$money');
			$money_type_property = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency.loan_infor.type_property.code' => ['$in' => ['XM', 'OTO']]), '$money');

			$data_call[$key]['tong_thuong_contest'] = $money_currency + $money_type_property;

			//Thưởng Kpi
			$data_call[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$money_kpi = 0;
			if ($data_call[$key]['money_kpi'] > 9000000){
				$money_kpi = 9000000;
			} else {
				$money_kpi = $data_call[$key]['money_kpi'];
			}
			$data_call[$key]['total_money'] = $data_call[$key]['tong_thuong_contest'] + $money_kpi;

		}

		foreach ($user_field as $key => $value){
			//Team
			$data_field[$key]['team'] = "Field";

			//Email
			$data_field[$key]['email'] = $value;

			//Bộ phận
			$data_field[$key]['chuc_vu'] = "Field B1 - B3";

			//Kết quả hoàn thành Kpi
			$data_field[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Tổng thưởng contest
			$money_currency = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => ['$in' => ['tong_phi_phat', 'rollback']]), '$money');
			$money_type_property = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency.loan_infor.type_property.code' => ['$in' => ['XM', 'OTO']]), '$money');

			$data_field[$key]['tong_thuong_contest'] = $money_currency + $money_type_property;

			//Thưởng Kpi
			$data_field[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$money_kpi = 0;
			if ($data_field[$key]['money_kpi'] > 17000000){
				$money_kpi = 17000000;
			} else {
				$money_kpi = $data_field[$key]['money_kpi'];
			}
			$data_field[$key]['total_money'] = $data_field[$key]['tong_thuong_contest'] + $money_kpi;

		}

		foreach ($user_field_b4 as $key => $value){
			//Team
			$data_field_b4[$key]['team'] = "Field 4+";

			//Email
			$data_field_b4[$key]['email'] = $value;

			//Bộ phận
			$data_field_b4[$key]['chuc_vu'] = "Field 4+";

			//Kết quả hoàn thành Kpi
			$data_field_b4[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Tổng thưởng contest
			$money_currency = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency' => ['$in' => ['tong_phi_phat', 'rollback']]), '$money');
			$money_type_property = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'currency.loan_infor.type_property.code' => ['$in' => ['XM', 'OTO']]), '$money');

			$data_field_b4[$key]['tong_thuong_contest'] = $money_currency + $money_type_property;

			//Thưởng Kpi
			$data_field_b4[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$money_kpi = 0;
			if ($data_field_b4[$key]['money_kpi'] > 17000000){
				$money_kpi = 17000000;
			} else {
				$money_kpi = $data_field_b4[$key]['money_kpi'];
			}
			$data_field_b4[$key]['total_money'] = $data_field_b4[$key]['tong_thuong_contest'] + $money_kpi;

		}

		foreach ($lead_call as $key => $value){
			//Team
			$data_lead_call[$key]['team'] = "Trưởng nhóm Call";

			//Email
			$data_lead_call[$key]['email'] = $value;

			//Kết quả hoàn thành Kpi
			$data_lead_call[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Thưởng Kpi
			$data_lead_call[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$data_lead_call[$key]['total_money'] = $data_lead_call[$key]['tong_thuong_contest'] + $data_lead_call[$key]['money_kpi'];

		}

		foreach ($lead_field as $key => $value){
			//Team
			$data_lead_field[$key]['team'] = "Trưởng nhóm Field";

			//Email
			$data_lead_field[$key]['email'] = $value;

			//Kết quả hoàn thành Kpi
			$data_lead_field[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Thưởng Kpi
			$data_lead_field[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$data_lead_field[$key]['total_money'] = $data_lead_field[$key]['tong_thuong_contest'] + $data_lead_field[$key]['money_kpi'];

		}

		foreach ($tp_thn as $key => $value){
			//Team
			$data_tp_thn[$key]['team'] = "Trưởng phòng";

			//Email
			$data_tp_thn[$key]['email'] = $value;

			//Kết quả hoàn thành Kpi
			$data_tp_thn[$key]['kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$kpi.kpi');

			//Thưởng Kpi
			$data_tp_thn[$key]['money_kpi'] = $report_commission_thn->sum_where(array('year' => $year, 'month' => $month, 'email' => $value,'money_kpi' => true), '$money');

			//Tổng thực nhận
			$data_tp_thn[$key]['total_money'] = $data_tp_thn[$key]['tong_thuong_contest'] + $data_tp_thn[$key]['money_kpi'];

		}

		$data_nhanvien = array_merge($data_call, $data_field, $data_field_b4);

		$data_quanly = array_merge($data_lead_call, $data_lead_field, $data_tp_thn);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data_nhanvien,
			'data_quanly' => $data_quanly,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


	public function exportExcelT10_post(){

		$month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
		$year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');

		$day = $this->lastday($month, $year);

		$condition_day = array(
			'$gte' => strtotime((date("$year-$month-01")) . ' 00:00:00'),
			'$lte' => strtotime((date("$year-$month-$day")) . ' 23:59:59')
		);

		//Khách hàng
		$dataContract = $this->debt_contract_model->find_where(['year' => $year, 'month' => $month]);

		$data = [];
		$count = 0;
		if (!empty($dataContract)){
			foreach ($dataContract as $key => $value){

				//Data hợp đồng
				$contract = $this->contract_model->find_one_select(['code_contract' => $value['code_contract']], ['code_contract_disbursement','customer_infor.customer_name','disbursement_date','loan_infor.amount_money','loan_infor.type_interest','original_debt.du_no_goc_con_lai','debt.so_ngay_cham_tra','customer_infor.customer_phone_number','job_infor.job','store.name','houseHold_address.province_name','houseHold_address.district_name', 'status']);

				//Check chậm trả
				if ($month == date('m') && $year == date('Y')){
					$so_ngay_cham_tra = $contract['debt']['so_ngay_cham_tra'];
					$check_cham_tra = true;
				} else {
					$so_ngay_cham_tra = $value['debt']['so_ngay_cham_tra'];
					$check_cham_tra = false;
				}

				$checkRule = $this->checkRuleContract($value['code_contract'], $so_ngay_cham_tra , $day, $condition_day, $check_cham_tra, $contract['status']);
				if ($checkRule == false){
					continue;
				}

				//Mã phiếu ghi
				$data[$count]['code_contract'] = !empty($value['code_contract']) ? $value['code_contract'] : "";


				if(!empty($contract)){
					//Mã hợp đồng
					$data[$count]['code_contract_disbursement'] = $contract['code_contract_disbursement'];

					//Họ và tên
					$data[$count]['customer_name'] = $contract['customer_infor']['customer_name'];

					//Ngày giải ngân
					$data[$count]['disbursement_date'] = $contract['disbursement_date'];

					//Khoản vay
					$data[$count]['amount_money'] = $contract['loan_infor']['amount_money'];

					//Hình thức vay
					$data[$count]['type_interest'] = $contract['loan_infor']['type_interest'];

					//Tiền kỳ
					$tien_1_ky_phai_tra = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $value['code_contract']]);
					$data[$count]['tien_1_ky_phai_tra'] = $tien_1_ky_phai_tra[0]['tien_tra_1_ky'];

					//Số tiền gốc còn lại
					if ($contract['status'] == 19){
						$data[$count]['du_no_goc_con_lai'] = 0;
					} else {
						$data[$count]['du_no_goc_con_lai'] = $contract['original_debt']['du_no_goc_con_lai'];
					}

					//Ngày quá hạn
					$data[$count]['ngay_qua_han'] = $contract['debt']['so_ngay_cham_tra'];

					//Số kỳ đã thanh toán
					$so_ky_thanh_toan = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $value['code_contract'], 'status' => 2]);
					$data[$count]['so_ky_da_thanh_toan'] = count($so_ky_thanh_toan);

					//Số đt khách hàng
					$data[$count]['customer_phone_number'] = $contract['customer_infor']['customer_phone_number'];

					//Nghề nghiệp
					$data[$count]['job_infor'] = $contract['job_infor']['job'];

					//Nghề nghiệp
					$data[$count]['name'] = $contract['store']['name'];

					//Quận, huyện
					$data[$count]['ward_name'] = $contract['houseHold_address']['district_name'];

					//Tỉnh
					$data[$count]['province_name'] = $contract['houseHold_address']['province_name'];
				}

				$count++;
			}
		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function checkRuleContract($code_contract, $so_ngay_cham_tra, $day, $condition_day, $check_cham_tra, $status)
	{
		//true - hiển thị, false - không hiển thị
		$so_ngay_gioi_han = 10 - $day;

		if ($so_ngay_cham_tra >= 10) {
			return true;
		}

		if ($check_cham_tra == true && $so_ngay_cham_tra < 10) {
			return false;
		}

		//Check ngày chậm trả
		if ($so_ngay_gioi_han < $so_ngay_cham_tra) {

			//Check hợp đồng trong tháng đã là T+10 hay chưa
			$bang_lai_ky = $this->temporary_plan_contract_model->find_where_select_check_rule(['code_contract' => $code_contract, 'ngay_ky_tra' => $condition_day]);

			if (!empty($bang_lai_ky)) {

				$date_pay = strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($code_contract)) . ' 23:59:59');

				$current_day = strtotime(date('Y-m-d', $date_pay) . ' 23:59:59');

				if ($status == 33 || $status == 34) {

					$date_pay = strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_gh_cc($code_contract)) . ' 23:59:59');

					$current_day = $date_pay;

				}

				$ngay_ky_tra_ky_ht = !empty($bang_lai_ky[0]['ngay_ky_tra']) ? strtotime(date('Y-m-d', $bang_lai_ky[0]['ngay_ky_tra'] . ' 23:59:59')) : $current_day;

				$time = intval(($current_day - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

				if ($bang_lai_ky[0]['status'] == 1 && $status != 33 && $status != 34) {
					$ngay_den_han = (date('d', $bang_lai_ky[0]['ngay_ky_tra'] ));
					$ngay_cham_tra = $day - (int)$ngay_den_han;
				} else {
					$ngay_cham_tra = $bang_lai_ky[0]['so_ngay_cham_tra'];
				}

				if ($ngay_cham_tra < 10) {
					return false;
				} else {
					return true;
				}

			} else {
				return false;
			}
		}
		return false;
	}


	public function report_debt_ninety_post()
	{

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		$condition_du_no = array(
			'$gte' => strtotime(trim($start) . ' 00:00:00'),
			'$lte' => strtotime(trim($end) . ' 23:59:59')
		);

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HN1','KV_QN','Priority']);
			$condition['store'] = ['$in' => $arr_store];
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HCM1','KV_HCM2','KV_MK','KV_BD']);
			$condition['store'] = ['$in' => $arr_store];
		}

		$data = [];
		//Đang cho vay
		$condition['status'] = list_array_trang_thai_dang_vay();
		$data['lending_count'] = $this->contract_model->count_status($condition);
		$data['lending_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()), 'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

		//Đã tất toán
		$condition['status'] = [19];
		$data['settlement_count'] = $this->contract_model->count_status($condition);
		$data['settlement_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' => [19]), 'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

		//Nợ xấu > 90 ngày
		$condition['status'] = list_array_trang_thai_dang_vay();
		$condition['debt'] = 1;
		$data['debt_count'] = $this->contract_model->count_status($condition);
		$data['debt_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'debt.so_ngay_cham_tra'=>['$gt'=>90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function list_store($area){
		$store = $this->store_model->find_where(['code_area' => ['$in' => $area]]);
		$arr = [];
		foreach ($store as $value){
			array_push($arr, (string)$value['_id']);
		}
		return $arr;
	}

	public function report_debt_product_post(){

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		$condition_du_no = array(
			'$gte' => strtotime(trim($start) . ' 00:00:00'),
			'$lte' => strtotime(trim($end) . ' 23:59:59')
		);

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HN1','KV_QN','Priority']);
			$condition['store'] = ['$in' => $arr_store];
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HCM1','KV_HCM2','KV_MK','KV_BD']);
			$condition['store'] = ['$in' => $arr_store];
		}

		$data = [];
		$condition['debt'] = 1;
		$condition['status'] = list_array_trang_thai_dang_vay();
		//Tất cả
		$data['bad_debt_count'] = $this->contract_model->count_status($condition);
		$data['bad_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

		//Xe máy
		$condition['type_property'] = "XM";
		$data['xm_count'] = $this->contract_model->count_status($condition);
		$data['xm_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'loan_infor.type_property.code' => $condition['type_property'] ,'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');
		$data['xm_count_ratio'] = ($data['bad_debt_count'] != 0) ? number_format(($data['xm_count']/$data['bad_debt_count'])*100,2)  : 0;
		$data['xm_debt_ratio'] = ($data['bad_debt'] != 0) ? number_format(($data['xm_debt']/$data['bad_debt'])*100,2)  : 0;

		//Ô Tô
		$condition['type_property'] = "OTO";
		$data['oto_count'] = $this->contract_model->count_status($condition);
		$data['oto_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'loan_infor.type_property.code' => $condition['type_property'] ,'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');
		$data['oto_count_ratio'] = ($data['bad_debt_count'] != 0) ? number_format(($data['oto_count']/$data['bad_debt_count'])*100,2)  : 0;
		$data['oto_debt_ratio'] = ($data['bad_debt'] != 0) ? number_format(($data['oto_debt']/$data['bad_debt'])*100,2)  : 0;

		//Tín chấp
		$condition['type_property'] = "TC";
		$data['tc_count'] = $this->contract_model->count_status($condition);
		$data['tc_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'loan_infor.type_property.code' => $condition['type_property'] ,'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');
		$data['tc_count_ratio'] = ($data['bad_debt_count'] != 0) ? number_format(($data['tc_count']/$data['bad_debt_count'])*100,2)  : 0;
		$data['tc_debt_ratio'] = ($data['bad_debt'] != 0) ? number_format(($data['tc_debt']/$data['bad_debt'])*100,2)  : 0;



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function report_debt_area_post(){

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		$condition_du_no = array(
			'$gte' => strtotime(trim($start) . ' 00:00:00'),
			'$lte' => strtotime(trim($end) . ' 23:59:59')
		);

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HN1','KV_QN','Priority']);
			$condition['store'] = ['$in' => $arr_store];
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HCM1','KV_HCM2','KV_MK','KV_BD']);
			$condition['store'] = ['$in' => $arr_store];
		}

		$data = [];
		$condition['debt'] = 1;
		$condition['status'] = list_array_trang_thai_dang_vay();
		//Tất cả
		$data['bad_debt_count'] = $this->contract_model->count_status($condition);
		$data['bad_debt'] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

		//Nợ xấu quá hạn 3 kỳ đầu
		$count = 0;
		$debt = 0;

		$data_contract = $this->contract_model->find_where_debt($condition);
		if (!empty($data_contract)){
			foreach ($data_contract as $value){
				$flag = false;
				$check_temp = $this->temporary_plan_contract_model->find_where_report($value['code_contract']);
				foreach ($check_temp as $item){
					if($item->status == 1){
						$flag = true;
						break;
					}
				}

				if (!empty($check_temp) && $flag == true){
					$count++;
					$debt += $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'code_contract' => $value['code_contract'] ,'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');
				}
			}
			$data['count'] = $count;
			$data['debt'] = $debt;
		}
		$data['ratio_count'] = ($data['bad_debt_count'] != 0) ? number_format(($data['count']/$data['bad_debt_count'])*100,2)  : 0;
		$data['ratio_debt'] = ($data['bad_debt'] != 0) ? number_format(($data['debt']/$data['bad_debt'])*100,2)  : 0;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_debt_month_post(){

		$arr = [2019,2020,2021,2022];
		$data = [];

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HN1','KV_QN','Priority']);
			$condition_1['store'] = ['$in' => $arr_store];
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HCM1','KV_HCM2','KV_MK','KV_BD']);
			$condition_1['store'] = ['$in' => $arr_store];
		}

		foreach ($arr as $value){
			$condition_1 = [];
			$start_1 =  date("$value-01-01");
			$end_1 =  date("$value-12-31");
			$condition_1['start'] = strtotime(trim($start_1) . ' 00:00:00');
			$condition_1['end'] = strtotime(trim($end_1) . ' 23:59:59');
			$condition_duno1 = array(
				'$gte' => strtotime(trim($start_1) . ' 00:00:00'),
				'$lte' => strtotime(trim($end_1) . ' 23:59:59')
			);

			//Đã giải ngân
			$condition_1['status'] = [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19];
			$data["count_dagiaingan_$value"] = $this->contract_model->count_status($condition_1);
			$data["debt_dagiaingan_$value"] = $this->contract_model->sum_where_total(['status' => array('$in' =>  $condition_1['status']) ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_duno1, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

			//Đang vay
			$condition_1['status'] = list_array_trang_thai_dang_vay();
			$data["count_dangvay_$value"] = $this->contract_model->count_status($condition_1);
			$data["debt_dangvay_$value"] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()) ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_duno1, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

			//Nợ xấu
			$condition_1['debt'] = 1;
			$data["count_noxau_$value"] = $this->contract_model->count_status($condition_1);
			$data["debt_noxau_$value"] = $this->contract_model->sum_where_total(['status' => array('$in' =>  list_array_trang_thai_dang_vay_gh_cc()), 'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'store.id' => ['$in' => $arr_store ] ,'disbursement_date' => $condition_duno1, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

			//Tỉ lệ
			$data["count_tile_giaingan_$value"] = ($data["count_dagiaingan_$value"] != 0) ? number_format(($data["count_noxau_$value"]/$data["count_dagiaingan_$value"])*100,5)  : 0;
			$data["debt_tile_giaingan_$value"] = ($data["debt_dagiaingan_$value"] != 0) ? number_format(($data["debt_noxau_$value"]/$data["debt_dagiaingan_$value"])*100,5)  : 0;

			$data["count_tile_dangvay_$value"] = ($data["count_dangvay_$value"] != 0) ? number_format(($data["count_noxau_$value"]/$data["count_dangvay_$value"])*100,5)  : 0;
			$data["debt_tile_dangvay_$value"] = ($data["debt_dangvay_$value"] != 0) ? number_format(($data["debt_noxau_$value"]/$data["debt_dangvay_$value"])*100,5)  : 0;

		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function report_debt_district_post(){

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		$condition_du_no = array(
			'$gte' => strtotime(trim($start) . ' 00:00:00'),
			'$lte' => strtotime(trim($end) . ' 23:59:59')
		);

		$data = [];

		$province = $this->province_model->find();

		$condition['debt'] = 1;
		$condition['status'] = list_array_trang_thai_dang_vay();

		$count_hd_no_xau = 0;

		foreach ($province as $key => $value){

			//Tên thành phố
			$data[$key]['name'] = $value['name'];

			//Số hđ nợ xấu
			$condition['province'] = $value['code'];
			$data[$key]['count_hd_no_xau'] = $this->contract_model->count_status($condition);

			$count_hd_no_xau +=  $data[$key]['count_hd_no_xau'];

			//Tổng tiền tất toán
			$data[$key]['total_tattoan'] =  $this->contract_model->sum_where_total(['status' => array('$in' => [19]), 'houseHold_address.province' => $value['code'] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], array('$toLong' => '$loan_infor.amount_money'));

			//Dư nợ đang cho vay
			$data[$key]['debt_hd_dang_cho_vay'] = $this->contract_model->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()), 'houseHold_address.province' => $value['code'] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

			//Tổng dư nợ
			$data[$key]['debt_hd_no_xau'] = $this->contract_model->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()), 'houseHold_address.province' => $value['code'] , 'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');

		}



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
			'count_hd_no_xau' => $count_hd_no_xau,


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function report_debt_pgd_post(){

		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$store = $this->store_model->find_where(['code_area' => ['$in' => ['KV_HN1','KV_QN','Priority']]]);
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$store = $this->store_model->find_where(['code_area' => ['$in' => ['KV_HCM1','KV_HCM2','KV_MK','KV_BD']]]);
		}

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');

		$condition_du_no = array(
			'$gte' => strtotime(trim($start) . ' 00:00:00'),
			'$lte' => strtotime(trim($end) . ' 23:59:59')
		);

		$data = [];

		$count_hd_no_xau = 0;

		$condition['debt'] = 1;
		$condition['status'] = list_array_trang_thai_dang_vay();

		foreach ($store as $key => $value){

			//Tên thành phố
			$data[$key]['name'] = $value['name'];

			//Số hđ nợ xấu
			$condition['store'] = (string)$value['_id'];
			$data[$key]['count_hd_no_xau'] = $this->contract_model->count_status($condition);

			$count_hd_no_xau +=  $data[$key]['count_hd_no_xau'];

			//Tổng tiền tất toán
			$data[$key]['total_tattoan'] =  $this->contract_model->sum_where_total(['status' => array('$in' => [19]), 'store.id' => (string)$value['_id'] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], array('$toLong' => '$loan_infor.amount_money'));

			//Dư nợ đang cho vay
			$data[$key]['debt_hd_dang_cho_vay'] = $this->contract_model->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()), 'store.id' => (string)$value['_id'] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');


			//Tổng dư nợ
			$data[$key]['debt_hd_no_xau'] = $this->contract_model->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()), 'store.id' => (string)$value['_id'] , 'debt.so_ngay_cham_tra'=>['$gt'=> 90] ,'disbursement_date' => $condition_du_no, 'code_contract_parent_gh' => array('$exists' => false), 'code_contract_parent_cc' => array('$exists' => false), 'tat_toan_gh' => array('$exists' => false)], '$debt.tong_tien_goc_con');


		}


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
			'count_hd_no_xau' => $count_hd_no_xau,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function report_debt_detail_post(){

		$condition = [];
		$start = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : date('2018-01-01');
		$end = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : date('Y-m-d');

		$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		$condition['end'] = strtotime(trim($end) . ' 23:59:59');


		$groupRoles = $this->getGroupRole($this->id);

		if (!empty($groupRoles) && in_array('tp-thn-mien-bac', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HN1','KV_QN','Priority']);
			$condition['store'] = ['$in' => $arr_store];
		} elseif (!empty($groupRoles) && in_array('tp-thn-mien-nam', $groupRoles)) {
			$arr_store = $this->list_store(['KV_HCM1','KV_HCM2','KV_MK','KV_BD']);
			$condition['store'] = ['$in' => $arr_store];
		}

		$data = $this->contract_model->get_debt_detail($condition);

		if (!empty($data)){
			foreach ($data as $value){
				//
				$tien_1_ky_phai_tra = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $value['code_contract']]);
				$value['tien_1_ky_phai_tra'] = $tien_1_ky_phai_tra[0]['tien_tra_1_ky'];

				//
				$so_ky_thanh_toan = $this->temporary_plan_contract_model->find_where_select_excel(['code_contract' => $value['code_contract'], 'status' => 2]);
				$value['so_ky_da_thanh_toan'] = count($so_ky_thanh_toan);
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Success",
			'data' => $data,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	private function getStores_list($userId)
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
								array_push($roleStores,key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}

}

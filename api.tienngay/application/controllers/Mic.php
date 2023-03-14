<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Mic extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('mic_model');
		$this->load->model('log_mic_model');
		$this->load->model('warehouse_model');
		$this->load->model('contract_model');
		$this->load->model('investor_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model('temporary_plan_contract_model');
		$this->load->model('log_model');
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
	}

	public function find_where_not_in_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$mics = $this->mic_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mics
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function find_where_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$mics = $this->mic_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
		if (!empty($mics)) {
			foreach ($mics as $sto) {
				$sto['mic_id'] = (string)$sto['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mics
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function get_all_post()
	{
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
		if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
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
		$condition['type_mic'] = !empty($data['type_mic']) ? $data['type_mic'] : '';
		$isExport = !empty($data['isExport']) ? $data['isExport'] : '';
		if (!empty($isExport)) {
			$condition['isExport'] = $isExport;
		}
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$mic = $this->mic_model->getMic($condition, $per_page, $uriSegment);
		$total = $this->mic_model->getMic_total($condition);
		if (!empty($mic)) {
			foreach ($mic as $key => $value) {
				$contract = $this->contract_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value['contract_id'])]);
				$value['contract_info'] = $contract;
				$value['log'] = $this->log_mic_model->findOne(['code_contract_disbursement' => $contract['code_contract_disbursement']]);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic,
			'total' => $total,
			'groupRoles' => $groupRoles
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_log_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$condition = array();
		$groupRoles = $this->getGroupRole($this->id);
		$data = $this->input->post();
		$all = false;

		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

		$gic = $this->log_mic_model->getMic($condition, $per_page, $uriSegment);
		$condition['total'] = true;
		$total = $this->log_mic_model->getMic($condition, $per_page, $uriSegment);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $gic,
			'total' => $total

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function resend_mic_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		if (empty($data['id'])) return;
		$mics = $this->mic_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($data['id'])));
		$type_gic = "MIC_TDCN";
		if (empty($mics)) return;
		$insert_mic = $this->insert_mic($mics['request']);
		$this->log_mic($insert_mic->request, $insert_mic->response, $mics['code_contract_disbursement'], $type_gic);
		//  var_dump($insert_mic);
		if ($insert_mic->res == true) {
			$this->mic_model->update(
				array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
				array('status' => 'active'
				, 'mic_gcn' => $insert_mic->data->GCN
				, 'mic_fee' => $insert_mic->data->PHI
				, 'updated_at' => $this->createdAt
				, 'updated_by' => $this->uemail
				)
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Update mic success",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Update mic false",

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$response = array(
			'status' => REST_Controller::HTTP_UNAUTHORIZED,
			'message' => "Update mic false",

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	private function insert_mic($originalXML)
	{


		$wsdl_url = $this->config->item("API_MIC") . '?wsdl';
		try {
			$params = new \SoapVar($originalXML, XSD_ANYXML);
			//var_dump($params ); die;
			$this->soapClient = new \SoapClient($wsdl_url, array('soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => false));
			$this->soapClient->__setLocation($this->config->item("API_MIC"));
			$result = $this->soapClient->ws_GCN_TRA($params);
			// var_dump($result); die;
			$xml = simplexml_load_string($result->ws_GCN_TRAResult);
			if ($xml->STATUS == "TRUE") {
				$response = [
					'res' => true,
					'status' => "200",
					'data' => $xml,
					'request' => $originalXML,
					'response' => (string)$xml

				];
				return json_decode(json_encode($response));
			} else {
				$response = [
					'res' => false,
					'status' => "401",
					'request' => $originalXML,
					'response' => (string)$xml
				];
				return json_decode(json_encode($response));
			}


		} catch (Exception $e) {
			$response = [
				'res' => false,
				'status' => "401",
				'request' => $originalXML,
				'response' => $e->getMessage()
			];
			return json_decode(json_encode($response));


		}


	}

	public function get_all_home_post()
	{

		$mic = $this->mic_model->find_where('status', ['active']);
		if (!empty($mic)) {
			foreach ($mic as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id mic already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$mic = $this->mic_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_temporary_plan_contract_one_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id mic already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$mic = $this->temporary_plan_contract_model->find_where(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	//lấy danh danh contract bằng mã nhà đầu tư
	public function get_investor_in_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id mic empty"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$contract = $this->contract_model->find_where(array("investor_infor._id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	//lấy danh sách chi tiết thành toán cho nhà đầu tư bằng mã contract
	public function get_temporary_plan_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
		if (empty($code_contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id mic empty"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//var_dump($id); die;
		$mic = $this->temporary_plan_contract_model->find_where(array("code_contract" => $code_contract));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_description_mic_post()
	{

		$data = $this->input->post();
		$link = !empty($data['link']) ? $data['link'] : "";
		if (empty($link)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id mic already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$mic = $this->mic_model->findOne(array("link" => $link));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $mic
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function create_mic_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		$this->mic_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create mic success",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_mic_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->mic_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if ($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại tin tức nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->log_mic($data);
		unset($data['id']);

		$this->mic_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update mic success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_temporary_plan_contract_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->temporary_plan_contract_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if ($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại thông tin nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->log_temporary_plan_contract($data);
		unset($data['id']);

		$this->temporary_plan_contract_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update mic success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function log_mic($data)
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";

		$mic = $this->mic_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$mic['id'] = (string)$mic['_id'];
		unset($mic['_id']);
		$dataInser = array(
			"new_data" => $data,
			"old_data" => $mic,
			"type" => 'mic'

		);
		$this->log_model->insert($dataInser);
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

	private function getUserbyStores($storeId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['stores']) && count($role['stores']) > 0) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if (in_array($storeId, $arrStores) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['users'] as $key => $item) {
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	public function log_temporary_plan_contract($data)
	{
		$id = !empty($data['id']) ? $data['id'] : "";
		$mic = $this->temporary_plan_contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$mic['id'] = (string)$mic['_id'];
		unset($mic['_id']);
		$dataInser = array(
			"new_data" => $data,
			"old_data" => $mic,
			"type" => 'temporary_plan_contract'

		);
		$this->log_model->insert($dataInser);
	}

}

?>

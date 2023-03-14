<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Pti_vta_fee extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('pti_vta_fee_model');
		$this->load->model('pti_vta_log_model');

		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$dataPost = $this->input->post();
		$this->flag_login = 1;
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
				}
			}
		}
	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function createPtiFee_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$data['start_date'] = strtotime($data['start_date'] . ' 00:00:00');
		$data['end_date'] = strtotime($data['end_date'] . ' 23:59:59');
		$data['packet'] = trim(strtoupper($data['packet']));
		$data['number_packet'] = trim(strtoupper(substr($data['packet'],1,1)));
		$data['created_at'] = $this->createdAt;
		$data['created_by'] = $this->uemail;
		$oldPacket = $this->pti_vta_fee_model->findOne(['status' => 'active', 'packet' => $data['packet']]);
		if (!empty($oldPacket)) {
			$arr_update = [
				'status' => 'deactivate',
				'updated_at' => $this->createdAt,
				'updated_by' => $this->uemail
			];
			$this->pti_vta_fee_model->update(
				['_id' => $oldPacket['_id']],
				$arr_update
			);
			$insertUpdateLog = array(
				"type" => "pta_fee",
				"action" => "update",
				"pti_fee_id" => (string)$oldPacket['_id'],
				"old" => $oldPacket,
				"new" => $arr_update,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->pti_vta_log_model->insert($insertUpdateLog);
		}
		$ptiFeeId = $this->pti_vta_fee_model->insertReturnId($data);
		$insertLog = array(
			"type" => "pta_fee",
			"action" => "create",
			"pti_fee_id" => (string)$ptiFeeId,
			"old" => $data,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->pti_vta_log_model->insert($insertLog);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Thêm mới biểu phí bảo hiểm thành công!"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function list_pti_fee_post()
	{
		$list_pti_fee = $this->pti_vta_fee_model->find_where_by_packet(['status' => 'active']);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $list_pti_fee
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_pti_fee_post()
	{
		$data = $this->input->post();
		$data['packet'] = $this->security->xss_clean($data['packet']);
		$dataFee = $this->pti_vta_fee_model->findOne(['status' => 'active', 'packet' => $data['packet']]);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataFee
		];
		$this->set_response($response,REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$list_pti_fee = $this->pti_vta_fee_model->find_where(['status' => 'active']);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $list_pti_fee
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

}

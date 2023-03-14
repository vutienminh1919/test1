<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');

require_once APPPATH . 'libraries/HeyU.php';

class CronHeyU extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
		$this->load->model("hey_u_model");
		$this->load->model("log_hey_u_model");
		$this->load->model("history_heyu_model");
	}

	public function get_history_heyu()
	{
		$heyU = new HeyU();
		$param = [
			'page' => 0,
			'limit' => 60,
			'sort' => -1
		];
		$result = $heyU->history_recharge($param);

		foreach ($result->data as $value) {
			$record_heyu = $this->history_heyu_model->findOne(['transactionId' => $value->transactionId]);
			if (!empty($record_heyu)) {
				continue;
			}
			$insert = [
				'name' => $value->member->name,
				'name_code' => $value->member->code,
				'amount' => $value->amount,
				'orderId' => $value->orderId,
				'transactionId' => $value->transactionId,
				'created_at' => $value->createdAt
			];
			$this->history_heyu_model->insert($insert);
		}
		return 'ok';
	}
}

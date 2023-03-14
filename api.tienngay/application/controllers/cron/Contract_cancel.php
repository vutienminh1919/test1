<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Contract_cancel extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
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
		$this->load->model("transaction_extend_model");
		$this->load->model('borrowed_model');
		$this->load->model('log_borrowed_model');
		$this->load->model('borrowed_noti_model');
		$this->load->model('file_return_model');
		$this->load->model('log_file_return_model');
		$this->load->model('log_sendfile_model');
		$this->load->model('sendfile_model');
		$this->load->model('file_manager_model');
		$this->load->model('email_template_model');
		$this->load->model('email_history_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());

	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;


	public function cancel_ContractDay()
	{
      die('xx');
		$contract = $this->contract_model->find_where_cancel(array("status" => 8));

		$time = strtotime('-10 day', strtotime(date('Y-m-d')));

		if (!empty($contract)) {
			foreach ($contract as $key => $value) {
				$data_log = $this->log_hs_model->find_where_in_cancel($value->code_contract);
				if (!empty($data_log)) {
					foreach ($data_log as $item) {
						if ($item['created_at'] < $time) {
							$this->contract_model->update(array("code_contract" => $item["old"]['code_contract']), array("status" => 3));

							$insertLog = array(
								"type" => "contract_cron",
								"action" => "updateStatus_cancel",
								"code_contract" => $item["old"]['code_contract'],
								"new" => [
									"status" => 3
								],
								"created_at" => $this->createdAt
							);
							$this->log_model->insert($insertLog);
						}
					}
				}
			}
		}
		echo 'ok';

	}

	public function update_qua_han()
	{
		//cron 1 ngay 1 lan
		$check_borrowed = $this->borrowed_model->find_where(array("status" => "7"));
//		$user_cht = $this->getGroupRole_cht();

		if (!empty($check_borrowed)) {
			foreach ($check_borrowed as $key => $value) {
				if ($this->createdAt > $value->borrowed_end) {

					$log = array(
						"type" => "borrowed",
						"action" => "qua_han",
						"borrowed_id" => $value['_id'],
						"old" => $value,
						"new" => ["status" => "12"],
						"created_at" => $this->createdAt,
						"created_by" => "cron"
					);

					$this->log_borrowed_model->insert($log);

					$user = array($value['created_by']['id']);
					$value['status'] = "12";
					$this->sendEmailApprove_borrowed($value, $user);

					if (!empty($user)) {
						foreach (array_unique($user) as $c) {
							$data_approve = [
								'action_id' => (string)$value['_id'],
								'action' => 'Borrowed',
								'note' => 'Quá hạn mượn HS',
								'user_id' => $c,
								'status' => 1, //1: new, 2 : read, 3: block,
								'borrowed_status' => 12,
								'created_at' => $this->createdAt,
								"created_by" => $this->uemail
							];
							$this->borrowed_noti_model->insert($data_approve);
						}
					}

					$this->borrowed_model->update(array("_id" => $value['_id']), ["status" => "12"]);
				}
			}
		}

		echo "ok";

	}

	public function check_handling()
	{
		// cron 1 ngày 1 lần
		$check_file_manager = $this->file_manager_model->find_where(array("status" => "3"));

		foreach ($check_file_manager as $value) {
			if ($this->createdAt > $value['created_at']) {
				$check_time = abs($this->createdAt - $value['created_at']);
				$years = floor($check_time / (365 * 60 * 60 * 24));
				$months = floor(($check_time - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
				$days = floor(($check_time - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

				if ($days >= 2) {
					$fileReturn = $this->file_manager_model->findOne(array("_id" => new MongoDB\BSON\ObjectId((string)$value['_id'])));

					$check_area = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($fileReturn['stores'])));

					if ($check_area['code_area'] == "Priority" ||$check_area['code_area'] == "KV_HN1" || $check_area['code_area'] == "KV_HN2" || $check_area['code_area'] == "KV_QN" || $check_area['code_area'] == "KV_MT1") {
						$user = $this->quan_ly_ho_so_mb();
					} else {
						$user = $this->quan_ly_ho_so_mn();
					}

					if (!empty($user)) {
						foreach (array_unique($user) as $re) {
							$data_approve = [
								'action_id' => (string)$value['_id'],
								'action' => 'FileReturn_pending',
								'code_contract' => $value['code_contract_disbursement_text'],
								'note' => 'Chưa xử lý hồ sơ do CVKD gửi',
								'user_id' => $re,
								'status' => 1, //1: new, 2 : read, 3: block,
								'fileReturn_status' => 15,
								'created_at' => $this->createdAt,
							];
							$this->borrowed_noti_model->insert($data_approve);
						}
					}
				}

			}

		}

		echo "oke";

	}

	public function check_day_five()
	{
		//cron 1 ngày 1 lần
		$time = strtotime('-5 day', strtotime(date('Y-m-d', (int)$this->createdAt)));
		$time = date("Y-m-d", $time);

		$start = strtotime(trim($time) . ' 00:00:00');
		$end = strtotime(trim($time) . ' 23:59:59');

		$contract = $this->contract_model->find_where_day(array("status" => 17));

		foreach ($contract as $value) {

			if ($start <= $value['disbursement_date'] && $value['disbursement_date'] <= $end) {

				$check_file_manager = $this->file_manager_model->findOne(array("code_contract_disbursement_text" => $value->code_contract_disbursement));

				if (empty($check_file_manager)) {

					$user1 = $this->user_model->findOne(array("email" => $value['created_by']));
					$user = array((string)$user1['_id']);

					if (!empty($user)) {
						foreach (array_unique($user) as $c) {
							$data_approve = [
								'action' => 'Chưa gửi HS giải ngân',
								'note' => 'Chưa gửi HS giải ngân',
								"code_contract" => $value->code_contract_disbursement,
								'user_id' => $c,
								'status' => 1, //1: new, 2 : read, 3: block,
								'borrowed_status' => 13,
								'created_at' => $this->createdAt,
								"created_by" => "cron"
							];
							$this->borrowed_noti_model->insert($data_approve);
						}
					}

				}
			}
		}

	}


	public function quan_ly_ho_so_mn()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-nam")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	public function quan_ly_ho_so_mb()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];

		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "qlhs-mien-bac")) {
				foreach ($role['users'] as $key1 => $user) {

					foreach ($user as $key2 => $item) {

						array_push($data, $key2);

					}
				}
			}
		}
		return $data;
	}

	private function sendEmailApprove_borrowed($fileReturn, $user_qlhs)
	{
		$status_text = "";
		$id = $fileReturn['_id'];

		if ($fileReturn['status'] == "1") {
			$status_text = "Mới";
		} elseif ($fileReturn['status'] == "2") {
			$status_text = "Hủy yêu cầu";
		} elseif ($fileReturn['status'] == "3") {
			$status_text = "PGD YC mượn HS giải ngân";
		} elseif ($fileReturn['status'] == "4") {
			$status_text = "Yêu cầu mượn HS";
		} elseif ($fileReturn['status'] == "5") {
			$status_text = "QLHS trả về yêu cầu mượn";
		} elseif ($fileReturn['status'] == "6") {
			$status_text = "Chờ nhận hồ sơ";
		} elseif ($fileReturn['status'] == "7") {
			$status_text = "Đang mượn hồ sơ";
		} elseif ($fileReturn['status'] == "8") {
			$status_text = "Chưa nhận đủ HS mượn";
		} elseif ($fileReturn['status'] == "9") {
			$status_text = "Trả HS mượn về HO";
		} elseif ($fileReturn['status'] == "10") {
			$status_text = "Lưu kho";
		} elseif ($fileReturn['status'] == "11") {
			$status_text = "Chưa trả đủ HS đã mượn";
		} elseif ($fileReturn['status'] == "12") {
			$status_text = "Quá hạn mượn HS";
		}

		$data = array(
			'code' => "vfc_send_email_qlhs",
			'code_contract_disbursement' => $fileReturn['code_contract_disbursement_text'],
			'status' => $status_text,
			'url' => "https://cpanel.tienngay.vn/file_manager/detail?id=$id"
		);

		foreach ($user_qlhs as $item) {
			$email_user = $this->getGroupRole_email($item);
			foreach ($email_user as $value) {
				$data['email'] = "$value";
				$data['API_KEY'] = $this->config->item('API_KEY');
				$this->user_model->send_Email($data);
//				$this->sendEmail($data);

			}

		}
		return;
	}

	private function getGroupRole_email($userId)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($arr, $item[key($item)]['email']);
					continue;
				}
			}
		}
		return array_unique($arr);
	}


	public function sendEmail($dataPost)
	{
		$email_template = $this->email_template_model->findOne(array('code' => $dataPost['code'], 'status' => 'active'));

		$domain = $this->config->item('sendgrid_domain');
		// var_dump($email_template); die;

		$from = 'support@tienngay.vn';
		$from_name = $email_template['from_name'];
		$subject = $email_template['subject'];
		$message = $this->getEmailStr($email_template['message'], $dataPost);
		$status = 'active';
		$data = array(
			"code" => $dataPost['code'],
			"from" => $from,
			"from_name" => $from_name,
			"to" => $dataPost['email'],
			"subject" => $subject,
			"email_domain" => $domain,
			"status" => $status,
			"message" => $message,
//			"device" => $this->agent->browser() . ';' . $this->agent->platform(),
//			"ipaddress" => getIpAddress(),
			"created_at" => (int)$this->createdAt
		);

		//var_dump('expression');

		$this->email_history_model->insert($data);
		return;


	}

	public function send($from, $to, $subject, $message, $from_name)
	{

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($from, $from_name);
		$email->setSubject($subject);
		$email->addTo($to, "");
		$email->addContent(
			"text/html", $message
		);
		$sendgrid = new \SendGrid($this->config->item('sendgrid_api_key'));
		try {
			$response = $sendgrid->send($email);
			// print $response->statusCode() . "\n";
			//    print_r($response->headers());
			//    print $response->body() . "\n";
			if ($response->statusCode() == '202') {
				return true;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}


	}

	public function getEmailStr($emailTemplate, $filter)
	{
		foreach ($filter as $key => $value) {
			$emailTemplate = str_replace("{" . $key . "}", $value, $emailTemplate);
		}
		return $emailTemplate;
	}

	public function getGroupRole_cht()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'cua-hang-truong'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}


	public function update_qua_han_vpp()
	{

		//cron 1 ngay 1 lan
		$check_borrowed = $this->sendfile_model->find_where(array("status" => "1"));

		if (!empty($check_borrowed)) {
			foreach ($check_borrowed as $key => $value) {

				$check_time = strtotime(trim(date("Y-m-d", $value->send_end)) . ' 23:59:59');

				if ($this->createdAt > $check_time && empty($value->return_time)) {

					$log = array(
						"type" => "sendFile",
						"action" => "chua-nhan-vpp",
						"sendFile_id" => (string)$value['_id'],
						"old" => $value,
						"new" => ["status" => "4"],
						"created_at" => $this->createdAt,
						"created_by" => "cron"
					);

					$this->log_sendfile_model->insert($log);

					foreach ($value['store_take_value'][0] as $item) {

//						$user = $this->getGroupRole_slug($item);
						$user = $this->getGroupRole_vpp();

						if (!empty($user)) {
							foreach (array_unique($user) as $c) {
								$data_approve = [
									'action_id' => (string)$value['_id'],
									'action' => 'SendFile',
									'note' => 'Gửi vpp - Chưa nhận đc VPP',
									'user_id' => $c,
									'status' => 1, //1: new, 2 : read, 3: block,
									'sendFile_status' => 4,
									'created_at' => $this->createdAt,
									"created_by" => $this->check_name($item)
								];
								$this->borrowed_noti_model->insert($data_approve);
							}
						}

					}

					$this->sendfile_model->update(array("_id" => $value['_id']), ["status" => "4"]);
				}
			}
		}

		echo "ok";

	}

	public function getGroupRole_vpp()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'hanh-chinh'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						array_push($arr, $key);
					}

				}
			}
		}
		return $arr;
	}

	public function check_name($slug)
	{
		$groupRoles = $this->role_model->find_where(array("status" => "active", 'slug' => $slug));

		if (!empty($groupRoles)) {
			return $groupRoles[0]['name'];

		}
	}


}



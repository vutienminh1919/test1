<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Phonenet.php';

//require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class Ladipage_lead extends REST_Controller
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
		$this->load->model('lead_otp_model');
		$this->load->model('lead_at_log_model');
		$this->load->model('log_accesstrade_model');
		$this->load->model('lead_investors_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
		$phonenet = new Phonenet();
		$this->phonenet = $phonenet;

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


	public function register_lead_post()
	{

		$this->dataPost['name'] = !empty($this->security->xss_clean($this->dataPost['name'])) ? $this->dataPost['name'] : "";
		$phone_number = !empty($this->security->xss_clean($this->dataPost['phone'])) ? $this->dataPost['phone'] : "";


		$message = $this->validate_phone_number($phone_number);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

//		$lead = $this->lead_otp_model->findOne(['phone_number' => $phone_number]);
		$otp = rand(100000, 999999);
		$timeExpried = time() + 2 * 60;

		$result = $this->phonenet->send_sms_voice_otp($phone_number, $otp);
		if ($result['status'] == 'ok') {
			$user_id = $this->lead_otp_model->insertReturnId(
				[
					'name' => $this->dataPost['name'],
					'phone_number' => $phone_number,
					'created_at' => $this->createdAt,
					'status' => 'new',
					'status_lead' => false,
					'token_active' => $otp,
					'timeExpried_active' => $timeExpried,
					'response' => $result,
				]
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'lead_id' => (string)$user_id,
				'message' => "Mã OTP sẽ được cung cấp thông qua cuộc gọi điện thoại!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Gửi OTP thất bại",
				'result'=>$result
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


	}

	public function register_lead_ndt_post()
	{

		$this->dataPost['name'] = !empty($this->security->xss_clean($this->dataPost['name'])) ? $this->dataPost['name'] : "";
		$phone_number = !empty($this->security->xss_clean($this->dataPost['phone'])) ? $this->dataPost['phone'] : "";
		$phone_ngt = !empty($this->security->xss_clean($this->dataPost['sdt_ndt'])) ? $this->dataPost['sdt_ndt'] : "";


		$message = $this->validate_phone_number($phone_number);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$message1 = $this->validate_phone_number_ndt($phone_ngt);
		if (count($message1) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message1[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$lead = $this->lead_investors_model->findOne(['phone_number' => $phone_number]);

		if (!empty($lead)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Số điện thoại đã tổn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$otp = rand(100000, 999999);
		$timeExpried = time() + 2 * 60;

		$result = $this->phonenet->send_sms_voice_otp($phone_number, $otp);
		if ($result['status'] == 'ok') {
			$user_id = $this->lead_otp_model->insertReturnId(
				[
					'name' => $this->dataPost['name'],
					'phone_number' => $phone_number,
					'phone_ngt' => $phone_ngt,
					'created_at' => $this->createdAt,
					'status' => 'new',
					'status_lead' => false,
					'token_active' => $otp,
					'timeExpried_active' => $timeExpried,
					'response' => $result,
				]
			);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'lead_id' => (string)$user_id,
				'message' => "Mã OTP sẽ được cung cấp thông qua cuộc gọi điện thoại!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Gửi OTP thất bại",
				'result'=>$result
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function insert_lead_post()
	{

		$this->dataPost['phone'] = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
		$this->dataPost['name'] = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		$this->dataPost['otp_phone'] = !empty($this->dataPost['otp_phone']) ? $this->dataPost['otp_phone'] : "";
		$this->dataPost['lead_id'] = !empty($this->dataPost['lead_id']) ? $this->dataPost['lead_id'] : "";
		$this->dataPost['utmSource'] = !empty($this->dataPost['utmSource']) ? $this->dataPost['utmSource'] : "";
		$this->dataPost['utmCampaign'] = !empty($this->dataPost['utmCampaign']) ? $this->dataPost['utmCampaign'] : "";
		$this->dataPost['link'] = !empty($this->dataPost['link']) ? $this->dataPost['link'] : "";

		$message_phone = $this->validate_phone_number($this->dataPost['phone']);
		if (count($message_phone) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message_phone[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		$message = $this->validate_otp($this->dataPost['otp_phone']);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$lead = $this->lead_otp_model->findOne(['_id' => new MongoDB\BSON\ObjectId((string)$this->dataPost['lead_id']), 'token_active' => (int)$this->dataPost['otp_phone']]);

		if (empty($lead)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "OTP không chính xác"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {

			$link = !empty($this->dataPost['link']) ? $this->dataPost['link'] : "";
			$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
			$phoneNumber = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
			$utmSource = !empty($this->dataPost['utmSource']) ? $this->dataPost['utmSource'] : "direct";
			$utmCampaign = !empty($this->dataPost['utmCampaign']) ? $this->dataPost['utmCampaign'] : $link;

			//Kh giới thiệu khách hàng - 200k
			$presenter_name = isset($_POST['presenter_name']) ? $_POST['presenter_name'] : "";
			$customer_phone_introduce = isset($_POST['presenter_phone']) ? $_POST['presenter_phone'] : "";
			$presenter_email = isset($_POST['presenter_email']) ? $_POST['presenter_email'] : "";
			$presenter_stk = isset($_POST['presenter_stk']) ? $_POST['presenter_stk'] : "";
			$presenter_bank = isset($_POST['presenter_bank']) ? $_POST['presenter_bank'] : "";

			if ($utmSource == "masoffer" && !empty($utmCampaign)) {

				$click_id = explode("=", $utmCampaign);

				$click_id = $click_id[2];
				$click_id_masoffer = !empty($click_id) ? $click_id : "";

			}

			$page = "";
			$area = '00';
			if (!empty($utmCampaign)) {
				if (strlen(strstr(strtoupper($utmCampaign), "HN")) > 0) {
					$area = '01';
				} elseif (strlen(strstr(strtoupper($utmCampaign), "HCM")) > 0) {
					$area = '79';
				} else {
					$area = '00';
				}
			}

			if (!empty($utmCampaign)) {
				$source = explode("/", $utmCampaign);
				if (count($source) > 2) {
					$toss = $source[3];
				}
			}
			if (!empty($toss) && $toss == "toss") {
				$utmSource = "Toss";
			}

			$source_new = "1";
			if ($utmSource == "KH") {
				$source_new = "11";
			}

			$result = array();

			$data = array(
				"fullname" => $name,
				"phone_number" => convert_zero_phone($phoneNumber),
				"utm_source" => $utmSource,
				"utm_campaign" => $utmCampaign,

				"source" => $source_new,
				'link' => $link,
				"status" => '1',
				"area" => $area,
				"status_sale" => '1',
				"ip" => $this->get_client_ip(),
				"created_at" => $this->createdAt,
				"click_id_masoffer" => $click_id_masoffer,

				"presenter_name" => $presenter_name,
				"customer_phone_introduce" => $customer_phone_introduce,
				"presenter_email" => $presenter_email,
				"presenter_stk" => $presenter_stk,
				"presenter_bank" => $presenter_bank,

			);
			$lead = $this->lead_model->findOne_langding(array("phone_number" => convert_zero_phone($phoneNumber)));


			if (!empty($lead)) {

				$current_day = strtotime(date('m/d/Y'));
				$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
				$time = intval(($current_day - $datetime) / (24 * 60 * 60));
				$last = 1 - $time;
				if ($time >= 1) {
					$this->lead_model->insert($data);

					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Create success"
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;
				} else {

					date_default_timezone_set('Asia/Ho_Chi_Minh');

					$leadData = $this->lead_at_log_model->findOne(array("phone_number" => convert_zero_phone($phoneNumber)));

					if (!empty($leadData['utm_campaign'])) {
						$tracking_id = explode("=", $leadData['utm_campaign']);
						$result_tracking = $tracking_id[2];
						if (count($tracking_id) > 3) {
							$result_tracking_1 = explode("&", $tracking_id[2]);
							$result_tracking = $result_tracking_1[0];
						}
					}
					$data2 = array(

						"conversion_id" => !empty($leadData['_id']) ? (string)$leadData['_id'] : "",

						"conversion_result_id" => "30",

						"tracking_id" => !empty($result_tracking) ? $result_tracking : "",

						"transaction_id" => !empty($leadData['_id']) ? (string)$leadData['_id'] : "",

						"transaction_time" => !empty($leadData["created_at"]) ? date('Y-m-d\TH:i:s.Z\Z', $leadData["created_at"]) : "",

						"transaction_value" => 0,

						"status" => 2,

						"extra" => [
							"rejected_reason" => "Trùng số điện thoại",
							"phone_number" => $leadData['phone_number']
						],

						"is_cpql" => 1,

						"items" => []

					);

					$data_string = json_encode($data2);

					$ch = curl_init('https://api.accesstrade.vn/v1/postbacks/conversions');

					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

					curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_HTTPHEADER, array(

						'Content-Type: application/json',

						'Authorization: Token fn1-vtdKGhR3afT1eJ3qw3XS9N3yv78K'

					));

					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

					curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds


					curl_exec($ch);

					//insert log
					$this->log_accesstrade_model->insert($data2);

					$response = array(
						'status' => REST_Controller::HTTP_OK,
						'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
					);
					$this->set_response($response, REST_Controller::HTTP_OK);
					return;

				}


			} else {

				if ($utmSource == "accesstrade" || $utmSource == "google") {
					$this->lead_at_log_model->insert($data);
				}

				$this->lead_model->insert($data);

				//Masoffer
				//api_key = 9Tprs9wMJ4q2Q7lB -- Masoffer
				//
				if ($utmSource == "masoffer" && $click_id_masoffer != "") {
					$api_key = "9Tprs9wMJ4q2Q7lB";
					$lead_masoffer = $this->lead_model->findOne(array("phone_number" => convert_zero_phone($phoneNumber)));
					$transaction_id_masoffer = (string)$lead_masoffer['_id'];

					$url = "https://s2s.riofintech.net/v1/tienngay/postback.json?api_key=$api_key&postback_type=cpl_standard_postback&transaction_id=$transaction_id_masoffer&click_id=$click_id_masoffer&status_code=0";

					$ch = curl_init($url);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

					curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout in seconds

					$result = curl_exec($ch);

					curl_close($ch);

					echo $result;
				}
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => "Create success"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;

			}
		}


	}

	public function insert_lead_ndt_post()
	{

		$this->dataPost['phone'] = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
		$this->dataPost['name'] = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
		$this->dataPost['otp_phone'] = !empty($this->dataPost['otp_phone']) ? $this->dataPost['otp_phone'] : "";
		$this->dataPost['lead_id'] = !empty($this->dataPost['lead_id']) ? $this->dataPost['lead_id'] : "";
		$this->dataPost['utmSource'] = !empty($this->dataPost['utmSource']) ? $this->dataPost['utmSource'] : "";
		$this->dataPost['utmCampaign'] = !empty($this->dataPost['utmCampaign']) ? $this->dataPost['utmCampaign'] : "";
		$this->dataPost['link'] = !empty($this->dataPost['link']) ? $this->dataPost['link'] : "";
		$this->dataPost['sdt_ndt'] = !empty($this->dataPost['sdt_ndt']) ? $this->dataPost['sdt_ndt'] : "";

		$message_phone = $this->validate_phone_number($this->dataPost['phone']);
		if (count($message_phone) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message_phone[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$message_phone1 = $this->validate_phone_number_ndt($this->dataPost['sdt_ndt']);
		if (count($message_phone1) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message_phone1[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


		$message = $this->validate_otp($this->dataPost['otp_phone']);
		if (count($message) > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => $message[0]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$lead = $this->lead_otp_model->findOne(['_id' => new MongoDB\BSON\ObjectId((string)$this->dataPost['lead_id']), 'token_active' => (int)$this->dataPost['otp_phone']]);

		if (empty($lead)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "OTP không chính xác"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {

			$link = !empty($this->dataPost['link']) ? $this->dataPost['link'] : "";
			$name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
			$phoneNumber = !empty($this->dataPost['phone']) ? $this->dataPost['phone'] : "";
			$utmSource = !empty($this->dataPost['utmSource']) ? $this->dataPost['utmSource'] : "direct";
			$utmCampaign = !empty($this->dataPost['utmCampaign']) ? $this->dataPost['utmCampaign'] : $link;

			$phone_ngt = !empty($this->dataPost['sdt_ndt']) ? $this->dataPost['sdt_ndt'] : "";

			$check_lead_ndt = $this->lead_investors_model->findOne_langding(array("phone_number" => convert_zero_phone($phoneNumber)));


			if (empty($check_lead_ndt)) {
				$data = array(
					"fullname" => $name,
					"phone_number" => convert_zero_phone($phoneNumber),
					"utm_source" => $utmSource,
					"utm_campaign" => $utmCampaign,
					"phone_ngt" => $phone_ngt,
					"source" => '1',
					'link' => $link,
					"status" => '1',
					"status_nđt" => '1',
					"created_at" => $this->createdAt,
				);
				$this->lead_investors_model->insert($data);

				$this->call_api_ndt($data);

				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => "Create success"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}


		}
	}

	public function call_api_ndt($sendApi){

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://apindt.tienngay.vn/insert_lead_invest',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($sendApi),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded',
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;

	}

	private function validate_phone_number($phone_number)
	{
		$message = [];
		if (empty($phone_number)) {
			$message[] = "Số điện thoại không để trống";
		}
		if (strip_tags($phone_number) == 1) {
			$message[] = "Số điện thoại không hợp lệ. Chứa các tag nguy hiểm";
		}
		if (preg_replace('/\s+/', '', $phone_number) == 1) {
			$message[] = "Số điện thoại không hợp lệ. Có chứa khoảng trắng";
		}
		if (preg_match("/^[0-9]{10,11}$/", $phone_number) != 1) {
			$message[] = "Số điện thoại từ 10 đến 11 kí tự";

		}
		return $message;
	}
	private function validate_phone_number_ndt($phone_number)
	{
		$message = [];
		if (strip_tags($phone_number) == 1) {
			$message[] = "Số điện thoại không hợp lệ. Chứa các tag nguy hiểm";
		}
		if (preg_replace('/\s+/', '', $phone_number) == 1) {
			$message[] = "Số điện thoại không hợp lệ. Có chứa khoảng trắng";
		}

		return $message;
	}

	private function validate_otp($otp)
	{
		$message = [];
		if (empty($otp)) {
			$message[] = "OTP không để trống";
		}
		if (preg_match("/^[0-9]{6}$/", $otp) != 1) {
			$message[] = "OTP phải 6 kí tự số";
		}
		return $message;
	}


	public function get_client_ip()
	{
		$ipaddress = '';
		if (getenv('HTTP_CLIENT_IP'))
			$ipaddress = getenv('HTTP_CLIENT_IP');
		else if (getenv('HTTP_X_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED'))
			$ipaddress = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		else if (getenv('HTTP_FORWARDED'))
			$ipaddress = getenv('HTTP_FORWARDED');
		else if (getenv('REMOTE_ADDR'))
			$ipaddress = getenv('REMOTE_ADDR');
		else
			$ipaddress = 'UNKNOWN';

		return $ipaddress;
	}

	public function apiOtpCore_v2_post(){

		$otp = !empty($this->dataPost['otp']) ? $this->dataPost['otp'] : "";
		$ctv_phone = !empty($this->dataPost['ctv_phone']) ? $this->dataPost['ctv_phone'] : "";

		$result = $this->phonenet->send_sms_voice_otp($ctv_phone, $otp);

		if ($result['status'] == 'ok') {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Mã OTP sẽ được cung cấp thông qua cuộc gọi điện thoại!"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Gửi OTP thất bại",
				'result'=> $result
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}


}

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Lead extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("lead_model");
		$this->load->model("log_lead_model");
		$this->load->model("dashboard_model");
		$this->load->model("landing_page_model");
		$this->load->helper('lead_helper');
		$this->load->model('log_accesstrade_model');
		$this->load->model('lead_at_log_model');
		$this->load->model('lead_investors_model');
		$this->load->model('lead_dinos_log_model');
		$this->load->model('webhook_vbee_model');
		$this->load->model('log_vbee_model');
		$this->load->model('recording_model');
		$this->load->model('log_vbee_missed_call_model');
		$this->load->model('list_topup_model');
		$this->ci =& get_instance();
		$this->ci->config->load('config');
		$this->baseURL = $this->ci->config->item("missed_call");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());

	}

	private $createdAt;

	public function loannow_post()
	{
		$data = $this->input->post();
		if (empty($data['type_finance']) || empty($data['phone_number'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Fields can not empty'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$data['fullname'] = $this->security->xss_clean($data['fullname']);
		$data['phone_number'] = convert_zero_phone($this->security->xss_clean($data['phone_number']));
		$data['type_finance'] = $this->security->xss_clean($data['type_finance']);
		$data['type_finance'] = $data['type_finance'];
		$data['type'] = $data['type'];
		$data['status_sale'] = (!empty($data['status_sale'])) ? $data['status_sale'] : '1';
		$data['city'] = $this->security->xss_clean($data['city']);
		$data['call'] = $this->security->xss_clean($data['call']);
		$data['status'] = $this->security->xss_clean($data['status']);
		$data['created_at'] = $this->createdAt;
		$data['area'] = $this->security->xss_clean($data['city']);
//		$data['status_call']= '0';

//Count number
		$lead = $this->lead_model->findOne_langding(array("phone_number" => $data['phone_number']));
		if (!empty($lead)) {
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$last = 1 - $time;
			if ($time >= 1) {
				$this->lead_model->insert($data);

			} else {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {

			$this->lead_model->insert($data);

		}
//Summary for dashboard
		$dashboard = $this->dashboard_model->find();
		if (isset($dashboard[0]['lead_customer']['not_call'])) {
			$count = $dashboard[0]['lead_customer']['not_call'];
			$this->dashboard_model->update(
				array("_id" => $dashboard[0]['_id']),
				array("lead_customer.not_call" => $count + 1)
			);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Loan now successfully'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function servicenow_post()
	{
		$data = $this->input->post();
		if (empty($data['type_finance']) || empty($data['phone_number'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Fields can not empty'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$data['phone_number'] = convert_zero_phone($this->security->xss_clean($data['phone_number']));
		$data['type_finance'] = $this->security->xss_clean($data['type_finance']);
		$data['type_finance'] = $data['type_finance'];
		$data['status_sale'] = (!empty($data['status_sale'])) ? $data['status_sale'] : '1';
		$data['service'] = $this->security->xss_clean($data['service']);
		$data['call'] = $this->security->xss_clean($data['call']);
		$data['status'] = $this->security->xss_clean($data['status']);
		$data['created_at'] = $this->createdAt;
		$data['area'] = $this->security->xss_clean($data['city']);
//		$data['status_call'] = '0';
		//Count number
		$lead = $this->lead_model->findOne_langding(array("phone_number" => $data['phone_number']));
		if (!empty($lead)) {
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$last = 1 - $time;
			if ($time >= 1) {
				$this->lead_model->insert($data);

			} else {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {

			$this->lead_model->insert($data);

		}

		//Summary for dashboard
		$dashboard = $this->dashboard_model->find();
		if (isset($dashboard[0]['lead_customer']['not_call'])) {
			$count = $dashboard[0]['lead_customer']['not_call'];
			$this->dashboard_model->update(
				array("_id" => $dashboard[0]['_id']),
				array("lead_customer.not_call" => $count + 1)
			);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Loan now successfully'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function register_lading_post()
	{

		$link = isset($_POST['link']) ? $_POST['link'] : "";
		$name = isset($_POST['name']) ? $_POST['name'] : "";
		$phoneNumber = isset($_POST['phone']) ? $_POST['phone'] : "";
		$utmSource = isset($_POST['utm_source']) ? $_POST['utm_source'] : "direct";
		$utmCampaign = isset($_POST['utm_campaign']) ? $_POST['utm_campaign'] : $link;
		$typeLoan = isset($_POST['type_loan']) ? $_POST['type_loan'] : "";
		$address = isset($_POST['address']) ? $_POST['address'] : "";

//		$utmSource = "Dinos";
//		$utmCampaign = "https://dangky.tienngay.vn/dinos?utm_source=Dinos&utm_param_sub_id=b7f4e7ff28c3261d370b0b98748b4432";

		$click_id_masoffer = "";
		$click_id_dinos = "";
		if ($utmSource == "masoffer" && !empty($utmCampaign)) {

			$click_id = explode("=", $utmCampaign);

			$click_id = $click_id[2];
			$click_id_masoffer = !empty($click_id) ? $click_id : "";

		}
		if ($utmSource == "Dinos" && !empty($utmCampaign)) {

			$click_id = explode("=", $utmCampaign);
			$click_id = $click_id[2];
			$click_id_dinos = !empty($click_id) ? $click_id : "";

		}

		//Kh giới thiệu khách hàng - 200k
		$presenter_name = isset($_POST['presenter_name']) ? $_POST['presenter_name'] : "";
		$customer_phone_introduce = isset($_POST['presenter_phone']) ? $_POST['presenter_phone'] : "";
		$presenter_email = isset($_POST['presenter_email']) ? $_POST['presenter_email'] : "";
		$presenter_stk = isset($_POST['presenter_stk']) ? $_POST['presenter_stk'] : "";
		$presenter_bank = isset($_POST['presenter_bank']) ? $_POST['presenter_bank'] : "";


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
			"type_finance" => $this->get_finance($typeLoan),
			"address" => $address,
			"source" => $source_new,
			'link' => $link,
			"status" => '1',
			"area" => $area,
			"status_sale" => '1',
//			"status_call" => '0',
			"ip" => $this->get_client_ip(),
			"created_at" => $this->createdAt,
			"click_id_masoffer" => $click_id_masoffer,
			"click_id_dinos" => $click_id_dinos,

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
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}


		} else {

			if ($utmSource == "accesstrade" || $utmSource == "google") {
				$this->lead_at_log_model->insert($data);
			}
			if ($utmSource == "Dinos") {
				$this->lead_dinos_log_model->insert($data);
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

	private function api_dinos($click_id)
	{

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://api.dinos.vn/api/v1/post_back_campaign_redirect?click_id=$click_id&status=pending",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;


	}

	public function run_area_post()
	{
		$lead = $this->lead_model->find();
		foreach ($lead as $key => $value) {
			$link = (isset($value['link'])) ? $value['link'] : '';
			if (!empty($link)) {
				$page_url = explode('?', $link, 2);
				$page = $page_url[0];
			}
			$area = '01';
			$lang_ding = $this->landing_page_model->findOne(array("url" => $page));
			if (!empty($lang_ding)) {
				$area = $lang_ding['province_id'];
			}
			//Update lead
			$this->lead_model->update(
				array("_id" => $value['_id']),
				array('area' => $area)
			);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "OK"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function run_area_log_post()
	{
		$lead = $this->log_lead_model->find();

		foreach ($lead as $key => $value_t) {
			$value = $value_t['old_data'];
			$page = "";
			$link = (isset($value['link'])) ? $value['link'] : '';
			if (!empty($link)) {
				$page_url = explode('?', $link, 2);
				$page = $page_url[0];
			}
			$area = '01';
			$lang_ding = $this->landing_page_model->findOne(array("url" => $page));
			if (!empty($lang_ding)) {
				$area = $lang_ding['province_id'];
			}
			//Update lead
			$this->log_lead_model->update(
				array("_id" => $value_t['_id']),
				array('old_data.area' => $area)
			);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "OK"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_finance($id)
	{
		switch ($id) {
			case 'Vay cầm cố xe máy':
				return "2";
				break;
			case 'Vay cầm cố ô tô':
				return "1";
				break;
			case 'Vay tiền bằng đăng ký xe máy':
				return "4";
				break;
			case 'Vay tiền bằng đăng ký xe ô tô':
				return "3";
				break;
				break;
			case 'Vay tiền bằng cà vẹt xe/ đăng ký xe máy':
				return "4";
				break;
			case 'Vay tiền bằng cà vẹt xe/ đăng ký xe ô tô':
				return "3";
				break;
		}
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

	public function create_landing_post()
	{

	}

	public function get_advisory()
	{

		$data = $this->input->post();
		if (empty($data['phone_number'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Fields can not empty'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$data['fullname'] = $this->security->xss_clean($data['fullname']);
		$data['phone_number'] = convert_zero_phone($this->security->xss_clean($data['phone_number']));
		$data['type_finance'] = $this->security->xss_clean($data['type_finance']);
		$data['type_finance'] = $data['type_finance'];
		$data['type'] = $data['type'];
		$data['status_sale'] = (!empty($data['status_sale'])) ? $data['status_sale'] : '1';
		$data['city'] = $this->security->xss_clean($data['city']);
		$data['call'] = $this->security->xss_clean($data['call']);
		$data['status'] = $this->security->xss_clean($data['status']);
		$data['created_at'] = $this->createdAt;
		$data['area'] = '01';
//		$data['status_call'] = '0';
		//Count number
		$lead = $this->lead_model->findOne_langding(array("phone_number" => $data['phone_number']));
		if (!empty($lead)) {
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$last = 1 - $time;
			if ($time >= 1) {
				$this->lead_model->insert($data);

			} else {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {

			$this->lead_model->insert($data);

		}
//Summary for dashboard
		$dashboard = $this->dashboard_model->find();
		if (isset($dashboard[0]['lead_customer']['not_call'])) {
			$count = $dashboard[0]['lead_customer']['not_call'];
			$this->dashboard_model->update(
				array("_id" => $dashboard[0]['_id']),
				array("lead_customer.not_call" => $count + 1)
			);
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Loan now successfully'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function register_lading_investors_post()
	{

		$link = isset($_POST['link']) ? $_POST['link'] : "";
		$name = isset($_POST['name']) ? $_POST['name'] : "";
		$phoneNumber = isset($_POST['phone']) ? $_POST['phone'] : "";
		$utmSource = isset($_POST['utm_source']) ? $_POST['utm_source'] : "direct";
		$utmCampaign = isset($_POST['utm_campaign']) ? $_POST['utm_campaign'] : $link;

		$email = isset($_POST['email_ndt']) ? $_POST['email_ndt'] : "";
		$money = isset($_POST['sotien_ndt']) ? $_POST['sotien_ndt'] : "";
		$area = isset($_POST['khuvuc_ndt']) ? $_POST['khuvuc_ndt'] : "";
		$phone_ngt = isset($_POST['sdt_ndt']) ? $_POST['sdt_ndt'] : "";

		$check_lead_ndt = $this->lead_investors_model->findOne_langding(array("phone_number" => convert_zero_phone($phoneNumber)));


		if (empty($check_lead_ndt)) {
			$data = array(
				"fullname" => $name,
				"phone_number" => convert_zero_phone($phoneNumber),
				"utm_source" => $utmSource,
				"utm_campaign" => $utmCampaign,
				"email" => $email,
				"money" => $money,
				"area" => $area,
				"phone_ngt" => $phone_ngt,
				"source" => '1',
				'link' => $link,
				"status" => '1',
				"status_nđt" => '1',
				"ip" => $this->get_client_ip(),
				"created_at" => $this->createdAt,
			);
			$this->lead_investors_model->insert($data);

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Create success"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function index_lading_investors_post()
	{


		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";

		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 30;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		$lead_ndt = $this->lead_investors_model->getDataByRole($condition, $per_page, $uriSegment);
		if (empty($lead_ndt)) {
			echo "Không có dữ liệu";
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead_ndt
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function count_index_lading_investors_post()
	{


		$this->dataPost = $this->input->post();
		$condition = [];

		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";


		if (!empty($fdate)) {
			$condition['fdate'] = strtotime(trim($fdate) . ' 00:00:00');
		}
		if (!empty($tdate)) {
			$condition['tdate'] = strtotime(trim($tdate) . ' 23:59:59');
		}


		$lead_ndt = $this->lead_investors_model->getDataByRole_count($condition);
		if (empty($lead_ndt)) {
			echo "Không có dữ liệu";
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $lead_ndt
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function form_index_post()
	{

		$datapost = $this->input->post();
		if (empty($datapost['phone_number'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => 'Số điện thoại không được để trống'
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$datapost['fullname'] = $this->security->xss_clean($datapost['fullname']);
		$datapost['phone_number'] = convert_zero_phone($this->security->xss_clean($datapost['phone_number']));
		$datapost['type_finance'] = $this->security->xss_clean($datapost['type_finance']);
		$datapost['type'] = "1";
		$datapost['area'] = $this->security->xss_clean($datapost['city']);
		$datapost['created_at'] = $this->createdAt;
		$lead = $this->lead_model->findOne_langding(array("phone_number" => $datapost['phone_number']));
		if (!empty($lead)) {
			$current_day = strtotime(date('m/d/Y'));
			$datetime = !empty($lead[0]['created_at']) ? intval($lead[0]['created_at']) : $current_day;
			$time = intval(($current_day - $datetime) / (24 * 60 * 60));
			$last = 1 - $time;
			if ($time >= 1) {
				$this->lead_model->insert($datapost);

			} else {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau " . $last . " ngày nữa"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {
			$this->lead_model->insert($datapost);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Đăng ký vay thành công'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	//vbee

//đẩy api


	public function import_vbee_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		//20 - Đang gọi,40 - Đã nghe, 50 - Không thành công, 60 - Lỗi
		$secret_key = $this->config->item("vbee_sec_key");
		$access_token = $this->config->item("vbee_token");
		$campaign_id = 16511;
		$count = 0;
		$data = [];


		$start = strtotime(trim(date('Y-m-d')) . ' 8:30:00');
		$end = strtotime(trim(date('Y-m-d')) . ' 17:30:00');
		$current_time = $this->createdAt;


		if ($current_time > $start && $current_time < $end) {
			$leadData = $this->lead_model->find_where((array("source" => ['$in' => ["1","2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17"]], "status_call" => "0", "status_sale" => ['$in' => ["1"]])));
		} else {
			$leadData = [];
		}

		if (!empty($leadData)) {
			foreach ($leadData as $value) {
				if (($value['status_sale'] == "1") || ($value['call_vbee'] == 3 && $value['day_call'] == "1")) {
					$data[$count]["phone_number"] = $value['phone_number'];
					$data[$count]["ho_ten"] = !empty($value['fullname']) ? $value['fullname'] : "";
					$count++;
				}
			}
		}

		$data = json_encode($data);
		$response = $this->vbee_import($data, $campaign_id, $access_token);
		$response = json_decode($response);

		if (!empty($response->results) && $response->status == 1) {
			foreach ($response->results as $item) {
				if (!empty($item->phone_number)) {
					$lead = $this->lead_model->find_one_check_phone($item->phone_number);

					if (!empty($lead) && empty($lead[0]['call_id'])) {
						$this->lead_model->update(array("_id" => $lead[0]['_id']), array('call_id' => $item->call_id));
					}
					if (!empty($lead[0]['call_id']) && $lead[0]['call_vbee'] == 3) {
						$this->lead_model->update(array("_id" => $lead[0]['_id']), array('call_id' => $item->call_id));
					}
				}
			}
		}
	}

//đẩy api cho vbee

	private function vbee_import($data, $campaign_id, $access_token)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://aicallcenter.vn/api/campaigns/$campaign_id/import?access_token=$access_token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{\n    \"contacts\":  $data  \n}\t",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

//hứng api của vbeee
//vbee_aicc@tienngay.vn
	public function webhook_vbee_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$current_time = $this->createdAt;
		$check_time = strtotime(trim(date('Y-m-d')) . ' 16:50:00');
		$dataDB['request'] = json_decode($this->input->raw_input_stream);
		$check_status_lead = $this->lead_model->findOne(["call_id" => (int)$dataDB['request']->data->call_id]);

		if (!empty($dataDB['request'])) {
			$check_phone = (substr($dataDB['request']->data->key_press, 0, 1));
			$vbeeState = (int)$dataDB['request']->data->state;

			if (!empty($check_status_lead)
				&& ($vbeeState == 20 || $vbeeState == 50 || $vbeeState == 60 || $vbeeState == 40)
			) {
				if ($vbeeState == 40) {
					if ($check_phone == 1 || $check_phone == 2) {
						if ($check_status_lead['status_sale'] == "1") {
							$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_sale' => "1", 'status_vbee' => $vbeeState, 'priority' => $check_phone, "status_call" => "1"));
						} else {
							$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "status_call" => "1"));
						}
					} else {
						if ($check_status_lead['status_sale'] == "1") {
							$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_sale' => "1", 'status_vbee' => $vbeeState, 'priority' => "3", "status_call" => "1"));
						} else {
							$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "status_call" => "1"));
						}
					}
				} elseif ($vbeeState == 50 || $vbeeState == 60) {
					if ($check_status_lead['status_sale'] == "1") {
						$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_sale' => "1", 'status_vbee' => $vbeeState, 'priority' => "3", "status_call" => "1", "call_vbee" => "1"));
					} elseif (($check_status_lead['status_sale'] != "1") && ($check_status_lead['call_vbee'] == "1")) {
						$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "status_call" => "1", "call_vbee" => "2"));
					} elseif (($check_status_lead['status_sale'] != "1") && ($check_status_lead['call_vbee'] == "2")) {
						$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "status_call" => "1", "call_vbee" => "3"));
					}
				}
			}
			$this->log_vbee_model->insert($dataDB);
			$this->webhook_vbee_model->insert($dataDB);

			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $dataDB,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
	}

	public function insert_status_call_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$current_time = $this->createdAt;
		$targetTime = $current_time - 24 * 60 * 60;
		$leadData = $this->lead_model->find_where((array("source" => ['$in' => ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17"]], "created_at" => ['$gte' => $targetTime]
		)));
		foreach ($leadData as $value) {
			if (!isset($value["status_call"])) {
				$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($value['_id'])), array("status_call" => "0"));
			}
		}
	}

	public function vbee_missed_call_import_post()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$secret_key = $this->config->item("vbee_sec_key");
		$access_token = $this->config->item("vbee_token");
		$campaign_id = 17978;
		$count = 0;
		$data = [];
		$current_time = $this->createdAt;
		$targetTime = time() - 24 * 60 * 60;
		$start = strtotime(trim(date('Y-m-d')) . ' 8:30:00');
		$end = strtotime(trim(date('Y-m-d')) . ' 17:30:00');
		if ($current_time > $start && $current_time < $end) {
			$missedCallData = $this->recording_model->find_where(array
			("status" => "active" ,
				'$and' => [
					array("toExt" => ['$regex' => '^([0-9][0-9][0-9])$']),
					array("toExt" => ['$ne'=> '299'])
				],
				"direction" => "inbound",
				"missed_call_vbee" => ['$exists' => false],
				"created_at" => ['$gte' => (string)$targetTime],
			 ));
		}else{
			$missedCallData = [];
		}
		if (!empty($missedCallData)) {
			foreach ($missedCallData as $value) {
				if (($value['status'] == "active") && ($value['direction'] == "inbound") ){
					$data[$count]["phone_number"] = $value["fromNumber"];
					$count++;
				}
				$this->recording_model->update(array("_id" => $value['_id']),array("missed_call_vbee" => "1"));
			}
		}
		$data = json_encode($data);
		$response = $this->vbee_missed_call_import_1($data, $campaign_id, $access_token);
		$response = json_decode($response);
		if (!empty($response->results) && $response->status == 1) {
			foreach ($response->results as $item) {
				if (!empty($item->phone_number)) {
					$lead = $this->recording_model->find_one_check_phone_vbee($item->phone_number);
					if (!empty($lead) && empty($lead[0]['call_id'])) {
					$this->recording_model->update(array("_id" => $lead[0]['_id']), array('call_id' => $item->call_id,"missed_call_vbee" => "1"));
					}
				}
			}
		}
	}

	private function vbee_missed_call_import_1($data, $campaign_id, $access_token)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://aicallcenter.vn/api/campaigns/$campaign_id/import?access_token=$access_token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "{\n    \"contacts\":  $data  \n}\t",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	public function webhook_vbee_missed_call_post(){
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$current_time = $this->createdAt;

		$dataDB['request'] = json_decode($this->input->raw_input_stream);
		$check_status_lead = $this->recording_model->findOne(["call_id" => (int)$dataDB['request']->data->call_id]);


		if (!empty($dataDB['request'])) {
			$check_phone_number = $dataDB['request']->data->callee_id;
			$check_phone = (substr($dataDB['request']->data->key_press, 0, 1));
			$vbeeState = (int)$dataDB['request']->data->state;

			if (!empty($check_status_lead)
				&& ($vbeeState == 20 || $vbeeState == 50 || $vbeeState == 60 || $vbeeState == 40)
			){

				if (($vbeeState == 40) && ($check_phone == 3)) {
					$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])),
						array("missed_call" => "1",
							'status_vbee' => $vbeeState,
							"missed_call_vbee" => "1"));
				} elseif (($vbeeState == 40) && ($check_phone == 2)) {
					if ($check_status_lead['status'] == "active") {
						$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "missed_call_vbee" => "1"));
					}
					$result = $this->recording_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])));
					$data['phone'] = $result['fromNumber'];
					$response = $this->ndt_import($data);
					$response = json_decode($response);
				} elseif (($vbeeState == 40) && ($check_phone == 1)) {
					if ($missed_call_lead = $this->lead_model->find_where_1(
						array(
							"source" => ['$in' => ["1","2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17"]],
							"phone_number" => $check_phone_number
						))
					) {
						$this->lead_model->update(array("_id" => new \MongoDB\BSON\ObjectId($missed_call_lead[0]['_id'])), array
						('status_vbee' => $vbeeState,
						 "priority" => "1",
						 "missed_call_vbee" => "1",
						 "call_id" => $check_status_lead['call_id']));

						 $this->recording_model->update(["call_id" =>  (int)$check_status_lead['call_id']], array
						('status_vbee' => $vbeeState,
						 "missed_call_vbee" => "1",
						 ));
					} else {
						$this->lead_model->insert(
							["phone_number" => $check_phone_number,
								"call_id" => $check_status_lead['call_id'],
								'priority' => "1", 'source' => "3",
								"status_sale" => "1", "status" => "1",
								"created_at" => $current_time,
								"updated_at" => $current_time,
								'status_vbee' => $vbeeState,
								"missed_call_vbee" => "1"]);
						$this->recording_model->update(["call_id" =>  (int)$check_status_lead['call_id']], array
						('status_vbee' => $vbeeState,
						 "missed_call_vbee" => "1",
						 ));
					}
				} elseif ((($vbeeState == 40) && ($check_phone != 3 || $check_phone != 1 || $check_phone != 2))) {
					$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array("missed_call" => "2", "missed_call_vbee" => "1"));
				}

				if (($vbeeState == 50) || ($vbeeState == 60)) {
					if ($check_status_lead['status'] == "active" && empty($check_status_lead['call_vbee']))   {
						$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "call_vbee" => "1","missed_call_vbee" => "1"));
					}
					if (($check_status_lead['call_vbee'] == "1") && ($check_status_lead['status'] == "active")) {
						$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array("call_vbee" => "2"));
					}
					if (($check_status_lead['call_vbee'] == "2") && ($check_status_lead['status'] == "active")) {
						$this->recording_model->update(array("_id" => new \MongoDB\BSON\ObjectId($check_status_lead['_id'])), array('status_vbee' => $vbeeState, "call_vbee" => "3", "missed_call" => "3"));
					}
				}

				$this->log_vbee_missed_call_model->insert($dataDB);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'data' => $response,
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
			}
		}
	}

	public function get_missed_call_post()
	{
		$result = $this->recording_model->get_missed_call();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

//đẩy sang  invetor
	private function ndt_import($data)
	{
		$service = $this->baseURL . '/missed_call';
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $service,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
		));
		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	public function import_lead_topup_post(){

		$data = $this->input->post();

		$this->list_topup_model->insert($data);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Import topup success",
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


}

?>

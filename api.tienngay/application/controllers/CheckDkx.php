<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class CheckDkx extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('mic_tnds_model');
		$this->load->model('log_mic_tnds_model');
		$this->load->model("user_model");
		$this->load->model("role_model");
		$this->load->model("group_role_model");
		$this->load->model("store_model");
		$this->load->model("transaction_model");
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

	public function check_info_dkx_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$url1 = !empty($data['img1']) ? $data['img1'] : '';
		$url2 = !empty($data['img2']) ? $data['img2'] : '';

		$TRANDATA_USER = '8f55bd15641a439ea3256484f97f19ad';
		$TRANDATA_PAS = 'f61eb72bd849944410ad5ab9d78ca41fdfcbaff42e81009b4a4337ce91b02e46';
		$url_api = 'https://cloud.computervision.com.vn/api/v2/ocr/vehicle_registrations?img1=' . $url1 . '&img2=' . $url2 . '&format_type=url&get_thumb=false';

		$ch = curl_init($url_api);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $TRANDATA_USER . ":" . $TRANDATA_PAS);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,60);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$respone_data = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($respone_data);
		$data = [];
		foreach ($result1->data as $item) {
			if ($item->type == 'vehicle_registration_front') {
				$data['mat_truoc']['so_dang_ky'] = $item->info->id;
				$data['mat_truoc']['phan_tram'] = round(($item->info->id_confidence * 100), 3) . ' %';
			}
			if ($item->type == 'vehicle_registration_back') {
				$data['mat_sau']['nhan_hieu'] = $item->info->brand;
				$data['mat_sau']['model'] = $item->info->model;
				$data['mat_sau']['bien_so_xe'] = $item->info->plate;
				$data['mat_sau']['so_khung'] = $item->info->chassis;
				$data['mat_sau']['so_may'] = $item->info->engine;
				$data['mat_sau']['ten_chu_xe'] = $item->info->name;
				$data['mat_sau']['dia_chi'] = $item->info->address;
				$data['mat_sau']['ngay_cap'] = $item->info->last_issue_date;
				$data['mat_sau']['phan_tram'] = round((($item->info->brand_confidence + $item->info->address_confidence + $item->info->chassis_confidence + $item->info->engine_confidence + $item->info->last_issue_date_confidence + $item->info->model_confidence + $item->info->name_confidence + $item->info->plate_confidence) / 8) * 100, 3) . ' %';
			}
		}
		if ($result1->errorCode == "0") {
			$response = [
				'status' => 200,
				'data' => $data,
				'message' => $result1->errorMessage
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = [
				'status' => 401,
				'message' => $result1->errorMessage
			];
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

}

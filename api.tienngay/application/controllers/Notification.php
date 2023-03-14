<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
// require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
// require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;
// use ElephantIO\Client;
// use ElephantIO\Engine\SocketIO\Version2X;

class Notification extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();

		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('time_model');
		$this->load->model('thongbao_model');
		$this->load->model('log_model');
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


	public function process_create_notification_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$this->dataPost['notification_type'] = $this->security->xss_clean($this->dataPost['notification_type']);
		$this->dataPost['priority_level'] = $this->security->xss_clean($this->dataPost['priority_level']);
		$this->dataPost['title'] = $this->security->xss_clean($this->dataPost['title']);
		$this->dataPost['content'] = !empty($_POST['content']) ?  $_POST['content'] : "" ;
		$this->dataPost['start_date'] = strtotime($this->security->xss_clean($this->dataPost['start_date']));
		$this->dataPost['end_date'] = strtotime($this->security->xss_clean($this->dataPost['end_date']).' 23:59:59');
		$this->dataPost['selectize_role_value'] = $this->security->xss_clean($this->dataPost['selectize_role_value']);
		$this->dataPost['selectize_area_value'] = $this->security->xss_clean($this->dataPost['selectize_area_value']);

		$this->dataPost['image_accurecy'] = $this->security->xss_clean($this->dataPost['image_accurecy']);

		//Thông tin khách hàng
		if (empty($this->dataPost['notification_type'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Loại thông báo không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['priority_level'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mức độ ưu tiên không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['title'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tiêu đề không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['content'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Nội dung không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['start_date'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày bắt đầu không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($this->dataPost['end_date'])){
			if ($this->dataPost['start_date'] > $this->dataPost['end_date']){
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày kết thúc phải lớn hơn ngày bắt đầu"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
		if (!empty($this->dataPost['start_date']) && !empty($this->dataPost['end_date'])){
			if ($this->dataPost['start_date'] == $this->dataPost['end_date']){
				$this->dataPost['end_date'] = "";
			}
		}


		if (empty($this->dataPost['selectize_role_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bộ phận không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($this->dataPost['selectize_role_value'])) {
			foreach ($this->dataPost['selectize_role_value'][0] as $item) {
				if ($item == "Giao dịch viên") {
					if (empty($this->dataPost['selectize_area_value'])) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Vùng miền không thể để trống"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}


		if (empty($this->dataPost['image_accurecy'])) {
			$arrImages = array(
				"identify" => "",
				"household" => "",
				"driver_license" => "",
				"vehicle" => "",
				"expertise" => ""
			);
			$this->dataPost['image_accurecy'] = $arrImages;
		}


		$this->dataPost['created_at'] = $this->createdAt;

		$this->thongbao_model->insert($this->dataPost);

		$insertLog = array(
			"type" => "notification",
			"action" => "create",
			"content" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create new notification success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$notification_type = !empty($this->dataPost['notification_type']) ? $this->dataPost['notification_type'] : "";
		$priority_level = !empty($this->dataPost['priority_level']) ? $this->dataPost['priority_level'] : "";
		$title = !empty($this->dataPost['title']) ? $this->dataPost['title'] : "";

		$per_page = !empty($this->dataPost['per_page']) ? $this->dataPost['per_page'] : 50;
		$uriSegment = !empty($this->dataPost['uriSegment']) ? $this->dataPost['uriSegment'] : 0;

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		if (!empty($notification_type)) {
			$condition['notification_type'] = $notification_type;
		}
		if (!empty($priority_level)) {
			$condition['priority_level'] = $priority_level;
		}
		if (!empty($title)) {
			$condition['title'] = $title;
		}

		$contract = $this->thongbao_model->getDataByRole($condition, $per_page, $uriSegment);



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_all_header_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();

		$contract = $this->thongbao_model->getDataByRole_header();
		 if (empty($contract)) return;
		$time = strtotime(trim(date('Y-m-d ')) . ' 23:59:59');

		$all_header = [];

		foreach ($contract as $key => $item){
			if (isset($item['end_date']) && !empty($item['end_date'])){
				if ($time >= $item['start_date'] && $time <= $item['end_date']){
					array_push($all_header, $item);
				}
			} elseif (isset($item['start_date']) && empty($item['start_date']) && $time >= $item['start_date']){
				array_push($all_header, $item);
			}
		}

		

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $all_header
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_count_all_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost = $this->input->post();
		$condition = [];
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$notification_type = !empty($this->dataPost['notification_type']) ? $this->dataPost['notification_type'] : "";
		$priority_level = !empty($this->dataPost['priority_level']) ? $this->dataPost['priority_level'] : "";
		$title = !empty($this->dataPost['title']) ? $this->dataPost['title'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59')
			);
		}

		if (!empty($notification_type)) {
			$condition['notification_type'] = $notification_type;
		}
		if (!empty($priority_level)) {
			$condition['priority_level'] = $priority_level;
		}
		if (!empty($title)) {
			$condition['title'] = $title;
		}

		$contract = $this->thongbao_model->getCountByRole($condition);



		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_one_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$thongbao = $this->thongbao_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data["id"])));
		if (empty($thongbao)) return;

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $thongbao
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function process_update_notification_post()
	{
		//	$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$this->dataPost['notification_type'] = $this->security->xss_clean($this->dataPost['notification_type']);
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['priority_level'] = $this->security->xss_clean($this->dataPost['priority_level']);
		$this->dataPost['title'] = $this->security->xss_clean($this->dataPost['title']);
		$this->dataPost['content'] = !empty($_POST['content']) ?  $_POST['content'] : "" ;
		$this->dataPost['start_date'] = strtotime($this->security->xss_clean($this->dataPost['start_date']));
		$this->dataPost['end_date'] = strtotime($this->security->xss_clean($this->dataPost['end_date']).' 23:59:59');
		$this->dataPost['selectize_role_value'] = $this->security->xss_clean($this->dataPost['selectize_role_value']);
		$this->dataPost['selectize_area_value'] = $this->security->xss_clean($this->dataPost['selectize_area_value']);

		$this->dataPost['image_accurecy'] = $this->security->xss_clean($this->dataPost['image_accurecy']);


		//Thông tin khách hàng
		if (empty($this->dataPost['notification_type'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Loại thông báo không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['priority_level'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mức độ ưu tiên không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['title'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Tiêu đề không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['content'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Nội dung không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (empty($this->dataPost['start_date'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Ngày bắt đầu không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($this->dataPost['end_date'])){
			if ($this->dataPost['start_date'] > $this->dataPost['end_date']){
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Ngày kết thúc phải lớn hơn ngày bắt đầu"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}

		if (empty($this->dataPost['selectize_role_value'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Bộ phận không thể để trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if (!empty($this->dataPost['selectize_role_value'])) {
			foreach ($this->dataPost['selectize_role_value'][0] as $item) {
				if ($item == "Giao dịch viên") {
					if (empty($this->dataPost['selectize_area_value'])) {
						$response = array(
							'status' => REST_Controller::HTTP_UNAUTHORIZED,
							'message' => "Vùng miền không thể để trống"
						);
						$this->set_response($response, REST_Controller::HTTP_OK);
						return;
					}
				}
			}
		}

		if (empty($this->dataPost['image_accurecy'])) {
			$arrImages = array(
				"identify" => "",
				"household" => "",
				"driver_license" => "",
				"vehicle" => "",
				"expertise" => ""
			);
			$this->dataPost['image_accurecy'] = $arrImages;
		}

		$inforNotification = $this->thongbao_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$this->dataPost['updated_at'] = $this->createdAt;
		unset($this->dataPost['id']);

		$this->thongbao_model->update(array("_id" => $inforNotification['_id']), $this->dataPost);

		$insertLog = array(
			"type" => "notification",
			"action" => "update",
			"content" => $this->dataPost,
			"created_at" => $this->createdAt,
			"created_by" => $this->uemail
		);
		$this->log_model->insert($insertLog);


		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update notification success"

		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}


}

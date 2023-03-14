<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Blacklist extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('log_model');
		$this->load->model('blacklist_model');
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

	public function add_blacklist_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$url_image = !empty($data['image']) ? $data['image'] : '';
		$name = !empty($data['name']) ? $data['name'] : '';
		$phone = !empty($data['phone']) ? $data['phone'] : '';
		$identify = !empty($data['identify']) ? $data['identify'] : '';
		$note = !empty($data['note']) ? $data['note'] : '';
		$id_img_cvs = !empty($data['id_img_cvs']) ? $data['id_img_cvs'] : '';
		if (!empty($url_image) && !empty($name) && !empty($phone) && !empty($identify) && !empty($note) && !empty($id_img_cvs)) {
			$data = [
				'image' => $url_image,
				'name' => $name,
				'phone' => $phone,
				'identify' => $identify,
				'note' => $note,
				'id_img_cvs' => (int)$id_img_cvs,
				'status' => 'active',
				'created_by' => $this->uemail,
				'created_at' => $this->createdAt
			];
			$this->blacklist_model->insert($data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Add blacklist success",
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function get_blacklist_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$result = $this->blacklist_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function getBlacklistById_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$result = $this->blacklist_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($data["id"])]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function deleteBlacklist_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$result = $this->blacklist_model->delete(['_id' => new \MongoDB\BSON\ObjectId($data["id"])]);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function updateBlacklist_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$url_image = !empty($data['image']) ? $data['image'] : '';
		$name = !empty($data['name']) ? $data['name'] : '';
		$phone = !empty($data['phone']) ? $data['phone'] : '';
		$identify = !empty($data['identify']) ? $data['identify'] : '';
		$note = !empty($data['note']) ? $data['note'] : '';
		$id_img_cvs = !empty($data['id_img_cvs']) ? $data['id_img_cvs'] : '';
		if (!empty($url_image) && !empty($name) && !empty($phone) && !empty($identify) && !empty($note) && !empty($id_img_cvs)) {
			$data = [
				'image' => $url_image,
				'name' => $name,
				'phone' => $phone,
				'identify' => $identify,
				'note' => $note,
				'id_img_cvs' => (int)$id_img_cvs,
				'status' => 'active',
				'created_by' => $this->uemail,
				'created_at' => $this->createdAt
			];
			$this->blacklist_model->insert($data);
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Add blacklist success",
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function update_blacklist_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id_img_cvs = !empty($data['id']) ? $data['id'] : '';
		$status = !empty($data['status']) ? $data['status'] : '';
		if (!empty($id_img_cvs) && !empty($status)) {
			$data_arr = array('image_id' => (int)$id_img_cvs, 'metadata' => array('status' => $status));
			$url = $this->config->item("API_CVS") . 'face_search/edit_metadata';
			$result1 = $this->push_api_cvs($url, json_encode($data_arr, JSON_NUMERIC_CHECK));
			if($result1->status_code == 0) {
				$data = [
					'status' => $status,
					'updated_at' => $this->createdAt,
					'updated_by' => $this->uemail
				];
				$result = $this->blacklist_model->update(array('id_img_cvs' => (int)$id_img_cvs), $data);
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'Update blacklist success'
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			} else {
				$response = array(
					'status' => REST_Controller::HTTP_OK,
					'message' => 'Update blacklist fails'
				);

				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
	}

	private function push_api_cvs($url = '', $data_post = [])
	{
		$username = $this->config->item("CVS_API_KEY");
		$password = $this->config->item("CVS_API_SECRET");
		$service = $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $service);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}
}
?>

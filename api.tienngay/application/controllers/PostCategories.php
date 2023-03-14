<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class PostCategories extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('post_categories_model');
		$this->load->model('log_model');
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

	public function get_all_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$categories = $this->post_categories_model->find_where('status', ['active', 'deactive']);
		if (!empty($categories)) {
			foreach ($categories as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $categories
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_category_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if (empty($id)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id danh mục không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$category = $this->post_categories_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $category
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function create_category_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();

		$data['category_name_banner'] = $this->security->xss_clean($data['category_name_banner']);
		$data['category_name_post'] = $this->security->xss_clean($data['category_name_post']);
		$data['type_category'] = $this->security->xss_clean($data['type_category']);
		$data['status'] = $this->security->xss_clean($data['status']);
		$page = 9; // page  trong bài viết banner trước khi có danh mục
		$type_new = 10; // type_new trong bài viết banner trước khi có danh mục
		$check_page = $this->post_categories_model->find_where_type_category(['type_category' => '1']);
		$check_type_new = $this->post_categories_model->find_where_type_category(['type_category' => '2']);

		if ($data['type_category'] == 1) {
			if (empty($check_page)) {
				$page = 10;
				$param_insert = [
					'category_name_banner' => $data['category_name_banner'],
					'page' => $page,
				];
			} else {
				$param_insert = [
					'category_name_banner' => $data['category_name_banner'],
					'page' => $check_page[0]['page'] + 1,
				];
			}

		} else {
			if (empty($check_type_new)) {
				$type_new = 11;
				$param_insert = [
					'category_name_post' => $data['category_name_post'],
					'type_new' => $type_new,
				];
			} else {
				$param_insert = [
					'category_name_post' => $data['category_name_post'],
					'type_new' => $check_type_new[0]['type_new'] + 1,
				];
			}
		}

		if (!empty($check_page)) {
			foreach ($check_page as $check_p) {
				if ($check_p['page'] == $param_insert['page']) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Danh mục đã tồn tại!"
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return $response;
				}
			}
		}
		if (!empty($check_type_new)) {
			foreach ($check_type_new as $check_type) {
				if ($check_type['type_new'] == $param_insert['type_new']) {
					$response = [
						'status' => REST_Controller::HTTP_UNAUTHORIZED,
						'message' => "Danh mục đã tồn tại!"
					];
					$this->set_response($response, REST_Controller::HTTP_OK);
					return $response;
				}
			}
		}
		$param_insert['type_category'] = $data['type_category'];
		$param_insert['status'] = $data['status'];
		$param_insert['created_at'] = $this->createdAt;
		$param_insert['created_by'] = $this->uemail;

		$this->post_categories_model->insert($param_insert);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Tạo danh mục thanh công!",
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_category_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->post_categories_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if ($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại danh mục nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->log_category($data);
		unset($data['id']);

		//$data period
		if (isset($data['type_category'])) {
			$data['type'] = $data['type_category'];
		}

		$this->post_categories_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhập danh mục thành công!",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function log_category($data)
	{
		$id = !empty($data['id']) ? $data['id'] : "";
		$category = $this->post_categories_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$category['id'] = (string)$category['_id'];
		unset($category['_id']);
		$dataInsert = array(
			"new_data" => $data,
			"old_data" => $category,
			"type" => 'categories'

		);
		$this->log_model->insert($dataInsert);
	}

	public function get_banner_categories_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$banner_categories = $this->post_categories_model->find_where_type_category(['type_category' => '1', 'status' => 'active']);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $banner_categories
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_post_categories_post()
	{
			$flag = notify_token($this->flag_login);
			if ($flag == false) return;
		$banner_categories = $this->post_categories_model->find_where_type_category(['type_category' => '2', 'status' => 'active']);
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $banner_categories
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}

?>

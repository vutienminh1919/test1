<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Handbook extends REST_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('handbook_model');
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
					'_id'=>new \MongoDB\BSON\ObjectId($token->id),
					'email'=>$token->email,
					"status" => "active",
					// "is_superadmin" => 1
				);
				//Web
				if($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1){
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					// $this->ulang = $this->info['lang'];
					$this->uemail = $this->info['email'];
				}
			}
		}
	}

	public function find_where_not_in_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$newss = $this->handbook_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $newss
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function find_where_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$newss = $this->handbook_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
		if (!empty($newss)) {
			foreach ($newss as $sto) {
				$sto['news_id'] = (string)$sto['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $newss
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function get_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$news = $this->handbook_model->find_where('status', ['active','deactive']);
		if (!empty($news)) {
			foreach ($news as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function get_all_home_post(){
		$data = $this->input->post();
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 8;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$condition = array();

		$news = $this->handbook_model->find_where_pt($condition, $per_page, $uriSegment) ;
		$condition['count']="true";
		$total = $this->handbook_model->find_where_pt($condition, $limit = 8, $offset = 0) ;
		if (!empty($news)) {
			foreach ($news as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news,
			'count'=>$total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function get_handbook_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if(empty($id)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id news already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$news = $this->handbook_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function get_description_handbook_post(){

		$data = $this->input->post();
		$data['link'] = $this->security->xss_clean($data['link']);
		$condition = array();
		$condition['link'] = $data['link'];
		if(empty($data['link'])){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id news already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$news = $this->handbook_model->find_handbook_by_link($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function create_handbook_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$cfile = new CURLFile($data['image']["tmp_name"],$data['image']["type"],$data['image']["name"]);

		$push_upload = $this->pushUpload($cfile);
		if(is_object($push_upload))
		{
			if($push_upload->code == 200){
				$data['image']=$push_upload->path;
			}else{
				$data['image']="";
			}
		}else{
			$data['image']="";
		}
		if(isset($data['type_new']))
		{
			$data['type']=$data['type_new'];
		}
		if(isset($data['period']))
		{
			$data['period']=strtotime($data['period'].' 00:00:00');
		}
		$this->handbook_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create news success",
			'data'=>$push_upload
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_handbook_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->handbook_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại bài viết nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->log_handbook($data);
		unset($data['id']);
		if(!empty($data['image']["tmp_name"]))
		{
			$cfile = new CURLFile($data['image']["tmp_name"],$data['image']["type"],$data['image']["name"]);


			$push_upload = $this->pushUpload($cfile);
			if(is_object($push_upload))
			{
				if($push_upload->code == 200){
					$data['image']=$push_upload->path;
				}else{
					unset($data['image']);
				}
			}else{
				unset($data['image']);
			}
		}else{ unset($data['image']); }
		//$data period
		if(isset($data['type_new']))
		{
			$data['type'] = $data['type_new'];
		}
		if(isset($data['period']))
		{
			$data['period']=strtotime($data['period'].' 00:00:00');
		}
		
		$this->handbook_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Cập nhập bài viết thành công!",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	private function pushUpload($cfile){
		$serviceUpload = $this->config->item("url_service_upload");
		// $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
		$post = array('avatar'=> $cfile );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$serviceUpload);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec ($ch);
		curl_close ($ch);
		$result1 = json_decode($result);
		return $result1;
	}
	public function log_handbook($data){
		$id = !empty($data['id']) ? $data['id'] : "";
		$news = $this->handbook_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$news['id'] = (string)$news['_id'];
		unset($news['_id']);
		$dataInser = array(
			"new_data" => $data,
			"old_data" => $news,
			"type" => 'news'

		);
		$this->log_model->insert($dataInser);
	}

	public function search_web_post()
	{
		$data = $this->input->post();
		$data['title_vi'] = $this->security->xss_clean($data['title_vi']);
		$data['province'] = $this->security->xss_clean($data['province']);
		$condition = array();
		$condition['status'] = "active";
		$condition['type'] = "2";
		if (!empty($data['title_vi'])) $condition['title_vi'] = $data['title_vi'];
		if (!empty($data['province'])) $condition['province'] = $data['province'];
		$news = $this->handbook_model->find_where_select($condition, array("title_vi", "province_text", "period", "limit"));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_position_by_province_post()
	{
		$data = $this->input->post();
		$province = !empty($data['id']) ? $data['id'] : "";
		$position = $this->handbook_model->find_where_title_vi(array("province" => $province));
		$response = [
			'status' => REST_Controller::HTTP_OK,
			'data' => $position
		];
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_handbook_by_position_post()
	{
		$data = $this->input->post();
		$title_vi = !empty($data['title_vi']) ? $data['title_vi'] : "";
		$province = !empty($data['province']) ? $data['province'] : "";
		$condition = array(
			'province' => $province,
			'title_vi' => $title_vi
		);
		$news = $this->handbook_model->get_news_by_position_model($condition);
		if (!empty($news)) {
			foreach ($news as $n) {
				$n['id'] = (string)$n['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_all_home_insurance_post(){
		$data = $this->input->post();
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$condition = array();
		$news = $this->handbook_model->get_post_insurance($condition, $per_page, $uriSegment) ;
		$condition['count']="true";
		$total = $this->handbook_model->get_post_insurance($condition, $per_page, $offset = 0) ;
		if (!empty($news)) {
			foreach ($news as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $news,
			'count'=>$total
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function get_all_camnangctv_post(){

		$get_all = $this->handbook_model->camnangctv();

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => !empty($get_all) ? $get_all : [],
		);
		$this->set_response($response, REST_Controller::HTTP_OK);

	}
	

}
?>

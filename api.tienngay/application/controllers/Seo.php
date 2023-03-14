
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Seo extends REST_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('seo_model');
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
		$faqs = $this->faq_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $faqs
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function find_where_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		unset($data['type']);
		$faqs = $this->faq_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
		if (!empty($faqs)) {
			foreach ($faqs as $sto) {
				$sto['faq_id'] = (string)$sto['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $faqs
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function get_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$seo = $this->seo_model->find_where('status', ['active','deactive']);
		if (!empty($seo)) {
			foreach ($seo as $r) {
				$r['id'] = (string)$r['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $seo
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_seo_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		if(empty($id)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id seo already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$seo = $this->seo_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $seo
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function get_description_faq_post(){

		$data = $this->input->post();
		$link = !empty($data['link']) ? $data['link'] : "";
		if(empty($link)){
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Id faq already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$faq = $this->faq_model->findOne(array("link" => $link));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $faq
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function create_seo_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$this->seo_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create seo success",
			'data' => $data
		);

		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function update_seo_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->seo_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại trang seo nào cần cập nhật"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->log_seo($data);
		unset($data['id']);

		$this->seo_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update seo success",
			'data' => $data
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function log_seo($data){
		$id = !empty($data['id']) ? $data['id'] : "";
		$seo = $this->seo_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$seo['id'] = (string)$seo['_id'];
		unset($seo['_id']);
		$dataInser = array(
			"new_data" => $data,
			"old_data" => $seo,
			"type" => 'seo'
		);
		$this->log_model->insert($dataInser);
	}

	private function initNumberseoCode()
	{
		$maxNumber = $this->seo_model->getMaxNumberseo();
		$maxNumberseo = !empty($maxNumber[0]['code_seo']) ? (int)$maxNumber[0]['code_seo'] + 1 : 1;
		return $maxNumberseo;
	}

	public function get_all_home_post(){

		$seoData = $this->seo_model->find_where('status', ['active']);
		if (!empty($seoData)) {
			foreach ($seoData as $seo) {
				$seo['id'] = (string)$seo['_id'];
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $seoData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
}
?>

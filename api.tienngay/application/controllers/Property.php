<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Property extends REST_Controller{
    public function __construct(){
	parent::__construct();
	$this->load->model('main_property_model');
	$this->load->model('property_log_model');
	$this->load->model('depreciation_property_model');
	$this->load->model('configuration_formality_model');
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
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

	public function get_depreciation_by_property_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$type_loan = !empty($data['type_loan']) ? $data['type_loan'] : "";
		$code_type_property = !empty($data['code_type_property']) ? $data['code_type_property'] : "";
		$configuration_formality = $this->configuration_formality_model->find_where(array("code" => $type_loan));
		$percent=0;
		if(!empty($configuration_formality))
			$percent = $configuration_formality[0]['percent'][$code_type_property];
		$propertyData = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$price_property = $propertyData['price'] - ($propertyData['price']* (int)$propertyData['giam_tru_tieu_chuan']/100);
		if(!empty($propertyData['depreciations'])){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $propertyData['depreciations'],
				'price_property' => $price_property,
				"percent" => $percent,
				'price_goc'=>$propertyData['price']

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(),
				'price_property' => $price_property,
				"percent" => $percent,
				'price_goc'=>$propertyData['price']

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function get_percent_formality_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$type_loan = !empty($data['type_loan']) ? $data['type_loan'] : "";
		$code_type_property = !empty($data['code_type_property']) ? $data['code_type_property'] : "";
		$configuration_formality = $this->configuration_formality_model->find_where(array("code" => $type_loan));
		$percent = $configuration_formality[0]['percent'][$code_type_property];
		if(!empty($percent)){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				"percent" => $percent
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(),
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function get_depreciation_by_property_app_post(){
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$type_loan = !empty($data['type_loan']) ? $data['type_loan'] : "";
		$code_type_property = !empty($data['code_type_property']) ? $data['code_type_property'] : "";
		$configuration_formality = $this->configuration_formality_model->find_where(array("code" => $type_loan));
		$percent = $configuration_formality[0]['percent'][$code_type_property];
		$propertyData = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		if(!empty($propertyData['depreciations'])){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $propertyData['depreciations'],
				'price_property' => $propertyData['price'],
				"percent" => $percent
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}else{
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => array(),
				'price_property' => $propertyData['price'],
				"percent" => $percent

			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}
	public function get_property_main_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$main_property = $this->main_property_model->find_where(array("parent_id" => "","status" => "active"));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $main_property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_property_main_app_post(){
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$main_property = $this->main_property_model->find_where(array("parent_id" => "","status" => "active"));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $main_property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_property_by_main_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$parent_id = !empty($data['parent_id']) ? $data['parent_id'] : "";
		$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($parent_id)));
		$dataPropertyValuation = $this->getPropertyValuation($parent_id,"");
		$properties = !empty($main_property['properties']) ? $main_property['properties'] : array();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataPropertyValuation,
			'properties' => $properties
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_property_by_main_homepage_post(){

		$data = $this->input->post();
		$parent_id = !empty($data['parent_id']) ? $data['parent_id'] : "";
		$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($parent_id)));
		$dataPropertyValuation = $this->getPropertyValuation($parent_id,"");
		$properties = !empty($main_property['properties']) ? $main_property['properties'] : array();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataPropertyValuation,
			'properties' => $properties
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}
	private $name_property = array();
	function getPropertyValuation($parent_id = "",$parent_name = "") {
		$main_property = $this->main_property_model->find_where(array("parent_id" => $parent_id,"status"=>"active"));
		$data = array();

		foreach($main_property  as $key => $item){
			if($parent_id == (string)$item['parent_id']){
				if(!empty($item['price'])){
					// $data['name'] = $parent_name." ".$item['name'];
					$data['name'] = $item['str_name'];
					$data['price'] = $item['price'];
					$data['id'] = (string)$item['_id'];
					array_push($this->name_property,$data);

				}

			}
			// Tiếp tục đệ quy để tìm con của item đang lặp
			$id  = (string)$item['_id'];
			$this->getPropertyValuation($id,$item['name']);

		}
		return $this->name_property;
	}
	public function update_property_main_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$count = $this->main_property_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
		if($count != 1) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại tài sản nào cần xóa"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$this->log_property($data);
		unset($data['id']);
		//$data
		$this->main_property_model->update(
			array("_id" => new MongoDB\BSON\ObjectId($id)),
			$data
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Update menu success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_property_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $main_property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_property_homepage_post(){

		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $main_property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_property_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$main_property = $this->main_property_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $main_property
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function create_property_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		//Count name
		$count = $this->main_property_model->count(array("name" => $data['name'], "status"=>"active"));
		if($count > 0) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Name already exists"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$parent_id = !empty($data['parent_id']) ? $data['parent_id'] : "";
		$property_name = !empty($data['name']) ? $data['name'] : "";
		$str_name = "";
		if(!empty($parent_id)){
			$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($parent_id)));
			$str_name = $main_property['str_name']." ".$property_name;
		}else{
			$str_name = $property_name;
		}
		$data['str_name'] = $str_name;
		unset($data['type_login']);
		$this->main_property_model->insert($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Create menu success"
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_depreciation_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$property_id = $data['parent_property_id'];
		$main_property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($property_id)));
		$paren_property_id = $main_property['parent_id'];

		$depreciation = $this->depreciation_property_model->find_where(array("status" => "active",'property_id'=>$paren_property_id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $depreciation,
			'property_id' =>  $property_id,
			'paren_property_id' =>  $paren_property_id,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function log_property($data){
		$id = !empty($data['id']) ? $data['id'] : "";
		$property = $this->main_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		$property['id'] = (string)$property['_id'];
		unset($property['_id']);
		$dataInser = array(
			"new_data" => $data,
			"old_data" => $property
		);
		$this->property_log_model->insert($dataInser);
	}
	public function find_property_by_name_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$name = !empty($data['name']) ? $data['name'] : "";
		$propertyData = $this->main_property_model->findPropertyByName($name);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $propertyData,
			'name' => $name
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;

	}

	public function get_none_depreciations_properties_post()
	{
		$flag = notify_token($this->flag_login);
		$data = $this->input->post();
		if ($flag == false) return;
		$data = array("status" => "active");
		$propertiesData = $this->main_property_model->getPropertiesNoneDepreciations($data);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $propertiesData
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}
?>

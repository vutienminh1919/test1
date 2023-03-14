<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Province extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('province_model');
        $this->load->model('district_model');
        $this->load->model('ward_model');
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

    public function get_province_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $province = $this->province_model->find();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $province
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function get_province_app_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $province = $this->province_model->find();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $province
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
	public function get_province_noheader_post(){

		$province = $this->province_model->find();
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $province
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

    public function get_province_by_code_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
		$data = $this->input->post();
		$code = !empty($data['code']) ? $data['code'] : "";
		if (empty($code)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => array(),
				'message' => 'Code province empty',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
        $province = $this->province_model->findOne(array('code' => $code));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $province
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_district_by_province_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $parent_id = !empty($data['id']) ? $data['id'] : "";
        $district = $this->district_model->find_where(array("parent_code" => $parent_id));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $district
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
   
	public function get_district_by_province_noheader_post(){
		$data = $this->input->post();
		$parent_id = !empty($data['id']) ? $data['id'] : "";
		$district = $this->district_model->find_where(array("parent_code" => $parent_id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $district
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

    public function get_ward_by_district_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $district_id = !empty($data['id']) ? $data['id'] : "";
        $ward = $this->ward_model->find_where(array("parent_code" => $district_id));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $ward
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

	public function get_ward_by_district_noheader_post(){
		$data = $this->input->post();
		$district_id = !empty($data['id']) ? $data['id'] : "";
		$ward = $this->ward_model->find_where(array("parent_code" => $district_id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $ward
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function searchCodeprovince_post(){
		$data = $this->input->post();
		$province_name= !empty($data['name']) ? $data['name'] : "";
		$province = $this->province_model->find_where(array("slug" => $province_name));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $province
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function searchCodedistrict_post(){
		$data = $this->input->post();
		$district_name= !empty($data['name']) ? $data['name'] : "";
		$parent_code = !empty($data['parent_code']) ? $data['parent_code'] : "";
		$district = $this->district_model->find_where(array("slug" => $district_name, "parent_code"=>$parent_code));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $district
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function searchCodeward_post(){
		$data = $this->input->post();
		$ward_name= !empty($data['name']) ? $data['name'] : "";
		$parent_code= !empty($data['parent_code']) ? $data['parent_code'] : "";
		$ward = $this->ward_model->find_where(array("slug" => $ward_name, "parent_code"=>$parent_code));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $ward
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}
?>

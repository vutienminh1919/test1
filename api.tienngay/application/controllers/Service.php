<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';

use Restserver\Libraries\REST_Controller;

class Service extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model("service_vimo_model");
        $this->load->model("device_model");
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->dataPost = $this->input->post();
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

    public function find_where_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $service = $this->service_vimo_model->find_where(array("service_code" => $data['sevice_code']));
        if(!empty($service)){
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $service['0']
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
    }

    public function get_all_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $service = $this->service_vimo_model->findOne();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $service
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_service_post(){
		 $flag = notify_token($this->flag_login);
		 if ($flag == false) return;
		$service = $this->service_vimo_model->find_where_custom('service_code', ['BILL_WATER','BILL_FINANCE','BILL_ELECTRIC']);
			if (!empty($service)) {
				foreach ($service as $s) {
					$s['id'] = (string)$s['_id'];
				}
			}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $service
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_service_name_by_service_code_post()
	{
		$data = $this->input->post();
		$parent_id = !empty($data['service_code']) ? $data['service_code'] : "";
		$service = $this->service_vimo_model->findOne(array("service_code" => $parent_id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $service['publisher']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function save_token_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$device = !empty($data['token']) ? $data['token'] : "";
		$device_user = $this->device_model->findOne(['user_id' => (string)$this->id]);
		if (!empty($device_user)) {
			$this->device_model->update(
				array("_id" => $device_user['_id']),
				array(
					'device_token' => $device,
					"updated_at" => $this->createdAt
				)
			);
		} else {
			$this->device_model->insert([
				'user_id' => (string)$this->id,
				'device_token' => $device,
				'created_at' => $this->createdAt
			]);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}
?>

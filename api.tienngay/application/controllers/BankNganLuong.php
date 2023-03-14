<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/BillingVIMO_lib.php';
use Restserver\Libraries\REST_Controller;

class BankNganLuong extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("bank_nganluong_model");
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->dataPost = $this->input->post();
        $headers = $this->input->request_headers();
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
                if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
                if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
                }
            }
        }
        unset($this->dataPost['type']);
    }
    private $createdAt, $dataPost, $isTriple, $libraries, $flag_login, $id, $uemail, $ulang, $app_login;


    public function get_all_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $account_type = !empty($this->dataPost['account_type']) ? $this->dataPost['account_type'] : "3"; 
        $bank = $this->bank_nganluong_model->find_where_in('account_type', ['23','3','2','1']);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bank
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

	public function get_bank_name_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$bank_id = !empty($data['bank_id']) ? $data['bank_id'] : "";
		if (empty($bank_id)) {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => array(),
				'message' => 'Bank_id province empty',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$bank = $this->bank_nganluong_model->findOne(array('bank_id' => $bank_id));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $bank
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}

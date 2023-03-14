<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
//require_once APPPATH . 'libraries/AccessRight.php';
use Restserver\Libraries\REST_Controller;

class FeeLoan extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("fee_loan_model");
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
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
                    $this->is_superadmin = !empty($this->info['is_superadmin']) ? $this->info['is_superadmin'] : 0;
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $is_superadmin;

    public function get_fee_all_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $dataLoan = $this->fee_loan_model->find_where(array("status" => "active"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $dataLoan
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        //Count name
        $fee = $this->fee_loan_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
        if(empty($fee)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $percent_fee = !empty($data["percent_fee"]) ?  (float)$data["percent_fee"] : 0;
        $amount_fee = !empty($data["amount_fee"]) ?  (float)$data["amount_fee"] : 0;
        $arr = array(
            "updated_by" => $this->uemail,
            "percent" => $percent_fee,
            "amount" => $amount_fee
        );

         //Insert log
         $insertLog = array(
            "type" => "feeLoan",
            "action" => "upload",
            "old" => $fee,
            "new" => $arr,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);
        $this->fee_loan_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($data['id'])),
            $arr
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update success",
            "arr" => $arr,
            'id' => $data['id']
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function count_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $count = $this->menu_model->count($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            "count"=> $count
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
  
}

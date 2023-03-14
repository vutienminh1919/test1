<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
//require_once APPPATH . 'libraries/AccessRight.php';
use Restserver\Libraries\REST_Controller;

class FeeLoanNew extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("fee_loan_model");
        $this->load->model("column_fee_loan_model");
        $this->load->model('log_model');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $headers = $this->input->request_headers();
        $this->dataPost = $this->input->post();
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
                    $this->is_superadmin = !empty($this->info['is_superadmin']) ? $this->info['is_superadmin'] : 0;
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $is_superadmin, $dataPost;
    
    public function get_column_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $dataLoan = $this->column_fee_loan_model->findOne(array("status" => "active"));
        $response = array(
            'code' => REST_Controller::HTTP_OK,
            'data' => $dataLoan
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        if(empty($this->dataPost['title']) || empty($this->dataPost['to']) || empty($this->dataPost['from']) || empty($this->dataPost['infor'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Dữ liệu không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['from'] = strtotime(trim($this->dataPost['from']).' 00:00:00');
         $this->dataPost['to'] = strtotime(trim($this->dataPost['to']).' 00:00:00');
        //$this->dataPost['infor'] = json_encode($this->dataPost['infor']);
        
        foreach($this->dataPost['infor'] as $k1=>$v1) {
            foreach($v1 as $k2=>$v2) {
                foreach($v2 as $k3=>$v3) {
                    if($k3 == 'percent_interest_customer') {
                        $this->dataPost['infor'][$k1][$k2][$k3] = 1.5;
                    }
                }
            }
        }
        
        $insert = array(
            "status" => "active",
            "title" => $this->dataPost['title'],
            "from" => $this->dataPost['from'],
            "to" => $this->dataPost['to'],
            "infor" => $this->dataPost['infor'],
            "created_at" => $this->createdAt
        );
        $id = $this->fee_loan_model->insertReturnId($insert);
        //Insert log
        $insertLog = array(
            "type" => "fee_loan",
            "action" => "create",
            "fee_loan_id" => (string)$id,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Tạo mới thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
  
    public function get_all_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $dataLoan = $this->fee_loan_model->find_where(array("status" => "active"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'code' => REST_Controller::HTTP_OK,
            'data' => $dataLoan
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        if(empty($this->dataPost['id']) || empty($this->dataPost['title']) || empty($this->dataPost['from'])  || empty($this->dataPost['to']) || empty($this->dataPost['infor'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Dữ liệu không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['from'] = strtotime(trim($this->dataPost['from']).' 00:00:00');
        $this->dataPost['to'] = strtotime(trim($this->dataPost['to']).' 00:00:00');
        
        foreach($this->dataPost['infor'] as $k1=>$v1) {
            foreach($v1 as $k2=>$v2) {
                foreach($v2 as $k3=>$v3) {
                    if($k3 == 'percent_interest_customer') {
                        $this->dataPost['infor'][$k1][$k2][$k3] = 1.5;
                    }
                }
            }
        }
        
        $update = array(
            "status" => "active",
            "title" => $this->dataPost['title'],
            "from" => $this->dataPost['from'],
            "to" => $this->dataPost['to'],
            "infor" => $this->dataPost['infor'],
            "updated_at" => $this->createdAt,
            "updated_by" => $this->uemail
        );
        $oldData = $this->fee_loan_model->findOneAndUpdate(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            $update
        );
        
        //Insert log
        $insertLog = array(
            "type" => "fee_loan",
            "action" => "update",
            "fee_loan_id" => (string)$this->dataPost['id'],
            "old" => $oldData,
            "new" => $update,
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Cập nhật thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
}

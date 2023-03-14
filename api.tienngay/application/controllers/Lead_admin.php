<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Lead_admin extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("lead_model");
        $this->load->model("dashboard_model");
        $this->load->model("log_lead_model");
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
                unset($this->dataPost['type']);
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    $this->ulang = $this->info['lang'];
                    $this->uemail = $this->info['email'];
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;
    
    public function get_lead_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $leads = $this->lead_model->find();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $leads
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_one_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $lead = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if(empty($lead)) return;
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $lead
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if(empty($leadDB)) return;
        $id = $this->dataPost['id'];
        unset($this->dataPost['id']);
        $this->dataPost['type_finance'] = (int)$this->dataPost['type_finance'];
        $this->dataPost['call'] = (int)$this->dataPost['call'];
        $this->dataPost['status'] = (int)$this->dataPost['status'];
        $this->dataPost['reason_1'] = !empty((int)$this->dataPost['reason_1']) ? (int)$this->dataPost['reason_1'] : "" ;
        $this->dataPost['reason_2'] = !empty((int)$this->dataPost['reason_2']) ? (int)$this->dataPost['reason_2'] : "" ;
        $this->dataPost['reason_3'] = !empty((int)$this->dataPost['reason_3']) ? (int)$this->dataPost['reason_3'] : "" ;
        //Update lead
        $old = $this->lead_model->findOneAndUpdate(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $this->dataPost
        );
        //Insert log
        $log = array(
            'old' => $leadDB,
            'new' => $this->dataPost,
            'updated_at' => $this->dataPost['updated_at'],
            'updated_by' => $this->dataPost['updated_by']
        );
        $this->log_lead_model->insert($log);
        
        //Summary for dashboard
        $this->updateStatusCall($old, $this->dataPost);
        $this->updateConfirmDisburse($old, $this->dataPost);
        
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update lead success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    private function updateStatusCall($old, $new) {
        //Check status
        if($old['status'] == $new['status']) return;
        $dashboard = $this->dashboard_model->find();
        $countNotCall = $dashboard[0]['lead_customer']['not_call'];
        $countCalled = $dashboard[0]['lead_customer']['called'];
        $dataUpdate = array();
        //Old = chưa gọi và New = đã gọi
        if($old['status'] == 1) {
            $dataUpdate = array(
                "lead_customer.not_call" => $countNotCall - 1,
                "lead_customer.called" => $countCalled + 1
            );
        } 
        //Old = đã gọi và New = chưa gọi
        else {
            $dataUpdate = array(
                "lead_customer.called" => $countCalled - 1,
                "lead_customer.not_call" => $countNotCall + 1
            );
        }
        $this->dashboard_model->update(
            array("_id" => $dashboard[0]['_id']),
            $dataUpdate
        );
    }
    
    private function updateConfirmDisburse($old, $new) {
        if(empty($new['reason_2']) || $old['reason_2'] == $new['reason_2']) return;
        $dashboard = $this->dashboard_model->find();
        $count = $dashboard[0]['lead_customer']['confirm_disburse'];
        //Old = đã chốt và new = không chốt
        if($old['reason_2'] == 2 || $old['reason_2'] == 3) {
            $count--;
        } else {
            $count++;
        }
        $this->dashboard_model->update(
            array("_id" => $dashboard[0]['_id']),
            array("lead_customer.confirm_disburse" => $count)
        );
    }
}
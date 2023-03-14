<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class AccountingSystem extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("access_right_model");
        $this->load->model("contract_model");
        $this->load->model("contract_tempo_model");
        $this->load->model("tempo_contract_accounting_model");
        $this->load->model("log_model");
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
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost;
    
    public function summary_total_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        //Get contract
        $datas = $this->contract_model->findContractWithTemp();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $datas
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function pay_investor_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        if(empty($this->dataPost['plan_id']) || empty($this->dataPost['resource_pay']) || empty($this->dataPost['date_pay']) || empty($this->dataPost['amount_interest_paid']) || empty($this->dataPost['amount_root_paid'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Dữ liệu post không thể để trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Update kỳ lãi tháng
        $this->dataPost['plan_id'] = $this->security->xss_clean($this->dataPost['plan_id']);
        $this->dataPost['resource_pay'] = $this->security->xss_clean($this->dataPost['resource_pay']);
        $this->dataPost['date_pay'] = $this->security->xss_clean($this->dataPost['date_pay']);
        $this->dataPost['amount_interest_paid'] = $this->security->xss_clean($this->dataPost['amount_interest_paid']);
        $this->dataPost['amount_root_paid'] = $this->security->xss_clean($this->dataPost['amount_root_paid']);
        $pay_investor = array(
            'resource_pay' => $this->dataPost['resource_pay'],
            'date_pay' => $this->dataPost['date_pay'],
            'amount_interest_paid' => $this->dataPost['amount_interest_paid'],
            'amount_root_paid' => $this->dataPost['amount_root_paid'],
        );
        $old = $this->tempo_contract_accounting_model->findOneAndUpdate(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['plan_id'])),
            array("pay_investor" => $pay_investor)
        );
        //Insert log
        $insertLog = array(
            "type" => "tempo_contract_accounting",
            "action" => "update_pay_investor",
            "plan_contract_id" => $this->dataPost['plan_id'],
            "new" => $pay_investor,
            "old" => $old['pay_investor'],
            "created_at" => $this->createdAt,
            "created_by" => $this->uemail
        );
        $this->log_model->insert($insertLog);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update successfully",
            "data" => $this->dataPost
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function search_summary_total_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $condition = array();
        if(!empty($start)) $condition['start'] = strtotime(trim($start).' 00:00:00');
        if(!empty($end)) $condition['end'] = strtotime(trim($end).' 23:59:59');
        $contract = $this->contract_model->findContractWithTemp($condition);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function export_summary_total_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        
        $startMonth = date('Y-m-01', strtotime($start)); // 2020-01 => 2020-01-01
        $endMonth = date('Y-m-t', strtotime($end)); // 2020-01 => 2020-01-31
        $condition = array();
        if(!empty($start)) $condition['start'] = strtotime(trim($startMonth).' 00:00:00');
        if(!empty($end)) $condition['end'] = strtotime(trim($endMonth).' 23:59:59');
        $contract = $this->contract_model->findContractExport($condition);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function export_contract_volume_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $condition = array();
        if(!empty($start)) $condition['start'] = strtotime(trim($start).' 00:00:00');
        if(!empty($end)) $condition['end'] = strtotime(trim($end).' 23:59:59');
       
        
        $contract = $this->contract_model->getReportContractVolume($condition, $start, $end);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
   
}
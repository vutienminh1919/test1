<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/CheckoutVIMO.php';
require_once APPPATH . 'libraries/BillingVIMO_lib.php';
use Restserver\Libraries\REST_Controller;

class Vimo extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("log_vimo_model");
        $this->load->model("disbursement_accounting_model");
        $this->dataPost = $this->input->post();
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        //Check secret_key
        $libTripleDes = new TripleDes();
        $this->isTriple = $libTripleDes->Decrypt($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY"));
        unset($this->dataPost['type']);
        unset($this->dataPost['secret_key']);
    }
    
    private $createdAt, $dataPost;
    
    public function create_withdrawal_post() {
//        if ($this->isTriple == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        $this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
        $this->dataPost['type_payout'] = $this->security->xss_clean($this->dataPost['type_payout']);
        $this->dataPost['order_code'] = $this->security->xss_clean($this->dataPost['order_code']);
        $this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']);
        $this->dataPost['bank_id'] = $this->security->xss_clean($this->dataPost['bank_id']);
        $this->dataPost['description'] = $this->security->xss_clean($this->dataPost['description']);
        //Bank account = 2 or Quick deposit into bank accounts  = 10
        if($this->dataPost['type_payout'] == 2 || $this->dataPost['type_payout'] == 10) {
            $this->dataPost['bank_account'] = $this->security->xss_clean($this->dataPost['bank_account']);
            $this->dataPost['bank_account_holder'] = $this->security->xss_clean($this->dataPost['bank_account_holder']);
            $this->dataPost['bank_branch'] = $this->security->xss_clean($this->dataPost['bank_branch']);
           
        }
        //ATM Card Number = 3
        if($this->dataPost['type_payout'] == 3) {
            $this->dataPost['atm_card_number'] = $this->security->xss_clean($this->dataPost['atm_card_number']);
            $this->dataPost['atm_card_holder'] = $this->security->xss_clean($this->dataPost['atm_card_holder']);
        }
        // Start check null
        
        if(empty($this->dataPost['type_payout'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Type payout can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['order_code'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Order code can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['amount'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Amount can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['bank_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Bank ID can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
       
        if($this->dataPost['type_payout'] == 2 || $this->dataPost['type_payout'] == 10) {
            unset($this->dataPost['atm_card_number']);
            unset($this->dataPost['atm_card_holder']);
            if(empty($this->dataPost['bank_account'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Bank account can not empty"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if(empty($this->dataPost['bank_account_holder'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Bank account holder can not empty"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if(empty($this->dataPost['bank_branch'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Bank branch can not empty"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        if($this->dataPost['type_payout'] == 3) {
            unset($this->dataPost['bank_account']);
            unset($this->dataPost['bank_account_holder']);
            if(empty($this->dataPost['atm_card_number'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Atm card number can not empty"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if(empty($this->dataPost['atm_card_holder'])) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Atm card holder can not empty"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        
        $count = $this->disbursement_accounting_model->count(array(
            "code_contract" => $this->dataPost['code_contract'],
            "status" => "new"
        ));
        if($count > 1) { 
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Mã HĐ đã tồn tại và trạng thái new"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
        //End
        //Call API VIMO
        $checkoutVIMO = new CheckoutVIMO();
        $result = $checkoutVIMO->createWithdrawal($this->dataPost);
        //Insert log_vimo_model
        $log = array(
            "code_contract" => $this->dataPost['code_contract'],
            "response" => $result,
            "created_at" => $this->createdAt,
            "created_by" => $this->dataPost['created_by'],
            "data_post" => $this->dataPost
        );
        $this->log_vimo_model->insert($log);
        if($result['error_code'] == '00') {
            //Update disbursement_accounting_model
            $this->disbursement_accounting_model->update(
                array("code_contract" => $this->dataPost['code_contract'],
                      "status" => "new"),
                array("response_create_withdrawal" => $result,
                      "status" => "create_withdrawal_success",
                      "disbursement_by" => $this->dataPost['created_by']
            ));
            //Update status_disbursement of contract
            $this->disbursement_accounting_model->update(
                array("code_contract" => $this->dataPost['code_contract'],
                      "status" => "new"),
                array("status_disbursement" => 2
            ));
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Create withdrawal success",
                'result' => $result
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Create withdrawal failed",
                'result' => $result
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        }
    }
    
    public function get_withdrawal_transaction_status_post() {
//        if ($this->isTriple == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        $this->dataPost['order_code'] = $this->security->xss_clean($this->dataPost['order_code']);
        $this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']);
        $this->dataPost['amount'] = (int)$this->dataPost['amount'];
        $checkoutVIMO = new CheckoutVIMO();
        $result = $checkoutVIMO->getWithdrawalTransactionStatus($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $result
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
}
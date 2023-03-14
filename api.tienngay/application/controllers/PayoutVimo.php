<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/CheckoutVIMO.php';
use Restserver\Libraries\REST_Controller;

class PayoutVimo extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("role_model");
        $this->load->model("log_vimo_model");
        $this->load->model("log_model");
        $this->load->model("contract_model");
        $this->load->model("group_role_model");
        $this->dataPost = $this->input->post();
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        //Check secret_key
        $libTripleDes = new TripleDes();
        $this->isTriple = $libTripleDes->Decrypt($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY"));
        unset($this->dataPost['type']);
        unset($this->dataPost['secret_key']);
        $headers = $this->input->request_headers();
        $this->flag_login = 1;
        $this->dataPost = $this->input->post();
        $this->superadmin = false;
        if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
            $headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
            $token = Authorization::validateToken($headers_item);
            if ($token != false) {
                // Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
                $this->dataPost['type'] = $this->security->xss_clean($this->dataPost['type']);
                if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
                if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
                unset($this->dataPost['type']);
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    // $this->ulang = $this->info['lang'];
                    $this->uemail = $this->info['email'];
                    $this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
                    
                    // Get access right
                    $roles = $this->role_model->getRoleByUserId((string)$this->id);
                    $this->roleAccessRights = $roles['role_access_rights'];
                    $this->groupRoles = $this->getGroupRole($this->id);
                   
                }
            }
        }
    }
    
    private $createdAt, $dataPost, $roleAccessRights;
    
    public function create_withdrawal_post() {
//        if ($this->isTriple == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        if (!in_array('van-hanh', $this->groupRoles) && $this->superadmin == false) {
            // Check access right
            if(!in_array('5def15a268a3ff1204003ad6', $this->roleAccessRights)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'data' => "No have access right"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
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
        if(empty($this->dataPost['created_by'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Created by can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
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
        $contractDB = $this->contract_model->findOne(array(
            "code_contract" => $this->dataPost['code_contract'],
            "status" => 15
        ));
        if(empty($contractDB)) { 
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Hợp đồng chưa được duyệt để giải ngân"
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
            //Update contract_model
            $this->contract_model->update(
                array("code_contract" => $this->dataPost['code_contract'],
                      "status" => 15,),
                array(
                        // "status" => 16,
                      "response_create_withdrawal" => $result,
                      "status_create_withdrawal" => "create_withdrawal_success",
                      "disbursement_by" => $this->dataPost['created_by'],
                      "status_disbursement" => 2,
                      "investor_code" => $this->dataPost['investor_code'],
                      "fee.percent_interest_investor" => $this->dataPost['percent_interest_investor'],

            ));
            //Insert log_model
            $log1 = array(
                "type" => "contract",
                "action" => "create_withdrawal",
                "contract_id" => (string)$contractDB['_id'],
                "created_at" => $this->createdAt,
                "created_by" => $this->dataPost['created_by']
            );
            $this->log_model->insert($log1);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Create withdrawal success",
                'result' => $result
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Create withdrawal error",
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
        $checkoutVIMO = new CheckoutVIMO();
        $result = $checkoutVIMO->getWithdrawalTransactionStatus($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $result
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    private function getGroupRole($userId) {
        $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach($groupRoles as $groupRole) {
			if(empty($groupRole['users'])) continue;
			foreach($groupRole['users'] as $item) {
				if(key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
		return $arr;
	}
}

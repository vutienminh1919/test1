<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/BillingVIMO_lib.php';
use Restserver\Libraries\REST_Controller;

class BillingVimo extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("log_billing");
          $this->load->model("order_model");
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
        $this->libraries = new BillingVIMO_lib();
    }
    private $createdAt, $dataPost, $isTriple, $libraries, $flag_login, $id, $uemail, $ulang, $app_login;
    
    //Nạp tiền tài khoản điện thoại/game
    public function topup_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //Check secret_key
//        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_TOPUP"));
//        unset($this->dataPost['secret_key']);
//        if ($isSecretKey == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        $this->dataPost['mc_request_id'] = $this->security->xss_clean($this->dataPost['mc_request_id']); // Mã yêu cầu dịch vụ của merchant
        $this->dataPost['service_code'] = $this->security->xss_clean($this->dataPost['service_code']); //Mã dịch vụ
        $this->dataPost['publisher'] = $this->security->xss_clean($this->dataPost['publisher']); //Mã nhà cung cấp/phát hành
        $this->dataPost['receiver'] = $this->security->xss_clean($this->dataPost['receiver']); // SĐT/Tài khoản người nhận
        $this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']); //Mệnh giá
        //Check null
        if(empty($this->dataPost['mc_request_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã yêu cầu dịch vụ của merchant không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['service_code'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã dịch vụ không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['publisher'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã nhà cung cấp/phát hành không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['receiver'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "SĐT/Tài khoản người nhận không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['amount'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mệnh giá không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Call API
        $param = array(
            "mc_request_id" => $this->dataPost['mc_request_id'],
            "service_code" => $this->dataPost['service_code'],
            "publisher" => $this->dataPost['publisher'],
            "receiver" => $this->dataPost['receiver'],
            "amount" => $this->dataPost['amount'],
        );
        $res = $this->libraries->topup($param);
        $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    //Kiểm tra giao dịch topup
    public function check_topup_transaction_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //Check secret_key
        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_TOPUP"));
        unset($this->dataPost['secret_key']);
        if ($isSecretKey == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->dataPost['mc_request_id'] = $this->security->xss_clean($this->dataPost['mc_request_id']); // Mã yêu cầu dịch vụ của merchant
        $this->dataPost['service_code'] = $this->security->xss_clean($this->dataPost['service_code']); //Mã dịch vụ
        //Check null
        if(empty($this->dataPost['mc_request_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã yêu cầu dịch vụ của merchant không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['service_code'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã dịch vụ không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
    //Mua mã thẻ điện thoại/game
    public function pincode_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //Check secret_key
//        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_PINCODE"));
//        unset($this->dataPost['secret_key']);
//        if ($isSecretKey == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        $this->dataPost['mc_request_id'] = $this->security->xss_clean($this->dataPost['mc_request_id']); // Mã yêu cầu dịch vụ của merchant
        $this->dataPost['service_code'] = $this->security->xss_clean($this->dataPost['service_code']); //Mã dịch vụ
        $this->dataPost['quantity'] = $this->security->xss_clean($this->dataPost['quantity']); //Số lượng
        $this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']); //Mệnh giá
        $this->dataPost['publisher'] = $this->security->xss_clean($this->dataPost['publisher']); //Mã nhà cung cấp/phát hành 
        //Check null
        if(empty($this->dataPost['mc_request_id'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã yêu cầu dịch vụ của merchant không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['service_code'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã dịch vụ không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['quantity'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Số lượng không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['amount'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mệnh giá không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if(empty($this->dataPost['publisher'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã nhà cung cấp/phát hành  không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        // Insert log
        $log = array(
            "fnc" => "pincode",
            "data_post" => $this->dataPost,
            "email" => $this->uemail,
            "created_at" => $this->createdAt
        );
        $this->log_billing->insert($log);
        // Call API
        $param = array(
            "mc_request_id" => $this->dataPost['mc_request_id'],
            "service_code" => $this->dataPost['service_code'],
            "quantity" => (int)$this->dataPost['quantity'],
            "amount" => (int)$this->dataPost['amount'],
            "publisher" => $this->dataPost['publisher']
        );
        $res = $this->libraries->pincode($param);
        if($res['error_code'] == '00') {
            //Tạo phiếu thu cho nhân viên phòng giao dịch
            
            //Tạo phiếu thu cho nhân viên phòng giao dịch
            
            
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $res
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'data' => $res,
                'message' => "Call API have error"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
        
    }
    //Kiểm tra giao dịch pincode
    public function get_pincode_transaction_post() {
        //Check secret_key
        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_PINCODE"));
        unset($this->dataPost['secret_key']);
        if ($isSecretKey == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
    //Truy vấn hóa đơn 
    public function query_bill_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
        //Check secret_key
        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_BILL"));
        unset($this->dataPost['secret_key']);
//        if ($isSecretKey == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
		$this->dataPost['mc_request_id'] = $this->security->xss_clean($this->dataPost['mc_request_id']); // Mã yêu cầu dịch vụ của merchant
		$this->dataPost['service_code'] = $this->security->xss_clean($this->dataPost['service_code']); //Mã dịch vụ
		$this->dataPost['publisher'] = $this->security->xss_clean($this->dataPost['publisher']); //Mã nhà cung cấp/phát hành
		$this->dataPost['customer_code'] = $this->security->xss_clean($this->dataPost['customer_code']); // Mã hóa đơn khách hàng
		//Check null
		if(empty($this->dataPost['mc_request_id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => "Mã yêu cầu dịch vụ của merchant không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if(empty($this->dataPost['service_code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => "Mã dịch vụ không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if(empty($this->dataPost['publisher'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => "Mã nhà cung cấp/phát hành không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if(empty($this->dataPost['customer_code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'data' => "Mã hóa đơn khách hàng không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		//Call API
		$param = array(
			"mc_request_id" => $this->dataPost['mc_request_id'],
			"service_code" => $this->dataPost['service_code'],
			"publisher" => $this->dataPost['publisher'],
			"customer_code" => $this->dataPost['customer_code'],
		);
		$res = $this->libraries->querybill($param);
		if($res['error_code'] == '00') {
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' => $res
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'data' => $res,
				'message' => "Call API have error"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
    }
    //Thanh toán hóa đơn
    public function pay_bill_post() {
        //Check secret_key
        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_BILL"));
        unset($this->dataPost['secret_key']);
        if ($isSecretKey == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
    //Kiểm tra giao dịch thanh toán hóa đơn
    public function check_bill_transaction_post() {
        //Check secret_key
        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_BILL"));
        unset($this->dataPost['secret_key']);
        if ($isSecretKey == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
    
    //Get balance
    public function get_balance_post() {
        //Check secret_key
//        $isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_BILL"));
//        unset($this->dataPost['secret_key']);
//        if ($isSecretKey == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
        $res = $this->libraries->getbalance();
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
       
    
       
    
    private function checkSecretKey($secretKey, $tripleDes) {
        $isCorrect = TRUE;
        $libTripleDes = new TripleDes();
        $this->isTriple = $libTripleDes->Decrypt($secretKey, $tripleDes);
        if($this->isTriple == FALSE) $isCorrect = FALSE;
        return $isCorrect;
    }
     //revert
    public function revert_bill_post() {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        //Check secret_key
      $data = $this->input->post();
   
      
        //Check null
        if(empty($data['id_order'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Mã đơn hàng không thể trống"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $check = $this->order_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($data['id_order'])]);
        if(empty($check))
             return;
       unset($data['type']);
        unset($data['id_order']);
       $data['mc_request_id'] ='BILL_TN_'.time(); // Mã yêu cầu dịch vụ của merchant
        $data['service_code'] = $check['service_code']; //Mã dịch vụ
        $data['publisher'] = $check['detail']['publisher']; //Mã nhà cung cấp/phát hành
        $data['customer_code'] =$check['detail']['customer_code']; // Mã hóa đơn khách hàng
        $data['mc_transaction_id'] =$check['mc_request_id'];  //Mã giao dịch đã thanh toán
        $data['bill_payment'] =[$check['detail']['bill_payment']];
        
      
        $res = $this->libraries->revertbill($data);
             // Insert log
        $log = array(
            "fnc" => "revertbill",
            "request" => $data,
            "response" => $res,
            "order_id" => (string)$check['_id'],
            "email" => $this->uemail,
            "created_at" => $this->createdAt
        );
        $this->log_billing->insert($log);
        if($res['error_code'] == '00') {
            $this->order_model->update(
                ['_id'=>$check['_id']],
                [
                    'status'=>'revert',
                    'response_revert'=>$res
                ]
            );
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $res
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'data' => $res,
                'message' => "Call API have error"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
}

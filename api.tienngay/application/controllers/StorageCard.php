<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/BillingVIMO_lib.php';
use Restserver\Libraries\REST_Controller;

class StorageCard extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("user_model");
        $this->load->model("storage_card_model");
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->dataPost = $this->input->post();
        $headers = $this->input->request_headers();
        $this->flag_login = 1;
        $this->superadmin = false;
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
                    $this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
                }
            }
        }
        unset($this->dataPost['type']);
    }
    private $createdAt, $dataPost, $isTriple, $flag_login, $id, $uemail, $ulang, $app_login, $superadmin;

	public function get_quantity_post() {
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//Check secret_key
//		$isSecretKey = $this->checkSecretKey($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_BILLING_VIMO_BILL"));
		unset($this->dataPost['secret_key']);
//        if ($isSecretKey == false) {
//            $response = array(
//                'status' => REST_Controller::HTTP_UNAUTHORIZED,
//                'data' => "Secret key invalid"
//            );
//            $this->set_response($response, REST_Controller::HTTP_OK);
//            return;
//        }
		$this->dataPost['publisher'] = $this->security->xss_clean($this->dataPost['publisher']); // tong tien
		$this->dataPost['amount'] = $this->security->xss_clean($this->dataPost['amount']); // tong tien
		$this->dataPost['service_code'] = $this->security->xss_clean($this->dataPost['service_code']); // tong tien

		//Check null
		if(empty($this->dataPost['publisher'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Nhà cung cấp mã thẻ không được trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if(empty($this->dataPost['service_code'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mã dịch vụ không được trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if(empty($this->dataPost['amount'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Mệnh giá thẻ không thể trống"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		} else {
			$this->dataPost['amount'] = (int)$this->dataPost['amount'];
		}
		$this->dataPost['status'] = 'new';
		$count = $this->storage_card_model->count($this->dataPost);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'Lấy số lượng thẻ thành công',
			'count' => $count
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
}

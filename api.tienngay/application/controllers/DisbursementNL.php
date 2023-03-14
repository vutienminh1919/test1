<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 */

require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;


class DisbursementNL extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
// Tk: merchant@yopmail.com
// Pass: test123
// Mk giao dịch: 123test
// Tên chủ tk: Nguyễn Văn A
// Tên đăng nhập: demo123
// merchant_id: 47792
// merchantPass: 2a349ed1ff2658bfe793628405bbfa89
    }

    public function accountant_investors_disbursement_post(){
        $merchant_id = "47792";
        $merchant_pass = "2a349ed1ff2658bfe793628405bbfa89";
        $receiver = "merchant@yopmail.com";
        
        $nlcheckout = new NL_Withdraw($merchant_id, $merchant_pass, $receiver);
		$nlcheckout->url_api = $this->config->item("NL_WITHDRAW_URL");
		$total_amount = 100000000;
        $account_type = 3;
        $bank_code = "STB";
        $ref_code = "macode_".time();

        $card_fullname = '1234567890123456';
        $card_number = 1234567890123456;
        $card_month = '';
        $card_year = '';
        $branch_name = "abc";
		$nl_result = $nlcheckout->SetCashoutRequest($ref_code, $total_amount, $account_type, $bank_code, $card_fullname, $card_number, $card_month, $card_year,$branch_name);
        if ($nl_result->error_code == '00') {
            echo "Thành công";
            //die();
        } else {
            echo $nl_result->error_message;
        }

    }






}

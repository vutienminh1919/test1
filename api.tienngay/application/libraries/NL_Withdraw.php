<?php

class NL_Withdraw {

    public $merchant_id = '';
    public $merchant_password = '';
    public $receiver_email = '';
	public $url_api = '';

    function __construct($merchant_id, $merchant_password, $receiver_email) {
        // $this->ci =& get_instance();
        // $this->ci->config->load('config');
        // $this->url_api = $this->ci->config->item('NL_WITHDRAW_URL');

        $this->merchant_id = $merchant_id;
        $this->merchant_password = $merchant_password;
        $this->receiver_email = $receiver_email;
    }

    function CheckCashout($ref_code,$transaction_id) {       
###################### BEGIN #####################
        $params = array(
            'merchant_id' => $this->merchant_id,
            'merchant_password' => MD5($this->merchant_password),
			'receiver_email' => $this->receiver_email,
            'func' => 'CheckCashout',
			'ref_code' => $ref_code,
            'transaction_id' => $transaction_id
        );
        $api_url =$this->url_api;
        /* $post_field = '';
        foreach ($params as $key => $value) {
            if ($post_field != '')
                $post_field .= '&';
            $post_field .= $key . "=" . $value;
        } */
		
		$post_field = '';
        foreach ($params as $key => $value) {
            if ($post_field != '')
                $post_field .= '&';
            $post_field .= $key . "=" . urlencode($value);
        }
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if ($result != '' && $status == 200) {
            $nl_result = json_decode($result);
            return $nl_result;
        }

        return false;
        ###################### END #####################
    }

    /*

      Hàm lấy link thanh toán bằng thẻ visa
      ===============================
      Tham số truyền vào bắt buộc phải có
      order_code
      total_amount
      payment_method

      buyer_fullname
      buyer_email
      buyer_mobile
      ===============================
      $array_items mảng danh sách các item name theo quy tắc
      item_name1
      item_quantity1
      item_amount1
      item_url1
      .....
      payment_type Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
     */

    function SetCashoutRequest($ref_code, $total_amount, $account_type, $bank_code, $card_fullname, $card_number, $card_month, $card_year,$branch_name,$reason) {
        $params = array(
            'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
            'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)	
			'receiver_email' => $this->receiver_email,			
            'func' => "SetCashoutRequest", 
			'ref_code' => $ref_code, //Mã hóa đơn do website bán hàng sinh ra
            'total_amount' => $total_amount, //Tổng số tiền rút
			'account_type' => $account_type, //Kiểu giao dịch: 2 - Rút về thẻ; 3 - Rút về tài khoản; Nếu không truyền hoặc bằng rỗng thì =3
            'bank_code' => $bank_code,
			'card_fullname' => $card_fullname, //tên chủ tài khoản/ thẻ
            'card_number' => $card_number, //Số thẻ/tài khoản
            'card_month' => $card_month, //Tháng phát hành thẻ
            'card_year' => $card_year, //Năm phát hành thẻ
            'branch_name' => $branch_name, //Chi nhánh
        );
        if(!empty($reason)){
            $params['reason'] = $reason;
        }
		//print_r($params); die;
        
		$post_field = '';
        foreach ($params as $key => $value) {
            if ($post_field != '')
                $post_field .= '&';
            $post_field .= $key . "=" . urlencode($value);
        }
		// var_dump($post_field);
        // die();

        $nl_result = $this->CheckoutCall($post_field);
        return $nl_result;
    }

    function CheckoutCall($post_field) {
        $api_url =$this->url_api;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
        
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if ($result != '' && $status == 200) {
            //$xml_result = str_replace('&', '&amp;', (string) $result);
			
            $nl_result = json_decode($result);
            $nl_result->error_message = $this->GetErrorMessage($nl_result->error_code);
        } else
            $nl_result->error_message = $error;
        return $nl_result;
    }

    function GetErrorMessage($error_code) {
        $arrCode = array(
            '00' => 'Thành công',
            '99' => 'Lỗi chưa xác minh',
            '01' => 'Merchant không được phép sử dụng phương thức này',
            '02' => 'Thông tin thẻ sai định dạng',
            '03' => 'Thông tin merchant không chính xác',
            '04' => 'Có lỗi trong quá trình kết nối',
            '05' => 'Số tiền không hợp lệ',
            '06' => 'Tên chủ thẻ không hợp lệ',
            '07' => 'Số tài khoản không hợp lệ',
            '08' => 'Ngân hàng không hỗ trợ',
            );
        return array_key_exists(trim($error_code), $arrCode) ? $arrCode[trim($error_code)] : '99';
    }

}

?>
<?php

define('ROOT_URL', 'http://sandbox.vimo.vn/checkout_api/payment/v2/checkout.php');


class CheckoutVIMO{	
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->config->load('config');
        
        $this->VM_CHECKSUM_KEY = $this->ci->config->item('VM_CHECKSUM_KEY');
        $this->VM_ENCRYPTION_KEY = $this->ci->config->item('VM_ENCRYPTION_KEY');
        $this->VM_URL_CHECKOUT = $this->ci->config->item('VM_URL_CHECKOUT');
        $this->VM_MERCHANT_ID = $this->ci->config->item('VM_MERCHANT_ID');
        $this->VM_USER_ID = $this->ci->config->item('VM_USER_ID');
    }
    
    #public $__eMethod = 'DES';
    public $__eMethod = 'AES';
    
    private $VM_CHECKSUM_KEY, $VM_URL_CHECKOUT, $VM_MERCHANT_ID, $VM_USER_ID, $VM_ENCRYPTION_KEY;
    
    function createWithdrawal($param) {
        // var_dump($param);die;
        $fnc = 'CreateWithdrawal';
        $dataJson = json_encode($this->getDataSend($param), true);
        if($this->__eMethod == 'AES') {
            $encodeTRIPLEDES = $this->encryptNew($dataJson, $this->VM_ENCRYPTION_KEY);
        } else {
            $encodeTRIPLEDES = $this->Encrypt($dataJson, $this->VM_ENCRYPTION_KEY);
        }
        $checksum = md5($fnc.$this->VM_MERCHANT_ID.$this->VM_USER_ID.$param['type_payout'].$encodeTRIPLEDES.$this->VM_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "mid" => $this->VM_MERCHANT_ID,
            "uid" => $this->VM_USER_ID,
            "type" => $param['type_payout'],
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
            'order_code' => $param['order_code'], //Will be unset
            'amount' => $param['amount'] //Will be unset
        );
        $result = $this->call($send, $fnc);

        return $result;
    }
    
    private function getDataSend($param) {
        $data = array();
        $data['order_code'] = $param['order_code'];
        $data['amount'] = (int)$param['amount'];
        $data['bank_id'] = $param['bank_id'];
        $data['description'] = $param['description'];
        //Bank’s Account 
        if($param['type_payout'] == 2 || $param['type_payout'] == 10) {
            $data['bank_account'] = $param['bank_account'];
            $data['bank_account_holder'] = $param['bank_account_holder'];
            $data['bank_branch'] = $param['bank_branch'];
            
        }
        //ATM Card Number
        if($param['type_payout'] == 3) {
            $data['atm_card_number'] = $param['atm_card_number'];
            $data['atm_card_holder'] = $param['atm_card_holder'];
        }
        return $data;
    }
    
    function getWithdrawalTransactionStatus($param) {
        $fnc = 'GetTransactionWithdrawalStatus';
        $dataJson = json_encode($param, true);
        if($this->__eMethod == 'AES') {
            $encodeTRIPLEDES = $this->encryptNew($dataJson, $this->VM_ENCRYPTION_KEY);
        } else {
            $encodeTRIPLEDES = $this->Encrypt($dataJson, $this->VM_ENCRYPTION_KEY);
        }
        $checksum = md5($fnc.$this->VM_MERCHANT_ID.$this->VM_USER_ID.$encodeTRIPLEDES.$this->VM_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "mid" => $this->VM_MERCHANT_ID,
            "uid" => $this->VM_USER_ID,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
            'order_code' => !empty($param['order_code']) ? $param['order_code']: "" //Will be unset
        );
        $result = $this->call($send, $fnc);
        return $result;
    }
    
    function call($params, $fnc){
        $this->WriteLog($fnc.date("Ymd",time()).".txt", " ================= START ======================= ");
        $this->WriteLog($fnc.date("Ymd",time()).".txt", "[INPUT]: ".json_encode($params));
        $this->WriteLog($fnc.date("Ymd",time()).".txt", " ================= END ======================= ");
        unset($params['order_code']);
        unset($params['amount']);
        unset($params['type_payout']);
        $curl = curl_init($this->VM_URL_CHECKOUT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);                         
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);                    
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                          
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);                           
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
        $response = curl_exec($curl);
        $resultStatus = curl_getinfo($curl);
        $this->WriteLog($fnc.date("Ymd",time()).".txt", " ================= START ======================= ");
        $this->WriteLog($fnc.date("Ymd",time()).".txt", "[OUTPUT_exec]: ".json_encode($response));
        $this->WriteLog($fnc.date("Ymd",time()).".txt", "[OUTPUT_getinfo]: ".json_encode($resultStatus));
        $this->WriteLog($fnc.date("Ymd",time()).".txt", " ================= END ======================= ");
        $data = json_decode($response, true);
        return $data;
//        if ($resultStatus['http_code'] == 200) {
//            $data = json_decode($response, true);
//            return $data;
//        } else {
//            echo 'Call Failed ' . print_r($resultStatus);
//        }
    }

    function WriteLog($fileName,$data,$breakLine=true,$addTime=true) {
        $fp = fopen("log/".$fileName,'a');
        if ($fp)
        {
            if ($breakLine)
            {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ",time()).$data. " \n";
                else
                    $line = $data. " \n";
            }
            else
            {
                if ($addTime)
                    $line = date("H:i:s, d/m/Y:  ",time()).$data;
                else 
                    $line = $data;
            }
            fwrite($fp,$line);
            fclose($fp);
        }
    }
    
    function encryptNew($data, $encryptkey, $method = 'AES-256-ECB') {
        $key = hash('sha256', $encryptkey);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);		
        // For storage/transmission, we simply concatenate the IV and cipher text
        $encrypted = base64_encode($iv . $encrypted);
        return $encrypted;
    }

    function decryptNew($data, $encryptkey, $method = 'AES-256-ECB') {
        $key = hash('sha256', $encryptkey);
        $data = base64_decode($data);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivSize);
        $data = openssl_decrypt(substr($data, $ivSize), $method, $key, OPENSSL_RAW_DATA, $iv);
        return $data;
    }
    
    function Encrypt($input, $keyCode) {
        $input = trim($input);
        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($input);
        $padding = $block - ($len % $block);
        $input .= str_repeat(chr($padding), $padding);
        // generate a 24 byte key from the md5 of the seed
        $key = substr(md5($keyCode), 0, 24);
        $ivSize = mcrypt_get_iv_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        // encrypt
        $encryptedData = mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $input, MCRYPT_MODE_ECB, $iv);
        // clean up output and return base64 encoded
        $encryptedData = base64_encode($encryptedData);
        return $encryptedData;
    }
    
    function Decrypt($input, $keyCode) {
        $input = base64_decode($input);
        $key = substr(md5($keyCode), 0, 24);
        $text = mcrypt_decrypt(MCRYPT_TRIPLEDES, $key, $input, MCRYPT_MODE_ECB, 'Mkd34ajdfka5');
        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $packing = ord($text{strlen($text) - 1});
        if ($packing && ($packing < $block)) {
            for ($P = strlen($text) - 1; $P >= strlen($text) - $packing; $P--) {
                if (ord($text{$P}) != $packing) {
                    $packing = 0;
                }
            }
        }
        $text = substr($text, 0, strlen($text) - $packing);
        return $text;
    }
}
?>
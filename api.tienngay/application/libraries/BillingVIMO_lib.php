<?php

define('ROOT_URL', 'http://sandbox.vimo.vn/checkout_api/payment/v2/checkout.php');

require 'AES256.php';

class BillingVIMO_lib{	
    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->config->load('config');
        $this->BILLING_VIMO_URL_API = $this->ci->config->item('BILLING_VIMO_URL_API');
        $this->BILLING_VIMO_MC_CODE = $this->ci->config->item('BILLING_VIMO_MC_CODE');
        $this->BILLING_VIMO_MC_AUTH_USER = $this->ci->config->item('BILLING_VIMO_MC_AUTH_USER');
        $this->BILLING_VIMO_MC_AUTH_PASS = $this->ci->config->item('BILLING_VIMO_MC_AUTH_PASS');
        $this->BILLING_VIMO_MC_ENCRYPT_KEY = $this->ci->config->item('BILLING_VIMO_MC_ENCRYPT_KEY');
        $this->BILLING_VIMO_MC_CHECKSUM_KEY = $this->ci->config->item('BILLING_VIMO_MC_CHECKSUM_KEY');
    }
    
    public $__eMethod = 'AES';
    
    private $BILLING_VIMO_URL_API, $BILLING_VIMO_MC_CODE, $BILLING_VIMO_MC_AUTH_USER, $BILLING_VIMO_MC_AUTH_PASS, $BILLING_VIMO_MC_ENCRYPT_KEY, $BILLING_VIMO_MC_CHECKSUM_KEY;
    
    function topup($param) {
        $fnc = "topup";
        $dataJson = json_encode($param, true);
        $encodeTRIPLEDES = $this->Encrypt($dataJson);
        $checksum = md5($this->BILLING_VIMO_MC_CODE.$encodeTRIPLEDES.$this->BILLING_VIMO_MC_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "merchantcode" => $this->BILLING_VIMO_MC_CODE,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
        );
        $result = $this->call($send, $fnc);
        return $result;
    }
    
    function pincode($param) {
        $fnc = "pincode";
        $dataJson = json_encode($param);
        $encodeTRIPLEDES = $this->Encrypt($dataJson);
        $checksum = md5($this->BILLING_VIMO_MC_CODE.$encodeTRIPLEDES.$this->BILLING_VIMO_MC_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "merchantcode" => $this->BILLING_VIMO_MC_CODE,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
        );
        $result = $this->call($send, $fnc);
        return $result;
    }

    function querybill($param) {
        $fnc = "querybill";
        $dataJson = json_encode($param);
        $encodeTRIPLEDES = $this->Encrypt($dataJson);
        $checksum = md5($this->BILLING_VIMO_MC_CODE.$encodeTRIPLEDES.$this->BILLING_VIMO_MC_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "merchantcode" => $this->BILLING_VIMO_MC_CODE,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
        );
        $result = $this->call($send, $fnc);
        return $result;
    }
      function revertbill($param) {
        $fnc = "revertbill";
        $dataJson = json_encode($param);
        $encodeTRIPLEDES = $this->Encrypt($dataJson);
        $checksum = md5($this->BILLING_VIMO_MC_CODE.$encodeTRIPLEDES.$this->BILLING_VIMO_MC_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "merchantcode" => $this->BILLING_VIMO_MC_CODE,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum,
        );
        $result = $this->call($send, $fnc);
        return $result;
    }
    function getbalance() {
        $balance = 0;
        $param = array(
            "mc_request_id" => sha1(rand(1, 9999999999999999999)),
            "merchant_code" => $this->BILLING_VIMO_MC_CODE
        );
        $fnc = "getbalance";
        $dataJson = json_encode($param);
        $encodeTRIPLEDES = $this->Encrypt($dataJson);
        $checksum = md5($this->BILLING_VIMO_MC_CODE.$encodeTRIPLEDES.$this->BILLING_VIMO_MC_CHECKSUM_KEY);
        $send = array(
            "fnc" => $fnc,
            "merchantcode" => $this->BILLING_VIMO_MC_CODE,
            "data" => $encodeTRIPLEDES,
            "checksum" => $checksum
        );
        $result = $this->call($send, $fnc);
//        if($result['error_code'] == '00') {
//            $balance = $result['data']['balance'];
//        }
        return $result;
    }
    
    function call($params, $fnc){
        
        //return $params;
        //return $this->BILLING_VIMO_URL_API.$fnc;
        
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", " ================= START ======================= ");
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", "[INPUT]: ".json_encode($params));
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", " ================= END ======================= ");
        $curl = curl_init($this->BILLING_VIMO_URL_API.$fnc);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);                         
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);                    
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);                          
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);                           
        curl_setopt($curl, CURLOPT_USERAGENT, 'MERCHANT');
        curl_setopt($curl, CURLOPT_USERPWD, $this->BILLING_VIMO_MC_AUTH_USER.':'.$this->BILLING_VIMO_MC_AUTH_PASS);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
        $response = curl_exec($curl);
        $resultStatus = curl_getinfo($curl);
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", " ================= START ======================= ");
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", "[OUTPUT_exec]: ".json_encode($response));
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", "[OUTPUT_getinfo]: ".json_encode($resultStatus));
        $this->WriteLog("Billing_VIMO_".$fnc.date("Ymd",time()).".txt", " ================= END ======================= ");
        $data = json_decode($response, true);
        return $data;
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
    
    public function Encrypt($data)
    {		
        return AES256::Encrypt($data, $this->BILLING_VIMO_MC_ENCRYPT_KEY);
    }

    public function Decrypt($data)
    {		
        return AES256::Decrypt($data, $this->BILLING_VIMO_MC_ENCRYPT_KEY);
    }
}
?>

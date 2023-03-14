<?php

defined('BASEPATH') or exit('No direct script access allowed');

class VFCPayment
{
    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('config');
        $this->baseURL = $this->ci->config->item("corev2_vfcpayment");
    }

    public function getAllContractsbyVan($van, $auth = [])
    {   
        $acc = $this->ci->config->item("vfcpayment_acc");
        $pass = $this->ci->config->item("vfcpayment_pass");
        $header = [
            'Authorization: ' . 'Bearer ' . base64_encode($acc.':'.$pass),
        ];
        $headers = array_merge($header, $auth);
        $service = $this->baseURL . '/search_van';
        $data = [
            "requestId" => self::generateRequestID(),
            'reference1' => $van,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($result, true);
        if (isset($data['status']) && $data['status'] == 200) {
            return $data['data'];
        }
        return false;
    }
    /**
    *
    * generate unique string
    * @return string
    */
    private static function generateRequestID() {
        return (string) time() . (string) rand(0, 99);
    }
}

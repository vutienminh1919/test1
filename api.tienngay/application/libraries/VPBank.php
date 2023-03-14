<?php

defined('BASEPATH') or exit('No direct script access allowed');

class VPBank
{
    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('config');
        $this->baseURL = $this->ci->config->item("corev2_vpbank");

    }

    public function assignVan($codeContract)
    {
        if (empty($codeContract)) {
            log_message('info', 'VPBank assignVan method: codeContract is empty');
            return false;
        }
        $service = $this->baseURL . '/getVan';
        $data = [
            'contract_code' => $codeContract,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($result, true);
        if (isset($data["status"]) && $data["status"] == 200) {
            return [
                'bankName' => $data['data']["bankName"],
                'masterAccountName' => $data['data']["masterAccountName"],
                'van' => $data['data']["van"],
                'contract_code' => $data['data']["contract_code"],
            ];
        }
        log_message('info', 'VPBank assignVan method: get VAN failed');
        return false;
    }
}

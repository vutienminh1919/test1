<?php

defined('BASEPATH') or exit('No direct script access allowed');

class BaoHiemPTI {

	public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('config');
        $this->baseURL = $this->ci->config->item("corev2_pti");

    }

    /*
    * Call create order api
    */
    public function call_api($data)
    {
    	$this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        if (empty($data)) {
   			$this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", "Data is empty");
            return false;
        }
        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI data " . json_encode($data));
        $service = $this->baseURL . '/orderByContract';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI response " . json_encode($response));

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= END ======================= ");
        return $response;
    }

    /*
    * Call create order api
    */
    public function call_apiBN($data)
    {
        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= START BN ======================= ");
        if (empty($data)) {
            $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", "Data is empty");
            return false;
        }
        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI data " . json_encode($data));
        $service = $this->baseURL . '/orderByBN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI response " . json_encode($response));

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= END ======================= ");
        return $response;
    }

    /*
    * Call get pdf gcn api
    */
    public function getGCN($data)
    {
        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        if (empty($data)) {
            $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", "Data is empty");
            return false;
        }
        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI data " . json_encode($data));
        $service = $this->baseURL . '/apiGetPdfFile';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " PTI response " . json_encode($response));

        $this->WriteLog("BH-PTI" . date("Ymd", time()) . ".txt", " ================= END ======================= ");
        return $response;
    }

    public function WriteLog($fileName,$data,$breakLine=true,$addTime=true) {
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

    /*
    * Call create order PTI BHTN api
    */
    public function call_bhtn_api($data)
    {
        $this->WriteLog("BH-PTI-BHTN" . date("Ymd", time()) . ".txt", " ================= START ======================= ");
        if (empty($data)) {
            $this->WriteLog("BH-PTI-BHTN" . date("Ymd", time()) . ".txt", "Data is empty");
            return false;
        }
        $this->WriteLog("BH-PTI-BHTN" . date("Ymd", time()) . ".txt", " PTI data " . json_encode($data));
        $service = $this->baseURL . '/bhtn/orderByContract';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        $this->WriteLog("BH-PTI-BHTN" . date("Ymd", time()) . ".txt", " PTI response " . json_encode($response));

        $this->WriteLog("BH-PTI-BHTN" . date("Ymd", time()) . ".txt", " ================= END ======================= ");
        return $response;
    }

}

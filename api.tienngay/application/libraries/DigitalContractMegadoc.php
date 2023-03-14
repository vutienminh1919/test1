<?php

defined('BASEPATH') or exit('No direct script access allowed');

class DigitalContractMegadoc
{

	public function __construct()
	{

		$this->ci =& get_instance();
		$this->ci->config->load('config');

		$this->MGD_URL = $this->ci->config->item('MGD_URL');
		$this->MGD_USERNAME = $this->ci->config->item('MGD_USERNAME');
		$this->MGD_PASSWORD = $this->ci->config->item('MGD_PASSWORD');
		$this->MGD_TAXCODE = $this->ci->config->item('MGD_TAXCODE');
		$this->MGD_CATECODE = $this->ci->config->item('MGD_CATECODE');
		$this->MGD_SUBCATECODE = $this->ci->config->item('MGD_SUBCATECODE');
		$this->MGD_DEBTCODE = $this->ci->config->item('MGD_DEBTCODE');

		$this->MGD_DB_URL = $this->ci->config->item('MGD_DB_URL');
		$this->MGD_DB_USERNAME = $this->ci->config->item('MGD_DB_USERNAME');
		$this->MGD_DB_PASSWORD = $this->ci->config->item('MGD_DB_PASSWORD');
		$this->MGD_DB_TAXCODE = $this->ci->config->item('MGD_DB_TAXCODE');
		$this->MGD_DB_CATECODE = $this->ci->config->item('MGD_DB_CATECODE');
		$this->MGD_DB_SUBCATECODE = $this->ci->config->item('MGD_DB_SUBCATECODE');
		$this->MGD_DB_DEBTCODE = $this->ci->config->item('MGD_DB_DEBTCODE');


	}

	public function generateAuthentication($check_company_send)
	{
		$nonce = md5(uniqid());
		$user_name_megadoc = "";
		$password_megadoc = "";
		if ($check_company_send == "TCV") {
			$user_name_megadoc = $this->MGD_USERNAME;
			$password_megadoc = $this->MGD_PASSWORD;
		} elseif ($check_company_send == "TCVĐB") {
			$user_name_megadoc = $this->MGD_DB_USERNAME;
			$password_megadoc = $this->MGD_DB_PASSWORD;
		}
		$authenString = $user_name_megadoc . ':' . $password_megadoc . ':' . $nonce;
		$authentication = base64_encode($authenString);

		return $authentication;
	}


	public function create_contract($dataSend, $check_company_send)
	{
		$authentication = $this->generateAuthentication($check_company_send);
		$company_url = "";
		$tax_code = "";
		if ($check_company_send == "TCV") {
			$company_url = $this->MGD_URL;
			$tax_code = $this->MGD_TAXCODE;
		} elseif ($check_company_send == "TCVĐB") {
			$company_url = $this->MGD_DB_URL;
			$tax_code = $this->MGD_DB_TAXCODE;
		}
		$headers = array(
			"Authentication: " . $authentication,
			"Taxcode: " . $tax_code
		);
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $company_url . 'api/pdf-contract/create-contract',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $dataSend,
			CURLOPT_HTTPHEADER => $headers
		));
		$response = curl_exec($curl);
		curl_close($curl);
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "================= START ======================= ");
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "[OUTPUT_create_contract]:" . json_encode($response));
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", " ================= END ======================= ");
		return json_decode($response);
	}

	public function status_contract($searchkey, $check_company_send)
	{
		$authentication = $this->generateAuthentication($check_company_send);
		$company_url = "";
		$tax_code = "";
		if ($check_company_send == "TCV") {
			$company_url = $this->MGD_URL;
			$tax_code = $this->MGD_TAXCODE;
		} elseif ($check_company_send == "TCVĐB") {
			$company_url = $this->MGD_DB_URL;
			$tax_code = $this->MGD_DB_TAXCODE;
		}
		$headers = array(
			"Authentication: " . $authentication,
			"Taxcode: " . $tax_code,
			'Content-Type: application/json'
		);
		$data[] = [
			"searchkey" => $searchkey
		];

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $company_url . "api/pdf-contract/status",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($data),
			CURLOPT_HTTPHEADER => $headers,
		]);

		$response = curl_exec($curl);
		curl_close($curl);
		return json_encode($response);
	}

	public function download_file($searchkey, $check_company_send)
	{
		$authentication = $this->generateAuthentication($check_company_send);
		$company_url = "";
		$tax_code = "";
		if ($check_company_send == "TCV") {
			$company_url = $this->MGD_URL;
			$tax_code = $this->MGD_TAXCODE;
		} elseif ($check_company_send == "TCVĐB") {
			$company_url = $this->MGD_DB_URL;
			$tax_code = $this->MGD_DB_TAXCODE;
		}
		$headers = array(
			"Authentication: " . $authentication,
			"Taxcode: " . $tax_code
		);
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $company_url . "api/pdf-contract/download?searchkey=" . $searchkey,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => $headers
		]);


		$response = curl_exec($curl);
		curl_close($curl);
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "================= START ======================= ");
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "[OUTPUT_download_file]:" . json_encode($response));
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", " ================= END ======================= ");
		return $response;
	}

	public function cancel_contract($data, $check_company_send)
	{
		$authentication = $this->generateAuthentication($check_company_send);
		$company_url = "";
		$tax_code = "";
		if ($check_company_send == "TCV") {
			$company_url = $this->MGD_URL;
			$tax_code = $this->MGD_TAXCODE;
		} elseif ($check_company_send == "TCVĐB") {
			$company_url = $this->MGD_DB_URL;
			$tax_code = $this->MGD_DB_TAXCODE;
		}
		$headers = array(
			"Authentication: " . $authentication,
			"TaxCode: " . $tax_code,
			"Content-Type: application/json"
		);
		$formData[] = array(
			"FKey" => $data["code_contract"],
			"ContractNo" => $data["code_contract_disbursement"],
			"Reason" => $data['reason_cancel_contract']
		);
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $company_url . 'api/pdf-contract/cancel',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($formData),
			CURLOPT_HTTPHEADER => $headers
		]);
		$response = curl_exec($curl);
		curl_close($curl);
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "================= START ======================= ");
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", "[OUTPUT_cancel_contract]:" . json_encode($response));
		$this->WriteLog("MEGADOC" . date("Ymd", time()). ".txt", " ================= END ======================= ");
		return json_encode($response);
	}

	public function WriteLog($fileName, $data, $breakLine = true, $addTime = true) {
		$fp = fopen("log/".$fileName,'a');
		if ($fp) {
			if ($breakLine) {
				if ($addTime) {
					$line = date("H:i:s, d/m/Y:  ", time()). $data . " \n";
				} else {
					$line = $data. " \n";
				}
			} else {
				if ($addTime) {
					$line = date("H:i:s, d/m/Y:  ", time()). $data;
				} else {
					$line = $data;
				}
			}
			fwrite($fp,$line);
			fclose($fp);
		}
	}



}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

class HeyU
{
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('config');

		$this->KEY = $this->ci->config->item('KEY_HEYU');
		$this->SECRET = $this->ci->config->item('SECRET_HEYU');
		$this->URL = $this->ci->config->item('URL_HEYU');
	}

	private $KEY, $SECRET, $URL;

	public function recharge($param)
	{
		$dataJson = json_encode($param);
		$checksum = md5($dataJson . "+" . $this->SECRET);
		$url = $this->URL . "/api/v1.0/topup/charge";
		$data_send = [
			"apiKey" => $this->KEY,
			"checksum" => $checksum,
			"data" => $param
		];
		$result = $this->call_api($data_send, $url);
		return $result;
	}

	public function find_user($param)
	{
		$dataJson = json_encode($param);
		$checksum = md5($dataJson . "+" . $this->SECRET);
		$url = $this->URL . "/api/v1.0/topup/find-user-by-code";
		$data_send = [
			"apiKey" => $this->KEY,
			"checksum" => $checksum,
			"data" => $param
		];
		$result = $this->call_api($data_send, $url);
		return $result;
	}

	public function history_recharge($param)
	{
		$dataJson = json_encode($param);
		$checksum = md5($dataJson . "+" . $this->SECRET);
		$url = $this->URL . "/api/v1.0/topup/list-transaction";
		$data_send = [
			"apiKey" => $this->KEY,
			"checksum" => $checksum,
			"data" => $param
		];
		$result = $this->call_api($data_send, $url);
		return $result;
	}

	public function call_api($data_send, $url)
	{
		$headers = array(
			'Content-Type: application/json'
		);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data_send));
		$response = curl_exec($curl);
		$data = json_decode($response);

		return $data;
	}
}

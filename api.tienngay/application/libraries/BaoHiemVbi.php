<?php

defined('BASEPATH') or exit('No direct script access allowed');

class BaoHiemVbi
{
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('config');

		$this->PRIVATE_KEY = $this->ci->config->item('private_key_vbi');
		$this->URL = $this->ci->config->item('url_vbi_tnds');
		$this->VBI_CODE = $this->ci->config->item('VBI_CODE');
		$this->NSD = $this->ci->config->item('nsd_vbi_tnds');
		$this->DOI_TAC = $this->ci->config->item('doi_tac');
	}

	public function call_api($data_send, $url)
	{
		$headers = array(
			'Content-Type: application/json',
			'Accept: application/json',
			"Authority: $this->VBI_CODE"
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data_send, JSON_UNESCAPED_UNICODE));
		$response = curl_exec($curl);
		$data = json_decode($response);
		return $data;
	}

	public function danh_sach_bh_utv()
	{
		$url = $this->URL . '/api/p/list_insurance_package?nhom='.$this->DOI_TAC.'&nv=UTV';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		$data = json_decode($response);

		return $data;
	}

	public function tao_don_bh_utv($param)
	{
		$url = $this->URL . "/api/p/3";
		$result = $this->call_api($param, $url);
		return $result;
	}

	public function danh_sach_bh_sxh()
	{
		$url = $this->URL . '/api/p/list_insurance_package?nhom='.$this->DOI_TAC.'&nv=CN.9';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		$data = json_decode($response);

		return $data;
	}

	public function tao_don_bh_sxh($param)
	{
		$url = $this->URL . "/api/p/3";
		$result = $this->call_api($param, $url);
		return $result;
	}
}

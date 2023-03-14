<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Vbi_tnds_oto
{
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('config');

		$this->KEY = $this->ci->config->item('vbi_api_common_key');
		$this->DOI_TAC = $this->ci->config->item('doi_tac');
		$this->URL = $this->ci->config->item('url_vbi_tnds');
		$this->VBI_CODE = $this->ci->config->item('VBI_CODE');
	}

	private $KEY, $DOI_TAC, $BIEU_PHI, $URL, $VBI_CODE;

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
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data_send,JSON_UNESCAPED_UNICODE));
		$response = curl_exec($curl);
		$data = json_decode($response);
		return $data;
	}

	public function tao_don($param)
	{
		$url = $this->URL . "/api/xe/xe_nhap";
		$result = $this->call_api($param, $url);
		return $result;
	}

	public function tinh_phi($param)
	{
		$url = $this->URL . "/api/xe/tinh_phi";
		$result = $this->call_api($param, $url);
		return $result;
	}

	public function danh_muc_xe()
	{
		$param = [
			'vbi_api_common_key' => $this->KEY,
			'doi_tac' => $this->DOI_TAC
		];
		$url = $this->URL . "/api/xe/danh_muc";
		$result = $this->call_api($param, $url);
		return $result;
	}
}

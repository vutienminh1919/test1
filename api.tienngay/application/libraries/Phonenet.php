<?php


class Phonenet
{
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->config->load('config');
		$this->brandname_sms_phonenet = $this->ci->config->item("brandname_sms_phonenet");
		$this->template_phonenet = $this->ci->config->item("template_phonenet");
		$this->url_phonenet = $this->ci->config->item("url_phonenet");
		$this->accessKey = $this->ci->config->item("access_key_phonenet");
	}

	public function api_phonenet($post = '', $data_post = "", $get = "")
	{
		$service = $this->url_phonenet . $get;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $service);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $post);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'token:' . $this->accessKey));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$result1 = json_decode($result);
		return $result1;
	}

	public function send_sms_voice_otp($number_phone, $otp)
	{
		$data_sms = array(
			'brandName' => $this->brandname_sms_phonenet,
			'template' => $this->template_phonenet,
			'number' => $number_phone,
			'content' => (string)$otp

		);
		$data=[];
		$res = $this->api_phonenet('POST', json_encode($data_sms), '/sms');
		if (isset($res->sendError) && $res->sendError == true) {
			$data['response_update'] = $res;
			$data['type'] = 'SMS_VOICE';
			$data['data_sms'] = $data_sms ;
			$data['status'] = 'fail';
			return $data;
		} else {
			$data['response_update'] = $res;
			$data['type'] = 'SMS_VOICE';
			$data['data_sms'] = $data_sms ;
			$data['status'] = 'ok';
			return $data;
		}
	}
}

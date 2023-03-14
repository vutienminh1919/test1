<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * FCM simple server side implementation in PHP
 *
 * @author Abhishek
 */
class Fcm
{

	/** @var string     push message title */
	private $title;

	/** @var string     message */
	private $message;

	/** @var string     URL String */
	private $image;

	/** @var array     Custom payload */
	private $data;
	private $type;
	public $contract_id;
	private $badge;
	private $lead_id;
	private $lead_phone_number;
	private $click_action;

//	public function __construct()
//	{
//		$this->ci =& get_instance();
//		$this->ci->config->load('config');
//		$this->FCM_URL = $this->ci->config->item("FCM_URL");
//		$this->FIREBASE_CPANEL = $this->ci->config->item("FIREBASE_CPANEL");
//
//	}
	/**
	 * flag indicating whether to show the push notification or not
	 * this flag will be useful when perform some opertation
	 * in background when push is recevied
	 */

	/**
	 * @param mixed $badge
	 */
	public function setBadge($badge)
	{
		$this->badge = $badge;
	}

	/** @var bool     set background or not */
	private $is_background;

	/**
	 * Function to set the title
	 *
	 * @param string $title The title of the push message
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Function to set the message
	 *
	 * @param string $message Message
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * Function to set the image (optional)
	 *
	 * @param string $imageUrl URI string of image
	 */
	public function setImage($imageUrl)
	{
		$this->image = $imageUrl;
	}

	/**
	 * Function to set the custom payload (optional)
	 *
	 * eg:
	 *      $payload = array('user' => 'user1');
	 *
	 * @param array $data Custom data array
	 */
	public function setPayload($data)
	{
		$this->data = $data;
	}

	/**
	 * Function to specify if is set background (optional)
	 *
	 * @param bool $is_background
	 */
	public function setIsBackground($is_background)
	{
		$this->is_background = $is_background;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @param mixed $contract_id
	 */
	public function setContractId($contract_id)
	{
		$this->contract_id = $contract_id;
	}
	public function setLeadId($lead_id)
	{
		$this->lead_id = $lead_id;
	}

	public function setClickAction($click_action)
	{
		$this->click_action = $click_action;
	}


	/**
	 * Generating the push message array
	 *
	 * @return array  array of the push notification data to be send
	 */
	public function getMessage()
	{
		$res = array();
		$res['title'] = $this->title;
		$res['body'] = $this->message;
		$res['icon'] = 'https://service.egate.global/uploads/avatar/1625018369-88a4c5c5bbe6c321934ad7b9ecd5b25e.png';
		$res['badge'] = $this->badge;
		$res['click_action'] = $this->click_action;
		return $res;
	}

	public function getData()
	{
		$res = array();
		$res['type'] = $this->type;
		$res['contract_id'] = $this->contract_id;
		return $res;
	}

	public function getDataLead($lead_phone_number)
	{
		$res = array();
		$res['lead_id'] = $this->lead_id;
		$res['link'] = 'https://cpanel.tienngay.vn/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
//		$res['link'] = 'https://sandboxcpanel.tienngay.vn/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
//		$res['link'] = 'http://localhost/tienngay/cpanel.tienngay/lead_custom?fdate=&tdate=&tab=4&sdt='. $lead_phone_number;
		$res['click_action'] = $this->click_action;
		return $res;
	}

	public function getPush()
	{
		$res = array();
		$res['data']['title'] = $this->title;
		$res['data']['is_background'] = $this->is_background;
		$res['data']['message'] = $this->message;
		$res['data']['image'] = $this->image;
		$res['data']['payload'] = $this->data;
		$res['data']['timestamp'] = date('Y-m-d G:i:s');
		return $res;
	}

	/**
	 * Function to send notification to a single device
	 *
	 * @param string $to registration id of device (device token)
	 * @param array $message push notification array returned from getPush()
	 * @param string $os platform type
	 *
	 * @return  array   array of notification data and to address
	 */
	public function send($to, $message, $os = "ios")
	{
		if ($os == "ios") {
			$fields = array(
				'to' => $to,
				'data' => $message,
			);
		} else {
			$fields = array(
				'to' => $to,
				'notification' => $message,
			);
		}

		return $this->sendPushNotification($fields);
	}

	/**
	 * Function to send notification to a topic by topic name
	 *
	 * @param string $to topic
	 * @param array $message push notification array returned from getPush()
	 * @param string $os platform type
	 *
	 * @return  array   array of notification data and to address (topic)
	 */
	public function sendToTopic($to, $message, $data)
	{
		$fields = [
			'to' => $to,
			'notification' => $message,
			'data' => $data
		];
		return $this->sendPushNotification($fields);
	}

	/**
	 * Function to send notification to multiple users by firebase registration ids
	 *
	 * @param array $to array of registration ids of devices (device tokens)
	 * @param array $message push notification array returned from getPush()
	 * @param string $os platform type
	 *
	 * @return  array   array of notification data and to addresses
	 */
	public function sendMultiple($registration_ids, $message, $data)
	{
		$fields = array(
			'registration_ids' => $registration_ids,
			'notification' => $message,
			'data' => $data

		);
		return $this->sendPushNotification($fields);
	}

	/**
	 * Function makes curl request to firebase servers
	 *
	 * @param array $fields array of registration ids of devices (device tokens)
	 *
	 * @return  string   returns result from FCM server as json
	 */
	private function sendPushNotification($fields)
	{

		$CI = &get_instance();
		$CI->load->config('fcm_config'); //loading of config file

		// Set POST variables
		$url = $CI->config->item('fcm_url');


		$headers = array(
			'Authorization: key=' . $CI->config->item('key'),
			'Content-Type: application/json',
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		if ($result === false) {
			die('Curl failed: ' . curl_error($ch));
		}

		// Close connection
		curl_close($ch);

		return $result;
	}

	public function sendToTopicCpanel($to, $message, $data)
	{
		$fields = [
			'to' => $to[0],
//			'notification' => $message,
			'data' => $data,
		];
		return $this->sendPushNotificationCpanel($fields);
	}

	private function sendPushNotificationCpanel($fields)
	{

		$CI = &get_instance();
		$CI->load->config('config'); //loading of config file

		// Set POST variables
		$url = $CI->config->item('FCM_URL');
		$headers = array(
			'Authorization: key=' . $CI->config->item('FIREBASE_CPANEL'),
			'Content-Type: application/json',
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		if ($result === false) {
			die('Curl failed: ' . curl_error($ch));
		}

		// Close connection
		curl_close($ch);

		return $result;
	}


}

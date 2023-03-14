<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/NL_Withdraw.php';

class Lead extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('contract_model');
		$this->load->model('role_model');
		$this->load->model('kpi_pgd_model');
		$this->load->model('kpi_gdv_model');
		$this->load->model('store_model');
		$this->load->model('user_model');
		$this->load->model('bao_hiem_pgd_model');
		$this->load->model('report_kpi_model');
		$this->load->model('report_kpi_user_model');
		$this->load->model('report_kpi_top_user_model');
		$this->load->model('report_kpi_top_pgd_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('transaction_model');
		$this->load->model('group_role_model');
		$this->load->model("lead_model");
		$this->load->model("area_model");
		$this->load->model("recording_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->load->model('contract_tempo_model');
		$this->load->model('log_accesstrade_model');


	}
     public function update_da_tat_toan()
	{
		$contractData = $this->contract_model->find_where_select(array('status' => 19),['code_contract','customer_infor']);
		foreach ($contractData as $key => $c) {
			$ck = $this->lead_model->findOne(array( "contract_id"=>(string)$c['_id'],'da_tat_toan' => "yes"));
			if(empty($ck))
			{
			   if (isset($c['customer_infor']['customer_phone_number']) && !empty($c['customer_infor']['customer_phone_number']))
			   {
				$leadDB = $this->lead_model->findOne(array("phone_number" => $c['customer_infor']['customer_phone_number']));
				if ($c['customer_infor']['customer_phone_number'] && !empty($c['customer_infor']['customer_phone_number']) && !empty($leadDB)) {
					$this->lead_model->update(
						array("_id" => $leadDB['_id'] ),
						array('da_tat_toan' => "yes",
							  "contract_id"=>(string)$c['_id']
					    )
					);
					print($c['code_contract'] . '</br>');
				}else{
				$this->lead_model->insert(
						
						array(
							'fullname' => $c['customer_infor']['customer_name'],
							'phone_number' => $c['customer_infor']['customer_phone_number'],
							'da_tat_toan' => "yes",
						    "contract_id"=>(string)$c['_id'],
						    "created_at"=>(int)$c['created_at'],
						    "created_by"=>$c['created_by']
					)
					);
					print($c['code_contract'] . '</br>');
			}
			}
		}
			
		}
		return 'OK';
	}

	public function update_status_pgd()
	{
		$contractData = $this->contract_model->find_where(array('status' => array('$gte' => 17), 'customer_infor.id_lead' => array('$ne' => ""), 'customer_infor.id_lead' => array('$exists' => true)));
		foreach ($contractData as $key => $c) {
			if (!empty($c['customer_infor']['id_lead'])) {
				$leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($c['customer_infor']['id_lead'])));
				if ($leadDB['phone_number'] == $c['customer_infor']['phone_number']) {
					$this->lead_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($c['customer_infor']['id_lead'])),
						array('status_pgd' => "18")
					);
					print($c['code_contract'] . '</br>');
				}
			}
		}
		return 'OK';
	}

    public function get_recording_date()
    {
    	$from=(isset($_POST['from'])) ? $_POST['from'] : strtotime( date('Y-m-d'))*1000;
    	$to=(isset($_POST['to'])) ? $_POST['to'] : strtotime( date('Y-m-d')." +1 days")*1000;
    	$res= $this->api_phonenet('GET','',"/call?page=1&pageSize=100000000000&from=".$from."&to=".$to);
    	if(!empty($res->totalDocs) && $res->totalDocs >0)
    	{

    		foreach ($res->docs as $key => $dataPost) {
    			
    	$_id= isset($dataPost->_id) ? $dataPost->_id : '';
        $host= isset($dataPost->host) ? $dataPost->host : '';
        $client= isset($dataPost->client) ? $dataPost->client : '';
        $gateway= isset($dataPost->gateway) ? $dataPost->gateway : '';
        $phoneNumber= isset($dataPost->phoneNumber) ? $dataPost->phoneNumber : '';
        $fromUser= isset($dataPost->fromUser) ? $dataPost->fromUser : '';
        $toUser= isset($dataPost->toUser) ? $dataPost->toUser : '';
        $fromGroup= isset($dataPost->fromGroup) ? $dataPost->fromGroup : '';
        $toGroup= isset($dataPost->toGroup) ? $dataPost->toGroup : '';
        $fromCallId= isset($dataPost->fromCallId) ? $dataPost->fromCallId : '';
        $toCallId= isset($dataPost->toCallId) ? $dataPost->toCallId : '';
        $direction= isset($dataPost->direction) ? $dataPost->direction : '';
        $fromExt= isset($dataPost->fromExt) ? $dataPost->fromExt : '';
        $fromNumber= isset($dataPost->fromNumber) ? $dataPost->fromNumber : '';
        $toNumber= isset($dataPost->toNumber) ? $dataPost->toNumber : '';
        $startTime= isset($dataPost->startTime) ? $dataPost->startTime : '';
        $answerTime= isset($dataPost->answerTime) ? $dataPost->answerTime : '';
        $endTime= isset($dataPost->endTime) ? $dataPost->endTime : '';
        $duration= isset($dataPost->duration) ? $dataPost->duration : '';
        $billDuration= isset($dataPost->billDuration) ? $dataPost->billDuration : '';
        $hangupCause= isset($dataPost->hangupCause) ? $dataPost->hangupCause : '';
        $createTime= isset($dataPost->createTime) ? $dataPost->createTime : '';
        $recording= isset($dataPost->recording) ? $dataPost->recording : '';
        $recordingDownloaded= isset($dataPost->recordingDownloaded) ? $dataPost->recordingDownloaded : '';
        $recordingSize= isset($dataPost->recordingSize) ? $dataPost->recordingSize : '';
        $recordingDeleted= isset($dataPost->recordingDeleted) ? $dataPost->recordingDeleted : '';
        $fromContact= isset($dataPost->fromContact) ? $dataPost->fromContact : '';
        $toContact= isset($dataPost->toContact) ? $dataPost->toContact : '';
        $ticketLog= isset($dataPost->ticketLog) ? $dataPost->ticketLog : '';
		$toExt = isset($dataPost->toExt) ? $dataPost->toExt : "";
        
        $createdAt = time();
        
         $data = array(
            "code"=>(string)$_id,
            "host"=>(string)$host,
            "client"=>(string)$client,
            "gateway"=>(string)$gateway,
            "phoneNumber"=>(string)$phoneNumber,
            "fromUser"=>$fromUser,
            "toUser"=>$toUser,
            "fromGroup"=>$fromGroup,
            "toGroup"=>$toGroup,
            "fromCallId"=>(string)$fromCallId,
            "toCallId"=>(string)$toCallId,
            "direction"=>(string)$direction,
            "fromExt"=>(string)$fromExt,
            "fromNumber"=>(string)$fromNumber,
            "toNumber"=>(string)$toNumber,
            "startTime"=>(string)$startTime,
            "answerTime"=>(string)$answerTime,
            "endTime"=>(string)$endTime,
            "duration"=>(string)$duration,
            "billDuration"=>(string)$billDuration,
            "hangupCause"=>(string)$hangupCause,
            "createTime"=>(string)$createTime,
            "recording"=>(string)$recording,
            "recordingDownloaded"=>(string)$recordingDownloaded,
            "recordingSize"=>(string)$recordingSize,
            "recordingDeleted"=>(string)$recordingDeleted,
            "fromContact"=>(string)$fromContact,
            "toContact"=>(string)$toContact,
            "ticketLog"=>(string)$ticketLog,
            "created_at" => (string)$createdAt,
            "updated_at" => (string)$createdAt,
            "created_by" => 'phonenet',
            "updated_by" => '',
            'status'=>'active',
			'toExt' => (string)$toExt

        );
        $recording = $this->recording_model->findOne(array("code" => $_id));
       // var_dump($recording); die;
        if(empty($recording))
        {
        	$this->recording_model->insert($data);
        	var_dump($_id);
        }
       }
    }
    }
      private function api_phonenet($post='',$data_post="",$get=""){
        $url_phonenet=$this->config->item("url_phonenet");
        $accessKey=$this->config->item("access_key_phonenet");
        $service = $url_phonenet.$get;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$service);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $post);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json','token:'.$accessKey));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($ch);
        curl_close ($ch);
        $result1 = json_decode($result);
        return $result1;
    }
	public function getAllListAT()
	{

		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$data = $this->input->post();

		$start = date('Y-m-d');
		$end = date('Y-m-d');

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start) . ' 00:00:00'),
				'end' => strtotime(trim($end) . ' 23:59:59'));
		}

		$leads = $this->lead_model->getByRole_AT_list($condition);


		if (!empty($leads)) {
			foreach ($leads as $key => $value) {


				$check_conversion = $this->log_accesstrade_model->findOne(["conversion_id" => (string)$value->_id]);

				$check_leads = $this->log_accesstrade_model->findOne(["phone_number" => $value['phone_number']]);
				unset($result_tracking);

				if (!empty($value->utm_campaign)) {
					$tracking_id = explode("=", $value->utm_campaign);
					$result_tracking = $tracking_id[2];
					if (count($tracking_id) >3){
						$result_tracking_1 = explode("&",$tracking_id[2]);
						$result_tracking = $result_tracking_1[0];
					}
				}

				if (empty($check_conversion)){
					$data1 = array(

						"conversion_id" => !empty($value->_id) ? (string)$value->_id : "",

						"conversion_result_id" => "30",

						"tracking_id" => !empty($result_tracking) ? $result_tracking : "",

						"transaction_id" => !empty($value->_id) ? (string)$value->_id : "",

						"transaction_time" => !empty($value["created_at"]) ? date('Y-m-d\TH:i:s.Z\Z', $value["created_at"]) : "",

						"transaction_value" => 0,

						"is_cpql" => 1,

						"items" => []

					);

					$data_string = json_encode($data1);

					$ch = curl_init('https://api.accesstrade.vn/v1/postbacks/conversions');

					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

					curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_HTTPHEADER, array(

						'Content-Type: application/json',

						'Authorization: Token fn1-vtdKGhR3afT1eJ3qw3XS9N3yv78K'

					));

					$result = curl_exec($ch);
					var_dump($result);

					$data = array(

						"conversion_id" => !empty($value->_id) ? (string)$value->_id : "",

						"conversion_result_id" => "30",

						"tracking_id" => !empty($tracking_id[2]) ? $tracking_id[2] : "",

						"phone_number" => !empty($value['phone_number']) ? $value['phone_number'] : "",

						"transaction_id" => !empty($value->_id) ? (string)$value->_id : "",

						"transaction_time" => !empty($value["created_at"]) ? date('Y-m-d\TH:i:s.Z\Z', $value["created_at"]) : "",

						"transaction_value" => 0,

						"is_cpql" => 1,

						"items" => []

					);
					//insert log
					$this->log_accesstrade_model->insert($data);

					if (!empty($check_leads)) {
						$data2 = array(
							"transaction_id" => !empty($value->_id) ? (string)$value->_id : "",
							"status" => 2,
							"rejected_reason" => "Trùng số điện thoại",
							"items" => []

						);

						$data_string = json_encode($data2);

						$ch = curl_init('https://api.accesstrade.vn/v1/postbacks/conversions');

						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

						curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

						curl_setopt($ch, CURLOPT_HTTPHEADER, array(

							'Content-Type: application/json',

							'Authorization: Token fn1-vtdKGhR3afT1eJ3qw3XS9N3yv78K'

						));

						$result = curl_exec($ch);
						var_dump($result);

						//insert log
						$this->log_accesstrade_model->insert($data2);
					}

				}
				
			}
		}
	}
	public function update_status_lead_have_contract()
	{
		$contractData = $this->contract_model->find_where(array('status' => array('$gte' => 17), 'customer_infor.id_lead' => array('$exists' => true)));
		foreach ($contractData as $key => $c) {
			if (!empty($c['customer_infor']['id_lead'])) {
				$leadDB = $this->lead_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($c['customer_infor']['id_lead'])));
				if ((string)$leadDB['_id'] == $c['customer_infor']['id_lead']) {
					$this->lead_model->update(
						array("_id" => new MongoDB\BSON\ObjectId($c['customer_infor']['id_lead'])),
						array('status_pgd' => "18")
					);
					print($c['code_contract'] . '</br>');
				}
			}
		}
		return 'OK';
	}


}

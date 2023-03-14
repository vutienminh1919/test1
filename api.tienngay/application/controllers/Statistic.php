<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Statistic extends REST_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model("gic_model");
		$this->load->model('mic_model');
		$this->load->model("gic_easy_model");
		$this->load->model("gic_plt_model");
		$this->load->model("contract_model");
		$this->load->model("vbi_model");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
		// if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
		// 	$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
		// 	$token = Authorization::validateToken($headers_item);
		// 	if ($token != false) {
		// 		// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
		// 		$this->app_login = array(
		// 			'_id'=>new \MongoDB\BSON\ObjectId($token->id),
		// 			'email'=>$token->email,
		// 			"status" => "active"
		// 		);
		// 		//Web
		// 		if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
		// 		if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
		// 		$count_account = $this->user_model->count($this->app_login);
		// 		$this->flag_login = 'success';
		// 		if ($count_account != 1) $this->flag_login = 2;
		// 		if ($count_account == 1){
		// 			$this->info = $this->user_model->findOne($this->app_login);
		// 			$this->id = $this->info['_id'];
		// 			$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
		// 			$this->uemail = $this->info['email'];

		// 			// Get access right
		// 			$roles = $this->role_model->getRoleByUserId((string)$this->id);
		// 			$this->roleAccessRights = $roles['role_access_rights'];
		// 			$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
		// 		}
		// 	}
		// }
		unset($this->dataPost['type']);

	}
	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

	

	public function search_post(){
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;
		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : date('Y-m-01');
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : date('Y-m-d');
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
		}
	
		// $data_dashboard['contract']['contract_total']  = $contract->count(array("created_at"=>$condition,));
		$gic = new Gic_model();
		$mic = new Mic_model();
		$gic_easy = new Gic_easy_model();
		$gic_plt = new Gic_plt_model();
		$contract =  new Contract_model();
        $data_dashboard= array();
        $vbi = new Vbi_model();
        //insurrance_kv
         //count
		$data_dashboard['insurrance_kv']['total_mic'] = $mic->count(array("created_at"=>$condition,"type_mic"=>"MIC_TDCN"));
		$data_dashboard['insurrance_kv']['total_gic'] = $gic->count(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC"=> array('$exists' => true)));
		$data_dashboard['insurrance_kv']['total'] = $data_dashboard['insurrance_kv']['total_mic']+$data_dashboard['insurrance_kv']['total_gic'];
		 //money
		$data_dashboard['insurrance_kv']['total_money_mic'] = $mic->sum_where(array("created_at"=>$condition,"type_mic"=>"MIC_TDCN","contract_info.loan_infor.amount_money"=> array('$exists' => true),"type_mic"=>"MIC_TDCN","contract_info.loan_infor.amount_money"=> array('$ne' => "")),'$contract_info.loan_infor.amount_money');
		 $data_dashboard['insurrance_kv']['total_money_gic'] = $gic->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_money"=> array('$exists' => true),"contract_info.loan_infor.amount_money"=> array('$ne' => "")),'$contract_info.loan_infor.amount_money');
		 $data_dashboard['insurrance_kv']['total_money'] = $data_dashboard['insurrance_kv']['total_money_mic']+$data_dashboard['insurrance_kv']['total_money_gic'];
		//  //fee
		$data_dashboard['insurrance_kv']['total_fee_mic'] = $mic->sum_where(array("created_at"=>$condition,"type_mic"=>"MIC_TDCN","mic_fee"=> array('$exists' => true),"mic_fee"=> array('$ne' => "")),'$mic_fee');
		$data_dashboard['insurrance_kv']['total_fee_gic'] = $gic->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC"=> array('$exists' => true),"contract_info.loan_infor.amount_GIC"=> array('$ne' => "")),'$contract_info.loan_infor.amount_GIC');
		$data_dashboard['insurrance_kv']['total_fee'] = $data_dashboard['insurrance_kv']['total_fee_mic']+$data_dashboard['insurrance_kv']['total_fee_gic'];
		//insurrance_plt
         //count
		
		$data_dashboard['insurrance_plt']['total_gic'] =$gic_plt->count(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC_plt"=> array('$exists' => true)));
		$data_dashboard['insurrance_plt']['total'] =$data_dashboard['insurrance_plt']['total_gic'];
		 //money
		
		 $data_dashboard['insurrance_plt']['total_money_gic'] = $gic_plt->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_money"=> array('$exists' => true),"contract_info.loan_infor.amount_money"=> array('$ne' => "")),'$contract_info.loan_infor.amount_money');
		 $data_dashboard['insurrance_plt']['total_money'] = $data_dashboard['insurrance_plt']['total_money_gic'];
		//  //fee
		
		$data_dashboard['insurrance_plt']['total_fee_gic'] = $gic_plt->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC_plt"=> array('$exists' => true),"contract_info.loan_infor.amount_GIC_plt"=> array('$ne' => "")),'$contract_info.loan_infor.amount_GIC_plt');
		$data_dashboard['insurrance_plt']['total_fee'] = $data_dashboard['insurrance_plt']['total_fee_gic'];
		//insurrance_easy
         //count
		
		$data_dashboard['insurrance_easy']['total_gic'] =$gic_easy->count(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC_easy"=> array('$exists' => true)));
		$data_dashboard['insurrance_easy']['total'] =$data_dashboard['insurrance_easy']['total_gic'];
		 //money
		
		 $data_dashboard['insurrance_easy']['total_money_gic'] = $gic_easy->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_money"=> array('$exists' => true),"contract_info.loan_infor.amount_money"=> array('$ne' => "")),'$contract_info.loan_infor.amount_money');
		 $data_dashboard['insurrance_easy']['total_money'] = $data_dashboard['insurrance_easy']['total_money_gic'];
		//  //fee
		
		$data_dashboard['insurrance_easy']['total_fee_gic'] = $gic_easy->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_GIC_easy"=> array('$exists' => true),"contract_info.loan_infor.amount_GIC_easy"=> array('$ne' => "")),'$contract_info.loan_infor.amount_GIC_easy');
		$data_dashboard['insurrance_easy']['total_fee'] = $data_dashboard['insurrance_easy']['total_fee_gic'];
		

//		//count
		$data_dashboard['insurrance_vbi']['total_vbi'] =$vbi->count(array("created_at"=>$condition,"contract_info.loan_infor.amount_VBI"=> array('$exists' => true)));
		$data_dashboard['insurrance_vbi']['total'] =$data_dashboard['insurrance_vbi']['total_vbi'];
//
//
//		//money
		$data_dashboard['insurrance_vbi']['total_money_vbi'] = $vbi->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_money"=> array('$exists' => true),"contract_info.loan_infor.amount_money"=> array('$ne' => "")),'$contract_info.loan_infor.amount_money');
		$data_dashboard['insurrance_vbi']['total_money'] = $data_dashboard['insurrance_vbi']['total_money_vbi'];
//
//		//fee
		$data_dashboard['insurrance_vbi']['total_fee_vbi'] = $vbi->sum_where(array("created_at"=>$condition,"contract_info.loan_infor.amount_VBI"=> array('$exists' => true),"contract_info.loan_infor.amount_VBI"=> array('$ne' => "")),'$contract_info.loan_infor.amount_VBI');
		$data_dashboard['insurrance_vbi']['total_fee'] = $data_dashboard['insurrance_vbi']['total_fee_vbi'];


		 //count
		$data_dashboard['insurrance']['total_vbi'] =$data_dashboard['insurrance_vbi']['total_vbi'];
		$data_dashboard['insurrance']['total_mic'] =$data_dashboard['insurrance_kv']['total_mic'];
		$data_dashboard['insurrance']['total_gic'] = $data_dashboard['insurrance_kv']['total_gic']+ $data_dashboard['insurrance_easy']['total_gic'] + $data_dashboard['insurrance_plt']['total_gic'];
		$data_dashboard['insurrance']['total'] = $data_dashboard['insurrance_kv']['total']+$data_dashboard['insurrance_easy']['total']+$data_dashboard['insurrance_plt']['total'] + $data_dashboard['insurrance']['total_vbi'] ;
		 //money
		$data_dashboard['insurrance']['total_money_mic'] =$data_dashboard['insurrance_kv']['total_money_mic'];
		$data_dashboard['insurrance']['total_money_vbi'] =$data_dashboard['insurrance_vbi']['total_money_vbi'];
		 $data_dashboard['insurrance']['total_money_gic'] = $data_dashboard['insurrance_kv']['total_money_gic']+ $data_dashboard['insurrance_easy']['total_money_gic'] + $data_dashboard['insurrance_plt']['total_money_gic'];
		 $data_dashboard['insurrance']['total_money'] = $data_dashboard['insurrance_kv']['total_money']+$data_dashboard['insurrance_easy']['total_money']+$data_dashboard['insurrance_plt']['total_money'] + $data_dashboard['insurrance']['total_money_vbi'] ;
		//  //fee
		$data_dashboard['insurrance']['total_fee_mic'] =$data_dashboard['insurrance_kv']['total_fee_mic'];
		$data_dashboard['insurrance']['total_fee_vbi'] =$data_dashboard['insurrance_vbi']['total_fee_vbi'];
		$data_dashboard['insurrance']['total_fee_gic'] = $data_dashboard['insurrance_kv']['total_fee_gic']+ $data_dashboard['insurrance_easy']['total_fee_gic'] + $data_dashboard['insurrance_plt']['total_fee_gic'];
		$data_dashboard['insurrance']['total_fee'] =$data_dashboard['insurrance_kv']['total_fee']+$data_dashboard['insurrance_easy']['total_fee']+$data_dashboard['insurrance_plt']['total_fee'] +$data_dashboard['insurrance']['total_fee_vbi'] ;




		if($data_dashboard){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_dashboard,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
	    }
	}

	




}

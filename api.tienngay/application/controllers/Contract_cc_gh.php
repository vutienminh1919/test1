<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/VPBank.php';


use Restserver\Libraries\REST_Controller;

class Contract_cc_gh extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model("transaction_model");
		$this->load->model('fee_loan_model');
		$this->load->model('contract_model');
		$this->load->model('gic_model');
		$this->load->model('mic_model');
		$this->load->model('gic_easy_model');
		$this->load->model('gic_plt_model');
		$this->load->model('log_contract_model');
		$this->load->model('log_model');
		$this->load->model('log_gic_model');
		$this->load->model('log_mic_model');
		$this->load->model('user_model');
		$this->load->model('role_model');
		$this->load->model('config_gic_model');
		$this->load->model('city_gic_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('investor_model');
		$this->load->model("group_role_model");
		$this->load->model("notification_model");
		$this->load->model("notification_app_model");
		$this->load->model("store_model");
		$this->load->model("lead_model");
		$this->load->model("sms_model");
		$this->load->model("temporary_plan_contract_model");
		$this->load->model('log_contract_tempo_model');
		$this->load->model('tempo_contract_accounting_model');
		$this->load->model('dashboard_model');
		$this->load->model('coupon_model');
		$this->load->model('verify_identify_contract_model');
		$this->load->model('device_model');
		$this->load->helper('lead_helper');
		$this->load->model('vbi_model');
		$this->load->model('log_hs_model');
		$this->load->model('log_call_debt_model');
		$this->load->model('contract_extend_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$headers = $this->input->request_headers();
		$this->flag_login = 1;
		$this->superadmin = false;
		$this->dataPost = $this->input->post();
		if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
			$headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
			$token = Authorization::validateToken($headers_item);
			if ($token != false) {
				// Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
				$this->app_login = array(
					'_id' => new \MongoDB\BSON\ObjectId($token->id),
					'email' => $token->email,
					"status" => "active",
					// "is_superadmin" => 1
				);
				//Web
				if ($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
				if ($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
				$count_account = $this->user_model->count($this->app_login);
				$this->flag_login = 'success';
				if ($count_account != 1) $this->flag_login = 2;
				if ($count_account == 1) {
					$this->info = $this->user_model->findOne($this->app_login);
					$this->id = $this->info['_id'];
					$this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
					$this->uemail = $this->info['email'];

					// Get access right
					$roles = $this->role_model->getRoleByUserId((string)$this->id);
					$this->roleAccessRights = $roles['role_access_rights'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
				}
			}
		}
		unset($this->dataPost['type']);


	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;
	public function check_update_type_payment_post()
	{
      $flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = array();
		$condition['code_contract'] =$data['code_contract'];
		$condition['type_payment']=2;
		$tran_list=$this->transaction_model->find_where($condition);
		foreach ($tran_list as $key => $value) {
			$this->transaction_model->update(
						array("_id" =>  $value['_id']),
						array(
							"type_payment" => 1
							)
					);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function get_list_gh_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = array();
		if (!empty($data['id_contract'])) {
			$condition['id_contract'] = new MongoDB\BSON\ObjectId($data['id_contract']);

		}
		$one_ct=$this->contract_model->findOne(array("_id" => $condition['id_contract']));
		$condition['code_contract_parent_gh']=(isset($one_ct['code_contract_parent_gh'])) ? $one_ct['code_contract_parent_gh'] : '';
		$condition['code_contract']=(isset($one_ct['code_contract'])) ? $one_ct['code_contract'] : '';
		$contract = $this->contract_extend_model->get_list_gh($condition);
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	public function debt_recovery_one()
	{

		$c = $this->contract_model->findOne(array('code_contract' => $code_contract));
           
            if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        
                    );
                }
       $da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$c['code_contract'],'status'=>1,'type'=>array('$in'=>[4,5])),'$total');
        $da_thanh_toan_gh_cc=$this->transaction_model->findOne(array('code_contract'=>$c['code_contract'],'status'=>array('$ne'=>3),'type_payment'=>array('$gt'=>1) , "type"=>4));
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_paid_1ky = 0;
                    $total_goc = 0;
                    $total_lai = 0;
                    $total_phi = 0;
                    $total_da_thanh_toan = 0;
                    $total_phi_phat_cham_tra = 0;
                    $time =0;
                    $n=0;
                    $ky_tt_xa_nhat = 0;
                    $ky_tt_xa_nhi = 0;
                    $thoi_han_vay = 0;
                     $lai_uoc_tinh = 0;
                    $phi_uoc_tinh = 0;

                    foreach ($detail as $de) {

                        $total_paid += (isset($de['tien_tra_1_ky'])) ? $de['tien_tra_1_ky'] : 0;
                        $total_goc +=  (isset($de['tien_goc_1ky_con_lai'])) ? $de['tien_goc_1ky_con_lai'] : 0;
                        $total_phi +=  (isset($de['tien_phi_1ky_con_lai'])) ? $de['tien_phi_1ky_con_lai'] : 0;
                        $total_lai +=  (isset($de['tien_lai_1ky_con_lai'])) ? $de['tien_lai_1ky_con_lai'] : 0;
                         $lai_uoc_tinh +=  (isset($de['tien_lai_1ky_phai_tra'])) ? $de['tien_lai_1ky_phai_tra'] : 0;
                        $phi_uoc_tinh +=  (isset($de['tien_phi_1ky_phai_tra'])) ? $de['tien_phi_1ky_phai_tra'] : 0;
                        $total_da_thanh_toan += $de['da_thanh_toan'];

                       if((count($detail)-1)==$de['ky_tra'])
                       $ky_tt_xa_nhi =$de['ngay_ky_tra'];

                       if(count($detail)==$de['ky_tra'])
                       $ky_tt_xa_nhat =$de['ngay_ky_tra'];

                     $thoi_han_vay=$thoi_han_vay+$de['so_ngay'];
                }
                if(empty($ky_tt_xa_nhi))
                 $ky_tt_xa_nhi=$c['disbursement_date']; 
               $time = 0;
              $current_day = strtotime(date('m/d/Y'));
              $datetime = $current_day;
              $detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $c['code_contract'], 'status' => 1]);
              if (!empty($detail)) {
              $datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
              $time = intval(($current_day - $datetime) / (24*60*60));
               }
                if($c['status']==33 ||  $c['status']==34  ||  $c['status']==19  ||  $c['status']==40)
                $time = 0;
               $penalty=$this->contract_model->get_phi_phat_cham_tra((string)$c['_id'], strtotime(date('Y-m-d').' 23:59:59'));
               $total_phi_phat_cham_tra=$penalty['penalty_now']+$penalty['tong_penalty_con_lai'];
               $check_gia_han=2;
               $check_tt_gh=0;
               $check_tt_cc=0;
              
                 if(!empty($da_thanh_toan_gh_cc))
                  {
                  if($da_thanh_toan_gh_cc['status']==1 && $da_thanh_toan_gh_cc['type_payment']==2)
                  {
                    //đã thanh toán gia hạn
                    $check_tt_gh=1;
                  }
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4]) && $da_thanh_toan_gh_cc['type_payment']==2)
                  {
                    //chờ thanh toán gia hạn
                    $check_tt_gh=2;
                  }
                    if($da_thanh_toan_gh_cc['status']==1 && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //đã thanh toán gia hạn
                    $check_tt_cc=1;
                  }
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4]) && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //chờ thanh toán gia hạn
                    $check_tt_cc=2;
                  }
                   
                  }
                   if( $current_day>=$ky_tt_xa_nhi && $c['loan_infor']['type_interest']==2  && (strtotime(date('Y-m-d').' 00:00:00') >=$c['disbursement_date']) && $check_tt_gh==0 ){
                //đủ yêu cầu gia hạn
                 $check_gia_han=1;
                  }
                  if( $current_day>=$ky_tt_xa_nhi && $c['loan_infor']['type_loan']['code']=='CC'  && (strtotime(date('Y-m-d').' 00:00:00') >=$c['disbursement_date']) && $c['loan_infor']['number_day_loan']=='30'  && $check_tt_gh==0){
                //đủ yêu cầu gia hạn
                 $check_gia_han=1;
                  }
                     $is_qua_han=0;
                   if(strtotime(date('Y-m-d').' 00:00:00') >$ky_tt_xa_nhat && in_array($c['status'],  [10,11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42])) {
                    $is_qua_han=1;

                   }
                       $data=[
                        'expire_date'=>$ky_tt_xa_nhat,
                        'debt'=>[
                        'current_day'=>$current_day,
                        'ngay_ky_tra'=>$datetime,
                         'so_ngay_cham_tra'=>$time,
                         'tong_tien_phai_tra'=>$total_paid,
                         'tong_tien_goc_con'=>$total_goc,
                         'tong_tien_phi_con'=>$total_phi,
                         'tong_tien_lai_con'=>$total_lai,
                         'tong_tien_cham_tra_con'=>$total_phi_phat_cham_tra,
                        'tong_tien_da_thanh_toan'=>$total_da_thanh_toan,
                        'tong_tien_da_thanh_toan_pt'=>$da_thanh_toan_pt,
                        'ky_tt_xa_nhat'=>$ky_tt_xa_nhat,
                        'ky_tt_xa_nhi'=>$ky_tt_xa_nhi+24*60*60,
                        'check_gia_han'=>$check_gia_han,
                        'check_tt_gh'=>$check_tt_gh,
                        'check_tt_cc'=>$check_tt_cc,
                        'thoi_han_vay' =>$thoi_han_vay,
                        'lai_uoc_tinh' =>$lai_uoc_tinh,
                        'phi_uoc_tinh' =>$phi_uoc_tinh,
                         'is_qua_han' =>$is_qua_han,
                        'run_date'=>date('d-m-Y H:i:s')
                          ]
                       ];
                    $this->contract_model->update(
                        array("_id" => $c['_id']),
                        $data
                    );
               }
        

		return 'OK';

	}
	public function get_list_cc_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$data = $this->input->post();
		$condition = array();
		if (!empty($data['id_contract'])) {
			$condition['id_contract'] = new MongoDB\BSON\ObjectId($data['id_contract']);
		}
		$one_ct=$this->contract_model->findOne(array("_id" => $condition['id_contract']));
		$condition['code_contract_parent_cc']=(isset($one_ct['code_contract_parent_cc'])) ? $one_ct['code_contract_parent_cc'] : '';
		$condition['code_contract']=(isset($one_ct['code_contract'])) ? $one_ct['code_contract'] : '';
		$contract = $this->contract_extend_model->get_list_cc($condition);
		if (empty($contract)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Hợp đồng không tồn tại"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}
	private function initNumberContractCode()
	{
		$maxNumber = $this->contract_model->getMaxNumberContract();
		$maxNumberContract = !empty($maxNumber[0]['number_contract']) ? (float)$maxNumber[0]['number_contract'] + 1 : 1;
		$res = array(
			"max_number_contract" => $maxNumberContract
		);
		return $res;
	}
	public function approve_gia_han_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$number_day_loan = $this->security->xss_clean($this->dataPost['number_day_loan']);
		$inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])));
		if (empty($inforDB)) return;
		$code_contract_last=$inforDB['code_contract'];
		$KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
		$ngay_gia_han=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
		$gh_da_tt=$this->transaction_model->findOne(array('code_contract'=>$inforDB['code_contract'],'status'=>1,'type_payment'=>2));
      
		$tien_goc_con_lai = $this->temporary_plan_contract_model->tien_goc_con_lai($inforDB['code_contract']);
		$investorData = $this->investor_model->findOne(array("_id" => $inforDB['investor_infor']['_id']));
		$count_extension = !empty($inforDB['count_extension']) ? $inforDB['count_extension'] : 0;
		$origin_code_contract = !empty($inforDB['code_contract_parent_gh']) ? $inforDB['code_contract_parent_gh'] : $inforDB['code_contract'];
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['count_extension'] = $count_extension + 1;
		$inforDB['code_contract_parent_gh'] = $origin_code_contract;
		$inforDB['loan_infor']['number_day_loan'] =  !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['amount_money'] = !empty($tien_goc_con_lai) ? $tien_goc_con_lai : 0;
		$maxNumberContract = $this->initNumberContractCode();
		$inforDB['code_contract'] = "00000" . $maxNumberContract['max_number_contract'];
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['type_gh']=empty($inforDB['code_contract_parent_gh']) ? 'origin' : $inforDB['count_extension'];
		$inforDB['number_contract'] = (int)$maxNumberContract['max_number_contract'];
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
        
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
        $inforDB['extend_all'] = array();
		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['extend_date'] = $ngay_gia_han;
		$inforDB['disbursement_date'] = $ngay_gia_han;
        $contract_origin = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_parent_gh']));
        $inforDB['created_at'] = $this->createdAt;

		$this->contract_model->update(
			array("code_contract" => $origin_code_contract),
			array(
				"code_contract_child_gh.".$inforDB['count_extension'] => $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'],
				"extend_all.".$inforDB['count_extension']=>array('extend_date'=>$ngay_gia_han,'number_day_loan'=>$inforDB['loan_infor']['number_day_loan'],'so_lan'=>$inforDB['count_extension']),
				"type_gh"=>"origin"
			)
		);

		unset($inforDB['_id']);

		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
		if(empty($ck_contract ))
		{
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			$contract_originDB = $this->contract_model->findOne(array("code_contract" => $code_contract_last));
			//$this->insert_transaction_extent($contract_originDB);
			$contractId = $this->contract_model->insertReturnId($inforDB);

			/**
			*
			* VPBank assign the vitual account number for new contract
			*/
			if ($contractId && isset($inforDB["code_contract"])) {
				$vpbank = new VPBank();
				$assignVan = $vpbank->assignVan($inforDB["code_contract"]);
			}
			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "create_contract_extension",
				"contract_id" => (string)$contractId,
				"old" => $inforDB,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			/**
			 * Save log to json file
			 */

			$insertLogNew = [
				"type" => "contract",
				"action" => "create_contract_extension",
				"contract_id" => (string)$contractId,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			];
			$log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
			$this->log_model->insert($insertLog);
			$insertLog['log_id'] = $log_id;

			$this->insert_log_file($insertLog, (string)$contractId);

			/**
			 * ----------------------
			 */

		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Extension contract success",
			'data' => $inforDB,
			'disbursement_date' => $inforDB['disbursement_date']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}

	public function approve_co_cau_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$this->dataPost['contract_id'] = $this->security->xss_clean($this->dataPost['contract_id']);
		$number_day_loan = $this->security->xss_clean($this->dataPost['number_day_loan']);

		$amount_money = $this->security->xss_clean($this->dataPost['amount_money']);

		$type_loan = $this->security->xss_clean($this->dataPost['type_loan']);
		$type_interest = $this->security->xss_clean($this->dataPost['type_interest']);

		$inforDB = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['contract_id'])));
		if (empty($inforDB)) return;

		$cc_da_tt=$this->transaction_model->findOne(array('code_contract'=>$inforDB['code_contract'],'status'=>1,'type_payment'=>3));
       $ngay_co_cau=(isset($cc_da_tt['date_pay'])) ? $cc_da_tt['date_pay'] : $this->createdAt;
       $KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
	
		 if($ngay_co_cau >$KiPhaiThanhToanXaNhat)
		 {
          $ngay_co_cau=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
		 }

		$count_structure= !empty($inforDB['count_structure']) ? $inforDB['count_structure'] : 0;
		$origin_code_contract = !empty($inforDB['code_contract_parent_cc']) ? $inforDB['code_contract_parent_cc'] : $inforDB['code_contract'];
		$inforDB['count_structure'] = $count_structure + 1;
		$inforDB['code_contract_parent_cc'] = $origin_code_contract;
		$inforDB['loan_infor']['type_interest'] =$type_interest;
		$inforDB['loan_infor']['number_day_loan'] = !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['amount_money'] = $amount_money;
		$arr_type_loan=array();
		if($type_loan=="DKX")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ee7a104d435e3b8ae66';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cho vay';
			$inforDB['loan_infor']['type_loan']['code'] = 'DKX';
		}else if($type_loan=="CC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ed2a104d435e3b8ae65';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cầm cố';
			$inforDB['loan_infor']['type_loan']['code'] = 'CC';
		}else if($type_loan=="TC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5fdf75fa6653056471f0b7fe';
			$inforDB['loan_infor']['type_loan']['text'] = 'Tín chấp';
			$inforDB['loan_infor']['type_loan']['code'] = 'TC';
		}


		$maxNumberContract = $this->initNumberContractCode();
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['code_contract'] = "00000" . $maxNumberContract['max_number_contract'];
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['type_cc']=empty($inforDB['code_contract_parent_cc']) ? 'origin' : $inforDB['count_structure'];
		$inforDB['number_contract'] = (int)$maxNumberContract['max_number_contract'];
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
		$inforDB['created_at'] = $this->createdAt;
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
        $inforDB['structure_all'] = array();
		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['structure_date'] = $ngay_co_cau;
		$inforDB['disbursement_date'] = $ngay_co_cau;
		$arrFee = $this->getFee($inforDB);
		if (empty($arrFee['fee'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Chưa lấy được biểu phí",
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$inforDB['fee'] = $arrFee['fee'];
		$inforDB['fee_id'] = $arrFee['id'];


		$this->contract_model->update(
			array("code_contract" => $origin_code_contract),
			array(
				"code_contract_child_cc.".$inforDB['count_structure'] => $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'],
				"structure_all.".$inforDB['count_structure']=>array('structure_date'=>$ngay_co_cau,'number_day_loan'=>$inforDB['loan_infor']['number_day_loan'],'amount_money'=>$inforDB['loan_infor']['amount_money'],'type_loan'=>$inforDB['loan_infor']['type_loan'],'so_lan'=>$inforDB['count_structure'],'type_interest'=>$inforDB['loan_infor']['type_interest']),
				"type_cc"=>"origin"

			)
		);

		unset($inforDB['_id']);
		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
		if(empty($ck_contract ))
		{
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			$contractId = $this->contract_model->insertReturnId($inforDB);

			/**
			*
			* VPBank assign the vitual account number for new contract
			*/
			if ($contractId && isset($inforDB["code_contract"])) {
				$vpbank = new VPBank();
				$assignVan = $vpbank->assignVan($inforDB["code_contract"]);
			}

			//Insert log
			$insertLog = array(
				"type" => "contract",
				"action" => "create_contract_structure",
				"contract_id" => (string)$contractId,
				"old" => $inforDB,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			/**
			 * Save log to json file
			 */

			$insertLogNew = [
				"type" => "contract",
				"action" => "create_contract_structure",
				"contract_id" => (string)$contractId,
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			];
			$log_id =  $this->log_contract_model->insertReturnId($insertLogNew);
			$this->log_model->insert($insertLog);
			$insertLog['log_id'] = $log_id;

			$this->insert_log_file($insertLog, (string)$contractId);

			/**
			 * ----------------------
			 */
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Extension contract success",
			'data' => $inforDB,
			'disbursement_date' => $inforDB['disbursement_date']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}
	public function check_approve_gia_han_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$number_day_loan = $this->security->xss_clean($this->dataPost['number_day_loan']);
		$so_lan = $this->security->xss_clean($this->dataPost['so_lan']);
		//$extend_date = $this->security->xss_clean($this->dataPost['extend_date']);
		$inforDB = $this->contract_model->findOne(array("code_contract" => $this->dataPost['code_contract']));
		if (empty($inforDB)) return;
		 $tranDT=$this->transaction_model->findOne(array('code_contract'=>$inforDB['code_contract'],'status'=>1,'type_payment'=>2));
        
		$KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
	
		  $ngay_gia_han=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
	
		
		
		$count_extension = !empty($inforDB['count_extension']) ? $inforDB['count_extension'] : 0;
		if(!empty($so_lan))
		{
			$count_extension=(int)$so_lan;
		}
		$tien_goc_con_lai = $this->temporary_plan_contract_model->tien_goc_con_lai($inforDB['code_contract']);
		$investorData = $this->investor_model->findOne(array("_id" => $inforDB['investor_infor']['_id']));

		$origin_code_contract = !empty($inforDB['code_contract_parent_gh']) ? $inforDB['code_contract_parent_gh'] : $inforDB['code_contract'];
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['count_extension'] = $count_extension ;
		$inforDB['code_contract_parent_gh'] = $origin_code_contract;
		$inforDB['loan_infor']['number_day_loan'] =  !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['amount_money'] = !empty($tien_goc_con_lai) ? (string)$tien_goc_con_lai : 0;
		$maxNumberContract = $this->initNumberContractCode();
		$inforDB['code_contract'] = "00000" . $maxNumberContract['max_number_contract'];
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['type_gh']=empty($inforDB['code_contract_parent_gh']) ? 'origin' : $inforDB['count_extension'];
		$inforDB['number_contract'] = (int)$maxNumberContract['max_number_contract'];
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
		$inforDB['created_at'] =  $ngay_gia_han;
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
		$inforDB['extend_all'] = array();

		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['extend_date'] = $ngay_gia_han;
		$inforDB['disbursement_date'] = $ngay_gia_han;

         $this->contract_model->update(
				array("_id" => $inforDB['_id']),
				array(
					'status'=>33
				)
			);
		unset($inforDB['_id']);
        
		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
			$this->contract_model->update(
				array("code_contract" => $origin_code_contract),
				array(

					"code_contract_child_gh.".$inforDB['count_extension'] => $origin_code_contract_disbursement . '/GH-0' . $inforDB['count_extension'],
					"extend_all.".$inforDB['count_extension']=>array('extend_date'=>$ngay_gia_han,'number_day_loan'=>$inforDB['loan_infor']['number_day_loan'],'so_lan'=>$inforDB['count_extension']),
					"type_gh"=>"origin"
				)
			);
		if(empty($ck_contract ))
		{

			$contractId = $this->contract_model->insertReturnId($inforDB);
			/**
			*
			* VPBank assign the vitual account number for new contract
			*/
			if ($contractId && isset($inforDB["code_contract"])) {
				$vpbank = new VPBank();
				$assignVan = $vpbank->assignVan($inforDB["code_contract"]);
			}
		}else{
			$code_contract=$ck_contract['code_contract'];
			unset($inforDB['code_contract']);
			unset($inforDB['number_contract']);
			unset($inforDB['code_contract_child_gh']);
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			unset($inforDB['fee']);
          //   if(isset($ck_contract['extend_date']) && $ck_contract['extend_date'] >0)
          //   {
          //   	$inforDB['extend_date'] = (int)$ck_contract['extend_date'];
		        // $inforDB['disbursement_date'] = (int)$ck_contract['extend_date'];
          //   }
			$this->contract_model->update(
				array("code_contract" => $code_contract),
				$inforDB
			);
			$inforDB['code_contract']=$code_contract;
		}
        
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Extension contract success",
			'data' => $inforDB,
			'disbursement_date' => $inforDB['disbursement_date']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}
	public function check_approve_co_cau_post()
	{
		// $flag = notify_token($this->flag_login);
		// if ($flag == false) return;

		$this->dataPost['code_contract'] = $this->security->xss_clean($this->dataPost['code_contract']);
		$number_day_loan = $this->security->xss_clean($this->dataPost['number_day_loan']);

		$amount_money = $this->security->xss_clean($this->dataPost['amount_money']);

		$structure_date = $this->security->xss_clean($this->dataPost['structure_date']);
		$so_lan = $this->security->xss_clean($this->dataPost['so_lan']);

		$type_loan = $this->security->xss_clean($this->dataPost['type_loan']);
		$type_interest = $this->security->xss_clean($this->dataPost['type_interest']);

		$inforDB = $this->contract_model->findOne(array("code_contract" => $this->dataPost['code_contract']));
		if (empty($inforDB)) return;
       $KiPhaiThanhToanXaNhat = $this->contract_model->getKiPhaiThanhToanXaNhat($inforDB['code_contract']);
		$ngay_co_cau=(int)$structure_date;
		 if($ngay_co_cau >$KiPhaiThanhToanXaNhat)
		 {
          $ngay_co_cau=strtotime('+1 day', $KiPhaiThanhToanXaNhat);
		 }
		$tien_goc_con_lai = $this->temporary_plan_contract_model->tien_goc_con_lai($inforDB['code_contract']);
		if(!empty($amount_money))
		{
			$tien_goc_con_lai=$amount_money;
		}
		
		$count_structure=(int)$so_lan;
		
		$origin_code_contract = !empty($inforDB['code_contract_parent_cc']) ? $inforDB['code_contract_parent_cc'] : $inforDB['code_contract'];
		$inforDB_origin = $this->contract_model->findOne(array("code_contract" => $origin_code_contract));
		$origin_code_contract_disbursement=$inforDB_origin['code_contract_disbursement'];
		$inforDB['count_structure'] = $count_structure ;
		$inforDB['code_contract_parent_cc'] = $origin_code_contract;
		$inforDB['loan_infor']['number_day_loan'] = !empty($number_day_loan) ? $number_day_loan : $inforDB['loan_infor']['number_day_loan'];
		$inforDB['loan_infor']['type_interest'] = !empty($type_interest) ? $type_interest : $inforDB['loan_infor']['type_interest'];
		$inforDB['loan_infor']['amount_money'] = !empty($tien_goc_con_lai) ? $tien_goc_con_lai : $inforDB['loan_infor']['amount_money'];
		$arr_type_loan=array();
		if($type_loan=="DKX")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ee7a104d435e3b8ae66';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cho vay';
			$inforDB['loan_infor']['type_loan']['code'] = 'DKX';
		}else if($type_loan=="CC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5da82ed2a104d435e3b8ae65';
			$inforDB['loan_infor']['type_loan']['text'] = 'Cầm cố';
			$inforDB['loan_infor']['type_loan']['code'] = 'CC';
		}else if($type_loan=="TC")
		{
			$inforDB['loan_infor']['type_loan']['id'] = '5fdf75fa6653056471f0b7fe';
			$inforDB['loan_infor']['type_loan']['text'] = 'Tín chấp';
			$inforDB['loan_infor']['type_loan']['code'] = 'TC';
		}


		$maxNumberContract = $this->initNumberContractCode();
		$inforDB['code_contract'] = "00000" . $maxNumberContract['max_number_contract'];
		$inforDB['code_contract_disbursement'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['type_cc']=empty($inforDB['code_contract_parent_cc']) ? 'origin' : $inforDB['count_structure'];
		$inforDB['number_contract'] = (int)$maxNumberContract['max_number_contract'];
		$inforDB['reason1'] = !empty($inforDB['reason']) ? $inforDB['reason'] : "";
		$inforDB['created_at'] = $this->createdAt;
		$inforDB['status_disbursement'] = 2;
		$inforDB['updated_by'] = $this->uemail;
		$inforDB['status'] = 17;
		$inforDB['structure_all'] = array();
		$receiver_infor = $inforDB['receiver_infor'];
		$receiver_infor['order_code'] = $origin_code_contract_disbursement . '/CC-0' . $inforDB['count_structure'];
		$inforDB['receiver_infor'] = $receiver_infor;
		$inforDB['structure_date'] = $ngay_co_cau;
		$inforDB['disbursement_date'] = $ngay_co_cau;
		$arrFee = $this->getFee($inforDB);
		if (empty($arrFee['fee'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Chưa lấy được biểu phí",
				'inforDB' => $inforDB['loan_infor'],
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$inforDB['fee'] = $arrFee['fee'];
		$inforDB['fee_id'] = $arrFee['id'];
        $this->contract_model->update(
				array("_id" => $inforDB['_id']),
				array(
					'status'=>34
				)
			);
		unset($inforDB['_id']);
		$ck_contract = $this->contract_model->findOne(array("code_contract_disbursement" => $inforDB['code_contract_disbursement']));
		if(empty($ck_contract ))
		{
			$this->contract_model->update(
				array("code_contract" => $origin_code_contract),
				array(
					"code_contract_child_cc.".$inforDB['count_structure'] => $origin_code_contract . '/CC-0' . $inforDB['count_structure'],
					"structure_all.".$inforDB['count_structure']=>array('structure_date'=>$ngay_co_cau,'number_day_loan'=>$inforDB['loan_infor']['number_day_loan'],'amount_money'=>$inforDB['loan_infor']['amount_money'],'type_loan'=>$inforDB['loan_infor']['type_loan'],'so_lan'=>$inforDB['count_structure'],'type_interest'=>$inforDB['loan_infor']['type_interest']),
					"type_cc"=>"origin"
				)
			);
			$contractId = $this->contract_model->insertReturnId($inforDB);

			/**
			*
			* VPBank assign the vitual account number for new contract
			*/
			if ($contractId && isset($inforDB["code_contract"])) {
				$vpbank = new VPBank();
				$assignVan = $vpbank->assignVan($inforDB["code_contract"]);
			}
			
		}else{
			
			unset($inforDB['code_contract']);
			unset($inforDB['number_contract']);
			unset($inforDB['code_contract_child_cc']);
			unset($inforDB['extend_all']);
			unset($inforDB['structure_all']);
			unset($inforDB['fee']);
		
			//  if($ck_contract['status']!=3)
			//  {
			// $this->contract_model->update(
			// 	array("code_contract_disbursement" => $inforDB['code_contract_disbursement']),
			// 	$inforDB
			// );
		 //    }
			$inforDB['code_contract']=$ck_contract['code_contract'];
		
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => "Structure contract success",
			'data' => $inforDB,
			'disbursement_date' => $inforDB['disbursement_date']
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}
	private function getGroupRole($userId)
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (empty($groupRole['users'])) continue;
			foreach ($groupRole['users'] as $item) {
				if (key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
		return $arr;
	}
	public function getFee($contract)
	{
		$date_fee=isset($contract['structure_date']) ? $contract['structure_date'] : $this->createdAt;
		if (empty($contract)) return array();
		$default = array(
			"percent_interest_customer" => 0,
			"percent_interest_investor" => 0,
			"percent_advisory" => 0,
			"percent_expertise" => 0,
			"penalty_percent" => 0,
			"penalty_amount" => 0,
			"extend" => 0,
			"percent_prepay_phase_1" => 0,
			"percent_prepay_phase_2" => 0,
			"percent_prepay_phase_3" => 0
		);
		$typeLoan = !empty($contract['loan_infor']['type_loan']['code']) ? $contract['loan_infor']['type_loan']['code'] : "";
		$typeProperty = !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_property']['code'] : "";
		$number_day_loan = !empty($contract['loan_infor']['number_day_loan']) ? $contract['loan_infor']['number_day_loan'] : "";
		$loan_infor_kdol = !empty($contract['loan_infor']['loan_product']['code']) ? $contract['loan_infor']['loan_product']['code'] : "";
		$amount_loan = !empty($contract['loan_infor']['amount_loan']) ? $contract['loan_infor']['amount_loan'] : '';

		if ($typeProperty == "NĐ") {
			$typeLoan = "NĐ";
		}
		if ($typeLoan == "DKX" && $typeProperty == "XM") {
			$typeLoan = "DKXM";
		}
		if ($typeLoan == "DKX" && $typeProperty == "OTO") {
			$typeLoan = "DKXOTO";
		}
		if ($typeLoan == "DKX" && $typeProperty == "TC") {
			$typeLoan = "TC";
		}
		if ($loan_infor_kdol == 14) {
			$typeLoan = "KDOL";
		}
		if ($loan_infor_kdol == 14 && $typeProperty == "TC") {
			$typeLoan = "KDOL_TC";
		}

		if ($typeLoan == "NĐ") {

			$data = $this->fee_loan_model->findOne(array("from"=>['$lte'=>$date_fee],"to"=>['$gte'=>$date_fee],"status" => 'active', 'type' => "bieu-phi-nha-dat"));

			$default = array();
			if (!empty($data)) {
				$default['percent_prepay_phase_1'] = $data['infor']['percent_prepay_phase_1'];
				$default['percent_prepay_phase_2'] = $data['infor']['percent_prepay_phase_2'];
				$default['percent_prepay_phase_3'] = $data['infor']['percent_prepay_phase_3'];
				$default['extend'] = $data['infor']['extend'];
				$default['penalty_amount'] = $data['infor']['penalty_amount'];
				$default['penalty_percent'] = $data['infor']['penalty_percent'];
				if ($amount_loan <= 100000000) {
					$default['percent_interest_customer'] = $data['infor']['100']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['100']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['100']['percent_expertise'];
				} elseif ($amount_loan > 100000000 && $amount_loan <= 200000000) {
					$default['percent_interest_customer'] = $data['infor']['100-200']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['100-200']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['100-200']['percent_expertise'];
				} elseif ($amount_loan > 200000000) {
					$default['percent_interest_customer'] = $data['infor']['200']['percent_interest_customer'];
					$default['percent_advisory'] = $data['infor']['200']['percent_advisory'];
					$default['percent_expertise'] = $data['infor']['200']['percent_expertise'];
				}
			}
		} else {
			//Get record by time
			$data = $this->fee_loan_model->findOne(
				array(
					"status" => 'active',
					"type" => array(
						'$exists' => false
					),
					"from"=>['$lte'=>$date_fee],
					"to"=>['$gte'=>$date_fee]
				)
			);
			if (!empty($data)) $default = $data['infor'][$number_day_loan][$typeLoan];
		}

		//Get record by time
		$data['code_coupon'] = $this->security->xss_clean($contract['loan_infor']['code_coupon']);
		$data_coupon = $this->coupon_model->findOne(array("code" => $data['code_coupon'], 'status' => 'active'));
		if (!empty($data_coupon)) {
			if (isset($contract['store']['id'])) {
				$data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($contract['store']['id'])));
				if (!empty($data_store)) {

					if ($data_store['province_id'] != $data_coupon['selectize_province'] && !empty($data_coupon['selectize_province'])) {
						return array();
					}
					if ((string)$data_store['_id'] != $data_coupon['code_store'] && !empty($data_coupon['code_store'])) {
						return array();
					}
					if (!empty($data_coupon['code_area']) && is_array($data_coupon['code_area']) && !in_array($data_store['code_area'], (array)$data_coupon['code_area']) && !in_array('null', (array)$data_coupon['code_area'])) {
						return array();
					}
				}
			}
			if ($contract['loan_infor']['type_loan']['id'] != $data_coupon['type_loan'] && !empty($data_coupon['type_loan'])) {
				return array();
			}
			if ($contract['loan_infor']['type_property']['id'] != $data_coupon['type_property'] && !empty($data_coupon['type_property'])) {
				return array();
			}

			if (!empty($data_coupon['loan_product']) && is_array($data_coupon['loan_product']) && !in_array($contract['loan_infor']['loan_product']['code'], (array)$data_coupon['loan_product']) && !in_array('null', (array)$data_coupon['loan_product'])) {
				return array();
			}

			if (!empty($data_coupon['number_day_loan']) && is_array($data_coupon['number_day_loan']) && !in_array($contract['loan_infor']['number_day_loan'], (array)$data_coupon['number_day_loan']) && !in_array('null', (array)$data_coupon['number_day_loan'])) {
				return array();
			}
		}

		$arrNew = array();
		foreach ($default as $key => $value) {
			if (empty($data_coupon)) {
				$arrNew[$key] = (float)$value;
			} else {
				if(isset($data_coupon['set_by_coupon']) && $data_coupon['set_by_coupon']=='active')
				{
					if (isset($data_coupon[$key]) && $data_coupon[$key]>0) {
					       $fee = (float)$data_coupon[$key];
					    } else {
						if (isset($data_coupon[$key])  && ($key=='percent_advisory' || $key=='percent_expertise')) {
						     $fee =  (float)$data_coupon[$key];
					    }else{
                             $fee = (float)$value;
					    }
					}
				    $arrNew[$key] = ($fee < 0) ? 0 : $fee;

				}else{
					if (isset($data_coupon[$key])) {
					   if (isset($data_coupon[$key])  && ($key=='percent_advisory' || $key=='percent_expertise')) {
						   $fee = (float)$value - (float)$data_coupon[$key];
					    }else{
					    	$fee = (float)$value;
					    }
					} else {
							$fee = (float)$value;
						}
				   $arrNew[$key] = ($fee < 0) ? 0 : $fee;

				}


			}

		}

		

		 $arr_return=[
         	'fee' => $arrNew,
         	'id' => isset($data['_id']) ? (string)$data['_id'] : '',
         	'date'=>$typeLoan
         ];
		return $arr_return;
	}
	

	private function getUserGroupRole($GroupIds)
	{
		$arr = array();
		foreach ($GroupIds as $groupId) {
			$groups = $this->group_role_model->findOne(array('_id' => new MongoDB\BSON\ObjectId($groupId)));
			foreach ($groups['users'] as $item) {
				$arr[] = key($item);
			}
		}
		$arr = array_unique($arr);
		return $arr;
	}

	private function getStores($userId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleStores = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['users']) && count($role['users']) > 0) {
					$arrUsers = array();
					foreach ($role['users'] as $item) {
						array_push($arrUsers, key($item));
					}
					//Check userId in list key of $users
					if (in_array($userId, $arrUsers) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['stores'] as $key => $item) {
								array_push($roleStores, key($item));
							}
						}
					}
				}
			}
		}
		return $roleStores;
	}

	private function getUserbyStores($storeId)
	{
		$roles = $this->role_model->find_where(array("status" => "active"));
		$roleAllUsers = array();
		if (count($roles) > 0) {
			foreach ($roles as $role) {
				if (!empty($role['stores']) && count($role['stores']) > 0) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}
					//Check userId in list key of $users
					if (in_array($storeId, $arrStores) == TRUE) {
						if (!empty($role['stores'])) {
							//Push store
							foreach ($role['users'] as $key => $item) {
								array_push($roleAllUsers, key($item));
							}
						}
					}
				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}

	public function insert_log_file($value, $contract_id){

		$fp = fopen($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json', "a");

		if(!empty($fp)){

			$arrayData = $this->readFileJson($contract_id);

			if(empty($arrayData)){
				$arrayData = [];
			}
			array_push($arrayData, $value);

			$this->saveFileJson($arrayData, $contract_id);

		}
	}

	function saveFileJson($arrayData , $contract_id){
		$dataJson = json_encode($arrayData);
		file_put_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json',$dataJson);
	}
	function readFileJson($contract_id){
		$data = file_get_contents($this->config->item("URL_LOG_CONTRACT").$contract_id.'.json');
		return json_decode($data,true);
	}
}

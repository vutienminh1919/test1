<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Temporary_plan_contract extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('investor_model');
        $this->load->model('fee_loan_model');
         $this->load->model('log_model');
        $this->load->model('contract_model');
        $this->load->model('contract_tempo_model');
        $this->load->model('tempo_contract_accounting_model');
        $this->load->model('log_contract_tempo_model');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->dataPost = $this->input->post();
         $headers = $this->input->request_headers();
          if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
            $headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
            $token = Authorization::validateToken($headers_item);
            if ($token != false) {
                // Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
                $this->app_login = array(
                    '_id'=>new \MongoDB\BSON\ObjectId($token->id),
                    'email'=>$token->email,
                    "status" => "active"
                );
                //Web
                $type = !empty($this->dataPost['type']) ? $this->dataPost['type'] : 1;
                if($type == 1) $this->app_login['token_web'] = $headers_item;
                if($type == 2) $this->app_login['token_app'] = $headers_item;
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
					$this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
                }
            }
        }
    }
	private $createdAt, $dataPost;
    	public function get_all_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$full_name = !empty($this->dataPost['full_name']) ? $this->dataPost['full_name'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$tab = !empty($this->dataPost['tab']) ? $this->dataPost['tab'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $total_tran=0;
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate).' 00:00:00'),
				'end' => strtotime(trim($tdate).' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($full_name)) {
			$condition['full_name'] = trim($full_name);
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = trim($code_contract_disbursement);
		}
		if (!empty($code_contract)) {
			$code_contract = explode(", " , trim($code_contract));
			$condition['code_contract'] =$code_contract;
		}
		 if($tab=="wait"){
            $condition['status_disbursement'] = 2;
             $condition['status'] =17;
        }
        if(!empty($tab)){
            $condition['tab'] = $tab;
             
        }
         if($tab=="erro_nl"){
            $condition['status_create_withdrawal_nl'] = '03';
             $condition['status'] =15;
        }
          if($tab=="import"){
            $condition['type'] = 'old_contract';
            $condition['status_disbursement'] = 2;
            $condition['status'] =16;
          
        }
         if($tab=="import_payment"){
          // $condition['type'] = 'old_contract';
          // $condition['status_disbursement'] = 3;
          // $condition['status'] =17;

          
        }
        if($tab=="run_fee_again"){
         //$condition['type'] = 'old_contract';
         //  $condition['status'] =17;
        //  $condition['status_disbursement'] = 3;
        }
        if($tab=="rerun_cc"){
         //$condition['type'] = 'old_contract';
         //  $condition['status'] =34;
        //  $condition['status_disbursement'] = 3;
        }
        if($tab=="rerun_gh"){
         //$condition['type'] = 'old_contract';
         //  $condition['status'] =33;
        //  $condition['status_disbursement'] = 3;
        }
		if (!empty($store)) {
			$condition['store'] = $store;
		}
	     // var_dump($condition); die;
		$contracts = $this->contract_model->get_temporary_contract_kt($condition, $per_page , $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->contract_model->get_temporary_contract_kt($condition);
		if (!empty($contracts)) {
			foreach ($contracts as $tran) {
				$tran['id'] = (string)$tran['_id'];
					$cond = array();

				if(isset($tran['code_contract']))
				{
				$contract = $this->contract_model->find_where(array('code_contract'=> $tran['code_contract']));
				$tran['id_contract'] =(isset($contract[0]['_id'])) ? (string)$contract[0]['_id'] : '';
				$cond = array(
					'code_contract' => $tran['code_contract'],
					'end' => time() - 5* 24*3600, // 5 ngay tieu chuan
				);
			  }
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				$tran['detail'] = array();
					//var_dump($detail[0]); die;
				if (!empty($detail)) {
					$total_paid = 0;
					$total_phi_phat_cham_tra = 0;
					$total_da_thanh_toan=0;
					foreach ($detail as $de) {

						$total_paid = $total_paid + $de['tien_tra_1_ky'] ;
						$total_phi_phat_cham_tra +=$de['penalty'] ;
						$total_da_thanh_toan +=$de['da_thanh_toan'] ;
					}
					$tran['detail'] = $detail[0];
					$tran['detail']['total_paid'] = $total_paid;
					$tran['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
					$tran['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
				} else {
					$condition_new = array(
						'code_contract' => $tran['code_contract'],
						'status' => 1
					);
					$detail_new = $this->contract_tempo_model->getContract($condition_new);
					if (!empty($detail_new)) {
						$tran['detail'] = $detail_new[0];

						$tran['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'] ;
						$tran['detail']['total_phi_phat_cham_tra'] =$detail_new[0]['penalty'] ;
					$tran['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'] ;
					}
				}

			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contracts,
			'total'=>$total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_all_cc_gh_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
		$tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
		$full_name = !empty($this->dataPost['full_name']) ? $this->dataPost['full_name'] : "";
		$code_contract_disbursement = !empty($this->dataPost['code_contract_disbursement']) ? $this->dataPost['code_contract_disbursement'] : "";
		$code_contract = !empty($this->dataPost['code_contract']) ? $this->dataPost['code_contract'] : "";
		$tab = !empty($this->dataPost['tab']) ? $this->dataPost['tab'] : "";
		$store = !empty($this->dataPost['store']) ? $this->dataPost['store'] : "";
		$status = !empty($this->dataPost['status']) ? $this->dataPost['status'] : "";
        $total_tran=0;
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$condition = array();
		if (!empty($fdate) && !empty($tdate)) {
			$condition = array(
				'start' => strtotime(trim($fdate).' 00:00:00'),
				'end' => strtotime(trim($tdate).' 23:59:59')
			);
		}
		if (!empty($status)) {
			$condition['status'] = (int)$status;
		}
		if (!empty($full_name)) {
			$condition['full_name'] = $full_name;
		}
		if (!empty($code_contract_disbursement)) {
			$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}
		if (!empty($code_contract)) {
			$condition['code_contract'] = $code_contract;
		}
		
        if(!empty($tab)){
            $condition['tab'] = $tab;
             
        }
       
        if($tab=="rerun_cc"){
         //$condition['type'] = 'old_contract';
           $condition['status'] =34;
        //  $condition['status_disbursement'] = 3;
        }
        if($tab=="rerun_gh"){
         //$condition['type'] = 'old_contract';
           $condition['status'] =33;
        //  $condition['status_disbursement'] = 3;
        }
		if (!empty($store)) {
			$condition['store'] = $store;
		}
	     // var_dump($condition); die;
		$contracts = $this->contract_model->get_temporary_contract_kt($condition, $per_page , $uriSegment);
		$condition['total'] = true;
		$total_tran = $this->contract_model->get_temporary_contract_kt($condition);
	    if (!empty($contracts)) {
			foreach ($contracts as $tran) {
				$tran['id'] = (string)$tran['_id'];
					$cond = array();

				if(isset($tran['code_contract']))
				{
				$contract = $this->contract_model->find_where(array('code_contract'=> $tran['code_contract']));
				$tran['id_contract'] =(isset($contract[0]['_id'])) ? (string)$contract[0]['_id'] : '';
				$cond = array(
					'code_contract' => $tran['code_contract'],
					'end' => time() - 5* 24*3600, // 5 ngay tieu chuan
				);
			  }
				$detail = $this->contract_tempo_model->getContractTempobyTime($cond);
				$tran['detail'] = array();
					//var_dump($detail[0]); die;
				if (!empty($detail)) {
					$total_paid = 0;
					$total_phi_phat_cham_tra = 0;
					$total_da_thanh_toan=0;
					foreach ($detail as $de) {

						$total_paid = $total_paid + $de['tien_tra_1_ky'] ;
						$total_phi_phat_cham_tra +=$de['penalty'] ;
						$total_da_thanh_toan +=$de['da_thanh_toan'] ;
					}
					$tran['detail'] = $detail[0];
					$tran['detail']['total_paid'] = $total_paid;
					$tran['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
					$tran['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
				} else {
					$condition_new = array(
						'code_contract' => $tran['code_contract'],
						'status' => 1
					);
					$detail_new = $this->contract_tempo_model->getContract($condition_new);
					if (!empty($detail_new)) {
						$tran['detail'] = $detail_new[0];

						$tran['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'] ;
						$tran['detail']['total_phi_phat_cham_tra'] =$detail_new[0]['penalty'] ;
					$tran['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'] ;
					}
				}

			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contracts,
			'total'=>$total_tran
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function processContract_post(){
	
		//Check null mục Thông tin hợp đồng
		if(empty($this->dataPost['code_contract'])) {
                    $response = array(
                        'status' => REST_Controller::HTTP_BAD_REQUEST,
                        'message' => "Không tồn tại mã hợp đồng"
                    );
                    echo json_encode($response);
                    return;
                        
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
		}
		if(empty($this->dataPost['disbursement_date'])) {
                    $response = array(
                            'status' => REST_Controller::HTTP_BAD_REQUEST,
                            'message' => "Không tồn tại ngày giải ngân"
                    );
                    echo json_encode($response);
                    return;
                        
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
		}
		if(empty($this->dataPost['investor_code'])) {
                    $response = array(
                            'status' => REST_Controller::HTTP_BAD_REQUEST,
                            'message' => "Không có nhà đầu tư"
                    );
                    echo json_encode($response);
                    return;
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
		}
		$contract = $this->contract_model->findOne(array('code_contract' => $this->dataPost['code_contract']));
		if (empty($contract['status_disbursement']) || $contract['status_disbursement'] != 2) {
                    $response = array(
                            'status' => REST_Controller::HTTP_BAD_REQUEST,
                            'message' => "Hợp đồng không phù hợp trạng thái giải ngân",
							//'message' => "Contract no suitable with status disbursement",
							'data' => $contract
                    );
                    echo json_encode($response);
                    return;
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;

		}
		// check exist tempo contract
		if (!empty($contract)) {
			$amount_money = isset($contract['loan_infor']['amount_money']) ? intval($contract['loan_infor']['amount_money']) : 0;
			$code_contract = $this->dataPost['code_contract'];
			$customer_info = isset($contract['customer_infor']) ? $contract['customer_infor'] : '';
			$number_day_loan = isset($contract['loan_infor']['number_day_loan']) ? intval($contract['loan_infor']['number_day_loan']) : 0;
			$period_pay_interest = isset($contract['loan_infor']['period_pay_interest']) ? intval($contract['loan_infor']['period_pay_interest']) : 0;
			$type_interest = isset($contract['loan_infor']['type_interest']) ? $contract['loan_infor']['type_interest'] : 0;
			$disbursement_date = intval($this->dataPost['disbursement_date']);
			$insurrance = isset($contract['loan_infor']['insurrance_contract']) ? boolval($contract['loan_infor']['insurrance_contract']) : false;
			$investor_code = $this->dataPost['investor_code'];
			$disbursement_date = $this->dataPost['disbursement_date'];
			$this->spreadsheetFeeLoan($code_contract,$customer_info ,$investor_code, $disbursement_date , $amount_money, $number_day_loan, $period_pay_interest, $type_interest,$insurrance);
			$this->generateFeeLoanbyMonth($code_contract,$customer_info ,$investor_code, $disbursement_date , $amount_money, $number_day_loan, $period_pay_interest, $type_interest,$insurrance);
			$investor = $this->investor_model->findOne(array('status' => 'active', 'code' => $investor_code));
			$dataContract['status_disbursement'] = 3; // update created data tempo
			$dataContract['investor_code'] = $investor_code; // update investor
			$dataContract['investor_infor'] = $investor; // update investor
			$dataContract['expire_date'] = $this->periodDays(date('Y-m-d',$disbursement_date),$number_day_loan/30,$disbursement_date,$period_pay_interest)['date']; // update expire date
			$dataContract['disbursement_date'] = intval($this->dataPost['disbursement_date']); // update date
			$this->contract_model->findOneAndUpdate(array('code_contract' => $contract['code_contract']), $dataContract);

			//Insert log
			$insertLog = array(
				"type" => "update",
				"table" => 'contract',
				"new" => $dataContract,
				"created_at" => $this->createdAt
			);
			$this->log_contract_tempo_model->insert($insertLog);

			$response = array(
                            'status' => REST_Controller::HTTP_OK,
                            'message' => 'Thành công'
			);
                        echo json_encode($response);
                        return;
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
		} else {
			$response = array(
                            'status' => REST_Controller::HTTP_UNAUTHORIZED,
                            'message' => "Không tồn tại hợp đồng"
			);
                        echo json_encode($response);
                        return;
//			$this->set_response($response, REST_Controller::HTTP_OK);
//			return;
		}
	}
	 
}
?>

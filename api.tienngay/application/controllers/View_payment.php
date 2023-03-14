<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/Fcm.php';
require_once APPPATH . 'libraries/VPBank.php';

use Restserver\Libraries\REST_Controller;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;

class View_payment extends REST_Controller
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
		date_default_timezone_set('Asia/Ho_Chi_Minh');

	}

	private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;
	//lấy thông tin gốc lãi phí các loại phí cần để thanh toán  hoặc tất toán
	public function tempo_detail_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;

		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$date_pay = (empty($this->dataPost['date_pay'])) ?  0 : strtotime($this->dataPost['date_pay'].' 23:59:59');
		if (empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại id"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$condition = array(
			'code_contract' => $dataDB['code_contract']
		);
		$contract = $this->contract_tempo_model->getAll($condition);

		//  lấy data các nhà đầu tư còn hoạt động
		$investor = $this->investor_model->find_where(array('status' => 'active'));
		$data_investor = array();
		if (!empty($investor)) {
			foreach ($investor as $in) {
				$data_investor[$in['code']] = $in;
			}
		}
		$current_day = strtotime(date('Y-m-d').' 23:59:59');
		if($date_pay==0)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($dataDB['code_contract'])).' 23:59:59') ;
		$current_day=$date_pay;
	     }
	     if($dataDB['status']==33 || $dataDB['status']==34)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_gh_cc($dataDB['code_contract'])).' 23:59:59') ;
		$current_day=$date_pay;
	     }
		
		
		$date_pay = ($date_pay==0) ? $current_day :  intval($date_pay);
		$total_money_paid_now = 0;
		$total_money_paid = 0;
		$total_money_remaining = 0;
		$total_paid = 0;
		$tong_thanh_toan=0;
		$ky_han_truoc = 0;
		$penalty=0;
		$penalty_tt=0;
		$penalty_con_lai=0;
		$so_ngay_chenh_lech_tt_tt=0;
		$ngay_ky_tra=0;
		$penalty_now_tt=0;
		$penalty_pay_tt=0;
		$penalty_now=0;
		$penalty=0;
		$penalty_pay=0;
		$ngay_ket_thuc=0;
		$ky_cham_tra=0;
		$penalty_da_tra=0;
		$tong_so_ngay_trong_ky=0;
		$tien_chua_tra_ky_thanh_toan=0;
		$tien_du_ky_truoc=0;
		$tong_penalty_con_lai=0;
		$so_ngay_cham_tra_now=0;
		$so_ngay_cham_tra_pay=0;
		$so_ngay_cham_tra_pay=0;
		$money_difference=0;
		$tien_thua_thanh_toan=0;
		$tien_con_no=0;
		if (!empty($contract)) {
			//lặp bảng lãi kỳ để lấy thống tin gốc, lãi , phí , phí chậm trả
			foreach ($contract as $c) {
				$penalty=0;

				//$c['disbursement_date'] = $dataDB['disbursement_date'];
				$c['amount_money'] = $dataDB['loan_infor']['amount_money'];
				$time_disbursement_date=$dataDB['disbursement_date'] ;
				$penalty_con_lai=0;
				$penalty_da_tra=0;
				//kỳ đã thanh toán
				if ($c['status'] == 2)
				{
					
					$tien_chua_tra_ky_thanh_toan += (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'];
				}
               
                $penalty_con_lai=(isset($c['tien_phi_cham_tra_1ky_con_lai'])) ? (float)$c['tien_phi_cham_tra_1ky_con_lai'] : 0;
			    $penalty_da_tra=(isset($c['tien_phi_cham_tra_1ky_da_tra'])) ? (float)$c['tien_phi_cham_tra_1ky_da_tra'] : 0;
				//penalty: chậm trả
				$tong_penalty_con_lai+=$penalty_con_lai;
				$total_paid += $c['da_thanh_toan']+$penalty_da_tra;
				$ngay_ky_tra_ky_ht=$c['ngay_ky_tra'];
				$ngay_ket_thuc=$ngay_ky_tra_ky_ht;
				if($ngay_ky_tra_ky_ht >0 && $c['status'] == 1)
				{
					
						$so_ngay_cham_tra_now = intval(($current_day - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

						$so_ngay_cham_tra_pay = intval(($date_pay - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));
					
					$c['so_ngay_cham_tra_now']=$so_ngay_cham_tra_now;

				}
				if($c['ky_tra'] >1)
				{
					//lấy thông tin kỳ trước đó
					$last_contract_tempo = $this->contract_tempo_model->findOne(array('code_contract' => $dataDB['code_contract'], 'ky_tra' => $c['ky_tra']-1));
				}

				$ky_han_truoc=(isset($last_contract_tempo['ngay_ky_tra'])) ? $last_contract_tempo['ngay_ky_tra'] : 0;
				// số ngày trong kỳ chậm trả
				$time_period = $this->contract_model->tinh_so_ngay_trong_ky_cham_tra($c['ky_tra'],$time_disbursement_date,strtotime(date('Y-m-d',$c['ngay_ky_tra']).' 23:59:59'),$ky_han_truoc);
				$c['so_ngay_trong_ky']=$time_period;
				$tong_so_ngay_trong_ky+=$time_period;
				//với kỳ 1
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - ($c['so_ngay_trong_ky']-1) * 24 * 3600)) && $c['ky_tra']==1) { // 5 ngay tieu chuan
					//tổng số cần thanh toán ngày hiện tại
					$total_money_paid_now = $total_money_paid_now + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;
					$ngay_ky_tra=$c['ngay_ky_tra'];
					//tổng số cần thanh toán ngày khách chọn
					$total_money_paid = $total_money_paid + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;

				}
				//với kỳ >1
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - (5 * 24 * 3600))) && $c['ky_tra']>1) { // 5 ngay tieu chuan
					//tổng số cần thanh toán ngày hiện tại
					$total_money_paid_now = $total_money_paid_now + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;
					$ngay_ky_tra=$c['ngay_ky_tra'];
					//tổng số cần thanh toán ngày khách chọn
					$total_money_paid = $total_money_paid + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;

				}
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra']+1* 24 * 3600) )) {
					//số kỳ chậm trả
					$ky_cham_tra++;
				}
                
                if($c['ngay_ky_tra']>$date_pay && $date_pay < $contract[count($contract)-1]['ngay_ky_tra'])
                {
                	//tiền dư kỳ tương lai
                	 $tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }
                // với kỳ chưa thanh toán quá khứ
                 if( $c['status'] == 1 && $c['ngay_ky_tra'] < $date_pay && $date_pay < $contract[count($contract)-1]['ngay_ky_tra'])
                {
                	//tiền dư kỳ tương lai
                	 $tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }
                //với kỳ chưa thanh toán tương lai
                 if( $date_pay > $contract[count($contract)-1]['ngay_ky_tra'] && $c['status'] == 1)
                {
                	 $tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }
                //tiền chậm trả với ngày hiện tại
                //penalty_percent % chậm trả
                //penalty_amount số tiền chậm trả
				$penalty_now =$this->contract_model->tinh_phi_phat($c['tien_tra_1_ky'],$dataDB['fee']['penalty_percent'],$dataDB['fee']['penalty_amount'],$so_ngay_cham_tra_now,$time_period);
				 //tiền chậm trả với ngày chọn
				$penalty_pay =$this->contract_model->tinh_phi_phat($c['tien_tra_1_ky'],$dataDB['fee']['penalty_percent'],$dataDB['fee']['penalty_amount'],$so_ngay_cham_tra_pay,$time_period);
				//chậm trả chưa trả
				$phat_qua_han_chua_tra=(isset($c['tien_phi_cham_tra_1ky_con_lai'])) ? (float)$c['tien_phi_cham_tra_1ky_con_lai'] : 0;
				$penalty_now_tt+=$penalty_now+$phat_qua_han_chua_tra;
				$penalty_pay_tt+=$penalty_pay+$phat_qua_han_chua_tra;

				$c['penalty_now']=$penalty_now+$phat_qua_han_chua_tra;

                 //lấy gốc còn kỳ chưa thanh toán
				if ($c['status'] == 1) {
					$total_money_remaining += (isset($c['tien_goc_1ky_con_lai'])) ? (int)$c['tien_goc_1ky_con_lai'] : 0;
					// $total_money_remaining += (isset($c['tien_lai_1ky_con_lai'])) ? (int)$c['tien_lai_1ky_con_lai'] : 0;
					// $total_money_remaining += (isset($c['tien_phi_1ky_con_lai'])) ? (int)$c['tien_phi_1ky_con_lai'] : 0;

				}
				//lấy chậm trả kỳ đã thanh toán
				$penalty= (isset($c['fee_delay_pay'])) ? (float)$c['fee_delay_pay'] : $penalty_pay;
				$tong_thanh_toan+=(float)$c['tien_tra_1_ky']  + $penalty;
			}
			if ($contract[0]['status'] == 1) {
				//tổng số tiền còn lại
				$total_money_remaining = $dataDB['loan_infor']['amount_money'] - $contract[0]['da_thanh_toan'];
			}

		}
		$tong_phi_phat_sinh=$this->transaction_model->get_tong_phi_phat_sinh($dataDB['code_contract']);
		$phi_phat_sinh_da_tra=$this->transaction_model->get_phi_phat_sinh_da_tra($dataDB['code_contract']);
		$phi_phat_sinh_hien_tai =$this->contract_model->get_phi_phat_sinh($dataDB,$current_day)['phi_phat_sinh'];
		$phi_phat_sinh_ngay_thanh_toan =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['phi_phat_sinh'];
		$phi_phat_sinh_chua_tra_hien_tai=$tong_phi_phat_sinh-$phi_phat_sinh_da_tra+$phi_phat_sinh_hien_tai;
		$phi_phat_sinh_chua_tra_ngay_thanh_toan=$tong_phi_phat_sinh-$phi_phat_sinh_da_tra+$phi_phat_sinh_ngay_thanh_toan;
		$tien_con_no=$this->temporary_plan_contract_model->get_tien_con_no($dataDB['code_contract'],$date_pay);
		$tong_so_tien_thieu=0;
		$tong_so_tien_thua_gh=0;
		if(!empty($dataDB['code_contract_parent_gh']))
		{
		   $tong_so_tien_thieu=$this->transaction_model->get_so_tien_thieu($dataDB['code_contract_parent_gh']);
	     }
	     if($dataDB['status']==33)
		{
			//lấy tiền thiếu khi gia hạn xong
          $tien_thieu_gia_han=$this->transaction_model->get_so_tien_thieu_gia_han($dataDB['code_contract']);
		}
		if($tong_phi_phat_sinh>0)
		{
			$phi_phat_sinh_hien_tai=$phi_phat_sinh_chua_tra_hien_tai;
			$phi_phat_sinh_ngay_thanh_toan=$phi_phat_sinh_chua_tra_ngay_thanh_toan;
		}
		//$tong_thanh_toan+=$tong_phi_phat_sinh;
		$total_paid +=$phi_phat_sinh_da_tra;
		//so_ngay_chenh_lech_tt_tt số ngày lệch thanh toán và tất toán
		if( $date_pay==0)
		{
			$so_ngay_chenh_lech_tt_tt=0;
		}else{
			$so_ngay_chenh_lech_tt_tt= intval(($current_day - $date_pay) / (24 * 60 * 60));
		}
        //tổng tiền đến ngày chọn
		$total_money_paid +=  $penalty_pay_tt+$phi_phat_sinh_ngay_thanh_toan+$tien_chua_tra_ky_thanh_toan+$tong_so_tien_thieu ;
		//tổng tiền đến ngày hiện tại
		$total_money_paid_now+=$penalty_now_tt+$phi_phat_sinh_hien_tai+$tien_chua_tra_ky_thanh_toan+$tong_so_tien_thieu;

        
		$da_thanh_toan=$this->transaction_model->get_da_thanh_toan($dataDB['code_contract']);
		$tien_thua_tat_toan=$this->transaction_model->sum_where(array('code_contract'=>$dataDB['code_contract'],"status"=>1,"type"=>3),'$tien_thua_tat_toan');
		$tien_thua_thanh_toan=$this->transaction_model->get_tong_tien_thua_thanh_toan($dataDB['code_contract']);
		//tên nhà đầu tư
		if (!empty($dataDB['investor_code']) && !empty($data_investor[$dataDB['investor_code']]['name'])) {
			$dataDB['investor_name'] = $data_investor[$dataDB['investor_code']]['name'];
		} else {
			$dataDB['investor_name'] = '';
		}
		$ky_thanh_toan_xa_nhat =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['ky_thanh_toan_xa_nhat'];
		$so_tien_phat_sinh =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['so_tien_phat_sinh'];
		$so_ngay_qua_han =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['so_ngay_qua_han'];
		//phí gia hạn
		$phi_gia_han_origin= isset($dataDB['fee']['extend']) ? (int)$dataDB['fee']['extend'] :  200000;
		//tiền giảm trừ bảo hiểm khoản vay
		$dataDB['tien_giam_tru_bhkv'] = isset($dataDB['tien_giam_tru_bhkv']) ? $dataDB['tien_giam_tru_bhkv'] : 0;
		$dataDB['total_money_paid'] = $total_money_paid;
		$dataDB['total_money_paid_now'] = $total_money_paid_now;
		$dataDB['difference_day_payment'] = $so_ngay_chenh_lech_tt_tt;
		$dataDB['actual_difference_payment'] = $total_money_paid_now-$total_money_paid;
		$dataDB['penalty_pay'] = $penalty_pay_tt;
		$dataDB['penalty_now'] = $penalty_now_tt;
		$dataDB['ngay_ket_thuc'] = $ngay_ket_thuc;
		$dataDB['money_difference'] = $money_difference;
		$dataDB['total_money_remaining'] = $total_money_remaining;
		$dataDB['tong_thanh_toan'] = $tong_thanh_toan;
		$dataDB['total_paid'] = $total_paid;
		$dataDB['da_thanh_toan'] = $da_thanh_toan;
		$dataDB['tien_thua_tat_toan'] = $tien_thua_tat_toan;
		$dataDB['ky_cham_tra']=$ky_cham_tra;
		$dataDB['phi_phat_sinh']=$phi_phat_sinh_ngay_thanh_toan;
		$dataDB['phi_phat_sinh_hien_tai']=$phi_phat_sinh_hien_tai;
		$dataDB['phi_phat_sinh_da_tra']=$phi_phat_sinh_da_tra;
		$dataDB['phi_phat_sinh_ngay_thanh_toan']=$phi_phat_sinh_ngay_thanh_toan;
		$dataDB['tong_phi_phat_sinh']=$tong_phi_phat_sinh;
		$dataDB['tong_penalty_con_lai']=$tong_penalty_con_lai;
		$dataDB['tong_so_ngay_trong_ky']=$tong_so_ngay_trong_ky;
		$dataDB['tien_du_ky_truoc']=$tien_du_ky_truoc;
		$dataDB['tien_chua_tra_ky_thanh_toan']=$tien_chua_tra_ky_thanh_toan;
		$dataDB['ky_thanh_toan_xa_nhat']=$ky_thanh_toan_xa_nhat;
		$dataDB['so_ngay_qua_han']=$so_ngay_qua_han;
		$dataDB['so_tien_phat_sinh']=$so_tien_phat_sinh;
		$dataDB['tien_thua_thanh_toan']=$tien_thua_thanh_toan;
		$dataDB['date_pay']=$date_pay;
		$dataDB['tien_con_no']=$tien_con_no;
		$dataDB['phi_gia_han']=$phi_gia_han_origin;
		$dataDB['tong_so_tien_thieu']=$tong_so_tien_thieu;
		$dataDB['tien_thieu_gia_han']=$tien_thieu_gia_han;
		

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'contract' => $dataDB,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function debt_detail_post()
	{
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		if (empty($this->dataPost['id'])) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại id"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		$now = (isset($this->dataPost['date_pay'])) ? strtotime($this->dataPost['date_pay'].' 23:59:59') : 0;
		
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($this->dataPost['id'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if($now==0)
		{
		$now=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($dataDB['code_contract'])).' 23:59:59') ;
	     }
		// hop dong
		$fee = array();
		
		$tien_gian_ngan = (int)$dataDB['loan_infor']['amount_money'];
		// tinh toan
		$total_money_remaining = 0;
		$kiem_tra_tat_toan = false;
		// $date_now = date('d-m-Y');
		// $now = strtotime($date_now);
		$all_contract_tempo = $this->contract_tempo_model->find_where(array('code_contract' => $dataDB['code_contract'], 'status' => 1));
		$hinh_thuc_vay = $dataDB['loan_infor']['type_loan']['code'];
		$so_ngay_vay = (isset($this->contract_model->get_phi_phat_cham_tra($this->dataPost['id'],$now)['so_ngay_vay'])) ? (int)$this->contract_model->get_phi_phat_cham_tra($this->dataPost['id'],$now)['so_ngay_vay'] : (int)$dataDB['loan_infor']['number_day_loan'];
		$period_pay_interest = 30;
		$so_ky_vay = $so_ngay_vay / $period_pay_interest;
		$type_interest = (int)$dataDB['loan_infor']['type_interest'];
		// ngay giai ngan
		$timestamp_ngay_giai_ngan = $dataDB['disbursement_date'];
		$timestamp_ngay_tat_toan = $timestamp_ngay_giai_ngan + $so_ngay_vay * 24 * 3600 - 24 * 60 * 60;
		$datediff = $now - $timestamp_ngay_tat_toan;
		$tong_tien_lai_phi_tat_toan = 0;
		$tien_phi_phat_tra_cham = 0;
		if ($so_ngay_vay == 30) {
			$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_chenh_lech = $so_ngay_vay_thuc_te - $so_ngay_vay;
			$tien_goc_con = $tien_gian_ngan;
		
		} else {
			
			$condition_success = array(
				'status' => 2,
				'code_contract' => $dataDB['code_contract'],
			);
			$contract_success = $this->contract_tempo_model->find_where_success($condition_success);
			if (!empty($contract_success)) { // hop dong da thanh toan ky lai
				$ngay_tra_lai_ky = $contract_success[count($contract_success) - 1]['ngay_ky_tra'];
				// ngay giai ngan
				$ngay_tra_lai_ky_gan_nhat = date('d-m-Y', $ngay_tra_lai_ky);
				$timestamp_tra_lai_ky_gan_nhat = strtotime($ngay_tra_lai_ky_gan_nhat);
				$datediff = $now - $timestamp_tra_lai_ky_gan_nhat;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			} else {
				
				$datediff = $now - $timestamp_ngay_giai_ngan;
				$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			}
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_da_vay = round($datediff / (60 * 60 * 24));
			$so_ngay_da_vay = $so_ngay_da_vay ;
			$so_ngay_chenh_lech = $so_ngay_da_vay - $so_ngay_vay;
		
		}
		$so_ngay_vay_thuc_te =0;
		$timestamp_ngay_giai_ngan =0;
		$datediff =0;
        
		$timestamp_ngay_giai_ngan = strtotime(date('Y-m-d',$dataDB['disbursement_date']). ' 00:00:00');
		
		$datediff = strtotime(date('Y-m-d',$now). ' 00:00:00') - $timestamp_ngay_giai_ngan;
		$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24))+1;
		//$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
		$so_ngay_vay_thuc_te=($so_ngay_vay_thuc_te < 0) ? 1 : $so_ngay_vay_thuc_te;
		
			$get_goc_chua_tra_den_thoi_diem_dao_han_1 = $this->contract_model->get_goc_chua_tra_den_thoi_diem_dao_han_1($dataDB['code_contract'],$now);
	
			$get_goc_chua_tra_den_thoi_diem_dao_han_2 = $this->contract_model->get_goc_chua_tra_den_thoi_diem_dao_han_2($dataDB['code_contract'],$now);
		
		$phi_tat_toan_truoc_han_new = $this->contract_model->get_phi_tat_toan_truoc_han($dataDB, $now,$so_ngay_vay,$so_ngay_vay_thuc_te);
		//var_dump($phi_tat_toan_truoc_han_new); die;
		//Get lãi + phí đến thời điểm đáo hạn
		$laiPhiDenThoiDiemDaoHan = $this->lai_phi_bang_ky_den_thoi_diem_dao_han($dataDB['code_contract']);
		$res = array(
			'laiPhiDenThoiDiemDaoHan' => $laiPhiDenThoiDiemDaoHan,
			'total_paid' => !empty($tong_tien_thanh_toan) ? $tong_tien_thanh_toan : 0,
			'da_thanh_toan' => !empty($da_thanh_toan) ? $da_thanh_toan : 0,
			'tien_thua_tat_toan' => !empty($tien_thua_tat_toan) ? $tien_thua_tat_toan : 0,
			'so_ngay_vay_thuc_te' => !empty($so_ngay_vay_thuc_te) ? $so_ngay_vay_thuc_te : 0,
			'so_ngay_da_vay_hop_dong' => !empty($so_ngay_da_vay) ? $so_ngay_da_vay : 0,
			'so_ngay_vay' => !empty($so_ngay_vay) ? $so_ngay_vay : 0,
			'lai_ky' => !empty($lai_ky) ? $lai_ky : 0,
			'phi_tham_dinh' => !empty($phi_tham_dinh) ? $phi_tham_dinh : 0,
			'phi_tu_van' => !empty($phi_tu_van) ? $phi_tu_van : 0,
			'tien_goc_con' => !empty($tien_goc_con) ? $tien_goc_con : 0,
			'phi_thanh_toan_truoc_han' => $phi_tat_toan_truoc_han_new,
			'ngay_giai_ngan' => !empty($timestamp_ngay_giai_ngan) ? $timestamp_ngay_giai_ngan : 0,
			'get_goc_chua_tra_den_thoi_diem_dao_han_1' => $get_goc_chua_tra_den_thoi_diem_dao_han_1,
			'get_goc_chua_tra_den_thoi_diem_dao_han_2' => $get_goc_chua_tra_den_thoi_diem_dao_han_2,
			'now' => date("d-m-Y",$now)
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $res,
			'message' => 'Success'
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function lai_phi_bang_ky_den_thoi_diem_dao_han($contractCode)
	{
		//Lấy thông tin kì hiện tại
		$tempPlan = $this->temporary_plan_contract_model->find_where(array(
			"code_contract" => $contractCode
		));
		$tong_lai_phi_con_lai_den_thoi_diem_dao_han = 0;
		foreach ($tempPlan as $item) {
			$tien_lai_1ky_con_lai = !empty($item['tien_lai_1ky_con_lai']) ? $item['tien_lai_1ky_con_lai'] : 0;
			$tien_phi_1ky_con_lai = !empty($item['tien_phi_1ky_con_lai']) ? $item['tien_phi_1ky_con_lai'] : 0;
			$tong_lai_phi_con_lai_den_thoi_diem_dao_han = $tong_lai_phi_con_lai_den_thoi_diem_dao_han +
				$tien_lai_1ky_con_lai +
				$tien_phi_1ky_con_lai;
		}
		return $tong_lai_phi_con_lai_den_thoi_diem_dao_han;
	}
	//lấy gốc lãi phí đến thời điểm tất toán
	public function get_infor_tat_toan_part_1_post()
	{
		$data = $this->input->post();
		$code_contract = $this->security->xss_clean($data['code_contract']);
		$date_pay = (!empty($data['date_pay'])) ? strtotime($data['date_pay'] . ' 23:59:59') : 0;
		if($date_pay==0)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($data['code_contract'])).' 23:59:59') ;
	     }

		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($code_contract,$date_pay);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $get_infor_tat_toan_part_1
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	/*
     * Tiền lãi còn nợ thực tế : từ thời điểm hiện tại đến ngày_ky_tra
     * Tiền phí còn nợ thực tế : từ thời điểm hiện tại đến ngày_ky_tra
     */

	public function get_infor_tat_toan_part_2_post()
	{
		$data = $this->input->post();
		$code_contract = $this->security->xss_clean($data['code_contract']);
		$date_pay = (!empty($data['date_pay'])) ? strtotime($data['date_pay'] . ' 23:59:59') : 0;
		if($date_pay==0)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($data['code_contract'])).' 23:59:59') ;
	     }
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($code_contract,$date_pay);
		$goc_lai_phi_con_lai_den_ngay_thanh_toan = $this->temporary_plan_contract_model->goc_lai_phi_con_lai_den_ngay_thanh_toan($code_contract, $date_pay);
		$goc_lai_phi_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($code_contract);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'lai_con_no_thuc_te' => !empty($get_infor_tat_toan_part_2['lai_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['lai_con_no_thuc_te'] : 0,
			'phi_con_no_thuc_te' =>  !empty($get_infor_tat_toan_part_2['phi_con_no_thuc_te']) ? $get_infor_tat_toan_part_2['phi_con_no_thuc_te'] : 0,

			'lai_con_lai_phai_tra_cua_ki_tiep_theo' => !empty($get_infor_tat_toan_part_2['lai_con_lai_phai_tra_cua_ki_tiep_theo']) ? $get_infor_tat_toan_part_2['lai_con_lai_phai_tra_cua_ki_tiep_theo'] : 0,
			'phi_con_lai_phai_tra_cua_ki_tiep_theo' =>  !empty($get_infor_tat_toan_part_2['phi_con_lai_phai_tra_cua_ki_tiep_theo']) ? $get_infor_tat_toan_part_2['phi_con_lai_phai_tra_cua_ki_tiep_theo'] : 0,
			'so_ngay_no_thuc_te' => !empty($get_infor_tat_toan_part_2['so_ngay_no_thuc_te']) ? $get_infor_tat_toan_part_2['so_ngay_no_thuc_te'] : 0,
			'ngay_trong_ky' =>  !empty($get_infor_tat_toan_part_2['ngay_trong_ky']) ? $get_infor_tat_toan_part_2['ngay_trong_ky'] : 0,
			'goc_chua_tra_co_cau' => !empty($goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['goc_chua_tra']) ? $goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['goc_chua_tra'] : 0,
			'lai_chua_tra_co_cau' => !empty($goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['lai_chua_tra']) ? $goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['lai_chua_tra'] : 0,
			'phi_chua_tra_co_cau' => !empty($goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['phi_chua_tra']) ? $goc_lai_phi_con_lai_den_ngay_thanh_toan[0]['phi_chua_tra'] : 0,
			'goc_chua_tra_qua_han' => !empty($goc_lai_phi_chua_tra[0]['goc_chua_tra']) ? $goc_lai_phi_chua_tra[0]['goc_chua_tra'] : 0,
			'lai_chua_tra_qua_han' => !empty($goc_lai_phi_chua_tra[0]['lai_chua_tra']) ? $goc_lai_phi_chua_tra[0]['lai_chua_tra'] : 0,
			'phi_chua_tra_qua_han' => !empty($goc_lai_phi_chua_tra[0]['phi_chua_tra']) ? $goc_lai_phi_chua_tra[0]['phi_chua_tra'] : 0,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function countGiaoDichTatToanChoDuyet_post()
	{
		$data = $this->input->post();
		$count = $this->transaction_model->count(array(
			"code_contract" => $data['code_contract'],
			"payment_method" => array('$in' => array("1", "2")),
			"type" => 3,
			"status" => array('$in' => array(2, 4))
		));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'count' => $count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
	public function get_bang_lai_ky_tat_toan_post()
	{
		$data = $this->input->post();

		$res = $this->temporary_plan_contract_model->find_where_order_by(
			array("code_contract" => $data['code_contract']),
			array("ngay_ky_tra" => "ESC")
		);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $res
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

	public function get_transaction_thanh_toan_lai_ky_tai_ky_tat_toan_post()
	{
		$arrTrans = array();
		$data = $this->input->post();

		$ki_tat_toan = $this->temporary_plan_contract_model->findOne(
			array("code_contract" => $data['code_contract'],
				"ki_khach_hang_tat_toan" => 1)
		);
		if (!empty($ki_tat_toan)) {
			$arrTrans = $this->transaction_model->find_where(array(
				"temporary_plan_contract_id" => $ki_tat_toan['_id'],
				"type" => 4
			));
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arrTrans
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
	public function countGiaoDichThanhToanChoDuyet_post()
	{
		$data = $this->input->post();
		$count = $this->transaction_model->count(array(
			"code_contract" => $data['code_contract'],
			"payment_method" => array('$in' => array("1", "2")),
			"type" => 4,
			"status" => array('$in' => array(2, 4, 11))
		));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'count' => $count
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}
}

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Payment_model extends CI_Model
{

	private $collection = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("transaction_model");
		$this->load->model("contract_model");
		$this->load->model("transaction_model");
		$this->load->model("tempo_contract_accounting_model");
		$this->load->model("contract_tempo_model");
		$this->load->model("transaction_extend_model");
		$this->load->model("investor_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		date_default_timezone_set('Asia/Ho_Chi_Minh');
	}

   public function get_payment($data)
	{


		$data['id_contract'] = $this->security->xss_clean($data['id_contract']);
		$date_pay = $data['date_pay'];
		if (empty($data['id_contract'])) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại id"
			);
			
			return $response;
		}
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại hợp đồng"
			);
			
			return $response;
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
	 //     if($dataDB['status']==33 || $dataDB['status']==34)
		// {
		// $date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_gh_cc($dataDB['code_contract'])).' 23:59:59') ;
		// $current_day=$date_pay;
	 //     }
		
		
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
		$ky_thanh_toan=0;
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
			foreach ($contract as $c) {
				$penalty=0;

				//$c['disbursement_date'] = $dataDB['disbursement_date'];
				$c['amount_money'] = $dataDB['loan_infor']['amount_money'];
				$time_disbursement_date=$dataDB['disbursement_date'] ;
				$penalty_con_lai=0;
				$penalty_da_tra=0;
				if ($c['status'] == 2)
				{
					
					$tien_chua_tra_ky_thanh_toan += (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'];
				}
               
                $penalty_con_lai=(isset($c['tien_phi_cham_tra_1ky_con_lai'])) ? (float)$c['tien_phi_cham_tra_1ky_con_lai'] : 0;
			    $penalty_da_tra=(isset($c['tien_phi_cham_tra_1ky_da_tra'])) ? (float)$c['tien_phi_cham_tra_1ky_da_tra'] : 0;
				

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
					$last_contract_tempo = $this->contract_tempo_model->findOne(array('code_contract' => $dataDB['code_contract'], 'ky_tra' => $c['ky_tra']-1));
				}

				$ky_han_truoc=(isset($last_contract_tempo['ngay_ky_tra'])) ? $last_contract_tempo['ngay_ky_tra'] : 0;
				$time_period = $this->contract_model->tinh_so_ngay_trong_ky_cham_tra($c['ky_tra'],$time_disbursement_date,strtotime(date('Y-m-d',$c['ngay_ky_tra']).' 23:59:59'),$ky_han_truoc);
				$c['so_ngay_trong_ky']=$time_period;
				$tong_so_ngay_trong_ky+=$time_period;
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - ($c['so_ngay_trong_ky']-1) * 24 * 3600)) && $c['ky_tra']==1) { // 5 ngay tieu chuan
					$total_money_paid_now = $total_money_paid_now + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;
					$ngay_ky_tra=$c['ngay_ky_tra'];
					$total_money_paid = $total_money_paid + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;

				}
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - (5 * 24 * 3600))) && $c['ky_tra']>1) { // 5 ngay tieu chuan
					$total_money_paid_now = $total_money_paid_now + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;
					$ngay_ky_tra=$c['ngay_ky_tra'];
					$total_money_paid = $total_money_paid + (float)$c['tien_tra_1_ky']  - (float)$c['da_thanh_toan'] + $penalty_con_lai;

				}
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra']+1* 24 * 3600) )) {
					$ky_cham_tra++;
				}
				if ($c['status'] == 2) {
					$ky_thanh_toan++;
				}
                
                if($c['ngay_ky_tra']>$date_pay && $date_pay < $contract[count($contract)-1]['ngay_ky_tra'])
                {
                	 $tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }
                 if( $c['status'] == 1 && $c['ngay_ky_tra'] < $date_pay && $date_pay < $contract[count($contract)-1]['ngay_ky_tra'])
                {
                	$tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }
                 if( $date_pay > $contract[count($contract)-1]['ngay_ky_tra'] && $c['status'] == 1)
                {
                	 $tien_du_ky_truoc+=(float)$c['da_thanh_toan']+(float)$c['tien_phi_cham_tra_1ky_da_tra'];
                }

				$penalty_now =$this->contract_model->tinh_phi_phat($c['tien_tra_1_ky'],$dataDB['fee']['penalty_percent'],$dataDB['fee']['penalty_amount'],$so_ngay_cham_tra_now,$time_period);
				$penalty_pay =$this->contract_model->tinh_phi_phat($c['tien_tra_1_ky'],$dataDB['fee']['penalty_percent'],$dataDB['fee']['penalty_amount'],$so_ngay_cham_tra_pay,$time_period);
				$phat_qua_han_chua_tra=(isset($c['tien_phi_cham_tra_1ky_con_lai'])) ? (float)$c['tien_phi_cham_tra_1ky_con_lai'] : 0;
				$penalty_now_tt+=$penalty_now+$phat_qua_han_chua_tra;
				$penalty_pay_tt+=$penalty_pay+$phat_qua_han_chua_tra;

				$c['penalty_now']=$penalty_now+$phat_qua_han_chua_tra;


				if ($c['status'] == 1) {
					$total_money_remaining += (isset($c['tien_goc_1ky_con_lai'])) ? (int)$c['tien_goc_1ky_con_lai'] : 0;
				}
				$penalty= (isset($c['fee_delay_pay'])) ? (float)$c['fee_delay_pay'] : $penalty_pay;
				$tong_thanh_toan+=(float)$c['tien_tra_1_ky']  + $penalty;
			}
			if ($contract[0]['status'] == 1) {
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
		if(!empty($dataDB['code_contract_parent_gh']))
		{
		$tong_so_tien_thieu=$this->transaction_model->get_so_tien_thieu($dataDB['code_contract_parent_gh']);
	     }

		
		if($tong_phi_phat_sinh>0)
		{
			$phi_phat_sinh_hien_tai=$phi_phat_sinh_chua_tra_hien_tai;
			$phi_phat_sinh_ngay_thanh_toan=$phi_phat_sinh_chua_tra_ngay_thanh_toan;
		}
		$tong_thanh_toan+=$tong_phi_phat_sinh;
		$total_paid +=$phi_phat_sinh_da_tra;
		if( $date_pay==0)
		{
			$so_ngay_chenh_lech_tt_tt=0;
		}else{
			$so_ngay_chenh_lech_tt_tt= intval(($current_day - $date_pay) / (24 * 60 * 60));
		}

		$total_money_paid +=  $penalty_pay_tt+$phi_phat_sinh_ngay_thanh_toan+$tien_chua_tra_ky_thanh_toan+$tong_so_tien_thieu ;
		$total_money_paid_now+=$penalty_now_tt+$phi_phat_sinh_hien_tai+$tien_chua_tra_ky_thanh_toan+$tong_so_tien_thieu;


        
		$da_thanh_toan=$this->transaction_model->get_da_thanh_toan($dataDB['code_contract']);
		$tien_thua_tat_toan=$this->transaction_model->sum_where(array('code_contract'=>$dataDB['code_contract'],"status"=>1,"type"=>3),'$tien_thua_tat_toan');
		$tien_thua_thanh_toan=$this->transaction_model->get_tong_tien_thua_thanh_toan($dataDB['code_contract']);
		if (!empty($dataDB['investor_code']) && !empty($data_investor[$dataDB['investor_code']]['name'])) {
			$dataDB['investor_name'] = $data_investor[$dataDB['investor_code']]['name'];
		} else {
			$dataDB['investor_name'] = '';
		}
		$ky_thanh_toan_xa_nhat =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['ky_thanh_toan_xa_nhat'];
		$so_tien_phat_sinh =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['so_tien_phat_sinh'];
		$so_ngay_qua_han =$this->contract_model->get_phi_phat_sinh($dataDB,$date_pay)['so_ngay_qua_han'];
		$phi_gia_han_origin= isset($dataDB['fee']['extend']) ? (int)$dataDB['fee']['extend'] :  200000;
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
		$dataDB['ky_thanh_toan']=$ky_thanh_toan;
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
		$response = array(
			'status' => '200',
			'contract' => $dataDB,
			'message' => 'Success'
		);
		
		return $response;
	}
	//lấy phí thanh toán trước hạn
	public function debt_detail($data)
	{

		$data['id_contract'] = $this->security->xss_clean($data['id_contract']);
		if (empty($data['id_contract'])) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại id"
			);
			
			return $response;
		}
		$now = (isset($data['date_pay'])) ? strtotime($data['date_pay'].' 23:59:59') : 0;
		
		$dataDB = $this->contract_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($data['id_contract'])));
		if (empty($dataDB)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại hợp đồng"
			);
			
			return $response;
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
		if (empty($all_contract_tempo)) {
			$response = array(
				'status' => '401',
				'message' => "Không tồn tại lãi kỳ của hợp đồng"
			);
			
			return $response;
		}
		$hinh_thuc_vay = $dataDB['loan_infor']['type_loan']['code'];
		$so_ngay_vay = (isset($this->contract_model->get_phi_phat_cham_tra($data['id'],$now)['so_ngay_vay'])) ? (int)$this->contract_model->get_phi_phat_cham_tra($data['id'],$now)['so_ngay_vay'] : (int)$dataDB['loan_infor']['number_day_loan'];
		$period_pay_interest = 30;
		$so_ky_vay = $so_ngay_vay / $period_pay_interest;
		$type_interest = (int)$dataDB['loan_infor']['type_interest'];
		// ngay giai ngan
		$timestamp_ngay_giai_ngan = $dataDB['disbursement_date'];
		$timestamp_ngay_tat_toan = $timestamp_ngay_giai_ngan + $so_ngay_vay * 24 * 3600 - 24 * 60 * 60;
		$datediff = $now - $timestamp_ngay_tat_toan;
		$tong_tien_lai_phi_tat_toan = 0;
		$tien_phi_phat_tra_cham = 0;
	
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
		
		
		return $res;
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
	//lấy dư nợ gốc lãi phí theo tất toán
	public function get_infor_tat_toan_part_1($data)
	{
		
		$code_contract = $this->security->xss_clean($data['code_contract']);
		$date_pay = $data['date_pay'];
		if($date_pay==0)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($data['code_contract'])).' 23:59:59') ;
	     }

		$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($code_contract,$date_pay);
	
		return $get_infor_tat_toan_part_1;
	}

	/*
     * Tiền lãi còn nợ thực tế : từ thời điểm hiện tại đến ngày_ky_tra
     * Tiền phí còn nợ thực tế : từ thời điểm hiện tại đến ngày_ky_tra
     */

	public function get_infor_tat_toan_part_2($data)
	{
		
		$code_contract = $this->security->xss_clean($data['code_contract']);
		$date_pay =$data['date_pay'];
		if($date_pay==0)
		{
		$date_pay=strtotime(date('Y-m-d', $this->transaction_model->get_ngay_thanh_toan_ky_chua_tra($data['code_contract'])).' 23:59:59') ;
	     }
		$get_infor_tat_toan_part_2 = $this->contract_model->get_infor_tat_toan_part_2($code_contract,$date_pay);
		$goc_lai_phi_con_lai_den_ngay_thanh_toan = $this->temporary_plan_contract_model->goc_lai_phi_con_lai_den_ngay_thanh_toan($code_contract, $date_pay);
		$goc_lai_phi_chua_tra = $this->temporary_plan_contract_model->goc_lai_phi_chua_tra($code_contract);
		$response = array(
			'status' => '200',
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
		
		return $response;
	}

    public function delete_lai_ky_lai_thang($data_post)
	{
		$code_contract = $data_post['code_contract'];
		$acount = new Tempo_contract_accounting_model();
		$tempo = new Contract_tempo_model();
		$tran_extend = new Transaction_extend_model();
		if (empty($code_contract)) {
			$response = array(
				'status' => '401',
				'message' => "Hợp đồng không tồn tại"
			);
			
			return $response;
		}
        $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $code_contract));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	$response = array(
				'status' => '401',
				'message' => "Hợp đồng đã khóa"
			);
			
			return $response;
        }
		$acount->delete_all(array("code_contract" => $code_contract));
		$tempo->delete_all(array("code_contract" => $code_contract));
		$tran_extend->delete_all(array("code_contract" => $code_contract));
		if (isset($data_post['type_gh']) && $data_post['type_gh'] == 'origin') {
			$tran_extend->delete_all(array("code_contract_parent_gh" => $data_post['code_contract']));
		}
		$data_transaction = $this->transaction_model->find_where_pay_all(array('code_contract' => $code_contract, 'type' => array('$in' => array(3, 4))));

		if (!empty($data_transaction)) {
			foreach ($data_transaction as $key => $value) {
				$transDB = $this->transaction_model->findOneAndUpdate(
					array("_id" => $value['_id']),
					array(
						"so_tien_lai_da_tra" => 0,
						"temporary_plan_contract_id" => '',
						"so_tien_phi_da_tra" => 0,
						"so_tien_goc_da_tra" => 0,
						"phi_phat_sinh" => 0,
						"tien_phi_phat_sinh_da_tra" => 0,
						"fee_delay_pay" => array(),
						"tien_thua_tat_toan" => 0,
						"so_ngay_phat_sinh" => 0,
						"so_tien_phat_sinh" => 0,
						"tien_thua_thanh_toan" => 0,
						"tien_thua_thanh_toan_con_lai" => 0,
						"tien_thua_thanh_toan_da_tra" => 0,
						"fee_finish_contract" => 0,
						"so_tien_phi_cham_tra_da_tra" => 0,
						"so_tien_phi_gia_han_da_tra" => 0,
						"chia_mien_giam" => [],
						"phai_tra_hop_dong" => [],
						"tat_toan_phai_tra" => [],
						"so_tien_goc_phai_tra_tat_toan" => 0,
						"so_tien_lai_phai_tra_tat_toan" => 0,
						"so_tien_phi_phai_tra_tat_toan" => 0,
						"so_tien_phi_cham_tra_phai_tra" => 0,
						"so_tien_phi_cham_tra_phai_tra_tat_toan" => 0,
						"so_tien_phi_gia_han_phai_tra_tat_toan" => 0,
						"so_tien_phi_phat_sinh_phai_tra_tat_toan" => 0,
						"so_tien_thieu" => 0,
						"so_tien_thieu_da_chuyen" => 0,
						"so_tien_thieu_con_lai" => 0,
						"con_lai_sau_thanh_toan" => [],
						    "goc_lai_phi_phai_tra"=> [],
							"tong_tien_tat_toan"=> 0,
							"ky_da_tt_gan_nhat" => 0,
						    "date_pay_tt" => 0,

					)
				);
			}
		}
		if (isset($data_post['type_gh']) && $data_post['type_gh'] == 'origin') {
			$data_transaction_origin = $this->transaction_model->find_where_pay_all(array('code_contract_parent_gh' => $data_post['code_contract'], 'type' => array('$in' => array(3, 4))));

			if (!empty($data_transaction_origin)) {
				foreach ($data_transaction_origin as $key => $value) {
					$transDB = $this->transaction_model->findOneAndUpdate(
						array("_id" => $value['_id']),
						array(
							"so_tien_lai_da_tra" => 0,
							"temporary_plan_contract_id" => '',
							"so_tien_phi_da_tra" => 0,
							"so_tien_goc_da_tra" => 0,
							"phi_phat_sinh" => 0,
							"tien_phi_phat_sinh_da_tra" => 0,
							"fee_delay_pay" => array(),
							"tien_thua_tat_toan" => 0,
							"so_ngay_phat_sinh" => 0,
							"so_tien_phat_sinh" => 0,
							"tien_thua_thanh_toan" => 0,
							"tien_thua_thanh_toan_con_lai" => 0,
							"tien_thua_thanh_toan_da_tra" => 0,
							"fee_finish_contract" => 0,
							"so_tien_phi_cham_tra_da_tra" => 0,
							"so_tien_phi_gia_han_da_tra" => 0,
							"code_contract_parent_gh" => "",
							"type_payment" => 1,
							"chia_mien_giam"=>[],
							"phai_tra_hop_dong"=>[],
							"tat_toan_phai_tra"=>[],
							"so_tien_goc_phai_tra_tat_toan"=>0,
							"so_tien_lai_phai_tra_tat_toan"=>0,
							"so_tien_phi_phai_tra_tat_toan"=>0,
							"so_tien_phi_cham_tra_phai_tra"=>0,
							"so_tien_phi_cham_tra_phai_tra_tat_toan"=>0,
							"so_tien_phi_gia_han_phai_tra_tat_toan"=>0,
							"so_tien_phi_phat_sinh_phai_tra_tat_toan"=>0,
							"so_tien_thieu" => 0,
							"so_tien_thieu_da_chuyen" => 0,
							"so_tien_thieu_con_lai" => 0,
							"con_lai_sau_thanh_toan" => [],
							 "goc_lai_phi_phai_tra"=> [],
							"tong_tien_tat_toan"=> 0,
							"ky_da_tt_gan_nhat" => 0,
						    "date_pay_tt" => 0,

						)
					);
				}
			}
		}
		if (isset($data_post['type_cc']) && $data_post['type_cc'] == 'origin') {
			$data_transaction_origin = $this->transaction_model->find_where_pay_all(array('code_contract_parent_cc' => $data_post['code_contract'], 'type' => array('$in' => array(3, 4))));

			if (!empty($data_transaction_origin)) {
				foreach ($data_transaction_origin as $key => $value) {
					$transDB = $this->transaction_model->findOneAndUpdate(
						array("_id" => $value['_id']),
						array(
						"code_contract" => $data_post['code_contract'],
							"so_tien_lai_da_tra" => 0,
							"temporary_plan_contract_id" => '',
							"so_tien_phi_da_tra" => 0,
							"so_tien_goc_da_tra" => 0,
							"phi_phat_sinh" => 0,
							"tien_phi_phat_sinh_da_tra" => 0,
							"fee_delay_pay" => array(),
							"tien_thua_tat_toan" => 0,
							"so_ngay_phat_sinh" => 0,
							"so_tien_phat_sinh" => 0,
							"tien_thua_thanh_toan" => 0,
							"tien_thua_thanh_toan_con_lai" => 0,
							"tien_thua_thanh_toan_da_tra" => 0,
							"fee_finish_contract" => 0,
							"so_tien_phi_cham_tra_da_tra" => 0,
							"so_tien_phi_gia_han_da_tra" => 0,
							"so_tien_phi_cham_tra_phai_tra"=>0,
							"code_contract_parent_cc" => "",
							"type_payment" => 1,
							"chia_mien_giam"=>[],
							"phai_tra_hop_dong"=>[],
							"tat_toan_phai_tra"=>[],
							"so_tien_goc_phai_tra_tat_toan"=>0,
							"so_tien_lai_phai_tra_tat_toan"=>0,
							"so_tien_phi_phai_tra_tat_toan"=>0,
							"so_tien_phi_cham_tra_phai_tra_tat_toan"=>0,
							"so_tien_phi_gia_han_phai_tra_tat_toan"=>0,
							"so_tien_phi_phat_sinh_phai_tra_tat_toan"=>0,
							"so_tien_thieu" => 0,
							"so_tien_thieu_da_chuyen" => 0,
							"so_tien_thieu_con_lai" => 0,
							"con_lai_sau_thanh_toan" => [],
							 "goc_lai_phi_phai_tra"=> [],
							"tong_tien_tat_toan"=> 0,
							"ky_da_tt_gan_nhat" => 0,
						    "date_pay_tt" => 0,

						)
					);
				}
			}
		}
		$data = array(
			"status_disbursement" => 2,
			"status_run_fee_again" => 1
			
		);
		$this->contract_model->update(
			array("code_contract" => $code_contract),
			$data
		);
		$response = array(
			'status' => '200',
			'message' => $code_contract,
		);
		return $response;
	}





}
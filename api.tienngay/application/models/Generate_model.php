<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Generate_model extends CI_Model
{

	private $collection = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		    $this->load->model('investor_model');
        $this->load->model('fee_loan_model');
        $this->load->model('contract_model');
        $this->load->model('contract_tempo_model');
        $this->load->model('tempo_contract_accounting_model');
        $this->load->model('log_contract_tempo_model');
        $this->load->helper('lead_helper');
         $this->load->model('coupon_model');
       
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}

    public function processGenerate($data){

		//Check mã phiếu ghi
		if(empty($data['code_contract'])) {
                    $response = array(
                        'status' => '401',
                        'message' => "Không tồn tại mã hợp đồng"
                    );
                    
                    return $response;
                        
		}
		 $contract_ck = $this->contract_model->findOne(array("code_contract" =>  $data['code_contract']));
        if( isset($contract_ck['contract_lock']) && $contract_ck['contract_lock']=='lock')
        {
        	$response = array(
				'status' => '401',
				'message' => "Hợp đồng đã khóa"
			);
			
			return $response;
        }
		//kiểm tra ngày giải ngân
		if(empty($data['disbursement_date'])) {
                    $response = array(
                            'status' => '401',
                            'message' => "Không tồn tại ngày giải ngân"
                    );
                   
                    return $response;
                        
		}
		//kiểm tra mã nhà đầu tư
		if(empty($data['investor_code'])) {
                    $response = array(
                            'status' => '401',
                            'message' => "Không có nhà đầu tư"
                    );
                    echo json_encode($response);
                    return;

		}
		$contract = $this->contract_model->findOne(array('code_contract' => $data['code_contract']));
		if (empty($contract['status_disbursement']) || $contract['status_disbursement'] != 2) {
                    $response = array(
                            'status' => '401',
                            'message' => "Hợp đồng không phù hợp trạng thái giải ngân",
							'data' => $contract
                    );
                 
                    return $response;

		}
		// check exist tempo contract
		if (!empty($contract)) {
			$amount_money = isset($contract['loan_infor']['amount_money']) ? intval($contract['loan_infor']['amount_money']) : 0;
			$code_contract = $data['code_contract'];
			$customer_info = isset($contract['customer_infor']) ? $contract['customer_infor'] : '';
			$number_day_loan = isset($contract['loan_infor']['number_day_loan']) ? intval($contract['loan_infor']['number_day_loan']) : 0;
			$period_pay_interest = isset($contract['loan_infor']['period_pay_interest']) ? intval($contract['loan_infor']['period_pay_interest']) : 30;
			$type_interest = isset($contract['loan_infor']['type_interest']) ? $contract['loan_infor']['type_interest'] : 0;
			$disbursement_date = intval($data['disbursement_date']);
			$insurrance = isset($contract['loan_infor']['insurrance_contract']) ? boolval($contract['loan_infor']['insurrance_contract']) : false;
			$investor_code = $data['investor_code'];
			$code_coupon = isset($contract['loan_infor']['code_coupon']) ? $contract['loan_infor']['code_coupon'] : '';
			$data_coupon = $this->coupon_model->findOne(array("code" => $code_coupon));
            $is_reduction_interest = isset($data_coupon['is_reduction_interest']) ? $data_coupon['is_reduction_interest'] : "deactive";
			$down_interest_on_month = isset($data_coupon['down_interest_on_month']) ? $data_coupon['down_interest_on_month'] : "deactive";
			// Check HĐ vay có coupon giảm lãi trừ vào kỳ cuối, có thỏa mãn điều kiện hưởng coupon hay không
			if ($is_reduction_interest == 'active' || $down_interest_on_month == 'active') {
				$type_payment = !empty($data['type_payment']) ? $data['type_payment'] : '';
				$type_transaction = !empty($data['type_transaction']) ? $data['type_transaction'] : '';
				$current_day = strtotime(date('Y-m-d') . ' 23:59:59');
				$date_pay_input = !empty($data['date_pay']) ? strtotime(date('Y-m-d', $data['date_pay']) . ' 23:59:59') : $current_day;
				$date_pay = !empty($date_pay_input) ? $date_pay_input : $current_day;
				$check_date_pay_late = !empty($contract['interest_reduction']['isset_date_late']) ? $contract['interest_reduction']['isset_date_late'] : false;
				$interest_reduction = !empty($data['interest_reduction']) ? $data['interest_reduction'] : '';
				// Nếu thanh toán quá hạn hoặc phát sinh số ngày chậm trả => không được hưởng ưu đãi coupon giảm lãi
				if ( !empty($contract['expire_date']) && ( ($date_pay > strtotime(date('Y-m-d', $contract['expire_date']) . ' 23:59:59')) || $check_date_pay_late == true) ) {
					if ($down_interest_on_month == 'active') {
						$down_interest_on_month = "deactive";
					}
					if ($is_reduction_interest == 'active') {
						$is_reduction_interest = "deactive";
					}
				}
				// Cron reset lãi đã trừ kỳ cuối do quá hạn hợp đồng
				if (!empty($interest_reduction) && $interest_reduction == 'deactive') {
					if ($down_interest_on_month == 'active') {
						$down_interest_on_month = "deactive";
					}
					if ($is_reduction_interest == 'active') {
						$is_reduction_interest = "deactive";
					}
				}
				if ($type_payment == 1 && $type_transaction == 3) {
					// Nếu tất toán trước hạn => không được hưởng ưu đãi coupon giảm lãi
					if ($date_pay < strtotime(date('Y-m-d', $contract['expire_date']) . ' 23:59:59')) {
						if ($down_interest_on_month == 'active') {
							$down_interest_on_month = "deactive";
						}
						if ($is_reduction_interest == 'active') {
							$is_reduction_interest = "deactive";
						}
					}
				}
			}

			$this->spreadsheetFeeLoan($code_contract,$customer_info ,$investor_code, $disbursement_date , $amount_money, $number_day_loan, $period_pay_interest, $type_interest,$insurrance,$is_reduction_interest,$down_interest_on_month);
			$this->generateFeeLoanbyMonth($code_contract,$customer_info ,$investor_code, $disbursement_date , $amount_money, $number_day_loan, $period_pay_interest, $type_interest,$insurrance);
			$investor = $this->investor_model->findOne(array('status' => 'active', 'code' => $investor_code));
			if($contract['status']==9 || $contract['status']==15 || $contract['status']==10 || $contract['status']==16  || $contract['status']==19)
			{
			$dataContract['status'] = 17; // update created data tempo
		    }
			$dataContract['status_disbursement'] = 3; // update created data tempo
			$dataContract['investor_code'] = $investor_code; // update investor
			$dataContract['investor_infor'] = $investor; // update investor
			$dataContract['expire_date'] = $this->periodDays(date('Y-m-d',$disbursement_date),$number_day_loan/30,$disbursement_date,$period_pay_interest)['date'];
			// update expire date
			$dataContract['disbursement_date'] = intval($data['disbursement_date']); // update date
			$this->contract_model->findOneAndUpdate(array('code_contract' => $contract['code_contract']), $dataContract);
             $this->debt_recovery_one($code_contract);
			//Insert log
			$insertLog = array(
				"type" => "update",
				"table" => 'contract',
				"new" => $dataContract,
				"created_at" => $this->createdAt
			);
			$this->log_contract_tempo_model->insert($insertLog);

			$response = array(
                            'status' => '200',
                            'message' => 'Thành công'
			);
                        return $response;

		} else {
			$response = array(
                            'status' => '401',
                            'message' => "Không tồn tại hợp đồng"
			);
                        
                        return $response;

		}
	}
	// chạy lại trường debt contract
	public function debt_recovery_one($code_contract)
	{

		$c = $this->contract_model->findOne(array('code_contract' => $code_contract));
           
            if (isset($c['code_contract'])) {
                    $cond = array(
                        'code_contract' => $c['code_contract'],
                        
                    );
                }
       $da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$c['code_contract'],'status'=>1,'type'=>array('$in'=>[4,5])),'$total');
       $da_thanh_toan_gh_cc=$this->transaction_model->findOne(array('code_contract'=>$c['code_contract'],'status'=>array('$ne'=>3),'type_payment'=>array('$gt'=>1)));
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

                    foreach ($detail as $de) {

                        $total_paid += (isset($de['tien_tra_1_ky'])) ? $de['tien_tra_1_ky'] : 0;
                        $total_goc +=  (isset($de['tien_goc_1ky_con_lai'])) ? $de['tien_goc_1ky_con_lai'] : 0;
                        $total_phi +=  (isset($de['tien_phi_1ky_con_lai'])) ? $de['tien_phi_1ky_con_lai'] : 0;
                        $total_lai +=  (isset($de['tien_lai_1ky_con_lai'])) ? $de['tien_lai_1ky_con_lai'] : 0;
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
               if( $current_day>=$ky_tt_xa_nhi && $c['loan_infor']['type_interest']==2   && strtotime(date('Y-m-d').' 00:00:00') >=$c['disbursement_date']){
                 $check_gia_han=1;
                  }
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
        private  function periodDays($start_date,$per,$disbursement_date,$period_pay_interest){
         $date_ngay_t= strtotime($this->config->item("date_t_apply"));
         $ngay_ky_tra=0;
        if($disbursement_date < $date_ngay_t)
        {
        $ngay_ky_tra = $disbursement_date +  (intval($period_pay_interest) * 24 *60 *60 * $per) - 24*60*60;
        $so_ngay =$period_pay_interest;
        return array('date'=>$ngay_ky_tra,'days'=> $so_ngay);
        }else{
		$from = new DateTime($start_date);
		$day = $from->format('j');
		$from->modify('first day of this month');
		$period = new DatePeriod($from, new DateInterval('P1M'), $per);
		$arr_date=[];
		foreach ($period as $date) {
		    $lastDay = clone $date;
		    $lastDay->modify('last day of this month');
		    $date->setDate($date->format('Y'), $date->format('n'), $day);
		    if ($date > $lastDay) {
		       $date = $lastDay;
		    }
		    $arr_date[]= $date->format('Y-m-d');
		}
		$datetime1 = new DateTime($arr_date[$per-1]);

		$datetime2 = new DateTime($arr_date[$per]);

		$difference = $datetime1->diff($datetime2);
		$days=$difference->days;
		   if($per==1)
		   $days=$days+1;

		return array('date'=>strtotime($arr_date[$per]),'days'=>$days);
		}
	  }
	   private  function time_timestamp($start_date,$per){
       
         $ngay_ky_tra=0;
      
		$from = new DateTime($start_date);
		$day = $from->format('j');
		$from->modify('first day of this month');
		$period = new DatePeriod($from, new DateInterval('P1M'), $per);
		$arr_date=[];
		foreach ($period as $date) {
		    $lastDay = clone $date;
		    $lastDay->modify('last day of this month');
		    $date->setDate($date->format('Y'), $date->format('n'), $day);
		    if ($date > $lastDay) {
		       $date = $lastDay;
		    }
		    $arr_date[]= $date->format('Y-m-d');
		}
		$datetime1 = new DateTime($arr_date[$per-1]);

		$datetime2 = new DateTime($arr_date[$per]);

		$difference = $datetime1->diff($datetime2);
		   
		return array('date'=>strtotime($arr_date[$per]),'days'=> $difference->days);
		
	  }
	private function spreadsheetFeeLoan($code_contract, $customer_info,$investor_code, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $insurrance,$is_reduction_interest,$down_interest_on_month){
//		$amount_money  tong tien
//		$type_loan  hinh thuc vay
//		$number_day_loan  tong so ngay vay
//		$period_pay_interest   so ngay thuc te 1 ky
//		$type_interest   hinh thuc tra lai
//		$insurrance  bao hiem

		// get thông tin phí vay
		$contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
		$fee = array();
		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		if (!empty($contract['fee'])) {
			$fee = $contract['fee'];
			if (empty($fee['percent_advisory'])) {
				$fee['percent_advisory'] = 0;
				$pham_tram_phi_tu_van = 0;
			} else {
				$pham_tram_phi_tu_van = floatval($fee['percent_advisory'])/100;
			}
			if (empty($fee['percent_expertise'])) {
				$fee['percent_expertise'] = 0;
				$pham_tram_phi_tham_dinh = 0;
			} else {
				$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise'])/100;
			}
			if (empty($fee['percent_interest_customer'])) {
				$fee['percent_interest_customer'] = 0;
				$lai_suat_ndt = 0;
			} else {
				$lai_suat_ndt = floatval($fee['percent_interest_customer'])/100;
			}
		}
		$tien_goc = $amount_money;

		// số kỳ vay
		$so_ky_vay = (int)$number_day_loan/(int)$period_pay_interest;
		$tien_giam_tru_bhkv = isset($contract['tien_giam_tru_bhkv']) ? $contract['tien_giam_tru_bhkv'] : 0;

		$lai_luy_ke = 0;
		$goc_lai_1_ky =0;
		$tong_lai_3ky=0;
		$lai_ki_dau = 0;
		//Hinh thức lãi dư nợ giảm dần
		if($type_interest == 1){
			//tiền trả 1 kỳ pow(2, -3)
            $goc_lai_1_ky = -pmt($lai_suat_ndt,$so_ky_vay,$tien_goc);
			// tong tien phai tra 1 ky
			$tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
			//tiền trả 1 kỳ làm tròn
			$round_tien_tra_1_ky = round($tien_tra_1_ky);

			//gốc còn lại
			$tien_goc_con = $tien_goc;
			//tong cac loai phi
            $so_ngay = 0;
			//khoan vay 1 ky
			for( $i = 1;$i<= $so_ky_vay;$i++){
				//kỳ trả
				$date_ky_tra = $this->periodDays(date('Y-m-d',$disbursement_date),$i,$disbursement_date,$period_pay_interest)['date'];
                $so_ngay = $this->periodDays(date('Y-m-d',$disbursement_date),$i,$disbursement_date,$period_pay_interest)['days'];
				$ky_tra = $i;
				$current_plan = $i == 1 ? $i : 2;
				//lãi
				$lai_ky = $lai_suat_ndt*$tien_goc_con;
				  if($is_reduction_interest=="active")
			        {
			        if($ky_tra==1 || $ky_tra==2 || $ky_tra==3)
			        {
			          $tong_lai_3ky+=$lai_ky;
			        }
			         if($ky_tra== $so_ky_vay )
			        {
			          $tien_tra_1_ky=$tien_tra_1_ky-$tong_lai_3ky;
			          $round_tien_tra_1_ky = round($tien_tra_1_ky);
			        }
			       } elseif ($down_interest_on_month == "active") {
					  if ($ky_tra == 1) {
						  $lai_ki_dau += $lai_ky;
					  }
					  if ($ky_tra == $so_ky_vay) {
						  // trừ tiền coupon có giảm lãi 1 tháng đầu vào kỳ cuối
						  $tien_tra_1_ky = $tien_tra_1_ky - $lai_ki_dau;
						  $round_tien_tra_1_ky = round($tien_tra_1_ky);
					  }
				  }
			       if($tien_giam_tru_bhkv>0)
		              {
		              
		               if($ky_tra== $so_ky_vay )
		              {
		                $tien_tra_1_ky=$tien_tra_1_ky-$tien_giam_tru_bhkv;
		                $round_tien_tra_1_ky = round($tien_tra_1_ky);
		              }
		             }
				// goc da tra
				$tien_goc_1ky = $goc_lai_1_ky - $lai_ky;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
				$lai_luy_ke = $lai_luy_ke + $tong_phi_lai;
				//tiền gốc
				//tiền gốc còn lại
				$tien_goc_con = $tien_goc_con - $tien_goc_1ky;
				//tiền tất toán
				$data_1ky = array(
					'code_contract' => $code_contract,
					'code_contract_disbursement' => $code_contract_disbursement,
					'type' => $type_interest,
					'current_plan' => $current_plan, // kỳ hiện tại phải đóng
					'customer_infor' => $customer_info,
					'investor_code' => $investor_code,
					'ky_tra' => $ky_tra,
					'so_ngay' => $so_ngay,
					'ngay_ky_tra' => $date_ky_tra,
					'tien_tra_1_ky' => $tien_tra_1_ky,
					'round_tien_tra_1_ky' => $round_tien_tra_1_ky,
					'tien_goc_1ky' => $tien_goc_1ky,
					'tong_phi_lai' => $tong_phi_lai,
					'phi_tu_van' => $phi_tu_van,
					'phi_tham_dinh' => $phi_tham_dinh,
					'lai_ky' => $lai_ky,
					'lai_luy_ke' => $lai_luy_ke,
					'tien_goc_con' => $tien_goc_con,
					'da_thanh_toan' => 0,
					'status' => 1, // 1: sap toi, 2: da dong, 3: qua han
					'created_at' => $this->createdAt,
                                    
                    "tien_goc_1ky_phai_tra" => $tien_goc_1ky,
                    "tien_goc_1ky_da_tra" => 0,
                    "tien_goc_1ky_con_lai" => $tien_goc_1ky,
                
                    "tien_lai_1ky_phai_tra" => $lai_ky,
                    "tien_lai_1ky_da_tra" => 0,
                    "tien_lai_1ky_con_lai" => $lai_ky,
                
                    "tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
                    "tien_phi_1ky_da_tra" => 0,
                    "tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,

                  
                    "tien_phi_cham_tra_1ky_da_tra" => 0,
                    "tien_phi_cham_tra_1ky_con_lai" => 0,

                    "fee_delay_pay"  =>0, 
                    "so_ngay_cham_tra"  => 0
                                    
				);
				$this->contract_tempo_model->insert($data_1ky);
				//Insert log
				$insertLog = array(
					"type" => "insert",
					"new" => $data_1ky,
					"created_at" => $this->createdAt
				);
				$this->log_contract_tempo_model->insert($insertLog);

			}
			 return;
		}else{

			//hình thức lãi hàng tháng, gốc cuối kỳ
			//khoan vay 1 ky
			for( $i = 1;$i<= $so_ky_vay;$i++){
				//kỳ trả
				$date_ky_tra =$this->periodDays(date('Y-m-d',$disbursement_date),$i,$disbursement_date,$period_pay_interest)['date'];
                $so_ngay = $this->periodDays(date('Y-m-d',$disbursement_date),$i,$disbursement_date,$period_pay_interest)['days'];
				$ky_tra = $i;
				$current_plan = $i == 1 ? $i : 2;
				//lãi
				$lai_ky = round($lai_suat_ndt*$tien_goc);
				 
				// goc da tra
				$tien_goc_1ky = $i == $so_ky_vay ? $tien_goc : 0;
				//tiền gốc còn lại
				$tien_goc_con = $i == $so_ky_vay ? 0 : $tien_goc;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi_lai = $phi_tu_van + $phi_tham_dinh + $lai_ky;
				$tien_tra_1_ky = $tien_goc_1ky + $tong_phi_lai;
				 $round_tien_tra_1_ky = round($tien_tra_1_ky);
				$lai_luy_ke = $lai_luy_ke + $tong_phi_lai;
                 if($is_reduction_interest=="active")
			        {
			        if($ky_tra==1 || $ky_tra==2 || $ky_tra==3)
			        {
			          $tong_lai_3ky+=$lai_ky;
			        }
			         if($ky_tra== $so_ky_vay )
			        {
			          $tien_tra_1_ky=$tien_tra_1_ky-$tong_lai_3ky;
			          $round_tien_tra_1_ky = round($tien_tra_1_ky);
			        }
			       } elseif ($down_interest_on_month == "active") {
					 if ($ky_tra == 1) {
						 $lai_ki_dau += $lai_ky;
					 }
					 if ($ky_tra == $so_ky_vay) {
						 // trừ tiền coupon có giảm lãi 1 tháng đầu vào kỳ cuối
						 $tien_tra_1_ky = $tien_tra_1_ky - $lai_ki_dau;
						 $round_tien_tra_1_ky = round($tien_tra_1_ky);
					 }
				 }
			       if($tien_giam_tru_bhkv>0)
		              {
		              
		               if($ky_tra== $so_ky_vay )
		              {
		                $tien_tra_1_ky=$tien_tra_1_ky-$tien_giam_tru_bhkv;
		                $round_tien_tra_1_ky = round($tien_tra_1_ky);
		              }
		             }
				$data_1ky = array(
					'code_contract' => $code_contract,
					'code_contract_disbursement' => $code_contract_disbursement,
					'customer_infor' => $customer_info,
					'investor_code' => $investor_code,
					'type' => $type_interest,
					'current_plan' => $current_plan, // kỳ hiện tại phải đóng
					'ky_tra' => $ky_tra,
					'so_ngay' => $so_ngay,
					'ngay_ky_tra' => $date_ky_tra,
					'tien_tra_1_ky' => $tien_tra_1_ky,
					'round_tien_tra_1_ky' => $round_tien_tra_1_ky,
					'tien_goc_1ky' => $tien_goc_1ky,
					'tien_goc_con' => $tien_goc_con,
					'da_thanh_toan' => 0,
					'phi_tu_van' => $phi_tu_van,
					'phi_tham_dinh' => $phi_tham_dinh,
					'lai_ky' => $lai_ky,
					'lai_luy_ke' => $lai_luy_ke,
					'status' => 1,  // 1: sap toi, 2: da dong, 3: qua han
					'created_at' => $this->createdAt,
                                    
                    "tien_goc_1ky_phai_tra" => $tien_goc_1ky,
                    "tien_goc_1ky_da_tra" => 0,
                    "tien_goc_1ky_con_lai" => $tien_goc_1ky,
                
                    "tien_lai_1ky_phai_tra" => $lai_ky,
                    "tien_lai_1ky_da_tra" => 0,
                    "tien_lai_1ky_con_lai" => $lai_ky,
                
                    "tien_phi_1ky_phai_tra" => $phi_tu_van + $phi_tham_dinh,
                    "tien_phi_1ky_da_tra" => 0,
                    "tien_phi_1ky_con_lai" => $phi_tu_van + $phi_tham_dinh,
                    
                    "tien_phi_cham_tra_1ky_da_tra" => 0,
                    "tien_phi_cham_tra_1ky_con_lai" => 0,
                    "fee_delay_pay"  =>0, 
                    "so_ngay_cham_tra"  => 0
                                    
				);
				$this->contract_tempo_model->insert($data_1ky);
				//Insert log
				$insertLog = array(
					"type" => "insert",
					"new" => $data_1ky,
					"created_at" => $this->createdAt
				);
				$this->log_contract_tempo_model->insert($insertLog);
			}
			 return;
		}
	}

private function generateFeeLoanbyMonth($code_contract, $customer_info,$investor_code, $disbursement_date, $amount_money, $number_day_loan, $period_pay_interest, $type_interest, $insurrance){
//		$amount_money  tong tien
//		$type_loan  hinh thuc vay
//		$number_day_loan  tong so ngay vay
//		$period_pay_interest   so ngay thuc te 1 ky
//		$type_interest   hinh thuc tra lai
//		$insurrance  bao hiem

		// get thông tin phí vay

		$contract = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$code_contract_disbursement = !empty($contract['code_contract_disbursement']) ? $contract['code_contract_disbursement'] : "";
		$fee = array();
		// lai suat tu van
		$pham_tram_phi_tu_van = 0;
		// lai suat tham dinh
		$pham_tram_phi_tham_dinh = 0;
		// lai suat nha dau tu
		$lai_suat_ndt = 0;
		if (!empty($contract['fee'])) {
			$fee = $contract['fee'];
			if (empty($fee['percent_advisory'])) {
				$fee['percent_advisory'] = 0;
				$pham_tram_phi_tu_van = 0;
			} else {
				$pham_tram_phi_tu_van = floatval($fee['percent_advisory'])/100;
			}
			if (empty($fee['percent_expertise'])) {
				$fee['percent_expertise'] = 0;
				$pham_tram_phi_tham_dinh = 0;
			} else {
				$pham_tram_phi_tham_dinh = floatval($fee['percent_expertise'])/100;
			}
			if (empty($fee['percent_interest_customer'])) {
				$fee['percent_interest_customer'] = 0;
				$lai_suat_ndt = 0;
			} else {
				$lai_suat_ndt = floatval($fee['percent_interest_customer'])/100;
			}
		}
		$tien_goc = $amount_money;

		// số kỳ vay
		$so_ky_vay = (int)$number_day_loan/(int)$period_pay_interest;

		$lai_luy_ke_thang = 0;
		//Hinh thức lãi dư nợ giảm dần
		if($type_interest == 1){
			//tiền trả 1 kỳ pow(2, -3)
			$goc_lai_1_ky = -pmt($lai_suat_ndt,$so_ky_vay,$tien_goc);
			// tong tien phai tra 1 ky
			$tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
			//tiền trả 1 kỳ làm tròn
			$round_tien_tra_1_ky = round($tien_tra_1_ky);

			//gốc còn lại
			$tien_goc_con = $tien_goc;
			$tien_goc_con_thang = $tien_goc;
			//tong cac loai phi
//			$tong_phi_tu_van = 0;
//			$tong_phi_tham_dinh  = 0;
			//khoan vay 1 ky
			$tien_lai_chenh_lech = 0;
			$tien_goc_chenh_lech = 0;
			$tien_goc_1thang = 0;
			$so_ngay_lai_con_lai = 0;
			$du_no_lai_thang_truoc = 0;
			$du_no_phi_thang_truoc = 0;
			$tien_lai_1thang_hien_tai = 0;
			$tien_tra_1thang_thang_sau = 0;
			$tien_lai_1thang_thang_sau = 0;
			$tien_goc_1thang_thang_sau = 0;
			$tong_phi_lai_thang_sau = 0;
			$tong_phi_hien_tai = 0;
			$lai_con_lai_luy_ke = 0;
			$phi_con_lai_luy_ke = 0;
			$goc_con_lai_thang_hien_tai = 0;
			$goc_con_lai_chua_thu_thang_truoc = 0;
			$du_no_goc_thang_truoc = 0;
			 $thang_dau_tien=0;
			 $ngay_trong_thang_dau_tien=0;
			for( $i = 0;$i<= $so_ky_vay;$i++){
				//kỳ trả
                
	           
				// phi tu van quan ly
				if($i==0)
                 {
                   $date_ky_tra =$disbursement_date;
                   $time_timestamp =$disbursement_date;
                  
                 }else{
				    $date_ky_tra = $this->periodDays(date('Y-m-d',$contract['disbursement_date']),$i,$disbursement_date,30)['date'];
				    $time_timestamp = $this->time_timestamp(date('Y-m-d',$contract['disbursement_date']),$i)['date'];
			     }
				 $date_ky_tra_tinh_lai = $this->periodDays(date('Y-m-d',$contract['disbursement_date']),$i+1,$disbursement_date,30)['date'];
				$lai_ky = $this->temporary_plan_contract_model->findOne(array(
								"code_contract" => $code_contract,"ngay_ky_tra"=>$date_ky_tra_tinh_lai))['lai_ky'];
                if(empty( $lai_ky))
                {
                	$lai_ky =0;
                }
				$tien_lai_1thang_BC_thang = $lai_ky;
				
				$tien_lai_1thang_hien_tai_ = $lai_ky;
				// goc da tra
				$tien_goc_1ky = $tien_goc;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi = $phi_tu_van + $phi_tham_dinh ;
				
				//tiền gốc
				 $period_pay_interest=days_in_month(date('m', $date_ky_tra),date('Y', $date_ky_tra));
				 
				$so_ngay_trong_thang=days_in_month(date('m', $date_ky_tra),date('Y', $date_ky_tra));
               $day_last_month = date("Y-m-t".' 23:59:59', $date_ky_tra);
				$last_date = strtotime($day_last_month);
				$datediff = $last_date - $date_ky_tra;
				$count = intval($datediff / (60 * 60 * 24));
				
				$time = date('m/Y', $date_ky_tra);
				$month = date('m', $date_ky_tra);
				$year = date('Y', $date_ky_tra);
               

				// goc da tra
				if ($i == 0) {
					$date_ngay_t= strtotime($this->config->item("date_t_apply"));
					if($disbursement_date >= $date_ngay_t)
					{
                    $count=$count+1;
                    } 
					$ngay_lai_thuc_te = $count;
					$tien_tra_1thang_hien_tai =  ($tien_tra_1_ky/(int)$period_pay_interest)*$count;
					$tien_goc_1thang = $tien_goc_1ky;
					$tien_phi_1thang_hien_tai = ($tong_phi/(int)$so_ngay_trong_thang)*$ngay_lai_thuc_te;
					$du_no_lai_thang_truoc = 0;
					$du_no_phi_thang_truoc = 0;
					$du_no_goc_thang_truoc = 0;
					$tien_goc_1thang = 0;
					$tien_phi_1thang = 0;
					$tien_lai_1thang = 0;
					$date_ngay_t= strtotime($this->config->item("date_t_apply"));
					
					 $thang_dau_tien=(int)$so_ngay_trong_thang;

					 $ngay_trong_thang_dau_tien=$ngay_lai_thuc_te;
					
					
				} else if ($i == $so_ky_vay) {
					
                    $so_ngay_lai_con_lai=$thang_dau_tien-$ngay_trong_thang_dau_tien;

                    $du_no_goc_thang_truoc = $tien_goc_1thang ;
					$du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tong_phi_hien_tai;
					$du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai_;
					$tien_tra_1thang_hien_tai = $tien_tra_1thang_thang_sau;
					$tien_goc_1thang = $tien_goc_1thang_thang_sau;
					$tien_phi_1thang_hien_tai = ($tong_phi/(int)$thang_dau_tien)*($so_ngay_lai_con_lai);
					
					
                   
				} else {
                    $du_no_goc_thang_truoc = $tien_goc_1thang ;
					$du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai_;
					$du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tong_phi;
					// $ngay_lai_thuc_te = $count + $so_ngay_lai_con_lai;
					// $date_ngay_t= strtotime($this->config->item("date_t_apply"));
					// if($disbursement_date >= $date_ngay_t)
					// {
                    $ngay_lai_thuc_te = $so_ngay_trong_thang;
                    //}
					$tien_tra_1thang_hien_tai =  ($tien_tra_1_ky/(int)$period_pay_interest)*$count + $tien_tra_1thang_thang_sau;
					$tien_phi_1thang_hien_tai = ($tong_phi/(int)$so_ngay_trong_thang)*$ngay_lai_thuc_te;
					
                   
				}

				//tiền gốc còn lại
				

				if ($i == $so_ky_vay) {
					$data_1thang = array(
						'code_contract' => $code_contract,
						"code_contract_disbursement" => $code_contract_disbursement,
						'type' => $type_interest,
						'time_timestamp' => $time_timestamp,
						'date_ky_tra' => $date_ky_tra,
						'time' => $time,
						'month' => $month,
						'year' => $year,
						'count_date_interest' => $so_ngay_lai_con_lai,
						'so_ngay_trong_thang'=>$so_ngay_trong_thang,
						'so_ngay_trong_thang_dau'=>$thang_dau_tien,
						
                                            
						'tien_goc_1thang' => $tien_goc_1ky,
						'tien_lai_1thang' => $tien_lai_1thang_hien_tai_,
						'tien_phi_1thang' => $tien_phi_1thang_hien_tai,
						
                                                
						'tien_goc_1thang_da_tra' => 0,
						'tien_lai_1thang_da_tra' => 0,
						'tien_phi_1thang_da_tra' => 0,

					
						'phi_phat_sinh_1thang_da_tra' => 0,
						'phi_tat_toan_1thang_da_tra' => 0,
						'phi_gia_han_1thang_da_tra' => 0,
						'phi_cham_tra_1thang_da_tra' => 0,
						'tien_thua_tat_toan' => 0,
                                                
                                            
					                   
                      
                        
						'created_at' => $this->createdAt,
						'status' => 1,  // 1: sap toi, 2: da dong
					);
					
						$this->tempo_contract_accounting_model->insert($data_1thang);
					
					return;
				} else {
					//lãi
					$data_1thang = array(
						'code_contract' => $code_contract,
						"code_contract_disbursement" => $code_contract_disbursement,
						'type' => $type_interest,
						'time_timestamp' => $time_timestamp,
						'date_ky_tra' => $date_ky_tra,
						'time' => $time,
						'month' => $month,
						'year' => $year,
						'count_date_interest' => $ngay_lai_thuc_te,
						'so_ngay_trong_thang'=>$so_ngay_trong_thang,

                                            
						'tien_goc_1thang' => $tien_goc_1thang,
						'tien_lai_1thang' => $tien_lai_1thang_hien_tai_,
						'tien_phi_1thang' => $tien_phi_1thang_hien_tai,
						
                                            
                        'tien_goc_1thang_da_tra' => 0,
						'tien_lai_1thang_da_tra' => 0,
						'tien_phi_1thang_da_tra' => 0,
                                                
                      
                       
						'tien_thua_tat_toan' => 0,                   
						
                                            
						'created_at' => $this->createdAt,
						'status' => 1, // 1: sap toi, 2: da dong
					);
					
						$this->tempo_contract_accounting_model->insert($data_1thang);
					
					$so_ngay_lai_con_lai = (int)$period_pay_interest - $count;
				}
			}
			 return;

		}else{

			//hình thức lãi hàng tháng, gốc cuối kỳ
			//khoan vay 1 ky
			$so_ngay_lai_con_lai = 0;
			$du_no_goc_thang_truoc = 0;
			$du_no_lai_thang_truoc = 0;
			$du_no_phi_thang_truoc = 0;
			$tien_lai_1thang_hien_tai = 0;
			$tien_phi_1thang_hien_tai = 0;
			$lai_con_lai_luy_ke = 0;
			$phi_con_lai_luy_ke = 0;
			$goc_con_lai_thang_hien_tai = 0;
			$tong_phi_hien_tai = 0;
			$goc_con_lai_chua_thu_thang_truoc = 0;
			$tien_goc_1thang = 0;
			$thang_dau_tien=0;
			$ngay_trong_thang_dau_tien=0;
			$count =0;
			//tiền trả 1 kỳ pow(2, -3)
			$goc_lai_1_ky = -pmt($lai_suat_ndt,$so_ky_vay,$tien_goc);
			// tong tien phai tra 1 ky
			$tien_tra_1_ky = $goc_lai_1_ky + ($pham_tram_phi_tu_van + $pham_tram_phi_tham_dinh) * $tien_goc;
			for( $i = 0;$i<= $so_ky_vay;$i++){
				//kỳ trả
               
				if($i==0)
                 {
                   $date_ky_tra =$disbursement_date;
                   $time_timestamp =$disbursement_date;
                  
                 }else{
				$date_ky_tra = $this->periodDays(date('Y-m-d',$disbursement_date),$i,$disbursement_date,30)['date'];
				 $time_timestamp = $this->time_timestamp(date('Y-m-d',$contract['disbursement_date']),$i)['date'];
			     }
			      $day_last_month = date("Y-m-t"." 23:59:59", $date_ky_tra);
				$last_date = strtotime($day_last_month);
				
			    $datediff = $last_date - $date_ky_tra;
				$count = intval($datediff / (60 * 60 * 24));
				
				
	            $period_pay_interest=days_in_month(date('m', $date_ky_tra),date('Y', $date_ky_tra));
				 
				$so_ngay_trong_thang=days_in_month(date('m', $date_ky_tra),date('Y', $date_ky_tra));
				
				
				
				$time = date('m/Y', $date_ky_tra);
				$month = date('m', $date_ky_tra);
				$year = date('Y', $date_ky_tra);
                $lai_ky = round($lai_suat_ndt*$tien_goc);
				$tien_lai_1thang_hien_tai_ = $lai_ky;
				//tiền gốc còn lại
				$tien_goc_con = $i == $so_ky_vay ? 0 : $tien_goc;
				// phi tu van quan ly
				$phi_tu_van = $pham_tram_phi_tu_van * $tien_goc;
				// phi tham dinh
				$phi_tham_dinh = $pham_tram_phi_tham_dinh * $tien_goc;
				//tổng phí lãi 1 kỳ
				$tong_phi = $phi_tu_van + $phi_tham_dinh ;
              
			

				// goc da tra
				if ($i == 0) {
					$du_no_goc_thang_truoc = 0;
					$du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
					$du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
					$tien_goc_1thang = $tien_goc;
					// $date_ngay_t= strtotime($this->config->item("date_t_apply"));
					// if($disbursement_date >= $date_ngay_t)
					// {
                    $count=$count+1;
                   // }                    
					$ngay_lai_thuc_te = $count;
					$tien_tra_1thang_hien_tai =  ($tien_tra_1_ky/(int)$so_ngay_trong_thang)*$ngay_lai_thuc_te;
					$tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai + $tien_goc_1thang;
					
					$tien_lai_1thang_hien_tai = ($lai_ky/(int)$so_ngay_trong_thang)*$ngay_lai_thuc_te;
					
					$tien_phi_1thang_hien_tai = ($tong_phi/(int)$so_ngay_trong_thang)*($ngay_lai_thuc_te);
					
					$thang_dau_tien=(int)$so_ngay_trong_thang;
					$ngay_trong_thang_dau_tien=$ngay_lai_thuc_te;
                    $goc_con_lai_thang_hien_tai = $tien_goc_1thang;
                    $tong_phi_hien_tai = $tong_phi;
					$goc_con_lai_chua_thu_thang_truoc = $tien_goc;

				} else if ($i == $so_ky_vay) {
					$so_ngay_lai_con_lai=$thang_dau_tien-$ngay_trong_thang_dau_tien;
					$du_no_goc_thang_truoc =   $tien_goc_1thang;
					$du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
					$du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
					
					$tien_goc_1thang = $tien_goc;
                                        
					$tien_tra_1thang_hien_tai = $tien_tra_1thang_hien_tai + $tien_goc_1thang;
					
					$tien_lai_1thang_hien_tai = ($lai_ky/$thang_dau_tien)*($so_ngay_lai_con_lai);
					
					$tien_phi_1thang_hien_tai = ($tong_phi/$thang_dau_tien)*($so_ngay_lai_con_lai);
					
				} else {
					$du_no_goc_thang_truoc = 0;
					$du_no_lai_thang_truoc = $du_no_lai_thang_truoc + $tien_lai_1thang_hien_tai;
					$du_no_phi_thang_truoc = $du_no_phi_thang_truoc + $tien_phi_1thang_hien_tai;
					$tien_goc_1thang = 0;
					// $ngay_lai_thuc_te = $count + $so_ngay_lai_con_lai;
					// $date_ngay_t= strtotime($this->config->item("date_t_apply"));
					// if($disbursement_date >= $date_ngay_t)
					// {
                    $ngay_lai_thuc_te = $so_ngay_trong_thang;
                   // } 
					$tien_tra_1thang_hien_tai =  ($tien_tra_1_ky/(int)$period_pay_interest)*$ngay_lai_thuc_te;
					
					$tien_lai_1thang_hien_tai = ($lai_ky/(int)$so_ngay_trong_thang)*$ngay_lai_thuc_te;

					$tien_phi_1thang_hien_tai = ($tong_phi/(int)$so_ngay_trong_thang)*($ngay_lai_thuc_te);
				}
				if ($i == 0) {
					$du_no_goc_thang_truoc = 0;
					$du_no_lai_thang_truoc = 0;
					$du_no_phi_thang_truoc = 0;
					$goc_con_lai_chua_thu_thang_truoc = 0;
					$tien_goc_1thang = 0;
					$tien_phi_1thang = 0;
					$tien_lai_1thang = 0;

				}
				if ($i == $so_ky_vay) {
					
					$data_1thang = array(
						'code_contract' => $code_contract,
						"code_contract_disbursement" => $code_contract_disbursement,
						'type' => $type_interest,
						'time_timestamp' => $time_timestamp,
						'date_ky_tra' => $date_ky_tra,
						'time' => $time,
						'month' => $month,
						'year' => $year,
						'count_date_interest' => $so_ngay_lai_con_lai,
						'so_ngay_trong_thang'=>$so_ngay_trong_thang,
						'so_ngay_trong_thang_dau'=>$thang_dau_tien,
                                            
						'tien_goc_1thang' => $tien_goc_1thang,
						'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
						'tien_phi_1thang' => $tien_phi_1thang_hien_tai,
						
                                                
                        'tien_goc_1thang_da_tra' => 0,
						'tien_lai_1thang_da_tra' => 0,
						'tien_phi_1thang_da_tra' => 0,

						'phi_phat_sinh_1thang_da_tra' => 0,
						'phi_tat_toan_1thang_da_tra' => 0,
						'phi_gia_han_1thang_da_tra' => 0,
						'phi_cham_tra_1thang_da_tra' => 0,
						'tien_thua_tat_toan' => 0,
                       
                                            
					
                                            
						'created_at' => $this->createdAt,
						'status' => 1, // 1: sap toi, 2: da dong
					);
					
						$this->tempo_contract_accounting_model->insert($data_1thang);
					
					return;
				} else {
					//lãi
				
					$data_1thang = array(
						'code_contract' => $code_contract,
						"code_contract_disbursement" => $code_contract_disbursement,
						'type' => $type_interest,
						'time_timestamp' => $time_timestamp,
						'date_ky_tra' => $date_ky_tra,
						'time' => $time,
						'month' => $month,
						'year' => $year,
						'count_date_interest' => $ngay_lai_thuc_te,
						'so_ngay_trong_thang'=>$so_ngay_trong_thang,
						'tien_goc_1thang' => $tien_goc_1thang,
						'tien_lai_1thang' => $tien_lai_1thang_hien_tai,
						'tien_phi_1thang' => $tien_phi_1thang_hien_tai,
						
                                            
                        'tien_goc_1thang_da_tra' => 0,
						'tien_lai_1thang_da_tra' => 0,
						'tien_phi_1thang_da_tra' => 0,
                                            
                       
						'phi_phat_sinh_1thang_da_tra' => 0,
						'phi_tat_toan_1thang_da_tra' => 0,
						'phi_gia_han_1thang_da_tra' => 0,
						'phi_cham_tra_1thang_da_tra' => 0,
						'tien_thua_tat_toan' => 0,
                                            
						
                                            
                  
						'created_at' => $this->createdAt,
						'status' => 1, // 1: sap toi, 2: da dong
					);
					
						$this->tempo_contract_accounting_model->insert($data_1thang);
					
					$so_ngay_lai_con_lai = (int)$period_pay_interest - $count;
				}
			}
			 return;
		}
	}

	// Check HĐ có phát sinh ngày chậm trả hay không
	private function check_number_day_pay_late($code_contract)
	{
		$temporary_plan_contracts = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract]);
		$tempo_not_pay = $this->temporary_plan_contract_model->find_where(['code_contract' => $code_contract, 'status' => 1]);
		$current_day = !empty($date_pay) ? $date_pay : strtotime(date('Y-m-d') . '  23:59:59');
		$date_pay_tempo = !empty($tempo_not_pay[0]['ngay_ky_tra']) ? intval($tempo_not_pay[0]['ngay_ky_tra']) : $current_day;
		$time = intval(($current_day - strtotime(date('Y-m-d', $date_pay_tempo))) / (24 * 60 * 60));
		$is_payment_slow = false;
		if ($time > 0) {
			$is_payment_slow = true;
		}
		if (!empty($temporary_plan_contracts)) {
			//fore bảng lãi kỳ để xác định chậm trả
			foreach ($temporary_plan_contracts as $temporary) {
				if ($temporary['so_ngay_cham_tra'] > 0) {
					$is_payment_slow = true;
					break;
				}
			}
		}
		return $is_payment_slow;
	}







}

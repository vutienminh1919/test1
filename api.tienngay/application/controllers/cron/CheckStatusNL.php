<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/NL_Withdraw.php';

class CheckStatusNL extends CI_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('contract_model');
		$this->load->model('lead_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('log_contract_tempo_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->load->model('contract_tempo_model');
		$this->load->model('contract_extend_model');
		$this->load->model('transaction_model');
		$this->load->helper('lead_helper');
		$this->load->model('store_model');
		$this->load->model('area_model');
    $this->load->model('payment_model');
    $this->load->model('generate_model');
    $this->load->model('allocation_model');
		 date_default_timezone_set('Asia/Ho_Chi_Minh');
	}
  
	 	public function checklog()
	{
      $contractData=$this->contract_model->find_where_select(array('code_contract'=>['$in'=>["000006403","000007250"]]),['_id','code_contract']);
       print('_id,code_contract,log1,log2,log3 <br>');
       foreach ($contractData as $key => $value) {
           $log = $this->log_model->getLogs(array("contract_id" => (string)$value['_id']));
         	
           
           	
           foreach ($log as $key1 => $value1) {
           
           	if( $key1==0)
           	{
           		$status1="";
           		//var_dump($value1['data_post']['status']); die;

              $status1=$value1['data_post']['status'];
           	}
           		if( $key1==1)
           	{
           			$status2="";
           		$status3="";
           		$status2=$value1['new']['status'];
           		$status3=$value1['old']['status'];
           	}
           		if( $key1==2 && empty($status2) && empty($status3))
           	{
           			$status2="";
           		$status3="";
           		$status2=$value1['new']['status'];
           		$status3=$value1['old']['status'];
           	}
           		if( $key1==2 && empty($status2) && empty($status3))
           	{
           			$status2="";
           		$status3="";
           		$status2=$value1['data_post']['status'];
           		$status3=$value1['data_post']['status'];
           	}
           
           	 
           }
             print((string)$value['_id'].' ,
		               '.$value['code_contract'].' ,
		               '.$status1.' ,
		               '.$status2.' ,
		               '.$status3.' <br>');

       }
	}

	//báo cáo của Thanh công nợ
	public function get_bc_thanh_time()
	{
		// $time_d=strtotime('2021-10-31 23:59:59');
		$time_d = strtotime($_GET['date'].' 23:59:59');

		if ( isset($_GET['code']) ) {
	    	$query = array(
				'debt' => array('$exists' => true),
				'status'=>['$gt'=>3],
				'code_contract'=> $_GET['code']
			);
	    } else {
	    	$query = array(
				'debt' => array('$exists' => true),
				'status'=>['$gt'=>3]
			);
	    }

		  $contractData=$this->contract_model->find_where($query);
		 $header = 'Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Phòng GD,Hình thức vay,Phương thức tính lãi,Kỳ hạn (tháng),Thời gian cho vay thực tế (số ngày),Số tiền vay,Ngày giải ngân,Ngày gia hạn/ cơ cấu,Ngày đáo hạn,Số tiền gốc phải trả,Số tiền gốc đã thanh toán,Số tiền gốc còn lại,Số tiền lãi phải trả,Số tiền lãi đã thanh toán,Số tiền lãi còn lại,Số tiền phí phải trả,Số tiền phí đã thanh toán,Số tiền phí còn lại,Tổng tiền phải trả,Tổng tiền đã thanh toán,Tổng tiền còn lại,Số ngày chậm trả, Trạng thái';
		 $header = str_replace(array("\n", "\r"), '', $header);
		 echo $header;
		 echo("\r\n");
		 foreach ($contractData as $key => $value) {

		 $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
			$number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
			$amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
			 $pt_gh_cc=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'type_payment'=>array('$in'=>[2,3])));
			 $ngay_gh_cc='';
			 if(isset($pt_gh_cc['date_pay']))
			 	$ngay_gh_cc=$pt_gh_cc['date_pay'];
             
              $goc_da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>array('$in'=>[3,4,5]),'date_pay'=>array('$lte' => $time_d ) ),'$so_tien_goc_da_tra');
              $lai_da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>array('$in'=>[3,4,5]),'date_pay'=>array('$lte' => $time_d ) ),'$so_tien_lai_da_tra');
               $tran_ttcuoi=$this->transaction_model->find_where_desc(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>4,'date_pay'=>array('$lte' => $time_d )))[0];
               $ky_da_tt=0;
               $ngay_tt_cuoi=0;
               if(!empty($tran_ttcuoi))
               {
                 $ky_da_tt=(isset($tran_ttcuoi['ky_da_tt_gan_nhat'])) ? $tran_ttcuoi['ky_da_tt_gan_nhat'] : 0;

               }
              $phi_da_thanh_toan_pt=$this->transaction_model->sum_where(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>array('$in'=>[3,4,5]),'date_pay'=>array('$lte' => $time_d ) ),'$so_tien_phi_da_tra');
              $da_thanh_toan_pt=$goc_da_thanh_toan_pt+$lai_da_thanh_toan_pt+$phi_da_thanh_toan_pt;
                 $goc_phai_tra = 0;
                  $tong_phai_tra = 0;
                    $lai_phai_tra = 0;
                    $phi_phai_tra = 0;
                    $cond = array(
                        'code_contract' => $value['code_contract'],

                    );
                  $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                  $ngay_ky_tra_da_tt=0;
                    foreach ($detail as $de) {
                    	$goc_phai_tra +=  (isset($de['tien_goc_1ky_phai_tra'])) ? $de['tien_goc_1ky_phai_tra'] : 0;
                        $lai_phai_tra +=  (isset($de['tien_lai_1ky_phai_tra'])) ? $de['tien_lai_1ky_phai_tra'] : 0;
                        $phi_phai_tra +=  (isset($de['tien_phi_1ky_phai_tra'])) ? $de['tien_phi_1ky_phai_tra'] : 0;
                         $tong_phai_tra +=  (isset($de['tien_tra_1_ky'])) ? $de['tien_tra_1_ky'] : 0;
                         if($de['ky_tra']==$ky_da_tt+1)
                         {
                            $ngay_ky_tra_da_tt=$de['ngay_ky_tra'];
                         }

                    }
                    $so_ngay_cham_tra=0;
                    if($ngay_ky_tra_da_tt>0)
                    {
                    	 $so_ngay_cham_tra = intval(($time_d - strtotime(date('Y-m-d',$ngay_ky_tra_da_tt))) / (24*60*60));
                    }
              $condition=[
              	'code_contract'=>$value['code_contract'],
              	'status'=>1,
              	'type'=>3,
              	'endMonth'=>$time_d,
              	'ky_tt_xa_nhat'=>$value['debt']['ky_tt_xa_nhat'],
              	'status_contract_origin'=>$value['status'],
              ];
              $status=$this->contract_extend_model->getStatusContract($condition);
			  header("Content-type: text/plain");
		  		 header("Content-Disposition: attachment; filename=bc_thanh_". date('d/m/Y_Hi') .".txt");
				   $data = $value['code_contract'].','
		               .$value['code_contract_disbursement'].','
		               .$this->contract_extend_model->getMaHopDongVay($value).','
		               .$value['customer_infor']['customer_name'].','
		                .$value['store']['name'].','
		              .$value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'].','
		               .$typePay.','
		              .$value['loan_infor']['number_day_loan'].','
		              .$value['debt']['thoi_han_vay'].','
		              .$value['loan_infor']['amount_money'].','
		              .date('d/m/Y',$value['disbursement_date']).','
		              .date('d/m/Y',$ngay_gh_cc).','
		              .date('d/m/Y',$value['expire_date']).','
		              .round($goc_phai_tra).','
		              .round($goc_da_thanh_toan_pt).','
		              .round($goc_phai_tra-$goc_da_thanh_toan_pt).','
		             .round($lai_phai_tra).','
		              .round($lai_da_thanh_toan_pt).','
		              .round($lai_phai_tra-$lai_da_thanh_toan_pt).','
		               .round($phi_phai_tra).','
		              .round($phi_da_thanh_toan_pt).','
		              .round($phi_phai_tra-$phi_da_thanh_toan_pt).','
		              .round($tong_phai_tra).','
		              .round($da_thanh_toan_pt).','
		              .round($tong_phai_tra-$da_thanh_toan_pt).','
		         
		              . $so_ngay_cham_tra.','
		           
		              .$status;
					  $data = str_replace(array("\n", "\r"), '', $data);
			      echo $data;
		          echo("\r\n");
             
			
		
		
		}
	}
	public function get_so_phai_tra_theo_ky()
	{
		$time_d=strtotime('2021-09-22 23:59:59');
		  $contractData=$this->contract_model->find_where(array('debt' => array('$exists' => true),'status'=>['$gt'=>3]));
		 print('Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Phòng GD	,Hình thức vay,	Phương thức tính lãi,Kỳ hạn HĐ,Số tiền vay,Ngày giải ngân,Ngày đáo hạn,		Ngày kỳ trả ,Kỳ,Số tiền gốc phải trả	, Số tiền lãi phải trả, 	Số tiền phí phải trả,	Số tiền phí chậm trả phải trả,	Số tiền phí gia hạn phải trả,	Số tiền phí tất toán trước hạn phải trả,Số tiền phí quá hạn phải trả <br>');
		foreach ($contractData as $key => $value) {

		 $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
			$number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
			$amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
			
			
                    $cond = array(
                        'code_contract' => $value['code_contract'],

                    );

                  $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                  $ngay_ky_tra=0;
                    foreach ($detail as $key=>$de) {
                    	if( $ngay_ky_tra==0)
                    	{
                          $from=$value['disbursement_date'];
                          $to=$de['ngay_ky_tra'];
                    	}else{
                           $from=$ngay_ky_tra;
                          $to=$de['ngay_ky_tra'];
                    	}
                    	$phi_gia_han=0;
                    	$phi_tat_toan_truoc_han=0;
                    	$phi_qua_han=0;
                    	if($key==count($detail)-1)
                    	{
                          $pt_tt=$this->transaction_model->find_where(array('code_contract'=>$value['code_contract'],'status'=>1,'date_pay'=>['$gte'=>$from]));
                    	}else{
                    	 $pt_tt=$this->transaction_model->find_where(array('code_contract'=>$value['code_contract'],'status'=>1,'date_pay'=>['$gte'=>$from,'$lt'=> $to]));
                    	}
                    	 foreach ($pt_tt as $key => $pt) {
                    	 	if($pt['type_payment']==2)
                    	 	$phi_gia_han=$value['fee']['extend'];
                            if(isset($pt['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']) )
                            {
                            	$phi_tat_toan_truoc_han=$pt['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong'];
                            	
                            }
                              if(isset($pt['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong']) )
                            {
                            	
                            	$phi_qua_han=$pt['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong'];
                            }
                    	 }
                    	$goc_phai_tra =  (isset($de['tien_goc_1ky_phai_tra'])) ? $de['tien_goc_1ky_phai_tra'] : 0;
                        $lai_phai_tra =  (isset($de['tien_lai_1ky_phai_tra'])) ? $de['tien_lai_1ky_phai_tra'] : 0;
                        $phi_phai_tra =  (isset($de['tien_phi_1ky_phai_tra'])) ? $de['tien_phi_1ky_phai_tra'] : 0;
                         $phi_cham_tra_phai_tra =  (isset($de['fee_delay_pay'])) ? $de['fee_delay_pay'] : 0;
                            $ngay_ky_tra=$de['ngay_ky_tra'];
                     print($value['code_contract'].' ,
		               '.$value['code_contract_disbursement'].' ,
		               '.$this->contract_extend_model->getMaHopDongVay($value).' ,
		               '.$value['customer_infor']['customer_name'].',
		                '.$value['store']['name'].',
		              '.$value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'].',
		               '.$typePay.',
		                '.$value['loan_infor']['number_day_loan'].',
		               '.$amount_money.' ,
		               '.date('d/m/Y',$value['disbursement_date']).' ,
		               '.date('d/m/Y',$value['expire_date']).' ,
		               '.date('d/m/Y',$de['ngay_ky_tra']).' ,
		               '.$de['ky_tra'].' ,

		             
		              '.round($goc_phai_tra).',
		             '.round($lai_phai_tra).',
		              '.round($phi_phai_tra).',
		              '.round($phi_cham_tra_phai_tra).',
		              '.round($phi_gia_han).',
		              '.round($phi_tat_toan_truoc_han).',
		              '.round($phi_qua_han).'
                       <br>');

                    }
		}
	}

     	public function get_bc_ksnb_lshd()
	{
		  $contractData=$this->contract_model->find_where(array('status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,41,42]]));
		 $header = 'Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Phòng GD	,Hình thức vay,	Phương thức tính lãi,	Kỳ hạn (tháng),	Thời gian cho vay thực tế (số ngày)	,Số tiền vay,Ngày giải ngân,	Ngày đáo hạn,Tổng tiền lãi phải trả ước tính	,Tổng tiền phí trả ước tính,Số ngày chậm trả,Ngày thanh toán cuối cùng,Ngày tất toán,Trạng thái';
		$header = str_replace(array("\n", "\r"), '', $header);
		echo $header;
		echo("\r\n");
		foreach ($contractData as $key => $value) {

		 $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
			$number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
			$amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
			
              $tran_tattoan=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>3));
              $tran_ttcuoi=$this->transaction_model->find_where_desc(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>4))[0];
              $ngay_thanht_cuoi=(isset($tran_ttcuoi['date_pay'])) ? date('d/m/Y',$tran_ttcuoi['date_pay']) : '';
               $ngay_tt=(isset($tran_tattoan['date_pay'])) ? date('d/m/Y',$tran_tattoan['date_pay']) : '';

               header("Content-type: text/plain");
			  header("Content-Disposition: attachment; filename=get_bc_ksnb_lshd.txt");
              $data = $value['code_contract'].','
		               .$value['code_contract_disbursement'].','
		               .$this->contract_extend_model->getMaHopDongVay($value).','
		               .$value['customer_infor']['customer_name'].','
		                .$value['store']['name'].','
		              .$value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'].','
		               .$typePay.','
		              .$value['loan_infor']['number_day_loan'].','
		              .$value['debt']['thoi_han_vay'].','
		              .$value['loan_infor']['amount_money'].','
		              .date('d/m/Y',$value['disbursement_date']).','
		              .date('d/m/Y',$value['expire_date']).','
		              .round($value['debt']['lai_uoc_tinh']).','
		              .round($value['debt']['phi_uoc_tinh']).','
		              .$value['debt']['so_ngay_cham_tra'].','
		              .$ngay_thanht_cuoi.','
		              .$ngay_tt.','
		              .contract_status($value['status']);
             $data = str_replace(array("\n", "\r"), '', $data);
		      echo $data;
              echo("\r\n");
			
		
		
		}
	}
    public function get_bc_ksnb_bieu_phi()
  {
    
      $contractData=$this->contract_model->find_where(array('status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,41,42]]));
     print('Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Phòng GD ,Hình thức vay, Phương thức tính lãi, Kỳ hạn (tháng), Thời gian cho vay thực tế (số ngày) ,Số tiền vay, Ngày giải ngân, Ngày đáo hạn, Lãi suất NĐT ,Phí tư vấn, Phí thẩm định và lưu trữ tài sản,  Phí quản lý số tiền vay chậm trả (%), Phí quản lý số tiền vay chậm trả (tối thiểu), Phí tất toán trước hạn (trước 1/3), Phí tất toán trước hạn(trước 2/3), Phí tất toán trước hạn (còn lại),Phí tư vấn gia hạn<br>');
    foreach ($contractData as $key => $value) {

     $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
      $number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
      $amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
      
             
              print($value['code_contract'].' ,
                   '.$value['code_contract_disbursement'].' ,
                   '.$this->contract_extend_model->getMaHopDongVay($value).' ,
                   '.$value['customer_infor']['customer_name'].',
                    '.$value['store']['name'].',
                  '.$value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'].',
                   '.$typePay.',
                  '.$value['loan_infor']['number_day_loan'].',
                  '.$value['debt']['thoi_han_vay'].',
                  '.$value['loan_infor']['amount_money'].',
                  '.date('d/m/Y',$value['disbursement_date']).' ,
                  '.date('d/m/Y',$value['expire_date']).',
                  '.$value['fee']['percent_interest_customer'].',
                  '.$value['fee']['percent_advisory'].',
                  '.$value['fee']['percent_expertise'].',
                  '.$value['fee']['penalty_percent'].',
                  '.$value['fee']['penalty_amount'].',
                  '.$value['fee']['percent_prepay_phase_1'].',
                  '.$value['fee']['percent_prepay_phase_2'].',
                  '.$value['fee']['percent_prepay_phase_3'].',
                  '.$value['fee']['extend'].'
                  <br>');
             
      
    
    
    }
  }

  	//báo cáo LINH chia lãi phí
	 	public function get_bc_ksnb_lstn()
	{
		//$time_d=strtotime('2021-10-16 23:59:59'date('Y-m-d').' 23:59:59');
    	$time_d=strtotime(date('Y-m-d').' 23:59:59');

    	if ( isset($_GET['code']) ) {
	    	$query = array(
			  	'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42]],
			  	'code_contract'=> $_GET['code']
			  );
	    } else {
	    	$query = array(
			  	'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42]]
			  );
	    }

		  $contractData=$this->contract_model->find_where($query);

		 $header = 'Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Hình thức vay,Thời hạn vay,Phương thức tính lãi,Ngày giải ngân,Ngày đáo hạn,Ngày thanh toán cuối,Ngày tất toán trên hệ hống,Gốc,Lãi ước tính,Phí ước tính,Số tiền gốc cần thu TT,Số tiền lãi cần thu TT,Số tiền phí cần thu TT,Số tiền phí gia hạn cần thu TT,Số tiền phí chậm trả cần thu TT,Số tiền phí trước hạn cần thu TT,Số tiền phí quá hạn cần thu TT,Tổng cần thu TT,Số tiền gốc đã thu hồi,Số tiền lãi đã thu hồi,Số tiền phí đã thu hồi,Số tiền phí gia hạn đã thu hồi,Số tiền phí chậm trả đã thu hồi,Số tiền phí trước hạn đã thu hồi,Số tiền phí quá hạn đã thu hồi,Tiền thừa thanh toán,Tiền thừa tất toán,Số tiền lãi cần thu GT,Số tiền phí cần thu GT,Số tiền gốc cần thu GT,Số tiền phí chậm trả cần thu GT,Số tiền phí quá hạn cần thu GT,Số tiền phí trước hạn cần thu GT,Số tiền phí gia hạn cần thu GT,Số tiền phí giảm trừ,Tổng chia,Tổng thu,Trạng thái HĐ hiện tại';
		$header = str_replace(array("\n", "\r"), '', $header);
		echo $header;
		echo("\r\n");
		foreach ($contractData as $key => $value) {
		 $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
			$number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
          			$number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
			$amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
			
              $tran_tattoan=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'phai_tra_hop_dong.so_tien_goc_phai_tra_hop_dong'=>['$gt'=>0],'date_pay'=>array('$lte' => $time_d )));
              $tran_ttcuoi=$this->transaction_model->find_where_desc(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>4,'date_pay'=>array('$lte' => $time_d )))[0];
              $ngay_thanht_cuoi=(isset($tran_ttcuoi['date_pay'])) ? date('d/m/Y',$tran_ttcuoi['date_pay']) : '';
              $tran_tt=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>3,'date_pay'=>array('$lte' => $time_d )));
               $ngay_tt=(isset($tran_tt['date_pay'])) ? date('d/m/Y',$tran_tt['date_pay']) : '';

               	$tran = $this->transaction_model->find_where(array('status'=>1,'type'=>['$in'=>[3,4]],'code_contract'=>$value['code_contract'],'date_pay'=>array('$lte' => $time_d )));
				$count_tt=0;
				$tien_thua_ct=0;
				$loan_infor=!empty($value['loan_infor']) && !empty($value['loan_infor']['type_loan']['code']) && !empty($value['loan_infor']['type_property']['code']) ? $value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'] : "";
				              $so_tien_goc_da_tra=0;
		               $so_tien_lai_da_tra=0;
		              $so_tien_phi_da_tra=0;
		              $so_tien_phi_gia_han_da_tra=0;
		              $so_tien_phi_cham_tra_da_tra=0;
		              $fee_finish_contract=0;
		                $tien_phi_phat_sinh_da_tra=0;
		               $tien_thua_thanh_toan=0;
		               $tien_thua_tat_toan=0;
		               $total_deductible=0;
		               $tong_chia=0;
		               $tong_thu=0;
					   $mien_giam_so_tien_lai_da_tra = 0;
					   $mien_giam_so_tien_phi_da_tra = 0;
					   $mien_giam_so_tien_goc_da_tra = 0;
					   $mien_giam_so_tien_cham_tra_da_tra = 0;
					   $mien_giam_so_tien_phat_sinh_da_tra = 0;
					   $mien_giam_so_tien_tat_toan_da_tra = 0;
					   $mien_giam_so_tien_gia_han_da_tra = 0;
		       $condition=[
              	'code_contract'=>$value['code_contract'],
              	'status'=>1,
              	'type'=>3,
              	'endMonth'=>$time_d,
              	'ky_tt_xa_nhat'=>$value['debt']['ky_tt_xa_nhat'],
              	'status_contract_origin'=>$value['status'],
              ];
              $status=$this->contract_extend_model->getStatusContract($condition);
				foreach ($tran as $key1 => $value_tran) {
					$mien_giam_so_tien_lai_da_tra += round($value_tran['chia_mien_giam']['so_tien_lai_da_tra']); 
					$mien_giam_so_tien_phi_da_tra += round($value_tran['chia_mien_giam']['so_tien_phi_da_tra']); 
					$mien_giam_so_tien_goc_da_tra += round($value_tran['chia_mien_giam']['so_tien_goc_da_tra']); 
					$mien_giam_so_tien_cham_tra_da_tra += round($value_tran['chia_mien_giam']['so_tien_phi_cham_tra_da_tra']); 
					$mien_giam_so_tien_phat_sinh_da_tra += round($value_tran['chia_mien_giam']['so_tien_phi_phat_sinh_da_tra']); 
					$mien_giam_so_tien_tat_toan_da_tra += round($value_tran['chia_mien_giam']['so_tien_phi_tat_toan_da_tra']); 
					$mien_giam_so_tien_gia_han_da_tra += round($value_tran['chia_mien_giam']['so_tien_phi_gia_han_da_tra']); 

					$so_tien_goc_da_tra+=round($value_tran['so_tien_goc_da_tra']); 
					$so_tien_lai_da_tra+=round($value_tran['so_tien_lai_da_tra']); 
					$so_tien_phi_da_tra+=round($value_tran['so_tien_phi_da_tra']); 
					$so_tien_phi_gia_han_da_tra+=round($value_tran['so_tien_phi_gia_han_da_tra']); 
					$so_tien_phi_cham_tra_da_tra+=round($value_tran['so_tien_phi_cham_tra_da_tra']); 
					$fee_finish_contract+=round($value_tran['fee_finish_contract']); 
					$tien_phi_phat_sinh_da_tra+=round($value_tran['tien_phi_phat_sinh_da_tra']); 
					$tien_thua_thanh_toan+=round($value_tran['tien_thua_thanh_toan_con_lai']); 
					$tien_thua_tat_toan+=round($value_tran['tien_thua_tat_toan']); 
					$total_deductible+=round($value_tran['total_deductible']); // Tổng giảm trừ
					$tong_chia+=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['so_tien_phi_gia_han_da_tra']+ $value_tran['tien_thua_tat_toan'] + $value_tran['tien_thua_thanh_toan_con_lai']);
					$tong_thu+=$value_tran['total'];
                } 
                  $lai_uoc_tinh=(isset($value['debt']['lai_uoc_tinh'])) ? $value['debt']['lai_uoc_tinh'] : 0;
                  $phi_uoc_tinh=(isset($value['debt']['phi_uoc_tinh'])) ? $value['debt']['phi_uoc_tinh'] : 0;

                  header("Content-type: text/plain");
		  		 header("Content-Disposition: attachment; filename=linh_hop_dong.txt");
              $data = $value['code_contract'].','
		               .$value['code_contract_disbursement'].','
		               .$this->contract_extend_model->getMaHopDongVay($value).' ,'
		               .$value['customer_infor']['customer_name'].','
		               .$loan_infor.','
		                .$number_day_loan.','
		                 .$typePay.','
		               .date('d/m/Y',$value['disbursement_date']).','
		               .date('d/m/Y',$value['expire_date']).','
                   .$ngay_thanht_cuoi.','
		                 .$ngay_tt.','
		               .$amount_money.','
		                .(int)$lai_uoc_tinh.','
		                .(int)$phi_uoc_tinh.','
		              .round($tran_tattoan['phai_tra_hop_dong']['so_tien_goc_phai_tra_hop_dong']).','
		               .round($tran_tattoan['phai_tra_hop_dong']['so_tien_lai_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['so_tien_phi_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['phi_gia_han_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['phi_cham_tra_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong']).','
		              .round($tran_tattoan['phai_tra_hop_dong']['so_tien_goc_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['so_tien_lai_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['so_tien_phi_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['phi_gia_han_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['phi_cham_tra_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']+$tran_tattoan['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong']).','
		              .round($so_tien_goc_da_tra).','
		               .round($so_tien_lai_da_tra).','
		              .round($so_tien_phi_da_tra).','
		              .round($so_tien_phi_gia_han_da_tra).','
		              .round($so_tien_phi_cham_tra_da_tra).','
		              .round($fee_finish_contract).','
		                .round($tien_phi_phat_sinh_da_tra).','
		               .round($tien_thua_thanh_toan).','
		               .round($tien_thua_tat_toan).','
		             .round($mien_giam_so_tien_lai_da_tra).',' 
		             .round($mien_giam_so_tien_phi_da_tra).',' 
		             .round($mien_giam_so_tien_goc_da_tra).','
        		    .round( $mien_giam_so_tien_cham_tra_da_tra ).','
        		    .round($mien_giam_so_tien_phat_sinh_da_tra).','
                   .round($mien_giam_so_tien_tat_toan_da_tra ).',' 
                  .round($mien_giam_so_tien_gia_han_da_tra).','
		               .round($total_deductible).','
		                .$tong_chia.','
		                .$tong_thu.','
		              .$status;
		              $data = str_replace(array("\n", "\r"), '', $data);
			      echo $data;
		          echo("\r\n");
		}
	}
  public function get_bc_rasoat_may()
  {
    $time_d=strtotime('2021-09-29 23:59:59');
      $contractData=$this->contract_model->find_where(array('status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42]]));
    
     print('Mã phiếu ghi,Mã hợp đồng,Mã hợp đồng gốc,Tên người vay,Hình thức vay,Thời hạn vay,Phương thức tính lãi,Ngày giải ngân,Ngày đáo hạn,Ngày tất toán trên hệ hống,Trạng thái phiếu thu cuối,Số tiền tất toán,Trạng thái HĐ hiện tại <br>');
    foreach ($contractData as $key => $value) {
     $type_interest = !empty($value['loan_infor']['type_interest']) ? $value['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
      $number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
                $number_day_loan = $value['loan_infor']['number_day_loan'] ? $value['loan_infor']['number_day_loan'] : 0;
      $amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
      
              $tran_tattoan=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>3,'date_pay'=>array('$lte' => $time_d )));
              $tran_ttcuoi=$this->transaction_model->find_where_desc(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>4,'date_pay'=>array('$lte' => $time_d )))[0];
              $ngay_thanht_cuoi=(isset($tran_ttcuoi['date_pay'])) ? date('d/m/Y',$tran_ttcuoi['date_pay']) : '';
               $ngay_tt=(isset($tran_tattoan['date_pay'])) ? date('d/m/Y',$tran_tattoan['date_pay']) : '';

                $tran = $this->transaction_model->find_where_desc(array('status'=>1,'type'=>['$in'=>[3,4]],'code_contract'=>$value['code_contract']));
                 $tran_cxn = $this->transaction_model->find_where_desc(array('status'=>['$in'=>[2,4,10]],'status'=>['$in'=>[3,4]],'code_contract'=>$value['code_contract']));
                 if(empty( $tran_cxn))
                 {
                $cho_xac_nhan="không";
                 }else{
                   $cho_xac_nhan="có";
                 }

             
        $count_tt=0;
        $tien_thua_ct=0;
        $loan_infor=!empty($value['loan_infor']) && !empty($value['loan_infor']['type_loan']['code']) && !empty($value['loan_infor']['type_property']['code']) ? $value['loan_infor']['type_loan']['code'].'-'.$value['loan_infor']['type_property']['code'] : "";
                  
           $condition=[
                'code_contract'=>$value['code_contract'],
                'status'=>1,
                'type'=>3,
                'endMonth'=>$time_d,
                'ky_tt_xa_nhat'=>$value['debt']['ky_tt_xa_nhat'],
                'status_contract_origin'=>$value['status'],
              ];
              $status=$this->contract_extend_model->getStatusContract($condition);
       
                  $lai_uoc_tinh=(isset($value['debt']['lai_uoc_tinh'])) ? $value['debt']['lai_uoc_tinh'] : 0;
                  $phi_uoc_tinh=(isset($value['debt']['phi_uoc_tinh'])) ? $value['debt']['phi_uoc_tinh'] : 0;
                  $tt_pt=($tran[0]['type']==4 || !isset($tran[0]['type'])) ? 'Thanh toán' : 'Tất toán';
              print($value['code_contract'].' ,
                   '.$value['code_contract_disbursement'].' ,
                   '.$this->contract_extend_model->getMaHopDongVay($value).' ,
                   '.$value['customer_infor']['customer_name'].',
                   '.$loan_infor.',
                    '.$number_day_loan.',
                     '.$typePay.',
                   '.date('d/m/Y',$value['disbursement_date']).' ,
                   '.date('d/m/Y',$value['expire_date']).' ,
                     '.$ngay_tt.' ,
                   '.$tt_pt.',
                    "",
                   '.$cho_xac_nhan.',
                   
                  '.$status.' <br>');
          
             
      
    
    
    }
  }
  public function get_bc_rasoat_may1()
  {
    $time_d=strtotime('2021-10-01 23:59:59');
      $contractData=$this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'status'=>['$in'=>[2,4,10,11]],'code_contract'=>['$in'=>['000004978','000002396','000007827','000004113','000002767','000004255','00000543','000002162','000001237','HĐCC/ĐKXM/HN26VP/2002/06','00000926','000005070','00000547','000008900','000002648','000004296','000004662','000004809','000008775','00000587','00000819','00000169','000002098','000002222','00000583','00000675','00000239','00000767','00000323','00000384','00000984','00000489','00000504','000001132','000001197','000001268','000001204','000001657','000001752','000001334','000001793','000001853','000002308','000002344','000002460','000002935','000002944','000002787','000004781','000004786','000004794','000004798','0000068','00000121','00000184','00000211','00000670','00000761','00000297','00000388','00000370','00000379','00000955','00000418','00000431','000001011','00000501','000001063','000001186','000001588','000001595','000001610','000001648','000001687','000001240','000001299','000001402','000001424','000001832','000001850','000001856','000002078','000002157','000002276','000002292','000002492','000002539','000002592','000002640','000003328','000002840','000003838','000004153','000004658','000004661','000004667','000004669','000004782','000004804','000004811','000002204','000002163','000002399','000004534','000001301','000001421','000002438','000002509','000003270','000003643','00000333','000004889','00000123','00000160','00000602','00000620','00000743','00000279','00000822','00000855','00000364','00000400','00000947','00000948','000001054','000001079','000002016','000002403','000002780','000002980','000003212','000004892','000008606','000008607','000008608','000008648','000008649','000008650','HĐCC/ĐKXM/HN28PHI/2002/08','HĐCC/XM/HN28PHI/2002/11','0000062','0000091','00000161','00000655','00000682','00000293','00000327','00000393','00000395','00000513','00000560','000001831','000001453','000002818','000003407','000004868','000004882','000004884','000004891','00000998','000001568','000001258','000001481','000002850','000003214','000007261','000001276','000002054','00000740','00000785','00000807','00000919','00000956','000001056','00000556','000001083','000001122','000001176','000001224','000001257','000001266','000001331','000001468','00000949','00000541','00000720','00000806','000003494','000004696','000004815','HĐCC/ĐKXM/HN494TC/2002/25','00000303','00000320','000002893','000006471','000008654','000008655','000008656','000008735','000002556','00000310','00000321','000004697','000004721','00000421','000004850','00000661','00000704','00000759','00000825','000002123','00000657','00000715','000001035','000001614','000005054','00000163','000003262','000004930','000008762','00000190','00000750','000001474','000004730','00000654','000002060','000002300','000003434','000004913','000004919','HĐCC/ĐKXM/HN71LTN/2002/05','00000143','00000218','00000591','00000650','00000664','00000247','00000754','00000264','00000302','00000470','00000542','00000561','000001096','000001182','000001742','000002055','000002391','000003358','000004300','000004728','000004729','000004908','000004924','000004925','000004939','000004940','000005302','000005666','000001655','000001959','000002405','000004206','000004042','00000985','000001087','000001116','000001579','000001884','000001952','000002174','000002329','00000721','00000811','000001556','000001970','000004532']]));
    
     print('Mã phiếu ghi,Mã phiếu thu,Trạng thái <br>');
    foreach ($contractData as $key => $value) {
   
              print($value['code_contract'].' ,
                   '.$value['code'].' ,
                   '.status_transaction($value['status']).' 
                  <br>');
    }
  }

  	//báo cáo LINH chi tiết chia phiếu thu
	 	public function get_bc_cbich_pthu()
	{
		//$time_d=strtotime('2021-09-22 23:59:59');
     $time_d=strtotime(date('Y-m-d').' 23:59:59');
     	if ( isset($_GET['code']) ) {
	    	$query = array(
	    		'type'=>['$in'=>[3,4]],
	    		'status'=>1,
	    		'code_contract'=> $_GET['code']
	    	);
	    } else {
	    	$query = array(
	    		'type'=>['$in'=>[3,4]],
	    		'status'=>1
	    	);
	    }
		  $tranData=$this->transaction_model->find_where($query);
	
		 $header = 'Mã phiếu thu,Mã hợp đồng,Mã HĐ gốc,Mã phiếu ghi,Tên người vay,Hình thức vay,Thời hạn vay,Phương thức tính lãi,Ngày giải ngân,Ngày đáo hạn,Ngày thanh toán,Gốc HĐ,Số tiền gốc cần thu TT,Số tiền lãi cần thu TT,Số tiền phí cần thu TT,Số tiền phí gia hạn cần thu TT,Số tiền phí chậm trả cần thu TT,Số tiền phí trước hạn cần thu TT,Số tiền phí quá hạn cần thu TT,Tổng cần thu TT,Số tiền gốc đã thu hồi,Số tiền lãi đã thu hồi,Số tiền phí đã thu hồi,Số tiền phí gia hạn đã thu hồi,Số tiền phí chậm trả đã thu hồi,Số tiền phí trước hạn đã thu hồi,Số tiền phí quá hạn đã thu hồi,Tiền thừa thanh toán,Tiền thừa tất toán,Số tiền lãi cần thu GT,Số tiền phí cần thu GT,Số tiền gốc cần thu GT,Số tiền phí chậm trả cần thu GT,Số tiền phí quá hạn cần thu GT,Số tiền phí trước hạn cần thu GT,Số tiền phí gia hạn cần thu GT,Số tiền phí giảm trừ,Thừa kỳ trước+TT,Tiền thừa thanh toán,Tiền thừa còn lại,Tiền thừa đã chyển,Tiền thiếu ,Tiền thiếu còn lại,Tiền thiếu đã chuyển,Tiền KH đóng,Loại thanh toán';
		
		$header = str_replace(array("\n", "\r"), '', $header);
		echo $header;
		echo("\r\n");
		foreach ($tranData as $key => $value_tran) {
			  $contract=$this->contract_model->findOne(array('code_contract'=>$value_tran['code_contract'],'status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42]]));
        if(empty( $contract))
          continue;
		 $type_interest = !empty($contract['loan_infor']['type_interest']) ? $contract['loan_infor']['type_interest'] : "";
            if($type_interest == 1){
                $typePay = "Dư nợ giảm dần";
            }else{
                $typePay = "Lãi hàng tháng gốc cuối kỳ";
            }
			$number_day_loan = $contract['loan_infor']['number_day_loan'] ? $contract['loan_infor']['number_day_loan'] : 0;
          			$number_day_loan = $contract['loan_infor']['number_day_loan'] ? $contract['loan_infor']['number_day_loan'] : 0;
			$amount_money = isset($contract['loan_infor']['amount_money']) ? $contract['loan_infor']['amount_money'] : 0;
			$loan_infor=!empty($contract['loan_infor']) && !empty($contract['loan_infor']['type_loan']['code']) && !empty($contract['loan_infor']['type_property']['code']) ? $contract['loan_infor']['type_loan']['code'].'-'.$contract['loan_infor']['type_property']['code'] : "";
             $ma_hd_goc=$this->contract_extend_model->getMaHopDongVay($contract);
               $ngay_tt=(isset($value_tran['date_pay'])) ? date('d/m/Y',$value_tran['date_pay']) : '';

               	
				$count_tt=0;
				$tien_thua_ct=0;
				
				              $so_tien_goc_da_tra=0;
		               $so_tien_lai_da_tra=0;
		              $so_tien_phi_da_tra=0;
		              $so_tien_phi_gia_han_da_tra=0;
		              $so_tien_phi_cham_tra_da_tra=0;
		              $fee_finish_contract=0;
		                $tien_phi_phat_sinh_da_tra=0;
		               $tien_thua_thanh_toan=0;
		               $tien_thua_tat_toan=0;
		               $total_deductible=0;
		               $tong_chia=0;
		               $tong_thu=0;
		      
              $status='Thành công';
				
					  $so_tien_goc_da_tra=round($value_tran['so_tien_goc_da_tra']); 
		               $so_tien_lai_da_tra=round($value_tran['so_tien_lai_da_tra']); 
		              $so_tien_phi_da_tra=round($value_tran['so_tien_phi_da_tra']); 
		              $so_tien_phi_gia_han_da_tra=round($value_tran['so_tien_phi_gia_han_da_tra']); 
		              $so_tien_phi_cham_tra_da_tra=round($value_tran['so_tien_phi_cham_tra_da_tra']); 
		              $fee_finish_contract=round($value_tran['fee_finish_contract']); 
		                $tien_phi_phat_sinh_da_tra=round($value_tran['tien_phi_phat_sinh_da_tra']); 
		               $tien_thua_thanh_toan=round($value_tran['tien_thua_thanh_toan_con_lai']); 
		               $tien_thua_tat_toan=round($value_tran['tien_thua_tat_toan']); 
		               $total_deductible=round($value_tran['total_deductible']); 
               
               $tien_thua_ky_truoc=0;
               if($value_tran['type']==3)
               {
               	$tran_tt=$this->transaction_model->find_where_desc(array('code_contract'=>$value_tran['code_contract'],'status'=>1,'type'=>4,'date_pay'=>array('$lte' => $time_d )));
               	foreach ($tran_tt as $key => $value_tran_tt) {
               		$tien_thua_ky_truoc+=$value_tran_tt['tien_thua_thanh_toan_da_tra'];
               	}
               }
                $tong_thu=$value_tran['total'];
                   
              header("Content-type: text/plain");
			  header("Content-Disposition: attachment; filename=linh_phieu_thu.txt");
              $data = $value_tran['code'].','
              			.$contract['code_contract_disbursement'].','
              			.$contract['code_contract'].','
              			.$ma_hd_goc.' ,'
              			.$contract['customer_infor']['customer_name'].','
              			.$loan_infor.','
              			.$number_day_loan.','
              			.$typePay.','
              			.date('d/m/Y',$contract['disbursement_date']).','
              			.date('d/m/Y',$contract['expire_date']).','
              			.$ngay_tt.','
              			.$amount_money.','
              			.round($value_tran['phai_tra_hop_dong']['so_tien_goc_phai_tra_hop_dong']).','
              			.round($value_tran['phai_tra_hop_dong']['so_tien_lai_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['so_tien_phi_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['phi_gia_han_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['phi_cham_tra_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong']).','
		              .round($value_tran['phai_tra_hop_dong']['so_tien_goc_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['so_tien_lai_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['so_tien_phi_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_gia_han_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_cham_tra_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_tat_toan_phai_tra_hop_dong']+$value_tran['phai_tra_hop_dong']['phi_phat_sinh_phai_tra_hop_dong']).','
		              .round($so_tien_goc_da_tra).','
		               .round($so_tien_lai_da_tra).','
		              .round($so_tien_phi_da_tra).','
		              .round($so_tien_phi_gia_han_da_tra).','
		              .round($so_tien_phi_cham_tra_da_tra).','
		              .round($fee_finish_contract).','
		                .round($tien_phi_phat_sinh_da_tra).','
		               .round($tien_thua_thanh_toan).','
		               .round($tien_thua_tat_toan).','
		             .round($value_tran['chia_mien_giam']['so_tien_lai_da_tra']).',' 
		             .round($value_tran['chia_mien_giam']['so_tien_phi_da_tra']).',' 
		             .round($value_tran['chia_mien_giam']['so_tien_goc_da_tra']).','
      		    .round( $value_tran['chia_mien_giam']['so_tien_phi_cham_tra_da_tra']).','
      		    .round($value_tran['chia_mien_giam']['so_tien_phi_phat_sinh_da_tra']).','
                 .round($value_tran['chia_mien_giam']['so_tien_phi_tat_toan_da_tra'] ).',' 
                .round($value_tran['chia_mien_giam']['so_tien_phi_gia_han_da_tra']).','
		               .round($total_deductible).','
		                .round($tien_thua_ky_truoc).','
		              .round($value_tran['tien_thua_thanh_toan']).','
		              .round($value_tran['tien_thua_thanh_toan_con_lai']).','
		              .round($value_tran['tien_thua_thanh_toan_da_tra']).','
		                 .round($value_tran['so_tien_thieu']).','
		              .round($value_tran['so_tien_thieu_con_lai']).','
		              .round($value_tran['so_tien_thieu_da_chuyen']).','
		               .round($tong_thu).','
		                 .type_transaction($value_tran['type']).','
		                .type_payment($value_tran['type_payment']).',';
		      $data = str_replace(array("\n", "\r"), '', $data);
		      echo $data;
              echo("\r\n");
          
		}


	}
    public function get_bc_thanh_pthu()
  {
    //$time_d=strtotime('2021-09-22 23:59:59');
     $time_d=strtotime(date('Y-m-d').' 23:59:59');
      $tranData=$this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'status'=>1,'amount_actually_received'=>['$gt'=>0],'amount_actually_received' => array('$exists' => true)));
  
     print('Mã HĐ, Mã Phiếu ghi,  Tên khách hàng,  Phòng giao dịch, Ghi chú, Mã giao dịch ngân hàng,  Ngày tạo phiếu,  Phương thức thanh toán,  Ngân hàng, Tiến trình xử lý,  Số tiền thực nhận, Loại thanh toán, Ngày khách thanh toán,  Tổng tiền thanh toán,   Mã phiếu thu,  Ngày bank nhận <br>');
    foreach ($tranData as $key => $value_tran) {
       
                 
              print($value_tran['code_contract_disbursement'].' ,
                   '.$value_tran['code_contract'].' ,
                   '.$value_tran['customer_infor']['customer_name'].',
                    '.$value_tran['store']['name'].',
                    '.$value_tran['note'].',
                   '.date('d/m/Y',$value_tran['created_at']).' ,
                   '.$value_tran['payment_method'].',
                   '.$value_tran['code_transaction_bank'].',
                   '.$value_tran['status'].',
                   '.$value_tran['amount_actually_received'].',
                   '.$value_tran['type '].',
                  '.date('d/m/Y',$value_tran['date_pay']).' ,
                   '.$value_tran['total'].',
                   '.$value_tran['code'].',
                    '.$value_tran['amount_actually_received'].',
                    '.date('d/m/Y',$value_tran['date_bank'])

                  .' ,<br>');
          
    }
  }
	public function get_type($id_type,$type_payment)
    {
      switch ($id_type) {
        case '1':
        return "Thanh toán hóa đơn";
             break;
        case '2':
        return  "Phí phạt";
           break;
        case '3':
          return "Tất toán";
           break;
        case ($type_payment==1 && $id_type==4):
          return "Thanh toán - Kỳ";
           break;
         case ($type_payment==2 && $id_type==4):
          return "Thanh toán - Gia hạn";
           break;
        case ($type_payment==3 && $id_type==4):
          return "Thanh toán - Cơ cấu";
           break;
         case '5':
          return "Gia hạn";
           break;
         case '6':
          return "Thanh toán NĐT";
           break;
    }

    }
	public function get_hd_giai_ngan()
	{
		$ct = $this->contract_model->find_where(array('status'=>array('$gte'=>17),'code_contract_parent_gh'=> array('$exists' => false),'code_contract_parent_cc'=> array('$exists' => false)));
		 print('Họ và tên ,Số điện thoại, Địa chỉ, Email , Sản phẩm , BH KV GIC,BH KH MIC ,BH EASY ,BH GIC PLT ,BH VBI ,BH PTI <br>');
		foreach ($ct as $key => $value) {
			$amount_insurrance = 0;
			$type_amount_insurrance = '';
			$amount_mic =0;
			$amount_gic =0;
			
			if (isset($value['loan_infor']['loan_insurance']) && $value['loan_infor']['loan_insurance'] == "1" && $value['loan_infor']['insurrance_contract'] == 1) {
				$amount_gic = isset($value['loan_infor']['amount_GIC']) ? $value['loan_infor']['amount_GIC'] : 0;
				$type_amount_insurrance = "GIC";
			} else if (isset($value['loan_infor']['loan_insurance']) && $value['loan_infor']['loan_insurance'] == "2" && $value['loan_infor']['insurrance_contract'] == 1) {
				$amount_mic = isset($value['loan_infor']['amount_MIC']) ? $value['loan_infor']['amount_MIC'] : 0;
				$type_amount_insurrance = "MIC";

			}
			$amount_GIC_plt=(isset($value['loan_infor']['amount_GIC_plt'])) ? $value['loan_infor']['amount_GIC_plt'] : 0;
			$amount_VBI=(isset($value['loan_infor']['amount_VBI'])) ? $value['loan_infor']['amount_VBI'] : 0;
			$amount_PTI=(isset($value['loan_infor']['bao_hiem_pti_vta'])) ? $value['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] : 0;
			$amount_loan=(isset($value['loan_infor']['amount_loan'])) ? $value['loan_infor']['amount_loan'] : $value['loan_infor']['amount_money'];
			$loan_product=(isset($value['loan_infor']['loan_product']['text'])) ? $value['loan_infor']['loan_product']['text'] : '';
			$amount_GIC_easy=(isset($value['loan_infor']['amount_GIC_easy'])) ? $value['loan_infor']['amount_GIC_easy'] : 0;
			$diachi_chu_hd=(!empty($value['current_address'])) ? $value['current_address']['ward_name'] . ' - ' .$value['current_address']['district_name'] . ' - ' . $value['current_address']['province_name'] : '.....';
             $diachi_chu_hd=  str_replace(',', '', $diachi_chu_hd);
              $loan_product=  str_replace(',', '', $loan_product);
              print($value['customer_infor']['customer_name'].' , '.$value['customer_infor']['customer_phone_number'].' , '.$diachi_chu_hd.' , '.$value['customer_infor']['customer_email'].' , '.$loan_product.' , '.$amount_gic.', '.$amount_mic.', '.$amount_GIC_easy.', '.$amount_GIC_plt.', '.$amount_VBI.' , '.$amount_PTI.' <br>');
             
			
		
		
		}
	}



	public function get_hd_loi_tien_gn()
	{
		$ct = $this->contract_model->find_where(array('status'=>array('$gte'=>17)));
		foreach ($ct as $key => $value) {
			// print($value['code_contract_disbursement'].' , '.$value['code_contract'].' , '.date('d-m-Y',$value['expire_date']).' , '.date('d-m-Y',$value['disbursement_date']).' , '.$value['loan_infor']['amount_money'].' <br>');
			$condition=[
				"type"=>"contract",
				"action"=>"accountant_investors_disbursement",
				"contract_id"=>(string)$value['_id'],
			];
			$log = $this->log_model->findOne_ghcc($condition);
			if(isset($log[0]['new']['response_get_transaction_withdrawal_status_nl']['total_amount']) && isset($value['loan_infor']['amount_loan']) && $log[0]['new']['response_get_transaction_withdrawal_status_nl']['total_amount'] >0 && $log[0]['new']['response_get_transaction_withdrawal_status_nl']['total_amount'] != $value['loan_infor']['amount_loan'])
			{
              print($value['code_contract_disbursement'].' , '.$value['code_contract'].' , '.$value['loan_infor']['amount_loan'].', '.$log[0]['new']['response_get_transaction_withdrawal_status_nl']['total_amount'].', '.$log[0]['new']['response_get_transaction_withdrawal_status_nl']['transaction_id'].' , '.date('d-m-Y',$value['disbursement_date']).' <br>');
             
			}else if(isset($log[0]['old']['loan_infor']['amount_loan']) && isset($value['loan_infor']['amount_loan'])  && $log[0]['old']['loan_infor']['amount_loan'] >0 && $log[0]['old']['loan_infor']['amount_loan'] != $value['loan_infor']['amount_loan']){
                 print($value['code_contract_disbursement'].' , '.$value['code_contract'].' , '.$value['loan_infor']['amount_loan'].' , '.$log[0]['old']['loan_infor']['amount_loan'].', - , '.date('d-m-Y',$value['disbursement_date']).'  <br>');
			}
		
		
		}
	}
	
		  public function get_hd_gia_han()
  {
        $tranData = $this->transaction_model->find_where(array('status'=>1,'type'=>4,'type_payment'=>2));
        print('Mã phiếu thu,Mã phiếu ghi,Loại(2:GH, 3:CC),Đã đóng,Lãi PĐ,Phí PĐ,Quá hạn PĐ, Chậm trả PĐ,Gia hạn PĐ,Tổng PĐ,Ngày <br>');
        foreach ($tranData as $key => $value_tran) {
          $contractDB = $this->contract_model->findOne(array('code_contract' =>$value_tran['code_contract']));
          $so_tien_lai_phai_tra=!empty($value_tran['so_tien_lai_phai_tra']) ? $value_tran['so_tien_lai_phai_tra'] : 0;
    $so_tien_phi_phai_tra=!empty($value_tran['so_tien_phi_phai_tra']) ? $value_tran['so_tien_phi_phai_tra'] : 0;
    $so_tien_phi_phat_sinh_phai_tra=!empty($value_tran['phi_phat_sinh']) ? $value_tran['phi_phat_sinh'] : 0;
    $phi_gia_han=isset($contractDB['fee']['extend']) ? (int)$contractDB['fee']['extend'] :  200000;
    $so_tien_phi_cham_tra_phai_tra=!empty($value_tran['so_tien_phi_cham_tra_phai_tra']) ? $value_tran['so_tien_phi_cham_tra_phai_tra'] : 0;
      $so_tien_phai_tra=$so_tien_lai_phai_tra+$so_tien_phi_phai_tra+$so_tien_phi_phat_sinh_phai_tra+$so_tien_phi_cham_tra_phai_tra+$phi_gia_han;
          print($value_tran['code'].', '.$value_tran['code_contract'].','.$value_tran['type_payment'].' ,'.$value_tran['total'].','.$so_tien_lai_phai_tra.','.$so_tien_phi_phai_tra.','.$so_tien_phi_phat_sinh_phai_tra.','.$so_tien_phi_cham_tra_phai_tra.','.$phi_gia_han.','.$so_tien_phai_tra.','.date("d-m-Y",$value_tran['date_pay']).' <br>');
        }
  }
			
	public function get_hd_loi()
	{
	
		
			$ct = $this->contract_model->find_where(array('status'=>array('$gte'=>17)));
			print('Tên lỗi,Mã phiếu thu,Mã phiếu ghi,Loại ,Lệch,Ngày <br>');
			foreach ($ct as $key => $value) {
				$tran = $this->transaction_model->find_where(array('status'=>1,'type'=>['$in'=>[3,4]],'code_contract'=>$value['code_contract']));
				$count_tt=0;
				$tien_thua_ct=0;
				  $date_pay_last=0;
          $total_last=0;
				foreach ($tran as $key1 => $value_tran) {
					if($value_tran['type']==3)
						$count_tt++;
					$fee_reduction = (!empty($value_tran['total_deductible'])) ? (int)$value_tran['total_deductible'] : 0;
					$tong_thu=$value_tran['total'];
					if($value_tran['type']==3){
					$tong_phai_tra=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);	
					$tong_chia=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);
				    }else if($value_tran['type']==4){
				    	if($count_tt>0)
				    	{
				    		print('HĐ có phiếu thu tất toán < Thanh toán,'.$value_tran['code'].', '.$value['code_contract'].','.$value_tran['type'].' ,'.$lech_tt.','.date("d-m-Y",$value_tran['date_pay']).' <br>');
				    	}
                      $tong_chia=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_thanh_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']+ $value_tran['tien_thua_tat_toan']);
				    }
					$tong_phai_tra_tat_toan=round($value_tran['so_tien_goc_phai_tra_tat_toan'] + $value_tran['so_tien_lai_phai_tra_tat_toan'] + $value_tran['so_tien_phi_phai_tra_tat_toan']  + $value_tran['so_tien_phi_cham_tra_phai_tra_tat_toan'] + $value_tran['so_tien_phi_gia_han_phai_tra_tat_toan'] + $value_tran['so_tien_phi_phat_sinh_phai_tra_tat_toan'] + $value_tran['so_tien_phi_tat_toan_phai_tra_tat_toan']);
					$tong_hop_le_tat_toan=round($value_tran['valid_amount']);
                      $tien_thua_ct=$value_tran['tien_thua_tat_toan'];
                      $lech=$tong_thu-$tong_chia;
                      $lech_tt=$tong_phai_tra_tat_toan-$tong_da_tra_tat_toan;
                   
					if ($tong_thu == $tong_chia) {
						
						
					} else {
						//print('Chia sai, '.$value_tran['code'].', '.$value['code_contract'].' , '.$value_tran['type'].' , '.$lech.','.date("d-m-Y",$value_tran['date_pay']).' <br>');
						//print($value['code_contract'].' <br>');
					}
					if(strtotime(date('Y-m-d',$value['disbursement_date']). ' 00:00:00')>strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00'))
					{
                    print('Ngày thanh toán < ngày giải ngân,  '.$value_tran['code'].' ,'.$value['code_contract'].'  , '.$value_tran['type'].' , '.$lech.','.date("d-m-Y",$value_tran['date_pay']).' <br>');
						}
                $ct_origin = $this->contract_model->findOne(array('code_contract'=>$value['code_contract_parent_gh']));
					if(!empty($ct_origin) && !empty($value['code_contract_parent_gh']))
					{
						if($value['status']==33 && $value_tran['so_tien_goc_da_tra']>0)
						{
                      print('Gia hạn lỗi, '.$value_tran['code'].' ,  '.$value['code_contract'].', - , - ,'.date("d-m-Y",$value_tran['date_pay']).' <br>');
                       }
					}

					if($value_tran['type']==4 && $date_pay_last>0 && date('Ymd',$date_pay_last) == date('Ymd',$value_tran['date_pay']) && $total_last==$value_tran['total'] && $value_tran['created_by']=="system")
					{
					     print('Trùng phiếu thu duyệt tự động, '.$value_tran['code'].' ,  '.$value['code_contract'].', - , - ,'.date("d-m-Y",$value_tran['date_pay']).' <br>');
					}
          $date_pay_last=$value_tran['date_pay'];
           $total_last=$value_tran['total'];
          
					if($value_tran['type']==3 && $value['status']!=19)
					{
						 print('HĐ chưa chuyển TT tất toán, - , '.$value['code_contract'].' , '.$value['status'].', '.$value['type'].','.date("d-m-Y",$value['created_at']).' <br>');
					}
					if(strpos(strtolower($value_tran['note']),'gia h') && is_string($value_tran['note']) && ($value_tran['type']==3 || $value_tran['type']==4) && $value_tran['status']==1 && !isset($value['type_gh']) &&  $value['status']!=33 &&  $value['status']!=19 )
					{
							$store = $this->store_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($value_tran['store']['id'])));
                         $area = $this->area_model->findOne(array("code" => $store['code_area']));
						 print('HĐ nghi ngờ gia hạn cơ cấu, '.$area['title'] .', '.$value['code_contract'].' , '.$value['status'].', '.$value_tran['store']['name'].','.$value_tran['note'].' <br>');

					}

				}
			
				
				  if(($value['code_contract_parent_gh']!="" || $value['code_contract_parent_cc']!="") &&  $value['loan_infor']['amount_money']==0)
           {
             print('Hợp đồng GH/CC tiền vay =0, - , '.$value['code_contract'].' , '.$value['status'].', '.$value['type'].','.date("d-m-Y",$value['created_at']).' <br>');
           }
			     if($count_tt==0 && $tien_thua_ct>0)
			     {
			     	 print('Không có PT tất toán, - , '.$value['code_contract'].' , '.$value['status'].', '.$value['type'].','.date("d-m-Y",$value['created_at']).' <br>');
			     }

				if($count_tt>1)
				{
					 print('Hợp đồng 2 PT tất toán, - , '.$value['code_contract'].' , '.$value['status'].', -  ,'.date("d-m-Y",$value['created_at']).'<br>');
				}
			  
			}
			return;
		}




	
	
 

}
?>

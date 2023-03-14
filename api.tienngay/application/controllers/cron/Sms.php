<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/NL_Withdraw.php';

class Sms extends CI_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('contract_model');
        $this->load->model('tempo_contract_accounting_model');
        $this->load->model('sms_model');
         $this->load->model('transaction_model');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->load->model('contract_tempo_model');
        $this->load->model('payment_model');
         $this->load->model('report_sms_month_model');
         $this->load->helper('lead_helper');
         date_default_timezone_set('Asia/Ho_Chi_Minh');
    }
 public function test_sms(){
  $data_p = $this->input->post();
    $c = $this->contract_model->findOne(array("status"=>17,'debt.is_qua_han'=>0,'debt.so_ngay_cham_tra'=>['$gte'=>-5,'$lte'=>0],'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'code_contract'=>$data_p['code_contract']  ) );

    if (!empty($c)) {
     
        $trans = $this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'code_contract'=>$c['code_contract']));
        $conti=0;
                foreach ($trans as $key => $value_tran) {
                  if(in_array($value_tran['status'], [2,4,11]) )
                  {
                    $conti=1;
                    
                  }
                  $tong_thu=$value_tran['total'];
                  $tong_chia=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);

                  if(strtotime(date('Y-m-d',$c['disbursement_date']). ' 00:00:00')>strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00'))
          {
              $conti=1;
                          
          }
          if($value_tran['type']==4 && ($value_tran['type_payment']==1 || !isset($value_tran['type_payment'])) && $value_tran['tien_thua_thanh_toan'] > 0 )
          {
                      $conti=1;
                           
          }
          if(strpos(strtolower($value_tran['note']),'gia h') && is_string($value_tran['note']) && ($value_tran['type']==3 || $value_tran['type']==4) && $value_tran['status']==1 && !isset($c['type_gh']) &&  $c['status']!=33  )
          {
                        $conti=1;
                         
          }
          if($value_tran['type']==3 && $c['status']!=19)
          {
                        $conti=1;
                        
          }
          if ($tong_thu != $tong_chia) {
            
                  $conti=1;
                          
          }
          if($value_tran['tien_thua_tat_toan']>0)
          {
                          $conti=1;
                          
          }


                }
                if($conti==1)
                {
                   return;
                }
          $cond = array();
          $code_contract_disbursement= (!empty($c['code_contract_disbursement'])) ? $c['code_contract_disbursement'] : "";
        
          $total_money_paid=0;
        $condition = array(
          'code_contract' => $c['code_contract']
        );
        $detail = $this->contract_tempo_model->getAll($condition);
          $ngay_ky_tra =0;
        $current_day = strtotime(date('Y-m-d'));
        $date_pay = strtotime(date('Y-m-d').' 23:59:59');
        if (!empty($detail)) {
          $total_paid = 0;
          $total_phi_phat_cham_tra = 0;
          $total_da_thanh_toan=0;
          
          foreach ($detail as $de) {
            if ($de['status'] == 1 && ($date_pay > ($de['ngay_ky_tra'] - (5 * 24 * 3600))) && $de['ky_tra']>=1) { 
              $ngay_ky_tra = !empty($de['ngay_ky_tra'] ) ?  date('d/m/Y',$de['ngay_ky_tra'] ) : '';
                  $ky_tra=(!empty($de['ky_tra'])) ? $de['ky_tra'] : '';
              }
                  }
                        
              }
          if($de['ngay_ky_tra']==0) return;
      

        $arr_data=[
          'date_pay'=>$date_pay,
          'id_contract'=>(string)$c['_id'],
          'code_contract'=>$c['code_contract']
        ]; 
        $contractDB=$this->payment_model->get_payment($arr_data)['contract'];
        $total_money_paid=(isset($contractDB['total_money_paid'])) ? $contractDB['total_money_paid'] : 0;
        $time = $c['debt']['so_ngay_cham_tra'];
        if($total_money_paid>=0 && ($time==-5 || $time==0))
        {
           $type = "";
           $content ="";
                       if ($time == -5) {
                         
                           if ((int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {

                               $type = "M1";
                               $template = "60b72056a51b0a227bf4526b";
                                $content = "TienvaNgay: HD cua QK se den han thanh toan vao ngay ". $ngay_ky_tra . ". So tien can thanh toan: " . number_format($total_money_paid) . " VND. QK vui long TT dung han tranh phi phat. LH: 19006907";
                        
                           }
                       }
                       if ($time == 0 ) {
                        $template = "60b7202ea51b0a227bf4521c";
                         
                           if ( (int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {
                               $type = "M2";
                                $content = "TienvaNgay: QK da den han thanh toan so tien: " . number_format($total_money_paid) ." VND. QK vui long TT truoc ". $ngay_ky_tra . " tranh phi phat. Huong dan TT https://rb.gy/lgbdb3 hoac LH 19006907";
                           }
                       }
                  
          if($type!="")
          {
            print('Số ngày chậm trả: '.$time.' <br>'); 
            print($content);      
          
          
        } 
      
    }   
     }  
   }
  public function insert_sms_t(){
 
  	$contract = $this->contract_model->find_where(array("status"=>17,'debt.is_qua_han'=>0,'debt.so_ngay_cham_tra'=>['$gte'=>-5,'$lte'=>0],'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)) );

  	if (!empty($contract)) {
			foreach ($contract as $key=>$c) {
				$trans = $this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'code_contract'=>$c['code_contract']));
				$conti=0;
        if(!empty($trans))
        {
                foreach ($trans as $key => $value_tran) {
                	if(in_array($value_tran['status'], [2,4,11]) )
                  {
                    $conti=1;
                    break;
                  }
                  $tong_thu=$value_tran['total'];
                  $tong_chia=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);

                          if(strtotime(date('Y-m-d',$c['disbursement_date']). ' 00:00:00')>strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00'))
        					{
        						  $conti=1;
                                   break;
        					}
        					if($value_tran['type']==4 && ($value_tran['type_payment']==1 || !isset($value_tran['type_payment'])) && $value_tran['tien_thua_thanh_toan'] > 0 )
        					{
                              $conti=1;
                                   break;
        					}
        					if(strpos(strtolower($value_tran['note']),'gia h') && is_string($value_tran['note']) && ($value_tran['type']==3 || $value_tran['type']==4) && $value_tran['status']==1 && !isset($c['type_gh']) &&  $c['status']!=33  )
        					{
                                $conti=1;
                                   break;
        					}
        					if($value_tran['type']==3 && $c['status']!=19)
        					{
                                $conti=1;
                                   break;
        					}
        					if ($tong_thu != $tong_chia) {
        						
        					        $conti=1;
                                   break;
        					}
        					if($value_tran['tien_thua_tat_toan']>0)
        					{
                                  $conti=1;
                                   break;
        					}

                 }
                }
                if($conti==1)
                {
                	continue;
                }
				$cond = array();
			    $code_contract_disbursement= (!empty($c['code_contract_disbursement'])) ? $c['code_contract_disbursement'] : "";
				
			  	$total_money_paid=0;
				$condition = array(
					'code_contract' => $c['code_contract']
				);
				$detail = $this->contract_tempo_model->getAll($condition);
			    $ngay_ky_tra =0;
				$current_day = strtotime(date('Y-m-d'));
				$date_pay = strtotime(date('Y-m-d').' 23:59:59');
				if (!empty($detail)) {
					$total_paid = 0;
					$total_phi_phat_cham_tra = 0;
					$total_da_thanh_toan=0;
					
					foreach ($detail as $de) {
						if ($de['status'] == 1 && ($date_pay > ($de['ngay_ky_tra'] - (5 * 24 * 3600))) && $de['ky_tra']>=1) { 
							$ngay_ky_tra = !empty($de['ngay_ky_tra'] ) ?  date('d/m/Y',$de['ngay_ky_tra'] ) : 0;
					        $ky_tra=(!empty($de['ky_tra'])) ? $de['ky_tra'] : '';
					    }
				          }
                        
				      }
				  if($ngay_ky_tra==0) continue;
			

				$arr_data=[
					'date_pay'=>$date_pay,
					'id_contract'=>(string)$c['_id'],
					'code_contract'=>$c['code_contract']
				]; 
				$contractDB=$this->payment_model->get_payment($arr_data)['contract'];
				$total_money_paid=(isset($contractDB['total_money_paid'])) ? $contractDB['total_money_paid'] : 0;
				$time = $c['debt']['so_ngay_cham_tra'];
				if($total_money_paid>=0 && ($time==-5 || $time==0))
				{

			     $type = "";
			     $content ="";
                       // if ($time == -5) {
                         
                       //     if ((int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {

                       //         $type = "M1";
                       //         $template = "60b72056a51b0a227bf4526b";
                       //          $content = "TienvaNgay: HD cua QK se den han thanh toan vao ngay ". $ngay_ky_tra . ". So tien can thanh toan: " . number_format($total_money_paid) . " VND. QK vui long TT dung han tranh phi phat. LH: 19006907";
                        
                       //     }
                       // }
                       if ($time == 0 ) {
                       	$template = "60b7202ea51b0a227bf4521c";
                         
                           if ( (int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {
                               $type = "M2";
                                $content = "TienvaNgay: QK da den han thanh toan so tien: " . number_format($total_money_paid) ." VND. QK vui long TT truoc ". $ngay_ky_tra . " tranh phi phat. Huong dan TT https://rb.gy/lgbdb3 hoac LH 19006907";
                           }
                       }
          var_dump($c['code_contract'].' - '.$total_money_paid.' - '.$time.'-'.$ngay_ky_tra.'-'. $type);
                  
					if($type!="")
					{
						  $con_m2=array(
                            'type'=>$type,
                           	'ngay_ky_tra'=>$ngay_ky_tra,
                           	'code_contract'=>$c['code_contract']
                           );
                           $infor = $this->sms_model->findOne($con_m2);
                        if (empty($infor))
                        {
						 $phone_send_sms=$c['customer_infor']['customer_phone_number'];
                        
						$con_sms=array(
							'id_contract'=>(string)$c['_id'],
							'code_contract'=>$c['code_contract'],
							'code_contract_disbursement'=> $code_contract_disbursement,
							'customer_name'=>$c['customer_infor']['customer_name'],
							'phone_number'=>$c['customer_infor']['customer_phone_number'],
							'content'=>$content,
							'template'=>$template,
							'response'=>"",
							'status'=>"new",
							'ngay_gui'=>date('d/m/Y',$this->createdAt),
							'ky_tra'=>$ky_tra,
							'store'=>$c['store'],
							'ngay_ky_tra'=>$ngay_ky_tra,
							'so_ngay_cham_tra'=>$time,
							'type'=>$type,
							'month'=>date('m',$this->createdAt),
							'year'=>date('Y',$this->createdAt),
							'created_at'=>$this->createdAt,
							'created_by'=>"superadmin",
						);
						  $this->sms_model->insert($con_sms);
					}
                      
					
					
				} 
			}
		}		
     }  
   }
   public function insert_sms_t_5(){
 
    $contract = $this->contract_model->find_where(array("status"=>17,'debt.is_qua_han'=>0,'debt.so_ngay_cham_tra'=>['$gte'=>-5,'$lte'=>0],'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)) );

    if (!empty($contract)) {
      foreach ($contract as $key=>$c) {
        $trans = $this->transaction_model->find_where(array('type'=>['$in'=>[3,4]],'code_contract'=>$c['code_contract']));
        $conti=0;
         if(!empty($trans))
        {
                foreach ($trans as $key => $value_tran) {
                  if(in_array($value_tran['status'], [2,4,11]) )
                  {
                    $conti=1;
                    break;
                  }
                  $tong_thu=$value_tran['total'];
                  $tong_chia=round($value_tran['so_tien_lai_da_tra'] + $value_tran['so_tien_phi_da_tra'] + $value_tran['so_tien_goc_da_tra'] + $value_tran['so_tien_phi_cham_tra_da_tra'] + $value_tran['tien_phi_phat_sinh_da_tra'] + $value_tran['fee_finish_contract'] + $value_tran['tien_thua_tat_toan'] + $value_tran['so_tien_phi_gia_han_da_tra']);

                  if(strtotime(date('Y-m-d',$c['disbursement_date']). ' 00:00:00')>strtotime(date('Y-m-d',$value_tran['date_pay']). ' 00:00:00'))
                  {
                      $conti=1;
                                   break;
                  }
                  if($value_tran['type']==4 && ($value_tran['type_payment']==1 || !isset($value_tran['type_payment'])) && $value_tran['tien_thua_thanh_toan'] > 0 )
                  {
                              $conti=1;
                                   break;
                  }
                  if(strpos(strtolower($value_tran['note']),'gia h') && is_string($value_tran['note']) && ($value_tran['type']==3 || $value_tran['type']==4) && $value_tran['status']==1 && !isset($c['type_gh']) &&  $c['status']!=33  )
                  {
                                $conti=1;
                                   break;
                  }
                  if($value_tran['type']==3 && $c['status']!=19)
                  {
                                $conti=1;
                                   break;
                  }
                  if ($tong_thu != $tong_chia) {
                    
                          $conti=1;
                                   break;
                  }
                  if($value_tran['tien_thua_tat_toan']>0)
                  {
                                  $conti=1;
                                   break;
                  }
                 }

                }
                if($conti==1)
                {
                  continue;
                }
        $cond = array();
          $code_contract_disbursement= (!empty($c['code_contract_disbursement'])) ? $c['code_contract_disbursement'] : "";
        
          $total_money_paid=0;
        $condition = array(
          'code_contract' => $c['code_contract']
        );
        $detail = $this->contract_tempo_model->getAll($condition);
          $ngay_ky_tra =0;
        $current_day = strtotime(date('Y-m-d'));
        $date_pay = strtotime(date('Y-m-d').' 23:59:59');
        if (!empty($detail)) {
          $total_paid = 0;
          $total_phi_phat_cham_tra = 0;
          $total_da_thanh_toan=0;
          
          foreach ($detail as $de) {
            if ($de['status'] == 1 && ($date_pay > ($de['ngay_ky_tra'] - (5 * 24 * 3600))) && $de['ky_tra']>=1) { 
              $ngay_ky_tra = !empty($de['ngay_ky_tra'] ) ?  date('d/m/Y',$de['ngay_ky_tra'] ) : '';
                  $ky_tra=(!empty($de['ky_tra'])) ? $de['ky_tra'] : '';
              }
                  }
                        
              }
          if($ngay_ky_tra==0) continue;
      

        $arr_data=[
          'date_pay'=>$date_pay,
          'id_contract'=>(string)$c['_id'],
          'code_contract'=>$c['code_contract']
        ]; 
        $contractDB=$this->payment_model->get_payment($arr_data)['contract'];
        $total_money_paid=(isset($contractDB['total_money_paid'])) ? $contractDB['total_money_paid'] : 0;
        $time = $c['debt']['so_ngay_cham_tra'];
        if($total_money_paid>=0 && ($time==-5 || $time==0))
        {
           $type = "";
           $content ="";
                       if ($time == -5) {
                         
                           if ((int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {

                               $type = "M1";
                               $template = "60b72056a51b0a227bf4526b";
                                $content = "TienvaNgay: HD cua QK se den han thanh toan vao ngay ". $ngay_ky_tra . ". So tien can thanh toan: " . number_format($total_money_paid) . " VND. QK vui long TT dung han tranh phi phat. LH: 19006907";
                        
                           }
                       }
                       // if ($time == 0 ) {
                       //  $template = "60b7202ea51b0a227bf4521c";
                         
                       //     if ( (int)$total_money_paid>0 &&  !empty($ngay_ky_tra)) {
                       //         $type = "M2";
                       //          $content = "TienvaNgay: QK da den han thanh toan so tien: " . number_format($total_money_paid) ." VND. QK vui long TT truoc ". $ngay_ky_tra . " tranh phi phat. Huong dan TT https://rb.gy/lgbdb3 hoac LH 19006907";
                       //     }
                       // }
                  var_dump($c['code_contract'].' - '.$total_money_paid.' - '.$time.'-'.$ngay_ky_tra.'-'. $type);
          if($type!="")
          {
              $con_m2=array(
                            'type'=>$type,
                            'ngay_ky_tra'=>$ngay_ky_tra,
                            'code_contract'=>$c['code_contract']
                           );
                           $infor = $this->sms_model->findOne($con_m2);
                        if (empty($infor))
                        {
             $phone_send_sms=$c['customer_infor']['customer_phone_number'];
                        
            $con_sms=array(
              'id_contract'=>(string)$c['_id'],
              'code_contract'=>$c['code_contract'],
              'code_contract_disbursement'=> $code_contract_disbursement,
              'customer_name'=>$c['customer_infor']['customer_name'],
              'phone_number'=>$c['customer_infor']['customer_phone_number'],
              'content'=>$content,
              'template'=>$template,
              'response'=>"",
              'status'=>"new",
              'ngay_gui'=>date('d/m/Y',$this->createdAt),
              'ky_tra'=>$ky_tra,
              'store'=>$c['store'],
              'ngay_ky_tra'=>$ngay_ky_tra,
              'so_ngay_cham_tra'=>$time,
              'type'=>$type,
              'month'=>date('m',$this->createdAt),
              'year'=>date('Y',$this->createdAt),
              'created_at'=>$this->createdAt,
              'created_by'=>"superadmin",
            );
              $this->sms_model->insert($con_sms);
          }
                      
          
          
        } 
      }
    }   
     }  
   }
     public function send_sms_t()
     {
     	
     	$sms_Data=$this->sms_model->find_where(['status'=>'new','type'=>'M2']);

     	if (!empty($sms_Data)) {
			foreach ($sms_Data as $key=>$sms) {
               
                
						$arr_api=array(
							"template" => $sms['template'],
							"number" => $sms['phone_number'],
							"content" => $sms['content']
						);
                        $res= $this->push_api_sms('POST',json_encode($arr_api),"/sms");
						
						if (!empty($res)) {
                       
                      if (isset($res->sendTime) ) {
                         $con_sms['status'] = "success";
					     $con_sms['response'] =$res;
                            $con_sms['send_time'] =time();
                             $this->sms_model->update(['_id'=>$sms['_id']],$con_sms);
                              var_dump("OK-".time().' - '. date("d/m/Y",time() ) .' - '.$sms['code_contract_disbursement'].' - '.$sms['type']);
                              
                          }else{

                             $con_sms['status'] = "fail";
					         $con_sms['response'] =$res;
                               $con_sms['send_time'] =time(); 
                             $this->sms_model->update(['_id'=>$sms['_id']],$con_sms);
                              var_dump("FALSE-".time().' - '. date("d/m/Y",time() ) .' - '.$sms['code_contract_disbursement'].' - '.$sms['type']);
                              
                           
                        }
                        
                   
					}
					
			}
		}

     }
     public function send_sms_t_5()
     {
     
     	$sms_Data=$this->sms_model->find_where(['status'=>'new','type'=>'M1']);

     	if (!empty($sms_Data)) {
			foreach ($sms_Data as $key=>$sms) {
               
                
						$arr_api=array(
							"template" => $sms['template'],
							"number" => $sms['phone_number'],
							"content" => $sms['content']
						);
                        $res= $this->push_api_sms('POST',json_encode($arr_api),"/sms");
						
						if (!empty($res)) {
                       
                      if (isset($res->sendTime) ) {
                         $con_sms['status'] = "success";
					     $con_sms['response'] =$res;
					     $con_sms['send_time'] =time();
                                
                             $this->sms_model->update(['_id'=>$sms['_id']],$con_sms);
                                 var_dump("OK-".time().' - '. date("d/m/Y",time() ) .' - '.$sms['code_contract_disbursement'].' - '.$sms['type']);
                              
                          }else{

                          $con_sms['status'] = "fail";
					      $con_sms['response'] =$res;
					       $con_sms['send_time'] =time();
                                
                             $this->sms_model->update(['_id'=>$sms['_id']],$con_sms);
                             var_dump("FALSE-".time().' - '. date("d/m/Y",time() ) .' - '.$sms['code_contract_disbursement'].' - '.$sms['type']);
                              
                           
                        }
                        
                   
					}
					 
			}
		}

     }
	private function push_api_sms($post='',$data_post="",$get="")
	{
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
	 public function run_report_month_user()
    {
        $rpsms = new Report_sms_month_model();
        $sms = new Sms_model();
       

      
       $month=date('m');
        $year=date('Y');
      
      
           $data_insert=array();
          
            $sms_data = $sms->find_where(['status'=>'success',"month"=>$month,"year"=>$year]);
            if(empty($sms_data))
            	return;
            $arr_user=[];
            $arr_user_all = [];
            $arr_contract=[];
            $arr_contract_all = [];
            foreach ($sms_data as $key => $value) {
            	 $arr_user_all += [$key => ['customer_name'=>$value['customer_name'],'phone_number'=>$value['phone_number'],'store_name'=>$value['store']['name']] ];
                $arr_contract_all += [$key => ['customer_name'=>$value['customer_name'],'phone_number'=>$value['phone_number'],'code_contract'=>$value['code_contract'],'store_name'=>$value['store']['name'] ] ];

            }
          $arr_user=array_unique($arr_user_all,SORT_REGULAR);
          $arr_contract=array_unique($arr_contract_all,SORT_REGULAR);
         
          if(empty($arr_user))
              return;
            foreach ($arr_user as $key => $value) {
                $count_send_sms= $sms->count(array('customer_name'=>$value['customer_name'],'phone_number'=>$value['phone_number'],'store.name'=>$value['store_name']));
                $count_contract= count_values($arr_contract, 'customer_name', $value['customer_name'], 'phone_number',$value['phone_number'], 'store_name', $value['store_name']);
                $data_insert=[
                'customer_name'=>$value['customer_name'],
                'phone_number'=>$value['phone_number'],
                'store_name'=>$value['store_name'],
                "total_sms_month"=>$count_send_sms,
                "total_contract_month"=>$count_contract,
                "month"=>$month,
                "year"=>$year
                
             ];
              $data_insert['created_at']=time();
              $data_insert['month']=$month;
              $data_insert['year']= $year;
              $ckRk = $rpsms->findOne(["phone_number"=> $value['phone_number'],"year"=>$year,"month"=>$month,"customer_name"=> $value['customer_name'],"store_name"=>$value['store_name']]);
              if(empty($ckRk))
            {
             $rpsms->insert($data_insert);
            }else{
              $rpsms->update(
                array("_id" => $ckRk['_id']),
                $data_insert);
            }
            }

            return 'ok';
    }
    }
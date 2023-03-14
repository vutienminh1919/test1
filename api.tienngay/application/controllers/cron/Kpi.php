<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kpi extends CI_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->model('contract_model');
		$this->load->model('lead_model');
		$this->load->model('contract_tempo_model');
		$this->load->model('log_contract_tempo_model');
		$this->load->model('log_model');
		$this->load->model('user_model');
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
		$this->load->model('report_kpi_commission_user_model');
		$this->load->model('report_kpi_commission_pgd_model');
		$this->load->model('commission_kpi_model');
		$this->load->model('transaction_model');
		$this->load->helper('lead_helper');
		$this->load->model('store_model');
		$this->load->model('area_model');
    $this->load->model('kpi_pgd_model');
    $this->load->model('kpi_gdv_model');
    $this->load->model('kpi_area_model');
    $this->load->model('role_model');
    $this->load->model('mic_tnds_model');
    $this->load->model('vbi_utv_model');
    $this->load->model('vbi_sxh_model');
    $this->load->model('vbi_model');
    $this->load->model('gic_easy_bn_model');
     $this->load->model('gic_easy_model');
     $this->load->model('gic_plt_model');
     $this->load->model('gic_plt_bn_model');
      $this->load->model('pti_vta_bn_model');
      $this->load->model('contract_tnds_model');
      $this->load->model('group_role_model');
      $this->load->model('pti_bhtn_model');


		 date_default_timezone_set('Asia/Ho_Chi_Minh');
	}
    public function run_commission_kpi()
	{
           $data_insert=array();
            $stores = $this->store_model->find_where_in('status', ['active']);

            foreach($stores as $key => $sto) {
              //chạy hoa hồng cho PGD
            $this->run_commision_kpi( '',$sto,true);

            $allusers = $this->getUserbyStores((string)$sto['_id']);
            if(!empty($allusers))
            {
            foreach ($allusers as $key1 => $email) {
              //chạy hoa hồng cho user
             $this->run_commision_kpi($email,$sto,false);

            }

            }

         }
            return 'ok';
	}

	private function thu_hoi_hoan_lai_thuong($email = '', $data_store = array(), $is_store = true)
	{
		$rk_user = new Report_kpi_commission_user_model();
		$contract_db = new Contract_model();
		$user = $this->user_model->findOne(array("email" => $email));
		//sản phẩm HDV
		$data_rk_user = $rk_user->find_where(['user' => $email, 'san_pham' => "HDV", 'type_total' => '+']);

		if (!empty($data_rk_user)) {

			foreach ($data_rk_user as $key => $value) {

				if (!empty($value['ma_san_pham'])) {
					$contract = $contract_db->findOne([
							'code_contract' => $value['ma_san_pham'],
							'status' => ['$ne' => 19]
						]
					);

					$data_report = array();
					$data_report['created_at'] = time();
					$data_report['month'] = date('m');
					$data_report['year'] = date('Y');

					$area = $this->area_model->findOne(array("code" => $data_store['code_area']));
					$data_report['store'] = ['id' => (string)$data_store['_id'], 'name' => $data_store['name']];
					$data_report['code_domain'] = (isset($area['domain']['code'])) ? $area['domain']['code'] : '';
					$data_report['code_region'] = (isset($area['region']['code'])) ? $area['region']['code'] : '';
					$data_report['code_area'] = (isset($data_store['code_area'])) ? $data_store['code_area'] : '';
					$data_report['type_total'] = '-';
					$data_report['doanh_so'] = 0;
					$data_report['san_pham'] = 'HDV';
					$data_report['id_san_pham'] = (string)$contract['_id'];
					$data_report['id_tham_chieu'] = (string)$value['_id'];
					$data_report['ma_san_pham'] = (string)$contract['code_contract'];
					$san_pham = "HDV";

					if (isset($value['commision']['tien_hoa_hong'])) {
						$data_report['commision'] = $value['commision'];
						$data_report['commision']['tien_hoa_hong'] = -(int)$value['commision']['tien_hoa_hong'] * (75 / 100);
						if (isset($contract['_id'])) {
							$phi_phat_tra_cham = $this->contract_model->get_phi_phat_cham_tra((string)$contract['_id'], time())['arr_penaty'];
							$ky1 = (isset($phi_phat_tra_cham[1]['so_ngay'])) ? $phi_phat_tra_cham[1]['so_ngay'] : 0;
							$ky2 = (isset($phi_phat_tra_cham[2]['so_ngay'])) ? $phi_phat_tra_cham[2]['so_ngay'] : 0;
							$ky3 = (isset($phi_phat_tra_cham[3]['so_ngay'])) ? $phi_phat_tra_cham[3]['so_ngay'] : 0;
							if ($ky1 >= 31 || $ky2 >= 31) {
								$commision_old = $rk_user->findOne(['ma_san_pham' => $value['ma_san_pham'], 'type_total' => '-']);
								if (empty($commision_old)) {
									$this->insert_commision($is_store, $email, $data_store, $data_report);
								}
//								else {
//									if ($ky3 <= 0) {
//										$data_report['type_total'] = '+';
//										$this->insert_commision($is_store, $email, $data_store, $data_report);
//									}
//								}

							}
						}
					}

				}
			}
		}


	}
    //chạy hoa hồng kpi
	private function run_commision_kpi($email='',$data_store=array(),$is_store=false)
    {
      //thu hồi và hoàn lại
        $this->thu_hoi_hoan_lai_thuong($email,$data_store,$is_store);
        $rk_user = new Report_kpi_commission_user_model();
        $rk_pgd = new Report_kpi_commission_pgd_model();
        $kpi = new Commission_kpi_model();
        $store_id=(string)$data_store['_id'];
        $user = $this->user_model->findOne(array("email" => $email));
        $condition = array();
        $condition_kpi = array();

		$list_cskh = $this->getGroupRole_telesale();


		$created_at = array(
			'$gte' => strtotime(date('Y-m-01').' 00:00:00'),
			'$lte' => strtotime(date('Y-m-t').' 23:59:59')
		);

            $condition['disbursement_date'] = array(
                '$gte' => strtotime(date('Y-m-01').' 00:00:00'),
                '$lte' => strtotime(date('Y-m-t').' 23:59:59')
            );

            $condition_kpi['end_date'] = array(
                '$gte' => strtotime(date('Y-m-t').' 23:59:59')
            );
            $condition_kpi['status'] ='active';
           $created_by ='system';
		$condition['status']=array('$in'=>[11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19]);

		if (!$is_store) {
            $condition['created_by']=$email;
        }else{
			 $condition['store.id']=array('$in'=>[(string)$data_store['_id']]);
        }


        $contract = new Contract_model();
        $data_report_list_check=$contract->find_where($condition);

		$data_report_list = [];

        foreach ($data_report_list_check as $value){
        	if (!in_array($value['created_by'], $list_cskh)){
				array_push($data_report_list, $value);
			}
		}

        $data_kpi=$kpi->findOne($condition_kpi);
        $data_report= array();
        $data_report['created_at']=time();
        $data_report['month']=date('m');
        $data_report['year']=date('Y');
       $area = $this->area_model->findOne(array("code" =>  $data_store['code_area']));
       $data_report['store']=array('id'=>(string)$data_store['_id'],'name'=>$data_store['name']);
      $data_report['code_domain']=(isset($area['domain']['code'])) ? $area['domain']['code'] : '';

       $data_report['code_region']=(isset($area['region']['code'])) ? $area['region']['code'] : '';
       $data_report['code_area']=(isset($data_store['code_area'])) ? $data_store['code_area'] : '';
        $data_report['type_total']='+';
        if(!empty($data_report_list))
        {
            foreach ($data_report_list as $key => $value) {
              //hợp đồng không phải gia hạn cơ cấu
              if(empty($value['code_contract_parent_gh']) && empty($value['code_contract_parent_cc']) )
              {
                $amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
                $data_report['doanh_so']= $amount_money;
                $data_report['san_pham']='HDV';
                $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['code_contract'];
                $doanh_so=$amount_money;
                $san_pham="HDV";
                $nguon=isset($value['customer_infor']['customer_resources']) ? $value['customer_infor']['customer_resources'] : 0;
                $loai_san_pham=isset($value['loan_infor']['loan_product']['code']) ? $value['loan_infor']['loan_product']['code'] : 0;
                 $loai_tai_san=isset($value['loan_infor']['type_property']['code']) ? $value['loan_infor']['type_property']['code'] : 0;
                $chi_phi_vay=isset($value['fee']) ? $value['fee']['percent_interest_customer']+ $value['fee']['percent_advisory']+ $value['fee']['percent_expertise'] : 0;
                $tu_ban=false;
                $ban_kem=true;
                $giam_tru_hd=$this->transaction_model->sum_where(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>array('$in'=>[3,4,5]) ),'$total_deductible');
                $tien_giam_tru_bhkv = isset($value['tien_giam_tru_bhkv']) ? $value['tien_giam_tru_bhkv'] : 0;
                $tong_giam_tru=(int)$giam_tru_hd+(int)$tien_giam_tru_bhkv ;
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem,$tong_giam_tru);
                  if($value['status']!=19)
                  {
                    $this->insert_commision($is_store,$email,$data_store,$data_report);

                  }else{
                    //- Không tính thưởng cho các Hợp đồng giải ngân mới và đóng trong vòng 03 ngày làm việc
                     $pt_tattoan=$this->transaction_model->findOne(array('code_contract'=>$value['code_contract'],'status'=>1,'type'=>3));
                      if(!empty($pt_tattoan))
                      {
                         $so_ngay_tt = intval(($pt_tattoan['date_pay']-$value['disbursement_date']) / (24*60*60));
                       if($so_ngay_tt>3)
                       {
                          $this->insert_commision($is_store,$email,$data_store,$data_report);
                       }

                      }
                  }
                  //------------------------bảo hiểm
                $data_report['doanh_so']= 0;
                $data_report['san_pham']='BH';
                $data_report['id_san_pham']="";
                $data_report['ma_san_pham']="";
                $doanh_so=$amount_money;
                $san_pham="BH";
                $nguon=0;
                $loai_san_pham='';
                 $loai_tai_san='';
                $chi_phi_vay='';
                 if(isset($value['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']) && $value['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']>0)
                 {
                   $loai_san_pham='PTI_VTA';
                   $pti_vta = $this->pti_vta_bn_model->findOne(array('code_contract' => $value['code_contract'],'type_pti' => ['$in' => ["HD"]],'status'=>1));
                   $data_report['id_san_pham']=(string)$pti_vta['_id'];
                   $data_report['ma_san_pham']=$pti_vta['code_pti_vta'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']) ? $value['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
                    if(!empty( $data_report['id_san_pham'])){
						$this->insert_commision($is_store,$email,$data_store,$data_report);

					}
                 }
                 if(isset($value['loan_infor']['amount_code_VBI_1']) && $value['loan_infor']['amount_code_VBI_1']>0)
                 {
                  $status_vbi1 = $value['loan_infor']['maVBI_1'];
                if (is_numeric($status_vbi1)) {
                  if ($status_vbi1 <= 6) {
                   $loai_san_pham='SXH';
                   $vbi = $this->vbi_model->findOne(array('code_contract' => $value['code_contract'],'type' => 'VBI_SXH','status_vbi'=>'active'));
                     }else{
                   $loai_san_pham='UTV';
                   $vbi = $this->vbi_model->findOne(array('code_contract' => $value['code_contract'],'type' => 'VBI_UTV','status_vbi'=>'active'));
                 }
                   $data_report['id_san_pham']=(string)$vbi['_id'];
                   $data_report['ma_san_pham']=$vbi['code'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['amount_code_VBI_1']) ? $value['loan_infor']['amount_code_VBI_1'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
                  //  if(!empty( $data_report['id_san_pham']))
                   $this->insert_commision($is_store,$email,$data_store,$data_report);
                 }
               }
                if(isset($value['loan_infor']['amount_code_VBI_2']) && $value['loan_infor']['amount_code_VBI_2']>0)
                 {
                  $status_vbi2 = $value['loan_infor']['maVBI_2'];
                if (is_numeric($status_vbi2)) {
                  if ($status_vbi2 <= 6) {
                   $loai_san_pham='SXH';
                   $vbi = $this->vbi_model->findOne(array('code_contract' => $value['code_contract'],'type' => 'VBI_SXH','status_vbi'=>'active'));
                     }else{
                   $loai_san_pham='UTV';
                   $vbi = $this->vbi_model->findOne(array('code_contract' => $value['code_contract'],'type' => 'VBI_UTV','status_vbi'=>'active'));
                 }
                  $data_report['id_san_pham']=(string)$vbi['_id'];
                   $data_report['ma_san_pham']=$vbi['code'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['amount_code_VBI_2']) ? $value['loan_infor']['amount_code_VBI_2'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   if(!empty( $data_report['id_san_pham']))
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
               //   if(!empty( $data_report['id_san_pham']))
                   $this->insert_commision($is_store,$email,$data_store,$data_report);
                 }
               }
                 if(isset($value['loan_infor']['amount_GIC_easy']) && $value['loan_infor']['amount_GIC_easy']>0)
                 {
                   $loai_san_pham='EASY';
                  $gic_easy = $this->gic_easy_model->findOne(array('contract_id' => (string)$value['_id'],'status'=>'CALL_API_SUCCESS'));
                   $data_report['id_san_pham']=(string)$gic_easy['_id'];
                   $data_report['ma_san_pham']=$gic_easy['gic_code'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['amount_GIC_easy']) ? $value['loan_infor']['amount_GIC_easy'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
                    if(!empty( $data_report['id_san_pham']))
                   $this->insert_commision($is_store,$email,$data_store,$data_report);
                 }
                   if(isset($value['loan_infor']['amount_GIC_plt']) && $value['loan_infor']['amount_GIC_plt']>0)
                 {
                   $loai_san_pham='PLT';
                    $gic_plt = $this->gic_plt_model->findOne(array('contract_id' => (string)$value['_id'],'status'=>'CALL_API_SUCCESS'));
                   $data_report['id_san_pham']=(string)$gic_plt['_id'];
                   $data_report['ma_san_pham']=$gic_plt['gic_code'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['amount_GIC_plt']) ? $value['loan_infor']['amount_GIC_plt'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
                   // if(!empty( $data_report['id_san_pham']))
                   $this->insert_commision($is_store,$email,$data_store,$data_report);
                 }
                    if(isset($value['loan_infor']['bao_hiem_tnds']) && $value['loan_infor']['bao_hiem_tnds']['price_tnds']>0)
                 {
                   $loai_san_pham='TNDS';
                    $contract_tnds = $this->contract_tnds_model->findOne(array('contract_id' => (string)$value['_id']));
                   $data_report['id_san_pham']=(string)$contract_tnds['_id'];
                   $data_report['ma_san_pham']=$contract_tnds['code_contract'];
                   $data_report['ma_contract_tham_chieu']=(string)$value['_id'];
                   $doanh_so=isset($value['loan_infor']['bao_hiem_tnds']['price_tnds']) ? $value['loan_infor']['bao_hiem_tnds']['price_tnds'] : 0;
                   $data_report['doanh_so']= $doanh_so;
                   $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
                   // if(!empty( $data_report['id_san_pham']))
                   $this->insert_commision($is_store,$email,$data_store,$data_report);
                 }

				  if (isset($value['loan_infor']['pti_bhtn']) && $value['loan_infor']['pti_bhtn']['price'] > 0) {
					  $loai_san_pham = 'PTI_BHTN';
					  $pti_bhtn = $this->pti_bhtn_model->findOne(array('contract_id' => (string)$value['_id'], 'status' => 'success', 'type' => "HD"));
					  $data_report['id_san_pham'] = (string)$pti_bhtn['_id'];
					  $data_report['ma_san_pham'] = $pti_bhtn['pti_info']['so_hd_pti'];
					  $data_report['ma_contract_tham_chieu'] = (string)$value['_id'];
					  $doanh_so = isset($value['loan_infor']['pti_bhtn']) ? $value['loan_infor']['pti_bhtn']['phi'] : 0;
					  $data_report['doanh_so'] = $doanh_so;
					  $data_report['commision'] = $this->get_commission_cvkd($data_kpi, $doanh_so, $san_pham, $loai_san_pham, $loai_tai_san, $nguon, $chi_phi_vay, $tu_ban, $ban_kem);
					  $this->insert_commision($is_store, $email, $data_store, $data_report);
				  }



              }

        }

        if (!$is_store) {
          $mic_tnds = $this->mic_tnds_model->find_where(array('created_by' => $email,'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at ));
        $vbi_utv = $this->vbi_utv_model->find_where(array('created_by' =>  $email,'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at));
        $vbi_sxh = $this->vbi_sxh_model->find_where(array('created_by' =>  $email,'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at));
        $gic_easy = $this->gic_easy_bn_model->find_where(array('created_by' =>  $email,'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at));
        $gic_plt = $this->gic_plt_bn_model->find_where(array('created_by' => $email,'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at));
        $pti_vta = $this->pti_vta_bn_model->find_where(array('created_by' => $email,'type_pti' => ['$in' => ["BN", "WEB"]],'status'=>1,'store.id'=>$store_id, 'created_at'=> $created_at));
        $pti_bhtn = $this->pti_bhtn_model->find_where(array('created_by' => $email, 'type' => "BN" ,'status'=>"success", 'store.id'=> $store_id, 'created_at'=> $created_at));
        }else{
        $mic_tnds = $this->mic_tnds_model->find_where(array('store.id' => $store_id,'status'=>1, 'created_at'=> $created_at));
        $vbi_utv = $this->vbi_utv_model->find_where(array('store.id' => $store_id,'status'=>1, 'created_at'=> $created_at));
        $vbi_sxh = $this->vbi_sxh_model->find_where(array('store.id' =>$store_id,'status'=>1, 'created_at'=> $created_at));
        $gic_easy = $this->gic_easy_bn_model->find_where(array('store.id' => $store_id,'status'=>1, 'created_at'=> $created_at));
        $gic_plt = $this->gic_plt_bn_model->find_where(array('store.id' => $store_id,'status'=>1, 'created_at'=> $created_at));
        $pti_vta = $this->pti_vta_bn_model->find_where(array('store.id' =>$store_id,'type_pti' => ['$in' => ["BN", "WEB"]],'status'=>1, 'created_at'=> $created_at));
        $pti_bhtn = $this->pti_bhtn_model->find_where(array('store.id' => $store_id, 'type' => "BN" ,'status'=>"success", 'created_at'=> $created_at));
        }

         $amount_money = isset($value['loan_infor']['amount_money']) ? $value['loan_infor']['amount_money'] : 0;
                $data_report['doanh_so']= 0;
                $data_report['san_pham']='BH';
                $data_report['id_san_pham']="";
                $data_report['ma_san_pham']="";
                $doanh_so=$amount_money;
                $san_pham="BH";
                $nguon=0;
                $loai_san_pham='';
                 $loai_tai_san='';
                $chi_phi_vay='';
                $tu_ban=true;
                $ban_kem=false;


       if(!empty($mic_tnds))
        {
          $loai_san_pham='TNDS';

            foreach ($mic_tnds as $key => $value) {
              $doanh_so=isset($value['mic_fee']) ? $value['mic_fee'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['SO_ID'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }

         if(!empty($vbi_sxh))
        {
          $loai_san_pham='SXH';

            foreach ($vbi_sxh as $key => $value) {
              $doanh_so=isset($value['fee']) ? $value['fee'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['code'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }
         if(!empty($vbi_utv))
        {
          $loai_san_pham='UTV';

            foreach ($vbi_utv as $key => $value) {
              $doanh_so=isset($value['fee']) ? $value['fee'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['code'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }
         if(!empty($gic_easy))
        {
          $loai_san_pham='EASY';

            foreach ($gic_easy as $key => $value) {
              $doanh_so=isset($value['price']) ? $value['price'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['gic_code'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }
         if(!empty($gic_plt))
        {
          $loai_san_pham='PLT';

            foreach ($gic_plt as $key => $value) {
              $doanh_so=isset($value['price']) ? $value['price'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['gic_code'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }
         if(!empty($pti_vta))
        {
          $loai_san_pham='PTI_VTA';

            foreach ($pti_vta as $key => $value) {
              $doanh_so=isset($value['price']) ? $value['price'] : 0;
              $data_report['doanh_so']= $doanh_so;
              $data_report['id_san_pham']=(string)$value['_id'];
                $data_report['ma_san_pham']=(string)$value['code_pti_vta'];
               $data_report['commision']=  $this->get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban,$ban_kem);
              $this->insert_commision($is_store,$email,$data_store,$data_report);
            }
        }

			if (!empty($pti_bhtn)) {
				$loai_san_pham = 'PTI_BHTN';
				foreach ($pti_bhtn as $key => $value) {
					$cbnv = false;
					$tong_giam_tru=0;
					$doanh_so = isset($value['pti_request']['phi']) ? $value['pti_request']['phi'] : 0;
					$data_report['doanh_so'] = $doanh_so;
					$data_report['id_san_pham'] = (string)$value['_id'];
					$data_report['ma_san_pham'] = (string)$value['pti_info']['so_hd_pti'];

					if (isset($value['pti_request']['email'])){
						$check_email = explode('@', $value['pti_request']['email']);
						if($check_email[1] == "tienngay.vn"){
							$cbnv = true;
						}
					}
					$data_report['commision'] = $this->get_commission_cvkd($data_kpi, $doanh_so, $san_pham, $loai_san_pham, $loai_tai_san, $nguon, $chi_phi_vay, $tu_ban, $ban_kem, $tong_giam_tru, $cbnv);
					$this->insert_commision($is_store, $email, $data_store, $data_report);
				}
			}

    }
  }
    //lấy hoa hồng cho chuyên viên kinh doanh
    public function get_commission_cvkd($data_kpi,$doanh_so,$san_pham,$loai_san_pham,$loai_tai_san,$nguon,$chi_phi_vay,$tu_ban=false,$ban_kem=false,$tong_giam_tru=0, $cbnv = false)
    {
		//sản phẩm : HDV , BH\
      //loại sản phẩm: đối với hợp đồng là sản phẩm với bảo hiểm là TNDS,UTV.,SXH,PTI_VTA, EASY
      //loại tài sản XM, OTO
      //nguồn nguồn của hđ
      $hoa_hong=0;
      $tien_hoa_hong=0;
      $donvi='%';
      if($san_pham=="HDV")
      {
        //Vay qua đăng ký xe máy
      if( in_array($loai_san_pham, ['2','3','4']) && $loai_tai_san=='XM' && $nguon!='9')
        {
           $hoa_hong=isset($data_kpi['dkxm_commission_1']) ? $data_kpi['dkxm_commission_1'] : 0;
           $donvi=isset($data_kpi['dkxm_commission_dv']) ? $data_kpi['dkxm_commission_dv'] : $donvi;
        }
      if( in_array($loai_san_pham, ['2','3','4']) && $loai_tai_san=='XM' && $nguon=='9')
        {
           $hoa_hong=isset($data_kpi['dkxm_commission_2']) ? $data_kpi['dkxm_commission_2'] : 0;
           $donvi=isset($data_kpi['dkxm_commission_dv']) ? $data_kpi['dkxm_commission_dv'] : $donvi;
        }
        //Vay nhanh + ứng tiền tài xế công nghệ
      if( in_array($loai_san_pham, ['1'])  && $nguon!='9')
        {
           $hoa_hong=isset($data_kpi['vnuttxcn_commission_1']) ? $data_kpi['vnuttxcn_commission_1'] : 0;
           $donvi=isset($data_kpi['vnuttxcn_commission_dv']) ? $data_kpi['vnuttxcn_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['1']) && $nguon=='9')
        {
           $hoa_hong=isset($data_kpi['vnuttxcn_commission_2']) ? $data_kpi['vnuttxcn_commission_2'] : 0;
           $donvi=isset($data_kpi['vnuttxcn_commission_dv']) ? $data_kpi['vnuttxcn_commission_dv'] : $donvi;
        }
         //Vay qua đăng ký + Cầm cố ô tô
        if( in_array($loai_san_pham, ['5','6','7']) && $loai_tai_san=='OTO' && $nguon!='9')
        {
           $hoa_hong=isset($data_kpi['vqdkccot_commission_1']) ? $data_kpi['vqdkccot_commission_1'] : 0;
           $donvi=isset($data_kpi['vqdkccot_commission_dv']) ? $data_kpi['vqdkccot_commission_dv'] : $donvi;
        }
          if( in_array($loai_san_pham, ['5','6','7']) && $loai_tai_san=='OTO' && $nguon=='9')
        {
           $hoa_hong=isset($data_kpi['vqdkccot_commission_2']) ? $data_kpi['vqdkccot_commission_2'] : 0;
           $donvi=isset($data_kpi['vqdkccot_commission_dv']) ? $data_kpi['vqdkccot_commission_dv'] : $donvi;
        }
         //Bất động sản
         if( $loai_tai_san=='NĐ' &&  $chi_phi_vay>=4)
        {
           $hoa_hong=isset($data_kpi['vqbds_commission_1']) ? $data_kpi['vqbds_commission_1'] : 0;
           $donvi=isset($data_kpi['vqbds_commission_dv']) ? $data_kpi['vqbds_commission_dv'] : $donvi;
        }
          if( $loai_tai_san=='NĐ' &&  $chi_phi_vay<4)
        {
           $hoa_hong=isset($data_kpi['vqbds_commission_2']) ? $data_kpi['vqbds_commission_2'] : 0;
           $donvi=isset($data_kpi['vqbds_commission_dv']) ? $data_kpi['vqbds_commission_dv'] : $donvi;
        }


      }else{
        //bảo hiểm
        if( in_array($loai_san_pham, ['PTI_VTA']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['ptivta_commission_1']) ? $data_kpi['ptivta_commission_1'] : 0;
           $donvi=isset($data_kpi['ptivta_commission_dv']) ? $data_kpi['ptivta_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['PTI_VTA']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['ptivta_commission_2']) ? $data_kpi['ptivta_commission_2'] : 0;
           $donvi=isset($data_kpi['ptivta_commission_dv']) ? $data_kpi['ptivta_commission_dv'] : $donvi;
        }
         if( in_array($loai_san_pham, ['SXH']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['sxh_commission_1']) ? $data_kpi['sxh_commission_1'] : 0;
           $donvi=isset($data_kpi['sxh_commission_dv']) ? $data_kpi['sxh_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['SXH']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['sxh_commission_2']) ? $data_kpi['sxh_commission_2'] : 0;
           $donvi=isset($data_kpi['sxh_commission_dv']) ? $data_kpi['sxh_commission_dv'] : $donvi;
        }
         if( in_array($loai_san_pham, ['UTV']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['utv_commission_1']) ? $data_kpi['utv_commission_1'] : 0;
           $donvi=isset($data_kpi['utv_commission_dv']) ? $data_kpi['utv_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['UTV']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['utv_commission_2']) ? $data_kpi['utv_commission_2'] : 0;
           $donvi=isset($data_kpi['utv_commission_dv']) ? $data_kpi['utv_commission_dv'] : $donvi;
        }
         if( in_array($loai_san_pham, ['EASY']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['easy_commission_1']) ? $data_kpi['easy_commission_1'] : 0;
           $donvi=isset($data_kpi['easy_commission_dv']) ? $data_kpi['easy_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['EASY']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['easy_commission_2']) ? $data_kpi['easy_commission_2'] : 0;
           $donvi=isset($data_kpi['easy_commission_dv']) ? $data_kpi['easy_commission_dv'] : $donvi;
        }
          if( in_array($loai_san_pham, ['PLT']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['bhplt_commission_1']) ? $data_kpi['bhplt_commission_1'] : 0;
           $donvi=isset($data_kpi['bhplt_commission_dv']) ? $data_kpi['bhplt_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['PLT']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['bhplt_commission_2']) ? $data_kpi['bhplt_commission_2'] : 0;
           $donvi=isset($data_kpi['bhplt_commission_dv']) ? $data_kpi['bhplt_commission_dv'] : $donvi;
        }

         if( in_array($loai_san_pham, ['TNDS']) && $tu_ban)
        {
           $hoa_hong=isset($data_kpi['bhtnds_commission_1']) ? $data_kpi['bhtnds_commission_1'] : 0;
           $donvi=isset($data_kpi['bhtnds_commission_dv']) ? $data_kpi['bhtnds_commission_dv'] : $donvi;
        }
        if( in_array($loai_san_pham, ['TNDS']) && $ban_kem)
        {
           $hoa_hong=isset($data_kpi['bhtnds_commission_2']) ? $data_kpi['bhtnds_commission_2'] : 0;
           $donvi=isset($data_kpi['bhtnds_commission_dv']) ? $data_kpi['bhtnds_commission_dv'] : $donvi;
        }
		  if( in_array($loai_san_pham, ['PTI_BHTN']) && $tu_ban)
		  {
			  $hoa_hong=isset($data_kpi['pti_bhtn_commission_2']) ? $data_kpi['pti_bhtn_commission_2'] : 0;
			  $donvi=isset($data_kpi['pti_bhtn_commission_dv']) ? $data_kpi['pti_bhtn_commission_dv'] : $donvi;
		  }
		  if( in_array($loai_san_pham, ['PTI_BHTN']) && $ban_kem)
		  {
			  $hoa_hong=isset($data_kpi['pti_bhtn_commission_1']) ? $data_kpi['pti_bhtn_commission_1'] : 0;
			  $donvi=isset($data_kpi['pti_bhtn_commission_dv']) ? $data_kpi['pti_bhtn_commission_dv'] : $donvi;
		  }

		  if( in_array($loai_san_pham, ['PTI_BHTN']) && $cbnv)
		  {
			  $hoa_hong=isset($data_kpi['pti_bhtn_commission_3']) ? $data_kpi['pti_bhtn_commission_3'] : 0;
			  $donvi=isset($data_kpi['pti_bhtn_commission_dv']) ? $data_kpi['pti_bhtn_commission_dv'] : $donvi;
		  }
      }
      if(!empty($hoa_hong) && !empty($donvi) && !empty($doanh_so))
           {
            $giam_tru=($tong_giam_tru/$doanh_so)*100;
            $tien_giam_tru=(int)$giam_tru/100*$doanh_so;
            if($donvi=='%')
            {
             $tien_hoa_hong= (($hoa_hong-$giam_tru)/100)*$doanh_so;

            }else{
              $tien_hoa_hong=$hoa_hong-$tien_giam_tru;
            }
           }
      return [
        'hoa_hong'=>(int)$hoa_hong,
        'tien_hoa_hong'=>(int)$tien_hoa_hong,
        'doanh_so'=>(int)$doanh_so,
        'san_pham'=>$san_pham,
        'loai_san_pham'=>$loai_san_pham,
        'loai_tai_san'=>$loai_tai_san,
        'nguon'=>$nguon,
        'chi_phi_vay'=>$chi_phi_vay,
        'tu_ban'=>$tu_ban,
        'ban_kem'=>$ban_kem,
        'donvi'=>$donvi
      ];

    }
    //thêm mới hoa hồng
    public function insert_commision($is_store,$email,$data_store,$data_report)
    {
      unset($data_report['doanh_so']);
      $rk_pgd = new Report_kpi_commission_pgd_model();
      $rk_user = new Report_kpi_commission_user_model();
        if (!$is_store) {
            $data_report['month']=date('m');
            $data_report['year']=date('Y');
            $data_report['user']=$email;
//             $ckRk= $rk_user->findOne(array('month'=> date('m'),'year'=> date('Y'),'store.id'=>(string)$data_store['_id'],'user'=>$email,'id_san_pham'=>$data_report['id_san_pham'] ));
             $ckRk= $rk_user->findOne(array('month'=> date('m'),'year'=> date('Y'),'user'=>$email,'id_san_pham'=>$data_report['id_san_pham'] ));
             if(empty($ckRk))
            {
              if(!empty($data_report))
             $rk_user->insert($data_report);

           }else{
             if(!empty($data_report))
                $rk_user->update(
                array("_id" => $ckRk['_id']),
                $data_report);
             }

        }else{
//
              $ckRk= $rk_pgd->findOne(array('month'=> date('m'),'year'=> date('Y'),'store.id'=>(string)$data_store['_id'],'id_san_pham'=>$data_report['id_san_pham']));
             if(empty($ckRk))
            {
              if(!empty($data_report))
             $rk_pgd->insert($data_report);

              }else{
                 if(!empty($data_report))

                $rk_pgd->update(
                array("_id" => $ckRk['_id']),
                $data_report);
             }

        }
    }
     private function getUserbyStores($storeId)
    {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleAllUsers = array();
        if (count($roles) > 0) {
            foreach ($roles as $role) {
                if (!empty($role['stores']) && count($role['stores']) == 1) {
                    $arrStores = array();
                    foreach ($role['stores'] as $item) {
                        array_push($arrStores, key($item));
                    }
                    //Check userId in list key of $users
                    if (in_array($storeId, $arrStores) == TRUE) {
                        if (!empty($role['users'])) {

                            foreach ($role['users'] as $key => $item) {
                                array_push($roleAllUsers, $item[key($item)]['email']);
                            }
                        }
                    }
                }
            }
        }
        $roleUsers = array_unique($roleAllUsers);
        return $roleUsers;
    }

	public function getGroupRole_telesale()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'telesales'));
		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {
						foreach ($item as $e){
							array_push($arr, $e);

						}
					}

				}
			}
		}
		return $arr;
	}

}

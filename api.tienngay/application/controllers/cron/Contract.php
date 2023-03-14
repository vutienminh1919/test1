<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/NL_Withdraw.php';
require_once APPPATH . 'libraries/DigitalContractMegadoc.php';
class Contract extends CI_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('contract_model');
        $this->load->model('role_model');
        $this->load->model('kpi_pgd_model');
        $this->load->model('kpi_gdv_model');
        $this->load->model('store_model');
        $this->load->model('user_model');
        $this->load->model('report_kpi_commission_pgd_model');
		$this->load->model('report_kpi_commission_user_model');
        $this->load->model('report_kpi_model');
        $this->load->model('report_kpi_user_model');
        $this->load->model('report_kpi_top_user_model');
         $this->load->model('report_kpi_top_pgd_model');
        $this->load->model('tempo_contract_accounting_model');
        $this->load->model('transaction_model');
         $this->load->model('group_role_model');
         $this->load->model("lead_model");
         $this->load->model("area_model");
         $this->load->model("debt_store_model");
         $this->load->model("debt_user_model");
         $this->load->model("coupon_model");

        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->load->model('contract_tempo_model');

       date_default_timezone_set('Asia/Ho_Chi_Minh');
    }

     public function run_kpi_date_store()
    {
        $ct = new Contract_model();
        $user = new User_model();
        $bh = new Report_kpi_commission_pgd_model();
        $store = new Store_model();
        $rk = new Report_kpi_model();
        $kpi = new Kpi_pgd_model();

		$debt_store = new Debt_store_model();


        $month=date('m');

        $startTime = date('Y-m-01');
        $endTime = date('Y-m-d') ;

		$start_thang_truoc = date("2018-01-01");
		$end_thang_nay = date('Y-m-d');


		$condition = [
			'$gte' => strtotime($start_thang_truoc . ' 00:00:00'),
			'$lte' => strtotime($end_thang_nay . ' 23:59:59')
		];
		$date = getdate();
		$month_thang_truoc = $date['mon']-1;
		$year=date('Y');
		if ($date['mon'] == 1){
			$month_thang_truoc = 12;
			$year = $date['year'] - 1;
		}

		$s_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
		$e_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

		$condition_thang_truoc = array(
			'$gte' => strtotime(trim($s_thang_truoc).' 00:00:00'),
			'$lte' => strtotime(trim($e_thang_truoc).' 23:59:59')
		);

        //   $month='01';
        // $year='2021';
       // $startTime = date('Y-01-01');
       //  $endTime = date('Y-01-31') ;
           $data_insert=array();
            $con_date = array(
                '$gte' => strtotime($startTime . ' 00:00:00'),
                '$lte' => strtotime($endTime . ' 23:59:59')
            );
            $stores = $this->store_model->find_where_in('status', ['active']);
            foreach ($stores as $key => $sto) {
            $sum_giai_ngan= $rk->sum_where_contract(array('store.id'=>(string)$sto['_id'],'status'=>array('$gte'=>17),'disbursement_date'=>$con_date,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false) ), array('$toLong' => '$loan_infor.amount_money'));
            $ckRk= $rk->findOne(array('month'=> $month,'year'=> $year,'store.id'=>(string)$sto['_id'] ));
            $kpiDt= $kpi->findOne(array('month'=> $month,'year'=> $year,'store.id'=>(string)$sto['_id'] ));
             $sum_bao_hiem= $bh->sum_where(array('month'=> $month,'year'=> $year,'store.id'=>(string)$sto['_id'] ,'san_pham'=>'BH'),'$commision.doanh_so');
              $count_khach_hang_moi= $ct->count(array('status'=>array('$gte'=>17),'customer_infor.status_customer'=> '1','store.id'=>(string)$sto['_id'],'disbursement_date'=>$con_date,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false) ));
               $area = $this->area_model->findOne(array("code" =>  $sto['code_area']));

               $total_du_no_trong_han_t10 = $ct->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>(string)$sto['_id'],'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');
				$total_du_no_oto_va_nha_dat = $ct->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay()),'store.id'=>(string)$sto['_id'],'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
				$du_no_tang_net_thang_nay = $total_du_no_trong_han_t10;

				$du_no_tang_net_thang_truoc = $debt_store->sum_where_total_mongo_read(['store.id'=>(string)$sto['_id'], 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

				$du_no_tang_net = $du_no_tang_net_thang_nay - $du_no_tang_net_thang_truoc;

				$data_insert=[
                'store'=>array('id'=>(string)$sto['_id'],'name'=>$sto['name']),
               'code_domain'=>(isset($area['domain']['code'])) ? $area['domain']['code'] : '',
                'code_region'=>(isset($area['region']['code'])) ? $area['region']['code'] : '',
                'code_area'=>(isset($sto['code_area'])) ? $sto['code_area'] : '',
                'run_date'=>date('d-m-Y H:i:s'),
                'sum_giai_ngan'=>$sum_giai_ngan,
                'sum_bao_hiem'=>$sum_bao_hiem,
                'total'=>$count_khach_hang_moi+$sum_bao_hiem+$sum_giai_ngan,
                'count_khach_hang_moi'=>$count_khach_hang_moi,
                'kpi'=>$kpiDt,
					'du_no_tang_net' => $du_no_tang_net
             ];
              $data_insert['created_at']=time();
                $data_insert['month']=$month;
                $data_insert['year']= $year;

            if(empty($ckRk))
            {

             $rk->insert($data_insert);
            }else{
              $rk->update(
                array("_id" => $ckRk['_id']),
                $data_insert);

            }


        }



            return 'ok';
    }
    public function run_kpi_date_store_user()
    {
        $ct = new Contract_model();
        $user = new User_model();
        $bh = new Report_kpi_commission_user_model();
        $store = new Store_model();
        $rku = new Report_kpi_user_model();
        $rk = new Report_kpi_model();
        $kpi = new Kpi_gdv_model();
		$debt_user = new Debt_user_model();

		$start_thang_truoc = date("2018-01-01");
		$end_thang_nay = date('Y-m-d');
		$condition = [
			'$gte' => strtotime($start_thang_truoc . ' 00:00:00'),
			'$lte' => strtotime($end_thang_nay . ' 23:59:59')
		];

		$date = getdate();
		$month_thang_truoc = $date['mon']-1;
		$year=date('Y');

		if ($date['mon'] == 1){
			$month_thang_truoc = 12;
			$year = $date['year'] - 1;
		}

		$s_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
		$e_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

		$condition_thang_truoc = array(
			'$gte' => strtotime(trim($s_thang_truoc).' 00:00:00'),
			'$lte' => strtotime(trim($e_thang_truoc).' 23:59:59')
		);

       $month=date('m');
        $startTime = date('Y-m-01');
        $endTime = date('Y-m-d') ;
        //   $month='01';
        // $year='2021';
       // $startTime = date('Y-01-01');
       //  $endTime = date('Y-01-31') ;
           $data_insert=array();
            $con_date = array(
                '$gte' => strtotime($startTime . ' 00:00:00'),
                '$lte' => strtotime($endTime . ' 23:59:59')
            );
            $stores = $this->store_model->find_where_in('status', ['active']);


            foreach ($stores as $key => $sto) {
            $allusers = $this->getUserbyStores((string)$sto['_id']);

            if(!empty($allusers))
            {
            foreach ($allusers as $key1 => $email) {
            $sum_giai_ngan= $rk->sum_where_contract(array('store.id'=>(string)$sto['_id'],'status'=>array('$gte'=>17),'disbursement_date'=>$con_date,'created_by'=>$email,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false) ), array('$toLong' => '$loan_infor.amount_money'));
            $ckRk= $rku->findOne(array('month'=> (string)$month,'year'=> (string)$year,'store.id'=>(string)$sto['_id'],'user_email'=>$email ));
            $kpiDt= $kpi->findOne(array('month'=> (string)$month,'year'=> (string)$year,'store.id'=>(string)$sto['_id'] ,'email_gdv'=>$email));
             $sum_bao_hiem= $bh->sum_where(array('month'=> (string)$month,'year'=> (string)$year,'user'=>$email ,'store.id'=>(string)$sto['_id'],'san_pham'=>'BH'),'$commision.doanh_so');
              $count_khach_hang_moi= $ct->count(array('status'=>array('$gte'=>17),'customer_infor.status_customer'=> '1','created_by'=>$email,'disbursement_date'=>$con_date,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)));
               $area = $this->area_model->findOne(array("code" =>  $sto['code_area']));


				$total_du_no_trong_han_t10 = $ct->sum_where_total_mongo_read(['created_by'=>$email,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>(string)$sto['_id'],'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');
				$total_du_no_oto_va_nha_dat = $ct->sum_where_total_mongo_read(['created_by'=>$email,'status' => array('$in' => list_array_trang_thai_dang_vay()),'store.id'=>(string)$sto['_id'],'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
				$du_no_tang_net_thang_nay = $total_du_no_trong_han_t10;

				$du_no_tang_net_thang_truoc = $debt_user->sum_where_total_mongo_read(['user'=> $email, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

				$du_no_tang_net = $du_no_tang_net_thang_nay - $du_no_tang_net_thang_truoc;


               $data_insert=[
                'store'=>array('id'=>(string)$sto['_id'],'name'=>$sto['name']),
                'code_domain'=>(isset($area['domain']['code'])) ? $area['domain']['code'] : '',
                'code_region'=>(isset($area['region']['code'])) ? $area['region']['code'] : '',
                'code_area'=>(isset($sto['code_area'])) ? $sto['code_area'] : '',
                'run_date'=>date('d-m-Y H:i:s'),
                'sum_giai_ngan'=>$sum_giai_ngan,
                'sum_bao_hiem'=>$sum_bao_hiem,
                'count_khach_hang_moi'=>$count_khach_hang_moi,
                'total'=>$count_khach_hang_moi+$sum_bao_hiem+$sum_giai_ngan,
                'kpi'=>$kpiDt,
                'user_email'=>$email,
				   'du_no_tang_net' => $du_no_tang_net

             ];
              $data_insert['created_at']=time();
              $data_insert['month']=$month;
                $data_insert['year']= $year;
              if(empty($ckRk))
            {
             $rku->insert($data_insert);
            }else{
              $rku->update(
                array("_id" => $ckRk['_id']),
                $data_insert);
            }
            }

            }

        }




            return 'ok';
    }
     public function test_run_kpi_top_user_pgd()
    {

       $startTime = strtotime(date('Y-m-01'));
         $endTime = strtotime( date('Y-m-d') );

   for ( $i = $endTime; $i >= $startTime; $i = $i - 86400 ) {
         var_dump(date('Y-m-d',$i));

        }

    }

    public function run_kpi_top_this_month()
    {


      $startTime = strtotime(date('Y-m-01'));
      $endTime = strtotime( date('Y-m-d') );

    for ( $i = $endTime; $i >= $startTime; $i = $i - 86400 ) {
          // $i=time();
           $data_insert=array();
            $stores = $this->store_model->find_where_in('status', ['active']);
            foreach($stores as $key => $sto) {
               $this->run_top_kpi( date('Y-m-d',$i),'',$sto,true);

            $allusers = $this->getUserbyStores((string)$sto['_id']);
            if(!empty($allusers))
            {
            foreach ($allusers as $key1 => $email) {
             $this->run_top_kpi( date('Y-m-d',$i),$email,$sto,false);

            }

            }

         }

        }

            return 'ok';
    }
    public function run_kpi_top_user_pgd()
    {

       // $startTime = strtotime(date('Y-m-01'));
       //   $endTime = strtotime( date('Y-m-d') );
      $startTime = strtotime(date('Y-m-01'));
        $endTime = strtotime(date('Y-m-d')) ;

    for ( $i = $endTime; $i >= $startTime; $i = $i - 86400 ) {
          // $i=time();
           $data_insert=array();
            $stores = $this->store_model->find_where_in('status', ['active']);
            foreach($stores as $key => $sto) {
               $this->run_top_kpi( date('Y-m-d',$i),'',$sto,true);

            $allusers = $this->getUserbyStores((string)$sto['_id']);
            if(!empty($allusers))
            {
            foreach ($allusers as $key1 => $email) {
             $this->run_top_kpi( date('Y-m-d',$i),$email,$sto,false);

            }

            }

         }

        }

            return 'ok';
    }
    private function run_top_kpi($i,$email='',$data_store=array(),$is_store=false)
    {

        $rk_user = new Report_kpi_top_user_model();
        $rk_pgd = new Report_kpi_top_pgd_model();
        $kpi = new Kpi_gdv_model();
        $stores=[(string)$data_store['_id']];
        $user = $this->user_model->findOne(array("email" => $email));
                  $condition = array();
        $condition_lead = array();

            $condition['disbursement_date'] = array(
                '$gte' => strtotime(trim($i).' 00:00:00'),
                '$lte' => strtotime(trim($i).' 23:59:59')
            );


            $condition_lead = array(
                '$gte' => strtotime(trim($i).' 00:00:00'),
                '$lte' => strtotime(trim($i).' 23:59:59')
            );



           $created_by ='system';
         if (!$is_store) {
            $condition['created_by']=$email;
        }else{
             $condition['store.id']=array('$in'=>[(string)$data_store['_id']]);
        }
        $condition['status']=array('$in'=>[17,19]);
        $report = new Report_kpi_model();
        $contract = new Contract_model();
        $lead = new Lead_model();
        $data_report= array();
        $data_report_list=$contract->find_where_select($condition,['loan_infor','code_contract_parent_cc','debt','code_contract_parent_gh']);
        $data_report['total_so_tien_vay']= 0;
        $data_report['total_du_no_qua_han_t4']=0;
         $data_report['total_du_no_qua_han_t10']=0;
        $data_report['total_du_no_dang_cho_vay']=0;
        if(!empty($data_report_list))
        {
            foreach ($data_report_list as $key => $value) {
              if(empty($value['code_contract_parent_gh']) && empty($value['code_contract_parent_cc']) )
              {
                $data_report['total_so_tien_vay']+= (isset($value['loan_infor']['amount_money'])) ? $value['loan_infor']['amount_money'] : 0;
                $data_report['total_du_no_qua_han_t4']+=(isset($value['debt']['so_ngay_cham_tra']) && $value['debt']['so_ngay_cham_tra']>=4) ? $value['debt']['tong_tien_goc_con'] : 0;
                 $data_report['total_du_no_qua_han_t10']+=(isset($value['debt']['so_ngay_cham_tra']) && $value['debt']['so_ngay_cham_tra']>=10) ? $value['debt']['tong_tien_goc_con'] : 0;
                $data_report['total_du_no_dang_cho_vay']+=(isset($value['debt']['tong_tien_goc_con'])) ? $value['debt']['tong_tien_goc_con'] : 0;
              }
            }
        }


        if ($is_store==false) {
        $data_report['contract_moi']=$contract->count(['status'=>array('$in'=>[1]),'store.id'=>array('$in'=> $stores),'created_at'=>$condition_lead,'created_by'=>$email]);
        $data_report['contract_dang_xl']=$contract->count(['status'=>array('$in'=>[2]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
       $data_report['contract_cho_pd']=$contract->count(['status'=>array('$in'=>[5]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
        $data_report['contract_da_duyet']=$contract->count(['status'=>array('$in'=>[6]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
        $data_report['contract_cho_gn']=$contract->count(['status'=>array('$in'=>[15]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
        $data_report['contract_da_gn']=$contract->count(['status'=>array('$in'=>[17]),'disbursement_date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
         $data_report['contract_khac']=$contract->count(['status'=>array('$nin'=>[1,2,5,6,15,17]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$email]);
        }else{
        $data_report['contract_moi']=$contract->count(['status'=>array('$in'=>[1]),'store.id'=>array('$in'=> $stores),'created_at'=>$condition_lead]);
        $data_report['contract_dang_xl']=$contract->count(['status'=>array('$in'=>[2]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores)]);
       $data_report['contract_cho_pd']=$contract->count(['status'=>array('$in'=>[5]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores)]);
       $data_report['contract_da_duyet']=$contract->count(['status'=>array('$in'=>[6]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores),'created_by'=>$created_by]);
        $data_report['contract_cho_gn']=$contract->count(['status'=>array('$in'=>[15]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores)]);
        $data_report['contract_da_gn']=$contract->count(['status'=>array('$in'=>[17]),'disbursement_date'=>$condition_lead,'store.id'=>array('$in'=> $stores)]);
         $data_report['contract_khac']=$contract->count(['status'=>array('$nin'=>[1,2,5,6,15,17]),'created_at'=>$condition_lead,'store.id'=>array('$in'=> $stores)]);
        }
         $data_report['contract_total']=$data_report['contract_moi']+ $data_report['contract_dang_xl']+ $data_report['contract_cho_pd']+ $data_report['contract_da_duyet']+ $data_report['contract_cho_gn']+ $data_report['contract_da_gn']+ $data_report['contract_khac'];
        $data_report['created_at']=time();
        $data_report['date']=strtotime($i.' 12:00:00');
        $data_report['month']=date('m', $data_report['date']);
        $data_report['year']=date('Y', $data_report['date']);
        $data_report['store']=array('id'=>(string)$data_store['_id'],'name'=>$data_store['name']);


        $total=$data_report['contract_total']+ $data_report['total_so_tien_vay']+ $data_report['total_du_no_qua_han_t4']+ $data_report['total_du_no_qua_han_t10']+ $data_report['total_du_no_dang_cho_vay'];
       // var_dump($ckRk['_id']); die;
          if (!$is_store) {
            $data_report['user']=$email;
             $ckRk= $rk_user->findOne(array('date'=> strtotime($i.' 12:00:00'),'store.id'=>(string)$data_store['_id'],'user'=>$email ));


             if(empty($ckRk))
            {
               if($total>0)
            {
             $rk_user->insert($data_report);
              }
           }else{
                $rk_user->update(
                array("_id" => $ckRk['_id']),
                $data_report);
             }

        }else{
              $ckRk= $rk_pgd->findOne(array('date'=> strtotime($i.' 12:00:00'),'store.id'=>(string)$data_store['_id']));


             if(empty($ckRk))
            {
               if($total>0)
            {
             $rk_pgd->insert($data_report);
              }
              }else{
                $rk_pgd->update(
                array("_id" => $ckRk['_id']),
                $data_report);
             }

        }
    }
    public function debt_recovery()
    {
		date_default_timezone_set('Asia/Ho_Chi_Minh');
        $contractData=$this->contract_model->find_where_select(array('status'=>['$in'=>[10,11,12,13,14,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,40,41,42]]),['_id','code_contract','disbursement_date','status','loan_infor']);
        foreach ($contractData as $key => $c) {

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
					$ky_tra_hien_tai = 0;

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
              $current_day = strtotime(date('y-m-d'));
              $datetime = $current_day;
              $phi_phat_sinh=0;
              $detail = $this->contract_tempo_model->getContractTempobyTime(['code_contract' => $c['code_contract'], 'status' => 1]);
              if (!empty($detail)) {
              $datetime = !empty($detail[0]['ngay_ky_tra']) ? intval($detail[0]['ngay_ky_tra']) : $current_day;
				  $ky_tra_hien_tai = !empty($detail[0]['ky_tra']) ? intval($detail[0]['ky_tra']) : "";
              $time = intval(($current_day - strtotime(date('Y-m-d',$datetime))) / (24*60*60));
               }

              if($c['status']==33 ||  $c['status']==34  ||  $c['status']==19  ||  $c['status']==40)
                $time = 0;

               $penalty=$this->contract_model->get_phi_phat_cham_tra((string)$c['_id'], strtotime(date('Y-m-d').' 23:59:59'));

               $total_phi_phat_cham_tra=$penalty['tong_penalty_con_lai'];
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
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4,11]) && $da_thanh_toan_gh_cc['type_payment']==2)
                  {
                    //đã thanh toán gia hạn
                    $check_tt_gh=2;
                  }
                    if($da_thanh_toan_gh_cc['status']==1 && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //đã thanh toán cơ cấu
                    $check_tt_cc=1;
                  }
                  if(in_array($da_thanh_toan_gh_cc['status'], [2,4,11]) && $da_thanh_toan_gh_cc['type_payment']==3)
                  {
                    //đã thanh toán gia hạn
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
//					$get_infor_tat_toan_part_1 = $this->contract_model->get_infor_tat_toan_part_1($c['code_contract'],strtotime(date('Y-m-d') . ' 23:59:59'));
//				    $du_no_goc_con_lai = isset($get_infor_tat_toan_part_1['goc_chua_tra_den_thoi_diem_dao_han']) ? $get_infor_tat_toan_part_1['goc_chua_tra_den_thoi_diem_dao_han'] : 0;
					$kpi_tong_tien_goc_con = 0;
					if (($c['loan_infor']['type_property']['code'] == "OTO" || $c['loan_infor']['type_property']['code'] == "NĐ") && $c['loan_infor']['type_loan']['code'] == "CC"){
						$kpi_tong_tien_goc_con = $total_goc * 0.9;
					}

                       $data=[
                        'expire_date'=>$ky_tt_xa_nhat,
                        'debt'=>[
                        'current_day'=>$current_day,
                        'ngay_ky_tra'=>$datetime,
						'ky_tra_hien_tai'=>$ky_tra_hien_tai,
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
                        'run_date'=>date('d-m-Y H:i:s'),
							'kpi_tong_tien_goc_con'=>$kpi_tong_tien_goc_con

                          ]
                       ];
                    $this->contract_model->update(
                        array("_id" => $c['_id']),
                        $data
                    );
               }

                }
                return "OK";
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

public function add_face_seach()
{

        $contract = $this->contract_model->find_where_in("status",array(17,18,19,20,21,22,23,24));
          if (!empty($contract)) {

            foreach ($contract as $key=>$c) {

             if(isset($c['image_accurecy']['vehicle']) && !empty($c['image_accurecy']['vehicle']))
             {

                foreach ($c['image_accurecy']['vehicle'] as $value) {
                   $path=(isset($value['path']))  ? $value['path'] : '';
                   if( $path!="" && strpos($path,'mp3')==0)
                   {

  $data_arr=array('image' =>  array('url' => $path, 'metadata' => array('customer_infor'=>$c['customer_infor'],'code_contract'=>$c['code_contract'],'store'=>$c['store']) ) );
                    $url = $this->config->item("API_CVS").'face_search/add';
                  var_dump( $path);
                    $result1 = $this->push_api_cvs($url,json_encode($data_arr));
                  //  var_dump($result1); die;
                    if($result1->status_code=="0")
                    {
                      var_dump($c['code_contract']." - TRUE");
                    }else{
                       var_dump($c['code_contract']."- FALSE");
                    }
                }
         }
      }
    }
   }
}
  private function push_api_cvs($url='',$data_post=[]){
       $username =$this->config->item("CVS_API_KEY");
        $password = $this->config->item("CVS_API_SECRET");
        $service = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$service);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close ($ch);
        $result1 = json_decode($result);
        return $result1;
    }
  public function update_bucket(){
        $contract = $this->contract_model->find_where_in("status",array(17,18,19,20,21,22,23,24));
          if (!empty($contract)) {
            foreach ($contract as $key=>$c) {
                $cond = array();

                if(isset($c['code_contract']))
                {
                $cond = array(
                    'code_contract' => $c['code_contract'],
                    'end' => time() - 5* 24*3600, // 5 ngay tieu chuan
                );
              }
                $detail = $this->contract_tempo_model->getContractTempobyTime($cond);
                $c['detail'] = array();
                if (!empty($detail)) {
                    $total_paid = 0;
                    $total_phi_phat_cham_tra = 0;
                    $total_da_thanh_toan=0;
                    foreach ($detail as $de) {

                        $total_paid = $total_paid + $de['tien_tra_1_ky'] ;
                        $total_phi_phat_cham_tra +=$de['penalty'] ;
                        $total_da_thanh_toan +=$de['da_thanh_toan'] ;
                    }
                    $c['detail'] = $detail[0];
                    $c['detail']['total_paid'] = $total_paid;
                    $c['detail']['total_phi_phat_cham_tra'] = $total_phi_phat_cham_tra;
                    $c['detail']['total_da_thanh_toan'] = $total_da_thanh_toan;
                } else {
                    $condition_new = array(
                        'code_contract' => $c['code_contract'],
                        'status' => 1
                    );
                    $detail_new = $this->contract_tempo_model->getContract($condition_new);
                    if (!empty($detail_new)) {
                        $c['detail'] = $detail_new[0];

                        $c['detail']['total_paid'] = $detail_new[0]['tien_tra_1_ky'] ;
                        $c['detail']['total_phi_phat_cham_tra'] =$detail_new[0]['penalty'] ;
                    $c['detail']['total_da_thanh_toan'] = $detail_new[0]['da_thanh_toan'] ;
                    }
                }

            $time=0;
            if (!empty($c['detail']) && $c['detail']['status'] == 1) {
                $current_day = strtotime(date('m/d/Y'));
                $datetime = !empty($c['detail']['ngay_ky_tra']) ? intval($c['detail']['ngay_ky_tra']) : $current_day;
                $time = intval(($current_day - $datetime) / (24 * 60 * 60));
                if ($time <= 0) {
                    $c['bucket'] = 'B0';
                } else if ($time >= 1 && $time <= 30) {
                    $c['bucket'] = 'B1';
                } else if ($time > 30 && $time <= 60) {
                    $c['bucket'] = 'B2';
                } else if ($time > 60 && $time <= 90) {
                    $c['bucket'] = 'B3';
                } else if ($time > 90 && $time <= 120) {
                    $c['bucket'] = 'B4';
                } else if ($time > 120 && $time <= 150) {
                    $c['bucket'] = 'B5';
                } else if ($time > 150 && $time <= 180) {
                    $c['bucket'] = 'B6';
                } else if ($time > 180 && $time <= 210) {
                    $c['bucket'] = 'B7';
                } else if ($time > 210 && $time <= 270) {
                    $c['bucket'] = 'B8';
                } else if ($time > 270 && $time <= 300) {
                    $c['bucket'] = 'B9';
                } else if ($time > 300 && $time <= 330) {
                    $c['bucket'] = 'B10';
                } else if ($time > 330 && $time <= 360) {
                    $c['bucket'] = 'B11';
                } else {
                    $c['bucket'] = 'B12';
                }
            } else if (!empty($c['detail']) && $c['detail']['status']  == 2) {
                $c['bucket'] =  'B0';
            } else {
                $c['bucket'] =  '-';
            }
            if(isset($c['code_contract']))
            {
                var_dump($c['code_contract'].' - '. $c['bucket'] );
            $this->contract_model->update(
                array("code_contract" => $c['code_contract']),
                array('bucket'=>$c['bucket'])
            );
            }


            }

        }

    }

    public function lay_du_no_goc_con_lai()
	{
    	$contracts = $this->contract_model->find_where(['status'=> 17]);
    	if(count($contracts) > 0){
    		foreach ($contracts as $contract){
    			$du_no_goc_con_lai = 0;
    			$tempo = $this->contract_tempo_model->find_where(['code_contract'=>$contract['code_contract'],'status'=>1]);
    			if(count($tempo) > 0){
    				foreach ($tempo as $t){
    					$du_no_goc_con_lai += (float)$t['tien_goc_1ky'];
					}
				}
    			$this->contract_model->update(['_id'=>$contract['_id']],['original_debt'=>['du_no_goc_con_lai'=>$du_no_goc_con_lai]]);
			}
		}
    	return 'ok';
	}

	/**
	 * Đồng bộ trạng thái HĐ Megadoc về db contract tienngay
	 */
	public function update_status_contract_megadoc()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		// Tìm trạng thái VB TTBB
		$statusTTBBTN = $this->contract_model->find_where_select(array('megadoc.ttbb.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.ttbb','store.id']);
		if (!empty($statusTTBBTN)) {
			$megadoc = new DigitalContractMegadoc();
			foreach ($statusTTBBTN as $key => $ttbb) {
				if (!empty($ttbb['megadoc']['ttbb']['searchkey'])) {
					$company_code = $this->check_store_tcv_dong_bac($ttbb['store']['id']);
					$response_ttbb = $megadoc->status_contract($ttbb['megadoc']['ttbb']['searchkey'], $company_code);
					$response_ttbb_decode = json_decode(json_decode($response_ttbb,true));
					$statusTTBBMG = $response_ttbb_decode[0]->Status;
					if (!empty($statusTTBBMG) && in_array($statusTTBBMG, [0,1,2,3,7])) {
						if ($ttbb['megadoc']['ttbb']['status'] != $statusTTBBMG) {
							$this->contract_model->update(
								["_id" => $ttbb["_id"] ],[
									"megadoc.ttbb.status" => $statusTTBBMG
								]
							);
						}
					}
				}
			}
		}
		// Tìm trạng thái VB BBBG before
		$statusBBBGBEFORETN = $this->contract_model->find_where_select(array('megadoc.bbbg_before_sign.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.bbbg_before_sign','store.id']);
		if (!empty($statusBBBGBEFORETN)) {
			$megadoc = new DigitalContractMegadoc();
			foreach ($statusBBBGBEFORETN as $key => $bbbg_before_sign) {
				if (!empty($bbbg_before_sign['megadoc']['bbbg_before_sign']['searchkey'])) {
					$company_code = $this->check_store_tcv_dong_bac($bbbg_before_sign['store']['id']);
					$response_ttbb = $megadoc->status_contract($bbbg_before_sign['megadoc']['bbbg_before_sign']['searchkey'], $company_code);
					$response_ttbb_decode = json_decode(json_decode($response_ttbb,true));
					$statusBBBGBEFOREMG = $response_ttbb_decode[0]->Status;
					if (!empty($statusBBBGBEFOREMG) && in_array($statusBBBGBEFOREMG,[0,1,2,3,7])) {
						if ($ttbb['megadoc']['bbbg_before_sign']['status'] != $statusBBBGBEFOREMG) {
							$this->contract_model->update(
								["_id" => $bbbg_before_sign["_id"] ],[
									"megadoc.bbbg_before_sign.status" => $statusBBBGBEFOREMG
								]
							);
						}
					}
				}
			}
		}
		// Tìm trạng thái VB BBBG after (final)
		$statusBBBGAFTERTN = $this->contract_model->find_where_select(array('megadoc.bbbg_after_sign.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.bbbg_after_sign','store.id']);
		if (!empty($statusBBBGAFTERTN)) {
			$megadoc = new DigitalContractMegadoc();
			foreach ($statusBBBGAFTERTN as $key => $bbbg_after_sign) {
				if (!empty($bbbg_after_sign['megadoc']['bbbg_after_sign']['searchkey'])) {
					$company_code = $this->check_store_tcv_dong_bac($bbbg_after_sign['store']['id']);
					$response_ttbb = $megadoc->status_contract($bbbg_after_sign['megadoc']['bbbg_after_sign']['searchkey'], $company_code);
					$response_ttbb_decode = json_decode(json_decode($response_ttbb,true));
					$statusBBBGAFTERMG = $response_ttbb_decode[0]->Status;
					if (!empty($statusBBBGAFTERMG) && in_array($statusBBBGAFTERMG,[0,1,2,3,7])) {
						if ($ttbb['megadoc']['bbbg_after_sign']['status'] != $statusBBBGAFTERMG) {
							$this->contract_model->update(
								["_id" => $bbbg_after_sign["_id"] ],[
									"megadoc.bbbg_after_sign.status" => $statusBBBGAFTERMG
								]
							);
						}
					}
				}
			}
		}
		echo "DONE!";
	}

	/**
	 * Xóa các file pdf HĐ megadoc đã hoàn thành
	 */
	public function remove_file_pdf_contract_megadoc()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		// Tìm trạng thái hoàn thành của VB TTBB
		$ttbbData = $this->contract_model->find_where_select(array('megadoc.ttbb.status'=> 3),['_id','code_contract']);
		if (!empty($ttbbData)) {
			foreach ($ttbbData as $key => $ttbb) {
				if (file_exists('assets/file/file_megadoc_download/' . $ttbb['code_contract'] . '_ttbb.pdf')) {
					unlink('assets/file/file_megadoc_download/' . $ttbb['code_contract'] . '_ttbb.pdf');
				}
			}
		}
		// Tìm trạng thái hoàn thành của VB BBBG before
		$bbbgTruoc = $this->contract_model->find_where_select(array('megadoc.bbbg_before_sign.status'=> 3),['_id','code_contract']);
		if (!empty($bbbgTruoc)) {
			foreach ($bbbgTruoc as $key => $bgt) {
				if (file_exists('assets/file/file_megadoc_download/' . $bgt['code_contract'] . '_bbbgt.pdf')) {
					unlink('assets/file/file_megadoc_download/' . $bgt['code_contract'] . '_bbbgt.pdf');
				}
			}
		}
		// Tìm trạng thái hoàn thành của VB BBBG after (final)
		$bbbgSau = $this->contract_model->find_where_select(array('megadoc.bbbg_after_sign.status'=> 3),['_id','code_contract']);
		if (!empty($bbbgSau)) {
			foreach ($bbbgSau as $key => $bgs) {
				if (file_exists('assets/file/file_megadoc_download/' . $bgs['code_contract'] . '_bbbgs.pdf')) {
					unlink('assets/file/file_megadoc_download/' . $bgs['code_contract'] . '_bbbgs.pdf');
				}
			}
		}
		echo "DONE!";
	}

	public function check_store_tcv_dong_bac($id_pgd)
	{
		$role = $this->role_model->findOne(['slug' => 'cong-ty-cpcn-tcv-dong-bac']);
		$id_store = [];
		if (count($role['stores']) > 0) {
			foreach ($role['stores'] as $store) {
				foreach ($store as $key => $value) {
					$id_store[] = $key;
				}
			}
		}
		if (in_array($id_pgd, $id_store)) {
			return 'TCVĐB';
		}
		return 'TCV';
	}

	public function remove_doc_tempo_contract_megadoc()
	{
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";

		if (!empty($code_contract)) {
			//remove thoa thuan ba ben
			unlink(APPPATH . '/file_megadoc/chovay_tcv_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/thechap_tcv_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/chovay_tcvdb_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/thechap_tcvdb_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $code_contract . '.pdf');

			//remove bien ban ban giao tai san khi vay
			unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $code_contract . '.pdf');
			//remove thong bao
			unlink(APPPATH . '/file_megadoc/thongbao_tcv_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $code_contract . '.pdf');

			//remove bien ban ban giao tai san sau khi Tat toan hop dong
			unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $code_contract . '.pdf');
			unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $code_contract . '.docx');
			unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $code_contract . '.pdf');
		} else {
			// Tìm trạng thái VB TTBB
			$statusTTBBTN = $this->contract_model->find_where_select(array('megadoc.ttbb.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.ttbb','store.id','code_contract']);
			if (!empty($statusTTBBTN)) {
				foreach ($statusTTBBTN as $key => $ttbb) {
					unlink(APPPATH . '/file_megadoc/chovay_tcv_' . $ttbb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/chovay_tcv_' . $ttbb['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/thechap_tcv_' . $ttbb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/thechap_tcv_' . $ttbb['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/chovay_tcvdb_' . $ttbb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/chovay_tcvdb_' . $ttbb['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/thechap_tcvdb_' . $ttbb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/thechap_tcvdb_' . $ttbb['code_contract'] . '.pdf');
				}
			}
			// Tìm trạng thái VB BBBG before
			$statusTB = $this->contract_model->find_where_select(array('megadoc.bbbg_before_sign.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.bbbg_before_sign','store.id','code_contract']);
			if (!empty($statusTB)) {
				foreach ($statusTB as $key => $tb) {
					unlink(APPPATH . '/file_megadoc/thongbao_tcv_' . $tb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcv_' . $tb['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/thongbao_tcvdb_' . $tb['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/thongbao_tcvdb_' . $tb['code_contract'] . '.pdf');
				}
			}
			// Tìm trạng thái VB THong bao
			$statusBBBGBEFORETN = $this->contract_model->find_where_select(array('megadoc.tb.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.tb','store.id','code_contract']);
			if (!empty($statusBBBGBEFORETN)) {
				foreach ($statusBBBGBEFORETN as $key => $bbbg_before_sign) {
					unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_' . $bbbg_before_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_' . $bbbg_before_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_' . $bbbg_before_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_' . $bbbg_before_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_' . $bbbg_before_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_' . $bbbg_before_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_' . $bbbg_before_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_' . $bbbg_before_sign['code_contract'] . '.pdf');
				}
			}
			// Tìm trạng thái VB BBBG after (final)
			$statusBBBGAFTERTN = $this->contract_model->find_where_select(array('megadoc.bbbg_after_sign.status'=>['$in'=>[0,1,2,3,7,99]]),['_id','megadoc.bbbg_after_sign','store.id','code_contract']);
			if (!empty($statusBBBGAFTERTN)) {
				foreach ($statusBBBGAFTERTN as $key => $bbbg_after_sign) {
					unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcv_tl_' . $bbbg_after_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcv_tl_' . $bbbg_after_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcv_tl_' . $bbbg_after_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcv_tl_' . $bbbg_after_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_chovay_tcvdb_tl_' . $bbbg_after_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_chovay_tcvdb_tl_' . $bbbg_after_sign['code_contract'] . '.pdf');
					unlink(APPPATH . '/file_megadoc/bbbg_thechap_tcvdb_tl_' . $bbbg_after_sign['code_contract'] . '.docx');
					unlink(APPPATH . '/file_megadoc_pdf/bbbg_thechap_tcvdb_tl_' . $bbbg_after_sign['code_contract'] . '.pdf');
				}
			}
		}
		echo "Deleted!";
	}

}

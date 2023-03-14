<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Report_kpi extends REST_Controller {
    public function __construct(){
        parent::__construct();
        $this->load->model('report_kpi_model');
         $this->load->model('kpi_area_model');
         $this->load->model('report_kpi_user_model');
        $this->load->model('report_kpi_user_model');
        $this->load->model('report_kpi_top_user_model');
        $this->load->model('report_kpi_top_pgd_model');
        $this->load->model('store_model');
        $this->load->model('log_model');
        $this->load->model('role_model');
        $this->load->helper('lead_helper');
        $this->load->model('group_role_model');
        $this->load->model("contract_model");
        $this->load->model("lead_model");
        $this->load->model("area_model");
        $this->load->model("report_kpi_commission_pgd_model");
        $this->load->model("report_kpi_commission_user_model");
        $this->load->model("vbi_sxh_model");
        $this->load->model("vbi_tnds_model");
        $this->load->model("vbi_utv_model");
        $this->load->model("pti_vta_bn_model");
        $this->load->model("mic_tnds_model");
        $this->load->model("gic_plt_bn_model");
        $this->load->model("contract_tnds_model");
        $this->load->model("vbi_model");
        $this->load->model("gic_easy_model");
        $this->load->model("user_model");
        $this->load->model("kpi_gdv_model");
        $this->load->model("kpi_pgd_model");
        $this->load->model("kpi_area_model");
        $this->load->model("debt_user_model");
        $this->load->model("debt_store_model");
        $this->load->model("gic_easy_bn_model");
        $this->load->model("gic_plt_model");
        $this->load->model("debt_du_no_model");


           $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $headers = $this->input->request_headers();
        $dataPost = $this->input->post();
        $this->flag_login = 1;
        if (!empty($headers['Authorization']) || !empty($headers['authorization'])) {
            $headers_item = !empty($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
            $token = Authorization::validateToken($headers_item);
            if ($token != false) {
                // Kiểm tra tài khoản và token có khớp nhau và có trong db hay không
                $this->app_login = array(
                    '_id'=>new \MongoDB\BSON\ObjectId($token->id),
                    'email'=>$token->email,
                    "status" => "active",
                    // "is_superadmin" => 1
                );
                //Web
                if($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
                if($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    // $this->ulang = $this->info['lang'];
                    $this->uemail = $this->info['email'];
                }
            }
        }
        unset($this->dataPost['type']);

    }
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost, $roleAccessRights, $info;

    public function get_detail_kpi_pgd_post()
    {
          $flag = notify_token($this->flag_login);
    if ($flag == false) return;
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $customer_email = !empty($this->dataPost['customer_email']) ? $this->dataPost['customer_email'] : "";
        $code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_area = !empty($this->dataPost['code_area']) ? $this->dataPost['code_area'] : "";
        $code_region = !empty($this->dataPost['code_region']) ? $this->dataPost['code_region'] : "";
        $code_domain = !empty($this->dataPost['code_domain']) ? $this->dataPost['code_domain'] : "";
        $condition_sum =array();

         $month = date('m', strtotime($start));
        $year = date('Y', strtotime($start));
        if (!empty($start)) {
            $condition = array(
                'month' =>$month,
                'year' => $year
            );
             $condition_sum['month'] = $month;
              $condition_sum['year'] = $year;
        }



        $stores = array();

        $stores = $this->getStores($this->id);
        if (empty($stores)) {

        }else{
            $condition['code_store'] = $stores;
             $condition_sum['store.id']=array('$in'=> $stores);
        }

          if (!empty($code_store)) {
            $condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
             $condition_sum['store.id']=array('$in'=> $code_store);
        }
         if (!empty($code_area)) {
            $condition['code_area'] = (is_array($code_area)) ? $code_area : [$code_area];
             $condition_sum['code_area']=array('$in'=> $code_area);
        }
         if (!empty($code_region)) {
            $condition['code_region'] = (is_array($code_region)) ? $code_region : [$code_region];
             $condition_sum['code_region']=array('$in'=> $code_region);
        }
         if (!empty($code_domain)) {
            $condition['code_domain'] = (is_array($code_domain)) ? $code_domain : [$code_domain];
             $condition_sum['code_domain']=array('$in'=> $code_domain);
        }




        if (!empty($customer_email)) {
            $condition['customer_email'] = $customer_email;
            $condition_sum['customer_email']=$customer_email;
        }

        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();

        $contract = $this->report_kpi_model->getKpiByTime(array(), $condition, $per_page, $uriSegment);
         $condition['total'] =1;
        $total = $this->report_kpi_model->getKpiByTime(array(), $condition);
        $arr_sum=array(
            'sum_giai_ngan'=>$this->report_kpi_model->sum_where($condition_sum,'$sum_giai_ngan'),
            'sum_bao_hiem'=>$this->report_kpi_model->sum_where($condition_sum,'$sum_bao_hiem'),
            'count_khach_hang_moi'=>$this->report_kpi_model->sum_where($condition_sum,'$count_khach_hang_moi'),
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total,
            'sum'=>$arr_sum
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function get_detail_kpi_user_post()
    {
          $flag = notify_token($this->flag_login);
    if ($flag == false) return;
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $customer_email = !empty($this->dataPost['customer_email']) ? $this->dataPost['customer_email'] : "";
        $code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_area = !empty($this->dataPost['code_area']) ? $this->dataPost['code_area'] : "";
        $code_region = !empty($this->dataPost['code_region']) ? $this->dataPost['code_region'] : "";
        $code_domain = !empty($this->dataPost['code_domain']) ? $this->dataPost['code_domain'] : "";
        $condition_sum =array();
        $month = date('m', strtotime($start));
        $year = date('Y', strtotime($start));
        if (!empty($start)) {
            $condition = array(
                'month' =>$month,
                'year' => $year
            );
             $condition_sum['month'] = $month;
              $condition_sum['year'] = $year;
        }


        $stores = array();

        $stores = $this->getStores($this->id);
        if (empty($stores)) {

        }else{
            $condition['code_store'] = $stores;
             $condition_sum['store.id']=array('$in'=> $stores);
        }

          if (!empty($code_store)) {
            $condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
             $condition_sum['store.id']=array('$in'=> $code_store);
        }
         if (!empty($code_area)) {
            $condition['code_area'] = (is_array($code_area)) ? $code_area : [$code_area];
             $condition_sum['code_area']=array('$in'=> $code_area);
        }
         if (!empty($code_region)) {
            $condition['code_region'] = (is_array($code_region)) ? $code_region : [$code_region];
             $condition_sum['code_region']=array('$in'=> $code_region);
        }
         if (!empty($code_domain)) {
            $condition['code_domain'] = (is_array($code_domain)) ? $code_domain : [$code_domain];
             $condition_sum['code_domain']=array('$in'=> $code_domain);
        }



        if (!empty($customer_email)) {
            $condition['customer_email'] = $customer_email;
            $condition_sum['customer_email']=$customer_email;
        }

        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();

        $contract = $this->report_kpi_user_model->getKpiByTime(array(), $condition, $per_page, $uriSegment);
         $condition['total'] =1;
        $total = $this->report_kpi_user_model->getKpiByTime(array(), $condition);
        $arr_sum=array(
            'sum_giai_ngan'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_giai_ngan'),
            'sum_bao_hiem'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_bao_hiem'),
            'count_khach_hang_moi'=>$this->report_kpi_user_model->sum_where($condition_sum,'$count_khach_hang_moi'),
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total,
            'sum'=>$arr_sum
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

	public function get_detail_kpi_user_v2_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$this->dataPost = $this->input->post()['condition'];
		$condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
		$customer_email = !empty($this->dataPost['customer_email']) ? $this->dataPost['customer_email'] : "";
		$code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

		$start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
		$end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
		$code_area = !empty($this->dataPost['code_area']) ? $this->dataPost['code_area'] : "";
		$code_region = !empty($this->dataPost['code_region']) ? $this->dataPost['code_region'] : "";
		$code_domain = !empty($this->dataPost['code_domain']) ? $this->dataPost['code_domain'] : "";
		$condition_sum =array();
		$month = date('m', strtotime($start));
		$year = date('Y', strtotime($start));
		if (!empty($start)) {
			$condition = array(
				'month' =>$month,
				'year' => $year
			);
			$condition_sum['month'] = $month;
			$condition_sum['year'] = $year;
		}


		$stores = array();

		$stores = $this->getStores($this->id);
		if (empty($stores)) {

		}else{
			$condition['code_store'] = $stores;
			$condition_sum['store.id']=array('$in'=> $stores);
		}

		if (!empty($code_store)) {
			$condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
			$condition_sum['store.id']=array('$in'=> [$code_store]);
		}
		if (!empty($code_area)) {
			$condition['code_area'] = (is_array($code_area)) ? $code_area : [$code_area];
			$condition_sum['code_area']=array('$in'=> $code_area);
		}
		if (!empty($code_region)) {
			$condition['code_region'] = (is_array($code_region)) ? $code_region : [$code_region];
			$condition_sum['code_region']=array('$in'=> $code_region);
		}
		if (!empty($code_domain)) {
			$condition['code_domain'] = (is_array($code_domain)) ? $code_domain : [$code_domain];
			$condition_sum['code_domain']=array('$in'=> $code_domain);
		}



		if (!empty($customer_email)) {
			$condition['customer_email'] = $customer_email;
			$condition_sum['customer_email']=$customer_email;
		}

		$per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
		$uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

		$contract = array();

		$contract = $this->report_kpi_user_model->getKpiByTime(array(), $condition, $per_page, $uriSegment);

		$condition['total'] =1;
		$total = $this->report_kpi_user_model->getKpiByTime(array(), $condition);

		$arr_sum=array(
			'sum_giai_ngan'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_giai_ngan'),
			'sum_bao_hiem'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_bao_hiem'),
			'count_khach_hang_moi'=>$this->report_kpi_user_model->sum_where($condition_sum,'$count_khach_hang_moi'),
		);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $contract,
			'total' => $total,
			'sum'=>$arr_sum
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

     public function get_detail_daily_pgd_post()
    {
          $flag = notify_token($this->flag_login);
    if ($flag == false) return;
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $customer_email = !empty($this->dataPost['customer_email']) ? $this->dataPost['customer_email'] : "";
        $code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_area = !empty($this->dataPost['code_area']) ? $this->dataPost['code_area'] : "";
        $code_region = !empty($this->dataPost['code_region']) ? $this->dataPost['code_region'] : "";
        $code_domain = !empty($this->dataPost['code_domain']) ? $this->dataPost['code_domain'] : "";
        $condition_sum =array();

        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
             $condition_sum['date'] = array(
                '$gte' => $condition['start'],
                '$lte' => $condition['end']
            );
        }


        $stores = array();

        $stores = $this->getStores($this->id);
        if (empty($stores)) {

        }else{
            $condition['code_store'] = $stores;
             $condition_sum['store.id']=array('$in'=> $stores);
        }

          if (!empty($code_store)) {
            $condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
             $condition_sum['store.id']=array('$in'=> $code_store);
        }
         if (!empty($code_area)) {
            $condition['code_area'] = (is_array($code_area)) ? $code_area : [$code_area];
             $condition_sum['code_area']=array('$in'=> $code_area);
        }
         if (!empty($code_region)) {
            $condition['code_region'] = (is_array($code_region)) ? $code_region : [$code_region];
             $condition_sum['code_region']=array('$in'=> $code_region);
        }
         if (!empty($code_domain)) {
            $condition['code_domain'] = (is_array($code_domain)) ? $code_domain : [$code_domain];
             $condition_sum['code_domain']=array('$in'=> $code_domain);
        }



        if (!empty($customer_email)) {
            $condition['customer_email'] = $customer_email;
            $condition_sum['customer_email']=$customer_email;
        }

        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();

        $contract = $this->report_kpi_top_pgd_model->getKpiByTime(array(), $condition, $per_page, $uriSegment);
         $condition['total'] =1;
        $total = $this->report_kpi_top_pgd_model->getKpiByTime(array(), $condition);
        $arr_sum=array(
            'sum_giai_ngan'=>$this->report_kpi_top_pgd_model->sum_where($condition_sum,'$sum_giai_ngan'),
            'sum_bao_hiem'=>$this->report_kpi_top_pgd_model->sum_where($condition_sum,'$sum_bao_hiem'),
            'count_khach_hang_moi'=>$this->report_kpi_top_pgd_model->sum_where($condition_sum,'$count_khach_hang_moi'),
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total,
            'sum'=>$arr_sum
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function get_detail_daily_user_post()
    {
          $flag = notify_token($this->flag_login);
    if ($flag == false) return;
        $this->dataPost = $this->input->post()['condition'];
        $condition = !empty($this->dataPost['condition']) ? $this->dataPost['condition'] : array();
        $customer_email = !empty($this->dataPost['customer_email']) ? $this->dataPost['customer_email'] : "";
        $code_store = !empty($this->dataPost['code_store']) ? $this->dataPost['code_store'] : "";

        $start = !empty($this->dataPost['start']) ? $this->dataPost['start'] : "";
        $end = !empty($this->dataPost['end']) ? $this->dataPost['end'] : "";
        $code_area = !empty($this->dataPost['code_area']) ? $this->dataPost['code_area'] : "";
        $code_region = !empty($this->dataPost['code_region']) ? $this->dataPost['code_region'] : "";
        $code_domain = !empty($this->dataPost['code_domain']) ? $this->dataPost['code_domain'] : "";
        $condition_sum =array();

        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
             $condition_sum['date'] = array(
                '$gte' => $condition['start'],
                '$lte' => $condition['end']
            );
        }


        $stores = array();

        $stores = $this->getStores($this->id);
        if (empty($stores)) {

        }else{
            $condition['code_store'] = $stores;
             $condition_sum['store.id']=array('$in'=> $stores);
        }

          if (!empty($code_store)) {
            $condition['code_store'] = (is_array($code_store)) ? $code_store : [$code_store];
             $condition_sum['store.id']=array('$in'=> $code_store);
        }
         if (!empty($code_area)) {
            $condition['code_area'] = (is_array($code_area)) ? $code_area : [$code_area];
             $condition_sum['code_area']=array('$in'=> $code_area);
        }
         if (!empty($code_region)) {
            $condition['code_region'] = (is_array($code_region)) ? $code_region : [$code_region];
             $condition_sum['code_region']=array('$in'=> $code_region);
        }
         if (!empty($code_domain)) {
            $condition['code_domain'] = (is_array($code_domain)) ? $code_domain : [$code_domain];
             $condition_sum['code_domain']=array('$in'=> $code_domain);
        }



        if (!empty($customer_email)) {
            $condition['customer_email'] = $customer_email;
            $condition_sum['customer_email']=$customer_email;
        }

        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;

        $contract = array();

        $contract = $this->report_kpi_user_model->getKpiByTime(array(), $condition, $per_page, $uriSegment);
         $condition['total'] =1;
        $total = $this->report_kpi_user_model->getKpiByTime(array(), $condition);
        $arr_sum=array(
            'sum_giai_ngan'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_giai_ngan'),
            'sum_bao_hiem'=>$this->report_kpi_user_model->sum_where($condition_sum,'$sum_bao_hiem'),
            'count_khach_hang_moi'=>$this->report_kpi_user_model->sum_where($condition_sum,'$count_khach_hang_moi'),
        );

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract,
            'total' => $total,
            'sum'=>$arr_sum
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function kpi_domain_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $data = $this->input->post();
        $start_old = '2019-11-01';
        $start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
        $end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
        $condition = array();
        $condition_lead = array();


        if (!empty($start) && !empty($end)) {
              $condition_old = array(
                '$gte' => strtotime(trim($start_old).' 00:00:00'),
                '$lte' => strtotime(trim($end).' 23:59:59')
            );
            $condition_lead = array(
                '$gte' => strtotime(trim($start).' 00:00:00'),
                '$lte' => strtotime(trim($end).' 23:59:59')
            );
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

            //Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] - 1;
			}


			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');


			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);

        }

         $groupRoles = $this->getGroupRole($this->id);
          $stores = $this->getStores_list($this->id);

        //   $stores =array();
         if (in_array('giao-dich-vien', $groupRoles)  && !in_array('cua-hang-truong', $groupRoles)) {

            $created_by = $this->uemail;
            $condition['created_by']=$created_by;


        }else{
          if(empty($stores))
          {
              $storeData = $this->store_model->find_where_in('status', ['active']);

            if (!empty($storeData)) {

                foreach ($storeData as $key => $item) {
                    array_push($stores,(string)$item['_id']);
                }
            }
             $condition['store.id']=array('$in'=>$stores);
          }else{
              $condition['store.id']=array('$in'=>$stores);
          }

        }


        // $data_dashboard['contract']['contract_total']  = $contract->count(array("date"=>$condition,));
        $rk = new Report_kpi_model();
        $rku = new Report_kpi_user_model();
        $rktu = new Report_kpi_top_user_model();
        $rktp = new Report_kpi_top_pgd_model();
         $kpi_area = new Kpi_area_model();
         $area = new Area_model();
          $contract = new Contract_model();
          $rkcpm = new Report_kpi_commission_pgd_model();
          $rkcum = new Report_kpi_commission_user_model();
		$debt_user = new Debt_user_model();
		$debt_store = new Debt_store_model();
		$debt_du_no = new Debt_du_no_model();

        $arr_area=array();
        $data_report= array();
         $data_report['total_so_tien_vay']= 0;
        $data_report['total_du_no_qua_han']=0;
        $data_report['total_du_no_dang_cho_vay']=0;


        //v2
		$data_report['total_du_no_trong_han_t10_old']=$contract->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'created_by'=>$created_by],'$debt.tong_tien_goc_con');
		$data_report['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total(['user'=> $created_by, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');


		$data_report['total_doanh_so_bao_hiem']=$rkcum->sum_where_total(['san_pham' => 'BH', 'created_at' => $condition_lead,'user'=>$created_by],'$commision.doanh_so');
		$data_report['total_tien_hoa_hong']=$rkcum->sum_where_total(['created_at' => $condition_lead,'user'=>$created_by],'$commision.tien_hoa_hong');


		$data_report['total_du_no_trong_han_t10'] =  $data_report['total_du_no_trong_han_t10_old'] - $data_report['total_du_no_trong_han_t10_thang_truoc'];

		if (in_array('giao-dich-vien', $groupRoles)  && !in_array('cua-hang-truong', $groupRoles)) {


			$data_report['total_so_tien_vay']=  $contract->sum_where_total(['created_by' => $created_by,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));
			$data_report['total_so_tien_vay_old']= $contract->sum_where_total(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19]),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'created_by' => $created_by],array('$toLong' => '$loan_infor.amount_money'));
        $data_report['total_du_no_dang_cho_vay_old']=$contract->sum_where_total(['disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'created_by' => $created_by],'$debt.tong_tien_goc_con');
//        $data_report['total_du_no_qua_han_t10_old']=$contract->sum_where_total_mongo_read(['created_by' => $created_by,'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_dang_cho_vay']=$contract->sum_where_total(['created_by' => $created_by,'disbursement_date'=>$condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');



			$data_report['contract_moi']=$rktu->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead,'user'=>$created_by],'$contract_moi');
        $data_report['contract_dang_xl']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_dang_xl');
       $data_report['contract_cho_cd']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_cho_cd');
        $data_report['contract_da_duyet']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_da_duyet');
        $data_report['contract_cho_gn']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_cho_gn');
        $data_report['contract_da_gn']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_da_gn');
         $data_report['contract_khac']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_khac');
         $data_report['contract_total']= $rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_total');

        }else{
        $data_report['total_so_tien_vay_old']= $contract->sum_where_total(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

		//V2
//        $data_report['total_du_no_qua_han_t10_thang_truoc']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_thang_truoc,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
//        $data_report['total_du_no_qua_han_t10_thang_nay']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_thang_nay,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			$data_report['total_du_no_trong_han_t10_old']=$contract->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');


			$data_report['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');
        $data_report['total_tien_hoa_hong']=$rkcpm->sum_where_total(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');


			$data_report['total_du_no_trong_han_t10'] =  $data_report['total_du_no_trong_han_t10_old'] - $data_report['total_du_no_trong_han_t10_thang_truoc'];

        //
        $data_report['total_du_no_dang_cho_vay_old']=$contract->sum_where_total(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

        $data_report['total_so_tien_vay']=  $contract->sum_where_total(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			$data_report['total_du_no_dang_cho_vay_thang_truoc'] = $debt_du_no->sum_where_total(['created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');
			$data_report['du_no_tang_net'] = $data_report['total_du_no_dang_cho_vay_old'] - $data_report['total_du_no_dang_cho_vay_thang_truoc'];
       // var_dump($stores); die;

        $data_report['total_du_no_dang_cho_vay']=$contract->sum_where_total(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');


       $data_report['contract_moi']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$contract_moi');

        $data_report['contract_dang_xl']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_dang_xl');
       $data_report['contract_cho_cd']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_cd');
        $data_report['contract_da_duyet']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_duyet');
        $data_report['contract_cho_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_gn');
        $data_report['contract_da_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_gn');
         $data_report['contract_khac']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_khac');
         $data_report['contract_total']= $rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_total');

        }
         if (in_array('giao-dich-vien', $groupRoles)  && !in_array('cua-hang-truong', $groupRoles)) {

         $arr_month=['01','02','03','04','05','06','07','08','09','10','11','12'];
        foreach ($arr_month as $key => $month) {
        	$report_kpiData= $rku->find_where(['month'=>$month,'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'user_email'=>$created_by]);
        if(!empty($report_kpiData))
        {
        	foreach ($report_kpiData as $report_kpi){
				$data_report['kpi_bao_hiem']+=$report_kpi['sum_bao_hiem'];
				$data_report['kpi_kh_moi']+=$report_kpi['count_khach_hang_moi'];
				$data_report['kpi_giai_ngan']+=$report_kpi['sum_giai_ngan'];
				$data_report['tong_chi_tieu']+=$report_kpi['sum_giai_ngan']+$report_kpi['count_khach_hang_moi']+$report_kpi['sum_bao_hiem'];
				if(!empty($report_kpi['kpi'])) {
					$giai_ngan_CT = (isset($report_kpi['kpi']['giai_ngan_CT'])) ? $report_kpi['kpi']['giai_ngan_CT'] : 0;
					$bao_hiem_CT = (isset($report_kpi['kpi']['bao_hiem_CT'])) ? $report_kpi['kpi']['bao_hiem_CT'] : 0;
					$khach_hang_moi_CT = (isset($report_kpi['kpi']['khach_hang_moi_CT'])) ? $report_kpi['kpi']['khach_hang_moi_CT'] : 0;
					$giai_ngan_TT = (isset($report_kpi['kpi']['giai_ngan_TT'])) ? $report_kpi['kpi']['giai_ngan_TT'] : 0;
					$bao_hiem_TT = (isset($report_kpi['kpi']['bao_hiem_TT'])) ? $report_kpi['kpi']['bao_hiem_TT'] : 0;
					$khach_hang_moi_TT = (isset($report_kpi['kpi']['khach_hang_moi_TT'])) ? $report_kpi['kpi']['khach_hang_moi_TT'] : 0;
				}
				$sum_bao_hiem+=(isset($report_kpi['sum_bao_hiem'])) ? $report_kpi['sum_bao_hiem'] : 0;
				$count_khach_hang_moi=(isset($report_kpi['count_khach_hang_moi'])) ? $report_kpi['count_khach_hang_moi'] : 0;
				$sum_giai_ngan+=(isset($report_kpi['sum_giai_ngan'])) ? $report_kpi['sum_giai_ngan'] : 0;

				if (in_array('giao-dich-vien', $groupRoles)  && !in_array('cua-hang-truong', $groupRoles)) {
					$data_report['data_labels']='"'.$report_kpi['user_email'].'",';
				}
			}

        $data_report['data_kpichitieu_dsGiaiNgan'].=$giai_ngan_CT.',';
         $data_report['data_kpichitieu_dsBaoHiem'].=$bao_hiem_CT.',';
         $data_report['data_kpichitieu_slKhachHangMoi'].=$khach_hang_moi_CT.',';
         $data_report['data_kpidatduoc_dsBaoHiem'].=$sum_bao_hiem.',';
         $data_report['data_kpidatduoc_slKhachHangMoi'].= $count_khach_hang_moi.',';
         $data_report['data_kpidatduoc_dsGiaiNgan'].=$sum_giai_ngan.',';
         $data_report['datakpi_titrong_dsGiaiNgan'].=$giai_ngan_TT.',';
         $data_report['datakpi_titrong_dsBaoHiem'].=$bao_hiem_TT.',';
         $data_report['datakpi_titrong_slKhachHangMoi'].=$khach_hang_moi_TT.',';
          }else{
			$data_report['data_kpichitieu_dsGiaiNgan'].='0,';
			$data_report['data_kpichitieu_dsBaoHiem'].='0,';
			$data_report['data_kpichitieu_slKhachHangMoi'].='0,';
			$data_report['data_kpidatduoc_dsBaoHiem'].='0,';
			$data_report['data_kpidatduoc_slKhachHangMoi'].= '0,';
			$data_report['data_kpidatduoc_dsGiaiNgan'].='0,';
			$data_report['datakpi_titrong_dsGiaiNgan'].='0,';
			$data_report['datakpi_titrong_dsBaoHiem'].='0,';
			$data_report['datakpi_titrong_slKhachHangMoi'].='0,';

		}
          }
          $data_report['data_kpichitieu_dsGiaiNgan']=rtrim($data_report['data_kpichitieu_dsGiaiNgan'],',');
         $data_report['data_kpichitieu_dsBaoHiem']=rtrim($data_report['data_kpichitieu_dsBaoHiem'],',');
         $data_report['data_kpichitieu_slKhachHangMoi']=rtrim($data_report['data_kpichitieu_slKhachHangMoi'],',');

         $data_report['data_kpidatduoc_dsBaoHiem']=rtrim($data_report['data_kpidatduoc_dsBaoHiem'],',');
         $data_report['data_kpidatduoc_slKhachHangMoi']=rtrim($data_report['data_kpidatduoc_slKhachHangMoi'],',');
         $data_report['data_kpidatduoc_dsGiaiNgan']=rtrim($data_report['data_kpidatduoc_dsGiaiNgan'],',');

         $data_report['datakpi_titrong_dsGiaiNgan']=rtrim($data_report['datakpi_titrong_dsGiaiNgan'],',');
         $data_report['datakpi_titrong_dsBaoHiem']=rtrim( $data_report['datakpi_titrong_dsBaoHiem'],',');
         $data_report['datakpi_titrong_slKhachHangMoi']=rtrim( $data_report['datakpi_titrong_slKhachHangMoi'],',');
         }else{
             if (in_array('cua-hang-truong', $groupRoles) && !in_array('phat-trien-san-pham', $groupRoles) && !in_array('quan-ly-khu-vuc', $groupRoles)) {
            $data_report['report_kpi']= $rku->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);
            $data_report['data_kpi']= $rk->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);
            }else{
            $data_report['report_kpi']= $rk->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);

            if (in_array('quan-ly-khu-vuc', $groupRoles) && !in_array('quan-ly-cap-cao', $groupRoles)){
				$check_area = [];
            	foreach ($stores as $item){
					$check = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($item)));
					if (!in_array($check['code_area'],$check_area)){
						array_push($check_area,$check['code_area']);
					}

				}
					 if (!empty($check_area)){
						 $data_report['data_kpi']= $kpi_area->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'area.code'=>array('$in'=> $check_area)]);

					 }
				 }
             }

            if ((!in_array('cua-hang-truong', $groupRoles) && !in_array('giao-dich-vien', $groupRoles)) || (in_array('phat-trien-san-pham', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)))  {
            $area_data=$area->find_where(['status'=>'active']);
            $n=0;
            foreach ($area_data as $key => $value) {
                $n++;
                $kpi_a=$kpi_area->findOne(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'area.code'=>$value['code']]);
               $bao_hiem=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$sum_bao_hiem');
               $giai_ngan=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$sum_giai_ngan');
               $du_no_tang_net=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$du_no_tang_net');
               $khach_hang_moi=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$count_khach_hang_moi');
              if(!empty($kpi_a))
              $arr_area+=[$n=>['bao_hiem'=>$bao_hiem,'giai_ngan'=>$giai_ngan,'khach_hang_moi'=>$khach_hang_moi,'name'=>$value['title'],'kpi'=>$kpi_a,'du_no_tang_net'=>$du_no_tang_net]];
            }
          }
            foreach ($data_report['report_kpi'] as $key => $value) {

        $data_report['kpi_bao_hiem']+=$value['sum_bao_hiem'];
         $data_report['kpi_kh_moi']+=$value['count_khach_hang_moi'];
         $data_report['kpi_giai_ngan']+=$value['sum_giai_ngan'];
         $data_report['tong_chi_tieu']+=$value['sum_giai_ngan']+$value['count_khach_hang_moi']+$value['sum_bao_hiem'];

        }
        }
        $data_report['data_area']=$arr_area;
        $data_report['groupRoles']=$groupRoles;




         if($data_report){
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' =>  $data_report


            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }

	public function kpi_domain_detail_post(){
//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$code_area = !empty( $data['code_area']) ? $data['code_area'] : "";

		$stores = $this->getStores_list_detail($code_area);

		//   $stores =array();

			if(empty($stores))
			{
				$storeData = $this->store_model->find_where_in('status', ['active']);

				if (!empty($storeData)) {

					foreach ($storeData as $key => $item) {
						array_push($stores,(string)$item['_id']);
					}
				}
				$condition['store.id']=array('$in'=>$stores);
			}else{
				$condition['store.id']=array('$in'=>$stores);
			}


		// $data_dashboard['contract']['contract_total']  = $contract->count(array("date"=>$condition,));
		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktu = new Report_kpi_top_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$lead = new Lead_model();
		$kpi_area = new Kpi_area_model();
		$area = new Area_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();

		$debt_store = new Debt_store_model();

		$arr_area=array();
		$data_report= array();
		$data_report['total_so_tien_vay']= 0;
		$data_report['total_du_no_qua_han']=0;
		$data_report['total_du_no_dang_cho_vay']=0;



		//v2


			$data_report['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));
			$data_report['total_du_no_qua_han_t4_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_old,'debt.so_ngay_cham_tra'=>['$gte'=>4],'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			$data_report['total_du_no_qua_han_t10_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//V2

			$data_report['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');


			$data_report['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');
			$data_report['total_tien_hoa_hong']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');


		$data_report['total_du_no_trong_han_t10'] = $data_report['total_du_no_trong_han_t10_old'] - $data_report['total_du_no_trong_han_t10_thang_truoc'];

			//
			$data_report['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			$data_report['total_so_tien_vay']=  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			// var_dump($stores); die;
			$data_report['total_du_no_qua_han_t4']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$total_du_no_qua_han_t4');
			$data_report['total_du_no_qua_han_t10']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$total_du_no_qua_han_t10');

			$data_report['total_du_no_dang_cho_vay']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');


			$data_report['contract_moi']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$contract_moi');

			$data_report['contract_dang_xl']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_dang_xl');
			$data_report['contract_cho_cd']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_cd');
			$data_report['contract_da_duyet']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_duyet');
			$data_report['contract_cho_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_gn');
			$data_report['contract_da_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_gn');
			$data_report['contract_khac']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_khac');
			$data_report['contract_total']= $rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_total');



			//Update không hiển thị các PGD đã cơ cấu
			$data_report['report_kpi']= $rk->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);
			$arr_store_new = [];
			foreach ($data_report['report_kpi'] as $value){
				$check = $this->check_store_cocau($value['store']['id']);

				if ($check == true){
					array_push($arr_store_new, $value);
				}
			}
			$data_report['report_kpi'] = $arr_store_new;

					$check_area = [];
					foreach ($stores as $item){
						$check = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($item)));
						if (!in_array($check['code_area'],$check_area)){
							array_push($check_area,$check['code_area']);
						}

					}
					if (!empty($check_area)){
						$data_report['data_kpi']= $kpi_area->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'area.code'=>array('$in'=> $check_area)]);

					}




				$area_data=$area->find_where(['status'=>'active']);
				$n=0;
				foreach ($area_data as $key => $value) {
					$n++;
					$kpi_a=$kpi_area->findOne(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'area.code'=>$value['code']]);
					$bao_hiem=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$sum_bao_hiem');
					$giai_ngan=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$sum_giai_ngan');
					$du_no_tang_net=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$du_no_tang_net');
					$khach_hang_moi=$rk->sum_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'code_area'=>$value['code']],'$count_khach_hang_moi');
					if(!empty($kpi_a))
						$arr_area+=[$n=>['bao_hiem'=>$bao_hiem,'giai_ngan'=>$giai_ngan,'khach_hang_moi'=>$khach_hang_moi,'name'=>$value['title'],'kpi'=>$kpi_a,'du_no_tang_net'=>$du_no_tang_net]];
				}

			foreach ($data_report['report_kpi'] as $key => $value) {

				$data_report['kpi_bao_hiem']+=$value['sum_bao_hiem'];
				$data_report['kpi_kh_moi']+=$value['count_khach_hang_moi'];
				$data_report['kpi_giai_ngan']+=$value['sum_giai_ngan'];
				$data_report['tong_chi_tieu']+=$value['sum_giai_ngan']+$value['count_khach_hang_moi']+$value['sum_bao_hiem'];

			}


		$data_report['data_area']=$arr_area;


		if($data_report){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_report


			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	private function check_store_cocau($idStore){

    	$check = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($idStore),'type_pgd' => '3']);

    	if (!empty($check)){
    		return false;
		} else {
    		return true;
		}

	}

       private function getStores_list($userId)
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
                                array_push($roleStores,key($item));
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
				if (!empty($role['stores']) && count($role['stores']) == 1) {
					$arrStores = array();
					foreach ($role['stores'] as $item) {
						array_push($arrStores, key($item));
					}

					//Check userId in list key of $users
					foreach ($storeId as $s){

						if (in_array($s, $arrStores) == TRUE) {
							if (!empty($role['stores'])) {
								//Push store

								foreach ($role['users'] as $key => $item) {
									foreach ($item as $e){
										array_push($roleAllUsers, $e->email);
									}
								}

							}
						}
					}

				}
			}
		}
		$roleUsers = array_unique($roleAllUsers);
		return $roleUsers;
	}


	private function getStores_list_detail($code_area)
	{
		$roles = $this->store_model->find_where(array("status" => "active","code_area"=>$code_area));
		$roleStores = array();
		if (count($roles) > 0){
			foreach ($roles as $role){
				array_push($roleStores,(string)$role['_id']);
			}
		}

		return $roleStores;
	}

	private function getStores_pgd_detail($store_id)
	{
		$roles = $this->store_model->find_where(array("_id" => new MongoDB\BSON\ObjectId($store_id)));
		$roleStores = array();
		if (count($roles) > 0){
			foreach ($roles as $role){
				array_push($roleStores,(string)$role['_id']);
			}
		}

		return $roleStores;
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

    public function kpi_domain_detail_lead_post(){

//		$flag = notify_token($this->flag_login);
//		if ($flag == false) return;
		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] - 1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

//		$groupRoles = $this->getGroupRole($this->id);

		$store_id = !empty( $data['store_id']) ? $data['store_id'] : "";

		$stores = $this->getStores_pgd_detail($store_id);

		$store_name = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($store_id)]);
		//   $stores =array();

			if(empty($stores))
			{
				$storeData = $this->store_model->find_where_in('status', ['active']);

				if (!empty($storeData)) {

					foreach ($storeData as $key => $item) {
						array_push($stores,(string)$item['_id']);
					}
				}
				$condition['store.id']=array('$in'=>$stores);
			}else{
				$condition['store.id']=array('$in'=>$stores);
			}


		// $data_dashboard['contract']['contract_total']  = $contract->count(array("date"=>$condition,));
		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktu = new Report_kpi_top_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$lead = new Lead_model();
		$kpi_area = new Kpi_area_model();
		$area = new Area_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();

		$debt_store = new Debt_store_model();

		$arr_area=array();
		$data_report= array();
		$data_report['total_so_tien_vay']= 0;
		$data_report['total_du_no_qua_han']=0;
		$data_report['total_du_no_dang_cho_vay']=0;



		//v2

			$data_report['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

			$data_report['total_du_no_qua_han_t10_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//V2

			$data_report['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data_report['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');
			$data_report['total_tien_hoa_hong']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

		$data_report['total_du_no_trong_han_t10'] =  $data_report['total_du_no_trong_han_t10_old'] - $data_report['total_du_no_trong_han_t10_thang_truoc'];

			//
			$data_report['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			$data_report['total_so_tien_vay']=  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			// var_dump($stores); die;
			$data_report['total_du_no_qua_han_t4']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$total_du_no_qua_han_t4');
			$data_report['total_du_no_qua_han_t10']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$total_du_no_qua_han_t10');

			$data_report['total_du_no_dang_cho_vay']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');


			$data_report['contract_moi']=$rktp->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead],'$contract_moi');

			$data_report['contract_dang_xl']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_dang_xl');
			$data_report['contract_cho_cd']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_cd');
			$data_report['contract_da_duyet']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_duyet');
			$data_report['contract_cho_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_cho_gn');
			$data_report['contract_da_gn']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_da_gn');
			$data_report['contract_khac']=$rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_khac');
			$data_report['contract_total']= $rktp->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores)],'$contract_total');


			$data_report['report_kpi']= $rku->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);
			$data_report['data_kpi']= $rk->get_where(['month'=>date("m",strtotime(trim($start).' 00:00:00')),'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'store.id'=>array('$in'=> $stores)]);


			foreach ($data_report['report_kpi'] as $key => $value) {

				$data_report['kpi_bao_hiem']+=$value['sum_bao_hiem'];
				$data_report['kpi_kh_moi']+=$value['count_khach_hang_moi'];
				$data_report['kpi_giai_ngan']+=$value['sum_giai_ngan'];
				$data_report['tong_chi_tieu']+=$value['sum_giai_ngan']+$value['count_khach_hang_moi']+$value['sum_bao_hiem'];

			}

		$data_report['data_area']=$arr_area;
		$data_report['store_name']= $store_name['name'];




		if($data_report){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_report


			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}


	}

	public function kpi_domain_detail_nhanvien_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$name = !empty( $data['name']) ?  $data['name'] : "";

		$id_user = $this->user_model->findOne(["email"=>$name]);

		$stores = $this->getStores_list((string)$id_user['_id']);

		//   $stores =array();

			$created_by = $name;
			$condition['created_by']=$created_by;


		// $data_dashboard['contract']['contract_total']  = $contract->count(array("date"=>$condition,));
		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktu = new Report_kpi_top_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$lead = new Lead_model();
		$kpi_area = new Kpi_area_model();
		$area = new Area_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();
		$debt_user = new Debt_user_model();

		$arr_area=array();
		$data_report= array();
		$data_report['total_so_tien_vay']= 0;
		$data_report['total_du_no_qua_han']=0;
		$data_report['total_du_no_dang_cho_vay']=0;



		//v2
		$data_report['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'created_by'=>$created_by],'$debt.tong_tien_goc_con');
		$data_report['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total_mongo_read(['user' => $created_by , 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

		$data_report['total_doanh_so_bao_hiem']=$rkcum->sum_where_total_mongo_read(['san_pham' => 'BH', 'created_at' => $condition_lead,'user'=>$created_by],'$commision.doanh_so');
		$data_report['total_tien_hoa_hong']=$rkcum->sum_where_total_mongo_read(['created_at' => $condition_lead,'user'=>$created_by],'$commision.tien_hoa_hong');



		$data_report['total_du_no_trong_han_t10'] =  $data_report['total_du_no_trong_han_t10_old'] - $data_report['total_du_no_trong_han_t10_thang_truoc'];


			$data_report['total_du_no_qua_han_t4']=$rktu->sum_where(['date'=>$condition_lead,'user'=>$created_by],'$total_du_no_qua_han_t4');

			$data_report['total_so_tien_vay']=  $contract->sum_where_total_mongo_read(['created_by' => $created_by,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));
			$data_report['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19]),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'created_by' => $created_by],array('$toLong' => '$loan_infor.amount_money'));
			$data_report['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'created_by' => $created_by],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_qua_han_t10_old']=$contract->sum_where_total_mongo_read(['created_by' => $created_by,'debt.so_ngay_cham_tra'=>['$gte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$data_report['total_du_no_dang_cho_vay']=$contract->sum_where_total_mongo_read(['created_by' => $created_by,'disbursement_date'=>$condition_lead,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');



			$data_report['contract_moi']=$rktu->sum_where(['store.id'=>array('$in'=> $stores),'date'=>$condition_lead,'user'=>$created_by],'$contract_moi');
			$data_report['contract_dang_xl']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_dang_xl');
			$data_report['contract_cho_cd']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_cho_cd');
			$data_report['contract_da_duyet']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_da_duyet');
			$data_report['contract_cho_gn']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_cho_gn');
			$data_report['contract_da_gn']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_da_gn');
			$data_report['contract_khac']=$rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_khac');
			$data_report['contract_total']= $rktu->sum_where(['date'=>$condition_lead,'store.id'=>array('$in'=> $stores),'user'=>$created_by],'$contract_total');




			$arr_month=['01','02','03','04','05','06','07','08','09','10','11','12'];
			foreach ($arr_month as $key => $month) {
				$report_kpiData= $rku->find_where(['month'=>$month,'year'=>date("Y",strtotime(trim($start).' 00:00:00')),'user_email'=>$created_by]);
				if(!empty($report_kpiData))
				{
					foreach ($report_kpiData as $report_kpi){
						$data_report['kpi_bao_hiem']+=$report_kpi['sum_bao_hiem'];
						$data_report['kpi_kh_moi']+=$report_kpi['count_khach_hang_moi'];
						$data_report['kpi_giai_ngan']+=$report_kpi['sum_giai_ngan'];
						$data_report['tong_chi_tieu']+=$report_kpi['sum_giai_ngan']+$report_kpi['count_khach_hang_moi']+$report_kpi['sum_bao_hiem'];
						if(!empty($report_kpi['kpi'])) {
							$giai_ngan_CT = (isset($report_kpi['kpi']['giai_ngan_CT'])) ? $report_kpi['kpi']['giai_ngan_CT'] : 0;
							$bao_hiem_CT = (isset($report_kpi['kpi']['bao_hiem_CT'])) ? $report_kpi['kpi']['bao_hiem_CT'] : 0;
							$khach_hang_moi_CT = (isset($report_kpi['kpi']['khach_hang_moi_CT'])) ? $report_kpi['kpi']['khach_hang_moi_CT'] : 0;
							$giai_ngan_TT = (isset($report_kpi['kpi']['giai_ngan_TT'])) ? $report_kpi['kpi']['giai_ngan_TT'] : 0;
							$bao_hiem_TT = (isset($report_kpi['kpi']['bao_hiem_TT'])) ? $report_kpi['kpi']['bao_hiem_TT'] : 0;
							$khach_hang_moi_TT = (isset($report_kpi['kpi']['khach_hang_moi_TT'])) ? $report_kpi['kpi']['khach_hang_moi_TT'] : 0;
						}
						$sum_bao_hiem+=(isset($report_kpi['sum_bao_hiem'])) ? $report_kpi['sum_bao_hiem'] : 0;
						$count_khach_hang_moi=(isset($report_kpi['count_khach_hang_moi'])) ? $report_kpi['count_khach_hang_moi'] : 0;
						$sum_giai_ngan+=(isset($report_kpi['sum_giai_ngan'])) ? $report_kpi['sum_giai_ngan'] : 0;

						if (in_array('giao-dich-vien', $groupRoles)  && !in_array('cua-hang-truong', $groupRoles)) {
							$data_report['data_labels']='"'.$report_kpi['user_email'].'",';
						}
					}

					$data_report['data_kpichitieu_dsGiaiNgan'].=$giai_ngan_CT.',';
					$data_report['data_kpichitieu_dsBaoHiem'].=$bao_hiem_CT.',';
					$data_report['data_kpichitieu_slKhachHangMoi'].=$khach_hang_moi_CT.',';
					$data_report['data_kpidatduoc_dsBaoHiem'].=$sum_bao_hiem.',';
					$data_report['data_kpidatduoc_slKhachHangMoi'].= $count_khach_hang_moi.',';
					$data_report['data_kpidatduoc_dsGiaiNgan'].=$sum_giai_ngan.',';
					$data_report['datakpi_titrong_dsGiaiNgan'].=$giai_ngan_TT.',';
					$data_report['datakpi_titrong_dsBaoHiem'].=$bao_hiem_TT.',';
					$data_report['datakpi_titrong_slKhachHangMoi'].=$khach_hang_moi_TT.',';
				}else{
					$data_report['data_kpichitieu_dsGiaiNgan'].='0,';
					$data_report['data_kpichitieu_dsBaoHiem'].='0,';
					$data_report['data_kpichitieu_slKhachHangMoi'].='0,';
					$data_report['data_kpidatduoc_dsBaoHiem'].='0,';
					$data_report['data_kpidatduoc_slKhachHangMoi'].= '0,';
					$data_report['data_kpidatduoc_dsGiaiNgan'].='0,';
					$data_report['datakpi_titrong_dsGiaiNgan'].='0,';
					$data_report['datakpi_titrong_dsBaoHiem'].='0,';
					$data_report['datakpi_titrong_slKhachHangMoi'].='0,';

				}
			}
			$data_report['data_kpichitieu_dsGiaiNgan']=rtrim($data_report['data_kpichitieu_dsGiaiNgan'],',');
			$data_report['data_kpichitieu_dsBaoHiem']=rtrim($data_report['data_kpichitieu_dsBaoHiem'],',');
			$data_report['data_kpichitieu_slKhachHangMoi']=rtrim($data_report['data_kpichitieu_slKhachHangMoi'],',');

			$data_report['data_kpidatduoc_dsBaoHiem']=rtrim($data_report['data_kpidatduoc_dsBaoHiem'],',');
			$data_report['data_kpidatduoc_slKhachHangMoi']=rtrim($data_report['data_kpidatduoc_slKhachHangMoi'],',');
			$data_report['data_kpidatduoc_dsGiaiNgan']=rtrim($data_report['data_kpidatduoc_dsGiaiNgan'],',');

			$data_report['datakpi_titrong_dsGiaiNgan']=rtrim($data_report['datakpi_titrong_dsGiaiNgan'],',');
			$data_report['datakpi_titrong_dsBaoHiem']=rtrim( $data_report['datakpi_titrong_dsBaoHiem'],',');
			$data_report['datakpi_titrong_slKhachHangMoi']=rtrim( $data_report['datakpi_titrong_slKhachHangMoi'],',');


		$data_report['data_area']=$arr_area;


		if($data_report){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_report


			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function exportDashboard_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$area = $this->area_model->find_where(["status" => "active"]);
		$code_area = [];
		if (!empty($area)){
			foreach ($area as $value){
				if ($value['code'] == "Priority"){
					continue;
				}

				$code_area += [$value['title']=>$value['code']];
			}
		}


		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();

		$debt_store = new Debt_store_model();

		$data = [];

		foreach ($code_area as $key => $item){

			$stores = $this->getStores_list_detail($item);

			$condition['store.id']=array('$in'=>$stores);

			$data[$key]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//du_no_tang_net

			$total_du_no_trong_han_t10_1 = $contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			$total_du_no_trong_han_t10_2 = $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');


			$data[$key]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//doanh_so_bao_hiem
			$data[$key]['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');

			//Tổng tiền giải ngân
			$data[$key]['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

			//Dư nợ quản lý
			$data[$key]['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 hiện tại
			$data[$key]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 kỳ trước
			$data[$key]['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function exportAllBaohiem_post(){

		$data = $this->input->post();

//		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
//		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');

		$start = !empty( $data['fdate']) ? $data['fdate'] :  date('Y-m-01');
		$end = !empty( $data['tdate']) ?  $data['tdate'] :  date('Y-m-d');

		$condition = [];

		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		}
		if (!empty($end)) {
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');
		}

		$groupRoles = $this->getGroupRole($this->id);
		$stores = $this->getStores_list($this->id);

		if (in_array('giao-dich-vien', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)) {
			$created_by = $this->uemail;
			$condition['created_by'] = $created_by;
		} else {
			if (empty($stores)) {
				$storeData = $this->store_model->find_where_in('status', ['active']);

				if (!empty($storeData)) {

					foreach ($storeData as $key => $item) {
						array_push($stores, (string)$item['_id']);
					}
				}
				$condition['store.id'] = array('$in' => $stores);
			} else {
				$condition['store.id'] = array('$in' => $stores);
			}
		}

		$condition['status'] = 1;

		$data_vbi_sxh = [];
		$data_vbi_tnds = [];
		$data_vbi_utv = [];
		$data_pti_vta_bn = [];
		$data_mic_tnds = [];
		$data_gic_plt_bn = [];
		$data_contract_tnds = [];
		$data_vbi = [];
		$data_gic_easy = [];
		$data_gic_plt_easy = [];

		//vbi_sxh
		$list_vbi_sxh = $this->vbi_sxh_model->getAll_excel($condition);
		if (!empty($list_vbi_sxh)){
			foreach ($list_vbi_sxh as $value){
				$data_1['ma_hop_dong'] = "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['vbi_sxh']['so_hd']) ? $value['vbi_sxh']['so_hd'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['customer_info']['ngay_sinh']) ? date('d/m/Y', $value['customer_info']['ngay_sinh']) : "";
				$data_1['goi_bao_hiem'] = !empty($value['goi_bh']) ? $value['goi_bh'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['fee']) ? $value['fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? date('d/m/Y', strtotime((int)$value['NGAY_HL']))  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? date('d/m/Y', strtotime((int)$value['NGAY_KT'])) : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_vbi_sxh,$data_1);
			}
		}


		//vbi_tnds
		$list_vbi_tnds = $this->vbi_tnds_model->getAll_excel($condition);
		if (!empty($list_vbi_tnds)){
			foreach ($list_vbi_tnds as $value){
				$data_1['ma_hop_dong'] = "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['vbi_tnds']['so_hd']) ? $value['vbi_tnds']['so_hd'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = "";
				$data_1['goi_bao_hiem'] = !empty($value['code']) ? $value['code'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['fee']) ? $value['fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? $value['NGAY_HL']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? $value['NGAY_KT'] : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_vbi_tnds,$data_1);
			}
		}

		//vbi_utv
		$list_vbi_utv = $this->vbi_utv_model->getAll_excel($condition);
		if (!empty($list_vbi_utv)){
			foreach ($list_vbi_utv as $value){
				$data_1['ma_hop_dong'] = "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['vbi_utv']['so_hd']) ? $value['vbi_utv']['so_hd'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['customer_info']['ngay_sinh']) ? date('d/m/Y', $value['customer_info']['ngay_sinh']) : "";
				$data_1['goi_bao_hiem'] = !empty($value['goi_bh']) ? $value['goi_bh'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['fee']) ? $value['fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? date('d/m/Y', strtotime((int)$value['NGAY_HL']))  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? date('d/m/Y', strtotime((int)$value['NGAY_KT'])) : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_vbi_utv,$data_1);
			}
		}

		//pti_vta_bn
		$list_pti_vta_bn = $this->pti_vta_bn_model->getAll_excel($condition);
		if (!empty($list_pti_vta_bn)){
			foreach ($list_pti_vta_bn as $value){

				if ($value->type_pti == "HD"){
					$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
					$data_1['ma_hop_dong_bao_hiem'] = !empty($value['pti_code']) ? $value['pti_code'] : "";
					$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['contract_info']['customer_infor']['customer_name']) ? $value['contract_info']['customer_infor']['customer_name'] : "";
					$data_1['so_dien_thoai'] = !empty($value['contract_info']['customer_infor']['customer_phone_number']) ? $value['contract_info']['customer_infor']['customer_phone_number'] : "";
					$data_1['email'] = !empty($value['contract_info']['customer_infor']['customer_email']) ? $value['contract_info']['customer_infor']['customer_email'] : "";
					$data_1['ngay_thang_nam_sinh'] = !empty($value['contract_info']['customer_infor']['customer_BOD']) ? $value['contract_info']['customer_infor']['customer_BOD'] : "";
					$data_1['goi_bao_hiem'] = !empty($value['code_pti_vta']) ? $value['code_pti_vta'] : "";
					$data_1['phi_bao_hiem'] = !empty($value['contract_info']['loan_infor']['bao_hiem_pti_vta']['price_pti_vta']) ? $value['contract_info']['loan_infor']['bao_hiem_pti_vta']['price_pti_vta'] : "";
					$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
					$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? date('d/m/Y', strtotime($value['NGAY_HL']))  : "";
					$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? date('d/m/Y', strtotime($value['NGAY_KT']))  : "" ;
					$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
					$data_1['nguoi_tao'] = !empty($value['contract_info']['created_by']) ? $value['contract_info']['created_by'] : "" ;
					array_push($data_pti_vta_bn,$data_1);
				} else {
					$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
					$data_1['ma_hop_dong_bao_hiem'] = !empty($value['request']['so_hd']) ? $value['request']['so_hd'] : "";
					$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['request']['btendn']) ? $value['request']['btendn'] : "";
					$data_1['so_dien_thoai'] = !empty($value['request']['bphonedn']) ? $value['request']['bphonedn'] : "";
					$data_1['email'] = !empty($value['request']['bemaildn']) ? $value['request']['bemaildn'] : "";
					$data_1['ngay_thang_nam_sinh'] = !empty($value['request']['ngay_sinh']) ? date('d/m/Y', strtotime($value['request']['ngay_sinh'])) : "";
					$data_1['goi_bao_hiem'] = !empty($value['code_pti_vta']) ? $value['code_pti_vta'] : "";
					$data_1['phi_bao_hiem'] = !empty($value['request']['phi_bh']) ? $value['request']['phi_bh'] : "";
					$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
					$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? date('d/m/Y', strtotime($value['NGAY_HL']))  : "";
					$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? date('d/m/Y', strtotime($value['NGAY_KT']))  : "" ;
					$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
					$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
					array_push($data_pti_vta_bn,$data_1);
				}


			}
		}

		//mic_tnds
		$list_mic_tnds = $this->mic_tnds_model->getAll_excel($condition);
		if (!empty($list_mic_tnds)){
			foreach ($list_mic_tnds as $value){
				$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['mic_code']) ? $value['mic_code'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['customer_info']['birthday']) ? date('d/m/Y', ((int)$value['customer_info']['birthday'])) : "";
				$data_1['goi_bao_hiem'] = !empty($value['type_mic']) ? $value['type_mic'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['mic_fee']) ? $value['mic_fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? $value['NGAY_HL']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? $value['NGAY_KT'] : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_mic_tnds, $data_1);
			}
		}

		//gic_plt_bn
		$list_gic_plt_bn = $this->gic_plt_bn_model->getAll_excel($condition);
		if (!empty($list_gic_plt_bn)){
			foreach ($list_gic_plt_bn as $value){
				$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['mic_code']) ? $value['mic_code'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['customer_info']['birthday']) ? date('d/m/Y', ((int)$value['customer_info']['birthday'])) : "";
				$data_1['goi_bao_hiem'] = !empty($value['type_mic']) ? $value['type_mic'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['mic_fee']) ? $value['mic_fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? $value['NGAY_HL']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? $value['NGAY_KT'] : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_gic_plt_bn, $data_1);
			}
		}

		//contract_tnds_model
		$list_contract_tnds = $this->contract_tnds_model->getAll_excel($condition);
		if (!empty($list_contract_tnds)){
			foreach ($list_contract_tnds as $value){

				$data_1['ma_hop_dong'] = !empty($value['contract_info']['code_contract_disbursement']) ? $value['contract_info']['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['data']['response']['SO_ID']) ? $value['data']['response']['SO_ID'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['contract_info']['customer_infor']['customer_name']) ? $value['contract_info']['customer_infor']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['contract_info']['customer_infor']['customer_phone_number']) ? $value['contract_info']['customer_infor']['customer_phone_number'] : "";
				$data_1['email'] = !empty($value['contract_info']['customer_infor']['customer_email']) ? $value['contract_info']['customer_infor']['customer_email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['contract_info']['customer_infor']['customer_BOD']) ? date('d/m/Y', strtotime($value['contract_info']['customer_infor']['customer_BOD'])) : "";
				$data_1['goi_bao_hiem'] = "Bảo hiểm TNDS";
				$data_1['phi_bao_hiem'] = !empty($value['data']['response']['PHI']) ? $value['data']['response']['PHI'] : "";
				$data_1['phong_giao_dich'] = !empty($value['store']['name']) ? $value['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['data']['NGAY_HL']) ? $value['data']['NGAY_HL']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['data']['NGAY_KT']) ? $value['data']['NGAY_KT'] : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_contract_tnds, $data_1);
			}
		}

		//vbi
		$list_vbi = $this->vbi_model->getAll_excel($condition);

		if (!empty($list_vbi)){
			foreach ($list_vbi as $value){

				$data_1['ma_hop_dong'] = !empty($value['contract_info']['code_contract_disbursement']) ? $value['contract_info']['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['vbi_sxh']['so_hd']) ? $value['vbi_sxh']['so_hd'] : $value['vbi_utv']['so_hd'];
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['customer_info']['customer_name']) ? $value['customer_info']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['customer_info']['customer_phone']) ? $value['customer_info']['customer_phone'] : "";
				$data_1['email'] = !empty($value['customer_info']['email']) ? $value['customer_info']['email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['customer_info']['ngay_sinh']) ? date('d/m/Y', $value['customer_info']['ngay_sinh']) : "";
				$data_1['goi_bao_hiem'] = !empty($value['goi_bh']) ? $value['goi_bh'] : "";
				$data_1['phi_bao_hiem'] = !empty($value['fee']) ? $value['fee'] : "";
				$data_1['phong_giao_dich'] = !empty($value['contract_info']['store']['name']) ? $value['contract_info']['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['NGAY_HL']) ? date('d/m/Y', strtotime($value['NGAY_HL']))  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['NGAY_KT']) ? date('d/m/Y', strtotime($value['NGAY_KT']))  : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_vbi, $data_1);
			}
		}

		//gic_easy
		$list_gic_easy = $this->gic_easy_model->getAll_excel($condition);
		if (!empty($list_gic_easy)){
			foreach ($list_gic_easy as $value){

				$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['gic_code']) ? $value['gic_code'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['contract_info']['customer_infor']['customer_name']) ? $value['contract_info']['customer_infor']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['contract_info']['customer_infor']['customer_phone_number']) ? $value['contract_info']['customer_infor']['customer_phone_number'] : "";
				$data_1['email'] = !empty($value['contract_info']['customer_infor']['customer_email']) ? $value['contract_info']['customer_infor']['customer_email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['contract_info']['customer_infor']['customer_BOD']) ? date('d/m/Y', strtotime($value['contract_info']['customer_infor']['customer_BOD'])) : "";
				$data_1['goi_bao_hiem'] = "Gic_easy";
				$data_1['phi_bao_hiem'] = !empty($value['contract_info']['loan_infor']['amount_GIC_easy']) ? $value['contract_info']['loan_infor']['amount_GIC_easy'] : "";
				$data_1['phong_giao_dich'] = !empty($value['contract_info']['store']['name']) ? $value['contract_info']['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiem']) ? $value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiem']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiemDen']) ? $value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiemDen']  : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['contract_info']['created_by']) ? $value['contract_info']['created_by'] : "" ;
				array_push($data_gic_easy, $data_1);
			}
		}

		//gic_plt
		$gic_plt_easy = $this->gic_plt_model->getAll_excel($condition);
		if (!empty($gic_plt_easy)){
			foreach ($gic_plt_easy as $value){

				$data_1['ma_hop_dong'] = !empty($value['code_contract_disbursement']) ? $value['code_contract_disbursement'] : "";
				$data_1['ma_hop_dong_bao_hiem'] = !empty($value['gic_code']) ? $value['gic_code'] : "";
				$data_1['ten_nguoi_duoc_bao_hiem'] = !empty($value['contract_info']['customer_infor']['customer_name']) ? $value['contract_info']['customer_infor']['customer_name'] : "";
				$data_1['so_dien_thoai'] = !empty($value['contract_info']['customer_infor']['customer_phone_number']) ? $value['contract_info']['customer_infor']['customer_phone_number'] : "";
				$data_1['email'] = !empty($value['contract_info']['customer_infor']['customer_email']) ? $value['contract_info']['customer_infor']['customer_email'] : "";
				$data_1['ngay_thang_nam_sinh'] = !empty($value['contract_info']['customer_infor']['customer_BOD']) ? date('d/m/Y', strtotime($value['contract_info']['customer_infor']['customer_BOD'])) : "";
				$data_1['goi_bao_hiem'] = "Gic_plt";
				$data_1['phi_bao_hiem'] = !empty($value['contract_info']['loan_infor']['amount_GIC_easy']) ? $value['contract_info']['loan_infor']['amount_GIC_easy'] : "";
				$data_1['phong_giao_dich'] = !empty($value['contract_info']['store']['name']) ? $value['contract_info']['store']['name'] : "";
				$data_1['ngay_hieu_luc'] = !empty($value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiem']) ? $value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiem']  : "";
				$data_1['ngay_ket_thuc'] = !empty($value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiemDen']) ? $value['gic_info']['noiDungBaoHiem_NgayHieuLucBaoHiemDen']  : "" ;
				$data_1['ngay_tao'] = !empty($value['created_at']) ? date('d/m/Y H:i:s', $value['created_at']) : "" ;
				$data_1['nguoi_tao'] = !empty($value['created_by']) ? $value['created_by'] : "" ;
				array_push($data_gic_plt_easy, $data_1);
			}
		}


		$data_baohiem = array_merge($data_vbi_sxh, $data_vbi_tnds, $data_vbi_utv, $data_pti_vta_bn, $data_mic_tnds, $data_gic_plt_bn, $data_contract_tnds, $data_vbi, $data_gic_easy, $data_gic_plt_easy);

		if($data_baohiem){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data_baohiem
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function exportDashboard_asm_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$stores = $this->getStores_list($this->id);
		$stores_for = [];
		$code_area = [];
		if (!empty($stores)){
			foreach ($stores as $value){
				$store_name = $this->store_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId((string)$value)));
				if (!empty($store_name)){
					$stores_for += [$store_name['name']=>$value];
				}
			}
		}

		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();
		$debt_store = new Debt_store_model();
		$data = [];

		foreach ($stores_for as $key => $item){

//			$stores = $this->getStores_list_detail($item);
			$stores = [$item];
			$condition['store.id']=array('$in'=>$stores);

			$data[$key]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai ;

			$total_du_no_trong_han_t10_2 = $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$key]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//doanh_so_bao_hiem
			$data[$key]['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');

			//Tổng tiền giải ngân
			$data[$key]['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

			//Dư nợ quản lý
			$data[$key]['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 hiện tại
			$data[$key]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 kỳ trước
			$data[$key]['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function exportDashboard_lead_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();

			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$stores = $this->getStores_list($this->id);

		$stores_for = [];
		$user = [];

		$list_user = $this->getUserbyStores($stores);

		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();

		$debt_user = new Debt_user_model();

		$data = [];

		foreach ($list_user as $key => $item){

//			$stores = $this->getStores_list_detail($item);
//			$stores = [$item];
//			$condition['store.id']=array('$in'=>$stores);

			$created_by = $item;
			$data[$key]['created_by'] = $created_by;
			$data[$key]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['created_by'=>$created_by,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$created_by,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$created_by,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai ;


			$total_du_no_trong_han_t10_2 = $debt_user->sum_where_total_mongo_read(['user'=>$created_by, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$key]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//doanh_so_bao_hiem
			$data[$key]['total_doanh_so_bao_hiem']=$rkcum->sum_where_total_mongo_read(['user'=>$created_by,'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');

			//Tổng tiền giải ngân
			$data[$key]['total_so_tien_vay_old']= $contract->sum_where_total_mongo_read(['created_by'=>$created_by,'disbursement_date'=>$condition_search_old,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

			//Dư nợ quản lý
			$data[$key]['total_du_no_dang_cho_vay_old']=$contract->sum_where_total_mongo_read(['created_by'=>$created_by,'disbursement_date'=>$condition_search_old,'status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 hiện tại
			$data[$key]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$created_by,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

			//Dư nợ trong hạn T+10 kỳ trước
			$data[$key]['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total_mongo_read(['user'=>$created_by, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	private function getGroupRole_gdv()
	{
		$groupRoles = $this->group_role_model->find_where(array("status" => "active", 'slug' => 'giao-dich-vien'));

		$arr = array();
		foreach ($groupRoles as $groupRole) {
			if (!empty($groupRole['users'])) {

				foreach ($groupRole['users'] as $value) {
					foreach ($value as $key => $item) {

						foreach ($item as $v){
//							array_push($arr, $key);
							$arr += ["$key" => $v];
						}
					}

				}
			}
		}
		return array_unique($arr);
	}


	public function exportKpiCvkd_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();
		$kpigdv = new Kpi_gdv_model();

		$debt_user = new Debt_user_model();

		$data = [];

		$list_user_pgd = $this->getGroupRole_gdv();

		$count = 0;
		$kpi_du_no=0;
		$kpi_bao_hiem=0;
		foreach ($list_user_pgd as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;
			$list_store_name = [];
//			$stores = $this->getStores_list($key);

//			if (!empty($stores)){
//				foreach ($stores as $s){
//
//					$store_name = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($s)));
//
//					array_push($list_store_name,$store_name['name']);
//
//				}
//			}

			$data[$count]['created_by'] = $item;
//			$data[$count]['store_name'] = implode(", ",$list_store_name);

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;


			$total_du_no_trong_han_t10_2 = $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_CT');
			$du_no_tt = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_TT');

			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcum->sum_where_total_mongo_read(['user'=>$item,'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');


			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['created_by'=>$item,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$data[$count]['total_tien_hoa_hong']=$rkcum->sum_where_total_mongo_read(['user'=>$item, 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'created_by'=>$item],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');



			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}


	public function exportKpiPGD_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}


			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();
		$kpigdv = new Kpi_gdv_model();
		$kpipgd = new Kpi_pgd_model();

		$debt_store = new Debt_store_model();

		$data = [];

		$stores = $this->store_model->find_where(["status" => "active"]);
		$stores_for = [];

		if (!empty($stores)){
			foreach ($stores as $value){
				$store_name = $this->store_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId((string)$value['_id'])));
				if (!empty($store_name)){
					$stores_for += [$store_name['name']=>$value];
				}
			}
		}

		$count = 0;
		$kpi_du_no=0;
		$kpi_bao_hiem=0;
		foreach ($stores_for as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;

			$stores = [(string)$item['_id']];

			$data[$count]['store_name'] = $item['name'];

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;


			$total_du_no_trong_han_t10_2 = $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$giai_ngan_CT');
			$du_no_tt = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$bao_hiem_TT');

			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');


			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$total_tien_hoa_hong = 0;
			$total_tien_hoa_hong = $rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

			if (isset($data[$count]['kpi'])){
				if ($data[$count]['kpi'] < 50){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.15;
				} elseif ($data[$count]['kpi'] >= 50 && $data[$count]['kpi'] < 80){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.2;
				} elseif ($data[$count]['kpi'] >= 80 && $data[$count]['kpi'] < 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.3;
				}  elseif ($data[$count]['kpi'] >= 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.4;
				}
			}

			$data[$count]['total_tien_hoa_hong'] = $total_tien_hoa_hong;

			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'store.id'=>array('$in'=> $stores)],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function exportKpiASM_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		$start_search = !empty( $data['start']) ? $data['start'] : '2019-11-01';

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_search_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim(date('Y-m-d')).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc= 12;
				$year = $date['year'] - 1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);
			$condition_thang_nay = array(
				'$gte' => strtotime(trim($start_thang_nay).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_nay).' 23:59:59')
			);
		}

		$rk = new Report_kpi_model();
		$rku = new Report_kpi_user_model();
		$rktp = new Report_kpi_top_pgd_model();
		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();
		$rkcum = new Report_kpi_commission_user_model();
		$kpigdv = new Kpi_gdv_model();
		$kpipgd = new Kpi_pgd_model();
		$kpi_asm = new Kpi_area_model();

		$debt_store = new Debt_store_model();

		$data = [];

		$area = $this->area_model->find_where(["status" => "active"]);
		$code_area = [];
		if (!empty($area)){
			foreach ($area as $value){
				if ($value['code'] == "Priority" || $value['code'] == "NextPay"){
					continue;
				}

				$code_area += [(string)$value['_id']=>$value['code']];
			}
		}


		$count = 0;
		$kpi_du_no=0;
		$kpi_bao_hiem=0;

		foreach ($code_area as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;

			$stores = $this->getStores_list_detail($item);

			$data[$count]['store_name'] = $item;

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;


			$total_du_no_trong_han_t10_2 = $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpi_asm->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'area.id'=> $key ],'$giai_ngan_CT');
			$du_no_tt = $kpi_asm->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'area.id'=>$key],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpi_asm->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'area.id'=>$key],'$bao_hiem_TT');


			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');


			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpi_asm->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'area.id'=>$key],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$total_tien_hoa_hong = 0;
			$total_tien_hoa_hong=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');


			if (isset($data[$count]['kpi'])){
				if ($data[$count]['kpi'] < 50){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0;
				} elseif ($data[$count]['kpi'] >= 50 && $data[$count]['kpi'] < 80){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.03;
				} elseif ($data[$count]['kpi'] >= 80 && $data[$count]['kpi'] < 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.05;
				}  elseif ($data[$count]['kpi'] >= 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.07;
				}

				if ($data[$count]['kpi'] >= 80 && $data[$count]['kpi'] < 90){
					$total_tien_hoa_hong = $total_tien_hoa_hong + 1500000;
				} elseif ($data[$count]['kpi'] >= 90 && $data[$count]['kpi'] < 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong + 2250000;
				}  elseif ($data[$count]['kpi'] >= 100 && $data[$count]['kpi'] < 120){
					$total_tien_hoa_hong = $total_tien_hoa_hong + 3000000;
				} elseif ($data[$count]['kpi'] >= 120){
					$total_tien_hoa_hong = $total_tien_hoa_hong + 5000000;
				}
			}
			$data[$count]['total_tien_hoa_hong'] = $total_tien_hoa_hong;



			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'store.id'=>array('$in'=> $stores)],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function cron_du_no_tang_net_user_post(){

		$start_old = '2018-11-01';

		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');

		$condition_old = array(
			'$gte' => strtotime(trim($start_old).' 00:00:00'),
			'$lte' => strtotime(trim($end).' 23:59:59')
		);

		$contract = new Contract_model();
		$debt_user = new Debt_user_model();

		$data_report = [];

		$list_user_pgd = $this->getGroupRole_gdv();


		if (!empty($list_user_pgd)){
			foreach ($list_user_pgd as $value){
				$data_report['month']=date('m');
				$data_report['year']=date('Y');
				$data_report['user']=$value;

				$data_report['total_du_no_trong_han_t10_old']= $contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'created_by'=>$value],'$debt.tong_tien_goc_con');

				$data_report['created_at'] = strtotime(date('Y-m-20'));


				$debt_user->insert($data_report);
			}
		}
		echo "cron ok";
	}

	public function cron_du_no_store_post(){

		$start_old = '2018-11-01';

		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');

		$condition_old = array(
			'$gte' => strtotime(trim($start_old).' 00:00:00'),
			'$lte' => strtotime(trim($end).' 23:59:59')
		);

		$contract = new Contract_model();
		$debt_du_no = new Debt_du_no_model();

		$data_report = [];

		$data_report['month']=date('m');
		$data_report['year']=date('Y');

		$data_report['total_du_no_trong_han_t10_old']= $contract->sum_where_total(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

		$data_report['created_at'] = strtotime(date('Y-m-20'));
//		$data_report['created_at'] = strtotime(date('2022-02-20'));

		$debt_du_no->insert($data_report);


		echo "cron ok";
	}

	public function cron_du_no_tang_net_store_post(){

		$start_old = '2018-11-01';

		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');

		$condition_old = array(
			'$gte' => strtotime(trim($start_old).' 00:00:00'),
			'$lte' => strtotime(trim($end).' 23:59:59')
		);

		$contract = new Contract_model();
		$debt_store = new Debt_store_model();

		$stores = $this->store_model->find_where_in('status', ['active']);

		if (!empty($stores)){
			foreach ($stores as $value){
				$data_report['month']=date('m');
				$data_report['year']=date('Y');
				$data_report['store']=array('id'=>(string)$value['_id'],'name'=>$value['name']);

				$stores = [(string)$value['_id']];

				$data_report['total_du_no_trong_han_t10_old']= $contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');

				$data_report['created_at'] = strtotime(date('Y-m-20'));

				$debt_store->insert($data_report);
			}
		}

		echo "cron ok";


	}


	public function exportAllDuNo_post(){

//		$start = '2019-11-01';
//		$end = '2021-9-30';
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty($data['end']) ?  $data['end'] : date('Y-m-d');

		if (!empty($start)) {
			$condition['start'] = strtotime(trim($start) . ' 00:00:00');
		}
		if (!empty($end)) {
			$condition['end'] = strtotime(trim($end) . ' 23:59:59');
		}

		$contract = $this->contract_model->exportAllContract($condition);

		if($contract){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $contract
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function cron_duno_gh_cc_post(){

    	$contractData = $this->contract_model->find_cron_du_no();

    	if (!empty($contractData)){
    		foreach ($contractData as $value){
    			if (!empty($value['code_contract_child_gh'])){
    				$code_contract_disbursement = $value['code_contract_child_gh'][count($value['code_contract_child_gh'])];
    				if (!empty($code_contract_disbursement)){
    					$contract = $this->contract_model->findOne_code(['code_contract_disbursement' => $code_contract_disbursement]);
						if (!empty($contract) && $contract['status'] == 19){
							$this->contract_model->update(array("_id" => new \MongoDB\BSON\ObjectId((string)$value['_id'])), ["tat_toan_gh" => 1]);
						}
					}
				}
			}
		}

		echo "cron_oke";

	}

	public function view_payroll_cvkd_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);

		}

		$contract = new Contract_model();

		$rkcum = new Report_kpi_commission_user_model();
		$kpigdv = new Kpi_gdv_model();

		$debt_user = new Debt_user_model();

		$data = [];

		$list_user_pgd = [$this->uemail];

		$count = 0;

		foreach ($list_user_pgd as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;
			$list_store_name = [];

			$data[$count]['created_by'] = $item;


			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;


			$total_du_no_trong_han_t10_2 = $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_CT');
			$du_no_tt = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_TT');

			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcum->sum_where_total_mongo_read(['user'=>$item,'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');


			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['created_by'=>$item,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$data[$count]['total_tien_hoa_hong']=$rkcum->sum_where_total_mongo_read(['user'=>$item, 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'created_by'=>$item],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');



			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}

	public function view_payroll_store_post(){
		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');

		$condition_lead = array();

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}


			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$start_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-01") : date('Y-m-01');
			$end_thang_nay = !empty($date['mon']) ? date("Y-$month_thang_nay-d") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);

		}

		$contract = new Contract_model();
		$rkcpm = new Report_kpi_commission_pgd_model();

		$kpipgd = new Kpi_pgd_model();

		$debt_store = new Debt_store_model();

		$data = [];

		$stores = $this->store_model->find_where(["status" => "active"]);

		$stores_check = $this->getStores_list($this->id);

		$stores_for = [];

		if (!empty($stores)){
			foreach ($stores as $value){
				if (in_array((string)$value['_id'],$stores_check)){
					$store_name = $this->store_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId((string)$value['_id'])));
					if (!empty($store_name)){
						$stores_for += [$store_name['name']=>$value];
					}
				}
			}
		}

		$count = 0;

		foreach ($stores_for as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;

			$stores = [(string)$item['_id']];

			$data[$count]['store_name'] = $item['name'];

			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'store.id'=>array('$in'=> $stores),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;

			$total_du_no_trong_han_t10_2 = $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$giai_ngan_CT');
			$du_no_tt = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$bao_hiem_TT');

			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');

			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpipgd->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'store.id'=>array('$in'=> $stores)],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores),'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$total_tien_hoa_hong = $rkcpm->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

			if (isset($data[$count]['kpi'])){
				if ($data[$count]['kpi'] < 50){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.15;
				} elseif ($data[$count]['kpi'] >= 50 && $data[$count]['kpi'] < 80){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.2;
				} elseif ($data[$count]['kpi'] >= 80 && $data[$count]['kpi'] < 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.3;
				}  elseif ($data[$count]['kpi'] >= 100){
					$total_tien_hoa_hong = $total_tien_hoa_hong * 0.4;
				}
			}

			$data[$count]['total_tien_hoa_hong'] = $total_tien_hoa_hong;

			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'store.id'=>array('$in'=> $stores)],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_store->sum_where_total_mongo_read(['store.id'=>array('$in'=> $stores), 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
	}

	public function view_payroll_cvkd_list_post(){

		$data = $this->input->post();
		$start_old = '2019-11-01';
		$start = !empty( $data['start']) ? $data['start'] : date('Y-m-01');
		$end = !empty( $data['end']) ?  $data['end'] : date('Y-m-d');
		$condition = array();
		$condition_lead = array();

		if (!empty($start) && !empty($end)) {
			$condition_old = array(
				'$gte' => strtotime(trim($start_old).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);
			$condition_lead = array(
				'$gte' => strtotime(trim($start).' 00:00:00'),
				'$lte' => strtotime(trim($end).' 23:59:59')
			);

			//Dư nợ quá hạn T+10 tháng trước
			$date = getdate();
			$month = $date['mon'];
			$month_thang_nay = $date['mon']-1;
			$month_thang_truoc = $date['mon'] - 1;
			$year = $date['year'];
			if ($date['mon'] == 1){
				$month_thang_nay = 12;
				$month_thang_truoc=12;
				$year = $date['year'] -1;
			}
			if ($date['mon'] < 10){
				$month = "0". $month;
			}

			$start_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-01") : date('Y-m-01');
			$end_thang_truoc = !empty($month_thang_truoc) ? date("$year-$month_thang_truoc-t") : date('Y-m-d');

			$condition_thang_truoc = array(
				'$gte' => strtotime(trim($start_thang_truoc).' 00:00:00'),
				'$lte' => strtotime(trim($end_thang_truoc).' 23:59:59')
			);

		}

		$contract = new Contract_model();

		$rkcum = new Report_kpi_commission_user_model();
		$kpigdv = new Kpi_gdv_model();

		$debt_user = new Debt_user_model();

		$data = [];
		$list_user_pgd = [];
		$stores_check = $this->getStores_list($this->id);

		foreach ($stores_check as $item){
			$user = $this->get_user_store_post($item);

			foreach ($user as $value){
				array_push($list_user_pgd,$value);
			}
		}

		$count = 0;

		foreach (array_unique($list_user_pgd) as $key => $item){
			$kpi_du_no=0;
			$kpi_bao_hiem=0;
			$list_store_name = [];

			$data[$count]['created_by'] = $item;


			//du_no_tang_net
			$total_du_no_trong_han_t10_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.tong_tien_goc_con');
			$total_du_no_oto_va_nha_dat_hien_tai=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'created_by'=>$item,'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false)],'$debt.kpi_tong_tien_goc_con');
			$total_du_no_trong_han_t10_1 = $total_du_no_trong_han_t10_hien_tai;


			$total_du_no_trong_han_t10_2 = $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');

			$data[$count]['total_du_no_trong_han_t10'] = $total_du_no_trong_han_t10_1 - $total_du_no_trong_han_t10_2;

			//Chỉ tiêu dư nợ tăng net
			$data[$count]['chi_tieu_du_no_tang_net'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_CT');
			$du_no_tt = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$giai_ngan_TT');
			$tt_bao_hiem  = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_TT');

			//doanh_so_bao_hiem
			$data[$count]['total_doanh_so_bao_hiem']=$rkcum->sum_where_total_mongo_read(['user'=>$item,'san_pham' => 'BH', 'created_at' => $condition_lead],'$commision.doanh_so');


			//chỉ tiêu bảo hiểm
			$data[$count]['chi_tieu_bao_hiem'] = $kpigdv->sum_where_total_mongo_read(['month' => "$month", 'year'=>date("Y",strtotime(trim($start).' 00:00:00')) ,'email_gdv'=>$item],'$bao_hiem_CT');

			//Tiền giải ngân mới trong tháng
			$data[$count]['total_so_tien_vay'] =  $contract->sum_where_total_mongo_read(['created_by'=>$item,'disbursement_date'=>$condition_lead,'code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34])],array('$toLong' => '$loan_infor.amount_money'));

			//Kpi
			if (!empty($data[$count]['chi_tieu_du_no_tang_net']) && $data[$count]['chi_tieu_du_no_tang_net'] != 0){
				$kpi_du_no = round(($data[$count]['total_du_no_trong_han_t10'] / $data[$count]['chi_tieu_du_no_tang_net']) * $du_no_tt);
			}
			if (!empty($data[$count]['chi_tieu_bao_hiem']) && $data[$count]['chi_tieu_bao_hiem'] != 0){
				$kpi_bao_hiem = round(($data[$count]['total_doanh_so_bao_hiem'] / $data[$count]['chi_tieu_bao_hiem']) * $tt_bao_hiem);
			}
			if ($kpi_du_no > 84){
				$kpi_du_no = 84;
			}
			if ($kpi_bao_hiem > 36){
				$kpi_bao_hiem = 36;
			}

			$data[$count]['kpi'] = $kpi_du_no + $kpi_bao_hiem;

			//Tiền hoa hồng
			$data[$count]['total_tien_hoa_hong']=$rkcum->sum_where_total_mongo_read(['user'=>$item, 'created_at' => $condition_lead],'$commision.tien_hoa_hong');

			//dư nợ trong hạn T+10
			$data[$count]['total_du_no_trong_han_t10_old']=$contract->sum_where_total_mongo_read(['status' => array('$in' => list_array_trang_thai_dang_vay_gh_cc()),'debt.so_ngay_cham_tra'=>['$lte'=>10],'disbursement_date'=>$condition_old,'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'code_contract_parent_cc' => array('$exists' => false),'created_by'=>$item],'$debt.tong_tien_goc_con');
			$data[$count]['total_du_no_trong_han_t10_thang_truoc']= $debt_user->sum_where_total_mongo_read(['user'=> $item, 'created_at' => $condition_thang_truoc],'$total_du_no_trong_han_t10_old');



			$count++;
		}

		if($data){
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'data' =>  $data
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

	}
	public function get_user_store_post($store_id)
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		$i = 0;
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && !empty($role['stores'])) {
				$data[$i]['users'] = $role['users'];
				$data[$i]['stores'] = $role['stores'];
				$i++;
			}
		}
		foreach ($data as $da) {
			foreach ($da['stores'] as $d) {
				$storeId = [];
				foreach ($d as $k => $v) {
					array_push($storeId, $k);
				}
				if (in_array($store_id, $storeId) == true) {
					if (count($da['stores']) > 1) {
						continue;
					}
					$user_id = [];
					foreach ($da['users'] as $ds) {
						foreach ($ds as $k => $v) {
							foreach ($v as $e){
								array_push($user_id, $e);

							}
						}
					}
				}
			}
		}
		return $user_id;
	}

	public function view_homepage_tienngay_post(){

		$data = [];
		$contract = new Contract_model();

		$data['total_giao_dich_thanh_cong'] = $contract->count(['code_contract_parent_cc' => array('$exists' => false), 'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])]);
		$data['total_so_tien_vay_old']= $contract->sum_where_total(['code_contract_parent_cc' => array('$exists' => false),'code_contract_parent_gh' => array('$exists' => false),'tat_toan_gh' => array('$exists' => false),'status' => array('$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,41,42,19])],array('$toLong' => '$loan_infor.amount_money'));

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' =>  $data


		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;


	}


}

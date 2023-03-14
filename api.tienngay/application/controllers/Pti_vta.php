<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/BaoHiemPTI.php';
use Restserver\Libraries\REST_Controller;

class Pti_vta extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pti_vta_bn_model');
        $this->load->model('log_pti_model');
        $this->load->model("user_model");
        $this->load->model("role_model");
        $this->load->model("group_role_model");
        $this->load->model("store_model");
        $this->load->model("contract_model");
        $this->load->model("transaction_model");
        $this->load->model("pti_vta_fee_model");
         $this->load->helper('lead_helper');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $headers = $this->input->request_headers();
        $dataPost = $this->input->post();
        $this->flag_login = 1;
        $this->superadmin = false;
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
                if ($dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
                if ($dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1) {
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    $this->name = $this->info['full_name'];
                    $this->phone = $this->info['phone_number'];
                    // $this->ulang = $this->info['lang'];
                    $this->uemail = $this->info['email'];
                    $this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
                }
            }
        }
    }

    public function insert_pti_vta_post()
    {
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $data = $this->input->post();
        $fullname = !empty($data['fullname']) ? $data['fullname'] : '';
        $gender = !empty($data['gender']) ? $data['gender'] : '';
        $cmt = !empty($data['cmt']) ? $data['cmt'] : '';
        $relationship = !empty($data['relationship']) ? $data['relationship'] : '';
        $address = !empty($data['address']) ? $data['address'] : '';
        $id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
        $obj = !empty($data['obj']) ? $data['obj'] : '';
        $phone = !empty($data['phone']) ? $data['phone'] : '';
        $email = !empty($data['email']) ? $data['email'] : '';
        $birthday = !empty($data['birthday']) ? $data['birthday'] : '';
        $fullname_another = !empty($data['fullname_another']) ? $data['fullname_another'] : '';
        $birthday_another = !empty($data['birthday_another']) ? $data['birthday_another'] : '';
        $email_another = !empty($data['email_another']) ? $data['email_another'] : '';
        $cmt_another = !empty($data['cmt_another']) ? $data['cmt_another'] : '';
        $phone_another = !empty($data['phone_another']) ? $data['phone_another'] : '';
        $address_another = !empty($data['address_another']) ? $data['address_another'] : '';
        $gender_another = !empty($data['gender_another']) ? $data['gender_another'] : '';
        $sel_ql = !empty($data['sel_ql']) ? $data['sel_ql'] : '';
        $sel_year = !empty($data['sel_year']) ? $data['sel_year'] : '';
        $price = !empty($data['price']) ? $data['price'] : '';
        $code_fee = !empty($data['code_fee']) ? $data['code_fee'] : '';
        $ck1 = !empty($data['ck1']) ? $data['ck1'] : '';
        $ck2 = !empty($data['ck2']) ? $data['ck2'] : '';
        $ck3 = !empty($data['ck3']) ? $data['ck3'] : '';
        $checked_img = !empty($data['checked_img']) ? $data['checked_img'] : '';
         if( $ck1=="co" ||  $ck2=="co" ||  $ck3=="co")
        {
           $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không thành công , xác nhận thông tin không hợp lệ
                    "
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if($obj=="nguoithan")
        {
           if ($this->validateAge($birthday_another, 1, 69) == "FALSE")
           {
              $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tuổi người hưởng bảo hiểm từ 1 đến 70"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
           }
           if (!preg_match("/^[0-9]{10,10}$/", $phone_another)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại phải là 10 số!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
            }
            if (!preg_match("/^[0-9]{9,12}$/", $cmt_another) && $checked_img=="tren18") {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Chứng minh thư người hưởng trong khoảng 9 đến 12 số!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!filter_var($email_another, FILTER_VALIDATE_EMAIL)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Định dạng email người hưởng không hợp lệ!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
		if (empty($data['address_another'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Địa chỉ người hưởng không hợp lệ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
		 }
        }
        if($obj=="banthan")
        {
         if ($this->validateAge($birthday, 1, 69) == "FALSE")
           {
              $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Tuổi người hưởng bảo hiểm từ 1 đến 70"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
           }

        if (!preg_match("/^[0-9]{10,10}$/", $phone)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại phải là 10 số!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
        if (!preg_match("/^[0-9]{9,12}$/", $cmt) && $checked_img=="tren18") {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Chứng minh thư trong khoảng 9 đến 12 số!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Định dạng email không hợp lệ!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
       }
        if (empty($data['address'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Địa chỉ không hợp lệ!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        if (empty($id_pgd)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Phòng giao dịch không thể trống!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
         $ma_cty = $this->check_store_tcv_dong_bac($id_pgd);
        $data['company_code']=$ma_cty;
         if($obj=="banthan")
        {
        $ck_bh = $this->pti_vta_bn_model->findOne(['so_xac_minh' => $cmt,'pti_info.code'=>"000"]);
        }else{
         $ck_bh = $this->pti_vta_bn_model->findOne(['so_xac_minh' => $cmt_another,'pti_info.code'=>"000"]);   
        }
        if(!empty($ck_bh) && strtotime($ck_bh['NGAY_KT']) > time())
        {
             $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Bạn đã mua bảo hiểm trước đó. Hết thời gian hiệu lực bạn mới có thể mua bảo hiểm mới."
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $store = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($id_pgd)]);
        
    
            $NGAY_HL = date('d-m-Y', strtotime("+1 days"));
           
         $NGAY_KT = date('d-m-Y', strtotime($NGAY_HL . ' + 1 year'));
         $type_pti = "PTI_VTA";
        $code = $type_pti.'_' . date("dmY") . "_" . time();
        $pti_vta = $this->insert_pti_vta($data, $NGAY_HL,$type_pti,$code);
    
        if ($pti_vta->success != true) {
            $mes = $pti_vta->message;
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => $mes
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $pti = $pti_vta->data;
            $request = $pti_vta->request;
            $NGAY_KT = $pti_vta->NGAY_KT;
            $number_item = $pti_vta->number_item;
          
            $dt_pti = array(
                'type_pti' => $type_pti
                ,'code_pti_vta'=>$code
                , 'pti_code' => !empty($request->so_hd) ? $request->so_hd : ''
                , 'number_item' => (int)$number_item
                ,'NGAY_HL' => $NGAY_HL
                ,'NGAY_KT' => $NGAY_KT
                ,'price' => $price
                 ,'so_xac_minh' => !empty($request->so_cmt) ? $request->so_cmt : ''
                ,'customer_info' => [
                    'customer_name' =>!empty($request->ten) ? $request->ten : '',
                    'gender' =>!empty($request->gioi) ? $request->gioi : '',
                    'customer_phone' =>!empty($request->phone) ? $request->phone : '',
                    'card' =>!empty($request->so_cmt) ? $request->so_cmt : '',
                    'email' => !empty($request->email) ? $request->email : '',
                    'birthday' =>!empty($request->ngay_sinh) ? $request->ngay_sinh : ''
                ],'customer_another_info' => [
                    'customer_name_another' =>!empty($request->ten_nh) ? $request->ten_nh : '',
                    'customer_phone_another' =>!empty($request->phone_nh) ? $request->phone_nh : '',
                    'card_another' =>!empty($request->cmt_nh) ? $request->cmt_nh : '',
                    'email_another' => !empty($request->email_nh) ? $request->email_nh : '',
                    'birthday_another' =>!empty($request->ns_nh) ? $request->ns_nh : '',
                    'address_another' =>!empty($request->dia_chi_nh) ? $request->dia_chi_nh : '',
                    'gender_another' =>!empty($request->gioi_tinh_nh) ? $request->gioi_tinh_nh : ''
                ]
                ,'request'=>$request
                ,'pti_info'=>$pti
                 ,'data_origin'=>$data
                ,'store' => [
                    'id' => (string)$store['_id']
                    ,'name' => $store['name']
                ]
                , 'type_pti' => 'BN'
                ,'status' => 10
                ,'created_at' => $this->createdAt
                ,'created_by' => $this->uemail
                ,'company_code' => $ma_cty
            );
            $this->pti_vta_bn_model->insert($dt_pti);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Bán bảo hiểm thành công!"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

    }
public function check_hieu_luc_pti_vta_post()
{
      // $data = $this->input->post();
      // $obj="banthan";
      // $cmt=isset($data['cmt']) ? $data['cmt'] : "";
    
      //   $ck_bh = $this->pti_vta_bn_model->findOne(['so_xac_minh' => $cmt,'pti_info.code'=>"000"]);
      
      //   if(!empty($ck_bh) && strtotime($ck_bh['NGAY_KT']) > time())
      //   {
      //        $response = array(
      //           'status' => REST_Controller::HTTP_UNAUTHORIZED,
      //           'message' => "Bạn đã mua bảo hiểm PTI Vững Tâm An trước đó. Hết thời gian hiệu lực bạn mới có thể mua bảo hiểm mới."
      //       );
            
      //   }else{
      //       $response = array(
      //           'status' => REST_Controller::HTTP_OK,
      //           'message' => "Bạn có thể mua bảo hiểm"
      //       );
           
      //   }

        // 2022/01/17 Với khách hàng vẫn còn hiệu lực BH vẫn được mua mới
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Bạn có thể mua bảo hiểm"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
            return;
}
 private function initNumberItem_pti_vta()
    {
        $maxNumber = $this->pti_vta_bn_model->getMaxNumberItem();
        $maxNumberContract = !empty($maxNumber[0]['number_item']) ? (float)$maxNumber[0]['number_item'] + 1 : 1;
        
        return  $maxNumberContract;
    }
   function validateAge($birthday, $from = 18, $to = 59)
    {
        //$today = new DateTime(date("Y-m-d"));
		$today = Datetime::createFromFormat("Y-m-d", date("Y-m-d"));
//        $bday = new DateTime($birthday);
		$bday = DateTime::createFromFormat("Y-m-d", $birthday);
        $interval = $today->diff($bday);
        if (intval($interval->y) >= $from && intval($interval->y) <= $to) {
            return 'TRUE';
        } else {
            return 'FALSE';
        }
    }

    public function insert_pti_vta($data, $NGAY_HL,$type,$code,$number_item=null)
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $fullname = !empty($data['fullname']) ? $data['fullname'] : '';
        $gender = !empty($data['gender']) ? $data['gender'] : '';
        $cmt = !empty($data['cmt']) ? $data['cmt'] : '';
        $relationship = !empty($data['relationship']) ? $data['relationship'] : '';
        $address = !empty($data['address']) ? $data['address'] : '';
        $id_pgd = !empty($data['id_pgd']) ? $data['id_pgd'] : '';
        $obj = !empty($data['obj']) ? $data['obj'] : '';
        $phone = !empty($data['phone']) ? $data['phone'] : '';
        $email = !empty($data['email']) ? $data['email'] : '';
        $birthday = !empty($data['birthday']) ? $data['birthday'] : '';
        $fullname_another = !empty($data['fullname_another']) ? $data['fullname_another'] : '';
        $birthday_another = !empty($data['birthday_another']) ? $data['birthday_another'] : '';
        $email_another = !empty($data['email_another']) ? $data['email_another'] : '';
        $cmt_another = !empty($data['cmt_another']) ? $data['cmt_another'] : '';
        $phone_another = !empty($data['phone_another']) ? $data['phone_another'] : '';
        $address_another = !empty($data['address_another']) ? $data['address_another'] : '';
        $gender_another = !empty($data['gender_another']) ? $data['gender_another'] : '';
        $sel_ql = !empty($data['sel_ql']) ? $data['sel_ql'] : '';
        $sel_year = !empty($data['sel_year']) ? $data['sel_year'] : '';
        $price = !empty($data['price']) ? $this->convertNumber($data['price']) : 0;
        $code_fee = !empty($data['code_fee']) ? $data['code_fee'] : '';
        $btendn=$fullname; 
        $bdiachidn=$address; 
        $bemaildn=$email; 
        $bphonedn=$phone; 
        $bmathue=$cmt; 
        $NgayYeuCauBh = $NGAY_HL;

        $ngay_kt_old = $this->pti_vta_bn_model->findNgayKTByCCCD($cmt);
        
        if ($ngay_kt_old && strtotime($ngay_kt_old) > strtotime($NGAY_HL)) {
            $NgayHieuLucBaoHiem = date('d-m-Y', strtotime($ngay_kt_old . ' + 1 day'));
        } else {
            $NgayHieuLucBaoHiem = $NGAY_HL;
        }
        if ($sel_year == "1Y") {
            $so_thang_bh = 12;
        } else if ($sel_year == "6M") {
            $so_thang_bh = 6;
        } else if ($sel_year == "3M") {
            $so_thang_bh = 3;
        }

        $NgayHieuLucBaoHiemDen = date('d-m-Y', strtotime($NgayHieuLucBaoHiem . ' + '.$so_thang_bh.' month'));

        $customer_name = (!empty($data['ten_kh'])) ? $data['ten_kh'] : '';
        $customer_BOD = (!empty($data['ngay_sinh'])) ? date("Y-m-d",$data['ngay_sinh']) : '';
        $customer_identify = (!empty($data['cmt'])) ? $data['cmt'] : '';
        if ($number_item == null) {
            $number_item=$this->initNumberItem_pti_vta();
        }
        //$so_hd='TN'.str_pad((string)$number_item,7, '0', STR_PAD_LEFT).'/041/CN.1.14/'.date('Y'); 
        if($obj=='banthan')
        {
           $ten=$fullname;
           $ngay_sinh=$birthday;
           $email=$email;
           $phone=$phone;
           $so_cmt=$cmt;
        }else{
            $ten=$fullname_another;
            $ngay_sinh=$birthday_another;
            $email=$email_another;
            $phone=$phone_another;
            $so_cmt=$cmt_another;
        }
      
        $goi = "";
        if ($sel_ql == "G1") {
            $goi = "GOI1";
        } else if ($sel_ql == "G2") {
            $goi = "GOI2";
        } else if ($sel_ql == "G3") {
            $goi = "GOI3";
        }

        $dt_pti = array(
        //'so_hd' => $so_hd
         'btendn' => $btendn
        , 'bdiachidn' => $bdiachidn
        , 'bemaildn' => $bemaildn
        , 'bphonedn' => $bphonedn
        , 'bmathue' => $bmathue
        , 'quan_he' => $relationship
        , 'ten' => $ten
        , 'ngay_sinh' => date('d-m-Y', strtotime($birthday))
        , 'so_cmt' => $so_cmt
        , 'email' => $email
        , 'phone' => $phone
        , 'phi_bh' => $price
        , 'so_thang_bh' => $so_thang_bh
        , 'ngay_hl' => $NgayHieuLucBaoHiem
        , 'ngay_kt' => $NgayHieuLucBaoHiemDen
        , 'goi' => $goi
        , 'gioi' => ($gender == 1) ? "NAM" : 'NU'
        );
        // return  $province;
        $message = '';
        $baohiem = new BaoHiemPTI();
        $res = $baohiem->call_apiBN($dt_pti);
        $this->log_pti(json_encode($dt_pti), $res, 'HD', $code);
        log_message('info', 'PTI response0 ' . json_encode($pti));
        if (!empty($res)) {
            if ($res['status'] == 200) {
                log_message('info', 'PTI response1 ' . json_encode($pti));
                $dt_pti['ma_goi_bh_ap_dung'] = $code_fee;
                $dt_pti['so_hd'] = $res["data"]["so_hd"];
                $dt_re = array(
                    'message' => 'Thành công',
                    'data' => $res["data"],
                    'number_item' => $number_item,
                    'success' => true,
                    'request' => (object)$dt_pti,
                    'NGAY_KT' => $NgayHieuLucBaoHiemDen,
                    'NGAY_HL' => $NgayHieuLucBaoHiem
                );
                return (object)$dt_re;

            } else {
                $dt_re = array(
                    'message' => 'Không thành công',
                    'success' => false
                );
                return (object)$dt_re;
            }
        } else {

            $dt_re = array(
                'message' => "Kết nối đến PTI bị lỗi !",
                'success' => false
            );
            return (object)$dt_re;
        }
        
    }

   public function log_pti($request, $data, $code, $type)
    {

        $dataInser = array(
            "type" => $type,
            "code" => $code,
            "res_data" => $data,
            "request_data" => $request,
            "created_at" => $this->createdAt
        );
        $this->log_pti_model->insert($dataInser);
    }


    public function get_list_pti_vta_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $tab = !empty($data['tab']) ? $data['tab'] : 'pti_vta';
        $start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
        $end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';

        $customer_name = !empty($data['customer_name']) ? $data['customer_name'] : '';
        $customer_phone = !empty($data['customer_phone']) ? $this->security->xss_clean($data['customer_phone']) : '';
        $code = !empty($data['code']) ? $this->security->xss_clean($data['code']) : '';
        $code_pti_vta = !empty($data['code_pti_vta']) ? $this->security->xss_clean($data['code_pti_vta']) : '';
        $filter_by_store = !empty($data['filter_by_store']) ? $this->security->xss_clean($data['filter_by_store']) : '';
        $customer_cmt = !empty($data['customer_cmt']) ? $data['customer_cmt'] : "";
        $customer_name_another = !empty($data['customer_name_another']) ? $data['customer_name_another'] : "";
        $filter_by_status = !empty($data['filter_by_status']) ? $data['filter_by_status'] : "";
        $filter_by_sell_per = !empty($data['filter_by_sell_per']) ? $data['filter_by_sell_per'] : "";
        $type_pti = !empty($data['type_pti']) ? $data['type_pti'] : "";
        $condition = array();
        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start) . ' 00:00:00'),
                'end' => strtotime(trim($end) . ' 23:59:59')
            );
        }
        if (!empty($customer_name)) {
            $condition['customer_name'] = $customer_name;
        }
        if (!empty($customer_phone)) {
            $condition['customer_phone'] = $customer_phone;
        }
        if (!empty($code)) {
            $condition['code'] = $code;
        }
        if (!empty($filter_by_store)) {
            $condition['filter_by_store'] = $filter_by_store;
        }
        if (!empty($code_pti_vta)) {
            $condition['code_pti_vta'] = $code_pti_vta;
        }
        if (!empty($filter_by_sell_per)) {
            $condition['filter_by_sell_per'] = $filter_by_sell_per;
        }
        if (!empty($filter_by_status)) {
            $condition['filter_by_status'] = $filter_by_status;
        }
        if (!empty($customer_name_another)) {
            $condition['customer_name_another'] = $customer_name_another;
        }
        if (!empty($customer_cmt)) {
            $condition['customer_cmt'] = $customer_cmt;
        }
        if (!empty($type_pti)) {
            $condition['type_pti'] = $type_pti;
        }
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }

        if (!$all) {
            // neu khong thuoc cac quyen all thi lay ra id cua store cua user hien tai
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array(),
                    'groupRoles' => array(),
                    'total' => 0
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
        }
        if (empty($filter_by_store)) {
			if (in_array('quan-ly-khu-vuc', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('ke-toan', $groupRoles) || in_array('hoi-so', $groupRoles)) {
				$stores = $this->getStores($this->id);
				$condition['stores'] = $stores;
			}
        } else {
            $condition['filter_by_store'] = $filter_by_store;
        }
        $per_page = !empty($data['per_page']) ? $data['per_page'] : 20;
        $uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
        if ($tab == 'pti_vta') {
            $result = $this->pti_vta_bn_model->get_list_pti_vta($condition, $per_page, $uriSegment);
            $total =0;
            $total_not_send_yet =0;
            $total_sended =0;
            $total_money =0;
            $total_not_send_yet_money =0;
            $total_sended_money =0;
            $total_web_success =0;
            $total_web_wait =0;
            $total_web =0;
            $total_web_kt =0;
        
          $staticData=$this->pti_vta_bn_model->get_statistics($condition);
          foreach ($staticData as $key => $value) {
              $total++;
              $total_money +=(int)$value['price'];
              if($value['status']==1)
              {
                $total_sended++;
                $total_sended_money +=(int)$value['price'];
              }else{
               $total_not_send_yet++;
               $total_not_send_yet_money +=(int)$value['price'];
              }
              if($value['type_pti'] == "WEB") {
                $total_web++;
                if($value['status']==1){
                    $total_web_success++;
                }
                if($value['status']==10){
                    $total_web_wait++;
                }
                if($value['status']==2){
                    $total_web_kt++;
                }
              }
          }
        
        } else {
            $result = $this->transaction_model->list_transaction_pti_vta($condition, $per_page, $uriSegment);
            $total = $this->transaction_model->total_list_transaction_pti_vta($condition);
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'test' => $condition,
            'data' => $result,
            'total' => $total,
            'stores'=>$stores,
            'total_not_send_yet' => $total_not_send_yet,
            'total_sended' => $total_sended,
            'total_money' => $total_money,
            'total_not_send_yet_money' => $total_not_send_yet_money,
            'total_sended_money' => $total_sended_money,
            'message' => 'thanh cong',
            'condition' => $condition,
            'total_web' => $total_web,
            'total_web_success' => $total_web_success,
            'total_web_wait' => $total_web_wait,
            'total_web_kt' => $total_web_kt
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function statistical_pti_vta_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $start = !empty($data['start']) ? $this->security->xss_clean($data['start']) : '';
        $end = !empty($data['end']) ? $this->security->xss_clean($data['end']) : '';
        $stores = $this->store_model->find_where(['status' => 'active']);
        foreach ($stores as $store) {
            if (!empty($start) && !empty($end)) {
                $condition = array(
                    'start' => strtotime(trim($start) . ' 00:00:00'),
                    'end' => strtotime(trim($end) . ' 23:59:59')
                );
            }
            $condition['store'] = (string)$store['_id'];
            $data = $this->get_pti_store_post($condition);
            $store['price'] = $data['price'];
            $store['total_transaction'] = $data['total_transaction'];

        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $stores,
            'message' => "thành công!"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_pti_store_post($condition)
    {
        $data = [];
        $ptis = $this->pti_vta_bn_model->get_list_pti_store($condition);
        if (!empty($ptis)) {
            $sum_price = 0;
            foreach ($ptis as $pti) {
                $sum_price += $pti['price'];
            }
            $data['price'] = !empty($sum_price) ? ($sum_price) : 0;
            $data['total_transaction'] = count($ptis);
        }
        return $data;

    }

    

    public function get_store_by_user_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $store = $this->role_model->get_store_user((string)$this->id);
        $data = [];
        foreach ($store as $value) {
            $data[] = $this->store_model->findOne(['_id' => new  MongoDB\BSON\ObjectId($value['id'])]);
        }
        if (count($data) > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $data,
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }

    public function detail_pti_vta_post()
    {
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : '';
        $data = $this->pti_vta_bn_model->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $data,
            'message' => "thành công!"
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

    public function get_pti_vta_accounting_transfe_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = [];
        $data = $this->input->post();
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        if (in_array('ke-toan', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('quan-ly-khu-vuc', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }

        if (!$all) {
            // neu khong thuoc cac quyen all thi lay ra id cua store cua user hien tai
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array(),
                    'groupRoles' => array(),
                    'total' => 0
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
        }
        $pti = $this->pti_vta_bn_model->get_pti_vta_accounting_transfe($condition);
        $total_money = 0;
        foreach ($pti as $value) {
            $total_money += (int)$value['price'];
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $pti,
            "total_money" => $total_money
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function get_total_pay_post()
    {
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : '';
        $total = 0;
        foreach ($code as $value) {
            $pti = $this->pti_vta_bn_model->findOne(['pti_code' => $value]);
            $total += (int)$pti['price'];
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'total' => number_format($total) . " VND",
            'message' => 'thanh cong'
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function create_transaction_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code_pti = !empty($data['code']) ? $data['code'] : '';
        $store_id = !empty($data['store']) ? $data['store'] : '';
        $storeUser = $this->store_model->findOne(['_id' => new MongoDB\BSON\ObjectId($store_id)]);
        $store = $this->role_model->get_store_user((string)$this->id);
        $code_coupon = !empty($data['code_coupon']) ? $data['code_coupon'] : '';
        if (empty($code_pti)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không có dữ liệu gửi sang kế toán"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $code = "PT_" . date('dmY') . '_' . uniqid();
        $money = 0;
        foreach ($code_pti as $value) {
            $pti_vta = $this->pti_vta_bn_model->findOne(['pti_code' => $value]);
            $this->pti_vta_bn_model->update(['_id' => $pti_vta['_id']], ['receipt_code' => $code, 'status' => 2]);
            $money += (int)$pti_vta['price'];
        }
        $data_transaction = [
            'code' => $code,
            'total' => (string)$money,
            'payment_method' => "1",
            'store' => [
                'name' => $storeUser['name'],
                'id' => (string)$storeUser['_id']
            ],
            "customer_bill_name" => $this->name,
            "customer_bill_phone" => $this->phone,
            'type' => 15,
            'status' => 2,
            'code_coupon_cash' => $code_coupon,
            'created_at' => $this->createdAt,
            'created_by' => $this->uemail
        ];
        $id_transaction = $this->transaction_model->insertReturnId($data_transaction);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Gửi yêu cầu thành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function detail_transaction_post()
    {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : '';
        $pti = $this->pti_vta_bn_model->find_where(['receipt_code' => $code]);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $pti
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    

    public function get_time_post()
    {
        $data = $this->input->post();
        $year = !empty($data['year']) ? $data['year'] : '';
        if ($year == '1') {
            $NGAY_HL = date('d/m/Y');
            $NGAY_KT = date('d/m/Y', strtotime("+1 year"));
        } elseif ($year == '3') {
            $NGAY_HL = date('d/m/Y');
            $NGAY_KT = date('d/m/Y', strtotime("+3 year"));
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'date' => $NGAY_KT,
            'message' => 'thanh cong'
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

   private function push_api_gci($action = '', $get = '', $data_post = [])
    {
        $url_pti = $this->config->item("url_pti");
        $accessKey = $this->config->item("access_key_pti");
        $service = $url_pti . '/api/PublicApi/' . $action . '?accessKey=' . $accessKey . $get;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $service);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($result);
        return $result1;
    }

	public function report_trade_pti_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$total_record = $this->pti_vta_bn_model->count_pti();
		$data_not_send_yet = $this->pti_vta_bn_model->getDataNotSendYet();
		$data_sended = $this->pti_vta_bn_model->getDataSend();

		$response = [
			'status' => REST_Controller::HTTP_OK,
			'total_record' => $total_record,
			'not_yet' => $data_not_send_yet,
			'sended' => $data_sended
		];
		$this->set_response($response,REST_Controller::HTTP_OK);
		return;
    }
    public function get_list_pti_vta_hd_post(){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = array();
       $total=0;
       $data = $this->input->post();
        $start = !empty($data['start']) ? $data['start'] : "";
        $end = !empty($data['end']) ? $data['end'] : "";
		$code_contract_disbursement = !empty($data['code_contract_disbursement']) ? $data['code_contract_disbursement'] : "";

        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start).' 00:00:00'),
                'end' => strtotime(trim($end).' 23:59:59')
            );
        }
        if (!empty($code_contract_disbursement)) {
        	$condition['code_contract_disbursement'] = $code_contract_disbursement;
		}

        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
         $total=0;
        if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
            $all = true;
        }
        if (!$all) {
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array(),
                    'total' => $total
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
           
        }
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
       
        $pti = $this->pti_vta_bn_model->getPti_vta_hd($condition,$per_page , $uriSegment);
        $total = $this->pti_vta_bn_model->getPti_vta_hd_total($condition);
           if (!empty($pti)) {
            foreach ($pti as $key => $value) {
              $contract=  $this->contract_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value['contract_id'])]);
               $value['contract_info']=$contract;
              }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $pti,
            'total' => $total,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
      public function get_list_pti_vta_doi_soat_post(){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = array();
       $total=0;
       $data = $this->input->post();
        $start = !empty($data['start']) ? $data['start'] : "";
        $end = !empty($data['end']) ? $data['end'] : "";

        if (!empty($start) && !empty($end)) {
            $condition = array(
                'start' => strtotime(trim($start).' 00:00:00'),
                'end' => strtotime(trim($end).' 23:59:59')
            );
        }

        $groupRoles = $this->getGroupRole($this->id);
        $all = true;
         $total=0;
        
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
       
        $pti = $this->pti_vta_bn_model->getPti_vta_doi_soat($condition,$per_page , $uriSegment);
        $total = $this->pti_vta_bn_model->getPti_vta_doi_soat_total($condition);
//           if (!empty($pti)) {
//            foreach ($pti as $key => $value) {
//              $contract=  $this->contract_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value['contract_id'])]);
//               $value['contract_info']=$contract;
//              }
//        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $pti,
            'total' => $total,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
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


    public function insert_pit_customer_post() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
		$fullname = !empty($data['fullname']) ? $data['fullname'] : '';
		$cmt = !empty($data['cmt']) ? $data['cmt'] : '';
		$relationship = !empty($data['relationship']) ? $data['relationship'] : '';
		$address = !empty($data['address']) ? $data['address'] : '';
		$obj = (!empty($data['obj']) && $data['obj'] == 'M') ? "banthan" : "nguoithan";
		$data['obj'] = $obj;
		if ($obj == "banthan") {
			$data['relationship'] = 'Bản thân';
		} else {
			$data['relationship'] = 'Người thân';
		}
		$phone = !empty($data['phone']) ? $data['phone'] : '';
		$email = !empty($data['email']) ? $data['email'] : '';
		$birthday = !empty($data['birthday']) ? $data['birthday'] : '';
		$fullname_another = !empty($data['fullname_another']) ? $data['fullname_another'] : '';
		$birthday_another = !empty($data['birthday_another']) ? $data['birthday_another'] : '';
		$email_another = !empty($data['email_another']) ? $data['email_another'] : '';
		$cmt_another = !empty($data['cmt_another']) ? $data['cmt_another'] : '';
		$phone_another = !empty($data['phone_another']) ? $data['phone_another'] : '';
		$fulladd_another = !empty($data['fulladd_another']) ? $data['fulladd_another'] : '';
		$gender_another = !empty($data['gender_another']) ? $data['gender_another'] : '';
		$sel_ql = !empty($data['sel_ql']) ? $data['sel_ql'] : '';
		$sel_year = !empty($data['sel_year']) ? $data['sel_year'] : '';
		$code_fee = !empty($data['code_fee']) ? $data['code_fee'] : '';
		$price = !empty($data['price']) ? $this->convertNumber($data['price']) : '';
		$data['price'] = $price;
		$data['code_fee'] = $code_fee;
		$ck1 = !empty($data['ck1']) ? $data['ck1'] : '';
		$ck2 = !empty($data['ck2']) ? $data['ck2'] : '';
		$ck3 = !empty($data['ck3']) ? $data['ck3'] : '';
		$checked_img = !empty($data['checked_img']) ? $data['checked_img'] : '';
		if ( $ck1=="co" ||  $ck2=="co" ||  $ck3=="co") {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không thành công , xác nhận thông tin không hợp lệ
                    "
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}
		if ($obj=="nguoithan") {
			if ($this->validateAge($birthday_another, 1, 69) == "FALSE") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Tuổi người hưởng bảo hiểm từ 1 đến 70"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!preg_match("/^[0-9]{10,10}$/", $phone_another)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Số điện thoại phải là 10 số!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!preg_match("/^[0-9]{9,12}$/", $cmt_another) && $checked_img == "tren18") {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Chứng minh thư người hưởng trong khoảng 9 đến 12 số!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
			if (!filter_var($email_another, FILTER_VALIDATE_EMAIL)) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Định dạng email người hưởng không hợp lệ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
        if ($obj=="banthan") {
            if ($this->validateAge($birthday, 1, 69) == "FALSE") {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Tuổi người hưởng bảo hiểm từ 1 đến 70"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!preg_match("/^[0-9]{10,10}$/", $phone)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Số điện thoại phải là 10 số!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }

            if (!preg_match("/^[0-9]{9,12}$/", $cmt) && $checked_img=="tren18") {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Chứng minh thư trong khoảng 9 đến 12 số!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Định dạng email không hợp lệ!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
		if ($obj == "banthan") {
			if (empty($data['address'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Địa chỉ không hợp lệ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		} else {
			if (empty($data['fulladd_another'])) {
				$response = array(
					'status' => REST_Controller::HTTP_UNAUTHORIZED,
					'message' => "Địa chỉ không hợp lệ!"
				);
				$this->set_response($response, REST_Controller::HTTP_OK);
				return;
			}
		}
        if($obj=="banthan") {
            $ck_bh = $this->pti_vta_bn_model->findOne(['so_xac_minh' => $cmt,'pti_info.code'=>"000"]);
        } else {
            $ck_bh = $this->pti_vta_bn_model->findOne(['so_xac_minh' => $cmt_another,'pti_info.code'=>"000"]);
        }
        if(!empty($ck_bh) && strtotime($ck_bh['NGAY_KT']) > time())
        {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Bạn đã mua bảo hiểm trước đó. Hết thời gian hiệu lực bạn mới có thể mua bảo hiểm mới."
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $type_pti = "WEB";
        $code = 'WEB_' . date("dmY") . "_" . time();
		$receipt_code = 'PT_' . date("dmY") . "_" . uniqid();

        $number_item=$this->initNumberItem_pti_vta();
		$storePti = $this->getStorePTI();

        $dt_pti = array(
            'type_pti' => "WEB"
            ,'code_pti_vta'=>$code
            , 'pti_code' => 'TN'.str_pad((string)$number_item,7, '0', STR_PAD_LEFT).'/041/CN.1.14/'.date('Y')
            , 'number_item' => (int)$number_item
            ,'price' =>  $this->convertNumber($price)
            ,'so_xac_minh' => !empty($cmt) ? $cmt : ''
            ,'data_origin'=>$data
            ,'status' => 10
			,'created_at' => time()
			,'receipt_code' => $receipt_code
			,'store' => $storePti
			,'code_fee' => $code_fee
        );
        $this->log_pti(json_encode($dt_pti), "", $type_pti, $code);
        
        $ptiDB = $this->pti_vta_bn_model->insert($dt_pti);

		// Tạo phiếu thu
		$data_transaction = [
			'code' => $receipt_code,
			'total' => (string)$price,
			'payment_method' => "2",
			'store' => $storePti,
			'code_fee' => $code_fee,
			"customer_bill_name" => $fullname,
			"customer_bill_phone" => $phone,
			'type' => 15,
			'status' => 2,
			'created_at' => time(),
			'created_by' => 'system'
		];
		$this->transaction_model->insertReturnId($data_transaction);
       
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Thêm bảo hiểm thành công!",
            'number_item' => (int)$number_item,
            'data' => $dt_pti
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
	}

	public function getStorePTI() {
		$data = $this->store_model->findOne([
			'name' => 'Bảo hiểm PTI'
		]);
		return [
			'name' => $data['name'],
			'id' => (string)$data['_id']
		];
	}

    public function convertNumber($num) {
		return (int) str_replace(',', '', $num);
	}

    public function confirm_payment_customer_post() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
        $number_item = !empty($data['number_item']) ? $data['number_item'] : '';

        $pti_insurance = $this->pti_vta_bn_model->findOne([
            'number_item' => (int) $number_item,
        ]);

        if ($pti_insurance) {
            $NGAY_HL = date('d-m-Y', strtotime("+1 days"));
            $pti_vta = $this->insert_pti_vta($pti_insurance['data_origin'], $NGAY_HL, $pti_insurance['type_pti'], $pti_insurance['code_pti_vta'], $pti_insurance['number_item']);
            $this->log_pti(json_encode($pti_insurance['data_origin']), $pti_vta, $pti_insurance['type_pti'], $pti_insurance['code_pti_vta']);
            if ($pti_vta->success == true) {
                $pti = $pti_vta->data;
                $request = $pti_vta->request;
                $NGAY_KT = $pti_vta->NGAY_KT;
                $this->pti_vta_bn_model->update([
                    'number_item' => (int) $number_item,
                ], [
                    'status' => 1,
                    'modify_user' => $this->uemail,
                    'modify_date' => time(),
                    'pti_info' => $pti,
                    'request' => $request,
                    'NGAY_KT' => $NGAY_KT,
                    'NGAY_HL' => $NGAY_HL,
                    'customer_info' => [
                        'customer_name' =>!empty($request->ten) ? $request->ten : '',
                        'customer_phone' =>!empty($request->phone) ? $request->phone : '',
                        'card' =>!empty($request->so_cmt) ? $request->so_cmt : '',
                        'email' => !empty($request->email) ? $request->email : '',
                        'birthday' =>!empty($request->ngay_sinh) ? $request->ngay_sinh : ''
                    ],
                    'request' => $request,
                ]);

                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => "Thành công!"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_BAD_REQUEST,
            'message' => "Thất bại"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function cancel_payment_customer_post() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
        $number_item = !empty($data['number_item']) ? $data['number_item'] : '';
        $this->pti_vta_bn_model->update([
            'number_item' => (int) $number_item,
        ], [
            'status' => 3,
            'refund' => 0,
            'modify_user' => $this->uemail,
            'modify_date' => time()
        ]);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Thành công!"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function refund_payment_customer_post() {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
		$data = $this->input->post();
        $number_item = !empty($data['number_item']) ? $data['number_item'] : '';
        $this->pti_vta_bn_model->update([
            'number_item' => (int) $number_item,
        ], [
            'status' => 3,
            'refund' => 1,
            'modify_user' => $this->uemail,
            'modify_date' => time()
        ]);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Thành công!"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    /**
    *   Lấy Giấy Chứng Nhận PTI VTA
    */
    public function getGCN_post() {
        $data = $this->input->post();
        $so_id = !empty($data['so_id']) ? $data['so_id'] : "";
        if (empty($so_id)) {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Thất bại, so_id không được để trống."
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }

        $inputData = [
            'so_id' => (int) $so_id
        ];

        $baohiem = new BaoHiemPTI();
        $res = $baohiem->getGCN($inputData);
        if ($res['status'] == 200 && isset($res['data']['data'])) {
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Thành công.",
                'data' => $res['data']['data']
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        } else {
            $response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Thất bại, không lấy được dữ liệu."
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
}

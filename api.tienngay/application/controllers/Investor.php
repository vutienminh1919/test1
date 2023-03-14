
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Investor extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('investor_model');
        $this->load->model('contract_model');
         $this->load->model('transaction_model');
         $this->load->model('contract_tempo_model');
         $this->load->model('temporary_plan_contract_model');
        $this->load->model('log_model');
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
    }
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $investorss = $this->investor_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investorss
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $investorss = $this->investor_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($investorss)) {
            foreach ($investorss as $sto) {
                $sto['investors_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investorss
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_investor_nl_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $investors = $this->investor_model->find_where(array("type_investors" => '1'));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $investors = $this->investor_model->find_where_in('status', ['active','deactive']);
        if (!empty($investors)) {
            foreach ($investors as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $investors = $this->investor_model->find_where_in('status', ['active']);
        if (!empty($investors)) {
            foreach ($investors as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_one_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $investors = $this->investor_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_temporary_plan_contract_one_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $investors = $this->temporary_plan_contract_model->find_where(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     //lấy danh danh contract bằng mã nhà đầu tư
      public function get_investor_in_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
                     }
            $contract = $this->contract_model->find_where(array("investor_infor._id" =>  new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        }

    //lấy danh danh payment contract bằng mã nhà đầu tư
      public function get_investor_payment_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
                     }
            $contract = $this->contract_model->findOne(array("_id" =>  new MongoDB\BSON\ObjectId($id)));
            if(!empty($contract))
            {
            
            $investors=$this->investor_model->findOne(array("code" =>  $contract['investor_code']));
            $contract['investors_info']=$investors;
             $cond = array(
                    'code_contract' => $contract['code_contract']
                );
            $detail = $this->contract_tempo_model->find_where($cond);
            if( !empty($detail))
            {
                $tong_tien_goc_den_han=0;
                $tong_tien_lai_den_han=0;
                foreach ($detail as $key => $de) {
               
                     $tong_tien_lai_den_han+=$de['lai_ky'];
                     $tong_tien_goc_den_han += (isset($de['tien_goc_1ky'])) ? (int)$de['tien_goc_1ky'] : 0;  
                    
                }
             $contract['tong_tien_goc_den_han']=$tong_tien_goc_den_han;
             $contract['tong_tien_lai_den_han']=$tong_tien_lai_den_han;

            }
        $condition = array(
            'code_contract' => $contract['code_contract'],
            'type' =>  6.0,
             "status" => 1.0
        );
        $so_tien_goc_da_tra = 0;
         $so_tien_lai_da_tra = 0;
        $transaction = $this->transaction_model->find_where($condition);
        foreach ($transaction as $key => $tran) {
         $so_tien_goc_da_tra += $tran['so_tien_goc_da_tra'];
         $so_tien_lai_da_tra += $tran['so_tien_lai_da_tra'];
          $contract['tong_tien_goc_da_tra']=$so_tien_goc_da_tra;
             $contract['tong_tien_lai_da_tra']=$so_tien_lai_da_tra;
        }
          $contract['tong_tien_con_lai_dao_han']=($tong_tien_goc_den_han-$so_tien_goc_da_tra)+($tong_tien_lai_den_han-$so_tien_lai_da_tra);
            }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'dataContract' => $contract,
            'dataTem' => $detail,
            'dataTran'=>$transaction 

        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        }

        //lấy danh danh sách nhà đầu tư đang sắp đến hạn
      public function get_investor_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;

        $data = $this->input->post();
            $contract = array();
            $contract = $this->contract_model->find_where(array("status"=> array('$gte' =>17)));

     
        if (!empty($contract)) {

            foreach ($contract as $key=>$c) {
                
                $cond = array(
                    'code_contract' => $c['code_contract']
                 
                );
                $detail = $this->contract_tempo_model->getContractInvestorTime($cond);
                if($c['code_contract']=="00000174")
                {
                   
                    $a = $detail ;
       
                }
               $tong_tien_goc_den_han=0;
               $tong_tien_lai_den_han=0;
               $ngay_ky_tra="";
                
                $total_paid = 0;
                foreach ($detail as $de) {
                    $investors = $this->investor_model->findOne(array("code" => $c['investor_code']));
                     $current_day = strtotime(date('m/d/Y'));
                      $datetime = !empty($de['ngay_ky_tra']) ? intval($de['ngay_ky_tra']): $current_day;
                      $time = intval(($current_day - $datetime) / (24*60*60));
                      // if($time>=0)
                      // {
                        $ngay_ky_tra=0;
                        $ngay_ky_tra=$de['ngay_ky_tra'];
                      //}
                      
                 
               
                     $tong_tien_lai_den_han+= (isset($de['lai_ky'])) ? (float)$de['lai_ky'] : 0;  
                     $tong_tien_goc_den_han += (isset($de['tien_goc_1ky'])) ? (float)$de['tien_goc_1ky'] : 0;  
                        
                   }
            $c['tong_tien_goc_den_han']=$tong_tien_goc_den_han;
             $c['tong_tien_lai_den_han']=$tong_tien_lai_den_han;
             $c['ngay_ky_tra']=$ngay_ky_tra;
              $c['ten_nha_dau_tu']=(!empty($investors['name'])) ? $investors['name'] : "";
             if ($ngay_ky_tra=="") {
                 unset($contract[$key]);
             }
                }
            
             
            
        }
     //   var_dump($contract); die;
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        }
        //lấy danh sách chi tiết thành toán cho nhà đầu tư bằng mã contract
        public function get_temporary_plan_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
        if(empty($code_contract)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        var_dump($id); die;
        $investors = $this->temporary_plan_contract_model->find_where(array("code_contract" =>  $code_contract));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_investors_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id investors already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $investors = $this->investor_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $investors
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_investors_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : "";
        $count = $this->investor_model->count(array("code" => $code));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Đã tồn tại nhà đầu tư"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->investor_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create investors success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_investors_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->investor_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại nhà đầu tư nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_investors($data);
        unset($data['id']);
     
        $this->investor_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update investors success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function update_temporary_plan_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id_contract']) ? $data['id_contract'] : "";
        $contract_id=$id ;
        $contract = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $count = $this->temporary_plan_contract_model->count(array("code_contract" =>$contract['code_contract'] ));
        $investors=$this->investor_model->findOne(array("code" =>  $contract['investor_code']));
            $contract['investors_info']=$investors;
        if($count < 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'msg' => "Không tồn tại thông tin nào cần cập nhật",
                'data'=>$id
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
       
         $data['lich_su_tra_ndt_thu']['so_tien_goc_da_tra']=(float)$data['lich_su_tra_ndt_thu']['so_tien_goc_da_tra'];
         $data['lich_su_tra_ndt_thu']['so_tien_lai_da_tra']=(float)$data['lich_su_tra_ndt_thu']['so_tien_lai_da_tra'];
      unset($data['id_contract']);
        $dataTem=$this->temporary_plan_contract_model->find_where(array("code_contract" =>$contract['code_contract'] ));

        foreach ($dataTem as $key => $tem) {
            $id=$tem['_id'];
            $lai_ky=$tem['lai_ky']; 
            $data['lich_su_tra_ndt_thu']['tien_goc_1ky_con_lai']=(float)$tem['tien_goc_1ky'];
            $data['lich_su_tra_ndt_thu']['tien_lai_1ky_con_lai']=(float)$lai_ky;
             $this->temporary_plan_contract_model->update(
            array("_id" => $id),
            $data
            );
             $data['id']=$id;
                $this->log_temporary_plan_contract($data);
       
        }
       

        $dt_tran= $this->temporary_plan_contract_model->findOne( array("_id" => new MongoDB\BSON\ObjectId($id)));
     
        $dt_save_tran= array(
            "code_contract_disbursement" =>(isset($contract['code_contract_disbursement'])) ? $contract['code_contract_disbursement'] : "", 
            "code_contract" => $dt_tran['code_contract'], 
            "total" => (float)$dt_tran['lich_su_tra_ndt_thu']['so_tien_goc_da_tra']+(float)$dt_tran['lich_su_tra_ndt_thu']['so_tien_lai_da_tra'], 
            "type" => 6.0, 
            "status" => 1.0, 
            "so_tien_goc_da_tra" =>(float)$dt_tran['lich_su_tra_ndt_thu']['so_tien_goc_da_tra'], 
            "so_tien_lai_da_tra" => (float)$dt_tran['lich_su_tra_ndt_thu']['so_tien_lai_da_tra'], 
            "ma_giao_dich_ngan_hang" => $dt_tran['lich_su_tra_ndt_thu']['ma_giao_dich_ngan_hang'],
            "hinh_thuc_tra" => $dt_tran['lich_su_tra_ndt_thu']['hinh_thuc_tra'],
            "ghi_chu" => $dt_tran['lich_su_tra_ndt_thu']['ghi_chu'],
            "date_pay" => (float)$dt_tran['lich_su_tra_ndt_thu']['ngay_tra'], 
            "created_at" =>(float)$this->createdAt,
            "created_by" => $this->uemail
        );
        $transaction_id =   $this->transaction_model->insertReturnId(
            $dt_save_tran
        );
       $url = 'investors/view_detail_payment?id='.(string)$contract_id;
      $return= $this->tinhtoanBangLaiKy($contract['code_contract']);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'msg' => "Update investors success1",
           'data' => $dt_save_tran,
           'url'=>$url,
            'ck'=> $return
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function log_investors($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $investors = $this->investor_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $investors['id'] = (string)$investors['_id'];
        unset($investors['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $investors,
            "type" => 'investors'

        );
        $this->log_model->insert($dataInser);
    }
      public function log_temporary_plan_contract($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $investors = $this->temporary_plan_contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $investors['id'] = (string)$investors['_id'];
        unset($investors['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $investors,
            "type" => 'temporary_plan_contract'

        );
        $this->log_model->insert($dataInser);
    }

    public function tinhtoanBangLaiKy($codeContract) {
        $condition = array(
            'code_contract' => $codeContract,
            'type' =>  6.0,
             "status" => 1.0
        );
        $so_tien_goc_da_tra = 0;
         $so_tien_lai_da_tra = 0;
        $transaction = $this->transaction_model->find_where($condition);
        foreach ($transaction as $key => $tran) {
         $so_tien_goc_da_tra += (float)$tran['so_tien_goc_da_tra'];
         $so_tien_lai_da_tra += (float)$tran['so_tien_lai_da_tra'];
        }
        
        //Tìm các bản ghi lãi kỳ
        $temps = $this->temporary_plan_contract_model->find_where_order_by(array(
            "code_contract" => $codeContract
        ));
      return  $this->bangLaiKy_tinhtoan_tien_datra_conlai($codeContract,$temps, $so_tien_goc_da_tra,$so_tien_lai_da_tra);
        //$this->bangLaiKy_tinhtoan_duno_thangtruoc($codeContract, $amount);
    }
     private function bangLaiKy_tinhtoan_tien_datra_conlai($codeContract,$temps, $so_tien_goc_da_tra,$so_tien_lai_da_tra) {
        
        $amountRemain_goc = 0;
        $amountRemain_lai = 0;
        $temps_lai=$temps;
        $so_goc_da_tra_transaction = 0;
        $so_lai_da_tra_transaction = 0;
   
        
        foreach($temps as $temp) {
            $id=$temp['_id'];
            if($amountRemain_goc == 0) $amountRemain_goc = $so_tien_goc_da_tra;
     
            
             //Tiền phải đóng tháng hiện tại
            $goc_phai_dong_thang_hien_tai = !empty($temp['tien_goc_1thang']) ? $temp['tien_goc_1thang'] : 0;
           
           

            //Tiền đã đóng kỳ hiện tại
            $goc_da_dong_ky_hien_tai = !empty($temp['lich_su_tra_ndt_thu']['tien_goc_1ky_da_tra']) ? $temp['lich_su_tra_ndt_thu']['tien_goc_1ky_da_tra'] : 0;
         
          
            
            //Tiền còn lại phải đóng kỳ hiện tại
            $goc_con_lai_ky_hien_tai = !empty($temp['lich_su_tra_ndt_thu']['tien_goc_1ky_con_lai']) ? $temp['lich_su_tra_ndt_thu']['tien_goc_1ky_con_lai'] : 0;
           
          
            $dataUpdate = array();
            $dataUpdate = $temp;
         
            //Gốc
            if($amountRemain_goc <= 0) break;
            if($goc_con_lai_ky_hien_tai > 0) {
                if($amountRemain_goc >= $goc_con_lai_ky_hien_tai) {
                    //Update $goc_da_dong_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_goc_1ky_da_tra'] = (float)$goc_da_dong_ky_hien_tai + (float)$goc_con_lai_ky_hien_tai;
                    
                    $so_goc_da_tra_transaction = (float)$so_goc_da_tra_transaction + (float)$goc_con_lai_ky_hien_tai;
                    
                    //Update $goc_con_lai_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_goc_1ky_con_lai'] = 0;
                    //Remain
                    $amountRemain_goc = (float)$amountRemain_goc - (float)$goc_con_lai_ky_hien_tai;
                } else {
                    //Update $goc_da_dong_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_goc_1ky_da_tra'] = (float)$goc_da_dong_ky_hien_tai + (float)$amountRemain_goc;
                    
                    $so_goc_da_tra_transaction = (float)$so_goc_da_tra_transaction + (float)$amountRemain_goc;
                    
                    //Update $goc_con_lai_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_goc_1ky_con_lai'] = (float)$goc_con_lai_ky_hien_tai - (float)$amountRemain_goc;
                    $amountRemain_goc = (float)$amountRemain_goc - (float)$goc_con_lai_ky_hien_tai;
                }
            }
            unset($dataUpdate['_id']);
             //return $dataUpdate['lich_su_tra_ndt_thu'];
            //Update DB
            if(!empty($dataUpdate)) {
            $log=    $this->temporary_plan_contract_model->update(
                    array("_id" => $id),
                     array('lich_su_tra_ndt_thu' => $dataUpdate['lich_su_tra_ndt_thu'] )
                );
            }
            }


            $temps = $this->temporary_plan_contract_model->find_where_order_by(array(
            "code_contract" => $codeContract
        ));
           // return $dataUpdate['lich_su_tra_ndt_thu'];
            foreach($temps as $temp) {
            $id=$temp['_id'];
        
            if($amountRemain_lai == 0) $amountRemain_lai = $so_tien_lai_da_tra;
            
             //Tiền phải đóng tháng hiện tại
        
            $lai_phai_dong_thang_hien_tai = !empty($temp['tien_lai_1thang']) ? $temp['tien_lai_1thang'] : 0;
           

            //Tiền đã đóng kỳ hiện tại
   
            $lai_da_dong_ky_hien_tai = !empty($temp['lich_su_tra_ndt_thu']['tien_lai_1ky_da_tra']) ? $temp['lich_su_tra_ndt_thu']['tien_lai_1ky_da_tra'] : 0;
          
            
            //Tiền còn lại phải đóng kỳ hiện tại
        
            $lai_con_lai_ky_hien_tai = !empty($temp['lich_su_tra_ndt_thu']['tien_lai_1ky_con_lai']) ? $temp['lich_su_tra_ndt_thu']['tien_lai_1ky_con_lai'] : 0;
          
            $dataUpdate = array();
            $dataUpdate = $temp;
            if($amountRemain_lai <= 0) break;
            //Lãi
            if($lai_con_lai_ky_hien_tai > 0) {
                if($amountRemain_lai >= $lai_con_lai_ky_hien_tai) {
                    //Update $lai_da_dong_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_lai_1ky_da_tra'] = (float)$lai_da_dong_ky_hien_tai + (float)$lai_con_lai_ky_hien_tai;
                    
                    $so_lai_da_tra_transaction = (float)$so_lai_da_tra_transaction + (float)$lai_con_lai_ky_hien_tai;
                    
                    //Update $lai_con_lai_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_lai_1ky_con_lai'] = 0;
                    //Remain
                    $amountRemain_lai = (float)$amountRemain_lai - (float)$lai_con_lai_ky_hien_tai;
                } else {
                    //Update $lai_da_dong_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_lai_1ky_da_tra'] = (float)$lai_da_dong_ky_hien_tai + (float)$amountRemain_lai;
                    
                    $so_lai_da_tra_transaction = (float)$so_lai_da_tra_transaction + (float)$amountRemain_lai;
                    
                    //Update $lai_con_lai_ky_hien_tai
                    $dataUpdate['lich_su_tra_ndt_thu']['tien_lai_1ky_con_lai'] = (float)$lai_con_lai_ky_hien_tai - (float)$amountRemain_lai;
                    //Remain
                    $amountRemain_lai = (float)$amountRemain_lai - (float)$lai_con_lai_ky_hien_tai;
                }
            }
            unset($dataUpdate['_id']);
            //Update DB
            if(!empty($dataUpdate)) {
                $this->temporary_plan_contract_model->update(
                    array("_id" => $id),
                     array('lich_su_tra_ndt_thu' => $dataUpdate['lich_su_tra_ndt_thu'] )
                );
            }
            
        }
           
           
            
         
        
    }
    

}
?>

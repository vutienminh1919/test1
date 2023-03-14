
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Coupon_cash extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('coupon_cash_model');
        $this->load->model('store_model');
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
     date_default_timezone_set('Asia/Ho_Chi_Minh');
    }
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $coupon_cashs = $this->coupon_cash_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $coupon_cashs
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $coupon_cashs = $this->coupon_cash_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($coupon_cashs)) {
            foreach ($coupon_cashs as $sto) {
                $sto['coupon_cash_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $coupon_cashs
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $coupon_cash = $this->coupon_cash_model->find_where('status', ['active','deactive']);
        if (!empty($coupon_cash)) {
            foreach ($coupon_cash as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $coupon_cash
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
        $condition=array();
       $data = $this->input->post();
       $created_at = !empty($data['created_at']) ? (int)$data['created_at'] : $this->createdAt;
       $bh_product = !empty($data['bh_product']) ? $data['bh_product'] : "";
       $store_id = !empty($data['store_id']) ? $data['store_id'] : "";
       $loai_khach = !empty($data['loai_khach']) ? $data['loai_khach'] : "";

        if(!empty($created_at))
        {
        $condition['created_at']=$created_at;
        }
       
       
      
        $coupon_cash = $this->coupon_cash_model->find_where_home($condition);
        if(!empty($coupon_cash))
        {
            foreach ($coupon_cash as $key => $data_coupon_cash) {
                if(empty($loai_khach) || empty($bh_product)  )
                {
                   unset($coupon_cash[$key]);
                }
      
                if( !empty($bh_product) && !empty($data_coupon_cash['bh_product']) && !in_array($bh_product,(array)$data_coupon_cash['bh_product']) && !in_array('null',(array)$data_coupon_cash['bh_product']))
                    {
                      unset($coupon_cash[$key]);
                    }
                  if( !empty($loai_khach) && !empty($data_coupon_cash['loai_khach']) && !in_array($loai_khach,(array)$data_coupon_cash['loai_khach']) && !in_array('null',(array)$data_coupon_cash['loai_khach']))
                    {
                      unset($coupon_cash[$key]);
                    }
                    $data_store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($store_id)));
                        if (!empty($data_store)) {
                            if ((string)$data_store['_id'] != $data_coupon_cash['code_store'] && !empty($data_coupon_cash['code_store'])) {
                                   unset($coupon_cash[$key]);
                                }
                          
                            if (!empty($data_coupon_cash['code_area']) && is_array((array)$data_coupon_cash['code_area']) && !in_array($data_store['code_area'], (array)$data_coupon_cash['code_area']) && !in_array('null', (array)$data_coupon_cash['code_area'])) {
                                unset($coupon_cash[$key]);
                            }
                        }

                   }

        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'code' =>isset($coupon_cash[0]['code']) ? $coupon_cash[0]['code'] : ''
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_coupon_cash_by_id_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id coupon_cash already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $coupon_cash = $this->coupon_cash_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $coupon_cash
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_coupon_cash_by_code_post(){
      
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : "";
        if(empty($code)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id coupon_cash already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $coupon_cash = $this->coupon_cash_model->findOne(array("code" => $code));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $coupon_cash
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_coupon_cash_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $count=0;
         $data = $this->input->post();
         $data['start_date']=strtotime($data['start_date'].' 00:00:00');
         $data['end_date']=strtotime($data['end_date'].' 23:59:59');
         $id = !empty($data['id']) ? $data['id'] : "";
         if(!empty($id)){ unset($data["code"]);}
         if(!empty($data["code"])) {
        $count = $this->coupon_cash_model->count(array("code" =>  $data["code"] ));
       if( $count >=1)
        {
           $this->coupon_cash_model->insert($data);
        $response = array(
        'status' => REST_Controller::HTTP_UNAUTHORIZED,
        'message' => "Coupon đã tồn tại"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
        }
        }
      
        if(empty($id)) {
         
        $data["created_at"] = $this->createdAt;
        $data["created_by"] = $this->id;
        $data["updated_at"] = $this->createdAt;
        $data["updated_by"] =$this->id;
       
        $this->coupon_cash_model->insert($data);
        $response = array(
        'status' => REST_Controller::HTTP_OK,
        'message' => "Thêm mới coupon_cash hành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
        }
         if(!empty($id)) {
        $count = $this->coupon_cash_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
       
        }
        if( $count!=1)
        {

     
        $data["updated_at"] = $this->createdAt;
        $data["updated_by"] =$this->id;
        unset($data['id']);
         $data = $this->input->post();
        $this->coupon_cash_model->insert($data);
        $response = array(
        'status' => REST_Controller::HTTP_OK,
        'message' => "Thêm mới coupon_cash hành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
        }
        $this->log_coupon_cash($data);
        unset($data['id']);
       
        $data["updated_at"] = $this->createdAt;
        $data["updated_by"] =$this->id;
        $this->coupon_cash_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );

        $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => " Cập nhật coupon_cash hành công"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_coupon_cash_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->coupon_cash_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại bản ghi nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_coupon_cash($data);
        unset($data['id']);
     
        $this->coupon_cash_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update coupon_cash success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_coupon_cash($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $coupon_cash = $this->coupon_cash_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $coupon_cash['id'] = (string)$coupon_cash['_id'];
        unset($coupon_cash['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $coupon_cash,
            "type" => 'coupon_cash'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

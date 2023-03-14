
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class WarehouseAsset extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse_asset_model');
        $this->load->model('warehouse_model');
        $this->load->model('contract_model');
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

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $investors = $this->investor_model->find_where('status', ['active','deactive']);
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
       
        $investors = $this->investor_model->find_where('status', ['active']);
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
        //var_dump($id); die;
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
                'message' => "Không tồn tại tin tức nào cần cập nhật"
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
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->temporary_plan_contract_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại thông tin nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_temporary_plan_contract($data);
        unset($data['id']);
     
        $this->temporary_plan_contract_model->update(
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

}
?>

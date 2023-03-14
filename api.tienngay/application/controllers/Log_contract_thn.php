
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Log_contract_thn extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('log_contract_thn_model');
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
  
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;
    
       public function get_one_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
            $menu = $this->log_contract_thn_model->findOne(array("contract_id" => $data['id']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menu
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $Log_contract_thn = $this->Log_contract_thn_model->find_where('status', ['active','deactive']);
        if (!empty($Log_contract_thn)) {
        	foreach ($Log_contract_thn as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $Log_contract_thn
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $Log_contract_thn = $this->Log_contract_thn_model->find_where('status', ['active']);
        if (!empty($Log_contract_thn)) {
            foreach ($Log_contract_thn as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $Log_contract_thn
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_log_contract_thn_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id Log_contract_thn already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $Log_contract_thn = $this->Log_contract_thn_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $Log_contract_thn
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_log_contract_thn_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id Log_contract_thn already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $Log_contract_thn = $this->Log_contract_thn_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $Log_contract_thn
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_log_contract_thn_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
      
        $this->Log_contract_thn_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create Log_contract_thn success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_log_contract_thn_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->Log_contract_thn_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại thông tin nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_Log_contract_thn($data);
        unset($data['id']);
     
        $this->Log_contract_thn_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update Log_contract_thn success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }



}
?>

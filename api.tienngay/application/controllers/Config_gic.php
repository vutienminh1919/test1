<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Config_gic extends REST_Controller{
   
    public function __construct(){
        parent::__construct();
        $this->load->model('config_gic_model');
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
      public function get_config_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : "";
        if(empty($code)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Code null"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $faq = $this->config_gic_model->findOne(array("code" => $code));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $faq
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
      public function get_city_post()
       {
          $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
         $res= $this->push_api_gci('GetDropdownDataFromCode','&code=CITY',$data);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $res
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     public function get_district_post()
       {
          $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
         $res= $this->push_api_gci('GetDropdownDataFromCode','&code=DISTRICT',$data);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $res
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     private function push_api_gci($action='',$get='',$data_post=[]){
         $url_gic=$this->config->item("url_gic");
        $accessKey=$this->config->item("access_key_gic");
        $service = $url_gic.'/api/PublicApi/'.$action.'?accessKey='.$accessKey.$get;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$service);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($ch);
        curl_close ($ch);
        $result1 = json_decode($result);
        return $result1;
    }
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $config_gics = $this->config_gic_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gics
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $config_gics = $this->config_gic_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($config_gics)) {
        	foreach ($config_gics as $sto) {
        		$sto['config_gic_id'] = (string)$sto['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gics
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $config_gic = $this->config_gic_model->find_where('type', ['1']);
      
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $config_gic = $this->config_gic_model->find_where('status', ['active']);
        if (!empty($config_gic)) {
            foreach ($config_gic as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_config_gic_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id config_gic already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $config_gic = $this->config_gic_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_config_gic_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id config_gic already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $config_gic = $this->config_gic_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $config_gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_config_gic_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code = !empty($data['code']) ? $data['code'] : "";
       $count = $this->config_gic_model->count(array("code" =>$code));
        if($count > 0 ) {

            $this->config_gic_model->update(
            array("code" => $code),
            $data
        );

            $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update config_gic success",
            'data' => $data
        );
           $this->set_response($response, REST_Controller::HTTP_OK);
         return;
        }
        $this->config_gic_model->insert($data);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create config_gic success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_config_gic_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
         $code = !empty($data['code']) ? $data['code'] : "";
        $count = $this->config_gic_model->count(array("code" =>$code));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Update config_gic success",
                'data' => $data
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_config_gic($data);
       
     
        $this->config_gic_model->update(
            array("code" =>$code),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update config_gic success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_config_gic($data){
        $code = !empty($data['code']) ? $data['code'] : "";
        $config_gic = $this->config_gic_model->findOne(array("code" => $code));
        $config_gic['id'] = (string)$config_gic['_id'];
        unset($config_gic['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $config_gic,
            "type" => 'config_gic'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

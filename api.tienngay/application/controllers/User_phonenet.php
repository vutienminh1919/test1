
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class User_phonenet extends REST_Controller{
   
    public function __construct(){
        parent::__construct();
        $this->load->model('user_phonenet_model');
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
    private function api_phonenet($post='',$data_post="",$get=""){
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
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $user_phonenets = $this->user_phonenet_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenets
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $user_phonenets = $this->user_phonenet_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($user_phonenets)) {
        	foreach ($user_phonenets as $sto) {
        		$sto['user_phonenet_id'] = (string)$sto['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenets
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $user_phonenet = $this->user_phonenet_model->find_where('type', ['1']);
        if (!empty($user_phonenet)) {
        	foreach ($user_phonenet as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $user_phonenet = $this->user_phonenet_model->find_where('status', ['active']);
        if (!empty($user_phonenet)) {
            foreach ($user_phonenet as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_user_phonenet_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id user_phonenet  exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $user_phonenet = $this->user_phonenet_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_user_phonenet_by_ext_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $extension_number = !empty($data['extension_number']) ? $data['extension_number'] : "";
        if(empty($extension_number)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id user_phonenet  exists1"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $user_phonenet = $this->user_phonenet_model->findOne(array("extension_number" => $extension_number));
        if(empty($user_phonenet)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id user_phonenet  exists1"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_user_phonenet_by_email_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $email_user = !empty($data['email_user']) ? $data['email_user'] : "";
        
        $user_phonenet = $this->user_phonenet_model->findOne(array("email_user" => $email_user));
        if(empty($user_phonenet)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id user_phonenet  exists1"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
         
          $res= $this->api_phonenet('GET','',"/profile/number?userId=".$user_phonenet['response']['_id']);
         
            $user_phonenet['number_call']=$res;
         
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_user_phonenet_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id user_phonenet already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $user_phonenet = $this->user_phonenet_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $user_phonenet
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_user_phonenet_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $email_user = !empty($data['email_user']) ? $data['email_user'] : "";
        $extension_number = !empty($data['extension_number']) ? $data['extension_number'] : "";
       $count = $this->user_phonenet_model->count(array("email_user" =>$email_user));
        if($count > 0 ) {

            $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "User đã tồn tại",
            'data' => $data
        );
           $this->set_response($response, REST_Controller::HTTP_OK);
         return;
        }
        $user = $this->user_model->findOne(array("email" => $email_user));
        $dt_phone=array(
            'email' =>$email_user ,
            'name' =>$user['full_name'] ,
            'ext' => $extension_number,
            'extPassword' =>'matkhau' ,
            'password' =>'matkhau' ,
            'phone' => $user['phone_number'],
            'role' => 'owner',
            'active'=>true
             );
          $res= $this->api_phonenet('POST',json_encode($dt_phone),"/user");
          if(isset($res->active))
         {
          $data['response']=$res;
           $this->user_phonenet_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create user_phonenet success",
            'data'=>$res
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
          
       }else{
         $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "Không thành công",
            'data' => $res
                );
           $this->set_response($response, REST_Controller::HTTP_OK);
           return;
       }
        
    }

    public function update_user_phonenet_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
         $id = !empty($data['id']) ? $data['id'] : "";
          $email_user = !empty($data['email_user']) ? $data['email_user'] : "";
          $status = !empty($data['status']) ? $data['status'] : "";
          $active  = ($status=='active') ? true : false;
        $extension_number = !empty($data['extension_number']) ? $data['extension_number'] : "";
        $info = $this->user_phonenet_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        if(empty($info)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Update not ok",
                'data' => $data
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $user = $this->user_model->findOne(array("email" =>$info['email_user']));
        if($status=="")
        {
        $dt_phone=array(
            'email' =>$email_user ,
            'name' =>$user['full_name'] ,
            'ext' => $extension_number,
            'extPassword' =>'matkhau' ,
            'password' =>'matkhau' ,
            'phone' => $user['phone_number'],
            'role' => 'owner',
            'active'=>true
             );
        }else{
            $dt_phone=array(
            'email' =>$info['email_user'] ,
            'name' =>$user['full_name'] ,
            'ext' => $info['extension_number'],
            'extPassword' =>'matkhau' ,
            'password' =>'matkhau' ,
            'phone' => $user['phone_number'],
            'role' => 'owner',
            'active'=>$active
             );
        }
          $res= $this->api_phonenet('PUT',json_encode($dt_phone),'/user/'.$info['response']['id']);
          if(isset($res->active))
         {
          $data['response_update']=$res;
        $this->log_user_phonenet($data);
        $this->user_phonenet_model->update(
            array("_id" =>new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update user_phonenet success",
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
       }else{
        $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "Update user_phonenet false",
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
       }
    }
     public function get_brandname_post(){
        $res= $this->api_phonenet('GET','','/sms-brand-name?page=1&pageSize=30');
        
        
        $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "Gửi SMS lỗi",
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
       

     }
     public function get_template_post(){
        $res= $this->api_phonenet('GET','','/sms-template?page=1&pageSize=30&brandName='.$this->config->item("brandname_sms_phonenet"));
        
        
        $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "Gửi SMS lỗi",
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
     }
    public function send_sms_voice_otp_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $brandname_sms_phonenet = $this->config->item("brandname_sms_phonenet");
        $template_phonenet = $this->config->item("template_phonenet");
        $number = !empty($data['number']) ? $data['number'] : "";
        $content = !empty($data['content']) ? $data['content'] : "";
         
        $user = $this->user_model->findOne(array("email" =>$info['email_user']));
      
        $data_sms=array(
            'brandName' =>$brandname_sms_phonenet ,
            'template' =>$template_phonenet ,
            'number' => $number,
            'content' =>$content 
            
             );
       
          $res= $this->api_phonenet('POST',json_encode($data_sms),'/sms');
          if(isset($res->sendError) && !$res->sendError)
         {
          $data['response_update']=$res;
           $data['type']='SMS_VOICE';
        $this->log_user_phonenet($data);
       
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Gửi SMS VOICE thành công",
            'data' => $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
       }else{
        $data['response_update']=$res;
           $data['type']='SMS_VOICE';
        $this->log_user_phonenet($data);
        $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "Gửi SMS VOICE lỗi",
            'data_reques' => json_encode($data_sms),
            'data_res' =>  $res
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
       }
    }
    public function log_user_phonenet($data){
        $code = !empty($data['code']) ? $data['code'] : "";
        $user_phonenet = $this->user_phonenet_model->findOne(array("code" => $code));
        $user_phonenet['id'] = (string)$user_phonenet['_id'];
        unset($user_phonenet['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $user_phonenet,
            "type" => 'user_phonenet'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

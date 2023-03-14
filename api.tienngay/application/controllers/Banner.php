<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Banner extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('banner_model');
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
        $banners = $this->banner_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banners
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $banners = $this->banner_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($banners)) {
        	foreach ($banners as $sto) {
        		$sto['banner_id'] = (string)$sto['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banners
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $banner = $this->banner_model->find_where('status', ['active']);
        if (!empty($banner)) {
        	foreach ($banner as $s) {
				$s['id'] = (string)$s['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banner
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $banner = $this->banner_model->find_where('status', ['active']);
        if (!empty($banner)) {
            foreach ($banner as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banner
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_banner_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id banner already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $banner = $this->banner_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banner
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_banner_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id banner already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $banner = $this->banner_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $banner
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_banner_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $cfile = new CURLFile($data['image']["tmp_name"],$data['image']["type"],$data['image']["name"]);
        $cfile_mb = new CURLFile($data['image_mb']["tmp_name"],$data['image_mb']["type"],$data['image_mb']["name"]);
     
        $push_upload = $this->pushUpload($cfile);
        if(is_object($push_upload))
        {
        if($push_upload->code == 200){
            $data['image']=$push_upload->path;
            }else{
            $data['image']="";
            }
        }else{
            $data['image']="";
        }
         $push_upload_mb = $this->pushUpload($cfile_mb);
        if(is_object($push_upload_mb))
        {
        if($push_upload_mb->code == 200){
            $data['image_mb']=$push_upload_mb->path;
            }else{
            $data['image_mb']="";
            }
        }else{
            $data['image_mb']="";
        }
        $this->banner_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create banner success",
            'data'=>$push_upload
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_banner_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->banner_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_banner($data);
        unset($data['id']);
        if(!empty($data['image']["tmp_name"]))
        {
          $cfile = new CURLFile($data['image']["tmp_name"],$data['image']["type"],$data['image']["name"]);
        $push_upload = $this->pushUpload($cfile);
          if(is_object($push_upload))
        {
        if($push_upload->code == 200){
            $data['image']=$push_upload->path;
            }else{
            unset($data['image']);
            }
        }else{
            unset($data['image']);
        }
       }else{ unset($data['image']); }

        if(!empty($data['image_mb']["tmp_name"]))
        {
          $cfile_mb = new CURLFile($data['image_mb']["tmp_name"],$data['image_mb']["type"],$data['image_mb']["name"]);
        $push_upload_mb = $this->pushUpload($cfile_mb);
          if(is_object($push_upload_mb))
        {
        if($push_upload_mb->code == 200){
            $data['image_mb']=$push_upload_mb->path;
            }else{
            unset($data['image_mb']);
            }
        }else{
            unset($data['image_mb']);
        }
       }else{ unset($data['image_mb']); }


        //$data
        $this->banner_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update banner success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
 private function pushUpload($cfile){
        $serviceUpload = $this->config->item("url_service_upload");
        // $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
        $post = array('avatar'=> $cfile );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$serviceUpload);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($ch);
        curl_close ($ch);
        $result1 = json_decode($result);
        return $result1;
    }
    public function log_banner($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $banner = $this->banner_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $banner['id'] = (string)$banner['_id'];
        unset($banner['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $banner,
            "type" => 'banner'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

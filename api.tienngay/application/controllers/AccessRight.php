<?php 
   
/*    
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class AccessRight extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("access_right_model");
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $headers = $this->input->request_headers();
        $this->dataPost = $this->input->post();
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
                if($this->dataPost['type'] == 1) $this->app_login['token_web'] = $headers_item;
                if($this->dataPost['type'] == 2) $this->app_login['token_app'] = $headers_item;
                unset($this->dataPost['type']);
                $count_account = $this->user_model->count($this->app_login);
                $this->flag_login = 'success';
                if ($count_account != 1) $this->flag_login = 2;
                if ($count_account == 1){
                    $this->info = $this->user_model->findOne($this->app_login);
                    $this->id = $this->info['_id'];
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost;
    
    public function get_all_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $accessRights = $this->access_right_model->find_where(array("status" => "active"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $accessRights
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function create_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        if(empty($this->dataPost['name'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Count name
        $count = $this->access_right_model->count(array("name" => $this->dataPost['name']));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->access_right_model->insert($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create access right success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        //Count name
        $db = $this->access_right_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if(!empty($db) && !empty($this->dataPost['name']) && $this->dataPost['name'] !== $db['name']) {
            $count = $this->access_right_model->count(array("name" => $this->dataPost['name'], "status"=>"active"));
            if($count > 0) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Name already exists"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        $id = $this->dataPost['id'];
        unset($this->dataPost['id']);
        $this->access_right_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $this->dataPost
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function delete_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->access_right_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            array("status" => "deactive")
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Delete success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $datas = $this->access_right_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $datas
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $datas = $this->access_right_model->find_where_select($data, array("_id", "name", "slug"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $datas
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
   
}
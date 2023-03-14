<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
//require_once APPPATH . 'libraries/AccessRight.php';
use Restserver\Libraries\REST_Controller;

class Menu extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("menu_model");
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
                    $this->ulang = !empty($this->info['lang']) ? $this->info['lang'] : "english";
                    $this->uemail = $this->info['email'];
                    $this->is_superadmin = !empty($this->info['is_superadmin']) ? $this->info['is_superadmin'] : 0;
                }
            }
        }
    }
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $is_superadmin;
    
    public function get_menu_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        $data = $this->input->post();
        $language = !empty($data['language']) ? $data['language'] :  "english";
        
        $menus = $this->menu_model->find_where_order_by(
            array("status" => "active"),
            array("name" => "ESC")
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menus,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_menu_all_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        
        $menus = $this->menu_model->find_where(array("status" => "active"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menus
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $menu = $this->menu_model->find_where($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menu
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_one_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
            $menu = $this->menu_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menu
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function create_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        //Count name
        $count = $this->menu_model->count(array("name" => $data['name']));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->menu_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create menu success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        //Count name
        $menu = $this->menu_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($data['id'])));
        if(!empty($menu) && !empty($data['name']) && $data['name'] !== $menu['name']) {
            $count = $this->menu_model->count(array("name" => $data['name'], "status"=>"active"));
            if($count > 0) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Name already exists"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        $id = $data['id'];
        unset($data['id']);
        $this->menu_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update menu success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function count_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $count = $this->menu_model->count($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            "count"=> $count
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $menus = $this->menu_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menus
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function find_where_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $menus = $this->menu_model->find_where_in($data['where'], $data['fields'], convertToMongoObject($data['in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $menus
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $users = $this->menu_model->find_where_select($data, array("_id", "name", "parent_id", "parent_name"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $users
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
}

<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class DepreciationProperty extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("depreciation_property_model");
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
    
    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login, $dataPost;
    
    public function get_data_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $datas = $this->depreciation_property_model->find_where(array("status" => "active"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $datas
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function create_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $dataPost = $this->input->post();
        //Count name
        $count = $this->depreciation_property_model->count(array("name" => $dataPost['name']));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists",
                "count" => $count
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        unset($dataPost['type']);
        $this->depreciation_property_model->insert($dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $dataPost = $this->input->post();
        if(empty($dataPost['name'])) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
        //Count name
        $db = $this->depreciation_property_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($dataPost['id'])));
        if(!empty($db) && !empty($dataPost['name']) && $dataPost['name'] !== $db['name']) {
            $count = $this->depreciation_property_model->count(array("name" => $dataPost['name'], "status"=>"active"));
            if($count > 0) {
                $response = array(
                    'status' => REST_Controller::HTTP_UNAUTHORIZED,
                    'message' => "Name already exists"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
        }
        $id = $dataPost['id'];
        unset($dataPost['id']);
        $this->depreciation_property_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $dataPost
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
        $dataPost = $this->input->post();
        $id = $dataPost['id'];
        unset($dataPost['id']);
        $this->depreciation_property_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $dataPost
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Delete success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }   
}
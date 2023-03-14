<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Restserver\Libraries\REST_Controller;

include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
class GroupRole extends REST_Controller {
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("group_role_model");
        $this->load->model("log_model");
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
                    //"is_superadmin" => 1
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
    
    private $dataPost, $createdAt, $id;
    
    public function create_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
        if(empty($name)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Count by name
        $count = $this->group_role_model->count(array("name" => $name));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Insert log
        $type = "group_role";
        $action = "create";
        $new = $this->dataPost;
        $this->log_model->insertLog($type, $action, "", $new, $this->createdAt, (string)$this->id);
        $this->group_role_model->insert($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create role success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function update_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $name = !empty($this->dataPost['name']) ? $this->dataPost['name'] : "";
        if(empty($name)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name can not empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $role_id = !empty($this->dataPost['role_id']) ? $this->dataPost['role_id'] : "";
        $old = $this->group_role_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($role_id)));
        unset($this->dataPost['role_id']);
        $this->group_role_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($role_id)),
            $this->dataPost
        );
        //Insert log
        $type = "group_role";
        $action = "update";
        $new = $this->dataPost;
        $this->log_model->insertLog($type, $action, $old, $new, $this->createdAt, (string)$this->id);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update role success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function delete_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $this->group_role_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            array("status" => "deactive",)
        );
        //Insert log
        $type = "group_role";
        $action = "delete";
        $this->log_model->insertLog($type, $action, "", "", $this->createdAt, (string)$this->id);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Delete role success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_one_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $role = $this->group_role_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        if(empty($role)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Role is not exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $role
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_all_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
        if (!empty($groupRoles)) {
        	foreach ($groupRoles as $g) {
        		$g['group_role_id'] = (string)$g['_id'];
			}
		}
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $groupRoles
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_group_role_by_user_post() {
        $flag = notify_token($this->flag_login);       
        if ($flag == false) return;
        $userId = $this->dataPost['user_id'];
        if(empty($userId)) return;
        $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
        $arr = array();
        foreach($groupRoles as $groupRole) {
            if(empty($groupRole['users'])) continue;
            foreach($groupRole['users'] as $item) {
                if(key($item) == $userId) {
                    array_push($arr, (string)$groupRole['_id']);
                    continue;
                }
            }
        }
        $response1 = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $arr
        );
        $this->set_response($response1, REST_Controller::HTTP_OK);
    }
    
    public function getGroupRole_post() {
        $flag = notify_token($this->flag_login);       
        if ($flag == false) return;
        $userId = $this->dataPost['user_id'];
		$groupRoles = $this->group_role_model->find_where(array("status" => "active"));
		$arr = array();
		foreach($groupRoles as $groupRole) {
			if(empty($groupRole['users'])) continue;
			foreach($groupRole['users'] as $item) {
				if(key($item) == $userId) {
					array_push($arr, $groupRole['slug']);
					continue;
				}
			}
		}
        $response1 = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $arr
        );
        $this->set_response($response1, REST_Controller::HTTP_OK);
    }
    
}

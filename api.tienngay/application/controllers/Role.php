<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Restserver\Libraries\REST_Controller;

include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
class Role extends REST_Controller {
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("role_model");
        $this->load->model("log_model");
        $this->load->model("user_model");
        $this->load->model("group_role_model");
        $this->load->model("store_model");
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
        $count = $this->role_model->count(array("name" => $name));
        if($count > 0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Name already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Insert log
        $type = "role";
        $action = "create";
        $new = $this->dataPost;
        $this->log_model->insertLog($type, $action, "", $new, $this->createdAt, (string)$this->id);
        $this->role_model->insert($this->dataPost);
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
        $old = $this->role_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($role_id)));
        unset($this->dataPost['role_id']);
        $this->role_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($role_id)),
            $this->dataPost
        );
        //Insert log
        $type = "role";
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
        $this->role_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])),
            array("status" => "deactive",)
        );
        //Insert log
        $type = "role";
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
        $role = $this->role_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
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
        $role = $this->role_model->find_where(array("status" => "active"));
       
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $role
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_role_by_user_post() {
        $flag = notify_token($this->flag_login);       
        if ($flag == false) return;
        $userId = $this->dataPost['user_id'];
        $response = $this->role_model->getRoleByUserId($userId);
        $response1 = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $response
        );
        $this->set_response($response1, REST_Controller::HTTP_OK);
    }


    public function getUserByEmail_Ksnb_post(){
        //$email = $this->uemail;
        $arrUser = [];
        $searchEmail = $this->dataPost['email'];
        if (empty($searchEmail)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Lấy dữ liệu thất bại ",
				'data' => []
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
        }
        $currentUser = $this->user_model->findOne(['status' => 'active', "email" => $searchEmail]);
        $userId = (String)$currentUser["_id"];
        $groupRoles = $this->group_role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true]]);
        $isASM = false;
        $isCHT = false;
        $isADMIN = false;
        $isVH = false;
        $isHS = false;
        $isQLCC = false;
        $isKSNB = false;
        foreach ($groupRoles as $role) {
            if ($role["slug"] == "quan-ly-khu-vuc") {
                $isASM = true;
            }
            if ($role["slug"] == "cua-hang-truong") {
                $isCHT = true;
            }
            if ($role["slug"] == "van-hanh") {
                $isVH = true;
            }
            if ($role["slug"] == "super-admin") {
                $isADMIN = true;
            }
            if ($role["slug"] == "hoi-so") {
                $isHS = true;
            }
            if ($role["slug"] == "quan-ly-cap-cao") {
                $isQLCC = true;
            }
            if ($role["slug"] == "kiem-soat-noi-bo"){
            	$isKSNB = true;
            }
        }
        if ($isVH || $isADMIN || $isQLCC || $isHS || $isKSNB) {
            //lấy hết mọi file ghi âm
            $response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Lấy dữ liệu thành công",
				'data' => $arrUser
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
        }
        if ($isASM) {
            //nếu là asm trả về hết các cua hàng trưởng và cả giao dịch viên
            $userRole = $this->role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true], "stores" => ['$ne' => ""]]);
            $stores = [];
            foreach ($userRole as $key => $role) {
                $roleStores = json_decode(json_encode($role["stores"]), true);
                foreach($roleStores as $key => $value) {
                    foreach($value as $k => $v) {
                        $stores[] = $k;
                    }
                }
            }
            foreach ($stores as $value) {
                $pgd = $this->role_model->find_where(['status' => 'active', "stores.$value" => ['$exists' => true], "stores" => ['$ne' => ""]]);
                foreach($pgd as $v) {
                    $roleStores = json_decode(json_encode($v["stores"]), true);
                    if (count($roleStores) > 1) {
                        continue;
                    }

                    $users = json_decode(json_encode($v["users"]), true);
                    foreach($users as $user) {
                        foreach($user as $u) {
                            if (!isset($arrUser[$u["email"]])) {
                                $arrUser[] = $u["email"];
                            }
                        }
                    }
                }
            }
            if (count($arrUser) == 0) {
                return $searchEmail;
            }
			 $response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Lấy dữ liệu thành công asm",
				'data' => $arrUser
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
//            return $arrUser;
        }
        if ($isCHT) {
            // nếu là cửa hàng trưởng, trả về các thành viên trong cửa hàng quản lý.
            $userRole = $this->role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true], "stores" => ['$ne' => ""]]);
            foreach ($userRole as $key => $role) {
                $roleStores = json_decode(json_encode($role["stores"]), true);
                if (count($roleStores) > 1) {
                    continue;
                }
                foreach($role->users as $users) {
                    foreach($users as $user) {
                        $arrUser[] = $user->email;
                    }
                }
            }
            if (count($arrUser) == 0) {
                return $searchEmail;
            }
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Lấy dữ liệu thành công cht",
				'data' => $arrUser
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
//            return $arrUser;
        }
            //Khác 6 Th
            // chỉ xem được file ghi âm của current user
//            return [$this->uemail];
			$response = array(
				'status' => REST_Controller::HTTP_OK,
				'message' => "Lấy dữ liệu thành công cht",
				'data' => [$searchEmail]
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;


    }



    public function getEmailCht_post() 
    {
        $data = $this->input->post();
        $email = !empty($data['email']) ? $data['email'] : "";
        $arrUser = [];
        $currentUser = $this->user_model->findOne(['status' => 'active', "email" => $email]);   
        $userId = (String)$currentUser["_id"];
        $storeRole = $this->role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true], "stores" => ['$ne' => ""]]);
        //lấy hết email trong cửa hàng
        foreach($storeRole as $key => $users) {
            $roleUser = json_decode(json_encode($users["users"]), true);
            foreach($roleUser as $key => $value) {
                foreach($value as $k => $v) {
                    foreach($v as $c) {
                        $arrUser[] = $c;
                    }
                }
            }
        }
        $isCHT = $this->role_model->find_where(['status' => 'active', 'slug' => 'cua-hang-truong']);
        $arrCHT = [];
        //lấy hết email CHT
        foreach($isCHT as $cht) {
            $roleCHT = json_decode(json_encode($cht["users"]), true);
            foreach($roleCHT as $key => $value) {
                foreach($value as $k => $v) {
                    foreach($v as $c) {
                        $arrCHT[] = $c;
                    }
                }
            }
        }
        //lấy ra email CHT quản lý nv vi phạm
        $getCHT = array_uintersect($arrUser, $arrCHT, "strcasecmp");
        return $getCHT;
    }

    public function getEmailAsm_post() {
        $data = $this->input->post();
        $email = !empty($data['email']) ? $data['email'] : "";
        $currentUser = $this->user_model->findOne(['status' => 'active', "email" => $email]);   
        $userId = (String)$currentUser["_id"];
        $storeRole = $this->role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true], "stores" => ['$ne' => ""]]);
        foreach($storeRole as $key => $store) {
            $roleStore = json_decode(json_encode($store['stores']), true);
            foreach($roleStore as $key => $value) {
                foreach($value as $k => $c) {
                   $arrIdStore = $k;
                }
            }
        }
        $code_area = $this->store_model->findOne(['_id' => new \MongoDB\BSON\ObjectId($arrIdStore)]);
        $emailAsm = [];
        if (!empty($code_area) ) {
            if($code_area['code_area'] == 'KV_HN1' || $code_area['code_area'] == 'KV_HN2') {
                $emailAsm = $this->getUserEmailASM('asm-hn1');
            }
            else if ($code_area['code_area'] == 'KV_HCM1' || $code_area['code_area'] == 'KV_HCM2' ||$code_area['code_area'] == 'KV_MK') {
                $emailAsm = $this->getUserEmailASM('asm-hcm1');
               
            }
            else if ($code_area['code_area'] == 'KV_QN') {
                $emailAsm = $this->getUserEmailASM('asm-qn');

            } 
        }
        $response = [
            'status' => REST_Controller::HTTP_OK,
            "message" => "Thành công",
            "data" => $emailAsm
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
        return ;
    }

    public function getUserEmailASM($slug)
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == $slug)) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
                        foreach($item as $a) {
                            if($this->user_model->findOne(['status' => 'active', 'email' => $a])) {
                                array_push($data, $a);
                            }
                        }
					}
				}
			}
		}
		return $data;
	}

    public function getAllEmailKsnb_post()
    {
        $user = [];
        $result = $this->group_role_model->find_where(['slug' => 'kiem-soat-noi-bo', 'status' => 'active']);
        if ($result) {
            foreach ($result as $array => $arr) {
                $role =  json_decode(json_encode($arr["users"]), true);
                foreach ($role as $key => $value) {
                    foreach ($value as $k => $v) {
                        foreach($v as $c => $a) {
                            $user[] = $a;
                        }
                    }
                }
            }

        }
        $response = [
            'status' => REST_Controller::HTTP_OK,
            "message" => "Thành công",
            "data" => $user
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    //gui email core.tienngay moduls reportsksnb
    public function sendEmailConfrim_post() {
        $code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
        $user_name = !empty($this->dataPost['user_name']) ? $this->dataPost['user_name'] : "";
        $user_email = !empty($this->dataPost['user_email']) ? $this->dataPost['user_email'] : "";
        $store_name = !empty($this->dataPost['store_name']) ? $this->dataPost['store_name'] : "";
        $code_error = !empty($this->dataPost['code_error']) ? $this->dataPost['code_error'] : "";
        $type = !empty($this->dataPost['type']) ? $this->dataPost['type'] : "";
        $punishment = !empty($this->dataPost['punishment']) ? $this->dataPost['punishment'] : "";
        $discipline = !empty($this->dataPost['discipline']) ? $this->dataPost['discipline'] : "";
        $url = !empty($this->dataPost['urlItem']) ? $this->dataPost['urlItem'] : "";
        $comment = !empty($this->dataPost['comment']) ? $this->dataPost['comment'] : "";
        $infer = !empty($this->dataPost['infer']) ? $this->dataPost['infer'] : "";
        $description = !empty($this->dataPost['description']) ? $this->dataPost['description'] : "";
        $send_user = !empty($this->dataPost['created_by']) ? $this->dataPost['created_by'] : "";
        $user_nv = !empty($this->dataPost['user_nv']) ? $this->dataPost['user_nv'] : "";
        $urlImg = !empty($this->dataPost['urlImg']) ? $this->dataPost['urlImg'] : "";
        $ksnb_comment = !empty($this->dataPost['ksnb_comment']) ? $this->dataPost['ksnb_comment'] : "";
        $position = !empty($this->dataPost['position']) ? $this->dataPost['position'] : "";
        $reason_not_confirm = !empty($this->dataPost['reason_not_confirm']) ? $this->dataPost['reason_not_confirm'] : "";

        if(!$store_name || !$code_error || !$type || !$punishment || !$discipline) {
            $response = [
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                "message" => "Gửi email thất bại",
                "data" => [
                    'user_name' => $user_name,
                    'user_email' => $user_email,
                    'store_name' => $store_name,
                    'code_error' => $code_error,
                    'type' => $type,
                    'punishment' => $punishment,
                    'discipline' => $discipline,
                    'urlItem' => $url,
                    'description' => $description,
                ]
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        try{

            $dataEmail = [
                "code" => $code,
                "urlItem" => $url,
                "user_name" => $user_name,
                'user_nv' => $user_nv,
                "store_name" => $store_name,
                "code_error" => $code_error,
                "type" => $type,
                "punishment" => $punishment,
                "discipline" => $discipline,
                "comment" => $comment,
                "infer" => $infer,
                'send_user' => $send_user,
                'description' => $description,
                // 'description_error' => $description_error,
                // "created_by" => "Kiểm soát nội bộ VFC"
                'urlImg' => $urlImg,
                'ksnb_comment' => $ksnb_comment,
                "position" => $position,
                "reason_not_confirm" => $reason_not_confirm,
            ];

            $dataEmail['user_email'] = $user_nv;
			foreach ($user_email as $key => $value) {
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
			}
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Gửi email thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		} catch (Exception $e) {
			$response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Gửi email thất bại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		}

    }

	public function sendEmailEndTime_post() {
        $code = !empty($this->dataPost['code']) ? $this->dataPost['code'] : "";
        $user_name = !empty($this->dataPost['user_name']) ? $this->dataPost['user_name'] : "";
        $user_email = !empty($this->dataPost['user_email']) ? $this->dataPost['user_email'] : "";
        $store_name = !empty($this->dataPost['store_name']) ? $this->dataPost['store_name'] : "";
        $code_error = !empty($this->dataPost['code_error']) ? $this->dataPost['code_error'] : "";
        $type = !empty($this->dataPost['type']) ? $this->dataPost['type'] : "";
        $punishment = !empty($this->dataPost['punishment']) ? $this->dataPost['punishment'] : "";
        $discipline = !empty($this->dataPost['discipline']) ? $this->dataPost['discipline'] : "";
        $url = !empty($this->dataPost['urlItem']) ? $this->dataPost['urlItem'] : "";
        $comment = !empty($this->dataPost['comment']) ? $this->dataPost['comment'] : "";
        $infer = !empty($this->dataPost['infer']) ? $this->dataPost['infer'] : "";
        $description = !empty($this->dataPost['description']) ? $this->dataPost['description'] : "";
        $send_user = !empty($this->dataPost['created_by']) ? $this->dataPost['created_by'] : "";
        $user_nv = !empty($this->dataPost['user_nv']) ? $this->dataPost['user_nv'] : "";
        $urlImg = !empty($this->dataPost['urlImg']) ? $this->dataPost['urlImg'] : "";
        $ksnb_comment = !empty($this->dataPost['ksnb_comment']) ? $this->dataPost['ksnb_comment'] : "";
        $position = !empty($this->dataPost['position']) ? $this->dataPost['position'] : "";

        if(!$store_name || !$code_error || !$type || !$punishment || !$discipline) {
            $response = [
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                "message" => "Gửi email thất bại",
                "data" => [
                    'user_name' => $user_name,
                    'user_email' => $user_email,
                    'store_name' => $store_name,
                    'code_error' => $code_error,
                    'type' => $type,
                    'punishment' => $punishment,
                    'discipline' => $discipline,
                    'urlItem' => $url,
                    'description' => $description,
                ]
            ];
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        try{

            $dataEmail = [
                "code" => $code,
                "urlItem" => $url,
                "user_name" => $user_name,
                'user_nv' => $user_email,
                "store_name" => $store_name,
                "code_error" => $code_error,
                "type" => $type,
                "punishment" => $punishment,
                "discipline" => $discipline,
                "comment" => $comment,
                "infer" => $infer,
                'send_user' => $send_user,
                'description' => $description,
                'urlImg' => $urlImg,
                'ksnb_comment' => $ksnb_comment,
                "position" => $position,
            ];

			$user_email = array($user_nv);
			foreach ($user_email as $key => $value) {
				$dataEmail['email'] = $value;
				$result = $this->user_model->send_Email($dataEmail);
			}
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'message' => "Gửi email thành công"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		} catch (Exception $e) {
			$response = array(
                'status' => REST_Controller::HTTP_BAD_REQUEST,
                'message' => "Gửi email thất bại"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
		}

    }

    //get user hcns
	public function getUserHcns_post()
	{
		$roles = $this->role_model->find_where(['status' => 'active']);
		$data = [];
		foreach ($roles as $key => $role) {
			if (!empty($role['users']) && ($role['slug'] == "hcns-hn")) {
				foreach ($role['users'] as $key1 => $user) {
					foreach ($user as $key2 => $item) {
						foreach ($item as $e) {
							array_push($data, $e);
						}
					}
				}
			}
		}
        foreach ($roles as $key3 => $hcnsmk) {
			if (!empty($hcnsmk['users']) && ($hcnsmk['slug'] == "hcns-mekong")) {
				foreach ($hcnsmk['users'] as $key4 => $usermk) {
					foreach ($usermk as $key5 => $item1) {
						foreach ($item1 as $mk) {
							array_push($data, $mk);
						}
					}
				}
			}
		}
        foreach ($roles as $key6 => $hcnshcm) {
			if (!empty($hcnshcm['users']) && ($hcnshcm['slug'] == "hcns-hcm")) {
				foreach ($hcnshcm['users'] as $key7 => $userhcm) {
					foreach ($userhcm as $key8 => $item2) {
						foreach ($item2 as $hcm) {
							array_push($data, $hcm);
						}
					}
				}
			}
		}
        $response = [
            'status' => REST_Controller::HTTP_OK,
            "message" => "Thành công",
            "data" => $data
        ];
        $this->set_response($response, REST_Controller::HTTP_OK);
        return ;
	}

}

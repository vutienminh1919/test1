<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;
class Recording extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('recording_model');
        $this->load->model("group_role_model");
        $this->load->model('role_model');
        $this->load->model('log_model');
        $this->load->model('lead_model');
        $this->load->model('contract_model');
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
        $recordings = $this->recording_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recordings
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $recordings = $this->recording_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($recordings)) {
            foreach ($recordings as $sto) {
                $sto['recording_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recordings
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_count_all_post(){
        $recording = $this->recording_model->find_where_count('status', ['active','deactive']);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recording
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_reccord_by_phone_number_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		//5ea1b686d6612bdf6c0422af telesales
		$data = $this->input->post();
		$users_tele_sale = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af'));
//		$recording = $this->recording_model->find_where_not_in(array('toNumber'=>$data['phone_number']), 'fromUser.email' , $users_tele_sale);
        $recording = $this->recording_model->find_where(array('toNumber'=>$data['phone_number']));
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $recording
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
           //5de72198d6612b4076140606 super admin
            //5de726a8d6612b6f2b431749 Van hanh
            //5de726c9d6612b6f2a617ef5 CHT
            //5de726e4d6612b6f2c310c78 GDV
            //5de726fcd6612b77824963b9 Ke Toan
            //5def671dd6612b75532960c5 Hoi so
            //5ea1b6d2d6612b65473f2b68 marketing
            //5ea1b6abd6612b6dd20de539 thu-hoi-no
            //5ea1b686d6612bdf6c0422af telesales
        $users_by_store=array();
        $users_by_role=array();
         $data = $this->input->post();
         $condition = !empty($data['condition']) ? $data['condition'] : array();
        $groupRoles = $this->getGroupRole($this->id);
        $inboundCall = $this->inboundCallCskh();
       //  if(in_array('tbp-cskh', $groupRoles))
       //  $users_by_role = $this->getUserGroupRole(array('5ea1b686d6612bdf6c0422af'));

       //  if(in_array('tbp-thu-hoi-no', $groupRoles))
       //  $users_by_role = $this->getUserGroupRole(array('5ea1b6abd6612b6dd20de539'));

        $array_user = array();
         if(in_array('cua-hang-truong', $groupRoles))
         {
            $storeId=$this->getStores($this->id)[0];
       $array_user = $this->getUserbyStores_email($storeId);
      }
		$per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
		$email = isset($data['email']) ? array($data['email']) : array();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] :  "";
		$missed = !empty($data['missed']) ? $data['missed'] :  "";
		$arr_store_hotline = !empty($data['arr_store_hotline']) ? $data['arr_store_hotline'] :  array();
		$cskh = !empty($data['cskh']) ? $data['cskh'] : "";
		$sdt = !empty($data['sdt']) ? $data['sdt'] : "";
		$email_nv = !empty($data['email_nv']) ? $data['email_nv'] : "";
        $user_name = !empty($data['user_name']) ? ucwords($data['user_name']) : "";
        $phone_name = !empty($data['phone_name']) ? ($data['phone_name']) : "";
		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start).' 00:00:00')*1000,
				'end' => strtotime(trim($end).' 23:59:59')*1000
			);
		} else if(!empty($start)) {
            $condition = array(
                'start' => strtotime(trim($start).' 00:00:00')*1000,
            );
        } else if (!empty($end)) {
            $condition = array(
                'end' => strtotime(trim($end).' 23:59:59')*1000,
            );
        }

		if (!empty($sdt)) {
			$condition['sdt'] = $sdt;
		}
		$check = $this->getUserByEmail();
	    if (!empty($check)) {
            $condition['email'] = $check;
        }
        //search_email_nhan_vien
		if (!empty($email_nv)) {
            if (empty($condition['email'])) {
                $condition['email'] = [$email_nv];
            } else {
                if (in_array($email_nv, $condition['email']) || in_array($this->uemail, $inboundCall)) {
                    $condition['email'] = [$email_nv];
                    $condition['email_nv'] = $email_nv;
                } else {
                    $condition['email'] = false;
                }
            }    
		}
        //nếu là yenpth@tienngay.vn, trả về all_miss_call
        if (in_array($this->uemail, $inboundCall)) {
            $condition['inbound'] = $inboundCall;
        }
        //var_dump($condition["email"]); die;
		if (!empty($groupRoles)) {
			$condition['groupRoles'] = $groupRoles;
		}
		if (!empty($array_user)) {
			$condition['array_user'] = $array_user ;
		}
		if (!empty($cskh)) {
			$condition['cskh'] = $cskh;
		}
		if (!empty($missed)) {
			$condition['missed'] = $missed;
		}
		if (!empty($missed)) {
			$condition['missed'] = $missed;
		}
        //search_name_customer_by_phone_number
        if (!empty($user_name)) {
           $phone = $this->getPhoneByName($user_name);
           $condition['phone'] = $phone;
        }
		if (!empty($phone_name)) {
			$condition['phone_name'] = $phone_name;
		}
        //bỏ code bên dưới cho cal_cskh
		// if((in_array('giao-dich-vien', $groupRoles) && !in_array('cua-hang-truong', $groupRoles)) || (in_array('telesales', $groupRoles) && !in_array('tbp-cskh', $groupRoles))) {
		// 	$condition['email_nv'] = $this->uemail;
		// }
        if(in_array('telesales', $groupRoles)){
            $condition['is_cskh'] = true;
        }else{
            $condition['is_cskh'] = false;

        }

        $condition['arr_store_hotline'] = $arr_store_hotline;
        
        $recording = $this->recording_model->getGet_pt($condition, $per_page , $uriSegment);
        $condition['count'] = 'ok';
        $total = $this->recording_model->getGet_pt($condition);

        if (!empty($recording)){
        	foreach ($recording as $value){
				$arr_code_contract = [];
        		$customer_name_contract = $this->contract_model->find_where_recording($value['toNumber']);

        		if (!empty($customer_name_contract)){
        			$value->customer_name = $customer_name_contract[0]['customer_infor']['customer_name'];
					foreach ($customer_name_contract as $item){
						array_push($arr_code_contract, $item['code_contract_disbursement']);
					}
					$value->code_contract_disbursement = $arr_code_contract;
				}

			}
		}

        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $recording,
            'count' => $total,
            'groupRoles'=> $groupRoles
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){

        $recording = $this->recording_model->find_where('status', ['active']);
        if (!empty($recording)) {
            foreach ($recording as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recording
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_recording_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id recording already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $recording = $this->recording_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recording
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_recording_post(){

        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id recording already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $recording = $this->recording_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $recording
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_recording_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        if(isset($data['code']))
        {
        $data['code'] = $this->security->xss_clean($data['code']);
        $data['host'] = $this->security->xss_clean($data['host']);
        $data['client'] = $this->security->xss_clean($data['client']);
        $data['gateway'] = $this->security->xss_clean($data['gateway']);
        $data['phoneNumber'] = $this->security->xss_clean($data['phoneNumber']);
        $data['fromUser'] = $this->security->xss_clean($data['fromUser']);
        $data['toUser'] = $this->security->xss_clean($data['toUser']);
        $data['fromGroup'] = $this->security->xss_clean($data['fromGroup']);
        $data['toGroup'] = $this->security->xss_clean($data['toGroup']);
        $data['fromCallId'] = $this->security->xss_clean($data['fromCallId']);
        $data['toCallId'] = $this->security->xss_clean($data['toCallId']);
        $data['direction'] = $this->security->xss_clean($data['direction']);
        $data['fromExt'] = $this->security->xss_clean($data['fromExt']);
        $data['fromNumber'] = $this->security->xss_clean($data['fromNumber']);
        $data['toNumber'] = $this->security->xss_clean($data['toNumber']);
        $data['startTime'] = $this->security->xss_clean($data['startTime']);
        $data['answerTime'] = $this->security->xss_clean($data['answerTime']);
        $data['endTime'] = $this->security->xss_clean($data['endTime']);
        $data['duration'] = $this->security->xss_clean($data['duration']);
        $data['billDuration'] = (int)$this->security->xss_clean($data['billDuration']);
        $data['hangupCause'] = $this->security->xss_clean($data['hangupCause']);
        $data['createTime'] = $this->security->xss_clean($data['createTime']);
        $data['recording'] = $this->security->xss_clean($data['recording']);
        $data['recordingDownloaded'] = $this->security->xss_clean($data['recordingDownloaded']);
        $data['recordingSize'] = $this->security->xss_clean($data['recordingSize']);
        $data['recordingDeleted'] = $this->security->xss_clean($data['recordingDeleted']);
        $data['fromContact'] = $this->security->xss_clean($data['fromContact']);
        $data['toContact'] = $this->security->xss_clean($data['toContact']);
        $data['ticketLog'] = $this->security->xss_clean($data['ticketLog']);

         }
		$data['billDuration'] = (int)$data['billDuration'];


         $recording = $this->recording_model->findOne(array("code" => $data['code']));
         if(empty($recording))
        {
        $this->recording_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create recording success",
            'data'=>$data
        );
        }else{
            $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create faild",
            'data'=>$data
        );

        }
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_recording_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->recording_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_recording($data);
        unset($data['id']);

        $this->recording_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update recording success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_recording($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $recording = $this->recording_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $recording['id'] = (string)$recording['_id'];
        unset($recording['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $recording,
            "type" => 'recording'

        );
        $this->log_model->insert($dataInser);
    }
    private function getUserGroupRole($GroupIds) {
        $arr = array();
        foreach ($GroupIds as $groupId) {
            $groups = $this->group_role_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId($groupId)));
            foreach ($groups['users'] as $item) {
                foreach ($item as $key => $value) {
                     $arr[] = $value->email;
                }

            }
        }
        $arr = array_unique($arr);
        return $arr;
    }
    private function getUserbyStores_email($storeId)
    {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleAllUsers = array();
        if (count($roles) > 0) {
            foreach ($roles as $role) {
                if (!empty($role['stores']) && count($role['stores']) == 1) {
                    $arrStores = array();
                    foreach ($role['stores'] as $item) {
                        array_push($arrStores, key($item));
                    }
                    //Check userId in list key of $users
                    if (in_array($storeId, $arrStores) == TRUE) {
                        if (!empty($role['stores'])) {
                            //Push store
                            foreach ($role['users'] as $key => $item) {
                                array_push($roleAllUsers, $item[key($item)]['email']);
                            }
                        }
                    }
                }
            }
        }
        $roleUsers = array_unique($roleAllUsers);
        return $roleUsers;
    }
    private function getUserbyStores($storeId) {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleAllUsers = array();
        if(count($roles) > 0) {
            foreach($roles as $role) {
                if(!empty($role['stores']) && count($role['stores']) > 0){
                    $arrStores = array();
                    foreach($role['stores'] as $item) {
                        array_push($arrStores, key($item));
                    }
                    //Check userId in list key of $users
                    if(in_array($storeId, $arrStores) == TRUE) {
                        if(!empty($role['stores'])) {
                            //Push store
                            foreach($role['users'] as $key=> $item) {
                                array_push($roleAllUsers, key($item));
                            }
                        }
                    }
                }
            }
        }
        $roleUsers = array_unique($roleAllUsers);
        return $roleUsers;
    }
        private function getGroupRole($userId) {
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
        return $arr;
    }
    private function getStores($userId)
    {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleStores = array();
        if (count($roles) > 0) {
            foreach ($roles as $role) {
                if (!empty($role['users']) && count($role['users']) > 0) {
                    $arrUsers = array();
                    foreach ($role['users'] as $item) {
                        array_push($arrUsers, key($item));
                    }
                    //Check userId in list key of $users
                    if (in_array($userId, $arrUsers) == TRUE) {
                        if (!empty($role['stores'])) {
                            //Push store
                            foreach ($role['stores'] as $key => $item) {
                                array_push($roleStores, key($item));
                            }
                        }
                    }
                }
            }
        }
        return $roleStores;
    }
    //search_name_customer_by_phone_number
    public function getPhoneByName($userName) {
        // $data = $this->input->post();
        // $userName = !empty($data['user_name']) ? $data['user_name'] : "";
        $arrLead = [];
        $userLead = [];
        $userContract = [];
        //get user by Lead table
        $userLead = $this->lead_model->searchByUserName(['fullname' => $userName]);
        if (count($userLead) > 0) {
            foreach ($userLead as $lead) {
                array_push($arrLead, $lead['phone_number']);
            }
        }
        //get user by Contract table
        $userContract = $this->contract_model->find_where(['customer_infor.customer_name' => $userName]);
        if (count($userContract) > 0) {
            foreach ($userContract as $contract) {
            array_push($arrLead, $contract['customer_infor']['customer_phone_number']);
            }
        }    
        return $arrLead;    
    }    
    //check_role_recording_call
    public function getUserByEmail(){
        //$email = $this->uemail;
        $arrUser = [];
        $currentUser = $this->user_model->findOne(['status' => 'active', "email" => $this->uemail]);      
        $userId = (String)$currentUser["_id"];
        $groupRoles = $this->group_role_model->find_where(['status' => 'active', "users.$userId" => ['$exists' => true]]);
        $isASM = false;
        $isCHT = false;
        $isADMIN = false;
        $isVH = false;
        $isHS = false;
        $isQLCC = false;
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
        }
        if ($isVH || $isADMIN || $isQLCC || $isHS) {
            //lấy hết mọi file ghi âm
            return $arrUser;
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
                return [$this->uemail];
            }
            return $arrUser;
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
                return [$this->uemail];
            }
            return $arrUser;
        }
            //Khác 6 TH trên
            // chỉ xem được file ghi âm của current user
            return [$this->uemail];


    }
    //List email cskh xem được inbound  call
    public function inboundCallCskh() {
    $roles = $this->role_model->find_where(['status' => 'active']);
    $data = [];
    foreach ($roles as $key => $role) {
        if (!empty($role['users']) && ($role['slug'] == "view-cuoc-goi-nho-cskh")) {
            foreach ($role['users'] as $key1 => $user) {
                foreach ($user as $key2 => $item) {
                    foreach ($item as $value) {
                        array_push($data, $value);
                    }
                }
            }
        }
    }
    return $data;
  }

	public function insert_note_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$name = !empty($data['name']) ? $data['name'] : "";
		$date = !empty($data['date']) ? $data['date'] : "";
		$address = !empty($data['address']) ? $data['address'] : "";
		$cmt = !empty($data['cmt']) ? $data['cmt'] : "";
		$noteMissedCall = !empty($data['noteMissedCall']) ? $data['noteMissedCall'] : "";
		$note['id'] = (string)$note['_id'];
		unset($note['_id']);
		$dataInser = array(
			"name" => $name,
			"date" => $date,
			"address" => $address,
			'cmt' => $cmt,
			'noteMissedCall'=>$noteMissedCall
		);
		$note = $this->recording_model->update(array("_id" => new MongoDB\BSON\ObjectId($id)), $dataInser);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $dataInser,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function get_one_note_post()
	{
		$data = $this->input->post();
		$id = !empty($data['id']) ? $data['id'] : "";
		$note = $this->recording_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
		 $note['id'] = (string)$note['_id'];
		 $response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $note
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return ;
	}

	public function excel_missed_call_post()
	{
		$condition =[];
		$data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";
		$sdt = !empty($data['sdt']) ? $data['sdt'] : "";

		if (empty($start) && empty($end)){
			$condition = [
			"sdt" => $sdt,
		];

		}elseif (empty($start)){
			$condition = [
			"end" => $end,
			"sdt" => $sdt,
		];

		}elseif (empty($end)){
			$condition = [
			"start" => $start,
			"sdt" => $sdt,
		];
		}
		if (!empty($start) && !empty($end)){
		$condition = [
			"start" => $start,
			"end" => $end,
			"sdt" => $sdt,
		];
		}
		$result = $this->recording_model->get_missed_call_excel($condition);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'message' => 'thành công!',
			'data' => $result,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}

}
?>

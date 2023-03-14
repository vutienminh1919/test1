<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Store extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('store_model');
         $this->load->model('role_model');
        $this->load->model('dashboard_model');
        $this->load->model('store_log_model');
        $this->load->model('contract_model');
        $this->load->model('log_followContract');
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
        $stores = $this->store_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function getStoreIdByName_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $cond= array(
            "name"=>$data['name']
        );
        $stores = $this->store_model->find_where($cond);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $stores = $this->store_model->find_where_select($data, array("_id", "name", "province", "district", "address","code_area"));
        if (!empty($stores)) {
            foreach ($stores as $sto) {
                $sto['store_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $store = $this->store_model->find_where_in('status', ['active','deactive']);
        if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $stores = $this->getStores($this->id);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store,
            'stores' => $stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_store_for_quickloan_post(){
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id store already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $store = $this->store_model->find_where(array('status' => 'active', 'province_id' => $id));
        if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_noheader_post(){
        $store = $this->store_model->find_where(array('status' => 'active'));
        if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

       public function get_all_app_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $store = $this->store_model->find_where(array('status' => 'active'));
        if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_store_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id store already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_store_by_province_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id store already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $store = $this->store_model->findOne(array("province_id" => $id));
         if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
       public function get_store_by_add_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $province_id = !empty($data['province_id']) ? $data['province_id'] : "";
        $district_id = !empty($data['district_id']) ? $data['district_id'] : "";
        $condition=array(
            'province_id'=>$province_id,
            'district_id'=>$district_id
        );
        $store = $this->store_model->getStore_by_add($condition);
         if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_store_by_code_area_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
        $code_area = !empty($data['code_area']) ? $data['code_area'] : "";
       
        $condition=array(
            'code_area'=>$code_area,
            'status'=>'active'
            
        );
        $store = $this->store_model->find_where($condition);
         if (!empty($store)) {
            foreach ($store as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function create_store_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $data['created_at']=$this->createdAt;
        $data['location']['lat']= (float)$data['location']['lat'];
        $data['location']['lng']= (float)$data['location']['lng'];    
        // gen vpbank_code tự động
        $company = !empty($data['company']) ? $data['company'] : "" ;
        if ($company == '1') {
            $stores = $this->store_model->get_vpb_store_code(['vpb_store_code' => '00012']);
        } else {
            $stores = $this->store_model->get_vpb_store_code(['vpb_store_code' => '00015']);
        }
        $max = 0; 
        foreach ($stores as $store) {
            if ((int)$store['vpb_store_code'] > $max) {
                $max = (int)$store['vpb_store_code'];
            }
        }
        $vpb_store_code = '000' . ($max + 1) ;

        $max_store_code = (int)$stores[0]['store_code'];
        foreach ($stores as $st) {
            if ($max_store_code < (int)$st['store_code']) {
                $max_store_code = (int)$st['store_code'];
            }
        }
        $store_code = $max_store_code + 1;
        $data['store_code'] = $store_code;
        $data['vpb_store_code']= $vpb_store_code;    
        //thêm vào trong bảng role phần slug => cong-ty-cpcn-tcv-dong-bac
        $id_store = $this->store_model->insertReturnId($data);
        $results = $this->role_model->findOne(['slug' => 'cong-ty-cpcn-tcv-dong-bac']);
        $pushStore = [
            "$id_store" => [
                "name" => !empty($data["name"]) ? $data["name"] : "",
                "province" => !empty($data["province"]) ? $data["province"] : "",
                "district" => !empty($data["district"]) ? $data["district"] : "",
                "address" => !empty($data["address"]) ? $data["address"] : "",
                "code_area" =>  !empty($data["code_area"]) ? $data["code_area"] : "",
            ]
        ];
        $update = (array) $results["stores"];
        $update[] = $pushStore;
        $results["stores"] = json_decode(json_encode($update, FALSE));
        if ($company == "2") {
            $this->role_model->update(['slug' => 'cong-ty-cpcn-tcv-dong-bac'], $results);
        }
        //Summary dashboard
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create store success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_store_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->store_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại phòng giao dịch nào cần xóa"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_store($data);
       $data['type']=isset($data['type_pgd']) ? $data['type_pgd'] : '';
        unset($data['type_login']);
        unset($data['id']);
      $data['location']['lat']= (float)$data['location']['lat'];
      $data['location']['lng']= (float)$data['location']['lng'];
        $data['updated_at']=$this->createdAt;
        //$data
        $old = $this->store_model->findOneAndupdate(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );

        //Summary dashboard
       // $this->updateDashboard_($old, $data);

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update store success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_store($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $store = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        // foreach($store as $key => $st){
        //     $data[$key."_old"] = $st;
        // }
        $store['id'] = (string)$store['_id'];
        unset($store['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $store
        );
        $this->store_log_model->insert($dataInser);
    }

    private function updateDashboard($data) {
        $dashboard = $this->dashboard_model->find();
        $dataUpdate = array();
        if(empty($dashboard)) return;
        if($data['code_province_store'] != 'HN' && $data['code_province_store'] != 'HCM') {
            $dashboard[0]['province']['other']++;
            $dataUpdate = array(
                'province.other' => $dashboard[0]['province']['other']
            );
        } else {
            $dashboard[0]['province'][$data['code_province_store']]++;
            $dataUpdate = array(
                'province.'.$data['code_province_store'] => $dashboard[0]['province'][$data['code_province_store']]
            );
        }
        $this->dashboard_model->update(
            array("_id" => $dashboard[0]['_id']),
            $dataUpdate
        );
    }

    private function updateDashboard_($old, $new) {
        $dashboard = $this->dashboard_model->find();
        if(empty($dashboard))return;
        if($old['code_province_store'] == $new['code_province_store']) return;
        $dataUpdate = array();
        if($old['code_province_store'] == 'HN') {
            $dashboard[0]['province'][$old['code_province_store']]--;
            if($new['code_province_store'] == 'HCM') {
                $dashboard[0]['province'][$new['code_province_store']]++;
                $dataUpdate = array(
                    'province.'.$old['code_province_store'] => $dashboard[0]['province'][$old['code_province_store']],
                    'province.HCM' => $dashboard[0]['province'][$new['code_province_store']]
                );
            } else {
                $dashboard[0]['province']['other']++;
                $dataUpdate = array(
                    'province.'.$old['code_province_store'] => $dashboard[0]['province'][$old['code_province_store']],
                    'province.other' => $dashboard[0]['province']['other']
                );
            }
        } else if($old['code_province_store'] == 'HCM') {
            $dashboard[0]['province'][$old['code_province_store']]--;
            if($new['code_province_store'] == 'HN') {
                $dashboard[0]['province'][$new['code_province_store']]++;
                $dataUpdate = array(
                    'province.'.$old['code_province_store'] => $dashboard[0]['province'][$old['code_province_store']],
                    'province.HN' => $dashboard[0]['province'][$new['code_province_store']]
                );
            } else {
                $dashboard[0]['province']['other']++;
                $dataUpdate = array(
                    'province.'.$old['code_province_store'] => $dashboard[0]['province'][$old['code_province_store']],
                    'province.other' => $dashboard[0]['province']['other']
                );
            }
        } else {
            //$old['code_province_store'] = 'AG'
            //$new['code_province_store'] = 'HN' or 'HCM'
            $dashboard[0]['province'][$new['code_province_store']]++;
            $dashboard[0]['province']['other']--;
            $dataUpdate = array(
                'province.'.$new['code_province_store'] => $dashboard[0]['province'][$new['code_province_store']],
                'province.other' => $dashboard[0]['province']['other']
            );
        }

        $this->dashboard_model->update(
            array("_id" => $dashboard[0]['_id']),
            $dataUpdate
        );
    }

    public function get_all_web_post(){
          $data = $this->input->post();
        $data['city'] = isset($data['city']) ? $data['city'] : '';
        if(empty($data['city']))
        {
          $store = $this->store_model->find_where_select(
            array('status' => 'active','type'=>'1'),
            array("name", "address", "phone", "location")
        );
        }else{
        $store = $this->store_model->find_where_select(
            array('status' => 'active','province_id'=>$data['city'],'type'=>'1'),
            array("name", "address", "phone", "location")
        );
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $store
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function search_web_post(){
        $data = $this->input->post();
        $data['province_id'] = $this->security->xss_clean($data['province_id']);
        $data['address'] = $this->security->xss_clean($data['address']);
        $condition = array();
        $condition['status'] = "active";
        $condition['type'] = "1";
        if(!empty($data['province_id'])) $condition['province_id'] = $data['province_id'];
        if(!empty($data['address'])) $condition['address'] = new \MongoDB\BSON\Regex(preg_quote($data['address']), 'i');
        $stores = $this->store_model->find_where_select($condition, array("name", "address", "phone"));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

	public function get_store_status_active_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$store = $this->store_model->find_where_in('status', ['active']);
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $store
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}


	public function get_all_store_post(){
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;

		$store = $this->store_model->find_where_in('status', ['active','deactive']);

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $store
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
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

    public function get_all_follow_pgd_post(){

		$roles = $this->role_model->find_where(array("status" => "active"));
		$arr_store = [];

		foreach ($roles as $role) {
			if (!empty($role['stores']) && (count($role['stores']) == 1)) {
				$arr_user = [];

				foreach ($role['users'] as $user) {
					foreach ($user as $key => $item) {
						$arr_user += ["$key" => $item];
					}
				}
				foreach ($role['stores'] as $store) {
					foreach ($store as $key2 => $item) {

						$check_store = $this->store_model->findOne(array("status" => "active", 'type_pgd' => '1', "name" => trim($item['name'])));

						if (!empty($check_store)){
							$arr_store += [ "$key2" => [
								"store_name" => $item['name'],
								"user_store" => $arr_user
							] ];
						}

					}
				}
			}
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $arr_store
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
	}

	public function update_follow_contract_post(){

		$this->dataPost = $this->input->post();
		$this->dataPost['id'] = $this->security->xss_clean($this->dataPost['id']);
		$this->dataPost['follow_email'] = $this->security->xss_clean($this->dataPost['follow_email']);
		$this->dataPost['follow_idStore'] = $this->security->xss_clean($this->dataPost['follow_idStore']);
		$this->dataPost['follow_idEmail'] = $this->security->xss_clean($this->dataPost['follow_idEmail']);

		$dataContract = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));

		$check_store = $this->getStores($this->dataPost['follow_idEmail']);

		if (!in_array($dataContract['store']['id'], $check_store)){

			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$this->contract_model->update(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])), array("follow_contract" => $this->dataPost['follow_email'],"follow_idStore" => $this->dataPost['follow_idStore'],"follow_idEmail" => $this->dataPost['follow_idEmail']));

		if (!empty($dataContract['follow_contract'])){

			$log = array(
				"type" => "Follow_contract",
				"action" => "Update",
				"contract_id" => $this->dataPost['id'],
				"old" => $dataContract['follow_contract'],
				"new" => [
					"follow_contract" => $this->dataPost['follow_email']
				],
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->log_followContract->insert($log);

		} else {

			$log = array(
				"type" => "Follow_contract",
				"action" => "Update",
				"contract_id" => $this->dataPost['id'],
				"old" => $dataContract['created_by'],
				"new" => [
					"follow_contract" => $this->dataPost['follow_email']
				],
				"created_at" => $this->createdAt,
				"created_by" => $this->uemail
			);
			$this->log_followContract->insert($log);

		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}


	public function get_log_followContract_post(){

		$this->dataPost = $this->input->post();

		$this->dataPost['id_contract'] = $this->security->xss_clean($this->dataPost['id_contract']);

		$result = $this->log_followContract->find_where(["contract_id"=> $this->dataPost['id_contract']]);

		if (empty($result)) {
			return;
		}

		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $result
		);
		$this->set_response($response, REST_Controller::HTTP_OK);


	}

	 public function check_roll_store($checkId_store){

		 $roles = $this->role_model->find_where(['status' => 'active']);
		 $value = "";
		 foreach ($roles as $role) {
			 if (!empty($role['stores']) && (count($role['stores']) == 1)) {
				 foreach ($role['stores'] as $store) {
					 foreach ($store as $key2 => $item) {
						if ($key2 == $checkId_store){
							$value = $role;

						}
					 }
				 }
			 }
		 }
		return $value;
	 }

	public function getAllStoreCentral_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$store = $this->store_model->find_where_select(
			array('status' => 'active','type_pgd'=> array('$in' => ['2', '3'])),
			array("_id")
		);
		$array_str = [];
		if (!empty($store)) {
			foreach ($store as $st) {
				array_push($array_str, (string)$st['_id']);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $array_str
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	 }

	public function getAllStoreCentralNoneDirectSales_post()
	{
		$flag = notify_token($this->flag_login);
		if ($flag == false) return;
		$store = $this->store_model->find_where_select(
			array('status' => 'active','type_pgd'=> array('$in' => ['2', '3'])),
			array("_id")
		);
		$array_str = [];
		if (!empty($store)) {
			foreach ($store as $st) {
				array_push($array_str, (string)$st['_id']);
			}
		}
		$response = array(
			'status' => REST_Controller::HTTP_OK,
			'data' => $array_str
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
		return;
	}



}
?>

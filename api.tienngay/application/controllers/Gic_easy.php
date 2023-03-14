
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Gic_easy extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('gic_easy_model');
        $this->load->model('warehouse_model');
        $this->load->model('contract_model');
        $this->load->model("user_model");
        $this->load->model("role_model");
        $this->load->model('log_gic_model');
        $this->load->model("group_role_model");
         $this->load->model('temporary_plan_contract_model');
        $this->load->model('log_model');
        $url_gic="http://bancasuat.gic.vn";
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $headers = $this->input->request_headers();
        $dataPost = $this->input->post();
        $this->flag_login = 1;
        $this->superadmin = false;
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
                     $this->superadmin = isset($this->info['is_superadmin']) && (int)$this->info['is_superadmin'] === 1;
                }
            }
        }
    }
    
    public function find_where_not_in_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $gics = $this->gic_easy_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gics
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $gics = $this->gic_easy_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($gics)) {
            foreach ($gics as $sto) {
                $sto['gic_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gics
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
		date_default_timezone_set('Asia/Ho_Chi_Minh');
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = array();
       $total=0;
       $data = $this->input->post();
		$start = !empty($data['start']) ? $data['start'] : "";
		$end = !empty($data['end']) ? $data['end'] : "";

		if (!empty($start) && !empty($end)) {
			$condition = array(
				'start' => strtotime(trim($start).' 00:00:00'),
				'end' => strtotime(trim($end).' 23:59:59')
			);
		}

        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
         $total=0;
      	if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
            $all = true;
        }
        if (!$all) {
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array(),
                    'total' => $total
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
           
        }
        $per_page = !empty($this->input->post()['per_page']) ? $this->input->post()['per_page'] : 30;
        $uriSegment = !empty($this->input->post()['uriSegment']) ? $this->input->post()['uriSegment'] : 0;
        if(isset($data['isEasy'])) {
        	$condition['isEasy'] = $data['isEasy'];
		}
        $gic = $this->gic_easy_model->getGic($condition,$per_page , $uriSegment);
        $total = $this->gic_easy_model->getGic_total($condition);
           if (!empty($gic)) {
            foreach ($gic as $key => $value) {
              $contract=  $this->contract_model->findOne(["_id" => new MongoDB\BSON\ObjectId($value['contract_id'])]);

                 $value['contract_info']=$contract;

            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $gic,
            'total' => $total,
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_all_log_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = array();
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        $total=0;
       $data = $this->input->post();
       
         $per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
        $uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;

       $gic = $this->log_gic_model->getGic($condition,$per_page , $uriSegment);
        $condition['total']=true;
         $total = $this->log_gic_model->getGic($condition,$per_page , $uriSegment);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $gic
           , 'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_all_home_post(){
       
        $gic = $this->gic_easy_model->find_where('status', ['active']);
        if (!empty($gic)) {
            foreach ($gic as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_one_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id gic already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $gic = $this->gic_easy_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_temporary_plan_contract_one_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id gic already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $gic = $this->temporary_plan_contract_model->find_where(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    //lấy danh danh contract bằng mã nhà đầu tư
      public function get_investor_in_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id gic empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
                     }
            $contract = $this->contract_model->find_where(array("investor_infor._id" =>  new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $contract
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        }
        //lấy danh sách chi tiết thành toán cho nhà đầu tư bằng mã contract
        public function get_temporary_plan_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
        if(empty($code_contract)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id gic empty"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //var_dump($id); die;
        $gic = $this->temporary_plan_contract_model->find_where(array("code_contract" =>  $code_contract));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_gic_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id gic already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $gic = $this->gic_easy_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $gic
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_gic_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
      
        $this->gic_easy_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create gic success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_gic_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->gic_easy_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_gic($data);
        unset($data['id']);
     
        $this->gic_easy_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update gic success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function update_temporary_plan_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->temporary_plan_contract_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại thông tin nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_temporary_plan_contract($data);
        unset($data['id']);
     
        $this->temporary_plan_contract_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update gic success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function log_gic($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $gic = $this->gic_easy_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $gic['id'] = (string)$gic['_id'];
        unset($gic['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $gic,
            "type" => 'gic'

        );
        $this->log_model->insert($dataInser);
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
          private function getStores($userId) {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleStores = array();
        if(count($roles) > 0) {
            foreach($roles as $role) {
                if(!empty($role['users']) && count($role['users']) > 0){
                    $arrUsers = array();
                    foreach($role['users'] as $item) {
                        array_push($arrUsers, key($item));
                    }
                    //Check userId in list key of $users
                    if(in_array($userId, $arrUsers) == TRUE) {
                        if(!empty($role['stores'])) {
                            //Push store
                            foreach($role['stores'] as $key=> $item) {
                                array_push($roleStores, key($item));
                            }
                        }
                    }
                }
            }
        }
        return $roleStores;
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

      public function log_temporary_plan_contract($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $gic = $this->temporary_plan_contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $gic['id'] = (string)$gic['_id'];
        unset($gic['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $gic,
            "type" => 'temporary_plan_contract'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

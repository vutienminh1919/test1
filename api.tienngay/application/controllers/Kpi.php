
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Kpi extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('kpi_pgd_model');
        $this->load->model('kpi_gdv_model');
        $this->load->model('kpi_area_model');
        $this->load->model('area_model');
        $this->load->model('store_model');
        $this->load->model('log_model');
        $this->load->model('role_model');
        $this->load->helper('lead_helper');
        $this->load->model('group_role_model');
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
        $kpis = $this->kpi_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpis
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $kpis = $this->kpi_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($kpis)) {
            foreach ($kpis as $sto) {
                $sto['kpi_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpis
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_pgd_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $stores = $this->getStores_list($this->id);
      $this->dataPost = $this->input->post();
        $month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
        $year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');
        $condition=array();
         $condition['month']= $month;
         $condition['year']=$year;
         $condition['store.id']=array('$in'=> $stores);
        $kpi = $this->kpi_pgd_model->find_where($condition);
        if (!empty($kpi)) {
            foreach ($kpi as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $kpi,
            'store'=>$stores
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_gdv_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $groupRoles = $this->getGroupRole($this->id);
        $all = false;
        $condition=array('status'=>'active');
        if (in_array('cua-hang-truong', $groupRoles)) {
            $all = true;
        }
        if ($all) {
            $stores = $this->getStores_list($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array()
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
//            $condition['store.id'] = $stores[0];
			$condition['store.id']=array('$in'=> $stores);
        }
          $this->dataPost = $this->input->post();
        $month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
        $year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');
       
         $condition['month']= $month;
         $condition['year']=$year;

        $kpi = $this->kpi_gdv_model->find_where($condition);
        if (!empty($kpi)) {
            foreach ($kpi as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpi
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
        private function getStores_list($userId)
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
    private function getGroupRole($userId)
    {
        $groupRoles = $this->group_role_model->find_where(array("status" => "active"));
        $arr = array();
        foreach ($groupRoles as $groupRole) {
            if (empty($groupRole['users'])) continue;
            foreach ($groupRole['users'] as $item) {
                if (key($item) == $userId) {
                    array_push($arr, $groupRole['slug']);
                    continue;
                }
            }
        }
        return $arr;
    }
    public function get_all_area_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $this->dataPost = $this->input->post();
        $month = !empty($this->dataPost['start']) ? date('m', strtotime($this->dataPost['start'])) : date('m');
        $year = !empty($this->dataPost['start']) ? date('Y', strtotime($this->dataPost['start'])) : date('Y');
        $condition=array();
         $condition['month']= $month;
         $condition['year']=$year;

        $kpi = $this->kpi_area_model->find_where($condition);
        if (!empty($kpi)) {
            foreach ($kpi as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpi
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $kpi = $this->kpi_model->find_where('status', ['active']);
        if (!empty($kpi)) {
            foreach ($kpi as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpi
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_kpi_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id kpi already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $kpi = $this->kpi_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpi
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_kpi_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id kpi already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $kpi = $this->kpi_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $kpi
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }


    public function create_kpi_pgd_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
       $store = $this->store_model->find_where(array('status' => 'active'));
        if (!empty($store)) {
            foreach ($store as $key => $value) {
                $data_arr=array();
                $data_arr['month']=date('m', strtotime($data['start']));
                $data_arr['year']=date('Y', strtotime($data['start']));
                $data_arr['giai_ngan_CT']=0;
                $data_arr['giai_ngan_TT']=70;
                $data_arr['bao_hiem_CT']=0;
                $data_arr['bao_hiem_TT']=30;
                $data_arr['khach_hang_moi_CT']=100000000;
                $data_arr['khach_hang_moi_TT']=20;
                $data_arr['no_xau_CT']=100000000;
                $data_arr['no_xau_TT']=20;
                $data_arr['status']='active';
                $data_arr['store']= array('name' => $value['name'], 'id' =>(string)$value['_id'] );
                $kpi = $this->kpi_pgd_model->findOne(array("month" =>  $data_arr['month'],"year" =>  $data_arr['year'],"store.id" =>  (string)$value['_id']));
                if(empty($kpi))
                {
                    if($key==0)
                    {
                        $data_arr['total']=count($store); 
                    }
                 $this->kpi_pgd_model->insert($data_arr);
                }
            }
        }
      
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create kpi success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
     public function create_kpi_gdv_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();  
                 $users=$this->getUserbyStores_email($data['code_store']);
                 if(!empty( $users))
                 {
                    foreach ($users as $key => $u) {
                $store = $this->store_model->findOne(array('_id' =>  new MongoDB\BSON\ObjectId($data['code_store'])));
                 
                $data_arr=array();
                $data_arr['month']=date('m', strtotime($data['start']));
                $data_arr['year']=date('Y', strtotime($data['start']));
                $data_arr['giai_ngan_CT']=0;
                $data_arr['giai_ngan_TT']=70;
                $data_arr['bao_hiem_CT']=0;
                $data_arr['bao_hiem_TT']=30;
                $data_arr['khach_hang_moi_CT']=100000000;
                $data_arr['khach_hang_moi_TT']=20;
                $data_arr['no_xau_CT']=100000000;
                $data_arr['no_xau_TT']=20;
                $data_arr['status']='active';
                $data_arr['store']= array('name' => $store['name'], 'id' =>(string)$store['_id'] );
                $data_arr['email_gdv']=$u;
                $kpi = $this->kpi_gdv_model->findOne(array("month" =>  $data_arr['month'],"year" =>  $data_arr['year'],"store.id" => $data_arr['store']['id'],"email_gdv" => $u));
                if(empty($kpi))
                {
                    if($key==0)
                    {
                        $data_arr['total']=count($users); 
                    }
                 $this->kpi_gdv_model->insert($data_arr);
                }
            }
            
        }
      
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create kpi success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function create_kpi_area_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
       $area = $this->area_model->find_where(array('status' => 'active'));
        if (!empty($area)) {
            foreach ($area as $key => $value) {

                $data_arr=array();
                $data_arr['month']=date('m', strtotime($data['start']));
                $data_arr['year']=date('Y', strtotime($data['start']));
                $data_arr['giai_ngan_CT']=0;
                $data_arr['giai_ngan_TT']=70;
                $data_arr['bao_hiem_CT']=0;
                $data_arr['bao_hiem_TT']=30;
                $data_arr['khach_hang_moi_CT']=100000000;
                $data_arr['khach_hang_moi_TT']=20;
                $data_arr['no_xau_CT']=100000000;
                $data_arr['no_xau_TT']=20;
                $data_arr['status']='active';
                $data_arr['area']= array('name' => $value['title'],'code' => $value['code'],  'id' =>(string)$value['_id'] );
                $kpi = $this->kpi_area_model->findOne(array("month" =>  $data_arr['month'],"year" =>  $data_arr['year'],"area.id" =>  (string)$value['_id']));
                if(empty($kpi))
                {
                    if($key==0)
                    {
                        $data_arr['total']=count($area); 
                    }
                 $this->kpi_area_model->insert($data_arr);
                }
            }
        }
      
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create kpi success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
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
        public function update_kpi_pgd_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->kpi_pgd_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        $kpi = $this->kpi_pgd_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        
        if(isset($data['updated_at']))
        {
            $data['updated_at']=(int)$data['updated_at'];
        }
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại kpi nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     
       $this->log_kpi_pgd($data);
        unset($data['id']);
        unset($data['type']);
         if(isset($data["giai_ngan_CT"]))
         $data["giai_ngan_CT"] = (float)$data["giai_ngan_CT"];
         if(isset($data["giai_ngan_TT"]))
        $data["giai_ngan_TT"]  = (float)$data["giai_ngan_TT"];
        if(isset($data["bao_hiem_CT"]))
        $data["bao_hiem_CT"]  = (float)$data["bao_hiem_CT"];
       if(isset($data["bao_hiem_TT"]))
        $data["bao_hiem_TT"] = (float)$data["bao_hiem_TT"];
       if(isset($data["khach_hang_moi_CT"]))
        $data["khach_hang_moi_CT"]  = (float)$data["khach_hang_moi_CT"];
    if(isset($data["khach_hang_moi_TT"]))
        $data["khach_hang_moi_TT"]  = (float)$data["khach_hang_moi_TT"];
    if(isset($data["no_xau_CT"]))
        $data["no_xau_CT"]  = (float)$data["no_xau_CT"];
    if(isset($data["no_xau_TT"]))
        $data["no_xau_TT"] = (float)$data["no_xau_TT"];
        $this->kpi_pgd_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update kpi success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
            public function update_kpi_area_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->kpi_area_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        $kpi = $this->kpi_area_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        
        if(isset($data['updated_at']))
        {
            $data['updated_at']=(int)$data['updated_at'];
        }
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại kpi nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     
       $this->log_kpi_area($data);
        unset($data['id']);
        unset($data['type']);
         if(isset($data["giai_ngan_CT"]))
         $data["giai_ngan_CT"] = (float)$data["giai_ngan_CT"];
         if(isset($data["giai_ngan_TT"]))
        $data["giai_ngan_TT"]  = (float)$data["giai_ngan_TT"];
        if(isset($data["bao_hiem_CT"]))
        $data["bao_hiem_CT"]  = (float)$data["bao_hiem_CT"];
       if(isset($data["bao_hiem_TT"]))
        $data["bao_hiem_TT"] = (float)$data["bao_hiem_TT"];
       if(isset($data["khach_hang_moi_CT"]))
        $data["khach_hang_moi_CT"]  = (float)$data["khach_hang_moi_CT"];
    if(isset($data["khach_hang_moi_TT"]))
        $data["khach_hang_moi_TT"]  = (float)$data["khach_hang_moi_TT"];
    if(isset($data["no_xau_CT"]))
        $data["no_xau_CT"]  = (float)$data["no_xau_CT"];
    if(isset($data["no_xau_TT"]))
        $data["no_xau_TT"] = (float)$data["no_xau_TT"];
        $this->kpi_area_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update kpi success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
            public function update_kpi_gdv_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->kpi_gdv_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        $kpi = $this->kpi_gdv_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        
        if(isset($data['updated_at']))
        {
            $data['updated_at']=(int)$data['updated_at'];
        }
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại kpi nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     
       $this->log_kpi_gdv($data);
        unset($data['id']);
        unset($data['type']);
        if(isset($data["giai_ngan_CT"]))
         $data["giai_ngan_CT"] = (float)$data["giai_ngan_CT"];
         if(isset($data["giai_ngan_TT"]))
        $data["giai_ngan_TT"]  = (float)$data["giai_ngan_TT"];
        if(isset($data["bao_hiem_CT"]))
        $data["bao_hiem_CT"]  = (float)$data["bao_hiem_CT"];
       if(isset($data["bao_hiem_TT"]))
        $data["bao_hiem_TT"] = (float)$data["bao_hiem_TT"];
       if(isset($data["khach_hang_moi_CT"]))
        $data["khach_hang_moi_CT"]  = (float)$data["khach_hang_moi_CT"];
    if(isset($data["khach_hang_moi_TT"]))
        $data["khach_hang_moi_TT"]  = (float)$data["khach_hang_moi_TT"];
    if(isset($data["no_xau_CT"]))
        $data["no_xau_CT"]  = (float)$data["no_xau_CT"];
    if(isset($data["no_xau_TT"]))
        $data["no_xau_TT"] = (float)$data["no_xau_TT"];
        $this->kpi_gdv_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update kpi success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
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

    public function log_kpi_pgd($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $kpi = $this->kpi_pgd_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $kpi['id'] = (string)$kpi['_id'];
        unset($kpi['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $kpi,
            "type" => 'kpi_pgd'

        );
        $this->log_model->insert($dataInser);
    }
      public function log_kpi_gdv($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $kpi = $this->kpi_gdv_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $kpi['id'] = (string)$kpi['_id'];
        unset($kpi['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $kpi,
            "type" => 'kpi_gdv'

        );
        $this->log_model->insert($dataInser);
    }
     public function log_kpi_area($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $kpi = $this->kpi_area_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $kpi['id'] = (string)$kpi['_id'];
        unset($kpi['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $kpi,
            "type" => 'kpi_area'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

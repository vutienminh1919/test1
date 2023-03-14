
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Bao_hiem_pgd extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('bao_hiem_pgd_model');
        $this->load->model('store_model');
        $this->load->model('user_model');
           $this->load->model("user_model");
        $this->load->model("role_model");
        $this->load->model("group_role_model");
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
        $bao_hiems = $this->bao_hiem_pgd_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bao_hiems
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $bao_hiems = $this->bao_hiem_pgd_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($bao_hiems)) {
            foreach ($bao_hiems as $sto) {
                $sto['bao_hiem_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bao_hiems
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

   
     public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $condition = array();
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
        if (in_array('supper-admin', $groupRoles) || $this->superadmin || in_array('van-hanh', $groupRoles) || in_array('hoi-so', $groupRoles) || in_array('ke-toan', $groupRoles)) {
            $all = true;
        } else if (in_array('giao-dich-vien', $groupRoles)) {
            $condition['created_by'] = $this->uemail;
        }
        if (!$all) {
            $stores = $this->getStores($this->id);
            if (empty($stores)) {
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'data' => array(),
                    'total' =>$total
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }
            $condition['stores'] = $stores;
           
        }
       
        $per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
        $uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
            $bh = $this->bao_hiem_pgd_model->getBH($condition,$per_page , $uriSegment);
            $condition['total']=true; 
            $total = $this->bao_hiem_pgd_model->getBH($condition);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $bh,
             'total' =>$total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $bao_hiem = $this->bao_hiem_pgd_model->find_where('status', ['active']);
        if (!empty($bao_hiem)) {
            foreach ($bao_hiem as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bao_hiem
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_bao_hiem_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id bao_hiem already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $bao_hiem = $this->bao_hiem_pgd_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bao_hiem
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_bao_hiem_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id bao_hiem already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $bao_hiem = $this->bao_hiem_pgd_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $bao_hiem
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function import_bao_hiem_post(){
        // $flag = notify_token($this->flag_login);
        // if ($flag == false) return;
        $data = $this->input->post();
     $data['ngay_ban']= (int)$data['ngay_ban'];
     $data['created_at']= $this->createdAt;
      $data['ten_khach_hang']= $data['ten_khach_hang'];
      $data['created_by']= $this->uemail;
       $data['status']='active';
      
     $store=  isset($this->getStores((string)$this->id)[0]) ? $this->getStores((string)$this->id)[0] : '' ;
      $bh_one = $this->bao_hiem_pgd_model->findOne(array("ten_khach_hang" => $data['ten_khach_hang'],"store.id"=> $store,"ngay_ban" => $data['ngay_ban'],"loai_bao_hiem" => $data['loai_bao_hiem'],"ghi_chu" => $data['ghi_chu']));

     if(!empty($store))
     {
     $storedt = $this->store_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($store)));
     $data['store']=['id'=>$store,'name'=>$storedt['name']];
     }
      $user_dt = $this->user_model->findOne(array("email" => $data['email_nv']));
      $data['name_nv']=(!empty($user_dt['full_name'])) ? $user_dt['full_name'] : '';
      if(empty($bh_one))
      {
         $this->bao_hiem_pgd_model->insert($data);
      }else{
         $this->bao_hiem_pgd_model->update(
            array("_id" => $bh_one['_id']),
            $data
        );
      }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create bao_hiem success",
            'data'=>$data
        );
          
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function create_bao_hiem_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
     
        $data['so_tien'] =(int)$data['so_tien'];
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create bao_hiem success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
        public function update_bao_hiem_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->bao_hiem_pgd_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        $bao_hiem = $this->bao_hiem_pgd_model->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        $data['so_tien'] =(int)$data['so_tien'];
        if(isset($data['updated_at']))
        {
            $data['updated_at']=(int)$data['updated_at'];
        }
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại bao_hiem nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
     
       $this->log_bao_hiem($data);
        unset($data['id']);
        unset($data['type']);
        $this->bao_hiem_pgd_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update bao_hiem success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    

    public function log_bao_hiem($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $bao_hiem = $this->bao_hiem_pgd_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $bao_hiem['id'] = (string)$bao_hiem['_id'];
        unset($bao_hiem['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $bao_hiem,
            "type" => 'bao_hiem'

        );
        $this->log_model->insert($dataInser);
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

    private function getUserbyStores($storeId)
    {
        $roles = $this->role_model->find_where(array("status" => "active"));
        $roleAllUsers = array();
        if (count($roles) > 0) {
            foreach ($roles as $role) {
                if (!empty($role['stores']) && count($role['stores']) > 0) {
                    $arrStores = array();
                    foreach ($role['stores'] as $item) {
                        array_push($arrStores, key($item));
                    }
                    //Check userId in list key of $users
                    if (in_array($storeId, $arrStores) == TRUE) {
                        if (!empty($role['stores'])) {
                            //Push store
                            foreach ($role['users'] as $key => $item) {
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
     

     

}
?>

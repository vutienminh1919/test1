
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Warehouse extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse_model');
        $this->load->model('contract_model');
         $this->load->model('warehouse_asset_model');
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
        $warehouses = $this->warehouse_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouses
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $warehouses = $this->warehouse_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($warehouses)) {
            foreach ($warehouses as $sto) {
                $sto['warehouse_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouses
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $warehouse = $this->warehouse_model->find_where('status', ['active','deactive']);
        if (!empty($warehouse)) {
            foreach ($warehouse as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $warehouse
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_all_asset_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
         $warehouse = $this->warehouse_asset_model->find();
        if (!empty($warehouse)) {
            foreach ($warehouse as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
           'data' => $warehouse
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $warehouse = $this->warehouse_model->find_where('status', ['active']);
        if (!empty($warehouse)) {
            foreach ($warehouse as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouse
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
                'message' => "Id warehouse already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $warehouse = $this->warehouse_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouse
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_one_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id warehouse already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $warehouse = $this->contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouse
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_status_asset_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id warehouse already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $warehouse = $this->warehouse_asset_model->findOne(array("id_contract" => $id));

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $warehouse
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    //lấy danh sách contract status=17 
        public function get_asset_contract_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $asset = $this->contract_model->find_where(array("status" => 17));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $asset
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function create_warehouse_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
      
        $this->warehouse_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create warehouse success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
      public function create_warehouse_asset_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : "";
         $warehouse_asset = $this->warehouse_asset_model->find_where("id_contract" ,[ $id_contract]);
        if (!empty($warehouse_asset)) {
            $response = array(
            'status' => REST_Controller::HTTP_UNAUTHORIZED,
            'message' => "NOT OK",
            'data'=>$data
        );
           return;
        }
        $this->warehouse_asset_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create warehouse success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function update_warehouse_asset_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : "";
        $count = $this->warehouse_asset_model->count(array("id_contract" => $id_contract));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại thong tin  nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_warehouse_access($data);
        unset($data['id_contract']);
     
        $this->warehouse_asset_model->update(
            array("id_contract" => $id_contract),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update warehouse success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function update_warehouse_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->warehouse_model->fint(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_warehouse($data);
        unset($data['id']);
     
        $this->warehouse_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update warehouse success",
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
            'message' => "Update warehouse success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }
    public function log_warehouse($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $warehouse = $this->warehouse_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $warehouse['id'] = (string)$warehouse['_id'];
        unset($warehouse['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $warehouse,
            "type" => 'warehouse'

        );
        $this->log_model->insert($dataInser);
    }
       public function log_warehouse_access($data){
        $id_contract = !empty($data['id_contract']) ? $data['id_contract'] : "";
        $warehouse = $this->warehouse_asset_model->findOne(array("id_contract" => $id_contract));
        $warehouse['id'] = (string)$warehouse['_id'];
        unset($warehouse['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $warehouse,
            "type" => 'warehouse_asset'

        );
        $this->log_model->insert($dataInser);
    }
    public function upload_image_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $data['id'] = $this->security->xss_clean($data['id']);
        $data['type_img'] = $this->security->xss_clean($data['type_img']);
        $data['file'] = $this->security->xss_clean($data['file']);
     
        if($data['file']['size'] > 10000000) {
            return $this->pushJson('200', json_encode(array(
                'code' => '201',
                'msg' => 'Kích cỡ max là 10MB'
            )));
        }
        $acceptFormat = array("jpeg", "png", "jpg", "mp3", "mp4");
        if(in_array($data['file']['type'], $acceptFormat)) {
            return $this->pushJson('200', json_encode(array(
                'code' => '201',
                'msg' => 'Định dạng không cho phép',
                'type' => $data['file']['type']
            )));
        }
        $dataDB = $this->warehouse_asset_model->findOne(array("id_contract" => $data['id']));
        if(empty($dataDB)) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại hợp đồng"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $cfile = new CURLFile($data['file']["tmp_name"],$data['file']["type"],$data['file']["name"]);

        $push_upload = $this->pushUpload($cfile);
        if($push_upload->code == 200){
            //Update DB
            $random = sha1(random_string());
            $data1 = array(
                'path' => $push_upload->path,
                'file_type' => $data['file']["type"],
                'file_name' => $data['file']["name"]
            );
            $dataDB['image'][$data['type_img']] = (array)$dataDB['image'][$data['type_img']];
            //$dataDB['image_accurecy'][$data['type_img']][$random] = $push_upload->path;
            $dataDB['image'][$data['type_img']][$random] = $data1;
            //Update
            $this->warehouse_asset_model->update(
                array("id_contract" => $data['id']),
                array("image.".$data['type_img'] => $dataDB['image'][$data['type_img']])
            );
            //Insert log
            $insertLog = array(
                "type" => "asset",
                "action" => "upload_image",
                "contract_id" => $data['id'],
                "path" => $push_upload->path,
                "created_at" => $this->createdAt,
                "created_by" => $this->uemail
            );
            $this->log_model->insert($insertLog);
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'path' => $push_upload->path,
                'key' => $random
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
    }
     private function pushUpload($cfile){
        $serviceUpload = $this->config->item("url_service_upload");
        // $curlFile  = new CURLFile($_FILES["avatar"]["tmp_name"],$_FILES["avatar"]["type"],$_FILES["avatar"]["name"]);
        $post = array('avatar'=> $cfile );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$serviceUpload);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($ch);
        curl_close ($ch);
        $result1 = json_decode($result);
        return $result1;
    }
      public function log_temporary_plan_contract($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $warehouse = $this->temporary_plan_contract_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $warehouse['id'] = (string)$warehouse['_id'];
        unset($warehouse['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $warehouse,
            "type" => 'temporary_plan_contract'

        );
        $this->log_model->insert($dataInser);
    }

}
?>

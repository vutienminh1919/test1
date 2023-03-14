
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Email_template extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('email_template_model');
        $this->load->model('log_model');
        $this->load->model('email_history_model');
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
        $email_templates = $this->email_template_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_templates
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $email_templates = $this->email_template_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($email_templates)) {
            foreach ($email_templates as $sto) {
                $sto['email_template_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_templates
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $email_template = $this->email_template_model->find_where('status', ['active','deactive']);
        if (!empty($email_template)) {
            foreach ($email_template as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_template
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_history_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $per_page = !empty($data['per_page']) ? $data['per_page'] : 30;
		$uriSegment = !empty($data['uriSegment']) ? $data['uriSegment'] : 0;
        $email_template = $this->email_history_model->getEmailHistory($per_page, $uriSegment);
        $total = $this->email_history_model->count(array());
        if (!empty($email_template)) {
            foreach ($email_template as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }

        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_template,
			'total' => $total
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $email_template = $this->email_template_model->find_where('status', ['active']);
        if (!empty($email_template)) {
            foreach ($email_template as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_template
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_email_template_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id email_template already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $email_template = $this->email_template_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_template
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_email_template_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id email_template already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $email_template = $this->email_template_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $email_template
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_email_template_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
      $data['created_at'] = (int)$data['created_at'];
        $this->email_template_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create email_template success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_email_template_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->email_template_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_email_template($data);
        unset($data['id']);
        $data['updated_at'] = (int)$data['updated_at'];
        $this->email_template_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update email_template success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_email_template($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $email_template = $this->email_template_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $email_template['id'] = (string)$email_template['_id'];
        unset($email_template['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $email_template,
            "type" => 'email_template'

        );
        $this->log_model->insert($dataInser);
    }

}
?>


<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Landing_page extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('landing_page_model');
        $this->load->model('log_model');
        $this->load->model('euro_season_model');
        $this->load->model('user_model');
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
        $landing_pages = $this->landing_page_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_pages
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function find_where_post() {
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        unset($data['type']);
        $landing_pages = $this->landing_page_model->find_where_select($data, array("_id", "name", "province", "district", "address"));
        if (!empty($landing_pages)) {
            foreach ($landing_pages as $sto) {
                $sto['landing_page_id'] = (string)$sto['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_pages
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    

    private $createdAt, $flag_login, $id, $uemail, $ulang, $app_login;

    public function get_all_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $landing_page = $this->landing_page_model->find_where('status', ['active','deactive']);
        if (!empty($landing_page)) {
            foreach ($landing_page as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_page
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_all_home_post(){
       
        $landing_page = $this->landing_page_model->find_where('status', ['active']);
        if (!empty($landing_page)) {
            foreach ($landing_page as $s) {
                $s['id'] = (string)$s['_id'];
            }
        }
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_page
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function get_landing_page_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        if(empty($id)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id landing_page already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $landing_page = $this->landing_page_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_page
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
     public function get_description_landing_page_post(){
      
        $data = $this->input->post();
        $link = !empty($data['link']) ? $data['link'] : "";
        if(empty($link)){
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Id landing_page already exists"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $landing_page = $this->landing_page_model->findOne(array("link" => $link));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $landing_page
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_landing_page_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
      
        $this->landing_page_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create landing_page success",
            'data'=>$data
        );

        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function update_landing_page_post(){
        $flag = notify_token($this->flag_login);
        if ($flag == false) return;
        $data = $this->input->post();
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->landing_page_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại tin tức nào cần cập nhật"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $this->log_landing_page($data);
        unset($data['id']);
     
        $this->landing_page_model->update(
            array("_id" => new MongoDB\BSON\ObjectId($id)),
            $data
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update landing_page success",
            'data' => $data
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function log_landing_page($data){
        $id = !empty($data['id']) ? $data['id'] : "";
        $landing_page = $this->landing_page_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($id)));
        $landing_page['id'] = (string)$landing_page['_id'];
        unset($landing_page['_id']);
        $dataInser = array(
            "new_data" => $data,
            "old_data" => $landing_page,
            "type" => 'landing_page'

        );
        $this->log_model->insert($dataInser);
    }

    public function landing_euro_post() {
        $data = $this->input->post();
        $name = isset($data['name']) ? $data['name'] : null;
        $identity = isset($data['identity']) ? $data['identity'] : null;
        $phone = isset($data['phone']) ? $data['phone'] : null;
        $email = isset($data['email']) ? $data['email'] : null;
        $guest_team = isset($data['guest_team']) ? $data['guest_team'] : null;
        $guest_number = isset($data['guest_number']) ? $data['guest_number'] : null;

        $user = $this->user_model->find_where([
            'type' => '2',
            'status' => 'active',
            'phone_number' => $phone
        ]);
        $check = $this->euro_season_model->find_where([
            'phone' => $phone
        ]);
        if (empty($check)) {
            if (!empty($user)) {
                $result = $this->euro_season_model->insertReturnId([
                    'name' => $name,
                    'identity' => $identity,
                    'phone' => $phone,
                    'email' => $email,
                    'guest_team' => $guest_team,
                    'guest_number' => $guest_number,
                    'user' => $user
                ]);

                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => 'Success',
                    'data' => $result
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
            }

			$response = array(
				'status' => REST_Controller::HTTP_BAD_REQUEST,
				'message' => 'Bạn chưa đăng ký app',
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
        }

		$response = array(
			'status' => REST_Controller::HTTP_BAD_REQUEST,
			'message' => 'Bạn chỉ có thể dự đoán một lần',
		);
		$this->set_response($response, REST_Controller::HTTP_OK);
    }

}
?>

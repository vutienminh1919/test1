<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Language extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
    }
    private $createdAt;
    public function index_get(){
        $response = array(
            'status' => true,
            'message' => 'Connected'
        );
        $this->set_response($response, REST_Controller::HTTP_UNAUTHORIZED);
    }
    public function vs_get(){
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            "vs"=> "vs0.0.1"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    public function list_post(){
        $post = $this->input->post();
        $data = $this->language_model->find_where(array('status'=>'active', 'code'=>$post['lang']));

        if(count($data)==0) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => $data
            );
        }else{
            $response = array(
                'status' => REST_Controller::HTTP_OK,
                'data' => $data
            );
        }
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
}
?>
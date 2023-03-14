<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include('application/vendor/autoload.php');
require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Customer extends REST_Controller{
    public function __construct(){
        parent::__construct();
        $this->load->model('lead_model');
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());

    }
    private $createdAt;

    public function register_post(){
        $data = $this->input->post();
         $data['created_at'] = $this->createdAt;
          $data['status_sale'] = (!empty($data['status_sale'])) ? $data['status_sale'] : '1';
        $lead = $this->lead_model->findOne(array("phone_number" => $data['phone']));
        if(!empty($lead)) {
        $current_day = strtotime(date('m/d/Y'));
         $datetime = !empty($lead['created_at']) ? intval($lead['created_at']): $current_day;
         $time = intval(($current_day - $datetime) / (24*60*60));
          $last=3-$time;
         if($time >= 3)
         {
             $this->lead_model->insert($data);
                $response = array(
                    'status' => REST_Controller::HTTP_OK,
                    'message' => "Create success"
                );
                $this->set_response($response, REST_Controller::HTTP_OK);
                return;
          }else{
              $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Số điện thoại đã được đăng ký, vui lòng đăng ký sau ".$last." ngày nữa"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        }else{
       
       $this->lead_model->insert($data);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
        }
    }
    
}
?>
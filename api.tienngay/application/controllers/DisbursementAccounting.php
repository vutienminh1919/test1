<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class DisbursementAccounting extends REST_Controller{
    public function __construct($config = 'rest') {
        parent::__construct($config);
        $this->load->model("disbursement_accounting_model");
        $this->load->model("contract_model");
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
        $this->dataPost = $this->input->post();
        //Check secret_key
        $libTripleDes = new TripleDes();
        $this->isTriple = $libTripleDes->Decrypt($this->dataPost['secret_key'], $this->config->item("TRIPLEDES_KEY_DISBURSEMENT_ACCOUNTING"));
        unset($this->dataPost['type']);
    }
    
    private $createdAt, $dataPost, $isTriple;
  
      
    public function search_post() {
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        unset($this->dataPost['secret_key']);
        $fdate = !empty($this->dataPost['fdate']) ? $this->dataPost['fdate'] : "";
        $tdate = !empty($this->dataPost['tdate']) ? $this->dataPost['tdate'] : "";
        $arr = array(
            'start' => strtotime(trim($fdate).' 00:00:00'),
            'end' => strtotime(trim($tdate).' 23:59:59')
            );

        $stores = $this->store_model->find_where_not_in($data['where'], $data['fields'], convertToMongoObject($data['not_in']));
        $disbursement = $this->disbursement_accounting_model->find_search($arr);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $disbursement
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function create_post() {
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        //Count name
        $counts = $this->disbursement_accounting_model->find_where(array("code_contract" => $this->dataPost['code_contract']));
        if(!empty($counts)) {
            foreach($counts as $key => $value){
                if($value['status'] == 'new' || $value['status'] == 'create_withdrawal_success' || $value['status'] == 'success'){
                    $response = array(
                        'status' => REST_Controller::HTTP_UNAUTHORIZED,
                        'message' => "Code contract does not exists"
                    );
                    $this->set_response($response, REST_Controller::HTTP_OK);
                    return;
                }
            }
        }
        $this->dataPost['import_at'] = $this->createdAt;
        $this->disbursement_accounting_model->insert($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Create success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }

    public function get_all_post(){
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $disbursement = $this->disbursement_accounting_model->getAll();                                                             
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $disbursement
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function get_one_post() {
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $disbursement = $this->disbursement_accounting_model->findOne(array("_id" => new MongoDB\BSON\ObjectId($this->dataPost['id'])));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $disbursement
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function get_where_post() {
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        unset($this->dataPost['secret_key']);
        $disbursement = $this->disbursement_accounting_model->find_where($this->dataPost);
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => $disbursement
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }
    
    public function update_post() {
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        
//        $response = array(
//            'status' => REST_Controller::HTTP_UNAUTHORIZED,
//            'data' => $this->dataPost['update']
//        );
//        $this->set_response($response, REST_Controller::HTTP_OK);
//        return;
        
        $this->disbursement_accounting_model->update(
            $this->dataPost['condition'],
            $this->dataPost['update']
        );
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'data' => "Update success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
    }

    public function hide_disbursement_post(){
        if ($this->isTriple == false) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'data' => "Secret key invalid"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $data = $this->input->post();
        $code_contract = !empty($data['code_contract']) ? $data['code_contract'] : "";
        $id = !empty($data['id']) ? $data['id'] : "";
        $count = $this->disbursement_accounting_model->count(array("_id" => new \MongoDB\BSON\ObjectId($id)));
        if($count != 1) {
            $response = array(
                'status' => REST_Controller::HTTP_UNAUTHORIZED,
                'message' => "Không tồn tại giao dịch nào cần xóa"
            );
            $this->set_response($response, REST_Controller::HTTP_OK);
            return;
        }
        $code_contract = $data['code_contract'];
        unset($data['code_contract']);
        unset($data['secret_key']);
        $data['updated_at'] = $this->createdAt;
        //$data
        $this->disbursement_accounting_model->update(array("_id" => new \MongoDB\BSON\ObjectId($id)),array("status"=>"hidden","updated_at" =>$this->createdAt ));
        $response = array(
            'status' => REST_Controller::HTTP_OK,
            'message' => "Update store success"
        );
        $this->set_response($response, REST_Controller::HTTP_OK);
        return;
    }


}
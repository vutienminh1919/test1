<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model{
    public function __Construct(){
        parent::__construct();
        $this->collection = 'user';
    }
    private $collection, $url;

    public function where_or($where=array()){
        return $this->mongo_db->or_where($where)->get($this->collection);
    }
    public function where_in($field = "", $in = array())
    {
        return $this->mongo_db
            ->where_in($field, $in)->get($this->collection);
    }
      // active user
    public function activate($token_active) {
        $query = $this->mongo_db->where('token_active', $token_active)->get('user');
        if (!empty($query)) {
            $data = array(
                'status_login' => true,
                'token_active' => ""
            );
            $this->update(array('token_active' =>  $token_active),$data);
            return  true;
        } else {
            return  false;
        }
    }
    public function insertReturnId($data) {
        return $this->mongo_db->insertReturnId($this->collection, $data);
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
	public function find_where($condition)
	{
		return $this->mongo_db->get_where($this->collection, $condition);
	}
	public function find_where2($condition1 = null ,$condition2 = null){
    	$user = $this->mongo_db->get_where($this->collection, $condition1);
    	if(!empty($user)){
			return $user;
		}else{
			return $this->mongo_db->get_where($this->collection, $condition2);
		}
    }
    public function find_where_in($field="", $in=array()){
        return $this->mongo_db
            ->where_in($field, $in)->order_by(array('updated_at' => 'DESC'))->get($this->collection);
    }
    public function find_where_select($condition, $value){
        return $this->mongo_db
            ->select($value)
            ->get_where($this->collection, $condition);
    }
    public function findOneAndUpdate($where="", $inforUupdate="") {
        $update = array(
            '$set' => $inforUupdate
        );
        return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
    }

	public function checkPass($password, $_id) {
		$query = $this->mongo_db->where('_id',  $_id)
			->limit(1)->get($this->collection);
		if (count($query) > 0) {
			if ($this->verifyHash($password, $query[0]['password'])) {
				return $query[0];
			};
		};
		return false;
	}

	// password verify
	public function verifyHash($password, $vpassword) {
		if (password_verify($password, $vpassword)) {
			return true;
		} else {
			return false;
		}
	}
    
    public function find_where_not_in($condition, $field="", $in=""){
        if(empty($in)) {
            return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get_where($this->collection, $condition);
        } else {
            return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->where_not_in($field, $in)
            ->get_where($this->collection, $condition);
        }
    }
    
    public function findTop($limit=1, $condition, $offset=1){
        $arr = $this->mongo_db
            ->order_by(array('created_at' => 'desc'))
            ->limit($limit)
            ->offset($offset)
            ->get_where($this->collection, $condition);
        return $arr;
    }
    public function find(){
        return $this->mongo_db
            ->get($this->collection);
    }
    private $app_count_user, $app_user_info;
    public function getAppCountUser(){
        return $this->app_count_user;
    }
    public function getAppUserInfo(){
        return $this->app_user_info;
    }
    public function signin($email, $password){
        $condition1 = array('email' => $email,'type'=>'1' );
        $condition2 = array('username' => $email ,'type'=>'1');
        $this->app_count_user = $this->count2($condition1, $condition2);
        $this->app_user_info = $this->find_where2($condition1, $condition2);
        if ($this->app_count_user == 1 && password_verify($password, $this->app_user_info[0]->password)){
            return true;
        }else{
            return false;
        }
    }
    public function signin_fb_gg($email){
        $condition = array('email' => $email);
        $this->app_count_user = $this->count($condition);
        $this->app_user_info = $this->find_where($condition);
        if ($this->app_count_user == 1){
            return true;
        }else{
            return false;
        }
    }

    public function load_info($email){
        $condition = array('email' => $email);
        $this->app_count_user = $this->count($condition);
        $this->app_user_info = $this->find_where($condition);
        if ($this->app_count_user == 1)return true;
        else return false;
    }
    public function setting_info($email){
        $condition = array('email' => $email);
        $this->app_count_user = $this->count($condition);
        $value = array('my_referal',
            'referal',
            'email',
            'created_at',
            'lang',
            'trust',
            'fee_id',
            'level_id',
            'whitelist',
            'nick_name',
            'enable_google_2fa',
            'enable_email_2fa',
            'enable_sms_2fa',
            'birthday',
            'city',
            'country',
            'address',
            'street_address_1',
            'street_address_2',
            'firt_name',
            'last_name',
            'avatar',
            'egt_fee',
            'chat',
            'passport_country',
            'passport'
        );
        $this->app_user_info = $this->find_where_select($condition, $value);
        if ($this->app_count_user == 1)return true;
        else return false;
    }
    public function signup($email) {
        $condition = array('email' => $email);
        if ($this->count($condition) === 0){
            return true;
        }else{
            return false;
        }
    }
    public function findOne($condition){
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }

    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
    }
    public function getUserDetail($uid){

    }
    public function count2($where1 = null ,$where2 = null){
    	$user = $this->mongo_db->where($where1)->count($this->collection);
        
    	if(!empty($user)){
			return $user;
		}else{
			return $this->mongo_db->where($where2)->count($this->collection);
		}

    }

	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}

    public function getObjectId($email = '')
    {
        $email_active = $this->findOne(array('email' => $email));
        $getId = $email_active['_id'];
        $idd = json_encode($getId);
        $json = json_decode($idd, true);
        $oId = $json['$oid'];
        return $oId;
    }


    public function send_Email($data) {
        $url_email = $this->config->item('URL_API_EMAIL');
        $arr_block=['hailm@tienngay.vn'];
        if(isset($data['email']) && !in_array($data['email'], $arr_block) )
        {
        $postdata = http_build_query(
           $data
        );
        $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'content' => $postdata,
                        //'ignore_errors' => '1'
                    )
                );
        $context = stream_context_create($opts);
		if (!empty($url_email)) {
			$result = file_get_contents($url_email, false, $context);
			$decodeResponse = json_decode($result);
		}
        }
        try{
            return true;
            // \Log::info($result);
        }catch(\Exception $e){
            return false;
        }
    }
     public function send_Email_Forgot($data) {
        $url_email = $this->config->item('URL_API_EMAIL');
     
       
        $postdata = http_build_query(
           $data
        );
        $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'content' => $postdata,
                        //'ignore_errors' => '1'
                    )
                );
        $context = stream_context_create($opts);
        if (!empty($url_email)) {
            $result = file_get_contents($url_email, false, $context);
            $decodeResponse = json_decode($result);
        }
        
        try{
            return true;
            // \Log::info($result);
        }catch(\Exception $e){
            return false;
        }
    }
	public function getUsers(){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function getUserPagination( $limit = 30, $offset = 0, $condition){
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['type_user'])) {
			$where['type'] = $condition['type_user'];
		} 
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['full_name'])) {
			$mongo = $mongo->like("full_name",$condition['full_name']);
		}
		if (!empty($condition['email'])) {
			$mongo = $mongo->like("email",$condition['email']);
		}
		if (!empty($condition['phone_number'])) {
			$mongo = $mongo->like("phone_number",$condition['phone_number']);
		}
		return $mongo->order_by(array('created_at' => 'DESC'))
			->limit($limit)->offset($offset)
			->get($this->collection);
	}
	public function getUserTotal($condition){
		$mongo = $this->mongo_db;
		$where = array();
		if(!empty($condition['type_user'])){
			$where['type'] = $condition['type_user'];
		} 
		if(!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if(!empty($condition['full_name'])){
			$mongo = $mongo->like("full_name",$condition['full_name']);
		}
		if(!empty($condition['email'])){
			$mongo = $mongo->like("email",$condition['email']);
		}
		if(!empty($condition['phone_number'])){
			$mongo = $mongo->like("phone_number",$condition['phone_number']);
		}
		return $mongo
			->count($this->collection);
	}


//     public function sendEmail($code, $email, $url, $device, $ipAddress, $passwordNew) {
//         $data = array(
//             'infor' => array (
//                 'code' => $code,
//                 'email' => $email,
//                 'url' => $url,
//                 'device' => $device,
//                 'ip_address' => $ipAddress,
//                 'password' => $passwordNew
//             )
//         );
//         $ch = curl_init();
//         // curl_setopt($ch, CURLOPT_URL, $this->config->item('url_service').'email/sendEmail');
//         curl_setopt($ch, CURLOPT_URL, $this->config->item('URL_API_EMAIL'));
//         curl_setopt($ch, CURLOPT_POST, 1);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
//         $result = curl_exec($ch);
//         var_dump($result);die;
//         try{
// //            \Log::info($result);
//         }catch(\Exception $e){
//             return null;
//         }
//     }
    
    public function sendEmailConfirm($code, $email, $emailVerifyCode) {
        $data = array(
            'infor' => array (
                'code' => $code,
                'email' => $email,
                'email_verify_code' => $emailVerifyCode
            )
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config->item('url_service').'email/sendEmail');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $result = curl_exec($ch);
        try{
//            \Log::info($result);
        }catch(\Exception $e){
            return null;
        }
    }

    public function checkEmailStatus($email = '', $password  = '') {
        $mail_active = $this->findOne(array('email' => $email));
        if(empty($mail_active) || !password_verify($password, $mail_active['password'])) return false;
        return true;
    }

    public function checkStatusLogin($email) {
        $mail_active = $this->findOne(array('email' => $email));
        if(!empty($mail_active) && $mail_active['status_login']) return true;
        return false;
    }

    function getContractPaginationByRole_limit($condition = array()){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		$where['is_customer_code'] = array('$exists' => false);
		$where['type'] = "2";

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)
//			->limit($limit)
//			->offset($offset)
			->select(["_id","phone_number","full_name"])
			->get($this->collection);
	}

	public function find_where_paginate($user_thn)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['status'] = "active";

		if (!empty($user_thn)){
			$where['email'] = array('$in' => $user_thn);
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->select(['email'])
			->get($this->collection);

	}

}


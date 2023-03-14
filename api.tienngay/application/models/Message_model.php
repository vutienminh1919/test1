<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message_model extends CI_Model{
    public function __Construct(){
        parent::__construct();
        $this->collection = 'message';
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function findTopOne(){
        $arr = $this->mongo_db
                    ->order_by(['created_at' => 'desc'])
                    ->limit(1)
                    ->get($this->collection);
        return $arr[0];
    }
    public function find_where($condition){
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
    public function find(){
        return $this->mongo_db
            ->get($this->collection);
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
    public function count($where){
        return $this->mongo_db->where($where)->count($this->collection);
    }
    public function msg($code=0, $lang = 'EN', $param=array()){
        if ($lang==null || $lang == '') $lang = 'EN';
        $rs = $this->find_where(array("group"=>(string)$code, "lang"=>$lang));
        if(!empty($param)) {
            foreach($param as $key=>$value) {
                $rs[0]['message'] = str_replace("{".$key."}", $value, $rs[0]['message']);
            }
        }
        return $rs[0]['message'];
    }
}
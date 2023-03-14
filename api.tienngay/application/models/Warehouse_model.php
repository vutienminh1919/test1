<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Warehouse_model extends CI_Model
{

    private $collection = 'warehouse';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function findOne($condition){
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function count($condition){
        return $this->mongo_db->where($condition)->count($this->collection);
    }
   public function find_where($field="", $in=array()){
        return $this->mongo_db
            ->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
    }
 
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
    }
    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }
    public function insertReturnId($data) {
        return $this->mongo_db->insertReturnId($this->collection, $data);
    }
    public function findOneAndUpdate($where="", $inforUupdate="") {
        $update = array(
            '$set' => $inforUupdate
        );
        return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
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
    
}

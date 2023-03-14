<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_gic_model extends CI_Model
{

    private $collection = 'log_gic';

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
    public function find_where($condition){
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
    public function find(){
        return $this->mongo_db
         ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }
    public function getGic($condition = array(),$limit = 30, $offset = 0) {
        $order_by = ['created_at' => 'DESC'];
        $where = array();
        $mongo = $this->mongo_db;
        if(!isset($condition['total'])){
            $mongo = $mongo->set_where($where);
            return $mongo->order_by($order_by)
              ->limit($limit)
                ->offset($offset)
                ->get($this->collection);
            }else{
                $mongo = $mongo->set_where($where);
            return $mongo->order_by($order_by)
                ->count($this->collection);
            }
        
    }
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
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
	public function getLogs($condition){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}
    
    public function find_where_select($condition, $value){
        return $this->mongo_db
            ->select($value)
            ->get_where($this->collection, $condition);
    }
    
    public function insertLog($type="", $action="", $old="", $new="", $createdAt="", $createdBy="") {
        $data = array(
            "type" => $type,
            "action" => $action,
            "old" => $old,
            "new" => $new,
            "created_at" => $createdAt,
            "created_by" => $createdBy
        );
        $this->insert($data);
    }
    
}

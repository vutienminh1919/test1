<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Disbursement_accounting_model extends CI_Model
{

    private $collection = 'disbursement_accounting';

    public function  __construct()
    {
        parent::__construct();
    }

    public function find_search($where = array()){
        $inputs['import_at'] = array(
            '$lte' => $where['end'],
            '$gte' => $where['start'],
        );
        unset($where['start']);
        unset($where['end']);
        $in = array("hidden");
        return $this->mongo_db
        ->where_not_in('status', $in)
        ->get_where($this->collection, $inputs);
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
    public function getAll(){
        $in = array("hidden");
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->where_not_in('status', $in)
            ->get($this->collection);
    }


    
}
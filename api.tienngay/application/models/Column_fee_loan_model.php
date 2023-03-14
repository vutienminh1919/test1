

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Column_fee_loan_model extends CI_Model
{

    public function  __construct()
    {
        parent::__construct();
        $this->collection = 'column_fee_loan';
    }
    private $collection;
    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
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
    
    public function find_where_select($condition, $value){
        return $this->mongo_db
            ->select($value)
            ->get_where($this->collection, $condition);
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
    
    public function getFee($where=array()){
        $mongo = $this->mongo_db;
        if (!empty($where)) {
			$mongo = $mongo->set_where($where);
        }
        $in = array("percent_advisory","percent_expertise","penalty_percent","penalty_amount","percent_prepay_phase_1","percent_prepay_phase_2","percent_prepay_phase_3","extend");
        return $mongo->where_in('code',$in)
            ->get($this->collection);
    }
   
    
}



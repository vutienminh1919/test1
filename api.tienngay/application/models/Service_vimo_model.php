<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Service_vimo_model extends CI_Model
{
    public function  __construct()
    {
        parent::__construct();
        $this->collection = 'service_vimo';
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
    }
    private $collection, $createdAt;

    public function insert($data)
    {
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function findTop($limit=1, $condition, $offset=1){
        $arr = $this->mongo_db
            ->order_by(array('created_at' => 'desc'))
            ->limit($limit)
            ->offset($offset)
            ->get_where($this->collection, $condition);
        return $arr;
    }
    public function find_where($condition)
    {
        return $this->mongo_db
        ->order_by(array('created_at' => 'DESC'))
        ->get_where($this->collection, $condition);
    }

    public function findOne($condition)
    {
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }

    public function update($condition, $set)
    {
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }

    public function delete($condition)
    {
        return $this->mongo_db->where($condition)->delete($this->collection);
    }

    public function count($where)
    {
        return $this->mongo_db->where($where)->count($this->collection);
    }
    public function find(){
        return $this->mongo_db
            ->get($this->collection);
    }

	public function find_where_custom($field="", $in=array()){
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
}
?>

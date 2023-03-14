<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model
{
    public function  __construct() {
        parent::__construct();
        $this->load->library('mongo_db', array('activate' => 'default'), 'mongo_db');
        $this->collection = 'notification';
    }
    private $collection;

    public function insert($data) {
        return $this->mongo_db->insert($this->collection, $data);
    }

    public function update($data) {
        return $this->mongo_db->insert($this->collection, $data);
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

    public function findManyAndUpdate($condition="", $inforUupdate="") {
        $update = array(
            '$set' => $inforUupdate
        );
        return $this->mongo_db->set_where($condition)->update_all($this->collection, $update);
    }

    public function findManyAndUpdateByIds($condition=array(), $inforUupdate="") {
        $update = array(
            '$set' => $inforUupdate
        );
        return $this->mongo_db->where_in('_id', $condition)->update_all($this->collection, $update);
    }

    public function where_in($field = "", $in = array())
    {
        return $this->mongo_db
            ->where_in($field, $in);
    }

	public function getNotification($inputs = array()){
		$mongo = $this->mongo_db;
		$where = [];
		$limit = '';
		if(isset($inputs['user_id'])) {
			$where['user_id'] = $inputs['user_id'];
		}
		if(isset($inputs['status'])) {
			$status = $inputs['status'];
			unset($inputs['status']);
		}
		if(isset($inputs['limit'])) {
			$limit = $inputs['limit'];
			unset($inputs['limit']);
		}
		$mongo = $mongo->set_where($where);
		if(isset($inputs['count'])) {
			unset($inputs['count']);
			return $mongo->order_by(array('created_at' => 'DESC'))
				->where_in('status', [1])
				->count($this->collection);
		}
		if (!empty($limit)) {
			return $mongo->order_by(array('created_at' => 'DESC'))
				->limit($limit)
				->offset(0)
				->where_in('status', $status)
				->get_where($this->collection, $inputs);
		}
		return $mongo->order_by(array('created_at' => 'DESC'))
			->where_in('status', $status)
			->get_where($this->collection, $inputs);
	}

	public function getNotification_all($inputs = array(),$per_page = 30,$uriSegment = 0){
		$mongo = $this->mongo_db;
		$where = [];

		if(isset($inputs['user_id'])) {
			$where['user_id'] = $inputs['user_id'];
		}
		if(isset($inputs['status'])) {
			$status = $inputs['status'];
			unset($inputs['status']);
		}

		$mongo = $mongo->set_where($where);
		if(isset($inputs['count'])) {
			unset($inputs['count']);
			return $mongo->order_by(array('created_at' => 'DESC'))
				->where_in('status', [1])
				->count($this->collection);
		}

		return $mongo->order_by(array('created_at' => 'DESC'))
			->limit($per_page)
			->offset($uriSegment)
			->where_in('status', $status)
			->get_where($this->collection, $inputs);
	}



	public function getLimitNotification($inputs = array(),$limit = 10, $offset = 0, $order_by = []){
        $mongo = $this->mongo_db;
        $where = [];
        if(isset($inputs['user_id'])) {
            $where['user_id'] = $inputs['user_id'];
        }
        if(isset($inputs['status'])) {
            $status = $inputs['status'];
            unset($inputs['status']);
        }
        $mongo = $mongo->set_where($where);
        return $mongo->order_by(array('created_at' => 'DESC'))
            ->limit($limit)
            ->offset($offset)
            ->where_in('status', $status)
            ->get_where($this->collection, $inputs);
    }

    public function find_where($condition ){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get_where($this->collection, $condition);
    }

    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }

    public function findOne($condition)
    {
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function set_where($condition)
    {
        return $this->mongo_db->set_where($condition)->get($this->collection);
    }

    public function delete($condition)
    {
        return $this->mongo_db->where($condition)->delete($this->collection);
    }

    public function count($where)
    {
        return $this->mongo_db->where($where)->count($this->collection);
    }
    
    public function orderBy($condition) {
        return $this->mongo_db->order_by($condition);
    }

	public function get_count_notification_user($condition = array())
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['user_id'])) {
			$where['user_id'] = $condition['user_id'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['type_notification'])) {
			$where['type_notification'] = $condition['type_notification'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->count($this->collection);
	}
}
?>

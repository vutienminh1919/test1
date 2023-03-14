<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Area_debt_recovery_model extends CI_Model
{
	private $collection = 'area_debt_recovery';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function getArea($select, $condition)
	{
		$arr = $this->mongo_db
			->select($select)
			->get_where($this->collection, $condition);
		return $arr;
	}

	public function getAreaUser($condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['province' => 'ASC'];
		if (isset($condition['id'])) {
			$where['user_id'] = $condition['id'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function getTotalAreaUser($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (isset($condition['id'])) {
			$where['user_id'] = $condition['id'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->count($this->collection);
	}

}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

class History_heyu_model extends CI_Model
{
	private $collection = 'history_heyu';

	public function __construct()
	{
		parent::__construct();
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
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

	public function get_all($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => (int)$condition['start'],
				'$lte' => (int)$condition['end']
			);
		}
		$mongo = $mongo->set_where($where);
		return $mongo
			->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function count_all($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => (int)$condition['start'],
				'$lte' => (int)$condition['end']
			);
		}
		$mongo = $mongo->set_where($where);
		return $mongo
			->count($this->collection);

	}
}

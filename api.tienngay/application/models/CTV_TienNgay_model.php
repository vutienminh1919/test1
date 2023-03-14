<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class CTV_TienNgay_model extends CI_Model
{
	private $collection = 'collaborator';

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

	public function getAllCTVIntro($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['ctv_name'])) {
			$where['ctv_name'] = $condition['ctv_name'];
		}
		if (isset($condition['ctv_phone'])) {
			$where['ctv_phone'] = $condition['ctv_phone'];
		}
		$where['form'] = '1';
		$where['user_type'] = '1';
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['total'])) {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->count($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function getAllGroup()
	{
		$mongo = $this->mongo_db;
		$order_by = ["created_at" => "DESC"];
		$where = array();
		$where['status'] = "active";
		$where['account_type'] = "1";
		$where['form'] = '2';
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}

}

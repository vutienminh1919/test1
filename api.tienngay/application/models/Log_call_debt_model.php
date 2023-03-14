<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log_call_debt_model extends CI_Model
{
	private $collection = 'log_call_debt';

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
			->order_by(array('created_at' => 'DESC'))
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

	public function get_log_call_debt($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
		}
		if (!empty($condition['email'])) {
			$where['created_by'] = $condition['email'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_count_call_debt($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
		}
		if (!empty($condition['email'])) {
			$where['created_by'] = $condition['email'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->count($this->collection);
	}

	public function contractLog($condition = array())
	{
		$order_by = ['created_at' => "DESC"];
		$mongo = $this->mongo_db;
		$where = array();
		$where['type'] = 'assign_contract_field';
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition["contract_id"])) {
			$mongo = $mongo->where_in("old.contract_id", array($condition["contract_id"]));
		}
		return $mongo->order_by($order_by)
			->get($this->collection);
	}

	public function findNoteReminder($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		$where['action'] = 'note_reminder';
		$where['created_by'] = $condition['user'];
		$where['created_at'] = [
			'$lte' => $condition['tdate'],
			'$gte' => $condition['fdate']
		];
		$where['contract_id'] = $condition['id'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->count($this->collection);
	}

	public function findCalled($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		$where['action'] = 'note_reminder';
		$where['created_by'] = $condition['user'];
		$where['created_at'] = [
			'$lte' => $condition['tdate'],
			'$gte' => $condition['fdate']
		];
		$where['contract_id'] = $condition['id'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by(['created_at' => 'DESC'])
			->limit(1)
			->count($this->collection);
	}



	public function find_where_reminder($condition)
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->limit(1)
			->select(['old.result_reminder', 'new', 'created_at'])
			->get_where($this->collection, $condition);
	}


}

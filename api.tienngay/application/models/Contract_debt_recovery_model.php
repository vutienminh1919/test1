<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contract_debt_recovery_model extends CI_Model
{
	private $collection = 'contract_debt_recovery';

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

	public function getArea($select, $condition)
	{
		$arr = $this->mongo_db
			->select($select)
			->get_where($this->collection, $condition);
		return $arr;
	}

	public function findDebt($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (isset($condition['user_id'])) {
			$where['user_id'] = $condition['user_id'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->get($this->collection);
	}

	public function findDebtSort($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['user_id'])) {
			$where['user_id'] = $condition['user_id'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->get($this->collection);
	}

	public function findFieldNote($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		$where['created_by'] = $condition['user'];
		$where['created_at'] = [
			'$lte' => $condition['tdate'],
			'$gte' => $condition['fdate']
		];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->count($this->collection);

	}

	public function findFielded($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		$where['created_by'] = $condition['user'];
		$where['created_at'] = [
			'$lte' => $condition['tdate'],
			'$gte' => $condition['fdate']
		];
		$where['contract_id'] = $condition['contract_id'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->select(['note', 'created_at', 'created_by'])
			->get($this->collection);
	}

	public function getFielded($contract_id)
	{
		$mongo = $this->mongo_db;
		return $mongo->where(['contract_id' => $contract_id])
			->get($this->collection);

	}

}

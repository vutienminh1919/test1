<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_transaction_model extends CI_Model {

	private $collection = 'bank_transaction';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function findOne_asc($condition)
	{
		return $this->mongo_db->order_by(array('time_print' => 'ASC'))->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('date' => 'DESC'))
			->where($condition)
			->get($this->collection);
	}

	public function find($condition, $limit, $offset){
		return $this->mongo_db
			->where($condition)
			->limit($limit)
			->offset($offset)
			->order_by(['create_at' => 'DESC'])
			->get($this->collection);
	}

}

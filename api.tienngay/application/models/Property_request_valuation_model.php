<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Property_request_valuation_model extends CI_Model
{
		private $collection = 'property_pending_valuation';
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
	
	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db->where($condition)->get($this->collection);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('updated_at' => 'DESC'))->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function get_pending_valuation_property($condition, $limit, $offset)
	{
		$where = array();
		if (!empty($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (!empty($condition['slug_name'])) {
			$where['slug_name'] = $condition['slug_name'];
		}
		if (!empty($condition['year_property'])) {
			$where['year_property'] = $condition['year_property'];
		}
		if (!empty($condition['hang_xe'])) {
			$where['hang_xe'] = $condition['hang_xe'];
		}
		if (!empty($condition['user'])) {
			$where['created_by'] = $condition['user'];
		}
		$mongo = $this->mongo_db;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by(['status_valuation' => 'ASC','created_at' => 'DESC'])
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_count_pending_valuation_property($condition)
	{
		$where = [];
		if (!empty($condition['user'])) {
			$where['created_by'] = $condition['user'];
		}
		if (!empty($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (!empty($condition['slug_name'])) {
			$where['slug_name'] = $condition['slug_name'];
		}
		if (!empty($condition['year_property'])) {
			$where['year_property'] = $condition['year_property'];
		}
		if (!empty($condition['hang_xe'])) {
			$where['hang_xe'] = $condition['hang_xe'];
		}
		$mongo = $this->mongo_db;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->count($this->collection);
	}




}

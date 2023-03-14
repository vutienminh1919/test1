<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log_property_model extends CI_Model
{
	private $collection = 'log_history_property';

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

	public function get_history($condition, $limit, $offset)
	{

		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (!empty($condition['year'])) {
			$where['data.year_property'] = $condition['year'];
		}
		if (!empty($condition['phan_khuc'])) {
			$where['data.phan_khuc'] = $condition['phan_khuc'];
		}
		if (!empty($condition['name'])) {
			$where['data.slug_name'] = $condition['name'];
		}
		if (!empty($condition['hang_xe'])) {
			$where['data.car_company'] = $condition['hang_xe'];
		}
		if (!empty($condition['loai_xe'])) {
			$where['data.type_property'] = $condition['loai_xe'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by(['created_at' => 'DESC'])
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function count_history($condition)
	{

		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['year'])) {
			$where['data.year_property'] = $condition['year'];
		}
		if (!empty($condition['phan_khuc'])) {
			$where['data.phan_khuc'] = $condition['phan_khuc'];
		}
		if (!empty($condition['name'])) {
			$where['data.slug_name'] = $condition['name'];
		}
		if (!empty($condition['hang_xe'])) {
			$where['data.car_company'] = $condition['hang_xe'];
		}
		if (!empty($condition['loai_xe'])) {
			$where['data.type_property'] = $condition['loai_xe'];
		}
		if (!empty($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->count($this->collection);
	}

	public function find_where_history($condition)
	{
		return $this->mongo_db->where($condition)->order_by(['created_at'=>'DESC'])->get($this->collection);

	}

}

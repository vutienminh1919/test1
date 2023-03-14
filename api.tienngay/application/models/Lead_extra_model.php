<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Lead_extra_model extends CI_Model
{

	private $collection = 'lead_extra';
	private $manager;

	public function __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->find_one($this->collection);
	}

	public function findOne_langding($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
	}

	public function findOne_create($condition, $in)
	{
		return $this->mongo_db->where($condition)->where_in('status_sale', $in)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
	}

	public function count($condition = array())
	{
		if (!empty($condition)) {
			return $this->mongo_db->where($condition)->count($this->collection);
		} else {
			return $this->mongo_db->count($this->collection);
		}
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find_where_not_in($condition, $field = "", $in = "")
	{
		if (empty($in)) {
			return $this->mongo_db
				->order_by(array('created_at' => 'DESC'))
				->get_where($this->collection, $condition);
		} else {
			return $this->mongo_db
				->order_by(array('created_at' => 'DESC'))
				->where_not_in($field, $in)
				->get_where($this->collection, $condition);
		}
	}
}

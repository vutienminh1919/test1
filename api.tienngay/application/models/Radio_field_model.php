<?php if (!defined('BASEPATH')) exit('No direct script access allowed');



class Radio_field_model extends CI_Model
{
	private $collection = 'radio_field';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('name' => 'ASC'))
			->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}



}

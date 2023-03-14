<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tracking_location_model extends CI_Model
{
	private $collection = 'tracking_location';

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
			->get_where($this->collection, $condition);
	}

	public function get_location_user($condition = array(), $limit = 1, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['user_id'])) {
			$where['user_id'] = $condition['user_id'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}


}

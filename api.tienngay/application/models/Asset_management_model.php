<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Asset_management_model extends CI_Model
{
	private $collection = 'asset_management';

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
			->where($condition)->order_by(array('updated_at' => 'DESC'))->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function getMaxNumberAsset()
	{
		return $this->mongo_db
			->select(array("number_asset"))
			->order_by(array('number_asset' => 'DESC'))
			->limit(1)
			->get($this->collection);
	}

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function get_all_list_asset($condition, $limit, $offset)
	{
		$order_by = ['asset_code' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['type_asset'])) {
			$mongo = $mongo->where_in('type', $condition['type_asset']);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['text'])) {
			$mongo = $mongo->where_text($condition['text'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['asset_code'])) {
			$mongo = $mongo->like("asset_code", $condition['asset_code']);
		}
		if (!empty($condition['so_khung'])) {
			$mongo = $mongo->like("so_khung", $condition['so_khung']);
		}
		if (!empty($condition['so_may'])) {
			$mongo = $mongo->like("so_may", $condition['so_may']);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_count_all_asset($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['type_asset'])) {
			$mongo = $mongo->where_in('type', $condition['type_asset']);
		}
		if (!empty($condition['text'])) {
			$mongo = $mongo->where_text($condition['text'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['asset_code'])) {
			$mongo = $mongo->like("asset_code", $condition['asset_code']);
		}
		if (!empty($condition['so_khung'])) {
			$mongo = $mongo->like("so_khung", $condition['so_khung']);
		}
		if (!empty($condition['so_may'])) {
			$mongo = $mongo->like("so_may", $condition['so_may']);
		}
		return $mongo
			->count($this->collection);
	}
}

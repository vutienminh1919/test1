<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Sms_megadoc_model extends CI_Model
{

	private $collection = 'sms_megadoc';
	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("time_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
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

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
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
			->order_by(array('created_at' => 'DESC'))->get_where($this->collection, $condition);
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

	public function get_all_sms($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$order_by = array('created_at' => 'DESC');
		$where = array();
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['type_sms'])) {
			$where['type'] = $condition['type_sms'];
		}
		if (isset($condition['type_document'])) {
			$where['type_document'] = $condition['type_document'];
		}
		if (isset($condition['status_sms'])) {
			$where['status'] = $condition['status_sms'];
		}
		$in = $condition['stores'];
		$mongo = $mongo->set_where($where);
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);
			}
		}
		if (isset($condition['total'])) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->where_in('store.id', $in)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}
}

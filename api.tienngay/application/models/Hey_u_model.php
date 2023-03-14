<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hey_u_model extends CI_Model
{
	private $collection = 'heyu';

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

	public function get_list_hey_u($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code_driver_filter'])) {
			$where['code_driver'] = $condition['code_driver_filter'];
		}
		if (isset($condition['name_driver_filter'])) {
			$where['name_driver'] = $condition['name_driver_filter'];
		}
		if (isset($condition['code_heyu'])) {
			$where['transaction_code'] = $condition['code_heyu'];
		}
		
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else  {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}

	}

	public function count_list_hey_u($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code_driver_filter'])) {
			$where['code_driver'] = $condition['code_driver_filter'];
		}
		if (isset($condition['name_driver_filter'])) {
			$where['name_driver'] = $condition['name_driver_filter'];
		}
		if (isset($condition['code_heyu'])) {
			$where['transaction_code'] = $condition['code_heyu'];
		}
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->count($this->collection);
		}
	}

	public function get_hey_u_accounting_transfe($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['status'] = array('$in' => array(10, 3));
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->get($this->collection);
		}
	}

	public function count_hey_u_accounting_transfe($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		$where['status'] = array('$in' => array(10, 3));
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->count($this->collection);
		}
	}

	public function getNotYetSendAll($condition)
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 00:00 01/4/2021
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		$time_start_calculate_cash = $CI->config->item('date_cash_management');
		$where['created_at'] = array('$gte' => (intval(strtotime($time_start_calculate_cash))));
		$where['status'] = 10;
		
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','money','name_driver','transaction_code','created_at','approved_by','status'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','money','name_driver','transaction_code','created_at','approved_by','status'))
				->get($this->collection);
		}
	}
	public function getSendDay($condition)
	{
		if ((!empty($condition['type_transaction']) && !in_array($condition['type_transaction'], [7])) || (!empty($condition['status']) && $condition['status'] != 10)) return;
		$order_by = ['created_at' => 'ASC'];
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
		if (!empty($condition['code'])) {
			$where['transaction_code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['money'] = $condition['total'];
		}
		$where['status'] = 10;
		
		if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->select(array('_id','store','created_by','money','name_driver','transaction_code','created_at','approved_by','status'))
					->get($this->collection);
		} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->select(array('_id','store','created_by','money','name_driver','transaction_code','created_at','approved_by','status'))
					->get($this->collection);
		}
	}
	
	
}

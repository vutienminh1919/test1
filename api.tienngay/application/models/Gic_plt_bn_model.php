<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


class Gic_plt_bn_model extends CI_Model
{
	private $collection = 'gic_plt_bn';

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

	public function count_mic()
	{
		return $this->mongo_db
		   
			->count($this->collection);
	}

	public function get_list_gic_plt($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['customer_name'])) {
			$where['customer_info.customer_name'] = $condition['customer_name'];
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_info.customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['code_gic_plt'])) {
			$where['gic_code'] = $condition['code_gic_plt'];
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
		} else {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_count_gic_plt($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['customer_name'])) {
			$where['customer_info.customer_name'] = $condition['customer_name'];
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_info.customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['code_gic_plt'])) {
			$where['gic_code'] = $condition['code_gic_plt'];
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

	public function get_list_mic_store($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
		}
		if (!empty($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->get($this->collection);
	}

	public function get_gic_plt_accounting_transfe($condition)
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

	public function getMicTndsNotYetSendAll($condition)
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
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}
	public function getMicTndsSendDay($condition)
	{
		if ((!empty($condition['type_transaction']) && !in_array($condition['type_transaction'], [8])) || (!empty($condition['status']) && $condition['status'] != 10)) return;
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
		
		$where['status'] = 10;
		if (!empty($condition['code'])) {
			$where['gic_code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['mic_fee'] = $condition['total'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','price'))
				->get($this->collection);
		}

	}

	public function getGicPltNotYetSendAll($condition)
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
				->select(array('_id','store','created_by','price','gic_code','created_at','approved_by','status'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','price','gic_code','created_at','approved_by','status'))
				->get($this->collection);
		}
	}

	public function getGicPltSendDay($condition)
	{
		if ((!empty($condition['type_transaction']) && !in_array($condition['type_transaction'], [14])) || (!empty($condition['status']) && $condition['status'] != 10)) return;
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
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['fee'] = $condition['total'];
		}
		$where['status'] = 10;
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','price','gic_code','created_at','approved_by','status'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','price','gic_code','created_at','approved_by','status'))
				->get($this->collection);
		}

	}

	public function find_by_select($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['customer_info.customer_phone'] = $condition['lead_phone'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->select(['status', 'price'])
			->get($this->collection);
	}

	public function getAll_excel($condition){

		$order_by = ["created_at"=>"DESC"];
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

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}



		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		return $mongo->order_by($order_by)
			->get($this->collection);


	}


}

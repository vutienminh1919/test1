<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Call_debt_manager_model extends CI_Model
{
	private $collection = 'call_debt_manager';

	public function __construct()
	{
		parent::__construct();
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
			->order_by(array('created_at' => 'DESC'))
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

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function get_all_contract_call_assign($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['user_id'])) {
			$where['call_id'] = $condition['user_id'];
		}

		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		return $mongo
			->order_by($order_by)
			->where_not_in('status', [37])
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_all_contract_call_assign_total($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['user_id'])) {
			$where['call_id'] = $condition['user_id'];
		}

		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
			->where_not_in('status', [37])
			->count($this->collection);
	}

	public function get_all_contract_call_to_debt($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['user_id'])) {
			$where['call_id'] = $condition['user_id'];
		}

		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		return $mongo
			->order_by($order_by)
			->where_in('status', [37])
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_all_contract_call_to_debt_total($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['user_id'])) {
			$where['call_id'] = $condition['user_id'];
		}

		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
			->where_in('status', [37])
			->count($this->collection);
	}

	public function find_one_email($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->distinct($this->collection, 'call_email');
	}
}

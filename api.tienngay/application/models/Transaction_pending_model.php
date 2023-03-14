<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Transaction_pending_model extends CI_Model
{
	private $collection = 'transaction_pending';

	public function __construct()
	{
		parent::__construct();
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
	public function findOne_asc($condition)
	{
		return $this->mongo_db->order_by(array('date_pay' => 'ASC'))->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where_desc($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'DESC', 'created_at' => 'DESC'))->get_where($this->collection, $condition);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_pay_all($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'ASC', 'created_at' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
	}

	public function delete_all($condition)
	{
		return $this->mongo_db->where($condition)->delete_all($this->collection);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function getTransactionPendingByDay($condition = array(), $limit = 4000)
	{
		$order_by = ['tran_created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['tran_created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['total'] = $condition['total'];
		}
		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		$where['status'] = 11;
		$mongo = $mongo->set_where($where);
		if (isset($condition['total_record'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->count($this->collection);
			} else {
				return $mongo->order_by($order_by)
					->limit($limit)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->get($this->collection);
			} else {
				return $mongo->order_by($order_by)
					->limit($limit)
					->get($this->collection);
			}
		}
	}
}

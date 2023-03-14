<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transaction_print_model extends CI_Model
{

	private $collection = 'transaction_print';

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
		return $this->mongo_db->order_by(array('time_print' => 'ASC'))->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where_desc($condition)
	{
		return $this->mongo_db
			->order_by(array('time_print' => 'DESC'))->get_where($this->collection, $condition);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('time_print' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_pay_all($condition)
	{
		return $this->mongo_db
			->order_by(array('time_print' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('time_print' => 'DESC'))->get($this->collection);
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
			->order_by(array('time_print' => 'DESC'))
			->get($this->collection);
	}

	public function count_all($condition) {
		$query = $this->exe_condition($condition);

		return $this->mongo_db->where($query)->count($this->collection);
	}

	public function get_by_store($condition) {
		$query = $this->exe_condition($condition);

		$ops = [];
		if ( count($query) > 0 ) {
			array_push($ops, array(
				'$match' => $query
			));
		}
		array_push($ops, array(
			'$group' => array(
				'_id' => '$store',
				'count' => array('$sum' => 1),
				'time_print' => ['$last' => '$time_print'],
			),
		));
		array_push($ops, array(
			'$sort' => [
				'time_print' => -1,
			]
		));
		return $this->findAggregate($ops);
	}

	public function get_by_store_and_contract($condition) {
		$query = $this->exe_condition($condition);

		$ops = [];
		if ( count($query) > 0 ) {
			array_push($ops, array(
				'$match' => $query
			));
		}
		array_push($ops, array(
			'$group' => array(
				'_id' => [
					'store' => '$store',
					'contract' => '$code_contract_disbursement',
					'user_print' => '$user_print'
				],
				'count' => array('$sum' => 1),
				'code_contract_disbursement' => [ '$first' => '$code_contract_disbursement'],
				'code_contract' => [ '$first' => '$code_contract'],
				'customer_name' => [ '$first' => '$customer_name'],
				'user_print' => [ '$first' => '$user_print'],
				'code_transaction' => ['$first' => '$code_transaction'],
				'time_print' => ['$last' => '$time_print'],
				'help_pgd_name' => ['$first' => '$help_pgd_name'],
				'money' => ['$first' => '$money']
			),
		));
		array_push($ops, array(
			'$sort' => [
				'time_print' => -1,
			]
		));
		return $this->findAggregate($ops);
	}

	public function find_condition($condition) {
		$query = $this->exe_condition($condition);
		return $this->find_where_desc($query);
	}

	public function exe_condition($condition) {
		$query = [];
		if ( isset($condition['store']) ) {
			$listStore = explode(",", $condition['store']);
			$query['store.id'] = [
				'$in' => $listStore
			];
		}
		if ( isset($condition['code_contract_disbursement']) ) {
			$query['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		if ( isset($condition['code_contract']) ) {
			$query['code_contract'] = $condition['code_contract'];
		}
		if ( isset($condition['code_transaction']) ) {
			$query['code_transaction'] = $condition['code_transaction'];
		}
		if ( isset($condition['user_print']) ) {
			$query['user_print'] = $condition['user_print'];
		}
		if ( isset($condition['fromdate']) || isset($condition['todate']) ) {
			$query['time_print'] = [];
			if ( isset($condition['fromdate']) ) {
				$query['time_print']['$gte'] = strtotime($condition['fromdate']);
			}
			if ( isset($condition['todate']) ) {
				$query['time_print']['$lte'] = strtotime($condition['todate']. " 23:59:59");
			}
		}

		return $query;
	}

}
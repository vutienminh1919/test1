<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contract_tnds_model extends CI_Model
{
	private $collection = 'contract_tnds';

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

	public function findOneOrderBy($condition, $orderBy)
	{
		return $this->mongo_db
			->order_by($orderBy)
			->where($condition)
			->find_one($this->collection);
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

	public function delete_all($condition)
	{
		return $this->mongo_db->where($condition)->delete_all($this->collection);
	}


	public function getTnds($condition = array(),$limit = 30, $offset = 0) {
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if(isset($condition['start']) && isset($condition['end'])){
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],


			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if(!empty($condition['type_tnds'])){
			$where['contract_info.loan_infor.bao_hiem_tnds.type_tnds'] = $condition['type_tnds'];
		}
	
		if(isset($condition['phone']) && !empty($condition['phone'])){
			$where['contract_info.customer_infor.customer_phone_number'] = $condition['phone'];
		}
	
		$mongo = $mongo->set_where($where);
       if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("contract_info.code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['full_name'])) {
			$mongo = $mongo->where_text($condition['full_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
        if(!isset($condition['count']))
        {
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);

			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}else{
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
				->count($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->count($this->collection);
		}
	}
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

		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}

		if (isset($condition['store.id'])) {
			$where['store.id'] = $condition['store.id'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		return $mongo->order_by($order_by)
			->get($this->collection);


	}

	

}

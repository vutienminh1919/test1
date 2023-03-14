<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Borrow_paper_model extends CI_Model
{

	private $collection = 'borrow_paper';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
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
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->find_one($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function getDataByRole($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ["created_at" => "DESC"];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['created_at'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		if (isset($condition['stores_list'])) {
			$where['store.store.id'] = array('$in' => $condition['stores_list']);
		}

		if (isset($condition['store'])){
			$where['store.store.id'] = $condition['store'];
		}

		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['code_contract_disbursement_text'])) {
			$mongo = $mongo->like("code_contract_disbursement_value", $condition['code_contract_disbursement_text']);
		}

		return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);

	}

	public function getDataByRole_count($condition = array()){

		$order_by = ["created_at" => "DESC"];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['created_at'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		if (isset($condition['stores_list'])) {
			$where['store.store.id'] = array('$in' => $condition['stores_list']);
		}

		if (isset($condition['store'])){
			$where['store.store.id'] = $condition['store'];
		}


		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['code_contract_disbursement_text'])) {
			$mongo = $mongo->like("code_contract_disbursement_value", $condition['code_contract_disbursement_text']);
		}

		return $mongo->order_by($order_by)
			->count($this->collection);


	}








}

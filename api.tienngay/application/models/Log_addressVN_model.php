<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log_addressVN_model extends CI_Model
{

	private $collection = 'log_addressVN';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("transaction_model");
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
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)->find_one($this->collection);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}

	public function find_one_select($condition, $select)
	{
		return $this->mongo_db
			->select($select)
			->where($condition)
			->find_one($this->collection);
	}

	public function findOneAndUpdate($where = "", $inforUupdate = "")
	{
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function countContract()
	{
		$condition = array(
			'type' => array('$ne' => "vaynhanh")
		);
		return $this->mongo_db
			->where($condition)->count($this->collection);
	}


	public function countOldContract()
	{
		$condition = array(
			'type' => "old_contract"
		);
		return $this->mongo_db
			->where($condition)->count($this->collection);
	}

	public function count_in($field = "", $in = array())
	{
		return $this->mongo_db->where_in($field, $in)->count($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}

	public function find_where_order_by($condition, $orderBy = array())
	{
		return $this->mongo_db
			->order_by($orderBy)
			->get_where($this->collection, $condition);
	}

	public function findContract()
	{
		$condition = array(
			'type' => array('$ne' => "vaynhanh")
		);
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


	public function find_select()
	{
		return $this->mongo_db
			->select(array("code_contract"))
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}










}

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_sendfile_model extends CI_Model
{

	private $collection = 'log_sendfile';

	public function  __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}
	public function insert($data){
		return $this->mongo_db->insert($this->collection, $data);
	}
	public function findOne($condition){
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function find_where($condition){
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}
	public function find(){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function delete($condition){
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find_where_not_in($condition, $field="", $in=""){
		if(empty($in)) {
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
	public function getLogs($condition){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}

	public function find_where_select($condition, $value){
		return $this->mongo_db
			->select($value)
			->get_where($this->collection, $condition);
	}

	public function insertLog($type="", $action="", $old="", $new="", $createdAt="", $createdBy="") {
		$data = array(
			"type" => $type,
			"action" => $action,
			"old" => $old,
			"new" => $new,
			"created_at" => $createdAt,
			"created_by" => $createdBy
		);
		$this->insert($data);
	}

	public function find_where_in($value)
	{

		$mongo = $this->mongo_db;
//		if (isset($condition['start']) && isset($condition['end'])) {
//			$where['created_at'] = array(
//				'$gte' => $condition['start'],
//				'$lte' => $condition['end']
//			);
//			unset($condition['start']);
//			unset($condition['end']);
//		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$condition['code_contract'] = $value;



		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("8","6","5","3"));
		}

		return $mongo
			->select(array("new.amount_loan",'contract_id','old.created_at', 'new.status','old.code_contract','old.store.name','old.customer_infor.customer_name','old.customer_infor.customer_identify','old.loan_infor.loan_product.text','old.loan_infor.amount_money','old.status','created_by','created_at','new.error_code','new.lead_cancel1_C1','new.lead_cancel1_C2','new.lead_cancel1_C3','new.lead_cancel1_C4','new.lead_cancel1_C5','new.lead_cancel1_C6','new.lead_cancel1_C7','new.exception1_value_detail','new.exception2_value_detail','new.exception3_value_detail','new.exception4_value_detail','new.exception5_value_detail','new.exception6_value_detail','new.exception7_value_detail','new.reason'))
			->order_by(array('created_at' => 'ASC'))->get($this->collection);
	}

	public function find_where_in_count($value)
	{
		$mongo = $this->mongo_db;
		$condition['code_contract'] = $value;
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("8"));
		}
		return $mongo->count($this->collection);
	}

	public function find_where_in_AT($value)
	{

		$mongo = $this->mongo_db;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$condition['contract_id'] = $value;

		$mongo = $mongo->where_in('contract_id', array($condition['contract_id']));

		$mongo = $mongo->where_in('new.status', array("3"));

		return $mongo->get($this->collection);
	}

	public function find_where_check($field="", $in=array()){
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->limit(1)->get($this->collection);
	}

	public function find_where_in_kt($value)
	{

		$mongo = $this->mongo_db;
//		if (isset($condition['start']) && isset($condition['end'])) {
//			$where['created_at'] = array(
//				'$gte' => $condition['start'],
//				'$lte' => $condition['end']
//			);
//			unset($condition['start']);
//			unset($condition['end']);
//		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$condition['code_contract'] = $value;


		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("7","15","3",17));
		}

		return $mongo
			->select(array("new.amount_loan",'contract_id','old.created_at', 'new.status','old.code_contract','old.store.name','old.customer_infor.customer_name','old.customer_infor.customer_identify','old.loan_infor.loan_product.text','old.loan_infor.amount_loan','old.status','created_by','created_at',"new.note"))
			->order_by(array('created_at' => 'ASC'))->get($this->collection);
	}

	public function find_where_in_count_kt($value)
	{
		$mongo = $this->mongo_db;
		$condition['code_contract'] = $value;
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("7"));
		}
		return $mongo->count($this->collection);
	}

	public function find_where_in_cancel($value)
	{

		$mongo = $this->mongo_db;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$condition['code_contract'] = $value;

		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("8"));
		}

		return $mongo
			->select(array('new.status','created_at',"old.code_contract"))
			->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}




}

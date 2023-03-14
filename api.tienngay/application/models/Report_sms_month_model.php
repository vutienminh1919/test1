<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_sms_month_model extends CI_Model
{

	private $collection = 'report_sms_month';


	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		
		$this->manager = new MongoDB\Driver\Manager("mongodb://" . $this->config->item("ip_db") . ":27017");
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}
	public function findOne($condition){
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function find_where($field="", $in=array()){
		return $this->mongo_db->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
		public function getMonth($condition)
	{
		if (isset($condition['month']) && isset($condition['year'])) {
			$where['month'] =(string)$condition['month'];
			$where['year'] =(string)$condition['year'];
		}

		$mongo = $this->mongo_db;
		// if (!empty($where)) {
		// 	$where['code_contract_parent_gh'] = array('$exists' => false);
		// 	$where['code_contract_parent_cc'] = array('$exists' => false);
		// 	$where['status'] = array('$gte' => 17);

		$mongo = $mongo->set_where($where);
		// }
		return $mongo
			//->select(array("code_contract", "disbursement_date"))
			->get($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function get_where($condition){
		return $this->mongo_db->where($condition)->order_by(array('sum_giai_ngan' => 'DESC'))->get($this->collection);
	}
	public function insert($data)
		{
			return $this->mongo_db->insert($this->collection, $data);
		}
}

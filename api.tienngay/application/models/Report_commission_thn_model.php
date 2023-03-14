<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_commission_thn_model extends CI_Model
{

	private $collection = 'report_commission_thn';
	private $collection_ct = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}
	public function findOne($condition){
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function find_where($condition){
		return $this->mongo_db
			->where($condition)->order_by(array('updated_at' => 'DESC'))->get($this->collection);
	}
	public function get_where($condition){
		return $this->mongo_db->where($condition)->order_by(array('sum_giai_ngan' => 'DESC'))->get($this->collection);
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}
	public function delete($condition){
		return $this->mongo_db->where($condition)->delete($this->collection);
	}



	public function sum_where($condtion = array(),$get){
		$ops = array(
			array (
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' =>  $get ),
				),
			),
		);
		$data = $this->mongo_db->aggregate($this->collection, $ops)->toArray();
		if(isset($data[0]['total'])){
			return $data[0]['total'];
		}else{
			return 0;
		}

	}
	public function getKpiByTime($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['month' => 'DESC','year' => 'DESC'];
		if (isset($condition['month'])) {
			$where['month'] =$condition['month'];
		}
		if (isset($condition['year'])) {
			$where['year'] =$condition['year'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['code_area'])) {
			$mongo = $mongo->where_in('code_area', $condition['code_area']);
		}
		if (isset($condition['code_region'])) {
			$mongo = $mongo->where_in('code_region', $condition['code_region']);
		}
		if (isset($condition['code_domain'])) {
			$mongo = $mongo->where_in('code_domain', $condition['code_domain']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('store.id', $condition['code_store']);
		}
		if (isset($condition['customer_email'])) {
			$mongo = $mongo->like("user_email", $condition['customer_email']);
		}


		if (isset($condition['total'])) {
			return $mongo->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function sum_where_total($condtion = array(), $get)
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get),
				),
			),
		);
		$data = $this->mongo_db->aggregate($this->collection, $ops)->toArray();
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}
	public function sum_where_total_mongo_read($condtion = array(), $get)
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get),
				),
			),
		);
		$data = $this->mongo_db_read->aggregate($this->collection, $ops)->toArray();
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}

	public function getCountByRole($condition){

		$order_by = ["created_at" => "DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['year'])) {
			$where['year'] =$condition['year'];
		}
		if (isset($condition['month'])) {
			$where['month'] =$condition['month'];
		}


		if (isset($condition['created_by'])) {
			$where['user'] =$condition['created_by'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)
			->count($this->collection);


	}

	public function getDataByRole($condition,  $limit = 30, $offset = 0){

		$order_by = ["created_at" => "DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['year'])) {
			$where['year'] = $condition['year'];
		}
		if (isset($condition['month'])) {

			$where['month'] = $condition['month'];
		}

		if (isset($condition['created_by'])) {
			$where['user'] = $condition['created_by'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)

			->limit($limit)
			->offset($offset)
			->get($this->collection);


	}

}

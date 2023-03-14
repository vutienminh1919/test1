<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Collaborator_model extends CI_Model
{
	private $collection = 'collaborator';

	public function  __construct()
	{
		parent::__construct();
	}

	public function find(){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
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
	public function find_where($field="", $in=array()){
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function delete($condition){
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find_where_not_in($condition = array(),$limit = 30, $offset = 0) {
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
	public function sum_where($condtion = array(),$get){
		$ops = array(
			array (
				'$match' => $condtion
			),

			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' =>  array('$toDouble'=>$get )),
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


	public function getByRole($condition)
	{
		$order_by = ['created_at' => 'DESC'];

		$where = array();

		$mongo = $this->mongo_db;

		if (isset($condition['check_flag'])) {
			$where['type'] = "1";
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['created_by'])) {
			$mongo = $mongo->or_where(array('created_by'=> $condition['created_by'] , 'phone_introduce' => $condition['phone_introduce'] ));
		}

		return $mongo->order_by($order_by)
			->get($this->collection);


	}

	public function find_notNumberCode(){

		$where = array();
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];

//		$where['number_code_ctv'] = array('$exists' => false);
//		$where['ctv_name'] = "Hoàng Thị Thuỳ Dung";


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)->get($this->collection);

	}

	public function getMaxNumberCodeCTV()
	{
		return $this->mongo_db
			->select(array("number_code_ctv"))
			->order_by(array('number_code_ctv' => 'DESC'))
			->limit(1)
			->get($this->collection);
	}

	public function getAllCTVIntro($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['ctv_name'])) {
			$where['ctv_name'] = $condition['ctv_name'];
		}
		if (isset($condition['ctv_phone'])) {
			$where['ctv_phone'] = $condition['ctv_phone'];
		}
		$where['form'] = '1';
		$where['user_type'] = '1';
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['total'])) {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->count($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function getAllGroup()
	{
		$mongo = $this->mongo_db;
		$order_by = ["created_at" => "DESC"];
		$where = array();
		$where['status'] = "active";
		$where['account_type'] = "1";
		$where['form'] = '2';
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}


}



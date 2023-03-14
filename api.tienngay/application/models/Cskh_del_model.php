<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Cskh_del_model extends CI_Model
{
	private $collection = 'cskh_del';

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


	public function getByRole($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];

		$where = array();

		$mongo = $this->mongo_db;

		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}


		return $mongo->order_by($order_by)->get($this->collection);



	}

	public function find_cskh_del_day($condition){
		$order_by = ['created_at' => 'DESC'];
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

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function delete_desc($condition){
		$order_by = ['created_at' => 'DESC'];
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

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)->limit(1)->get($this->collection);


	}


}



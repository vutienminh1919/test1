<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Order_model extends CI_Model
{

    private $collection = 'order';

    public function  __construct()
    {
        parent::__construct();
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
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
    }
    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }
	public function sum_where($condtion = array(),$get){
		$ops = array(
			array (
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
		if(isset($data[0]['total'])){
			return $data[0]['total'];
		}else{
			return 0;
		}

	}
	public function get_list_billing_utilities($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['trading_code'])) {
			$where['mc_request_id'] = $condition['trading_code'];
		}
		if (isset($condition['service_name'])) {
			$where['detail.publisher'] = $condition['service_name'];
		}
		if (isset($condition['publisher_name'])) {
			$where['publisher_name'] = $condition['publisher_name'];
		}
		if (isset($condition['service_code'])) {
			$where['service_code'] = $condition['service_code'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else  {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}
	public function count_list_billing_utilities($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['trading_code'])) {
			$where['mc_request_id'] = $condition['trading_code'];
		}
		if (isset($condition['service_name'])) {
			$where['detail.publisher'] = $condition['service_name'];
		}
		if (isset($condition['publisher_name'])) {
			$where['publisher_name'] = $condition['publisher_name'];
		}
		if (isset($condition['service_code'])) {
			$where['service_code'] = $condition['service_code'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->count($this->collection);
		}
	}

	public function getDataByRole($condition){
		$mongo = $this->mongo_db;
		$where = array();

		if (isset($condition['transaction_code'])) {
			$where['transaction_code'] = $condition['transaction_code'];
		}

		$where['response_error.error_code'] = "00";

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->get($this->collection);
	}
}

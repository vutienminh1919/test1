<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: phanc
 * Date: 5/6/2020
 * Time: 11:24 AM
 */

class Baddebt_model extends CI_Model
{

	private $collection = 'baddebt';

	public function  __construct()
	{
		parent::__construct();
	}

	public function find_search($where = array()){
		$inputs['import_at'] = array(
			'$lte' => $where['end'],
			'$gte' => $where['start'],
		);
		unset($where['start']);
		unset($where['end']);
		$in = array("hidden");
		return $this->mongo_db
			->where_not_in('status', $in)
			->get_where($this->collection, $inputs);
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
	public function getAll(){
		$in = array("hidden");
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->where_not_in('status', $in)
			->get($this->collection);
	}
	public function findOneAndUpdate($where="", $inforUupdate="") {
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}
	public function getPagination( $limit = 30, $offset = 0, $condition){
		$mongo = $this->mongo_db;
		$where = array();
		if(isset($condition['start']) && isset($condition['end'])){
			$where['date_maturity'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if(!empty($condition['name'])){
			$mongo = $mongo->like("name", $condition['name']);
		}
		if(!empty($condition['identify'])){
			$mongo = $mongo->like("identify", $condition['identify']);
		}
		if(!empty($condition['number_phone'])){
			$mongo = $mongo->like("number_phone", $condition['number_phone']);
		}
		if(!empty($condition['code_contract'])){
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}
		return $mongo->order_by(array('update_at' => 'DESC'))
			->limit($limit)->offset($offset)
			->get($this->collection);
	}
	public function getTotal($condition){
		$mongo = $this->mongo_db;
		if(isset($condition['start']) && isset($condition['end'])){
			$where['date_maturity'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if(!empty($condition['name'])){
			$mongo = $mongo->like("name",$condition['name']);
		}
		if(!empty($condition['identify'])){
			$mongo = $mongo->like("identify",$condition['identify']);
		}
		if(!empty($condition['number_phone'])){
			$mongo = $mongo->like("number_phone",$condition['number_phone']);
		}
		if(!empty($condition['code_contract'])){
			$mongo = $mongo->like("code_contract",$condition['code_contract']);
		}
		return $mongo->order_by(array('createdAt' => 'DESC'))
			->count($this->collection);
	}
	public function find_one_select($condition, $select){
		return $this->mongo_db
			->select($select)
			->where($condition)
			->find_one($this->collection);
	}
}

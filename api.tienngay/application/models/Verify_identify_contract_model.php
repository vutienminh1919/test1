<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Verify_identify_contract_model extends CI_Model
{

	private $collection = 'verify_identify_contract';

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
	public function find(){
		return $this->mongo_db
			->get($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function delete($condition){
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

}

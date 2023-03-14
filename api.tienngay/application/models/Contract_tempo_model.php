<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Contract_tempo_model extends CI_Model
{

    private $collection = 'temporary_plan_contract';

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
    public function findOneOrderBy($condition, $orderBy){
        return $this->mongo_db
                ->order_by($orderBy)
                ->where($condition)
                ->find_one($this->collection);
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
    public function delete_all($condition){
		return $this->mongo_db->where($condition)->delete_all($this->collection);
	}
    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }

    public function getMaxNumberContract() {
        return $this->mongo_db
            ->select(array("number_contract"))
            ->order_by(array('number_contract' => 'DESC'))
            ->limit(1)
            ->get($this->collection);
    }

    public function getContract($condition) {
		return $this->mongo_db
			->order_by(array('created_at' => 'ASC'))
			->limit(1)
			->get_where($this->collection, $condition);
	}
	public function getAll($condition) {
		return $this->mongo_db
			->order_by(array('created_at' => 'ASC'))
			->get_where($this->collection, $condition);
	}
	public function countContractTempobyTime($time) {
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		$where = [];
		if (!empty($time['code_contract'])) {
			$where['code_contract'] = $time['code_contract'];
		}
		if(isset($time['end'])){
			$where['ngay_ky_tra'] = array(
				'$gte' => 0,
				'$lte' => intval($time['end'])
			);
		}
		$where['status'] = 1;
		$mongo = $mongo->set_where($where);
		return $mongo->order_by($order_by)
			->get($this->collection);
	}

	public function getContractTempobyTime($time) {
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		$where = [];
		if (!empty($time['code_contract'])) {
			$where['code_contract'] = $time['code_contract'];
		}
		if(isset($time['end'])){
			$where['ngay_ky_tra'] = array(
				'$gte' => 0,
				'$lte' => intval($time['end'])
			);
		}
		if(isset($time['status'])){
		$where['status'] = intval($time['status']);
	   }
		$mongo = $mongo->set_where($where);
		return $mongo->order_by($order_by)
			->get($this->collection);
	}
	public function getContractInvestorTime($time) {
			$mongo = $this->mongo_db;
			$order_by = ['created_at' => 'DESC'];
			$where = [];
			if (!empty($time['code_contract'])) {
				$where['code_contract'] = $time['code_contract'];
			}

			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	public function findTop($limit=1, $condition, $offset=1){
		$arr = $this->mongo_db
			->order_by(array('created_at' => 'asc'))
			->limit($limit)
			->offset($offset)
			->get_where($this->collection, $condition);
		return $arr;
	}
	public function find_where_success($condition){
		$arr = $this->mongo_db
			->order_by(array('created_at' => 'asc'))
			->get_where($this->collection, $condition);
		return $arr;
	}

	public function findOneAndUpdate($where="", $inforUupdate="") {
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}
}

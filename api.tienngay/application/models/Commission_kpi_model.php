<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Commission_kpi_model extends CI_Model
{
	private $collection = 'commission_kpi';
	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}


	public function getPropertyValuation($searchLike, $where = array())
	{
		$mongo = $this->mongo_db;
		$order_by = [' ' => 'DESC'];
		if (isset($where['start']) && isset($where['end'])) {
			$where['create_at'] = array(
				'$gte' => $where['start'],
				'$lte' => $where['end']
			);
			unset($where['start']);
			unset($where['end']);
			unset($where['count']);
		}

		$mongo = $mongo->set_where($where);
		if (!empty($searchLike)) {
			// var_dump($field);die;
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);

			}
		}
		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function findPropertyByName($searchLike)
	{
		if (isset($searchLike)) {
			return $this->mongo_db->where("name", $searchLike)->get($this->collection);
		}
	}

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function findOneAndUpdate($where="", $inforUupdate="") {
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
}

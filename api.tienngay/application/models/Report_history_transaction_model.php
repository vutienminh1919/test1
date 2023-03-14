
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report_history_transaction_model extends CI_Model
{
	private $collection = 'report_history_transaction';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function findOne_asc($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->where($condition)
			->get($this->collection);
	}

	public function find($condition, $limit, $offset){
		return $this->mongo_db
			->where($condition)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function sum_data($condition)
	{
		if (count($condition) > 0) {
			$ops = array(
				array(
					'$match' => $condition
				),
				array(
					'$group' => array(
						'_id' => 'all',
						'tien_goc_da_thu_hoi' => array('$sum' => '$tien_goc_da_thu_hoi'),
						'tien_lai_da_thu_hoi' => array('$sum' => '$tien_lai_da_thu_hoi'),
						'tien_phi_da_thu_hoi' => array('$sum' => '$tien_phi_da_thu_hoi'),
						'tien_phi_gia_han_da_thu_hoi' => array('$sum' => '$tien_phi_gia_han_da_thu_hoi'),
						'tien_phi_cham_tra_da_thu_hoi' => array('$sum' => '$tien_phi_cham_tra_da_thu_hoi'),
						'tien_phi_truoc_han_da_thu_hoi' => array('$sum' => '$tien_phi_truoc_han_da_thu_hoi'),
						'tien_phi_qua_han_da_thu_hoi' => array('$sum' => '$tien_phi_qua_han_da_thu_hoi'),
						'tong_thu_hoi_thuc_te' => array('$sum' => '$tong_thu_hoi_thuc_te'),
					),
				)
			);
		} else {
			$ops = array(
				array(
					'$group' => array(
						'_id' => 'all',
						'tien_goc_da_thu_hoi' => array('$sum' => '$tien_goc_da_thu_hoi'),
						'tien_lai_da_thu_hoi' => array('$sum' => '$tien_lai_da_thu_hoi'),
						'tien_phi_da_thu_hoi' => array('$sum' => '$tien_phi_da_thu_hoi'),
						'tien_phi_gia_han_da_thu_hoi' => array('$sum' => '$tien_phi_gia_han_da_thu_hoi'),
						'tien_phi_cham_tra_da_thu_hoi' => array('$sum' => '$tien_phi_cham_tra_da_thu_hoi'),
						'tien_phi_truoc_han_da_thu_hoi' => array('$sum' => '$tien_phi_truoc_han_da_thu_hoi'),
						'tien_phi_qua_han_da_thu_hoi' => array('$sum' => '$tien_phi_qua_han_da_thu_hoi'),
						'tong_thu_hoi_thuc_te' => array('$sum' => '$tong_thu_hoi_thuc_te'),
					),
				)
			);
		}
		return $this->findAggregate($ops);
	}

}



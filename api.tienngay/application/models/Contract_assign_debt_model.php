<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Contract_assign_debt_model extends CI_Model
{
	private $collection = 'contract_assign_debt';

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
			->order_by(array('created_at' => 'DESC'))
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

	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
	}

	public function findOneAndUpdate($where = "", $inforUupdate = "")
	{
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function get_all_contract_assign_to_field($searchLike, $condition, $limit = 30, $offset = 0)
	{
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
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}

		if (isset($condition['store'])) {
			$where['store_id'] = $condition['store'];
		}

		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (!empty($condition['email'])) {
			$where['debt_field_email'] = $condition['email'];
		}
		$current_month = (string)date('m');
		$current_year = (string)date('Y');
		$where['domain_contract'] = $condition['domain_contract'];
		$where['month'] = $current_month;
		$where['year'] = $current_year;
		$where['status_contract'] = array('$ne' => 19);
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['tab']) && $condition['tab'] == 'all') {
			$mongo = $mongo->where_in('status', array(1,2,3,4,279));
		}
		if (isset($condition['tab']) && $condition['tab'] == 'review') {
			$mongo = $mongo->where_in('status', array(1));
		}
		if (isset($condition['tab']) && $condition['tab'] == 'assigned') {
			$mongo = $mongo->where_in('status', array(2));
		}
		if (isset($condition['tab']) && $condition['tab'] == 'remaining') {
			$mongo = $mongo->where_in('status', array(2));
			$mongo = $mongo->where_in('evaluate', array(3));
		}
		if (isset($condition['tab']) && $condition['tab'] == 'block') {
			$mongo = $mongo->where_in('status', array(3));
		}
		if (isset($condition['tab']) && $condition['tab'] == 'cancel') {
			$mongo = $mongo->where_in('status', array(4));
		}

		if (isset($condition['debt_field_email'])) {
			$mongo = $mongo->where_in('debt_field_email', [$condition['debt_field_email']]);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['customer_phone'])) {
			$mongo = $mongo->like("customer_phone_number", $condition['customer_phone']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);
			}
		}
		if (isset($condition['total']) && $condition['total']) {
			return $mongo
				->order_by($order_by)
				->count($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_all_contract_review($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		$current_month = (string)date('m');
		$current_year = (string)date('Y');
		$where['domain_contract'] = $condition['domain_contract'];
		$where['month'] = $current_month;
		$where['year'] = $current_year;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['tab']) && $condition['tab'] == 'review') {
			$mongo = $mongo->where_in('status', array(1));
		}
		if (isset($condition['debt_caller_email'])) {
			$mongo = $mongo->where_in('debt_caller_email', [$condition['debt_caller_email']]);
		}
		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}

	public function getAllByCurrentMonth()
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		$current_month = (string)date('m');
		$current_year = (string)date('Y');
		$where['month'] = $current_month;
		$where['year'] = $current_year;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
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

	public function select_where($year, $month, $list_user_field, $bucket = []){

		$where = array();
		$mongo = $this->mongo_db;

		$where['year'] = $year;
		$where['month'] = $month;
		$where['status'] = ['$in' => [2]];

		$where['debt_field_email'] = ['$in' => $list_user_field];

		if (isset($bucket) && $bucket != []){
			$where['bucket_old'] = ['$in' => $bucket];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->select(['code_contract','pos_du_no','bucket_old','bucket'])
			->get($this->collection);

	}

	public function select_where_count($year, $month, $list_user_field, $bucket = []){

		$where = array();
		$mongo = $this->mongo_db;

		$where['year'] = $year;
		$where['month'] = $month;
		$where['status'] = ['$in' => [2]];

		$where['debt_field_email'] = $list_user_field;

		if (isset($bucket) && $bucket != []){
			$where['bucket_old'] = ['$in' => $bucket];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->count($this->collection);

	}

	public function getAllContractField($condition)
	{
		$where = [];
		$mongo = $this->mongo_db;
		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		$where['status'] = ['$in' => [2]];
		$where['domain_contract'] = $condition['domain_contract'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by(['created_at' => 'DESC'])
			->get($this->collection);
	}

	public function getAllContractFieldByUser($condition)
	{
		$where = [];
		$mongo = $this->mongo_db;
		$where['month'] = $condition['month'];
		$where['year'] = $condition['year'];
		$where['debt_field_email'] = $condition['user'];
		$where['domain_contract'] = $condition['domain_contract'];
		$where['status'] = ['$in' => [2]];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by(['created_at' => 'DESC'])
			->get($this->collection);
	}


}

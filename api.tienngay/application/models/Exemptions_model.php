<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Exemptions_model extends CI_Model
{

	private $collection = 'exemptions';
	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("time_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
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

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}

	public function find_one_select($condition, $select)
	{
		return $this->mongo_db
			->select($select)
			->where($condition)
			->find_one($this->collection);
	}

	public function findOneAndUpdate($where = "", $inforUupdate = "")
	{
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function countContract()
	{
		$condition = array(
			'type' => array('$ne' => "vaynhanh")
		);
		return $this->mongo_db
			->where($condition)->count($this->collection);
	}


	public function countOldContract()
	{
		$condition = array(
			'type' => "old_contract"
		);
		return $this->mongo_db
			->where($condition)->count($this->collection);
	}

	public function count_in($field = "", $in = array())
	{
		return $this->mongo_db->where_in($field, $in)->count($this->collection);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}

	public function find_where_order_by($condition, $orderBy = array())
	{
		return $this->mongo_db
			->order_by($orderBy)
			->get_where($this->collection, $condition);
	}

	public function findContract()
	{
		$condition = array(
			'type' => array('$ne' => "vaynhanh")
		);
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))->get_where($this->collection, $condition);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function delete_all($condition)
	{
		return $this->mongo_db->where($condition)->delete_all($this->collection);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function find_select()
	{
		return $this->mongo_db
			->select(array("code_contract"))
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function get_all($searchLike,$condition = array(), $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$order_by = ['created_profile_at' => "DESC"];
		$where = [];
		$in = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_profile_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}

		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);
			}
		}
		if (isset($condition['total'])) {
			return $mongo->order_by($order_by)
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function count_application_exemptions()
	{
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => "DESC"];
		$where = [];
		$in = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}

		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}

		if (isset($condition['customer_name'])) {
			$where['customer_name'] = $condition['customer_name'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);

			}
		}
		return $mongo->order_by($order_by)
					 ->count($this->collection);
	}


	public function exportExcelExemption($condition){

		$mongo = $this->mongo_db;
		$order_by = ['created_profile_at' => "DESC"];
		$where = [];

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_profile_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)
			->select(['code_contract','code_contract_disbursement','customer_name','status','note_tp_thn','amount_tp_thn_suggest'])
			->get($this->collection);


	}
	//lấy hết hợp đồng đã được duyệt giảm (blackList)
	public function getAllContractExempted($searchLike,$condition = array(), $limit = 30, $offset = 0) {
		$mongo = $this->mongo_db;
		$order_by = ['date_suggest' => "DESC"];
		$where = [];
		$in = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['date_suggest'] = array(
				'$gte' => (int)$condition['start'],
				'$lte' => (int)$condition['end']
			);
			
		} else if (isset($condition['start'])) {
			$where['date_suggest'] = array(
				'$gte' => (int)$condition['start'],
			);

		} else if (isset($condition['end'])) {
			$where['date_suggest'] = array(
				'$lte' => (int)$condition['end']
			);
		}
		
		if (!empty($condition['store_id'])) {
			$where['store.id'] = $condition['store_id'];
		}
		if (!empty($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (!empty($condition['customer_identify'])) {
			$where['customer_identify'] = $condition['customer_identify'];
		}
		if (!empty($condition['customer_phone_number'])) {
			$where["customer_phone_number"] = $condition['customer_phone_number'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		// if (!empty($condition['customer_name'])) {
		// 	$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		// }
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_name", $condition['customer_name']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);
			}
		}
		if (isset($condition['total'])) {
			return $mongo->order_by($order_by)
			->where(['status' => ['$in' => [5,7]]])
			->count($this->collection);
		} else {
			if (isset($condition['code_contract_disbursement']) && $condition['code_contract_disbursement'] == false) {
				return 0;
			}
			if (isset($condition['code_contract']) && $condition['code_contract'] == false) {
				return 0;
			}
			if (isset($condition['customer_phone_number']) && $condition['customer_phone_number'] == false) {
				return 0;
			}
			// if (isset($condition['customer_identify']) && $condition['customer_identify'] == false) {
			// 	return 0;
			// }
			return $mongo->order_by($order_by)
			->where(['status' => ['$in' => [5,7]]])
			->limit($limit)
			->offset($offset)
			->get($this->collection);
		}
	}

	//xuất đơn miễn giảm
	public function exportExcelExempted($condition){

		$mongo = $this->mongo_db;
		$order_by = ['date_suggest' => "DESC"];
		$where = [];
		$in = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['date_suggest'] = array(
				'$gte' => (int)$condition['start'],
				'$lte' => (int)$condition['end']
			);
			
		} else if (isset($condition['start'])) {
			$where['date_suggest'] = array(
				'$gte' => (int)$condition['start'],
			);

		} else if (isset($condition['end'])) {
			$where['date_suggest'] = array(
				'$lte' => (int)$condition['end']
			);
		}
		if(!empty($condition['customer_identify'])) {
			$where['customer_identify'] = $condition['customer_identify'];
		}
		if(!empty($condition['customer_phone_number'])) {
			$where['customer_phone_number'] = $condition['customer_phone_number'];
		}

		if(!empty($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (!empty($condition['store_id'])) {
			$where['store.id'] = $condition['store_id'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_name", $condition['customer_name']);
		}
		if(!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like('code_contract_disbursement' ,$condition['code_contract_disbursement']);
		}
		// var_dump($where);
		return $mongo->order_by($order_by)
			->where(['status' => ['$in' => [5,7]]])
			->select(['code_contract','code_contract_disbursement','customer_name','status','note_tp_thn','amount_tp_thn_suggest'])
			->get($this->collection);

	}
}

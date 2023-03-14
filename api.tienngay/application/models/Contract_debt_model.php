<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contract_debt_model extends CI_Model
{

	private $collection = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'], $this->config->item("mongo_db")['options']);
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

	public function getCountContractByRole($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		} else {
			$where['type'] = array('$ne' => "vaynhanh");
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->where_in_like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->where_in_like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['customer_identify'])) {
			$mongo = $mongo->where_in_like("customer_infor.customer_identify", $condition['customer_identify']);
		}
		if (!empty($condition['asset_name'])) {
			$mongo = $mongo->like("loan_infor.name_property.text", $condition['asset_name']);
		}

		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->count($this->collection);
		}
	}

	public function getCountOldContract($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		$where['type'] = array('$ne' => "vaynhanh");
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)->where("type", "old_contract")
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)->where("type", "old_contract")
				->count($this->collection);
		}
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

	public function findPaginationContract($per_page, $uriSegment)
	{
		$condition = array(
			'status_disbursement' => 3, //1 moi tao, 2: tạo giải ngân thành công, 3// tiền đến với khách, 4 thất bại
			'status' => 17  // giải ngân thành công, 18: giải ngân thất bại
		);
		return $this->mongo_db->where($condition)
			->order_by(array('created_at' => 'DESC'))->limit($per_page)->offset($uriSegment)
			->get($this->collection);
	}

	public function getMaxNumberContract()
	{
		return $this->mongo_db
			->select(array("number_contract"))
			->order_by(array('number_contract' => 'DESC'))
			->limit(1)
			->get($this->collection);
	}

	public function checkContractInvolve($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['status'])) {
			if ($condition['status'] != "") {
				$where['status'] = (int)$condition['status'];
			}
		} else {
			$where['status'] = array(
				'$gte' => 17
			);
		}

		$condition = array('$or' => array(array('customer_infor.customer_phone_number' => $condition['customer_phone_number']), array('customer_infor.customer_identify' => $condition['customer_identify'], array('relative_infor.phone_number_relative_1' => $condition['phone_number_relative_1']), array('relative_infor.phone_number_relative_2' => $condition['phone_number_relative_2']))));

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->get_where($this->collection, $condition);
	}

	public function checkContract($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'DESC'];

		if (isset($condition['customer_phone_number'])) {
			$where['customer_infor.customer_phone_number'] = $condition['customer_phone_number'];
		}
		if (isset($condition['customer_identify'])) {
			$mongo = $mongo->or_where(array("customer_infor.customer_identify" => $condition["customer_identify"], "customer_infor.customer_identify_old" => $condition["customer_identify"]));
		}
		if (isset($condition['customer_identify_old'])) {
			$mongo = $mongo->or_where(array("customer_infor.customer_identify" => $condition["customer_identify_old"], "customer_infor.customer_identify_old" => $condition["customer_identify_old"]));
		}
		if (isset($condition['phone_number_relative_1'])) {
			$where['relative_infor.phone_number_relative_1'] = $condition['phone_number_relative_1'];
		}
		if (isset($condition['phone_number_relative_2'])) {
			$where['relative_infor.phone_number_relative_2'] = $condition['phone_number_relative_2'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->get($this->collection);
	}

	public function get_temporary_contract_kt($condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] != "") {

				$where['status'] = (int)$condition['status'];
			}
		} else {
			$where['status'] = array(
				'$gte' => 17
			);
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];

		}
		// if (isset($condition['code_contract'])) {
		// 	$where['code_contract'] = $condition['code_contract'];

		// }

		if (isset($condition['tab'])) {
			if ($condition['tab'] == "import_payment") {
				$where['status_run_fee_again'] = array(
					'$ne' => 2
				);
			}


		}

		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['status_create_withdrawal_nl'])) {
			$where['status_create_withdrawal_nl'] = $condition['status_create_withdrawal_nl'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['code_contract'])) {
			if (is_array($condition['code_contract'])) {
				$mongo = $mongo->where_in("code_contract", $condition['code_contract']);
			} else {
				$mongo = $mongo->like("code_contract", $condition['code_contract']);
			}

		}
		if (empty($condition['total'])) {
			return $mongo->order_by($order_by)
				->select(array(), array('image_accurecy'))
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->select(array(), array('image_accurecy'))
				->count($this->collection);
		}
	}

	public function getContractByUser($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] != "") {
				$where['status'] = (int)$condition['status'];
			}
		} else {
			$where['status'] = array(
				'$gte' => 17
			);
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->select(array(), array('image_accurecy'))
			->get($this->collection);
	}

	public function getContractByEmailUser($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->select(array(), array('image_accurecy'))
			->get($this->collection);
	}

	public function getContractByTime($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] == "17") {
				$where['status'] = array(
					'$gte' => (int)$condition['status']
				);
			} else {
				$where['status'] = (int)$condition['status'];
			}
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}

		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);

			}
		}
		return $mongo->order_by($order_by)
			->select(array(), array('image_accurecy'))
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function getContractLoan($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['status'] = array(
			'$gte' => 17
		);

		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
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
			->select(array('code_contract_disbursement', 'code_contract', 'investor_code', 'customer_infor', 'current_address', 'houseHold_address', 'store', 'loan_infor'))
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function getContractByTimeAll($searchLike, $condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] == "17") {
				$where['status'] = array(
					'$gte' => (int)$condition['status']
				);
			} else {
				$where['status'] = (int)$condition['status'];
			}
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
//		if (isset($condition['bucket'])) {
//			$where['bucket'] = $condition['bucket'];
//		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);

			}
		}
		return $mongo->order_by($order_by)->count($this->collection);
	}

	public function getRemind_debt_first($condition = array(), $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		$where['is_change_tat_toan'] = array('$ne' => 1);
		$where['status_disbursement'] = 3;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		return $mongo->where_not_in('status', array(19, 23))->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function getUpdate_status($condition = array(), $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		if (isset($condition['is_change_dang_vay'])) {
			$where['is_change_dang_vay'] = (int)$condition['is_change_dang_vay'];
		}
		if (isset($condition['is_change_tat_toan'])) {
			$where['is_change_tat_toan'] = (int)$condition['is_change_tat_toan'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (isset($condition['total'])) {
			return $mongo->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function getRemind_debt_first_count($condition = array())
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		$where['is_change_tat_toan'] = array('$ne' => 1);
		$where['status_disbursement'] = 3;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}

		return $mongo->where_not_in('status', array(19, 23))->count($this->collection);
	}

	public function countContractActivebyTime($time, $store_id)
	{
		// $mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		$where = [];
		if (isset($time['start']) && isset($time['end'])) {
			$where['created_at'] = array(
				'$gte' => intval($time['start']),
				'$lte' => intval($time['end'])
			);
		}


		$condition = array("store.id" => $store_id, '$or' => array(array('status' => 16), array('status' => 17)));

		$arr = $this->mongo_db
			->set_where($where)
			->get_where($this->collection, $condition);
		return count($arr);

		return $mongo->get_where($this->collection, $condition);
	}

	public function findPageNotIn($condition, $offset = 1, $order_by = array('time' => 'desc'), $sl, $field, $in = "")
	{
		$arr = $this->mongo_db
			->order_by($order_by)
			->select($sl)
			->offset($offset)
			->where_not_in($field, $in)
			->get_where($this->collection, $condition);
		return $arr;
	}

	public function findContractPagination($limit = 30, $offset = 0)
	{
		$condition = array(
			'type' => array('$ne' => "vaynhanh")
		);
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))->limit($limit)->offset($offset)->get_where($this->collection, $condition);
	}

	public function findOldContract($limit = 30, $offset = 0)
	{
		$condition = array(
			'type' => "old_contract"
		);
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))->limit($limit)->offset($offset)->get_where($this->collection, $condition);
	}

	public function getContractPaginationByRole($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		} else {
			$where['type'] = array('$ne' => "vaynhanh");
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		// if(isset($condition['code_contract'])){
		//  $where['code_contract'] = $condition['code_contract'];
		// }
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->where_in_like("code_contract_disbursement", $condition['code_contract_disbursement']);

		}

//		if (!empty($condition['code_contract_disbursement'])) {
//			$mongo = $mongo->where_in_like("code_contract_disbursement", $condition['code_contract_disbursement']);
//		}

//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->where_in_like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['customer_identify'])) {
			$mongo = $mongo->where_in_like("customer_infor.customer_identify", $condition['customer_identify']);
		}
		if (!empty($condition['asset_name'])) {
			$mongo = $mongo->like("loan_infor.name_property.text", $condition['asset_name']);
		}

		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function getOldContractByRole($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		$where['type'] = array('$ne' => "vaynhanh");
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		// if(isset($condition['code_contract'])){
		//  $where['code_contract'] = $condition['code_contract'];
		// }
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)->where("type", "old_contract")
				->limit($limit)
				->offset($offset)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)->where("type", "old_contract")
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function getContractByRole($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}


		$where['type'] = array('$ne' => "vaynhanh");
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function getQuickLoanByRole($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (isset($condition['reminder_now'])) {
			$where['reminder_now'] = $condition['reminder_now'];
		}
		if (isset($condition['loan'])) {
			if ($condition['loan'] == '1') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '2') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'XM';
			}
			if ($condition['loan'] == '3') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '4') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'XM';
			}

		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function get_contract_ctd($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (isset($condition['reminder_now'])) {
			$where['reminder_now'] = $condition['reminder_now'];
		}
		if (isset($condition['loan'])) {
			if ($condition['loan'] == '1') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '2') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'XM';
			}
			if ($condition['loan'] == '3') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '4') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'XM';
			}

		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_contract_ctd_total($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (isset($condition['reminder_now'])) {
			$where['reminder_now'] = $condition['reminder_now'];
		}
		if (isset($condition['loan'])) {
			if ($condition['loan'] == '1') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '2') {
				$where['loan_infor.type_loan.code'] = 'CC';
				$where['loan_infor.type_property.code'] = 'XM';
			}
			if ($condition['loan'] == '3') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'OTO';
			}
			if ($condition['loan'] == '4') {
				$where['loan_infor.type_loan.code'] = 'DKX';
				$where['loan_infor.type_property.code'] = 'XM';
			}

		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['created_by'])) {
			$where['created_by'] = $condition['created_by'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->count($this->collection);
		}
	}

	public function getContractForQuickLoan($condition = array())
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
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)->where(["type" => "vaynhanh"])
			->get($this->collection);
	}

	public function findContractRenew($condition)
	{
		$status = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['status'])) {
			$status = $condition['status'];
			unset($condition['status']);
		}
		$mongo->set_where($condition);
		return $mongo->where_not_in('status', $status)
			->count($this->collection);
	}

	public function findContractWithTemp($condition = array())
	{
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'code_auto_disbursement' => 1,
						'fee' => 1,
						'status_create_withdrawal' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'code_transaction_bank_disbursement' => 1,
						'investor_code' => 1,
						'code_contract' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_email' => 1,
						'customer_infor.customer_identify' => 1,
						'customer_infor.customer_phone_number' => 1,
						'current_address' => 1,
						'houseHold_address' => 1,
						'receiver_infor' => 1,
						'investor_infor' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_loan.code' => 1,
						'loan_infor.type_property.code' => 1,
						'loan_infor.type_interest' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'localField' => "code_contract",
						'foreignField' => "code_contract",
						'as' => "plan_contract"
					]
				]
			],
			'cursor' => new stdClass,
		];
		$match = array();
		$match['$match']['status'] = 17;
		if (!empty($condition)) {
			if (!empty($condition['start']) && !empty($condition['end'])) {
				$match['$match']['created_at'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
			} else if (!empty($condition['start']) && empty($condition['end'])) {
				$match['$match']['created_at'] = array('$gte' => $condition['start']);
			} else if (!empty($condition['end']) && empty($condition['start'])) {
				$match['$match']['created_at'] = array('$lte' => $condition['end']);
			}
		}
		array_push($conditions['pipeline'], $match);
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getReportContractVolume($condition = array(), $start = "", $end = "")
	{
		//Step 1: Tìm tất cả các HĐ từ phòng giao dịch
		$conditions = [
			'aggregate' => "store",
			'pipeline' => [
				['$project' =>
					[
						"name" => 1,
					]
				],
				['$lookup' =>
					[
						'from' => 'contract',
						'let' => array(
							"store_id" => '$_id',
							'condition_start' => $condition['start'],
							'condition_end' => $condition['end'],
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$store.object_id', '$$store_id')
											),
											array(
												'$lte' => array($condition['start'], '$created_at')
											),
											array(
												'$gte' => array($condition['end'], '$created_at')
											)
										)
									)
								),
							),
							array(
								'$project' => [
									'code_contract' => 1,
									'loan_infor.amount_money' => 1,
									'created_at' => 1
								]
							)
						),
						'as' => "contracts",
					]
				]
			],
			'cursor' => new stdClass,
		];

		$command = new MongoDB\Driver\Command($conditions);
		$contractStores = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			array_push($contractStores, $item);
		}

		//var_dump($contractStore);
		//die;

		//Step 2: Lọc HĐ của phòng giao dịch theo tháng
		$start = (new DateTime($start))->modify('first day of this month');
		$end = (new DateTime($end))->modify('first day of next month');
		$interval = DateInterval::createFromDateString('1 month');
		$period = new DatePeriod($start, $interval, $end);

		$totalDisburseAccumulated = 0; // Tổng giải ngân lũy kế
		$arrMonth = array();
		foreach ($period as $dt) {
			$format = $dt->format("Y-m"); // 2020-01
			$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
			$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

			$startTimestamp = strtotime(trim($startMonth) . ' 00:00:00'); //1577836800
			$endTimestamp = strtotime(trim($endMonth) . ' 23:59:59'); //1580515199

			$arrMonth[$format] = array();

			//Foreach stores
			foreach ($contractStores as $contractStore) {
				if ($this->checkContractInMonth($contractStore, $startTimestamp, $endTimestamp) == FALSE) continue;
				//if(empty($contractStore->contracts) || count($contractStore->contracts) == 0) continue;

				$countContractDisburse = 0; // tổng Số hợp đồng giải ngân trong tháng của phòng giao dịch
				$totalDisburse = 0; //tổng giải ngân trong tháng của phòng giao dịch

				$arrMonth[$format][$contractStore->name] = array(
					"count_contract_disburse" => 0,
					"total_disburse" => 0,
					"total_disburse_accumulated" => 0,
					"total_debt_pay" => 0
				);

				//Foreach contracts of store
				foreach ($contractStore->contracts as $contract) {
					if ($contract->created_at < $startTimestamp || $contract->created_at > $endTimestamp) continue;
					$countContractDisburse++; // tổng Số hợp đồng giải ngân trong tháng của phòng giao dịch
					$totalDisburse = $totalDisburse + $contract->loan_infor->amount_money; //tổng giải ngân trong tháng của phòng giao dịch
					$totalDisburseAccumulated = $totalDisburseAccumulated + $contract->loan_infor->amount_money; // Tổng giải ngân lũy kế
				}

				$arrMonth[$format][$contractStore->name]['count_contract_disburse'] = $countContractDisburse;
				$arrMonth[$format][$contractStore->name]['total_disburse'] = $totalDisburse;
				$arrMonth[$format][$contractStore->name]['total_disburse_accumulated'] = $totalDisburseAccumulated;

			}
		}
//        var_dump($arrMonth);
//        die;

		return $arrMonth;
	}

	private function checkContractInMonth($contractStore, $startTimestamp, $endTimestamp)
	{
		$haveContract = false;
		foreach ($contractStore->contracts as $contract) {
			if ($contract->created_at < $startTimestamp || $contract->created_at > $endTimestamp) continue;
			$haveContract = true;
			break;
		}
		return $haveContract;
	}

	public function findContractExport($condition = array())
	{
		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'code_auto_disbursement' => 1,
						'fee' => 1,
						'status_create_withdrawal' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'code_transaction_bank_disbursement' => 1,
						'investor_code' => 1,
						'code_contract' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_email' => 1,
						'customer_infor.customer_identify' => 1,
						'customer_infor.customer_phone_number' => 1,
						'current_address' => 1,
						'houseHold_address' => 1,
						'receiver_infor' => 1,
						'investor_infor' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_loan.code' => 1,
						'loan_infor.type_property.code' => 1,
						'loan_infor.type_interest' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1
					]
				],
				['$match' =>
					[
						'status' => 17
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											$compareLte,
											$compareGte
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//                                    'created_at' => 1
//                                ]
//                            )
						),
						'as' => "plan_contract",
					]
				]
			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			if (empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getDisburse($condition)
	{
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => intval($condition['start']),
				'$lte' => intval($condition['end'])
			);
		}

		$mongo = $this->mongo_db;
		if (!empty($where)) {
			//$where['status'] = 17; //Giải ngân thành công - tiền đã về tay khách hàng
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			//->select(array("code_contract", "disbursement_date"))
			->get($this->collection);
	}

	public function getInterestReal($condition)
	{
		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}

		$compareLteTran = array();
		if (!empty($condition['start'])) {
			$compareLteTran = array(
				'$lte' => array($condition['start'], '$created_at')
			);
		}
		$compareGteTran = array();
		if (!empty($condition['end'])) {
			$compareGteTran = array(
				'$gte' => array($condition['end'], '$created_at')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array('$created_at', $condition['start'])
											)
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'fee_extend' => 1,
									'fee_finish_contract' => 1,
									'fee_delay' => 1,
									'created_at' => 1
								]
							)
						),
						'as' => "transaction_cac_thang_truoc",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											$compareLteTran,
											$compareGteTran
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'fee_extend' => 1,
									'fee_finish_contract' => 1,
									'fee_delay' => 1,
									'created_at' => 1
								]
							)
						),
						'as' => "transaction",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1thang_da_tra' => 1,
									'lai_da_dong_thang_hien_tai' => 1,
									'phi_da_dong_thang_hien_tai' => 1,
								]
							)
						),
						'as' => "plan_contract_all",
					]
				],
				['$project' =>
					[
						'code_auto_disbursement' => 1,
						'fee' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'investor_code' => 1,
						'status_create_withdrawal' => 1,
						'code_contract' => 1,
						'code_transaction_bank_disbursement' => 1,
						'content_transfer_disbursement' => 1,
						'code_contract_disbursement' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_email' => 1,
						'customer_infor.customer_identify' => 1,
						'customer_infor.customer_phone_number' => 1,
						'current_address' => 1,
						'houseHold_address' => 1,
						'receiver_infor' => 1,
						'response_create_withdrawal' => 1,
						'investor_infor' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_loan.code' => 1,
						'loan_infor.type_property.code' => 1,
						'loan_infor.type_interest' => 1,
						'loan_infor.amount_money' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,
//                        "so_tien_goc_da_thu_hoi" => array(
//                            '$sum' => '$plan_contract_all.tien_goc_1thang_da_tra'
//                        ),
						"so_tien_lai_NDT_da_thu_hoi" => array(
							'$sum' => '$plan_contract_all.lai_da_dong_thang_hien_tai'
						),
						"so_tien_phi_da_thu_hoi" => array(
							'$sum' => '$plan_contract_all.phi_da_dong_thang_hien_tai'
						),
						"plan_contract.goc_con_lai_chua_thu_cuoi_thang_hien_tai" => 1,
						"so_tien_goc_da_thu_hoi_AB" => array(
							'$sum' => '$transaction.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_AC" => array(
							'$sum' => '$transaction.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_AD" => array(
							'$sum' => '$transaction.so_tien_phi_da_tra'
						),
						//Phí phát sinh
						"so_tien_phi_tra_cham_da_thu_hoi" => array(
							'$sum' => '$transaction.fee_delay'
						),
						"so_tien_phi_tat_toan_da_thu_hoi" => array(
							'$sum' => '$transaction.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi" => array(
							'$sum' => '$transaction.fee_extend'
						),
						"so_tien_goc_da_thu_hoi_cac_thang_truoc" => array(
							'$sum' => '$transaction_cac_thang_truoc.so_tien_goc_da_tra'
						)
					]
				],
				['$match' =>
					[
						'status' => array('$gte' => 17)
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											$compareLte,
											$compareGte
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//                                    'created_at' => 1
//                                ]
//                            )
						),
						'as' => "plan_contract",
					]
				],

			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			if (empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getFollowCurrentMonth($condition)
	{
		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$gte' => array($condition['start'], '$created_at')
											)
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//
//                                ]
//                            )
						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array($condition['start'], '$created_at')
											),
											array(
												'$gte' => array($condition['end'], '$created_at')
											)
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//
//                                ]
//                            )
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//
//                                ]
//                            )
						),
						'as' => "transaction_luy_ke",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array($condition['start'], '$ngay_ky_tra')
											),
											array(
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky_den_thang_Tn",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											//$compareLte,
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky_den_thoi_diem_dao_han",
					]
				],
				['$project' =>
					[
						'code_contract_parent' => 1,
						'code_auto_disbursement' => 1,
						'fee' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'investor_code' => 1,
						'status_create_withdrawal' => 1,
						'code_contract' => 1,
						'code_contract_child' => 1,
						'code_transaction_bank_disbursement' => 1,
						'content_transfer_disbursement' => 1,
						'code_contract_disbursement' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_email' => 1,
						'customer_infor.customer_identify' => 1,
						'customer_infor.customer_phone_number' => 1,
						'current_address' => 1,
						'houseHold_address' => 1,
						'receiver_infor' => 1,
						'investor_infor' => 1,
						'response_create_withdrawal' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_loan.code' => 1,
						'loan_infor.type_property.code' => 1,
						'loan_infor.type_interest' => 1,
						'loan_infor.amount_money' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,
//                        "lai_luy_ke_den_thang_Tn" => array(
//                            '$sum' => '$plan_contract_until_Tn.tien_lai_1thang'
//                        ),
						"lai_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.lai_ky'
						),
//                        "phi_luy_ke_den_thang_Tn" => array(
//                            '$sum' => '$plan_contract_until_Tn.tien_phi_1thang',
//                        ),
						"phi_tu_van_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tu_van',
						),
						"phi_tham_dinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tham_dinh',
						),
						"phi_tra_cham_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_delay',
						),
						"phi_tra_truoc_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_finish_contract',
						),
						"phi_gia_han_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_extend',
						),


						//Đến thời điểm đáo hạn
						"goc_vay_phai_thu_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.tien_goc_1ky'
						),
						"lai_vay_phai_tra_NDT_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.lai_ky'
						),
						"phi_tu_van_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.phi_tu_van'
						),
						"phi_tham_dinh_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.phi_tham_dinh'
						),
						"phi_tra_cham_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_delay'
						),
						"phi_tra_truoc_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_finish_contract'
						),
						"phi_gia_han_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_extend'
						),
						"du_no_goc_thang_truoc" => 1,
						"du_no_lai_thang_truoc" => 1,
						"du_no_phi_thang_truoc" => 1,
						//Start
						"so_tien_goc_da_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_phi_da_tra'
						),
						"so_tien_goc_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_da_tra'
						),
						"so_tien_goc_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_da_tra'
						),
						//Phí phát sinh
						"so_tien_phi_tra_cham_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_delay'
						),
						"so_tien_phi_tat_toan_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_extend'
						),
					]
				],
				['$match' =>
					[
						'status' => array('$gte' => 17)
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array($condition['start'], '$ngay_ky_tra')
											),
											array(
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky_den_thang_Tn",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											//$compareLte,
											//$compareGte
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky_den_thoi_diem_dao_han",
					]
				],
			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getInterestRealInvestor($condition)
	{
		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}

		$compareLteTran = array();
		if (!empty($condition['start'])) {
			$compareLteTran = array(
				'$lte' => array($condition['start'], '$created_at')
			);
		}
		$compareGteTran = array();
		if (!empty($condition['end'])) {
			$compareGteTran = array(
				'$gte' => array($condition['end'], '$created_at')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array($condition['start'], '$created_at')
											),
											array(
												'$gte' => array($condition['end'], '$created_at')
											)
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//
//                                ]
//                            )
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											$compareLteTran,
											$compareGteTran
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'created_at' => 1
								]
							)
						),
						'as' => "transaction",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1thang_da_tra' => 1,
									'lai_da_dong_thang_hien_tai' => 1,
									'phi_da_dong_thang_hien_tai' => 1,
								]
							)
						),
						'as' => "plan_contract_all",
					]
				],
				['$project' =>
					[
						'code_auto_disbursement' => 1,
						'fee' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'investor_code' => 1,
						'status_create_withdrawal' => 1,
						'code_contract' => 1,
						'code_transaction_bank_disbursement' => 1,
						'content_transfer_disbursement' => 1,
						'code_contract_disbursement' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_email' => 1,
						'customer_infor.customer_identify' => 1,
						'customer_infor.customer_phone_number' => 1,
						'current_address' => 1,
						'houseHold_address' => 1,
						'receiver_infor' => 1,
						'response_create_withdrawal' => 1,
						'investor_infor' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_loan.code' => 1,
						'loan_infor.type_property.code' => 1,
						'loan_infor.type_interest' => 1,
						'loan_infor.amount_money' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,
//                        "so_tien_goc_da_thu_hoi" => array(
//                            '$sum' => '$plan_contract_all.tien_goc_1thang_da_tra'
//                        ),
						"so_tien_lai_NDT_da_thu_hoi" => array(
							'$sum' => '$plan_contract_all.lai_da_dong_thang_hien_tai'
						),
						"so_tien_phi_da_thu_hoi" => array(
							'$sum' => '$plan_contract_all.phi_da_dong_thang_hien_tai'
						),
						"plan_contract.goc_con_lai_chua_thu_cuoi_thang_hien_tai" => 1,
						"so_tien_goc_da_thu_hoi_AB" => array(
							'$sum' => '$transaction.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_AC" => array(
							'$sum' => '$transaction.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_AD" => array(
							'$sum' => '$transaction.so_tien_phi_da_tra'
						),
						"so_tien_goc_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_lai_da_tra'
						),
					]
				],
				['$match' =>
					[
						'status' => 17
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											$compareLte,
											$compareGte
										)
									)
								),
							),
//                            array(
//                                '$project' => [
//                                    'created_at' => 1
//                                ]
//                            )
						),
						'as' => "plan_contract",
					]
				],

			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			if (empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getCodeAutoDisburseMent()
	{
		$date = date("dmY", time());
		//Get max
		$condition = array(
			'max_code_auto_disbursement' => array('$ne' => null)
		);
		$numberMax = 1;
		$max = $this->mongo_db
			->select(array("max_code_auto_disbursement"))
			->where($condition)
			->order_by(array("max_code_auto_disbursement" => "DESC"))
			->find_one($this->collection);
		$numberMax = empty($max['max_code_auto_disbursement']) ? $numberMax++ : $max['max_code_auto_disbursement'] + 1;
		$return = array(
			'max_code_auto_disbursement' => $numberMax,
			'code_auto_disbursement' => $date . '-' . str_pad($numberMax, 6, '0', STR_PAD_LEFT)
		);
		return $return;
	}

	public function getFollowInvestor($condition)
	{
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$gte' => array($condition['start'], '$created_at')
											)
										)
									)
								),
							),
						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$lte' => array($condition['start'], '$created_at')
											),
											array(
												'$gte' => array($condition['end'], '$created_at')
											)
										)
									)
								),
							),
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
										)
									)
								),
							),
						),
						'as' => "transaction_luy_ke",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
								]
							)
						),
						'as' => "bang_lai_ky_den_thoi_diem_dao_han",
					]
				],
				['$project' =>
					[
						'code_auto_disbursement' => 1,
						'fee.percent_interest_customer' => 1,
						'response_get_transaction_withdrawal_status' => 1,
						'investor_code' => 1,
						'status_create_withdrawal' => 1,
						'code_contract' => 1,
						'code_transaction_bank_disbursement' => 1,
						'code_contract_disbursement' => 1,
						'content_transfer_disbursement' => 1,
						'loan_infor.number_day_loan' => 1,
						'disbursement_date' => 1,
						'expire_date' => 1,
						'customer_infor.customer_name' => 1,
						'receiver_infor.amount' => 1,
						'investor_infor' => 1,
						'response_create_withdrawal' => 1,
						'amount_extend' => 1,
						'code_contract_extend' => 1,
						'loan_infor.type_interest' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,

						//Đến thời điểm đáo hạn
						"goc_vay_phai_thu_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.tien_goc_1ky'
						),
						"lai_vay_phai_tra_NDT_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.lai_ky'
						),

						"so_tien_goc_da_tra_NDT_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_tra_NDT_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_lai_da_tra'
						),
						"so_tien_goc_da_tra_NDT_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_tra_NDT_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_lai_da_tra'
						),
						"so_tien_goc_da_tra_NDT_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_tra_NDT_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_lai_da_tra'
						),

					]
				],
				['$match' =>
					[
						'status' => 17
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)
										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky' => 1,
									'tien_goc_1ky_con_lai' => 1,
									'tien_lai_1ky_con_lai' => 1,
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,
									'fee_delay' => 1,
								]
							)
						),
						'as' => "bang_lai_ky_den_thang_Tn",
					]
				],
			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		return $cursor->toArray();
	}

	public function contract_chuyentd()
	{

		//Step 1: Tìm tất cả các HĐ từ phòng giao dịch
		$conditions = [
			'aggregate' => "contract",
			// '$match' =>
			// [
			//     'reminder_now' => 1,
			//     // 'status' => 17
			// ],

			'pipeline' => [
				['$project' =>
					[
						'code_contract' => 1,
						'code_contract_disbursement' => 1,
						'customer_infor' => 1,
						'loan_infor' => 1,
						'store' => 1,
						'disbursement_date' => 1,
						'current_address' => 1,
						'status' => 1,
						'reminder_now' => 1,
						"tong_goc_con_phai_dong" => array(
							'$sum' => '$bang_lai_ky.tien_goc_1ky_con_lai',
						),
						"tong_lai_con_phai_dong" => array(
							'$sum' => '$bang_lai_ky.tien_lai_1ky_con_lai',
						),
						"tong_phi_con_phai_dong" => array(
							'$sum' => '$bang_lai_ky.tien_phi_1ky_con_lai',
						),
						"tong_phi_tra_cham" => array(
							'$sum' => '$bang_lai_ky.fee_delay',
						),
						"tong_goc_da_dong" => array(
							'$sum' => '$bang_lai_ky.tien_goc_1ky_da_tra',
						),
						"tong_lai_da_dong" => array(
							'$sum' => '$bang_lai_ky.tien_lai_1ky_da_tra',
						),
						"tong_phi_da_dong" => array(
							'$sum' => '$bang_lai_ky.tien_phi_1ky_da_tra',
						),
					]
				],
				['$match' =>
					[
						'reminder_now' => "Call không còn khả năng tác động và đủ điều kiện chuyển qua thực địa",
						// 'status' => 1
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract',
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											)
										)
									)
								),
							),
							array(
								'$project' => [
									'code_contract' => 1,
									'loan_infor.amount_money' => 1,
									'created_at' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract',
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract', '$$code_contract')
											),
											array(
												'status' => 1
											)

										)
									)
								),
							),
							array(
								'$project' => [
									'code_contract' => 1,
									// 'loan_infor.amount_money' => 1,
									'ngay_ky_tra' => 1,
									'created_at' => 1
								]
							),
							array(
								'$limit' => 1
							),
							array(
								'$sort' => array(
									"ngay_kY_tra" => 1
								)
							)
						),
						'as' => "ban_ghi_lai_ky_qua_han",
					]
				],

			],

			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			// if(empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getTotalContractByTime($searchLike, $condition)
	{
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] == "17") {
				$where['status'] = array(
					'$gte' => (int)$condition['status']
				);
			} else {
				$where['status'] = (int)$condition['status'];
			}
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}

		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($searchLike)) {
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);
			}
		}
		return $mongo->order_by($order_by)->count($this->collection);
	}

	public function getContractByTime_($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$match = array();
		$match['status_disbursement'] = 3;
		// From date, End date
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$match['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		} else if (!empty($condition['start']) && empty($condition['end'])) {
			$match['created_at'] = array('$gte' => $condition['start']);
		} else if (!empty($condition['end']) && empty($condition['start'])) {
			$match['created_at'] = array('$lte' => $condition['end']);
		}

		$ops = array(
			['$lookup' =>
				[
					'from' => 'investor',
					'let' => array(
						"investor_code" => '$investor_code', // $symbol = field 'symbol' của 'aggregate' => $this->collection
					),
					'pipeline' => array(
						array(
							'$match' => array(
								'$expr' => array(
									'$and' => array(
										array(
											'$eq' => array('$code', '$$investor_code')
										)
									)
								)
							),
						),
						array(
							'$project' => [
								'name' => 1
							]
						),
					),
					'as' => "investor_infor"
				]
			],
			['$lookup' =>
				[
					'from' => 'temporary_plan_contract',
					'let' => array(
						"code_contract" => '$code_contract', // $symbol = field 'symbol' của 'aggregate' => $this->collection
					),
					'pipeline' => array(
						array(
							'$match' => array(
								'$expr' => array(
									'$and' => array(
										array(
											'$eq' => array('$code_contract', '$$code_contract')
										),
										array(
											'$lt' => array('ngay_ky_tra', $this->createdAt)
										),
									)
								)
							),
						),
					),
					'as' => "lai_ky_truoc_do"
				]
			],
			['$lookup' =>
				[
					'from' => 'temporary_plan_contract',
					'let' => array(
						"code_contract" => '$code_contract', // $symbol = field 'symbol' của 'aggregate' => $this->collection
					),
					'pipeline' => array(
						array(
							'$match' => array(
								'$expr' => array(
									'$and' => array(
										array(
											'$eq' => array('$code_contract', '$$code_contract')
										),
										array(
											'$lt' => array('ngay_ky_tra', $this->createdAt)
										),
										array(
											"status" => 1 // 1 = chưa trả, 2 =đã trả
										),
										array(
											'$limit' => 1,
										),
										array(
											'$sort' => array(
												"ngay_ky_tra" => 1
											)
										)
									)
								)
							),
						),
					),
					'as' => "lai_ky_chua_tra_truoc_do_xa_nhat"
				]
			],
			['$lookup' =>
				[
					'from' => 'temporary_plan_contract',
					'let' => array(
						"code_contract" => '$code_contract', // $symbol = field 'symbol' của 'aggregate' => $this->collection
					),
					'pipeline' => array(
						array(
							'$match' => array(
								'$expr' => array(
									'$and' => array(
										array(
											'$eq' => array('$code_contract', '$$code_contract')
										),
										array(
											'$gt' => array('ngay_ky_tra', $this->createdAt)
										)
									)
								)
							),
						),
						array(
							'$limit' => 1
						),
						array(
							'$sort' => array('ngay_ky_tra' => 1)
						),
					),
					'as' => "lai_ky_sau_do_gan_nhat"
				]
			],
			array(
				'$match' => $match
			),
			array(
				'$project' => array(
					"so_ngay_tre" => array(
						'lai_ky_chua_tra_truoc_do_xa_nhat.ngay_ky_tra'
					)
				)
			),
			array(
				'$limit' => $limit + $offset
			),
			array(
				'$skip' => $offset
			),
			array(
				'$sort' => array(
					'created_at' => -1
				)
			)
		);

		// Status
		if (!empty($condition['status'])) {
			$match['$match']['status'] = (int)$condition['status'];
		} else {
			$match['$match']['status'] = array('$gte' => 17);
		}
		if (isset($condition['status_disbursement'])) {
			$match['$match']['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$match['$match']['store.id'] = $condition['store'];
		}
		if (isset($condition['investor_code'])) {
			$match['$match']['investor_code'] = $condition['investor_code'];
		}

		array_push($conditions['pipeline'], $match);

		$data = $this->findAggregate($ops);

		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] != "") {

				$where['status'] = (int)$condition['status'];
			}
		} else {
			$where['status'] = array(
				'$gte' => 17
			);
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}

		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
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
			->select(array(), array('image_accurecy'))
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}


	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
	}

	public function get_infor_tat_toan_part_1($code_contract, $date_pay)
	{

		//Dư nợ còn lại =
		//gốc: chưa trả đến thời điểm đáo hạn (bảng kỳ)
		//lãi: chưa trả đến thời điểm hiện tại (bảng kỳ)
		//phí: chưa trả đến thời điểm hiện tại (bảng kỳ)
		$so_ngay_tat_toan = 0;
		$ky_chua_thanh_toan_gan_nhat = $this->temporary_plan_contract_model->getKiChuaThanhToanGanNhat($code_contract);
		$lai_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['tien_lai_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat['tien_lai_1ky_phai_tra'] : 0;
		$phi_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['tien_phi_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat['tien_phi_1ky_phai_tra'] : 0;
		$ngay_ky_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['ngay_ky_tra']) ? $ky_chua_thanh_toan_gan_nhat['ngay_ky_tra'] : 0;
		$so_ngay_trong_ky = !empty($ky_chua_thanh_toan_gan_nhat['so_ngay']) ? $ky_chua_thanh_toan_gan_nhat['so_ngay'] : 0;
		if ($ngay_ky_tra_ky_chua_thanh_toan_gan_nhat > 0) {
			$so_ngay_tat_toan = round(($date_pay - $ngay_ky_tra_ky_chua_thanh_toan_gan_nhat) / 86400);
			$lai_chua_tra_den_thoi_diem_hien_tai = $so_ngay_tat_toan * ($lai_chua_tra_ky_chua_thanh_toan_gan_nhat / $so_ngay_trong_ky);
			$phi_chua_tra_den_thoi_diem_hien_tai = $so_ngay_tat_toan * ($phi_chua_tra_ky_chua_thanh_toan_gan_nhat / $so_ngay_trong_ky);
		}
		$goc_da_tra_dao_han = $this->temporary_plan_contract_model->goc_da_tra_den_thoi_diem_dao_han($code_contract, $date_pay);
		$goc_da_tra = $this->temporary_plan_contract_model->tong_tien_goc_da_tra($code_contract);

		$tong_tien_goc = $this->temporary_plan_contract_model->tong_tien_goc($code_contract, $date_pay);
		$goc_chua_tra_den_thoi_diem_dao_han = $tong_tien_goc - $goc_da_tra_dao_han;
		$goc_chua_tra = $tong_tien_goc - $goc_da_tra;


		$du_no_con_lai = 0;
		$du_no_con_lai = $goc_chua_tra_den_thoi_diem_dao_han + $lai_chua_tra_den_thoi_diem_hien_tai + $phi_chua_tra_den_thoi_diem_hien_tai;


		$res = array();
		$res['goc_chua_tra'] = $goc_chua_tra;
		$res['lai_chua_tra_den_thoi_diem_hien_tai'] = $lai_chua_tra_den_thoi_diem_hien_tai;
		$res['phi_chua_tra_den_thoi_diem_hien_tai'] = $phi_chua_tra_den_thoi_diem_hien_tai;
		$res['du_no_con_lai'] = $du_no_con_lai;
		$res['goc_chua_tra_den_thoi_diem_dao_han'] = $goc_chua_tra_den_thoi_diem_dao_han;
		$res['fee'] = $percent_interest_customer + $percent_advisory + $percent_expertise;
		$res['so_ngay_lai_phi'] = $so_ngay_lai_phi;
		$res['ngay_trong_ky'] = $ngay_trong_ky;
		$res['lai_phi_chua_tra_den_thoi_diem_hien_tai'] = $lai_phi_chua_tra_den_thoi_diem_hien_tai;
		$res['ki_hien_tai'] = $ky_tra1;
		$res['date_pay'] = $date_pay;


		return $res;
	}

	public function get_infor_tat_toan_part_2($code_contract = "", $date_pay = "")
	{
		$ngay_trong_ky = 1;
		$lai_con_no_thuc_te = 0;
		$phi_con_no_thuc_te = 0;
		$so_ngay_no_thuc_te = 0;
		$ngay_trong_ky = 0;
		$contractDB = $this->findOne(array('code_contract' => $code_contract));
		$ngay_giai_ngan = $contractDB['disbursement_date'];
		$date_ngay_t = strtotime($this->config->item("date_t_apply"));
		$ky_tiep_theo = $this->temporary_plan_contract_model->lai_phi_con_no_cua_ki_tiep_theo_2($code_contract, $date_pay);
		if ($ngay_giai_ngan < $date_ngay_t) {
			$lai_phi_con_lai_phai_tra_cua_ki_tiep_theo = $this->temporary_plan_contract_model->lai_phi_con_no_cua_ki_tiep_theo_1($code_contract, $date_pay);
		} else {
			$lai_phi_con_lai_phai_tra_cua_ki_tiep_theo = $this->temporary_plan_contract_model->lai_phi_con_no_cua_ki_tiep_theo_2($code_contract, $date_pay);
		}

		$lai_con_lai_phai_tra_cua_ki_tiep_theo = !empty($lai_phi_con_lai_phai_tra_cua_ki_tiep_theo[0]->tien_lai_1ky_phai_tra) ? $lai_phi_con_lai_phai_tra_cua_ki_tiep_theo[0]->tien_lai_1ky_phai_tra : 0;
		$phi_con_lai_phai_tra_cua_ki_tiep_theo = !empty($lai_phi_con_lai_phai_tra_cua_ki_tiep_theo[0]->tien_phi_1ky_phai_tra) ? $lai_phi_con_lai_phai_tra_cua_ki_tiep_theo[0]->tien_phi_1ky_phai_tra : 0;
		$ngay_ky_tra_ki_tiep_theo = !empty($ky_tiep_theo[0]->ngay_ky_tra) ? $ky_tiep_theo[0]->ngay_ky_tra : 0;
		$ky_tra = !empty($ky_tiep_theo[0]->ky_tra) ? $ky_tiep_theo[0]->ky_tra : 0;

		//Số ngày nợ thực tế = ngày hiện tại - ngay_ky_tra của kì trước đó

		if ($ky_tra >= 1) {

			if ($ky_tra > 1) {
				$last_contract_tempo = $this->temporary_plan_contract_model->findOne(array('code_contract' => $code_contract, 'ky_tra' => $ky_tra - 1));
			}
			$ky_han_truoc = (isset($last_contract_tempo['ngay_ky_tra'])) ? $last_contract_tempo['ngay_ky_tra'] : '';

			$time_disbursement_date = $contractDB['disbursement_date'];
			$ngay_trong_ky = $this->tinh_so_ngay_trong_ky($ky_tra, $time_disbursement_date, $ngay_ky_tra_ki_tiep_theo, $ky_han_truoc);
			$timestamp30days = $ngay_trong_ky * 86400; // 1/5
			$rangeDate = strtotime(date('Y-m-d', $ngay_ky_tra_ki_tiep_theo) . ' 23:59:59') - $date_pay;  // = 1/6 - 15/5 = 23

			if ($timestamp30days - $rangeDate > 0) {
				$so_ngay_no_thuc_te = round(($timestamp30days - $rangeDate) / 86400);
			}
			if ($ngay_trong_ky == 0) $ngay_trong_ky = 30;
			if ($so_ngay_no_thuc_te < 0) $so_ngay_no_thuc_te = 0;


			//Tiền phí còn nợ thực tế = $phi_con_no_cua_ki_tiep_theo * ngay_no_thuc_te / 30
			//Tiền lãi còn nợ thực tế = $lai_con_no_cua_ki_tiep_theo * ngay_no_thuc_te / 30
			$lai_con_no_thuc_te = $lai_con_lai_phai_tra_cua_ki_tiep_theo * $so_ngay_no_thuc_te / $ngay_trong_ky;
			$phi_con_no_thuc_te = $phi_con_lai_phai_tra_cua_ki_tiep_theo * $so_ngay_no_thuc_te / $ngay_trong_ky;

		}
		$lai_con_no_thuc_te = 0;
		$phi_con_no_thuc_te = 0;
		$response = array(
			'lai_con_no_thuc_te' => $lai_con_no_thuc_te,
			'phi_con_no_thuc_te' => $phi_con_no_thuc_te,

			'lai_con_lai_phai_tra_cua_ki_tiep_theo' => $lai_con_lai_phai_tra_cua_ki_tiep_theo,
			'phi_con_lai_phai_tra_cua_ki_tiep_theo' => $phi_con_lai_phai_tra_cua_ki_tiep_theo,
			'so_ngay_no_thuc_te' => $so_ngay_no_thuc_te,
			'ngay_trong_ky' => $ngay_trong_ky,
		);
		return $response;
	}

	public function get_goc_chua_tra_den_thoi_diem_dao_han_1($code_contract, $date_pay)
	{
		$goc_chua_tra_den_thoi_diem_dao_han = $this->temporary_plan_contract_model->goc_chua_tra_den_thoi_diem_dao_han_1($code_contract, $date_pay);
		return $goc_chua_tra_den_thoi_diem_dao_han;
	}

	public function get_goc_chua_tra_den_thoi_diem_dao_han_2($code_contract, $date_pay)
	{
		$goc_chua_tra_den_thoi_diem_dao_han = $this->temporary_plan_contract_model->goc_chua_tra_den_thoi_diem_dao_han_2($code_contract, $date_pay);
		return $goc_chua_tra_den_thoi_diem_dao_han;
	}

	public function getKiPhaiThanhToanXaNhat($code_contract)
	{
		$ki_phai_thanh_toan_xa_nhat = $this->temporary_plan_contract_model->getKiPhaiThanhToanXaNhat($code_contract);
		return $ki_phai_thanh_toan_xa_nhat[0]['ngay_ky_tra'];
	}

	public function get_all_KiPhaiThanhToanXaNhat($code_contract)
	{
		$ki_phai_thanh_toan_xa_nhat = $this->temporary_plan_contract_model->getKiPhaiThanhToanXaNhat($code_contract);
		return $ki_phai_thanh_toan_xa_nhat;
	}

	public function tong_tien_phai_tra_den_thoi_diem_dao_han($code_contract, $date_pay)
	{
		$tong_tien_phai_tra_den_thoi_diem_dao_han = $this->temporary_plan_contract_model->tong_tien_phai_tra_den_thoi_diem_dao_han($code_contract, $date_pay);
		return $tong_tien_phai_tra_den_thoi_diem_dao_han;
	}

	public function get_phi_phat_sinh($contractDB, $date_pay = "")
	{
		$phi_phat_sinh = 0;
		$tong_tien_phai_tra_den_thoi_diem_dao_han = 0;
		$KiPhaiThanhToanXaNhat = $this->getKiPhaiThanhToanXaNhat($contractDB['code_contract']);
		$so_ngay_lech = intval(($date_pay - $KiPhaiThanhToanXaNhat) / (24 * 60 * 60));
		if ($so_ngay_lech > 3) {
			$tong_tien_phai_tra_den_thoi_diem_dao_han = $this->tong_tien_phai_tra_den_thoi_diem_dao_han($contractDB['code_contract'], $date_pay);
			$percent_interest_customer = 0;
			$percent_advisory = 0;
			$percent_expertise = 0;
			if (!empty($contractDB['fee'])) {
				$fee = $contractDB['fee'];
				$percent_interest_customer = floatval($fee['percent_interest_customer']) / 100;
				$percent_advisory = floatval($fee['percent_advisory']) / 100;
				$percent_expertise = floatval($fee['percent_expertise']) / 100;

			}
			$so_ngay_phat_sinh = intval(($date_pay - $KiPhaiThanhToanXaNhat) / 86400);
			$phi_phat_sinh = ($tong_tien_phai_tra_den_thoi_diem_dao_han * ($percent_interest_customer + $percent_advisory + $percent_expertise) * $so_ngay_phat_sinh) / 30;
		}
		return $phi_phat_sinh;

	}

	public function get_phi_tat_toan_truoc_han($contractDB, $date_pay = "", $so_ngay_vay = 0, $so_ngay_vay_thuc_te = 0)
	{
		$ngay_giai_ngan = $contractDB['disbursement_date'];

		$timestamp_ngay_giai_ngan = $ngay_giai_ngan;
		$hinh_thuc_vay = $contractDB['loan_infor']['type_loan']['code'];
		$date_ngay_t = strtotime($this->config->item("date_t_apply"));
		//if ($ngay_giai_ngan < $date_ngay_t) {
		$get_goc_chua_tra_den_thoi_diem_dao_han = $this->get_goc_chua_tra_den_thoi_diem_dao_han_1($contractDB['code_contract'], $date_pay);
		// } else {
		// 	$get_goc_chua_tra_den_thoi_diem_dao_han = $this->get_goc_chua_tra_den_thoi_diem_dao_han_2($contractDB['code_contract'], $date_pay);
		// }
		if ($so_ngay_vay_thuc_te == 0) {
			$so_ngay_vay = (isset($this->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['so_ngay_vay'])) ? (int)$this->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['so_ngay_vay'] : (int)$contractDB['loan_infor']['number_day_loan'];
		}
		$percent_prepay_phase_1 = 0;
		$percent_prepay_phase_2 = 0;
		$percent_prepay_phase_3 = 0;
		if (!empty($contractDB['fee'])) {
			$fee = $contractDB['fee'];
			$percent_prepay_phase_1 = floatval($fee['percent_prepay_phase_1']) / 100;
			$percent_prepay_phase_2 = floatval($fee['percent_prepay_phase_2']) / 100;
			$percent_prepay_phase_3 = floatval($fee['percent_prepay_phase_3']) / 100;
		}
		$phi_tat_toan_truoc_han = 0;
		if ($so_ngay_vay_thuc_te == 0) {
			$ngay_giai_ngan = date('d-m-Y', $ngay_giai_ngan);
			$timestamp_ngay_giai_ngan = strtotime($ngay_giai_ngan);

			$datediff = $date_pay - $timestamp_ngay_giai_ngan;
			$so_ngay_vay_thuc_te = round($datediff / (60 * 60 * 24));
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te + 1;
			$so_ngay_vay_thuc_te = ($so_ngay_vay_thuc_te < 0) ? 1 : $so_ngay_vay_thuc_te;
		}
		$phase = $so_ngay_vay_thuc_te / $so_ngay_vay;
		//Phase 1
		if ($phase > 0 && $phase < 1 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_1 * $get_goc_chua_tra_den_thoi_diem_dao_han;
		//Phase 2
		if ($phase > 1 / 3 && $phase < 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_2 * $get_goc_chua_tra_den_thoi_diem_dao_han;
		//Phase 3
		if ($phase > 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_3 * $get_goc_chua_tra_den_thoi_diem_dao_han;

		//hình thức vay = CC -> Free = $so_ngay_vay_thuc_te > $so_ngay_vay - 3
		if ($hinh_thuc_vay == 'CC') {
			if ($so_ngay_vay_thuc_te > $so_ngay_vay - 2) {
				$phi_tat_toan_truoc_han = 0;
			}
		}
		//hình thức vay = DKX -> Free = $so_ngay_vay_thuc_te > $so_ngay_vay - 7
		if ($hinh_thuc_vay == 'DKX') {
			if ($so_ngay_vay_thuc_te > $so_ngay_vay - 6) {
				$phi_tat_toan_truoc_han = 0;
			}
		}
		return $phi_tat_toan_truoc_han;
	}

	public function get_phi_phat_cham_tra($id_contract, $date_pay)
	{

		$dataDB = $this->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id_contract)));
		if (empty($dataDB)) {
			$response = array(
				'status' => REST_Controller::HTTP_UNAUTHORIZED,
				'message' => "Không tồn tại hợp đồng"
			);
			$this->set_response($response, REST_Controller::HTTP_OK);
			return;
		}

		$condition = array(
			'code_contract' => $dataDB['code_contract']
		);
		$tempo = new Contract_tempo_model();
		$contract = $tempo->getAll($condition);

		$total_money_paid_now = 0;
		$total_money_paid = 0;
		$total_money_remaining = 0;
		$total_paid = 0;
		$ky_han_truoc = 0;
		$penalty = 0;
		$penalty_tt = 0;
		$so_ngay_chenh_lech_tt_tt = 0;
		$ngay_ky_tra = 0;
		$penalty_now_tt = 0;
		$penalty_pay_tt = 0;
		$penalty_now = 0;
		$penalty_pay = 0;
		$so_ngay_vay = 0;
		$ngay_trong_ky = 0;
		$arr_penaty = array();
		if (!empty($contract)) {
			foreach ($contract as $c) {
				$current_day = strtotime(date('Y-m-d') . ' 23:59:59');
				$date_pay = ($date_pay == 0) ? $current_day : intval($date_pay);
				$penalty = 0;
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - 5 * 24 * 3600))) { // 5 ngay tieu chuan

					$ngay_ky_tra = $c['ngay_ky_tra'];

				}
				//$c['disbursement_date'] = $dataDB['disbursement_date'];
				$c['amount_money'] = $dataDB['loan_infor']['amount_money'];
				$time_disbursement_date = $dataDB['disbursement_date'];

				$total_paid += $c['da_thanh_toan'];
				$ngay_ky_tra_ky_ht = strtotime(date('Y-m-d', $c['ngay_ky_tra'] . ' 23:59:59'));
				if ($c['ky_tra'] > 1) {
					$last_contract_tempo = $tempo->findOne(array('code_contract' => $dataDB['code_contract'], 'ky_tra' => $c['ky_tra'] - 1));
				}
				$ky_han_truoc = (isset($last_contract_tempo['ngay_ky_tra'])) ? $last_contract_tempo['ngay_ky_tra'] : '';
				$time_period = $this->tinh_so_ngay_trong_ky_cham_tra($c['ky_tra'], $time_disbursement_date, strtotime(date('Y-m-d', $c['ngay_ky_tra']) . ' 23:59:59'), $ky_han_truoc);
				$ngay_trong_ky = $this->tinh_so_ngay_trong_ky($c['ky_tra'], $time_disbursement_date, $c['ngay_ky_tra'], $ky_han_truoc);
				$so_ngay_vay += $ngay_trong_ky;
				if ($ngay_ky_tra_ky_ht > 0) {

					$so_ngay_cham_tra_now = intval(($current_day - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

					$so_ngay_cham_tra_pay = intval(($date_pay - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

					$c['so_ngay_trong_ky'] = $ngay_trong_ky;
				}
				if ($ngay_ky_tra > 0) {

					$penalty_now = $this->tinh_phi_phat($c['tien_tra_1_ky'], $dataDB['fee']['penalty_percent'], $dataDB['fee']['penalty_amount'], $so_ngay_cham_tra_now, $time_period);
					$penalty_pay = $this->tinh_phi_phat($c['tien_tra_1_ky'], $dataDB['fee']['penalty_percent'], $dataDB['fee']['penalty_amount'], $so_ngay_cham_tra_pay, $time_period);
					$penalty_now_tt += $penalty_now;
					$penalty_pay_tt += $penalty_pay;
					$arr_penaty += [$c['ky_tra'] => ['so_tien' => $penalty_pay, 'so_ngay' => $so_ngay_cham_tra_pay]];
					$c['penalty_now'] = $penalty_now;
				}
			}
		}

		$dataDB['so_ngay_vay'] = $so_ngay_vay;
		$dataDB['penalty_pay'] = $penalty_pay_tt;
		$dataDB['penalty_now'] = $penalty_now_tt;
		$dataDB['arr_penaty'] = $arr_penaty;
		return $dataDB;
	}

	public function tinh_phi_phat($so_tien_tra_hang_ky, $penaltyPercent, $penalty_amount, $so_ngay_cham_tra, $so_ngay_trong_ky)
	{

		$phi_phat = 0;

		if ($so_ngay_trong_ky > 0) {
			$phi_phat_tinh_lai = ($so_tien_tra_hang_ky * ($penaltyPercent / 100) * $so_ngay_cham_tra) / $so_ngay_trong_ky;
			if ($so_ngay_cham_tra > 3) {
				if ($phi_phat_tinh_lai < $penalty_amount) {
					$phi_phat = (int)$penalty_amount;
				} else {
					$phi_phat = $phi_phat_tinh_lai;
				}
			}

		}

		return $phi_phat;
	}

	public function tinh_so_ngay_trong_ky_cham_tra($ky_tra = 0, $ngay_giai_ngan = 0, $ky_han = 0, $ky_han_truoc = 0)
	{

		if ($ky_tra == 1) {
			$so_ngay_trong_ky = intval((strtotime(date('Y-m-d', $ky_han)) - strtotime(date('Y-m-d', $ngay_giai_ngan))) / (24 * 60 * 60)) + 1;
		} else {
			$so_ngay_trong_ky = intval((strtotime(date('Y-m-d', $ky_han)) - strtotime(date('Y-m-d', $ky_han_truoc))) / (24 * 60 * 60));
		}
		return $so_ngay_trong_ky;
	}

	public function tinh_so_ngay_trong_ky($ky_tra = 0, $ngay_giai_ngan = 0, $ky_han = 0, $ky_han_truoc = 0)
	{

		if ($ky_tra == 1) {
			$so_ngay_trong_ky = intval((strtotime(date('Y-m-d', $ky_han)) - strtotime(date('Y-m-d', $ngay_giai_ngan))) / (24 * 60 * 60)) + 1;
		} else {

			$so_ngay_trong_ky = intval((strtotime(date('Y-m-d', $ky_han)) - strtotime(date('Y-m-d', $ky_han_truoc))) / (24 * 60 * 60));
		}
		return $so_ngay_trong_ky;
	}

	public function sum_where()
	{
		$ops = array(
			array(
				'$match' => array(
					"status" => 17
				)
			),
			array(
				'$group' => array(
					'_id' => null,
//					'tt' => array('$toLong'=> '$loan_infor.amount_money'),
					'total' => array('$sum' => array('$toLong' => '$loan_infor.amount_money')),
				),
			),
		);
		$data = $this->mongo_db->aggregate($this->collection, $ops)->toArray();
		return $data;
	}

	public function sum_where_total_amount($condtion = array(), $get)
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => array('$toLong' => '$loan_infor.amount_money')),
				),
			),
		);
		$data = $this->mongo_db->aggregate($this->collection, $ops)->toArray();
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}

	public function find_where_select($condition, $value)
	{
		return $this->mongo_db
			->select($value)
			->get_where($this->collection, $condition);
	}


//	public function find_select_export($per_page)
//	{
//		return $this->mongo_db
//			->select(array("status", "code_contract","code_contract_disbursement", "store.name", 'customer_infor.customer_name', 'customer_infor.customer_gender', 'customer_infor.customer_BOD', 'current_address.ward_name', 'current_address.district_name', 'current_address.province_name', 'job_infor.job', 'job_infor.salary', 'job_infor.job_position', 'job_infor.receive_salary_via', 'loan_infor.name_property.text', 'loan_infor.amount_money', 'loan_infor.type_interest', 'loan_infor.number_day_loan', 'loan_infor.loan_purpose', 'loan_infor.amount_loan'))
//			->limit($per_page)
//			->get($this->collection);
//	}

	public function find_select_export($condition = array())
	{
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'_id' => 1,
						'status' => 1,
						'code_contract_disbursement' => 1,
						'store.name' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_gender' => 1,
						'customer_infor.customer_BOD' => 1,
						'current_address.ward_name' => 1,
						'current_address.district_name' => 1,
						'current_address.province_name' => 1,
						'job_infor.job' => 1,
						'job_infor.salary' => 1,
						'job_infor.job_position' => 1,
						'job_infor.receive_salary_via' => 1,
						'loan_infor.name_property.text' => 1,
						'loan_infor.amount_money' => 1,
						'loan_infor.type_interest' => 1,
						'loan_infor.number_day_loan' => 1,
						'loan_infor.loan_purpose' => 1,
						'loan_infor.amount_loan' => 1,
						'code_contract' => 1,
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'localField' => "code_contract",
						'foreignField' => "code_contract",
						'as' => "contract_info"
					],
				],

			],
			'cursor' => new stdClass,
		];

		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		try {
			$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);

			foreach ($cursor as $item) {
				array_push($arr, $item);
			}
			return $arr;
		} catch (\MongoDB\Driver\Exception\Exception $e) {
			return $e->getMessage();
		}
	}

	public function find_select_transaction($condition = array())
	{
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'_id' => 1,
						'status' => 1,
						'code_contract_disbursement' => 1,
						'store.name' => 1,
						'customer_infor.customer_name' => 1,
						'customer_infor.customer_gender' => 1,
						'customer_infor.customer_BOD' => 1,
						'current_address.ward_name' => 1,
						'current_address.district_name' => 1,
						'current_address.province_name' => 1,
						'job_infor.job' => 1,
						'job_infor.salary' => 1,
						'job_infor.job_position' => 1,
						'job_infor.receive_salary_via' => 1,
						'loan_infor.name_property.text' => 1,
						'loan_infor.amount_money' => 1,
						'loan_infor.type_interest' => 1,
						'loan_infor.number_day_loan' => 1,
						'loan_infor.loan_purpose' => 1,
						'loan_infor.amount_loan' => 1,
						'code_contract' => 1,
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
						'localField' => "code_contract",
						'foreignField' => "code_contract",
						'as' => "contract_info"
					],
				],

			],
			'cursor' => new stdClass,
		];

		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		try {
			$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);

			foreach ($cursor as $item) {
				array_push($arr, $item);
			}
			return $arr;
		} catch (\MongoDB\Driver\Exception\Exception $e) {
			return $e->getMessage();
		}
	}

	public function get_contract_field_debt($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['result_reminder.0.created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['reminder_now'])) {
			$where['result_reminder.0.reminder'] = $condition['reminder_now'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		if (isset($condition['district'])) {
			$in = $condition['district'];
			unset($condition['district']);
		}
		if (isset($condition['user_debt'])) {
			$in_user = $condition['user_debt'];
			unset($condition['user_debt']);
		}
		if (!empty($in) && !empty($in_user)) {
			return $mongo
				->order_by($order_by)
				->where_in('current_address.district', array_values($in))
				->where_not_in('user_debt', array_values($in_user))
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_contract_field_debt_total($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['result_reminder.0.created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['reminder_now'])) {
			$where['result_reminder.0.reminder'] = $condition['reminder_now'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
//		if (isset($condition['created_by'])) {
//			$where['created_by'] = $condition['created_by'];
//		}
		if (isset($condition['province'])) {
			$where['current_address.province'] = $condition['province'];
		}

//		if (isset($condition['debt_status'])) {
//			$where['debt_field.' . $condition['user_id'] . '.evaluate'] = $condition['debt_status'];
//			unset($condition['debt_status']);
//		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['district'])) {
			$in = $condition['district'];
			unset($condition['district']);
		}
		if (isset($condition['user_debt'])) {
			$in_user = $condition['user_debt'];
			unset($condition['user_debt']);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
			->order_by($order_by)
			->where_in('current_address.district', array_values($in))
			->where_not_in('user_debt', array_values($in_user))
			->count($this->collection);

	}

	public function excelContractByTime($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] == "17") {
				$where['status'] = array(
					'$gte' => (int)$condition['status']
				);
			} else {
				$where['status'] = (int)$condition['status'];
			}
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}

		return $mongo->order_by($order_by)
			->select(array(), array('image_accurecy'))
			->get($this->collection);
	}

	public function excelContractByTimeTotal($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			if ($condition['status'] == "17") {
				$where['status'] = array(
					'$gte' => (int)$condition['status']
				);
			} else {
				$where['status'] = (int)$condition['status'];
			}
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		if (!empty($condition['customer_name'])) {
//			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
//		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}

		return $mongo
			->count($this->collection);
	}

	public function get_all_contract_field_debt($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['result_reminder.0.created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['reminder_now'])) {
			$where['result_reminder.0.reminder'] = $condition['reminder_now'];
		}

		if (isset($condition['user_id'])) {
			$where['user_debt'] = $condition['user_id'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
			->order_by($order_by)
//			->where_in('status', [17, 19])
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function get_all_contract_field_debt_total($condition = array())
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['result_reminder.0.created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['reminder_now'])) {
			$where['result_reminder.0.reminder'] = $condition['reminder_now'];
		}
		if (isset($condition['user_id'])) {
			$where['user_debt'] = $condition['user_id'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);

		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
//			->where_in('status', [17, 19])
			->count($this->collection);

	}

	public function get_contract_user_field_debt($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['result_reminder.0.created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['reminder_now'])) {
			$where['result_reminder.0.reminder'] = $condition['reminder_now'];
		}

		if (isset($condition['user_id'])) {
			$where['user_debt'] = $condition['user_id'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
//			->where_in('status', [17, 19])
			->get($this->collection);

	}

	public function contract_is_due($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		$where['debt.so_ngay_cham_tra'] = [
			'$gte' => -5,
			'$lte' => 3
		];
		$where['status'] = 17;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		return $mongo
			->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function count_contract_is_due($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['debt.so_ngay_cham_tra'] = [
			'$gte' => -5,
			'$lte' => 3
		];
		$where['status'] = 17;
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		return $mongo
			->order_by($order_by)
			->count($this->collection);
	}

	public function asset_contract()
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['asset_code'] = ['$exists' => false];
		$where['status'] = ['$in' => [1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30]];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}

	public function asset_contract_limit()
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['asset_code'] = ['$exists' => false];
		$where['status'] = ['$in' => [1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30]];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->limit(1)
			->order_by($order_by)
			->get($this->collection);
	}

	public function asset_contract_update()
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['asset_code'] = ['$exists' => true];
		$where['status'] = ['$in' => [1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30]];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}

	public function contract_tempo_debt_ho($condition, $limit, $offset)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function total_contract_tempo_debt_ho($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}
		return $mongo
			->count($this->collection);
	}

	public function contract_tempo_debt_ho_all($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['created_at' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->where_text($condition['customer_name'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		if (!empty($condition['id_card'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['id_card']);
		}

		return $mongo->order_by($order_by)
			->get($this->collection);
	}
}

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contract_model extends CI_Model
{

	private $collection = 'contract';

	private $manager, $createdAt, $manager_read;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("transaction_model");
		$this->load->helpers('lead_helper');
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
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)->find_one($this->collection);
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
//			$where['created_at'] = array(
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['search_htv'])) {
			$where['loan_infor.type_loan.code'] = $condition['search_htv'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
//		if (isset($condition['created_by'])) {
//			$where['created_by'] = $condition['created_by'];
//		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		} else {
			$where['type'] = array('$ne' => "vaynhanh");
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 1) {
			$where['customer_infor.type_contract_sign'] = array('$in'=> array('1'));
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 2) {
			$where['customer_infor.type_contract_sign'] = '2';
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 3) {
			$where['customer_infor.type_contract_sign'] = array('$exists'=> false);
		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "GH") {
			$where['status'] = array('$in' => [11, 13, 21, 25, 29, 30, 26, 33, 17, 19]);
			$where['count_extension'] = array('$exists' => true);
		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "CC") {
			$where['status'] = array('$in' => [12, 14, 23, 24, 27, 28, 31, 32, 34, 17, 19]);
			$where['count_structure'] = array('$exists' => true);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đang xử lý") {
			$where['status'] = array('$in' => [1,2,4,5,6,7,8,9,10,11,12,13,14,15,16,35,36]);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đã hủy") {
			$where['status'] = array('$in' => [3]);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đang vay") {
			$where['status'] = array('$in' => [17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,40,41,42,43]);
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

		if (isset($condition['created_by'])) {
//			$where['created_by'] = $condition['created_by'];
			$mongo = $mongo->or_where(array('created_by'=> $condition['created_by'] , 'follow_contract' => $condition['created_by'] ));

		}


		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
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
		if (isset($condition['phone_number_relative'])) {
			$mongo = $mongo->or_where(array('relative_infor.phone_number_relative_1'=> $condition['phone_number_relative'] , 'relative_infor.phone_number_relative_2' => $condition['phone_number_relative'] , 'relative_infor.phone_relative_3' => $condition['phone_number_relative'] ));

		}
		if (isset($condition['fullname_relative'])) {
			$mongo = $mongo->or_where(array('relative_infor.fullname_relative_1'=> $condition['fullname_relative'] , 'relative_infor.fullname_relative_2' => $condition['fullname_relative'] , 'relative_infor.fullname_relative_3' => $condition['fullname_relative'] ));
		}


		if (!empty($in)) {
			return $mongo->order_by($order_by)
			    ->select(array(), array('image_accurecy'))
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
			    ->select(array(), array('image_accurecy'))
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

	public function find_where_cancel($condition)
	{
		return $this->mongo_db
			->select(["status", "code_contract"])
			->get_where($this->collection, $condition);
	}

	public function find_where_mhd($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$in = [];
		$mongo = $this->mongo_db;

//		$where['status'] = array('$in' => [17,9]);
//		$where['status'] = array('$gte' => 17);
		$where['status'] = array('$in' => [17, 20, 21, 22, 23, 24, 25, 26, 27,28,29,30,31,32,33,34,37,38,39,40,41,42]);

		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($in)) {
			return $mongo
				->order_by($order_by)
				->where_in('store.id', $in)
				->select(["code_contract_disbursement"])
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->select(["code_contract_disbursement"])
				->get($this->collection);
		}

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

			$mongo = $mongo->or_where(array('customer_infor.customer_phone_number' => $condition['customer_phone_number']));
		}
		if (isset($condition['customer_identify'])) {
			$mongo = $mongo->or_where(array("customer_infor.customer_identify" => $condition["customer_identify"], "customer_infor.customer_identify_old" => $condition["customer_identify"]));
		}
		if (isset($condition['customer_identify_old'])) {
			$mongo = $mongo->or_where(array("customer_infor.customer_identify" => $condition["customer_identify_old"], "customer_infor.customer_identify_old" => $condition["customer_identify_old"]));
		}
		if (isset($condition['phone_number_relative_1'])) {
			$mongo = $mongo->or_where(array('customer_infor.customer_phone_number' => $condition['phone_number_relative_1']));

		}
		if (isset($condition['phone_number_relative_2'])) {
			$mongo = $mongo->or_where(array('customer_infor.customer_phone_number' => $condition['phone_number_relative_2']));
		}
		// if (isset($condition['phone_number_relative_1'])) {
		// 	$where['relative_infor.phone_number_relative_1'] = $condition['phone_number_relative_1'];
		// }
		// if (isset($condition['phone_number_relative_2'])) {
		// 	$where['relative_infor.phone_number_relative_2'] = $condition['phone_number_relative_2'];
		// }


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
		$order_by = ['disbursement_date' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (!isset($condition['status'])) {

			$where['status'] = array(
				'$gte' => 17
			);
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];

		}
		if (isset($condition['tab']) && $condition['tab'] == "rerun_cc") {
			$where['type_cc'] = 'origin';
			//$where['code_contract_child_gh'] = array('$exists' => true);
		}
		if (isset($condition['tab']) && $condition['tab'] == "rerun_gh") {
			$where['type_gh'] = 'origin';
			//$where['code_contract_child_gh'] = array('$exists' => true);
		}
		if (isset($condition['tab']) && $condition['tab'] == "import_payment") {

			$where['count_structure'] = array('$exists' => false);
			$where['count_extension'] = array('$exists' => false);
			$where['status'] = array('$in' => [17, 19]);
		}
		if (isset($condition['tab']) && $condition['tab'] == "run_fee_again") {

			$where['count_structure'] = array('$exists' => false);
			$where['count_extension'] = array('$exists' => false);
			$where['status'] = array('$in' => [17, 19]);
		}
		if (isset($condition['tab']) && $condition['tab'] == "wait") {


			$where['status'] = array('$in' => [9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 37, 38, 39, 40, 41, 42]);

		}
		if (isset($condition['tab']) && $condition['tab'] == "erro_nl") {


			$where['status'] = array('$in' => [15, 9]);
		}
       if (isset($condition['tab']) && $condition['tab'] == "contract_lock") {
       	$where['status'] = array('$in' => [19]);
       }

		// if (isset($condition['status_disbursement'])) {
		// 	$where['status_disbursement'] = (int)$condition['status_disbursement'];
		// }
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
		if (isset($condition['tab']) && $condition['tab'] == "wait") {
			$mongo = $mongo->or_where(["debt" => array('$exists' => false), "debt.ky_tt_xa_nhat" => 0]);
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
		if (isset($condition['fBucket']) && isset($condition['tBucket'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fBucket'],
				'$lte' => $condition['tBucket']
			);
		}
		if (empty($condition['status'])) {
			$where['status'] = array('$gte' => 17);
		} else {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		// if (isset($condition['store'])) {
		// 	$where['store.id'] = $condition['store'];
		// }
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
		// tìm theo mã hợp đồng và van
		if (isset($condition['code_contract']) && isset($condition['code_contracts'])) {
			if (!in_array($condition['code_contract'], $condition['code_contracts'])) {
				return [];
			} else {
				$mongo = $mongo->where_in('code_contract', array($condition['code_contract']));
			}
		} else if (isset($condition['code_contract']) && !isset($condition['code_contracts'])) {
			//tìm theo mã hđ
			$mongo = $mongo->where_in('code_contract', array($condition['code_contract']));
		} else if (isset($condition['code_contracts']) && !isset($condition['code_contract'])) {
			// tìm theo van
			$mongo = $mongo->where_in('code_contract', $condition['code_contracts']);
		} else {
			//do nothing
		}
		// if (isset($condition['store_vung'])) {
		// 	$in = $condition['store_vung'];
		// }

		if(isset($condition['store']) ) {
			$mongo = $mongo->where_in('store.id', array($condition['store']));
		} else if (!isset($condition['store']) && isset($condition['store_vung'])) {
			$mongo = $mongo->where_in('store.id', $condition['store_vung']);
		} else {
			// do nothing
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
		if (isset($condition['fBucket']) && isset($condition['tBucket'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fBucket'],
				'$lte' => $condition['tBucket']
			);
		}
		if (empty($condition['status'])) {
			$where['status'] = array('$gte' => 17);
		} else {
			$where['status'] = (int)$condition['status'];
		}

		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		// if (isset($condition['store'])) {
		// 	$where['store.id'] = $condition['store'];
		// }
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
		// tìm theo mã hợp đồng và van
		if (isset($condition['code_contract']) && isset($condition['code_contracts'])) {
			if (!in_array($condition['code_contract'], $condition['code_contracts'])) {
				return [];
			} else {
				$mongo = $mongo->where_in('code_contract', array($condition['code_contract']));
			}
		} else if (isset($condition['code_contract']) && !isset($condition['code_contracts'])) {
			//tìm theo mã hđ
			$mongo = $mongo->where_in('code_contract', array($condition['code_contract']));
		} else if (isset($condition['code_contracts']) && !isset($condition['code_contract'])) {
			// tìm theo van
			$mongo = $mongo->where_in('code_contract', $condition['code_contracts']);
		} else {
			//do nothing
		}
		// if (isset($condition['store_vung'])) {
		// 	$in = $condition['store_vung'];
		// }

		if(isset($condition['store']) ) {
			$mongo = $mongo->where_in('store.id', array($condition['store']));
		} else if (!isset($condition['store']) && isset($condition['store_vung'])) {
			$mongo = $mongo->where_in('store.id', $condition['store_vung']);
		} else {
			// do nothing
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

		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		$where['status'] =17;
		$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => -5,
				'$lte' => 0
			);
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_name'])) {
			$mongo = $mongo->like("customer_infor.customer_name", $condition['customer_name']);
		}
		if($condition['total'])
		{
          return $mongo->where_not_in('status', array(19, 23))->order_by($order_by)
			->count($this->collection);
		}else{
		return $mongo->where_not_in('status', array(19, 23))->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
		}
	}

	public function getRemind_debt_first_thn($condition = array())
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

	public function get_import_gh_cc($condition = array(), $limit = 30, $offset = 0)
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
		if (isset($condition['is_cocau'])) {
			$where['structure_all'] = array('$exists' => true);
			$where['is_import_cc'] = array('$exists' => true);
			$where['type_cc'] = 'origin';
		}
		if (isset($condition['is_giahan'])) {
			$where['extend_all'] = array('$exists' => true);
			$where['is_import_gh'] = array('$exists' => true);
			$where['type_gh'] = 'origin';
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
		if (isset($condition['is_export']) && $condition['is_export']==1)
		{
         $mongo = $this->mongo_db_read;
		}else{
         $mongo = $this->mongo_db;
		}

		if ($condition['ngaygiaingan'] == '1') {
			if (isset($condition['start']) && isset($condition['end'])) {
				$where['created_at'] = array(
//				$where['disbursement_date'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
				unset($condition['start']);
				unset($condition['end']);
			}
		} else {
			if (isset($condition['start']) && isset($condition['end'])) {
				$where['disbursement_date'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);

				unset($condition['start']);
				unset($condition['end']);
			}

		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "GH") {
			$where['status'] = array('$in' => [11, 13, 21, 25, 29, 30, 26, 33, 17, 19]);
			$where['count_extension'] = array('$exists' => true);
		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "CC") {
			$where['status'] = array('$in' => [12, 14, 23, 24, 27, 28, 31, 32, 34, 17, 19]);
			$where['count_structure'] = array('$exists' => true);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đang xử lý") {
			$where['status'] = array('$in' => [1,2,4,5,6,7,8,9,10,11,12,13,14,15,16,35,36]);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đã hủy") {
			$where['status'] = array('$in' => [3]);
		}
		if (isset($condition['search_status']) && $condition['search_status'] == "Đang vay") {
			$where['status'] = array('$in' => [17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,37,38,39,40,41,42,43]);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['search_htv'])) {
			$where['loan_infor.type_loan.code'] = $condition['search_htv'];
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 1) {
			$where['customer_infor.type_contract_sign'] = array('$in'=> array('1'));
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 2) {
			$where['customer_infor.type_contract_sign'] = '2';
		}
		if (isset($condition['type_contract_digital']) && $condition['type_contract_digital'] == 3) {
			$where['customer_infor.type_contract_sign'] = array('$exists'=> false);
		}

		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
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

		if (isset($condition['created_by'])) {
//			$where['created_by'] = $condition['created_by'];
			$mongo = $mongo->or_where(array('created_by'=> $condition['created_by'] , 'follow_contract' => $condition['created_by'] ));

		}

		if (isset($condition['phone_number_relative'])) {
			$mongo = $mongo->or_where(array('relative_infor.phone_number_relative_1'=> $condition['phone_number_relative'] , 'relative_infor.phone_number_relative_2' => $condition['phone_number_relative'] , 'relative_infor.phone_relative_3' => $condition['phone_number_relative'] ));

		}
		if (isset($condition['fullname_relative'])) {
			$mongo = $mongo->or_where(array('relative_infor.fullname_relative_1'=> $condition['fullname_relative'] , 'relative_infor.fullname_relative_2' => $condition['fullname_relative'] , 'relative_infor.fullname_relative_3' => $condition['fullname_relative'] ));
		}

		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		// if (!empty($condition['code_contract_disbursement'])) {
		// 	$mongo = $mongo->where_in_like("code_contract_disbursement", $condition['code_contract_disbursement']);

		// }


		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
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
			    ->select(array(), array('image_accurecy'))
				->limit($limit)
				->offset($offset)
				->where_in('store.id', $in)
				->get($this->collection,$condition);
		} else {
			return $mongo->order_by($order_by)
			    ->select(array(), array('image_accurecy'))
				->limit($limit)
				->offset($offset)
				->get($this->collection,$condition);
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
			$where['code_contract_parent_gh'] = array('$exists' => false);
			$where['code_contract_parent_cc'] = array('$exists' => false);
			$where['status'] = array('$gte' => 17);

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
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$match' =>
					[

						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$gte' => array($condition['start'], '$date_pay')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'tien_thua_thanh_toan' => 1,

								]
							)

						),
						'as' => "transaction_last",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'tien_thua_thanh_toan' => 1,

								]
							)


						),
						'as' => "transaction",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$gte' => array($condition['start'], '$date_pay')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,

								]
							)

						),
						'as' => "transaction_last_extend",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'tien_thua_thanh_toan' => 1,

								]
							)


						),
						'as' => "transaction_extend",
					]
				],

				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
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
												'$gte' => array($condition['start'], '$time_timestamp')
											)
										)
									)
								),
							),

							array(
								'$project' => [
									'tien_lai_1thang' => 1,
									'tien_phi_1thang' => 1,
									'tien_goc_1thang' => 1,


								]
							)


						),
						'as' => "plan_contract_last",
					]
				],
				['$project' =>
					[
						'code_contract' => 1,
						'code_contract_disbursement' => 1,
						'customer_infor' => 1,
						'fee' => 1,
						'expire_date' => 1,
						'disbursement_date' => 1,
						'loan_infor' => 1,
						'debt' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,
						'investor_infor' => 1,
						'type_gh' => 1,
						'code_contract_parent_gh' => 1,


						// lãi kỳ tháng trước
						"tien_lai_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_lai_1thang'
						),
						"tien_phi_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_phi_1thang'
						),
						"tien_goc_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_goc_1thang'
						),
						//đã trả trong tháng

						"tien_lai_1thang_da_tra" => array(
							'$sum' => '$transaction.so_tien_lai_da_tra'
						),
						"tien_lai_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
						),
						"tien_goc_1thang_da_tra" => array(
							'$sum' => '$transaction.so_tien_goc_da_tra'
						),
						"tien_phi_1thang_da_tra" => array(
							'$sum' => '$transaction.so_tien_phi_da_tra'
						),
						"tien_phi_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_phi_da_tra'
						),
						"so_tien_phi_gia_han_1thang_da_tra" => array(
							'$sum' => '$transaction.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_cham_tra_1thang_da_tra" => array(
							'$sum' => '$transaction.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_1thang_da_tra" => array(
							'$sum' => '$transaction.fee_finish_contract'
						),
						"so_tien_phi_phat_sinh_1thang_da_tra" => array(
							'$sum' => '$transaction.tien_phi_phat_sinh_da_tra'
						),
						"so_tien_thua_tat_toan_1thang_da_tra" => array(
							'$sum' => '$transaction.tien_thua_tat_toan'
						),
						"so_tien_thua_thanh_toan_1thang_da_tra" => array(
							'$sum' => '$transaction.tien_thua_thanh_toan'
						),
						//đã trả các tháng trước
						"so_tien_goc_da_thu_hoi" => array(
							'$sum' => '$transaction_last.so_tien_goc_da_tra'
						),
						"so_tien_lai_da_thu_hoi" => array(
							'$sum' => '$transaction_last.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi" => array(
							'$sum' => '$transaction_last.so_tien_phi_da_tra'
						),
						"so_tien_lai_da_thu_hoi_tien_thua" => array(
							'$sum' => '$transaction_last_extend.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_tien_thua" => array(
							'$sum' => '$transaction_last_extend.so_tien_phi_da_tra'
						),

						"so_tien_phi_gia_han_da_thu_hoi" => array(
							'$sum' => '$transaction_last.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_cham_tra_da_thu_hoi" => array(
							'$sum' => '$transaction_last.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_da_thu_hoi" => array(
							'$sum' => '$transaction_last.fee_finish_contract'
						),
						"so_tien_phi_phat_sinh_da_thu_hoi" => array(
							'$sum' => '$transaction_last.tien_phi_phat_sinh_da_tra'
						),
						"so_tien_thua_tat_toan_da_thu_hoi" => array(
							'$sum' => '$transaction_last.tien_thua_tat_toan'
						),
						"so_tien_thua_thanh_toan_da_thu_hoi" => array(
							'$sum' => '$transaction_last.tien_thua_thanh_toan'
						),

					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
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
											$compareLte,
											$compareGte
										)
									)
								),
							),
							array(
								'$sort' => array(
									"time_timestamp" => 1
								)
							),
							array(
								'$limit' => 1
							),
							array(
								'$project' => [
									'time_timestamp' => 1,
									'count_date_interest' => 1,
									'so_ngay_trong_thang' => 1,
									'so_ngay_trong_thang_dau' => 1,
									'du_no_lai_thang_truoc' => 1,
									'du_no_phi_thang_truoc' => 1,
									'du_no_goc_thang_truoc' => 1,
									'tien_lai_1thang' => 1,

								]
							)

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
			// if (empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function get_lich_su_hop_dong($condition)
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
				['$match' =>
					[

						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),
					]
				],

				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),

											array(
												'$gte' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'tien_thua_thanh_toan' => 1,
									'so_tien_phi_tat_toan_phai_tra_tat_toan' => 1

								]
							)


						),
						'as' => "transaction",
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

										)
									)
								),
							),
							array(
								'$project' => [
									'tien_goc_1ky_phai_tra' => 1,
									'tien_lai_1ky_phai_tra' => 1,
									'tien_phi_1ky_phai_tra' => 1,
									'fee_delay_pay' => 1

								]
							)

						),
						'as' => "plan_contract",
					]
				],


				['$project' =>
					[
						'code_contract' => 1,
						'code_contract_disbursement' => 1,
						'response_get_transaction_withdrawal_status_nl' => 1,
						'extend_date' => 1,
						'customer_infor' => 1,
						'fee' => 1,
						'expire_date' => 1,
						'disbursement_date' => 1,
						'loan_infor' => 1,
						'debt' => 1,
						'store.name' => 1,
						'created_at' => 1,
						'status' => 1,
						'investor_infor' => 1,
						'type_gh' => 1,

						//phải trả
						"tien_phi_phai_tra" => array(
							'$sum' => '$plan_contract.tien_phi_1ky_phai_tra'
						),
						"tien_lai_phai_tra" => array(
							'$sum' => '$plan_contract.tien_lai_1ky_phai_tra'
						),
						"tien_goc_phai_tra" => array(
							'$sum' => '$plan_contract.tien_goc_1ky_phai_tra'
						),
						"so_tien_phi_tat_toan_phai_tra_tat_toan" => array(
							'$sum' => '$transaction.so_tien_phi_tat_toan_phai_tra_tat_toan'
						),

						//đã trả
						"tien_lai_da_tra" => array(
							'$sum' => '$transaction.so_tien_lai_da_tra'
						),

						"tien_goc_da_tra" => array(
							'$sum' => '$transaction.so_tien_goc_da_tra'
						),
						"tien_phi_da_tra" => array(
							'$sum' => '$transaction.so_tien_phi_da_tra'
						),

						"so_tien_phi_gia_han_da_tra" => array(
							'$sum' => '$transaction.so_tien_phi_gia_han_da_tra'
						),


						"so_tien_phi_tat_toan_da_tra" => array(
							'$sum' => '$transaction.fee_finish_contract'
						),


					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$gte' => array($condition['end'], '$date_pay')
											),

										)
									)
								),
							),
							array(
								'$sort' => array(
									"date_pay" => 1
								)
							),
							array(
								'$limit' => 1
							),
							array(
								'$project' => [
									'date_pay' => 1,

								]
							)

						),
						'as' => "transaction_last",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array('$type', 3)
											),
											array(
												'$eq' => array('$status', 1)
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											),

										)
									)
								),
							),
							array(
								'$sort' => array(
									"date_pay" => 1
								)
							),
							array(
								'$limit' => 1
							),
							array(
								'$project' => [
									'date_pay' => 1,

								]
							)

						),
						'as' => "transaction_tt",
					]
				],


			],
			'cursor' => new stdClass,
		];
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach ($cursor as $item) {
			// if (empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getFollowCurrentMonth($condition)
	{
//		$compareLte = array();
//		if (!empty($condition['start'])) {
//			$compareLte = array(
//				'$eq' => array((string)date('m', strtotime(date('Y-m-d', $condition['start']) . " -1 month")), '$month')
//			);
//
//		}
//		$compareGte = array();
//		if (!empty($condition['end'])) {
//			$compareGte = array(
//				'$eq' => array((string)date('Y', strtotime(date('Y-m-d', $condition['year']))), '$year')
//			);
//		}
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
				//lấy hợp đồng >17, và ngày giải ngân nhỏ hơn ngày cuối tháng
				['$match' =>
					[
						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),

					]
				],
				//lấy bảng lãi kỳ các tháng trước, ngày kỳ trả < ngày đầu tháng start
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract'
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
												'$gt' => array($condition['start'], '$ngay_ky_tra')
											)
										)
									)
								),
							),

						),
						'as' => "lai_ky_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của các tháng trước
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$gt' => array($condition['start'], '$date_pay')
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
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'tien_thua_thanh_toan' => 1,

								]
							)

						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của tháng hiện tại
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											)

										)
									)
								),
							),
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				// lấy bảng phiếu thu tất cả của hợp đồng
				['$lookup' =>
					[
						'from' => 'transaction',
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
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke",
					]
				],
				// lấy bảng lãi kỳ đến tháng Tn đến cuối tháng đang tìm
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
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)

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
									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1,
								]
							)
						),
						'as' => "bang_lai_ky_den_thang_Tn",
					]
				],

				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
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
												'$gte' => array($condition['start'], '$time_timestamp')
											)
										)
									)
								),
							),

							array(
								'$project' => [
									'tien_lai_1thang' => 1,
									'tien_phi_1thang' => 1,
									'tien_goc_1thang' => 1,


								]
							)


						),
						'as' => "plan_contract_last",
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
									'fee_delay_pay' => 1,
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
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$gt' => array($condition['start'], '$date_pay')
											),
										)
									)
								),
							),
							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,

								]
							)

						),
						'as' => "transaction_last_extend",
					]
				],
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gt' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,
									'code_contract_parent_gh' => 1,
									'tien_thua_thanh_toan' => 1,
								]
							)


						),
						'as' => "transaction_extend",
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
						'debt' => 1,
						'created_at' => 1,
						'status' => 1,
						'code_contract_parent_gh' => 1,
						'type_gh' => 1,
						"phai_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.tien_tra_1_ky'
						),
						"da_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.da_thanh_toan'
						),
						"lai_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.lai_ky'
						),

						"phi_tu_van_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tu_van',
						),
						"phi_tham_dinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tham_dinh',
						),
						"phi_tra_cham_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_delay_pay',
						),
						"phi_tra_truoc_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_finish_contract',
						),
						"phi_gia_han_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_extend',
						),
						"phi_phat_sinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.phi_phat_sinh'
						),

						//Đến thời điểm đáo hạn
						"tong_phai_thu_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.tien_tra_1_ky'
						),
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
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_delay_pay'
						),
						"phi_tra_truoc_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_finish_contract'
						),
						"phi_gia_han_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_gia_han'
						),
						"phi_phat_sinh_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_phat_sinh'
						),
						//phải thu tháng trước
						"so_tien_goc_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_goc_1ky_phai_tra'
						),
						"so_tien_lai_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_lai_1ky_phai_tra'
						),
						"so_tien_phi_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_phi_1ky_phai_tra'
						),
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

						"tong_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.total'
						),
						"tong_thu_hoi_luy_ke_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.total'
						),
						"so_tien_phi_gia_han_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_gia_han_da_tra'
						),


						"so_tien_lai_da_thu_hoi" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.so_tien_phi_da_tra'
						),

						"so_tien_thua_thanh_toan_da_thu_hoi" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.tien_thua_thanh_toan'
						),
						"so_tien_thua_tat_toan_da_thu_hoi" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.tien_thua_tat_toan'
						),

						"so_tien_lai_da_thu_hoi_tien_thua" => array(
							'$sum' => '$transaction_last_extend.so_tien_lai_da_tra'
						),
						"so_tien_phi_da_thu_hoi_tien_thua" => array(
							'$sum' => '$transaction_last_extend.so_tien_phi_da_tra'
						),

//						"so_tien_lai_da_thu_hoi_tien_thua" => array(
//							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
//						),


						// lãi kỳ tháng trước
						"tien_lai_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_lai_1thang'
						),
						"tien_phi_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_phi_1thang'
						),
						"tien_goc_thang_truoc" => array(
							'$sum' => '$plan_contract_last.tien_goc_1thang'
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

						"so_tien_phi_cham_tra_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_truoc_han_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.fee_finish_contract'
						),

						"type" => array(
							'$sum' => '$transaction_thang_hien_tai.type'
						),

						//Phí phát sinh
						"so_tien_phi_tra_cham_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_phat_sinh_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.tien_phi_phat_sinh_da_tra'
						),

						//Gia Han
//						"tien_lai_1thang_da_tra_tien_thua" => array(
//							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
//						),
//						"tien_phi_1thang_da_tra_tien_thua" => array(
//							'$sum' => '$transaction_extend.so_tien_phi_da_tra'
//						),
//						"tien_goc_1thang_da_tra" => array(
//							'$sum' => '$transaction_extend.so_tien_goc_da_tra'
//						),
						"tong_thu_hoi_gia_han" => array(
							'$sum' => '$transaction_extend.total'
						),

						//đã trả trong tháng
						"tien_lai_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_lai_da_tra'
						),
						"tien_lai_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
						),
						"tien_goc_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_goc_da_tra'
						),
						"tien_phi_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_da_tra'
						),
						"tien_phi_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_phi_da_tra'
						),
						"so_tien_phi_gia_han_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_cham_tra_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.fee_finish_contract'
						),
						"so_tien_phi_phat_sinh_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.tien_phi_phat_sinh_da_tra'
						),
						"so_tien_thua_tat_toan_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.tien_thua_tat_toan'
						),
						"so_tien_thua_thanh_toan_1thang_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.tien_thua_thanh_toan'
						),
						"tien_phi_phat_sinh_da_tra" => array(
							'$sum' => '$transaction_thang_hien_tai.tien_phi_phat_sinh_da_tra'
						),

					]
				],
				//lấy bảng lãi kỳ tháng hiện tại
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
												'$lte' => array($condition['start'], '$ngay_ky_tra')
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
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,

									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
//									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
					]
				],
				['$lookup' =>
					[
						'from' => 'temporary_contract_accountting',
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
											$compareLte,
											$compareGte
										)
									)
								),
							),
							array(
								'$sort' => array(
									"time_timestamp" => 1
								)
							),
							array(
								'$limit' => 1
							),
							array(
								'$project' => [
									'time_timestamp' => 1,
									'count_date_interest' => 1,
									'so_ngay_trong_thang' => 1,
									'so_ngay_trong_thang_dau' => 1,
									'du_no_lai_thang_truoc' => 1,
									'du_no_phi_thang_truoc' => 1,
									'du_no_goc_thang_truoc' => 1,
									'tien_lai_1thang' => 1,

								]
							)

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
				'$lte' => array($condition['start'], '$date_pay')
			);
		}
		$compareGteTran = array();
		if (!empty($condition['end'])) {
			$compareGteTran = array(
				'$gte' => array($condition['end'], '$date_pay')
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
											// $compareLteTran,
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
									'so_tien_phi_cham_tra_da_tra' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'fee_finish_contract' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
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
						"so_tien_phi_cham_tra_da_thu_hoi_AE" => array(
							'$sum' => '$transaction.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_phat_sinh_da_thu_hoi_AF" => array(
							'$sum' => '$transaction.tien_phi_phat_sinh_da_tra'
						),
						"so_tien_phi_tat_toan_da_thu_hoi_AG" => array(
							'$sum' => '$transaction.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi_AH" => array(
							'$sum' => '$transaction.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_thua_da_thu_hoi_AI" => array(
							'$sum' => '$transaction.tien_thua_tat_toan'
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
									'fee_delay_pay' => 1,
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
									'fee_delay_pay' => 1,
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
							'$sum' => '$bang_lai_ky.fee_delay_pay',
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
									"ngay_ky_tra" => 1
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
    //lấy gốc lãi phí đến thời điểm tất toán
	public function get_infor_tat_toan_part_1($code_contract, $date_pay)
	{

		//Dư nợ còn lại =
		//gốc: chưa trả đến thời điểm đáo hạn (bảng kỳ)
		//lãi: chưa trả đến thời điểm hiện tại (bảng kỳ)
		//phí: chưa trả đến thời điểm hiện tại (bảng kỳ)
		$so_ngay_trong_ky_tinh_lai = 0;
		$lai_chua_tra_den_thoi_diem_hien_tai = 0;
		$phi_chua_tra_den_thoi_diem_hien_tai = 0;
		$goc_chua_tra_den_thoi_diem_dao_han=0;
		$ky_chua_thanh_toan_gan_nhat = $this->temporary_plan_contract_model->getKiChuaThanhToanGanNhat($code_contract);
		if (!empty($ky_chua_thanh_toan_gan_nhat[0]['ngay_ky_tra']) && $date_pay > $ky_chua_thanh_toan_gan_nhat[0]['ngay_ky_tra']) {
			$lai_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat[0]['tien_lai_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat[0]['tien_lai_1ky_phai_tra'] : 0;
		$phi_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat[0]['tien_phi_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat[0]['tien_phi_1ky_phai_tra'] : 0;
		$ngay_ky_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat[0]['ngay_ky_tra']) ? $ky_chua_thanh_toan_gan_nhat[0]['ngay_ky_tra'] : 0;
		$so_ngay_trong_ky = !empty($ky_chua_thanh_toan_gan_nhat[0]['so_ngay']) ? $ky_chua_thanh_toan_gan_nhat[0]['so_ngay'] : 0;
		}else{
			$ky_chua_thanh_toan_gan_nhat = $this->temporary_plan_contract_model->getCurrentPlan_top($code_contract,$date_pay);
			$lai_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['tien_lai_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat['tien_lai_1ky_phai_tra'] : 0;
		$phi_chua_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['tien_phi_1ky_phai_tra']) ? $ky_chua_thanh_toan_gan_nhat['tien_phi_1ky_phai_tra'] : 0;
		$ngay_ky_tra_ky_chua_thanh_toan_gan_nhat = !empty($ky_chua_thanh_toan_gan_nhat['ngay_ky_tra']) ? $ky_chua_thanh_toan_gan_nhat['ngay_ky_tra'] : 0;
		$so_ngay_trong_ky = !empty($ky_chua_thanh_toan_gan_nhat['so_ngay']) ? $ky_chua_thanh_toan_gan_nhat['so_ngay'] : 0;
		}

		//--------------
		$now = $date_pay;
		$dataDB = $this->contract_model->findOne(array("code_contract" => $code_contract));
		$so_ngay_vay = (isset($this->get_phi_phat_cham_tra((string)$dataDB['_id'], $now)['so_ngay_vay'])) ? (int)$this->get_phi_phat_cham_tra((string)$dataDB['_id']
			, $now)['so_ngay_vay'] : (int)$dataDB['loan_infor']['number_day_loan'];
		$period_pay_interest = 30;
		$so_ky_vay = $so_ngay_vay / $period_pay_interest;
		$type_interest = (int)$dataDB['loan_infor']['type_interest'];
		// ngay giai ngan
		$timestamp_ngay_giai_ngan = strtotime(date('d-m-Y', $dataDB['disbursement_date']));
		$timestamp_ngay_tat_toan = $timestamp_ngay_giai_ngan + $so_ngay_vay * 24 * 3600 - 24 * 60 * 60;
		//lấy số ngày tính lãi phí
		$datediff = $now - $timestamp_ngay_tat_toan;
		$tong_tien_lai_phi_tat_toan = 0;
		$tien_phi_phat_tra_cham = 0;

		$condition_success = array(
			'status' => 2,
			'code_contract' => $dataDB['code_contract'],
			'ngay_ky_tra' => array('$lte' => $date_pay),
		);
		$contract_success = $this->contract_tempo_model->find_where_success($condition_success);
		if (!empty($contract_success)) { // hop dong da thanh toan ky lai
			$ngay_tra_lai_ky = $contract_success[count($contract_success) - 1]['ngay_ky_tra'];
			// ngay giai ngan
			$ngay_tra_lai_ky_gan_nhat = date('d-m-Y', $ngay_tra_lai_ky);
			$timestamp_tra_lai_ky_gan_nhat = strtotime($ngay_tra_lai_ky_gan_nhat);
			$datediff = $now - $timestamp_tra_lai_ky_gan_nhat;

			$so_ngay_da_vay = round($datediff / (60 * 60 * 24));
			$so_ngay_da_vay = $so_ngay_da_vay - 1;
		} else {
			$datediff = $now - $timestamp_ngay_giai_ngan;
			$so_ngay_da_vay = round($datediff / (60 * 60 * 24));
			$so_ngay_da_vay = $so_ngay_da_vay;
		}

		$so_ngay_phat_sinh = $this->get_phi_phat_sinh($dataDB, $date_pay)['so_ngay_phat_sinh'];
		$so_ngay_trong_ky_tinh_lai = $so_ngay_da_vay - $so_ngay_phat_sinh;
		if ($so_ngay_trong_ky_tinh_lai < 0)
			$so_ngay_trong_ky_tinh_lai = 0;
		if ($ngay_ky_tra_ky_chua_thanh_toan_gan_nhat > 0 && $so_ngay_trong_ky > 0) {

			$lai_chua_tra_den_thoi_diem_hien_tai = $so_ngay_trong_ky_tinh_lai * ($lai_chua_tra_ky_chua_thanh_toan_gan_nhat / $so_ngay_trong_ky);
			$phi_chua_tra_den_thoi_diem_hien_tai = $so_ngay_trong_ky_tinh_lai * ($phi_chua_tra_ky_chua_thanh_toan_gan_nhat / $so_ngay_trong_ky);
		}
		$goc_da_tra_dao_han = $this->temporary_plan_contract_model->goc_da_tra_den_thoi_diem_dao_han($code_contract, $date_pay);
		$goc_da_tra = $this->temporary_plan_contract_model->tong_tien_goc_da_tra($code_contract);

		$tong_tien_goc = $this->temporary_plan_contract_model->tong_tien_goc($code_contract, $date_pay);
		$goc_chua_tra_den_thoi_diem_dao_han = $tong_tien_goc - $goc_da_tra_dao_han;
		$goc_chua_tra = $tong_tien_goc - $goc_da_tra;

		$du_no_con_lai = 0;
		$du_no_con_lai = $goc_chua_tra_den_thoi_diem_dao_han + $lai_chua_tra_den_thoi_diem_hien_tai + $phi_chua_tra_den_thoi_diem_hien_tai;

        if(in_array($dataDB['status'], [19,40,33]))
        {

			$lai_chua_tra_den_thoi_diem_hien_tai = 0;
			$phi_chua_tra_den_thoi_diem_hien_tai = 0;
			$goc_chua_tra_den_thoi_diem_dao_han=0;
        }
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
		$res['so_ngay_trong_ky_tinh_lai'] = $so_ngay_trong_ky_tinh_lai;

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

	public function get_phai_tra_bc_thu_hoi($code_contract, $start)
	{
		$startMonth = date('Y-m-01', strtotime($start)); // 2020-01-01
		$endMonth = date('Y-m-t', strtotime($start)); // 2020-01-31
		$start_date = strtotime(trim($startMonth) . ' 00:00:00');
		$end_date = strtotime(trim($endMonth) . ' 23:59:59');
		$tong_tien_phai_tra_den_thang = $this->temporary_plan_contract_model->tong_tien_phai_tra_den_thang($code_contract, $end_date);
		$contractDT = $this->findOne(array('code_contract' => $code_contract));
		$condition = array();
		$condition['date_pay'] = array(

			'$lt' => $start_date
		);
		$condition['status'] = 1;
		$condition['type'] = array('$in' => array(3, 4));
		$tong_tien_tt_den_thang_truoc = $this->transaction_model->sum_where($condition, '$total');
		$condition_l = array();
		$condition_l['date_pay'] = array(

			'$lte' => $end_date
		);
		$condition_l['status'] = 1;
		$condition_l['type'] = array('$in' => array(3, 4));
		$tong_tien_tt_den_thang = $this->transaction_model->sum_where($condition_l, '$total');
		$tong_phai_tra = $tong_tien_phai_tra_den_thang - $tong_tien_tt_den_thang_truoc;
		if ($tong_phai_tra < 0) $tong_phai_tra = 0;
		return array("tien_phai_tra" => $tong_phai_tra, "tien_thua" => $tong_tien_tt_den_thang - $tong_phai_tra, "thu_hoi_luy_ke" => $tong_tien_tt_den_thang);
	}
    // lấy phí phát sinh
	public function get_phi_phat_sinh($contractDB, $date_pay = "", $id_transaction = "")
	{
		$phi_phat_sinh = 0;
		$so_ngay_phat_sinh = 0;
		$so_ngay_qua_han = 0;
		$tong_tien_phai_tra_den_thoi_diem_dao_han = 0;
		$KiPhaiThanhToanXaNhat = $this->getKiPhaiThanhToanXaNhat($contractDB['code_contract']);
		$so_ngay_qua_han = intval(($date_pay - $KiPhaiThanhToanXaNhat) / (24 * 60 * 60));
		if ($so_ngay_qua_han > 0) {
			//$penalty=$this->get_phi_phat_cham_tra((string)$contractDB['_id'],  $date_pay);
			$tong_tien_phai_tra = $this->tong_tien_phai_tra_den_thoi_diem_dao_han($contractDB['code_contract'], $date_pay);
			$tong_tien_da_tra_den_thoi_diem_dao_han = 0;
			$condition = array();
			$condition['date_pay'] = array(
				'$lte' => $KiPhaiThanhToanXaNhat,

			);
			$condition['status'] = 1;
			$condition['type'] = 4;

			$condition['code_contract'] = $contractDB['code_contract'];
			$tong_tien_da_tra_den_thoi_diem_dao_han = $this->transaction_model->tong_tien_da_tra_den_thoi_diem_dao_han($condition);
			$tong_tien_phai_tra_den_thoi_diem_dao_han = $tong_tien_phai_tra - $tong_tien_da_tra_den_thoi_diem_dao_han;
			if ($tong_tien_phai_tra_den_thoi_diem_dao_han > 0) {


				$so_ngay_phat_sinh = floor(($date_pay - strtotime(date('Y-m-d', $KiPhaiThanhToanXaNhat) . ' 23:59:59')) / 86400);


				if ($so_ngay_phat_sinh < 0)
					$so_ngay_phat_sinh = 0;

                //lãi suất
				$percent_interest_customer = 0;
				//phí tư vấn
				$percent_advisory = 0;
				//phí thẩm định
				$percent_expertise = 0;
				if (!empty($contractDB['fee'])) {
					$fee = $contractDB['fee'];
					$percent_interest_customer = floatval($fee['percent_interest_customer']) / 100;
					$percent_advisory = floatval($fee['percent_advisory']) / 100;
					$percent_expertise = floatval($fee['percent_expertise']) / 100;

				}

				$phi_phat_sinh = ($tong_tien_phai_tra_den_thoi_diem_dao_han * ($percent_interest_customer + $percent_advisory + $percent_expertise) * $so_ngay_phat_sinh) / 30;

			}
		}

		return array(
			'phi_phat_sinh' => $phi_phat_sinh,
			'so_ngay_phat_sinh' => $so_ngay_phat_sinh,
			'so_tien_phat_sinh' => $tong_tien_phai_tra_den_thoi_diem_dao_han,
			'ky_thanh_toan_xa_nhat' => $KiPhaiThanhToanXaNhat,
			'so_ngay_qua_han' => $so_ngay_qua_han
		);

	}

   //lấy phí tất toán trước hạn
	public function get_phi_tat_toan_truoc_han($contractDB, $date_pay = "", $so_ngay_vay = 0, $so_ngay_vay_thuc_te = 0)
	{

		$ngay_giai_ngan = $contractDB['disbursement_date'];
		$timestamp_ngay_giai_ngan = $ngay_giai_ngan;
		//tổng ngày vay
		$number_day_loan = (int)$contractDB['loan_infor']['number_day_loan'];
		//hình thức vay
		$type_loan = $contractDB['loan_infor']['type_loan']['code'];
		$date_ngay_t = strtotime($this->config->item("date_t_apply"));
		$goc_da_tra_dao_han = $this->temporary_plan_contract_model->goc_da_tra_den_thoi_diem_dao_han($contractDB['code_contract'], $date_pay);
		$tong_tien_goc = $this->temporary_plan_contract_model->tong_tien_goc($contractDB['code_contract'], $date_pay);
		$get_goc_chua_tra_den_thoi_diem_dao_han = $tong_tien_goc - $goc_da_tra_dao_han;
		if ($so_ngay_vay_thuc_te == 0) {
			$so_ngay_vay = (isset($this->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['so_ngay_vay'])) ? (int)$this->get_phi_phat_cham_tra((string)$contractDB['_id'], $date_pay)['so_ngay_vay'] : (int)$contractDB['loan_infor']['number_day_loan'];
		}
		// phí tất toán trước hạn bảng contract trường fee
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
			$so_ngay_vay_thuc_te = $so_ngay_vay_thuc_te;
			$so_ngay_vay_thuc_te = ($so_ngay_vay_thuc_te < 0) ? 1 : $so_ngay_vay_thuc_te;
		}
		$phase =0;
		if($so_ngay_vay>0)
		$phase = $so_ngay_vay_thuc_te / $so_ngay_vay;
		//trước hoặc = 1/3
		if ($phase > 0 && $phase <= 1 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_1 * $get_goc_chua_tra_den_thoi_diem_dao_han;
		//trước hoặc = 2/3
		if ($phase > 1 / 3 && $phase <= 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_2 * $get_goc_chua_tra_den_thoi_diem_dao_han;
		//> 2/3
		if ($phase > 2 / 3) $phi_tat_toan_truoc_han = $percent_prepay_phase_3 * $get_goc_chua_tra_den_thoi_diem_dao_han;

        if (strtotime(date('Y-m-d',$date_pay). ' 00:00:00') < strtotime('2021-05-03 00:00:00') )
        {
			//cầm cố  tháng trước 3 ngày không tính
			if ($type_loan == 'CC') {
				if ($so_ngay_vay_thuc_te > $so_ngay_vay - 3) {
					$phi_tat_toan_truoc_han = 0;
				}
			}
			//khác cầm cố trước 7 ngày  không tính
			if ($type_loan != 'CC') {
				if ($so_ngay_vay_thuc_te > $so_ngay_vay - 7) {
					$phi_tat_toan_truoc_han = 0;
				}
			}
	   }
	   if ($so_ngay_vay_thuc_te >= $so_ngay_vay) {
					$phi_tat_toan_truoc_han = 0;
				}
		return $phi_tat_toan_truoc_han;
	}

    //lấy phí chậm trả
	public function get_phi_phat_cham_tra($id_contract, $date_pay)
	{

		$dataDB = $this->findOne(array("_id" => new \MongoDB\BSON\ObjectId($id_contract)));
		if (empty($dataDB)) {

			return array();
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
		$tong_penalty_con_lai = 0;
		$arr_penaty = array();
		if (!empty($contract)) {
			//lặp bảng lãi kỳ để lấy thông tin phí chậm trả còn lại , phải trả , đã trả
			foreach ($contract as $c) {
				$penalty_con_lai = 0;
				if ($c['status'] == 2) {
					$penalty_con_lai = (isset($c['tien_phi_cham_tra_1ky_con_lai'])) ? (float)$c['tien_phi_cham_tra_1ky_con_lai'] : 0;
					$penalty_da_tra = (isset($c['tien_phi_cham_tra_1ky_da_tra'])) ? (float)$c['tien_phi_cham_tra_1ky_da_tra'] : 0;
				}
                //tổng chậm trả còn lại
				$tong_penalty_con_lai += $penalty_con_lai;

				$current_day = strtotime(date('Y-m-d') . ' 23:59:59');
				$date_pay = ($date_pay == 0) ? $current_day : intval($date_pay);
				$penalty = 0;
				if ($c['status'] == 1 && ($date_pay > ($c['ngay_ky_tra'] - 5 * 24 * 3600))) { // 5 ngay tieu chuan

					$ngay_ky_tra = $c['ngay_ky_tra'];

				}
				//$c['disbursement_date'] = $dataDB['disbursement_date'];
				$c['amount_money'] = $dataDB['loan_infor']['amount_money'];
				$time_disbursement_date = $dataDB['disbursement_date'];
               //lấy tổng đã thanh toán
				$total_paid += $c['da_thanh_toan'];
				$ngay_ky_tra_ky_ht = strtotime(date('Y-m-d', $c['ngay_ky_tra'] . ' 23:59:59'));
				if ($c['ky_tra'] > 1) {
					//lấy kỳ trước
					$last_contract_tempo = $tempo->findOne(array('code_contract' => $dataDB['code_contract'], 'ky_tra' => $c['ky_tra'] - 1));
				}
				$ky_han_truoc = (isset($last_contract_tempo['ngay_ky_tra'])) ? $last_contract_tempo['ngay_ky_tra'] : '';
				//số ngày chậm trả
				$time_period = $this->tinh_so_ngay_trong_ky_cham_tra($c['ky_tra'], $time_disbursement_date, strtotime(date('Y-m-d', $c['ngay_ky_tra']) . ' 23:59:59'), $ky_han_truoc);
				//số ngày trong kỳ
				$ngay_trong_ky = $this->tinh_so_ngay_trong_ky($c['ky_tra'], $time_disbursement_date, $c['ngay_ky_tra'], $ky_han_truoc);
				$so_ngay_vay += $ngay_trong_ky;
				if ($ngay_ky_tra_ky_ht > 0) {

					$so_ngay_cham_tra_now = intval(($current_day - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

					$so_ngay_cham_tra_pay = intval(($date_pay - $ngay_ky_tra_ky_ht) / (24 * 60 * 60));

					$c['so_ngay_trong_ky'] = $ngay_trong_ky;
				}
				if ($ngay_ky_tra > 0) {
                   //tính phí chậm trả ngày hiện tại
					$penalty_now = $this->tinh_phi_phat($c['tien_tra_1_ky'], $dataDB['fee']['penalty_percent'], $dataDB['fee']['penalty_amount'], $so_ngay_cham_tra_now, $time_period);
					//tính phí chậm trả ngày chọn
					$penalty_pay = $this->tinh_phi_phat($c['tien_tra_1_ky'], $dataDB['fee']['penalty_percent'], $dataDB['fee']['penalty_amount'], $so_ngay_cham_tra_pay, $time_period);

					$penalty_pay_tt += $penalty_pay + $penalty_con_lai;

					$penalty_now_tt = $penalty_now + $penalty_con_lai;
                   //mảng chậm trả các kỳ
					$arr_penaty += [$c['ky_tra'] => ['so_tien' => $penalty_pay, 'so_ngay' => $so_ngay_cham_tra_pay]];

				}
			}
		}

		$dataDB['so_ngay_vay'] = $so_ngay_vay;
		//chậm trả ngày chọn
		$dataDB['penalty_pay'] = $penalty_pay_tt;
		//chậm trả ngày hiện tại
		$dataDB['penalty_now'] = $penalty_now_tt;
		//chậm trả còn lại
		$dataDB['tong_penalty_con_lai'] = $tong_penalty_con_lai;
		//mảng chậm trả các kỳ
		$dataDB['arr_penaty'] = $arr_penaty;
		return $dataDB;
	}
    //tính phí chậm trả
    //  $penaltyPercent % phí chậm trả
    // $penalty_amount số tiền chậm trả min
    //
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
    	public function sum_where_total($condtion = array(), $get)
	{
		$ops = array(
			array(
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
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}
	public function sum_where_total_mongo_read($condtion = array(), $get)
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get),
				),
			),
		);
		$data = $this->mongo_db_read->aggregate($this->collection, $ops)->toArray();
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}
	public function find_where_select($condition, $value)
	{
		return $this->mongo_db
			->order_by(["created_at" => "DESC"])
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

	public function get_contract_field_debt($select, $condition = array(), $limit = 30, $offset = 0)
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
		if (!empty($in)) {
			return $mongo->select($select)
				->order_by($order_by)
				->where_in('current_address.district', array_values($in))
				->where_not_in('user_debt', array_values($in_user))
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_contract_field_debt_total($select, $condition = array())
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
		if (!empty($in)) {
			return $mongo->select($select)
				->order_by($order_by)
				->where_in('current_address.district', array_values($in))
				->where_not_in('user_debt', array_values($in_user))
				->count($this->collection);
		}
	}

	public function find_select_log($condition)
	{
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

		$mongo = $mongo->where_in('status', array(8, 6, 5, 3, 7, 9, 10, 15, 16, 17, 18, 19));

		if (isset($condition['stores_ad'])) {
			$mongo = $mongo->where_in('store.name', array($condition['stores_ad']));
		}

		return $mongo
			->select(array("code_contract"))
			->order_by(array('created_at' => 'ASC'))
			->get($this->collection);
	}

	public function get_count_by_month_year($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (isset($condition['store_id'])) {
			$where["store.id"] = $condition['store_id'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['code_month_year_contract'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_month_year_contract']);
		}
		return $mongo->count($this->collection);
	}

	public function find_where_ksnb()
	{
		$mongo = $this->mongo_db;
		$where = array();

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$mongo = $mongo->where_in('status', array(17, 20, 33, 34));

		return $mongo->select(["code_contract_disbursement"])->get($this->collection);
	}

	public function where_in_count()
	{
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
		$condition['stores_ad'] = $condition;
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$mongo = $mongo->where_in('status', array(6));

		$mongo = $mongo->where_in('store.name', array($condition['stores_ad']));

		$mongo = $mongo->where_in('expertise_infor.exception1_value');


		return $mongo->count($this->collection);
	}

	public function find_select_hs($condition)
	{

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

		$mongo = $mongo->where_in('status', array(8, 6, 5, 3));


		return $mongo
			->order_by(array('created_at' => 'ASC'))
			->get($this->collection);

	}


	public function select_excel($condition)
	{
		$where = array();

		$mongo = $this->mongo_db;

		$mongo = $mongo->where_in('customer_infor.customer_phone_number', array($condition['phone']));

		return $mongo
			->select(array('customer_infor.customer_phone_number', 'status', 'created_at','loan_infor.amount_loan'))
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function findOne_identify($condition)
	{
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)->limit(1)->find_one($this->collection);
	}

	public function find_select_log_kt($condition)
	{
		$where = array();

		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		$mongo = $mongo->where_in('status', array(3, 6, 7, 9, 10, 15, 16, 17, 18, 19));
		$mongo = $mongo->where_in('status', array(3, 6, 7, 9, 10, 15, 16, 17, 18, 19, 41 , 42 , 21 , 22 , 23 , 24, 25 , 26 ,27 , 28,29,30,31,32,33,34,35,36));
		if (isset($condition['stores_ad'])) {
			$mongo = $mongo->where_in('store.name', array($condition['stores_ad']));
		}

		return $mongo
			->select(array("code_contract"))
			->order_by(array('created_at' => 'ASC'))
			->get($this->collection);
	}

	public function getFollowCurrentMonth_count($condition)
	{

		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$eq' => array((string)date('m', strtotime(date('Y-m-d', $condition['start']) . " -1 month")), '$month')
			);

		}
		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$eq' => array((string)date('Y', strtotime(date('Y-m-d', $condition['year']))), '$year')
			);
		}

		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				//lấy hợp đồng >17, và ngày giải ngân nhỏ hơn ngày cuối tháng
				['$match' =>
					[
						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),

					]
				],
				//lấy bảng lãi kỳ các tháng trước, ngày kỳ trả < ngày đầu tháng start
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract'
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
												'$gt' => array($condition['start'], '$ngay_ky_tra')
											)
										)
									)
								),
							),

						),
						'as' => "lai_ky_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của các tháng trước
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$gt' => array($condition['start'], '$date_pay')
											)
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của tháng hiện tại
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											)

										)
									)
								),
							),
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				// lấy bảng phiếu thu tất cả của hợp đồng
				['$lookup' =>
					[
						'from' => 'transaction',
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
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke",
					]
				],
				// lấy bảng lãi kỳ đến tháng Tn đến cuối tháng đang tìm
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
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)

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
									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1,
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
									'fee_delay_pay' => 1,
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
						'debt' => 1,
						'created_at' => 1,
						'status' => 1,

						"phai_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.tien_tra_1_ky'
						),
						"da_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.da_thanh_toan'
						),
						"lai_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.lai_ky'
						),

						"phi_tu_van_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tu_van',
						),
						"phi_tham_dinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tham_dinh',
						),
						"phi_tra_cham_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_delay_pay',
						),
						"phi_tra_truoc_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_finish_contract',
						),
						"phi_gia_han_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_extend',
						),
						"phi_phat_sinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.phi_phat_sinh'
						),

						//Đến thời điểm đáo hạn
						"tong_phai_thu_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.tien_tra_1_ky'
						),
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
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_delay_pay'
						),
						"phi_tra_truoc_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_finish_contract'
						),
						"phi_gia_han_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_gia_han'
						),
						"phi_phat_sinh_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_phat_sinh'
						),
						//phải thu tháng trước
						"so_tien_goc_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_goc_1ky_phai_tra'
						),
						"so_tien_lai_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_lai_1ky_phai_tra'
						),
						"so_tien_phi_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_phi_1ky_phai_tra'
						),
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

						"tong_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.total'
						),
						"tong_thu_hoi_luy_ke_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.total'
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

						"so_tien_phi_cham_tra_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_truoc_han_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.fee_finish_contract'
						),

						"type" => array(
							'$sum' => '$transaction_thang_hien_tai.type'
						),

						//Phí phát sinh
						"so_tien_phi_tra_cham_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_phat_sinh_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.tien_phi_phat_sinh_da_tra'
						),


					]
				],
				//lấy bảng lãi kỳ tháng hiện tại
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
												'$lte' => array($condition['start'], '$ngay_ky_tra')
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
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,

									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
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
		return count($arr);

	}

	public function getFollowCurrentMonth_view($condition, $limit = 30, $offset = 0)
	{

		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$eq' => array((string)date('m', strtotime(date('Y-m-d', $condition['start']) . " -1 month")), '$month')
			);
		}

		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$eq' => array((string)date('Y', strtotime(date('Y-m-d', $condition['year']))), '$year')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				//lấy hợp đồng >17, và ngày giải ngân nhỏ hơn ngày cuối tháng
				['$match' =>
					[
						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),
					],

				],
				array(
					'$limit' => $limit + $offset
				),
				array(
					'$skip' => (int)$offset
				),


				//lấy bảng lãi kỳ các tháng trước, ngày kỳ trả < ngày đầu tháng start
				['$lookup' =>
					[
						'from' => 'temporary_plan_contract',
						'let' => array(
							"code_contract" => '$code_contract'
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
												'$gt' => array($condition['start'], '$ngay_ky_tra')
											),
										)
									)
								),
							),

						),
						'as' => "lai_ky_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của các tháng trước
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$gt' => array($condition['start'], '$date_pay')
											)
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của tháng hiện tại
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											)

										)
									)
								),
							),
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				// lấy bảng phiếu thu tất cả của hợp đồng
				['$lookup' =>
					[
						'from' => 'transaction',
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
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke",
					]
				],
				// lấy bảng lãi kỳ đến tháng Tn đến cuối tháng đang tìm
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
												'$gte' => array($condition['end'], '$ngay_ky_tra')
											)

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
									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
									'tien_thua_tat_toan' => 1,
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
									'fee_delay_pay' => 1,
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
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gt' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,

								]
							)


						),
						'as' => "transaction_extend",
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
						'debt' => 1,
						'created_at' => 1,
						'status' => 1,

						"phai_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.tien_tra_1_ky'
						),
						"da_thu_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.da_thanh_toan'
						),
						"lai_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.lai_ky'
						),

						"phi_tu_van_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tu_van',
						),
						"phi_tham_dinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.phi_tham_dinh',
						),
						"phi_tra_cham_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_delay_pay',
						),
						"phi_tra_truoc_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_finish_contract',
						),
						"phi_gia_han_luy_ke_den_thang_Tn" => array(
							'$sum' => '$bang_lai_ky_den_thang_Tn.fee_extend',
						),
						"phi_phat_sinh_luy_ke_den_thang_Tn" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.phi_phat_sinh'
						),

						//Đến thời điểm đáo hạn
						"tong_phai_thu_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.tien_tra_1_ky'
						),
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
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_delay_pay'
						),
						"phi_tra_truoc_den_thoi_diem_dao_han" => array(
							'$sum' => '$bang_lai_ky_den_thoi_diem_dao_han.fee_finish_contract'
						),
						"phi_gia_han_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_gia_han'
						),
						"phi_phat_sinh_den_thoi_diem_dao_han" => array(
							'$sum' => '$transaction_luy_ke.phi_phat_sinh'
						),
						//phải thu tháng trước
						"so_tien_goc_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_goc_1ky_phai_tra'
						),
						"so_tien_lai_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_lai_1ky_phai_tra'
						),
						"so_tien_phi_luy_ke_thang_truoc" => array(
							'$sum' => '$lai_ky_luy_ke_thang_truoc.tien_phi_1ky_phai_tra'
						),
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

						"tong_thu_hoi_luy_ke_thang_truoc" => array(
							'$sum' => '$transaction_luy_ke_thang_truoc.total'
						),
						"tong_thu_hoi_luy_ke_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.total'
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

						"so_tien_phi_cham_tra_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_truoc_han_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.fee_finish_contract'
						),

						"type" => array(
							'$sum' => '$transaction_thang_hien_tai.type'
						),

						//Phí phát sinh
						"so_tien_phi_tra_cham_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_tat_toan_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.fee_finish_contract'
						),
						"so_tien_phi_gia_han_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.so_tien_phi_gia_han_da_tra'
						),
						"so_tien_phi_phat_sinh_da_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.tien_phi_phat_sinh_da_tra'
						),

						//Gia Han
						"tien_lai_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
						),
						"tien_phi_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_phi_da_tra'
						),
						"tien_goc_1thang_da_tra" => array(
							'$sum' => '$transaction_extend.so_tien_goc_da_tra'
						),
						"tong_thu_hoi_gia_han" => array(
							'$sum' => '$transaction_extend.total'
						),

					]
				],
				//lấy bảng lãi kỳ tháng hiện tại
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
												'$lte' => array($condition['start'], '$ngay_ky_tra')
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
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,

									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
//									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
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


	public function revoke_loan_1($condition)
	{

		$compareLte = array();
		if (!empty($condition['start'])) {
			$compareLte = array(
				'$eq' => array((string)date('m', strtotime(date('Y-m-d', $condition['start']) . " -1 month")), '$month')
			);
		}

		$compareGte = array();
		if (!empty($condition['end'])) {
			$compareGte = array(
				'$eq' => array((string)date('Y', strtotime(date('Y-m-d', $condition['year']))), '$year')
			);
		}
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				//lấy hợp đồng >17, và ngày giải ngân nhỏ hơn ngày cuối tháng
				['$match' =>
					[
						'status' => array('$gte' => 17),
						'disbursement_date' => array('$lte' => $condition['end']),

					],

				],

				// lấy bảng phiếu thu của các tháng trước
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$gt' => array($condition['start'], '$date_pay')
											)
										)
									)
								),
							),

						),
						'as' => "transaction_luy_ke_thang_truoc",
					]
				],
				// lấy bảng phiếu thu của tháng hiện tại
				['$lookup' =>
					[
						'from' => 'transaction',
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
												'$eq' => array(1, '$status')
											),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gte' => array($condition['end'], '$date_pay')
											)

										)
									)
								),
							),
						),
						'as' => "transaction_thang_hien_tai",
					]
				],
				// lấy bảng phiếu thu tất cả của hợp đồng
				['$lookup' =>
					[
						'from' => 'transaction',
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
									'fee_delay_pay' => 1,
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
				['$lookup' =>
					[
						'from' => 'transaction_extend',
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
											// array(
											// 	'$eq' => array(1, '$status')
											// ),
											array(
												'$lte' => array($condition['start'], '$date_pay')
											),
											array(
												'$gt' => array($condition['end'], '$date_pay')
											),
										)
									)
								),
							),

							array(
								'$project' => [
									'so_tien_goc_da_tra' => 1,
									'so_tien_lai_da_tra' => 1,
									'so_tien_phi_da_tra' => 1,
									'so_tien_phi_gia_han_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'tien_thua_tat_toan' => 1,

								]
							)


						),
						'as' => "transaction_extend",
					]
				],

				['$project' =>
					[
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

						"code_transaction_bank" => 1,


						//Start
						"tong_thu_hoi_luy_ke_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.total'
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

						"so_tien_phi_cham_tra_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.so_tien_phi_cham_tra_da_tra'
						),
						"so_tien_phi_truoc_han_da_thu_hoi_thang_hien_tai" => array(
							'$sum' => '$transaction_thang_hien_tai.fee_finish_contract'
						),

						//Tổng thu hồi lũy kế
						"tong_thu_hoi_luy_ke" => array(
							'$sum' => '$transaction_luy_ke.total'
						),


						//Gia Han
						"tien_lai_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_lai_da_tra'
						),
						"tien_phi_1thang_da_tra_tien_thua" => array(
							'$sum' => '$transaction_extend.so_tien_phi_da_tra'
						),
						"tien_goc_1thang_da_tra" => array(
							'$sum' => '$transaction_extend.so_tien_goc_da_tra'
						),
					]
				],
				//lấy bảng lãi kỳ tháng hiện tại
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
												'$lte' => array($condition['start'], '$ngay_ky_tra')
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
									'lai_ky' => 1,
									'phi_tu_van' => 1,
									'phi_tham_dinh' => 1,
									'fee_finish_contract' => 1,
									'fee_extend' => 1,

									'fee_delay_pay' => 1,
									'ngay_ky_tra' => 1,
									'ki_khach_hang_tat_toan' => 1,
									'so_tien_goc_da_tra_tat_toan' => 1,
									'so_tien_lai_da_tra_tat_toan' => 1,
									'so_tien_phi_da_tra_tat_toan' => 1,
//									'fee_finish_contract' => 1,
									'tien_phi_phat_sinh_da_tra' => 1,
									'so_tien_phi_cham_tra_da_tra' => 1,
									'tien_thua_tat_toan' => 1
								]
							)
						),
						'as' => "bang_lai_ky",
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

	public function getContractUser($condition, $limit = 30, $offset = 0)
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
		}

		if (!empty($condition['amount_money'])) {

			$where['amount_money'] = ['$gte' => $condition['amount_money']];
		}

		if (!empty($condition['type_loan'])) {
			$where['loan_infor.type_loan.code'] = $condition['type_loan'];
		}
		if (!empty($condition['chan_bao_hiem'])) {
			$where['chan_bao_hiem'] = $condition['chan_bao_hiem'];
		}
		if (!empty($condition['type_property'])) {
			$where['loan_infor.type_property.code'] = $condition['type_property'];
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$mongo = $mongo->set_where(['status'=>['$nin'=>[19,33,34,40]]]);
		}
		return $mongo->order_by($order_by)
			->select(array(), array('image_accurecy'))
			->where_in('store.id', array_values($condition['stores']))
			->where_not_in('status', [35, 36])
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function getTotalContractByUser($condition)
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
		}

		if (!empty($condition['amount_money'])) {

			$where['amount_money'] = ['$gte' => $condition['amount_money']];
		}
		if (!empty($condition['type_loan'])) {
			$where['loan_infor.type_loan.code'] = $condition['type_loan'];
		}
		if (!empty($condition['type_property'])) {
			$where['loan_infor.type_property.code'] = $condition['type_property'];
		}
		if (!empty($condition['chan_bao_hiem'])) {
			$where['chan_bao_hiem'] = $condition['chan_bao_hiem'];
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$mongo = $mongo->set_where(['status'=>['$nin'=>[19,33,34,40]]]);
		}
		return $mongo
			->where_in('store.id', array_values($condition['stores']))
			->where_not_in('status', [35, 36])
			->count($this->collection);
	}

	public function thong_ke_cac_chi_so_hd($condition)
	{
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
		}
		$where['status'] = ['$nin' => [33, 34, 35, 36]];
		$where['store.id'] = ['$in' => array_values($condition['stores'])];

		$tong_tien_vay = $this->tong_tien_vay($where);
		$tong_tien_goc_con_lai = $this->tong_tien_goc_con_lai($where);
		$data['tong_tien_vay'] = $tong_tien_vay;
		$data['tong_tien_goc_con_lai'] = $tong_tien_goc_con_lai;
		return $data;
	}


	public function tong_tien_vay($condtion = array())
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

	public function tong_tien_goc_con_lai($condtion = array())
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => array('$toLong' => '$debt.tong_tien_goc_con')),
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

	public function tong_hd_qua_han($condition)
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
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

		return $mongo
			->where_in('store.id', array_values($condition['stores']))
			->where_not_in('status', [19, 33, 34, 35, 36])
			->where_gt('debt.so_ngay_cham_tra', 0)
			->count($this->collection);
	}

	public function tong_hd_can_nhac_no($condition)
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
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

		return $mongo
			->where_in('store.id', array_values($condition['stores']))
			->where_not_in('status', [19, 33, 34, 35, 36])
			->where_gte('debt.so_ngay_cham_tra', -5)
			->where_lte('debt.so_ngay_cham_tra', 3)
			->count($this->collection);

	}

	public function getAllContractByUser($condition)
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
		if (isset($condition['fdebt']) && isset($condition['tdebt'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fdebt'],
				'$lte' => $condition['tdebt']
			);
			unset($condition['fdebt']);
			unset($condition['tdebt']);
		}
		if (!empty($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		} else {
			$where['status'] = ['$gte' => 17];
		}
		if (!empty($condition['type_loan'])) {
			$where['loan_infor.type_loan.code'] = $condition['type_loan'];
		}
		if (!empty($condition['type_property'])) {
			$where['loan_infor.type_property.code'] = $condition['type_property'];
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
		return $mongo
			->where_in('store.id', array_values($condition['stores']))
			->where_not_in('status', [18, 20, 22, 22, 23, 24, 25, 26, 27, 28, 30, 32, 35, 36])
			->get($this->collection);
	}

	public function getCountByStatus($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if ($condition['ngaygiaingan'] == '1') {
			if (isset($condition['start']) && isset($condition['end'])) {
				$where['created_at'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
				unset($condition['start']);
				unset($condition['end']);
			}
		} else {
			if (isset($condition['start']) && isset($condition['end'])) {
				$where['disbursement_date'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);

				unset($condition['start']);
				unset($condition['end']);
			}

		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "GH") {
			$where['status'] = array('$in' => [11, 13, 21, 25, 29, 30, 26, 33, 17, 19]);
			$where['count_extension'] = array('$exists' => true);
		}
		if (isset($condition['type_contract']) && $condition['type_contract'] == "CC") {
			$where['status'] = array('$in' => [12, 14, 23, 24, 27, 28, 31, 32, 34, 17, 19]);
			$where['count_structure'] = array('$exists' => true);
		}
		if (isset($condition['property'])) {
			$where['loan_infor.type_property.code'] = $condition['property'];
		}
		if (isset($condition['search_htv'])) {
			$where['loan_infor.type_loan.code'] = $condition['search_htv'];
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
		if (!empty($condition['code_contract'])) {
			$mongo = $mongo->like("code_contract", $condition['code_contract']);
		}

		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}

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
			    ->select(array(), array('image_accurecy'))
				->where_in('store.id', $in)
				->where_in('status', [$condition['find_status']])
				->count($this->collection);
		} else {
			return $mongo->order_by($order_by)
			    ->select(array(), array('image_accurecy'))
				->where_in('status', [$condition['find_status']])
				->count($this->collection);
		}
	}

	public function getCountByRole_200($condition = [])
	{

		$order_by = ["created_at" => "DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		$where['customer_infor.img_file_presenter_cmt'] = array('$exists' => true);

		$where['status'] = 17;

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
		if (!empty($condition['customer_identify'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['customer_identify']);
		}

		return $mongo->order_by($order_by)
			->count($this->collection);

	}

	public function getByRole_200($condition, $limit = 30, $offset = 0)
	{

		$order_by = ["created_at" => "DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		$where['customer_infor.img_file_presenter_cmt'] = array('$exists' => true);

		$where['status'] = 17;

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
		if (!empty($condition['customer_identify'])) {
			$mongo = $mongo->like("customer_infor.customer_identify", $condition['customer_identify']);
		}

		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function getContractLiquidations($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['liquidation_info.created_at_liquidations' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		$where['status'] = array('$in'=>[19,40]);
		$where['liquidation_info.status_liquidations'] = array('$exists'=>true);
		$mongo = $mongo->or_where(array("customer_infor.customer_identify" => $condition["customer_identify"], "customer_infor.customer_identify_old" => $condition["customer_identify"]));
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

	public function getContractByTimeAllLiquidations($searchLike, $condition)
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
		$where['status'] = array('$in'=>[19,40]);
		$where['liquidation_info.status_liquidations'] = array('$exists'=>true);
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		if (isset($condition['bucket'])) {
			$where['bucket'] = $condition['bucket'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
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



	public function find_where_sort_debt($condition)
	{
		return $this->mongo_db
			->where([
				'$or' => [
					['customer_infor.customer_identify' => $condition],
					['customer_infor.customer_identify_old' => $condition]
				],
				'status' => [
					'$in' => list_array_trang_thai_dang_vay()
				]
			])
			->order_by(['debt.so_ngay_cham_tra' => 'DESC'])
			->get($this->collection);
	}

	public function find_where_day($condition)
	{
		return $this->mongo_db
			->select(["status", "code_contract_disbursement", "disbursement_date","created_by"])
			->get_where($this->collection, $condition);
	}

	public function getContractByTimeExcel($searchLike, $condition, $limit = 30, $offset = 0)
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
		if (isset($condition['fBucket']) && isset($condition['tBucket'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fBucket'],
				'$lte' => $condition['tBucket']
			);
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
		// if (isset($condition['store'])) {
		// 	$where['store.id'] = $condition['store'];
		// }
//		if (isset($condition['bucket'])) {
//			$where['bucket'] = $condition['bucket'];
//		}
		if (isset($condition['investor_code'])) {
			$where['investor_code'] = $condition['investor_code'];
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
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
		//fix xuất excel
		if(isset($condition['store']) ) {
			$mongo = $mongo->where_in('store.id', array($condition['store']));
		} else if (!isset($condition['store']) && isset($condition['store_vung'])) {
			$mongo = $mongo->where_in('store.id', $condition['store_vung']);
		} else {
			// do nothing
		}

			return $mongo->order_by($order_by)
				->select(array(), array('image_accurecy'))
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		// }

	}

	public function getContractByTimeAllExcel($searchLike, $condition)
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
		if (isset($condition['fBucket']) && isset($condition['tBucket'])) {
			$where['debt.so_ngay_cham_tra'] = array(
				'$gte' => $condition['fBucket'],
				'$lte' => $condition['tBucket']
			);
		}
		if (isset($condition['status'])) {
			$where['status'] = (int)$condition['status'];
		}
		if (isset($condition['status_disbursement'])) {
			$where['status_disbursement'] = (int)$condition['status_disbursement'];
		}
//		if (isset($condition['bucket'])) {
//			$where['bucket'] = $condition['bucket'];
//		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
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
		if(!empty($condition['store_vung'])){
			$in = $condition['store_vung'];
		}

		if(!empty($in)){
			return $mongo->where_in('store.id', $in)->order_by($order_by)->count($this->collection);
		}else{
			return $mongo->order_by($order_by)->count($this->collection);
		}

	}

	public function getContractPaginationByRole_limit($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

//		$where['is_customer_code'] = array('$exists' => false);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

			return $mongo->order_by($order_by)
//				->limit($limit)
//				->offset($offset)
				->select(["_id","customer_infor.customer_name","customer_infor.customer_phone_number","customer_infor.customer_identify", "status"])
				->get($this->collection);

	}

	public function find_where_cmt($condition)
	{
		return $this->mongo_db
			->select(["status"])
			->get_where($this->collection, $condition);
	}

//	public function find_where_mhd_cron($condition = [],$limit=10,$offset = 0)

	public function find_where_mhd_cron($condition = [])
	{
		$order_by = ['created_at' => 'DESC'];

		$mongo = $this->mongo_db;

		if (isset($condition['start'])) {
			$where['created_at'] = array(
				'$lte' => $condition['start'],
			);
			unset($condition['start']);
		}

		$where['status'] = array('$in' => [17, 19, 20, 21, 22, 23, 24, 25, 26, 27,28,29,30,31,32,33,34,37,38,39,40,41,42]);


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

			return $mongo
//				->limit($limit)
//				->offset($offset)
				->order_by($order_by)
				->select(["code_contract_disbursement","created_by","store","created_at"])
				->get($this->collection);


	}

	public function get_current_month_date_number_code_contract_d($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (isset($condition['store_id'])) {
			$where["store.id"] = $condition['store_id'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['endCodeContractD'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['endCodeContractD']);
		}
		return $mongo->get($this->collection);
	}

	public function find_contract_notFeeId(){

		$mongo = $this->mongo_db;
		$where = array();

		$where['disbursement_date'] = array('$exists' => true);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->select(["code_contract","disbursement_date","loan_infor","fee_id"])
			->get($this->collection);
	}

	/*
	* Find contract list by condition where or
	* @param Array $conditions
	* @param Array $selectOption
	* @return Collection
	*/
	public function find_where_or($conditions, $selectOption)
	{
		return $this->mongo_db
			->where([
				'$or' => $conditions,
				'status' => [
					'$in' => list_array_trang_thai_dang_vay()
				],
				'code_contract_parent_gh' => ['$exists' => false],
				'code_contract_parent_cc' => ['$exists' => false],
			])
			->select($selectOption)
			->order_by(['debt.so_ngay_cham_tra' => 'DESC'])
			->get($this->collection);
	}

	public function find_by_select($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['customer_infor.customer_phone_number'] = $condition['phone_number'];
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->select(['status','loan_infor.amount_loan','loan_infor.type_loan','loan_infor.loan_product'])
			->order_by(['created_at' => 'DESC'])
			->get($this->collection);
	}

	public function findOne_storeId($condition){
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)
			->select(["store.id"])
			->find_one($this->collection);

	}


	public function find_where_qlhs($condition){

		$mongo = $this->mongo_db;
		$where = array();

		$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

//		if (!empty($condition['code_contract_disbursement'])) {
//			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
//		}

		return $mongo->get($this->collection);

	}

	public function findOne_where($condition){


		$mongo = $this->mongo_db;
		$where = array();

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['customer_phone_number'])) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition['customer_phone_number']);
		}
		return $mongo->order_by(array('created_at' => 'DESC'))->select(["status","loan_infor.amount_money"])->limit(1)->get($this->collection);
	}


	public function export_xadan($condition = [], $limit = 30, $offset = 0){

		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['status'] = array('$in'=>[11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42]);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['store'])) {
			$mongo = $mongo->or_where(array('store.id'=> $condition['store'], 'follow_contract' => ['$in' => $condition['follow_contract'] ]));
		}


		return $mongo
			->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function export_xadan_count($condition){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['status'] = array('$in'=>[11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42]);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['store'])) {
			$mongo = $mongo->or_where(array('store.id'=> $condition['store'], 'follow_contract' => ['$in' => $condition['follow_contract'] ]));
		}


		return $mongo
			->order_by($order_by)
			->count($this->collection);
	}

	public function export_xadan_excel($condition){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['status'] = array('$in'=>[11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42]);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['store'])) {
			$mongo = $mongo->or_where(array('store.id'=> $condition['store'], 'follow_contract' => ['$in' => $condition['follow_contract'] ]));
		}


		return $mongo
			->order_by($order_by)
			->get($this->collection);
	}

	public function findOne_code_contract($condition){


		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		$where['status'] = 19;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition)) {
			$mongo = $mongo->like("customer_infor.customer_phone_number", $condition);
		}

		return $mongo
			->select(['code_contract'])
			->order_by($order_by)
			->get($this->collection);

	}

	public function exportAllContract($condition){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['code_contract_parent_cc'] = array('$exists' => false);
		$where['code_contract_parent_gh'] = array('$exists' => false);
		$where['tat_toan_gh'] = array('$exists' => false);

		$where['status'] = ['$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,33,34]];

		$where['debt.so_ngay_cham_tra'] = ['$lte'=>10];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->select(['code_contract','code_contract_disbursement','store.name','debt.so_ngay_cham_tra','debt.tong_tien_goc_con','created_by','disbursement_date'])
			->order_by($order_by)
			->get($this->collection);
	}

	public function find_cron_du_no(){

		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		$where['type_gh'] = "origin";

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->select(['code_contract_child_gh','_id'])
			->order_by($order_by)
			->get($this->collection);

	}

	public function findOne_code($condition){
		return $this->mongo_db->select(['status'])->order_by(array('created_at' => 'DESC'))->where($condition)->find_one($this->collection);
	}

	public function findOne_select($condition)
	{
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)->select(['customer_infor.customer_name'])->find_one($this->collection);
	}

	public function find_where_by_time()
	{
		$order_by = ['created_by' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 00:00 01/12/2021 (thời điểm golive tính năng HĐ điện tử Megadoc
		$CI = &get_instance();
		$CI->load->config('config');
		$time_start_megadoc = $CI->config->item('TIME_START_MEGADOC');
		$where['created_at'] = array('$gte' => (intval(strtotime($time_start_megadoc))));
		$where['status'] = 6;
		return $mongo->set_where($where)
			->get($this->collection);

	}

	public function findOne_select_thn($code_contract)
	{
		$mongo = $this->mongo_db;
		$where = array();

		$where["code_contract"] = $code_contract;

		$where["status"] = 19;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->select(["loan_infor.type_property"])->get($this->collection);
	}
	public function findOne_select_debt($condition)
	{
		return $this->mongo_db->order_by(array('created_at' => 'DESC'))->where($condition)->select(['debt','status'])->find_one($this->collection);
	}

	public function find_one_unselect($condition, $unselect)
	{
		return $this->mongo_db
			->select(array(), $unselect)
			->where($condition)
			->find_one($this->collection);
	}

	public function find_where_recording($customer_phone_number)
	{
		$mongo = $this->mongo_db;
		$where = array();

		$where["customer_infor.customer_phone_number"] = $customer_phone_number;


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->select(["customer_infor.customer_name",'code_contract_disbursement'])->get($this->collection);
	}

	public function find_id_contract(){
		$mongo = $this->mongo_db;

		return $mongo->select(["_id"])
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function count_status($condition){

		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (isset($condition['status'])) {
			$where['status'] = ['$in' => $condition['status']];
		}

		if (isset($condition['type_property'])) {
			$where['loan_infor.type_property.code'] = $condition['type_property'];
		}

		if (isset($condition['debt'])) {
			$where['debt.so_ngay_cham_tra'] = ['$gt' => 90];
		}

		if (isset($condition['province'])) {
			$where['houseHold_address.province'] = $condition['province'];
		}

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->count($this->collection);

	}

	public function find_where_debt($condition){
		$mongo = $this->mongo_db;
		$where = array();

		$where['status'] = ['$in' => list_array_trang_thai_dang_vay()];
		$where['debt.so_ngay_cham_tra'] = ['$gt' => 90];

		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}



		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->select(["code_contract","debt","status"])->get($this->collection);
	}

	public function get_debt_detail($condition){
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$where['status'] = ['$in' => list_array_trang_thai_dang_vay()];

		$where['store.id'] = $condition['store'];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->select(["code_contract","debt","status","code_contract_disbursement",'loan_infor','store','customer_infor','houseHold_address','current_address','job_infor','disbursement_date'])->get($this->collection);
	}

	public function find_where_telesale($customer_phone_number, $condition)
	{
		$mongo = $this->mongo_db;
		$where = array();

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		$where['customer_infor.customer_phone_number'] = $customer_phone_number;

		$where['status'] = ['$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34]];

		$where['type_cc'] = array('$exists' => false);
		$where['type_gh'] = array('$exists' => false);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		return $mongo
			->select(['status','loan_infor.amount_money','code_contract','customer_infor.customer_name'])
			->get($this->collection);
	}

	public function find_where_telesale_name($customer_name, $condition){
		$mongo = $this->mongo_db;
		$where = array();

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['disbursement_date'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		$where['status'] = ['$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34]];

		$where['type_cc'] = array('$exists' => false);
		$where['type_gh'] = array('$exists' => false);

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$mongo = $mongo->like("customer_infor.customer_name", $customer_name);

		return $mongo
			->select(['status','loan_infor.amount_money','code_contract','customer_infor.customer_name'])
			->get($this->collection);
	}

	public function findOne_copy($customer_identify)
	{
		$mongo = $this->mongo_db;
		$where = array();

		$where['customer_infor.customer_identify'] = $customer_identify;

		$where['status'] = ['$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,19,33,34]];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->order_by(array("created_at" => "DESC"))
			->select(['code_contract'])
			->limit(1)
			->get($this->collection);
	}

	public function find_where_topup_pgd($customer_phone_number){

		$mongo = $this->mongo_db;
		$where = array();

		$where['status'] = ['$in' => [11,12,13,14,17,18,20,21,22,23,24,25,26,27,28,29,30,31,32,37,38,39,41,42,33,34]];

		$where['customer_infor.customer_phone_number'] = $customer_phone_number;

		$where['current_address.form_residence'] = "Thường trú";

		$where['loan_infor.type_property.code'] = ['$in' => ['OTO','XM']];

		$where['loan_infor.type_interest'] = "1";

		$where['debt.so_ngay_cham_tra'] = ['$lte' => 60];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo
			->order_by(array("created_at" => "DESC"))
			->select(['code_contract'])
			->limit(1)
			->get($this->collection);

	}

}

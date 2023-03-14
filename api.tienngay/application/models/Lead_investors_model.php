<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Lead_investors_model extends CI_Model
{

	private $collection = 'lead_investors';
	private $manager;

	public function __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->find_one($this->collection);
	}

	public function findOneASC()
	{
		return $this->mongo_db->order_by(array('created_at' => 'ASC'))->find_one($this->collection);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}

	public function findOne_langding($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
	}

	public function findOne_create($condition, $in)
	{
		return $this->mongo_db->where($condition)->where_in('status_sale', $in)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
	}

	public function count($condition = array())
	{
		if (!empty($condition)) {
			return $this->mongo_db->where($condition)->count($this->collection);
		} else {
			return $this->mongo_db->count($this->collection);
		}
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function find_where_not_in($condition, $field = "", $in = "")
	{
		if (empty($in)) {
			return $this->mongo_db
				->order_by(array('created_at' => 'DESC'))
				->get_where($this->collection, $condition);
		} else {
			return $this->mongo_db
				->order_by(array('created_at' => 'DESC'))
				->where_not_in($field, $in)
				->get_where($this->collection, $condition);
		}
	}

	public function getByRole($condition = array(), $limit = 30, $offset = 0)
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
		//  if(isset($condition['start']) && !isset($condition['end'])){
		//    $where['created_at'] = array(

		//        '$lte' => $condition['start']
		//    );
		//    unset($condition['start']);

		// }
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', array($condition['code_store']));
		}
		if (isset($condition['isExport'])) {
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function mkt_lead_cancel_static($condition = array())
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
		//  if(isset($condition['start']) && !isset($condition['end'])){
		//    $where['created_at'] = array(

		//        '$lte' => $condition['start']
		//    );
		//    unset($condition['start']);

		// }
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		return $mongo->order_by($order_by)
			->get($this->collection);

	}

	public function mkt_report_general($condition = array())
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
		//  if(isset($condition['start']) && !isset($condition['end'])){
		//    $where['created_at'] = array(

		//        '$lte' => $condition['start']
		//    );
		//    unset($condition['start']);

		// }
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		if (isset($condition['isExport'])) {
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
		return $mongo->order_by($order_by)
			->get($this->collection);

	}

	public function getByRole_total($condition)
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
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		return $mongo->order_by($order_by)
			->count($this->collection);
	}

	public function getByRole_pgd_excel($condition = array())
	{
		$order_by = ['office_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['office_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		//  if(isset($condition['start']) && !isset($condition['end'])){
		//    $where['created_at'] = array(

		//        '$lte' => $condition['start']
		//    );
		//    unset($condition['start']);

		// }
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}

		return $mongo->order_by($order_by)
			->get($this->collection);

	}

	public function getByRole_pgd($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['office_at' => 'DESC'];
//		$order_by = ['updated_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['office_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['status_pgd'])) {
			$where['status_pgd'] = $condition['status_pgd'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		if (isset($condition['id_PDG'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['id_PDG']);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (!empty($condition['phone_number'])) {
			$mongo = $mongo->like("phone_number", $condition['phone_number']);
		}
		if (isset($condition['total']) && $condition['total']) {
			return $mongo->where_in('status_sale', array('2'))
				->order_by($order_by)
				->count($this->collection);
		} else {
			return $mongo->where_in('status_sale', array('2'))->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}


	}

	public function getByRole_mkt_pgd($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['office_at' => 'DESC'];
//		$order_by = ['updated_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['office_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['status_pgd'])) {
			$where['status_pgd'] = $condition['status_pgd'];
		}
		if (isset($condition['status_contract'])) {
			$where['status_pgd'] = $condition['status_contract'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		if (isset($condition['id_PDG'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['id_PDG']);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}
		if (!empty($condition['phone_number'])) {
			$mongo = $mongo->like("phone_number", $condition['phone_number']);
		}
		if (isset($condition['total']) && $condition['total']) {
			return $mongo->where_in('status_sale', array('2'))
				->order_by($order_by)
				->count($this->collection);
		} else {
			return $mongo->where_in('status_sale', array('2'))->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}


	}

	public function getByRole_mkt($condition = array())
	{
		$order_by = ['office_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['office_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}
		if (isset($condition['status_sale_fist'])) {
			$where['status_sale'] = $condition['status_sale_fist'];
		}
		if (isset($condition['status_sale_last'])) {
			$where['status_sale'] = $condition['status_sale_last'];
		}


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['utm_source'])) {
			$mongo = $mongo->like("utm_source", $condition['utm_source']);
		}
		if (!empty($condition['utm_campaign'])) {
			$mongo = $mongo->like("utm_campaign", $condition['utm_campaign']);
		}

		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('id_PDG', $condition['code_store']);
		}
		return $mongo->where_in('status_sale', array('2', '30'))->order_by($order_by)
			->get($this->collection);

	}

	public function getLead_pt($condition = array(), $limit = 30, $offset = 0)
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
		if (isset($condition['status_sale'])) {
			$where['status_sale'] = $condition['status_sale'];
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (isset($condition['source'])) {
			$where['source'] = $condition['source'];
		}
		if (isset($condition['cskh'])) {
			$where['cskh'] = $condition['cskh'];
		}
		if (isset($condition['tab2'])) {
			$where['cskh'] = array('$exists' => false);
			$mongo = $mongo->where_in('status_sale', array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'));
		}
		if (isset($condition['tab3'])) {
			// if(in_array('telesales',  $condition['tab3']['groupRoles']) && !in_array('tbp-cskh',  $condition['tab3']['groupRoles']))
			// {
			$where['cskh'] = $condition['tab3']['email'];
			// }
		}
		if (isset($condition['tab6'])) {
			if (in_array('telesales', $condition['tab6']['groupRoles']) && !in_array('tbp-cskh', $condition['tab6']['groupRoles'])) {
				$where['cskh'] = $condition['tab6']['email'];
			}
		}
		if (isset($condition['tab4'])) {
			$where['status_sale'] = '1';
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['tab5'])) {
			$mongo = $mongo->where_in('status_sale', array('2'));
		}
		if (isset($condition['tab3'])) {
			$mongo = $mongo->where_in('status_sale', array('1', '2', '5', '6', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'));
		}
		if (isset($condition['tab1'])) {
			$mongo = $mongo->where_in('status_sale', array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'));
		}
		if (isset($condition['tab2'])) {
			$mongo = $mongo->where_in('status_sale', array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'));
		}
		if (isset($condition['tab6'])) {
			$mongo = $mongo->where_in('status_sale', array('5', '10', '11', '12', '13', '14', '15', '16', '17', '18'));
		}
		if (isset($condition['tab11'])) {
			$mongo = $mongo->where_in('status_pgd', ['16']);
		}
		if (isset($condition['tab10'])) {
			$mongo = $mongo->where_in('status_pgd', ['8']);
		}
		if (!empty($condition['email_cskh'])) {
			$mongo = $mongo->where_in('cskh', [$condition['email_cskh']]);
		}
		if (!empty($condition['fullname'])) {
			$mongo = $mongo->like("fullname", $condition['fullname']);
		}
		if (isset($condition['sdt'])) {
			$mongo = $mongo->like("phone_number", $condition['sdt']);
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

	public function getLead_test($condition = array(), $limit = 30, $offset = 0)
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

		if (isset($condition['sdt'])) {
			$mongo = $mongo->like("phone_number", $condition['sdt']);
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}

		if (!empty($condition['fullname'])) {
			$mongo = $mongo->like("fullname", $condition['fullname']);
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}

	}


	public function getAllLeadExport($condition = array())
	{

		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'_id' => ['$toString' => '$_id'],
						'cskh' => 1,
						'created_at' => 1,
						'source' => 1,
						'utm_source' => 1,
						'fullname' => 1,
						'phone_number' => 1,
						'status_sale' => 1,
						'reason_cancel' => 1,
						'id_PDG' => 1,
						'hk_district' => 1,
						'hk_province' => 1,
						'hk_ward' => 1,
						'ns_district' => 1,
						'ns_province' => 1,
						'ns_ward' => 1,
					]
				],
				['$lookup' =>
					[
						'from' => 'contract',
						'localField' => "_id",
						'foreignField' => "customer_infor.id_lead",
						'as' => "contract_info"
					],
				],
				['$project' =>
					[
						'_id' => ['$toString' => '$_id'],
						'cskh' => 1,
						'created_at' => 1,
						'source' => 1,
						'utm_source' => 1,
						'fullname' => 1,
						'phone_number' => 1,
						'status_sale' => 1,
						'reason_cancel' => 1,
						'id_PDG' => 1,
						'hk_district' => 1,
						'hk_province' => 1,
						'hk_ward' => 1,
						'ns_district' => 1,
						'ns_province' => 1,
						'ns_ward' => 1,
						'contract_info.status' => 1,
						'contract_info.loan_infor.amount_loan' => 1

					]
				],
			],
			'cursor' => new stdClass,
		];
		$match = array();
		if (!empty($condition)) {
			if (!empty($condition['start']) && !empty($condition['end'])) {
				$match['$match']['created_at'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
			}
			array_push($conditions['pipeline'], $match);
		}
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

	public function getListLeadMKTExport($condition = array())
	{
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'_id' => ['$toString' => '$_id'],
						'cskh' => 1,
						'created_at' => 1,
						'source' => 1,
						'utm_source' => 1,
						'utm_campaign' => 1,
						'fullname' => 1,
						'phone_number' => 1,
						'status_sale' => 1,
						'status_pgd' => 1,
						'id_PDG' => 1,
						'office_at' => 1,
						'updated_at' => 1
					]
				],
				['$lookup' =>
					[
						'from' => 'contract',
						'localField' => "_id",
						'foreignField' => "customer_infor.id_lead",
						'as' => "contract_info"
					],
				],
			],
			'cursor' => new stdClass,
		];
		$match = array();
		$match['$match']['status_sale'] = array(
			'$in' => array('2', '9')
		);
		if (!empty($condition)) {
			if (!empty($condition['start']) && !empty($condition['end'])) {
				$match['$match']['created_at'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
			}
		}
		array_push($conditions['pipeline'], $match);
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

	public function getLead_pt_total($condition = array())
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
		if (isset($condition['sdt'])) {
			$where['phone_number'] = $condition['sdt'];
		}
		if (isset($condition['tab2'])) {
			$where['cskh'] = array('$exists' => false);
		}
		if (isset($condition['tab3'])) {
			// if(in_array('telesales',  $condition['tab3']['groupRoles']))
			// {
			$where['cskh'] = $condition['tab3']['email'];
			// }else{
			//     $where['cskh'] =array('$exists' => true);
			// }
		}
		if (isset($condition['tab4'])) {
			$where['status_sale'] = '1';
		}
		if (isset($condition['area'])) {
			$where['area'] = $condition['area'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['tab5'])) {
			$mongo = $mongo->where_in('status_sale', array('2', '9'));
		}
		if (!empty($condition['fullname'])) {
			$mongo = $mongo->like("fullname", $condition['fullname']);
		}

		return $mongo->order_by($order_by)
			->count($this->collection);

	}

	public function find_where_select($condition, $value)
	{
		return $this->mongo_db
			->select($value)
			->get_where($this->collection, $condition);
	}

	public function findOneAndUpdate($where = "", $inforUupdate = "")
	{
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function getLeadLogCancel($condition = array())
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

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['reason_cancel'])) {
			$mongo = $mongo->like("reason_cancel", $condition['reason_cancel']);
		}

		return $mongo->order_by($order_by)
			->get($this->collection);
	}


	public function find_date($condition)
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
		if (isset($condition['status'])) {
			$where['status_sale'] = $condition['status_sale'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function find_date_day($condition)
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


		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function getDataByRole($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ["created_at"=>"DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['created_at'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);

	}

	public function getDataByRole_count($condition){
		$order_by = ["created_at"=>"DESC"];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['fdate']) && isset($condition['tdate'])) {
			$where['created_at'] = array(
				'$gte' => $condition['fdate'],
				'$lte' => $condition['tdate']
			);
			unset($condition['fdate']);
			unset($condition['tdate']);
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		return $mongo->order_by($order_by)
			->count($this->collection);
	}





}

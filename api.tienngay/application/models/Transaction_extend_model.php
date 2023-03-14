<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transaction_extend_model extends CI_Model
{

	private $collection = 'transaction_extend';

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

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function find_where_desc($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'DESC', 'created_at' => 'DESC'))->get_where($this->collection, $condition);
	}

	public function find_where($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_pay_all($condition)
	{
		return $this->mongo_db
			->order_by(array('date_pay' => 'ASC', 'created_at' => 'ASC'))->get_where($this->collection, $condition);
	}

	public function find_where_in($field = "", $in = array())
	{
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
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

	public function get_da_thanh_toan($code_contract)
	{
		$ops = array(
			array(
				'$match' => array(
					"code_contract" => $code_contract,
					"status" => 1,
					"type" => array('$in' => array(3, 4))
				)
			),
			array(
				'$group' => array(
					'_id' => '$code_contract',
					'so_tien_goc_da_tra' => array('$sum' => '$so_tien_goc_da_tra'),
					'so_tien_lai_da_tra' => array('$sum' => '$so_tien_lai_da_tra'),
					'so_tien_phi_da_tra' => array('$sum' => '$so_tien_phi_da_tra'),
					'so_tien_phi_cham_tra_da_tra' => array('$sum' => '$so_tien_phi_cham_tra_da_tra'),
					'tien_phi_phat_sinh_da_tra' => array('$sum' => '$tien_phi_phat_sinh_da_tra'),
				),
			)
		);
		$data = $this->findAggregate($ops);
		return $data[0]['so_tien_goc_da_tra'] + $data[0]['so_tien_lai_da_tra'] + $data[0]['so_tien_phi_da_tra'] + $data[0]['so_tien_phi_cham_tra_da_tra'] + $data[0]['tien_phi_phat_sinh_da_tra'];
	}

	public function get_tong_phi_phat_sinh($code_contract)
	{
		$phi_phat_sinh = 0;
		$phi_phat_sinh_da_tra = 0;
		$transactionData = $this->find_where(array('code_contract' => $code_contract, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {

				if (isset($value['phi_phat_sinh'])) {
					$phi_phat_sinh += $value['phi_phat_sinh'];
				}

			}
		}
		return $phi_phat_sinh;
	}
    public function get_tong_tien_thua_thanh_toan($code_contract)
	{
		$tien_thua_thanh_toan = 0;
		
		$transactionData = $this->find_where(array('code_contract' => $code_contract,'type' => 4, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {

				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan'];
				}

			}
		}
		return $tien_thua_thanh_toan;
	}
	public function get_tong_tien_thua_gia_han($code_contract_parent_gh)
	{
		$tien_thua_thanh_toan = 0;
		
		$transactionData = $this->find_where(array('code_contract_parent_gh' => $code_contract_parent_gh,'type_payment' => 2,'type' => 4, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {
               $tien_thua_da_thanh_toan=isset($value['tien_thua_thanh_toan_da_tra']) ? $value['tien_thua_thanh_toan_da_tra'] : 0;
				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan']-$tien_thua_da_thanh_toan;
				}

			}
		}
		return $tien_thua_thanh_toan;
	}
	public function get_phi_phat_sinh_da_tra($code_contract)
	{
		$phi_phat_sinh = 0;
		$phi_phat_sinh_da_tra = 0;
		$transactionData = $this->find_where(array('code_contract' => $code_contract, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {
				if (isset($value['tien_phi_phat_sinh_da_tra'])) {
					$phi_phat_sinh_da_tra += $value['tien_phi_phat_sinh_da_tra'];
				}
			}
		}
		return $phi_phat_sinh_da_tra;
	}
	public function get_phi_gia_han_da_tra($code_contract)
	{
		$phi_gia_han = 0;
		$phi_gia_han_da_tra = 0;
		$transactionData = $this->find_where(array('code_contract' => $code_contract, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {
				if (isset($value['so_tien_phi_gia_han_da_tra'])) {
					$phi_gia_han_da_tra += $value['so_tien_phi_gia_han_da_tra'];
				}
			}
		}
		return $phi_gia_han_da_tra;
	}

	public function getTransaction_v2($condition = array())
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
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (isset($condition['sdt'])) {
			$where['customer_bill_phone'] = $condition['sdt'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function getTransaction_kt($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['date_pay'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['code_transaction_bank'])) {
			$where['code_transaction_bank'] = $condition['code_transaction_bank'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['allocation']) && $condition['allocation'] == 'done') {
			$where['temporary_plan_contract_id'] = array('$exists' => true);
			$where['so_tien_goc_da_tra'] = array('$exists' => true);
		}
		if (isset($condition['allocation']) && $condition['allocation'] == 'not_done') {
			$where['temporary_plan_contract_id'] = array('$exists' => false);
			$where['so_tien_goc_da_tra'] = array('$exists' => false);
		}
		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (isset($condition['tab']) && $condition['tab'] == 'wait') {
			$where['status'] = 2;
		}
		if (isset($condition['tab']) && $condition['tab'] == 'import') {
			$where['is_import'] = 1;
		}
		$mongo = $mongo->set_where($where);
		if (!empty($condition['full_name'])) {
			$mongo = $mongo->like("customer_name", $condition['full_name']);
		}
		if (!empty($condition['code_contract_disbursement'])) {
			$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement']);
		}
		if (isset($condition['total']) && $condition['total']) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);

				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					//->where_not_in('type',[2])
					->count($this->collection);
			} else {

				return $mongo->order_by($order_by)

					//->where_not_in('type',[2])
					->count($this->collection);
			}

		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);

				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					// ->where_not_in('type',[2])
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			} else {

				return $mongo->order_by($order_by)
					// ->where_not_in('type',[2])
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			}

		}


	}

	public function getTransaction($condition = array(), $limit = 30, $offset = 0)
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
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (isset($condition['tab'])) {

			if ($condition['tab'] == 'wait') {
				$where['status'] = 2;
				$where['type'] = array(
					'$in' => array(3, 4, 5, 7, 8)
				);
			}
			if ($condition['tab'] == 'approval') {
				$where['status'] = 1;
				$where['type'] = array(
					'$in' => array(3, 4, 5, 7, 8)
				);
			}
			if ($condition['tab'] == 'return') {
				$where['status'] = 11;
				$where['type'] = array(
					'$in' => array(3, 4, 7, 8)
				);
			}

		}

		if (isset($condition['sdt'])) {
			$where['customer_bill_phone'] = $condition['sdt'];
		}
		if (isset($condition['total'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			}
		}
	}

	public function getTransaction_total($condition = array())
	{
		$order_by = ['date_pay' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['date_pay'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (isset($condition['type'])) {
			$where['type'] = $condition['type'];
		}
		if (isset($condition['sdt'])) {
			$where['customer_bill_phone'] = $condition['sdt'];
		}
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->count($this->collection);
		}
	}

	public function findOneAndUpdate($where = "", $inforUupdate = "")
	{
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}

	public function getRevokeLoan($condition)
	{
		$mongo = $this->mongo_db;
		//$condition['type'] = 4;
		$condition['$or'] = array(
			array('type' => 3), // Tất toán
			array('type' => 4), // thanh toán lãi kỳ
			array('type' => 5)  // Gia hạn
		);
		$condition['status'] = 1;
		$order_by = ['date_pay' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$condition['date_pay'] = array(
				'$gte' => intval($condition['start']),
				'$lte' => intval($condition['end'])
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		$mongo = $mongo->set_where($condition);
		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function getPayInvestor($condition)
	{
		$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
		$conditions = [
			'aggregate' => $this->collection,
			'pipeline' => [
				['$project' =>
					[
						'created_at' => 1,
						'status' => 1,
						'type' => 1,
						'code_transaction_bank' => 1,
						'ma_giao_dich_ngan_hang' => 1,
						'bank' => 1,
						'code_contract_disbursement' => 1,
						//'contract_infor.investor_infor.name' => 1,
						'so_tien_goc_da_tra' => 1,
						'so_tien_lai_da_tra' => 1
					]
				],
				['$lookup' =>
					[
						'from' => 'contract',
						'let' => array(
							"code_contract_disbursement" => '$code_contract_disbursement', // $code_contract = field 'code_contract' của 'aggregate' => $this->collection
						),
						'pipeline' => array(
							array(
								'$match' => array(
									'$expr' => array(
										'$and' => array(
											array(
												'$eq' => array('$code_contract_disbursement', '$$code_contract_disbursement')
											),

										)
									)
								),
							),
							array(
								'$project' => [
									'_id' => null,
									'investor_infor.name' => 1,
								]
							)
						),
						'as' => "contract_infor",
					]
				],
			],
			'cursor' => new stdClass,
		];
		$match = array();
		$match['$match']['code_contract_disbursement'] = array('$ne' => null);
		//$match['$match']['type'] = 4;
		$match['$match']['type'] = 6;
		$match['$match']['status'] = 1;
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
		$cursor = $manager->executeCommand($this->config->item("current_DB"), $command);
		return $cursor->toArray();
	}

	public function sum_where($condtion = array(), $get)
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

	public function getTransactionStoreByDay($condition = array(), $limit = 4000)
	{
		$order_by = ['created_at' => 'ASC'];
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
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['total'] = $condition['total'];
		}

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		} else {
			$where['status'] = array('$ne' => 3);
		}

		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}

		if (isset($condition['total_record'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->get($this->collection);
			}
		}
	}

	public function getTransactionInByDay($condition = array(), $limit = 4000)
	{
		$order_by = ['created_at' => 'ASC'];
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
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['total'] = $condition['total'];
		}

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		} else {
			$where['status'] = array('$ne' => 3);
		}

		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($condition['total'])) {
			$mongo = $mongo->like("total", $condition['total']);
		}

		if (isset($condition['total_record'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->get($this->collection);
			}
		}
	}

	public function getTransactionOutByDay($condition = array(), $limit = 4000)
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['approved_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['total'] = $condition['total'];
		}

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		} else {
			$where['status'] = array('$ne' => 3);
		}

		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($condition['total'])) {
			$mongo = $mongo->like("total", $condition['total']);
		}

		if (isset($condition['total_record'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->get($this->collection);
			}
		}
	}
	public function getTransactionInSentApproveByDay($condition = array(), $limit = 4000)
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (isset($condition['start']) && isset($condition['end'])) {
			$where['sent_approve_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['total'] = $condition['total'];
		}

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		} else {
			$where['status'] = array('$ne' => 3);
		}

		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($condition['total'])) {
			$mongo = $mongo->like("total", $condition['total']);
		}

		if (isset($condition['total_record'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->get($this->collection);
			}
		}
	}

	public function getInAll($condition = array())
	{

		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 10h58 30/3/2021
		$where['created_at'] = array('$gte' => (intval(1617076680)));
		$where['status'] = array('$ne' => 3);

		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function getOutAll($condition = array())
	{

		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 10h58 30/3/2021
		$where['approved_at'] = array('$gte' => (intval(1617076680)));
		$where['status'] = array('$ne' => 3);

		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function list_transaction_heyu($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code_transaction'])) {
			$where['code'] = $condition['code_transaction'];
		}
		$where['type'] = 7;

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function total_list_transaction_heyu($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code_transaction'])) {
			$where['code'] = $condition['code_transaction'];
		}
		$where['type'] = 7;
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->count($this->collection);
		}
	}

	public function getTransactionHeyU($condition = array(), $limit = 30, $offset = 0)
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

		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		
		if (isset($condition['email'])) {
			$where['created_by'] = $condition['email'];
		}
		if (isset($condition['code_transaction_bank'])) {
			$where['code_transaction_bank'] = $condition['code_transaction_bank'];
		}
		if (isset($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		$where['type'] = array(
			'$in' => array(7, 8, 10)
		);
		if (isset($condition['tab']) && $condition['tab'] == 'wait') {
			$where['status'] = 2;
		}

		$mongo = $mongo->set_where($where);
		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($condition['email'])) {
			$mongo = $mongo->like("created_by", $condition['email']);
		}
		if (!empty($condition['code_transaction_bank'])) {
			$mongo = $mongo->like("code_transaction_bank", $condition['code_transaction_bank']);
		}
		
		if (isset($condition['total']) && $condition['total']) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->count($this->collection);
			} else {
				return $mongo->order_by($order_by)
					->count($this->collection);
			}

		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
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
	}

	public function list_transaction_mic_tnds($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		$where['type'] = 8;

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function total_list_transaction_mic_tnds($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		$where['type'] = 8;
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->count($this->collection);
		}
	}

	public function getRevokeLoan_count($condition){
		$mongo = $this->mongo_db;
		//$condition['type'] = 4;
		$condition['$or'] = array(
			array('type' => 3), // Tất toán
			array('type' => 4), // thanh toán lãi kỳ
			array('type' => 5)  // Gia hạn
		);
		$condition['status'] = 1;
		$order_by = ['date_pay' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$condition['date_pay'] = array(
				'$gte' => intval($condition['start']),
				'$lte' => intval($condition['end'])
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		$mongo = $mongo->set_where($condition);
		return $mongo->order_by($order_by)->count($this->collection);
	}

	public function getRevokeLoan_view($condition, $limit = 30, $offset = 0){
		$mongo = $this->mongo_db;
		//$condition['type'] = 4;
		$condition['$or'] = array(
			array('type' => 3), // Tất toán
			array('type' => 4), // thanh toán lãi kỳ
			array('type' => 5)  // Gia hạn
		);
		$condition['status'] = 1;
		$order_by = ['date_pay' => 'DESC'];
		if (isset($condition['start']) && isset($condition['end'])) {
			$condition['date_pay'] = array(
				'$gte' => intval($condition['start']),
				'$lte' => intval($condition['end'])
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		$mongo = $mongo->set_where($condition);
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function list_transaction_vbi_tnds($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 10;

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);

		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function total_list_transaction_vbi_tnds($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 10;
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);

		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->count($this->collection);
		}
	}

	public function list_transaction_vbi_utv($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 10;

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);

		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function total_list_transaction_vbi_utv($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 10;
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		$mongo = $mongo->set_where($where);

		if (!empty($condition['code'])) {
			$mongo = $mongo->like("code", $condition['code']);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->count($this->collection);
		} else {
			return $mongo
				->count($this->collection);
		}
	}

	public function find_where_extend($condition){
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['date_pay'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);

			unset($condition['start']);
			unset($condition['end']);
		}

		if (isset($condition['code_contract'])) {
			$where['code_contract_parent_gh'] = $condition['code_contract'];
		}
		$mongo = $mongo->set_where($where);

		return $mongo->get($this->collection);

	}

	public function find_where_date($condition){

		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['end'])) {
			$where['date_pay'] = array(
				'$lte' => $condition['end']
			);
			unset($condition['end']);
		}

		if (isset($condition['code_contract'])) {
			$where['code_contract'] = $condition['code_contract'];
		}
		$mongo = $mongo->set_where($where);

		return $mongo->get($this->collection);

	}

}

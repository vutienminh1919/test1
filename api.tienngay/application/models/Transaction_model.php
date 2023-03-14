<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transaction_model extends CI_Model
{

	private $collection = 'transaction';

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
		return $this->mongo_db->order_by(array('date_pay' => 'ASC'))->where($condition)->find_one($this->collection);
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
					
					
					//chia mien giam
					'so_tien_goc_da_tra_mien_giam' => array('$sum' => '$chia_mien_giam.so_tien_goc_da_tra'),
					'so_tien_lai_da_tra_mien_giam' => array('$sum' => '$chia_mien_giam.so_tien_lai_da_tra'),
					'so_tien_phi_da_tra_mien_giam' => array('$sum' => '$chia_mien_giam.so_tien_phi_da_tra'),
					'so_tien_phi_cham_tra_da_tra_mien_giam' => array('$sum' => '$chia_mien_giam.so_tien_phi_cham_tra_da_tra'),
					
					
					
				),
			)
		);
		$data = $this->findAggregate($ops);
		return $data[0]['so_tien_goc_da_tra'] + $data[0]['so_tien_lai_da_tra'] + $data[0]['so_tien_phi_da_tra'] + $data[0]['so_tien_phi_cham_tra_da_tra'] + $data[0]['tien_phi_phat_sinh_da_tra']+ $data[0]['so_tien_phi_gia_han_da_tra']+ $data[0]['fee_finish_contract']+$data[0]['so_tien_goc_da_tra_mien_giam'] + $data[0]['so_tien_lai_da_tra_mien_giam'] + $data[0]['so_tien_phi_da_tra_mien_giam'] + $data[0]['so_tien_phi_cham_tra_da_tra_mien_giam'] + $data[0]['tien_phi_phat_sinh_da_tra_mien_giam']+ $data[0]['so_tien_phi_gia_han_da_tra_mien_giam']+ $data[0]['so_tien_phi_tat_toan_da_tra_mien_giam'];
	}
	public function tong_tien_da_tra_den_thoi_diem_dao_han($condition)
	{
		$ops = array(
			array(
				'$match' => $condition
			),
			array(
				'$group' => array(
					'_id' => '$code_contract',
					'so_tien_goc_da_tra' => array('$sum' => '$so_tien_goc_da_tra'),
					'so_tien_lai_da_tra' => array('$sum' => '$so_tien_lai_da_tra'),
					'so_tien_phi_da_tra' => array('$sum' => '$so_tien_phi_da_tra'),
					
				),
			)
		);
		$data = $this->findAggregate($ops);
		return $data[0]['so_tien_goc_da_tra'] + $data[0]['so_tien_lai_da_tra'] + $data[0]['so_tien_phi_da_tra'] ;
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
		public function get_so_tien_thieu($code_contract)
			{
				
				$so_tien_thieu_con_lai = 0;
				
				$transactionData = $this->find_where(array('code_contract_parent_gh' => $code_contract, 'status' => 1,'type'=>4));
				if (!empty($transactionData)) {
					foreach ($transactionData as $key => $value) {
                         
						if (isset($value['so_tien_thieu_con_lai'])) {
							$so_tien_thieu_con_lai += $value['so_tien_thieu_con_lai'];
						}
						

					}
				}
				return $so_tien_thieu_con_lai;
			}
			public function get_so_tien_thieu_gia_han($code_contract)
			{
				
				$so_tien_thieu = 0;
				$ngay_thanh_toan = strtotime(date('Y-m-d').' 23:59:59');
				$transactionData = $this->findOne(array('code_contract' => $code_contract, 'status' => 1,'type_payment'=>2,'type'=>4));
				$so_tien_thieu = !empty($transactionData['so_tien_thieu']) ? $transactionData['so_tien_thieu'] : 0;
				return $so_tien_thieu;
			}
		
	public function get_ngay_thanh_toan_ky_chua_tra($code_contract)
	{
		$ngay_thanh_toan = strtotime(date('Y-m-d').' 23:59:59');
		
		$transactionData = $this->findOne_asc(array('code_contract' => $code_contract, 'status' => array('$in'=>[2])));
		if (!empty($transactionData)) {
			$ngay_thanh_toan=(int)$transactionData['date_pay'];
		}
		return $ngay_thanh_toan;
	}
	public function get_ngay_thanh_toan_gh_cc($code_contract)
	{
		$ngay_thanh_toan = strtotime(date('Y-m-d').' 23:59:59');
		
		$transactionData = $this->findOne_asc(array('code_contract' => $code_contract, 'type_payment' => array('$in'=>[2,3]),'status'=>1));
		if (!empty($transactionData)) {
			$ngay_thanh_toan=(int)$transactionData['date_pay'];
		}
		return $ngay_thanh_toan;
	}
    public function get_tong_tien_thua_thanh_toan($code_contract)
	{
		$tien_thua_thanh_toan = 0;
		
		$transactionData = $this->find_where(array('code_contract' => $code_contract,'type' => 4, 'status' => 1));
		if (!empty($transactionData)) {
			foreach ($transactionData as $key => $value) {

				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
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
              
				if (isset($value['tien_thua_thanh_toan'])) {
					$tien_thua_thanh_toan += $value['tien_thua_thanh_toan_con_lai'];
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
		
		if (isset($condition['tab']) && $condition['tab'] == 'all') {
			$where['status_ksnb'] = array('$exists' => false);
			$where['type'] = array(
				'$in' => array(3, 4, 5)
			);
		}
		if (isset($condition['tab']) && $condition['tab'] == 'wait') {
			$where['status_ksnb'] = array('$exists' => false);
			$where['status'] = 2;
			$where['type'] = array(
				'$in' => array(3, 4, 5)
			);
		}
		if (isset($condition['tab']) && $condition['tab'] == 'import') {
			$where['is_import'] = 1;
		}
		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (isset($condition['tab']) && $condition['tab'] == 'contract_ksnb') {
			$where['status_ksnb'] = 1;
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
		if (isset($condition['payment_method'])) {
			$where['payment_method'] = $condition['payment_method'];
		}
		if (isset($condition['type_transaction'])) {
			$where['type'] = $condition['type_transaction'];
		}
		if (isset($condition['code_contract_disbursement'])) {
			$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		}
		if (isset($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		
		if (isset($condition['tab'])) {
			if ($condition['tab'] == 'all') {
				if (isset($condition['type_transaction']) && $condition['type_transaction'] == 3) {
					$where['type'] = 3;
				} else if (isset($condition['type_transaction']) && $condition['type_transaction'] == 4) {
					$where['type'] = 4;
				} else {
					$where['type'] = array(
						'$in' => array(3, 4, 5)
					);
				}
			}
			if ($condition['tab'] == 'wait') {
				$where['status'] = 2;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}
			if ($condition['tab'] == 'not-yet-send') {
				$where['status'] = 4;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}
			if ($condition['tab'] == 'approval') {
				$where['status'] = 1;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}
			if ($condition['tab'] == 'return') {
				$where['status'] = 11;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}

		} else {
			$where['type'] = array(
				'$in' => array(3, 4, 5)
			);
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
    public function getTransaction_ksnb($condition = array(), $limit = 30, $offset = 0)
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

		if (isset($condition['status'])) {
			$where['status'] = $condition['status'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		if (isset($condition['tab'])) {
			if ($condition['tab'] == 'all') {
				if (isset($condition['type_transaction']) && $condition['type_transaction'] == 1) {
					$where['type'] = 1;
				} else if (isset($condition['type_transaction']) && $condition['type_transaction'] == 3) {
					$where['type'] = 3;
				} else if (isset($condition['type_transaction']) && $condition['type_transaction'] == 4) {
					$where['type'] = 4;
				} else {
					$where['type'] = array(
						'$in' => array(1, 3, 4, 5)
					);
				}
			}
			if ($condition['tab'] == 'wait') {
				$where['status'] = 2;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}
			if ($condition['tab'] == 'not-yet-send') {
				$where['status'] = 4;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}
			if ($condition['tab'] == 'approval') {
				$where['status'] = 1;
				$where['type'] = array(
					'$in' => array(1, 3, 4, 5)
				);
			}
			if ($condition['tab'] == 'return') {
				$where['status'] = 11;
				$where['type'] = array(
					'$in' => array(3, 4, 5)
				);
			}

		} else {
			$where['type'] = array(
				'$in' => array(1, 3, 4, 5)
			);
		}

		if (isset($condition['sdt'])) {
			$where['customer_bill_phone'] = $condition['sdt'];
		}

		$where['status_ksnb'] = 1;

		if (isset($condition['total'])) {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);
				if (!empty($condition['code_contract_disbursement_search'])) {
					$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement_search']);
				}
				if (!empty($condition['code_contract'])) {
					$mongo = $mongo->like("code_contract", $condition['code_contract']);
				}
				if (!empty($condition['customer_name'])) {
					$mongo = $mongo->like("customer_name", $condition['customer_name']);
				}


				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->count($this->collection);
			} else {
				$mongo = $mongo->set_where($where);

				if (!empty($condition['code_contract_disbursement_search'])) {
					$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement_search']);
				}
				if (!empty($condition['code_contract'])) {
					$mongo = $mongo->like("code_contract", $condition['code_contract']);
				}
				if (!empty($condition['customer_name'])) {
					$mongo = $mongo->like("customer_name", $condition['customer_name']);
				}

				return $mongo->order_by($order_by)
					->count($this->collection);
			}
		} else {
			if (isset($condition['stores'])) {
				$in = $condition['stores'];
				unset($condition['stores']);
				$mongo = $mongo->set_where($where);

				if (!empty($condition['code_contract_disbursement_search'])) {
					$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement_search']);
				}
				if (!empty($condition['code_contract'])) {
					$mongo = $mongo->like("code_contract", $condition['code_contract']);
				}
				if (!empty($condition['customer_name'])) {
					$mongo = $mongo->like("customer_name", $condition['customer_name']);
				}

				return $mongo->order_by($order_by)
					->where_in('store.id', $in)
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				if (!empty($condition['code_contract_disbursement_search'])) {
					$mongo = $mongo->like("code_contract_disbursement", $condition['code_contract_disbursement_search']);
				}
				if (!empty($condition['code_contract'])) {
					$mongo = $mongo->like("code_contract", $condition['code_contract']);
				}
				if (!empty($condition['customer_name'])) {
					$mongo = $mongo->like("customer_name", $condition['customer_name']);
				}

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
		return $mongo->order_by($order_by)->select(array(), array('fee_delay_pay','image_banking'))->get($this->collection);
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
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
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
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
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
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
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
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
					->get($this->collection);
			} else {
				$mongo = $mongo->set_where($where);
				return $mongo->order_by($order_by)
					->limit($limit)
					->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
					->get($this->collection);
			}
		}
	}

	public function getInAll($condition = array())
	{

		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 00:00 01/4/2021
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		$time_start_calculate_cash = $CI->config->item('date_cash_management');
		$where['created_at'] = array('$gte' => (intval(strtotime($time_start_calculate_cash))));
		
		$where['status'] = array('$ne' => 3);

		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
				->get($this->collection);
		}
	}

	public function getOutAll($condition = array())
	{

		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 00:00 01/4/2021
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		$time_start_calculate_cash = $CI->config->item('date_cash_management');
		$where['approved_at'] = array('$gte' => (intval(strtotime($time_start_calculate_cash))));
		$where['status'] = array('$ne' => 3);

		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','created_at','sent_approve_at','approved_at','status','total','code','updated_at','updated_by','customer_name','type','customer_bill_name','approved_by'))
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
				->where_in('store.id', array($condition['filter_by_store']))
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
				->where_in('store.id', array($condition['filter_by_store']))
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
			'$in' => array(7, 8, 10, 11, 12,13,14,15, 16)
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
		    ->select(array(), array('fee_delay_pay','image_banking'))
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
		$where['type'] = 11;

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
		$where['type'] = 11;
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
    	public function list_transaction_pti_vta($condition, $limit, $offset)
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
		$where['type'] = 15;

		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (isset($condition['filter_by_status'])) {
			$where['status'] = (int)$condition['filter_by_status'];
		}
		if (isset($condition['filter_by_sell_per'])) {
			$where['created_by'] = $condition['filter_by_sell_per'];
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

	public function total_list_transaction_pti_vta($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 15;
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (isset($condition['filter_by_status'])) {
			$where['status'] = (int)$condition['filter_by_status'];
		}
		if (isset($condition['filter_by_sell_per'])) {
			$where['created_by'] = $condition['filter_by_sell_per'];
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
	public function list_transaction_billing_utilities($condition, $limit, $offset)
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
		
		$where['type'] = 1;

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
				->where_in('store.id', array($condition['filter_by_store']))
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function total_list_transaction_billing_utilities($condition)
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
		$where['type'] = 1;
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
				->where_in('store.id', array($condition['filter_by_store']))
				->count($this->collection);
		}
	}

	public function list_transaction_vbi_sxh($condition, $limit, $offset)
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
		$where['type'] = 12;

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

	public function total_list_transaction_vbi_sxh($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 12;
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
	public function checkTransaction($condition = array())
	{
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'ASC'];
		$where = array();

		if (isset($condition['code_contract'])) {
			$mongo = $mongo->or_where(array('code_contract' => $condition['code_contract']));
		}
		if (isset($condition['code_transaction'])) {
			$where['code'] = array('$ne' => $condition['code_transaction']);
		}
		$where['code_contract'] = $condition['code_contract'];
		$where['type'] = array('$in' => array(3, 4)
		);
		
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
					 ->get($this->collection);
	}

	public function countTransaction($time, $store_id)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (isset($time["start"]) && isset($time["end"])) {
			$where["created_at"] = array(
				'$gte' => intval($time["start"]),
				'$lte' => intval($time["end"])
			);
		}
		$condition = array(
			"store.id" => $store_id,
			"type" => array('$in' => array(1,3,4)),
			"payment_method" => "1"
		);
		$result_count = $mongo
						->set_where($where)
						->get_where($this->collection, $condition);
		return count($result_count);
	}
	
	public function getDataByRole($condition){

		$mongo = $this->mongo_db;
		$where = array();

		if (isset($condition['phone'])) {
			$where['customer_bill_phone'] = $condition['phone'];
		}

		$where['type'] = 1;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->get($this->collection);
	}
	   public function get_tien_da_tra_truoc_tat_toan($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "date_pay" => array('$lte' => $date_pay),
                    "type" => 4,
                     "status" => 1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'so_tien_goc_da_tra' =>  array('$sum' => '$so_tien_goc_da_tra'),
                    'so_tien_lai_da_tra' =>  array('$sum' => '$so_tien_lai_da_tra'),
                    'so_tien_phi_da_tra' =>  array('$sum' => '$so_tien_phi_da_tra'),
                    

                    
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }
    public function get_tong_tien_phieu_thu($codeContract="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "type" => array('$in' => [3,4]),
                     "status" => 1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tong_tien_phieu_thu' =>  array('$sum' => '$total'),
                    'tong_tien_mien_giam' =>  array('$sum' => '$total_deductible'),
                    
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }
		public function list_transaction_gic_easy($condition, $limit, $offset)
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
		$where['type'] = 13;

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

	public function total_list_transaction_gic_easy($condition)
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
		$where['type'] = 13;
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
		public function list_transaction_gic_plt($condition, $limit, $offset)
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
		$where['type'] = 14;

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

	public function total_list_transaction_gic_plt($condition)
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
		$where['type'] = 14;
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

	public function getNextTranCode($codeContract)
    {
            if (!$codeContract) {
                    return null;
            }
            //get transaction like '_1231_' (1231 is code contract (000001231))
            $results = $this->mongo_db->like('code', '_' . (int)$codeContract . '_')
                            ->get($this->collection);
            if (!empty($results)) {
                    $max = 1;
                    foreach ($results as $key => $value) {
                            $currentNumber = (int)substr($value["code"], -3); //get last 3 characters of transaction code. ex: PT20220210_1231_012 => 012
                            if ($currentNumber > $max) {
                                    $max = $currentNumber;
                            }
                    }
                    $code = 'PT'. date('ymd') . '_' . (int)$codeContract . '_' . str_pad(((int)$max + 1), 3, '0', STR_PAD_LEFT);
                    return $code;
            }

            return $code = 'PT'. date('ymd') . '_' . (int)$codeContract . '_001';
    }

	public function find_one_select($condition, $select)
	{
		return $this->mongo_db
			->select($select)
			->where($condition)
			->find_one($this->collection);
	}

	public function find_where_select($condition, $select)
	{
		return $this->mongo_db
			->select($select)
			->where($condition)
			->get($this->collection);
	}

	public function find_one_tran_finish($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['code_contract'] = $condition['code_contract'];
		$where['type_payment'] = 1;
		$where['type'] = 3;
		$where['status'] = 1;
		$mongo = $mongo->set_where($where);
		return $mongo->find_one($this->collection);

	}

	public function find_ond_tran_wait($condition)
	{
		$mongo = $this->mongo_db;
		$order_by = ['date_pay' => 'DESC'];
		$where = array();
		$where['code_contract'] = $condition['code_contract'];
		$where['type_payment'] = 1;
		$where['type'] = array('$in' => [3,4]);
		$where['status'] = 2;
		$mongo = $mongo->set_where($where);
		return $mongo
			->order_by($order_by)
			->find_one($this->collection);
	}

}

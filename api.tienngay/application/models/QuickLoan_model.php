<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class QuickLoan_model extends CI_Model
{

	private $collection = 'contract';

	private $manager;

	public function  __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager("mongodb://".$this->config->item("ip_db").":27017");
	}
	public function insert($data){
		return $this->mongo_db->insert($this->collection, $data);
	}
	public function insertReturnId($data) {
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}
	public function findOne($condition){
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function find_where_in($field="", $in=array()){
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
	public function find_one_select($condition, $select){
		return $this->mongo_db
			->select($select)
			->where($condition)
			->find_one($this->collection);
	}
	public function findOneAndUpdate($where="", $inforUupdate="") {
		$update = array(
			'$set' => $inforUupdate
		);
		return $this->mongo_db->find_one_and_update($this->collection, $where, $update);
	}
	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function count_in($field="", $in=array()){
		return $this->mongo_db->where_in($field, $in)->count($this->collection);
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

	public function getContractByTime($searchLike, $condition) {
		$mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		if(isset($condition['start']) && isset($condition['end'])){
			$condition['created_at'] = array(
				'$gte' => intval($condition['start']),
				'$lte' => intval($condition['end'])
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		$mongo = $mongo->set_where($condition);
		if(!empty($searchLike)){
			foreach($searchLike as $key => $value){
				$mongo = $mongo->like($key,$value);

			}
		}
		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function countContractActivebyTime($time, $store_id) {
		// $mongo = $this->mongo_db;
		$order_by = ['created_at' => 'DESC'];
		$where = [];
		if(isset($time['start']) && isset($time['end'])){
			$where['created_at'] = array(
				'$gte' => intval($time['start']),
				'$lte' => intval($time['end'])
			);
		}
		// $status = array(16, 17);
		// $where['status'] = array('$ne'=> 0);
		// $where['store.id'] = $store_id;
		// $status = array('$or'=>array(array('status'=>16), array('status'=>17)));
		// $mongo = $mongo->where_in('status',$status);
		// $mongo->set_where($where);
		// $mongo->or_where($status);
		// // return $mongo->where_in('status',$status)->count($this->collection);
		// return $mongo->count($this->collection);

		$condition = array("store.id" => $store_id, '$or'=>array(array('status'=>16), array('status'=>17)));

		$arr = $this->mongo_db
			->set_where($where)
			->get_where($this->collection, $condition);
		return count($arr);

		return $mongo->get_where($this->collection, $condition);
	}
	public function findPageNotIn($condition, $offset=1, $order_by=array('time' => 'desc'), $sl,$field,$in=""){
		$arr = $this->mongo_db
			->order_by($order_by)
			->select($sl)
			->offset($offset)
			->where_not_in($field, $in)
			->get_where($this->collection, $condition);
		return $arr;
	}

	public function getContractByRole($condition = array()){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$in = array();
		$mongo = $this->mongo_db;
		if(isset($condition['start']) && isset($condition['end'])){
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}
		if(isset($condition['type'])){
			$where['type'] = $condition['type'];
		}
		if(isset($condition['status'])){
			$where['status'] = (int)$condition['status'];
		}
		if(isset($condition['created_by'])){
			$where['created_by'] = $condition['created_by'];
		}
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if(!empty($in)){
			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
				->get($this->collection);
		} else {
			return $mongo->order_by($order_by)
				->get($this->collection);
		}
	}

	public function getContractForQuickLoan($condition = array()){
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		if(isset($condition['start']) && isset($condition['end'])){
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
		return $mongo->order_by($order_by)->where(["type"=>"vaynhanh"])
			->get($this->collection);
	}

	public function findContractRenew($condition) {
		$status = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['status'])) {
			$status = $condition['status'];
			unset($condition['status']);
		}
		$mongo->set_where($condition);
		return $mongo->where_not_in('status',$status)
			->count($this->collection);
	}

	public function findContractWithTemp($condition=array()) {
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
		if(!empty($condition)) {
			if(!empty($condition['start']) && !empty($condition['end'])) {
				$match['$match']['created_at'] = array(
					'$gte' => $condition['start'],
					'$lte' => $condition['end']
				);
			} else if(!empty($condition['start']) && empty($condition['end'])) {
				$match['$match']['created_at'] = array('$gte' => $condition['start']);
			} else if(!empty($condition['end']) && empty($condition['start'])) {
				$match['$match']['created_at'] = array('$lte' => $condition['end']);
			}
		}
		array_push($conditions['pipeline'], $match);
		$command = new MongoDB\Driver\Command($conditions);
		$arr = array();
		$cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
		foreach($cursor as $item) {
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getReportContractVolume($condition=array(), $start="", $end="") {
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
		foreach($cursor as $item) {
			array_push($contractStores, $item);
		}

		//var_dump($contractStore);
		//die;

		//Step 2: Lọc HĐ của phòng giao dịch theo tháng
		$start    = (new DateTime($start))->modify('first day of this month');
		$end      = (new DateTime($end))->modify('first day of next month');
		$interval = DateInterval::createFromDateString('1 month');
		$period   = new DatePeriod($start, $interval, $end);

		$totalDisburseAccumulated = 0; // Tổng giải ngân lũy kế
		$arrMonth = array();
		foreach ($period as $dt) {
			$format = $dt->format("Y-m"); // 2020-01
			$startMonth = date('Y-m-01', strtotime($format)); // 2020-01-01
			$endMonth = date('Y-m-t', strtotime($format)); // 2020-01-31

			$startTimestamp = strtotime(trim($startMonth).' 00:00:00'); //1577836800
			$endTimestamp = strtotime(trim($endMonth).' 23:59:59'); //1580515199

			$arrMonth[$format] = array();

			//Foreach stores
			foreach($contractStores as $contractStore) {
				if($this->checkContractInMonth($contractStore, $startTimestamp, $endTimestamp) == FALSE) continue;
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
				foreach($contractStore->contracts as $contract) {
					if($contract->created_at < $startTimestamp || $contract->created_at > $endTimestamp) continue;
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

	private function checkContractInMonth($contractStore, $startTimestamp, $endTimestamp) {
		$haveContract = false;
		foreach($contractStore->contracts as $contract) {
			if($contract->created_at < $startTimestamp || $contract->created_at > $endTimestamp) continue;
			$haveContract = true;
			break;
		}
		return $haveContract;
	}

	public function findContractExport($condition=array()) {
		$compareLte = array();
		if(!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if(!empty($condition['end'])) {
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
		foreach($cursor as $item) {
			if(empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getDisburse($condition) {
		if(isset($condition['start']) && isset($condition['end'])){
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

	public function getInterestReal($condition) {
		$compareLte = array();
		if(!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if(!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}

		$compareLteTran = array();
		if(!empty($condition['start'])) {
			$compareLteTran = array(
				'$lte' => array($condition['start'], '$created_at')
			);
		}
		$compareGteTran = array();
		if(!empty($condition['end'])) {
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
		foreach($cursor as $item) {
			if(empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getFollowCurrentMonth($condition) {
		$compareLte = array();
		if(!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if(!empty($condition['end'])) {
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
									'ngay_ky_tra' => 1
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
									'ngay_ky_tra' => 1
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
								]
							)
						),
						'as' => "bang_lai_ky_den_thoi_diem_dao_han",
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
									'ngay_ky_tra' => 1
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
									'ngay_ky_tra' => 1
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
		foreach($cursor as $item) {
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getFollowInvestor($condition) {
		$compareLte = array();
		if(!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if(!empty($condition['end'])) {
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

	public function getInterestRealInvestor($condition) {
		$compareLte = array();
		if(!empty($condition['start'])) {
			$compareLte = array(
				'$lte' => array($condition['start'], '$time_timestamp')
			);
		}
		$compareGte = array();
		if(!empty($condition['end'])) {
			$compareGte = array(
				'$gte' => array($condition['end'], '$time_timestamp')
			);
		}

		$compareLteTran = array();
		if(!empty($condition['start'])) {
			$compareLteTran = array(
				'$lte' => array($condition['start'], '$created_at')
			);
		}
		$compareGteTran = array();
		if(!empty($condition['end'])) {
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
		foreach($cursor as $item) {
			if(empty($item->plan_contract) || count($item->plan_contract) == 0) continue;
			array_push($arr, $item);
		}
		return $arr;
	}

	public function getCodeAutoDisburseMent() {
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
			'code_auto_disbursement' => $date.'-'.str_pad($numberMax, 6, '0', STR_PAD_LEFT)
		);
		return $return;
	}
}

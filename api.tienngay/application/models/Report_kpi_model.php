<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_kpi_model extends CI_Model
{

	private $collection = 'report_kpi';
	private $collection_ct = 'contract';

	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}
	public function findOne($condition){
		return $this->mongo_db->where($condition)->find_one($this->collection);
	}
	public function count($condition){
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function find_where($field="", $in=array()){
		return $this->mongo_db->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->get($this->collection);
	}
	public function update($condition, $set){
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}
	public function get_where($condition){
		return $this->mongo_db->where($condition)->order_by(array('sum_giai_ngan' => 'DESC'))->get($this->collection);
	}
	public function insert($data)
		{
			return $this->mongo_db->insert($this->collection, $data);
		}
	public function delete($condition){
		return $this->mongo_db->where($condition)->delete($this->collection);
	}
		public function find_select_top($condition_date=array(),$match_in = array())
	{
		
	
		$condition = array();
		$conditions = [
			'aggregate' => $this->collection_ct,
			'pipeline' => [
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
											'$lt' => array('$ngay_ky_tra', $this->createdAt-10* 24 * 3600)
										),
										array(
											'$eq' => array('$status', 1)
										),
									)
								)
							),
						),
						array(
								'$project' => [
									'tien_goc_1ky_con_lai' => 1,
									
								]
							)
					),
					'as' => "lai_ky_t_10"
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
											'$lt' => array('$ngay_ky_tra', $this->createdAt-4* 24 * 3600)
										),
										array(
											'$eq' => array('$status', 1)
										),
									)
								)
							),
						),
						array(
								'$project' => [
									'tien_goc_1ky_con_lai' => 1,
									
								]
							)
					),
					'as' => "lai_ky_t_4"
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
											'$eq' => array('$status', 1)
										),
									)
								)
							),
						),
						array(
								'$project' => [
									'tien_goc_1ky_con_lai' => 1,
									
								]
							)
					),
					'as' => "list_dang_cho_vay"
				]
			],
		
			[   
                '$match' => $condition_date ? : (object) []
           ],
   
           ['$project' =>
					[
						'_id'=>'$_id',
						"so_tien_vay" =>[
							'$sum' => array('$toLong' => '$loan_infor.amount_money')
					        ],
						"du_no_qua_han_t10" =>[
							'$sum' => '$lai_ky_t_10.tien_goc_1ky_con_lai'
						],
						"du_no_qua_han_t4" =>[
							'$sum' => '$lai_ky_t_4.tien_goc_1ky_con_lai'
						],
					     "du_no_dang_cho_vay" =>[
							'$sum' => '$list_dang_cho_vay.tien_goc_1ky_con_lai'
					         ]
					 

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

    
	public function sum_where($condtion = array(),$get){
		$ops = array(
			array (
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get ),
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
	public function sum_where_contract($condtion = array(),$get){
		$ops = array(
			array (
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get ),
				),
			),
		);
		$data = $this->mongo_db->aggregate($this->collection_ct, $ops)->toArray();
		if(isset($data[0]['total'])){
			return $data[0]['total'];
		}else{
			return 0;
		}

	}
	public function getKpiByTime($searchLike, $condition, $limit = 30, $offset = 0)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$in = array();
		$order_by = ['month' => 'DESC','year' => 'DESC'];
		if (isset($condition['month'])) {
			$where['month'] =$condition['month'];
		}
		if (isset($condition['year'])) {
			$where['year'] =$condition['year'];
		}
	if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (isset($condition['code_area'])) {
			$mongo = $mongo->where_in('code_area', $condition['code_area']);
		}
		if (isset($condition['code_region'])) {
			$mongo = $mongo->where_in('code_region', $condition['code_region']);
		}
		if (isset($condition['code_domain'])) {
			$mongo = $mongo->where_in('code_domain', $condition['code_domain']);
		}
		if (isset($condition['code_store'])) {
			$mongo = $mongo->where_in('store.id', $condition['code_store']);
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
	
}

<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


class Pti_vta_bn_model extends CI_Model
{
	private $collection = 'pti_vta_bn';

	public function __construct()
	{
		parent::__construct();
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('number_item' => 'DESC'))->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}
    public function getMaxNumberItem()
	{
		return $this->mongo_db
			->select(array("number_item"))
			->order_by(array('number_item' => 'DESC'))
			->limit(1)
			->get($this->collection);
	}
	public function find_where($condition)
	{
		return $this->mongo_db
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

	public function count_pti()
	{
		return $this->mongo_db
		  ->set_where(['type_pti'=>'BN'])
			->count($this->collection);
	}

	public function get_list_pti_vta($condition, $limit, $offset)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['customer_name'])) {
			if ($condition['type_pti'] != "WEB") {
				$where['request.btendn'] = $condition['customer_name'];
			} else {
				$where['data_origin.fullname'] = $condition['customer_name'];
			}
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_info.customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['code_pti_vta'])) {
			$where['pti_code'] = $condition['code_pti_vta'];
		}
		if (isset($condition['filter_by_sell_per'])) {
			if ($condition['type_pti'] != "WEB") {
				$where['created_by'] = $condition['filter_by_sell_per'];
			} else {
				$where['modify_user'] = $condition['filter_by_sell_per'];
			}
		}
		if (isset($condition['filter_by_status'])) {
			$where['status'] = (int)$condition['filter_by_status'];
		}
		if (isset($condition['customer_name_another'])) {
			if ($condition['type_pti'] != "WEB") {
				$where['request.ten'] = $condition['customer_name_another'];
			} else {
				$where['data_origin.fullname'] = $condition['customer_name'];
			}
		}
		if (isset($condition['customer_cmt'])) {
			$where['request.so_cmt'] = $condition['customer_cmt'];
		}
		if (!empty($condition['type_pti'])) {
			$where['type_pti'] =$condition['type_pti'];
		} else {
			$where['type_pti'] ="BN";
		}
		if ( $condition['type_pti'] == "WEB") {
			$in = ['611f4cbd5324a72ed500df52'];
		} else if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
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
				->where_in('store.id', array($condition['filter_by_store']))
				->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		}
	}

	public function get_count_pti_vta($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['customer_name'])) {
			$where['request.btendn'] = $condition['customer_name'];
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_info.customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['code_pti_vta'])) {
			$where['pti_code'] = $condition['code_pti_vta'];
		}
		if (isset($condition['filter_by_sell_per'])) {
			$where['created_by'] = $condition['filter_by_sell_per'];
		}
		if (isset($condition['filter_by_status'])) {
			$where['status'] = (int)$condition['filter_by_status'];
		}
		if (isset($condition['customer_name_another'])) {
			$where['request.ten'] = $condition['customer_name_another'];
		}
		if (isset($condition['customer_cmt'])) {
			$where['request.so_cmt'] = $condition['customer_cmt'];
		}
		if (!empty($condition['type_pti'])) {
			$where['type_pti'] =$condition['type_pti'];
		} else {
			$where['type_pti'] ="BN";
		}
		if (!empty($condition['stores']) && $condition['type_pti'] != "WEB") {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
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

	public function get_list_pti_store($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end'],
			);
		}
		if (!empty($condition['store'])) {
			$where['store.id'] = $condition['store'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->get($this->collection);
	}

	public function get_pti_vta_accounting_transfe($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;
		$where['status'] = array('$in' => array(10, 3));
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->order_by($order_by)
				->get($this->collection);
		} else {
			return $mongo
				->order_by($order_by)
				->get($this->collection);
		}
	}


	public function getPtaVtaNotYetSendAll($condition)
	{
		$order_by = ['created_at' => 'ASC'];
		$where = array();
		$mongo = $this->mongo_db;
		// lay du lieu tu 00:00 01/4/2021
		$CI = &get_instance();
		$CI->load->config('config_money_tnds');
		$time_start_calculate_cash = $CI->config->item('date_cash_management');
		$where['created_at'] = array('$gte' => (intval(strtotime($time_start_calculate_cash))));
		$where['status'] = 10;
        $where['type_pti'] = "BN";
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','price','code_pti_vta','created_at','approved_by','status'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','price','code_pti_vta','created_at','approved_by','status'))
				->get($this->collection);
		}
	}

	public function getPtaVtaSendDay($condition)
	{
		if ((!empty($condition['type_transaction']) && !in_array($condition['type_transaction'], [15])) || (!empty($condition['status']) && $condition['status'] != 10)) return;
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
		if (!empty($condition['code'])) {
			$where['code'] = $condition['code'];
		}
		if (isset($condition['total'])) {
			$where['fee'] = $condition['total'];
		}
		$where['type_pti'] = "BN";
		$where['status'] = 10;
		if (isset($condition['stores'])) {
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id', $in)
				->select(array('_id','store','created_by','price','code_pti_vta','created_at','approved_by','status'))
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->select(array('_id','store','created_by','price','code_pti_vta','created_at','approved_by','status'))
				->get($this->collection);
		}

	}
    public function get_statistics($condition)
	{
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		if (isset($condition['customer_name'])) {
			$where['request.btendn'] = $condition['customer_name'];
		}
		if (isset($condition['customer_phone'])) {
			$where['customer_info.customer_phone'] = $condition['customer_phone'];
		}
		if (isset($condition['code_pti_vta'])) {
			$where['pti_code'] = $condition['code_pti_vta'];
		}
		if (isset($condition['filter_by_sell_per'])) {
			$where['created_by'] = $condition['filter_by_sell_per'];
		}
		if (isset($condition['filter_by_status'])) {
			$where['status'] = (int)$condition['filter_by_status'];
		}
		if (isset($condition['customer_name_another'])) {
			$where['request.ten'] = $condition['customer_name_another'];
		}
		if (isset($condition['customer_cmt'])) {
			$where['request.so_cmt'] = $condition['customer_cmt'];
		}
		if (!empty($condition['type_pti'])) {
			$where['type_pti'] =$condition['type_pti'];
		} else {
			$where['type_pti'] ="BN";
		}
		if ( $condition['type_pti'] == "WEB") {
			$in = ['611f4cbd5324a72ed500df52'];
		} else if (!empty($condition['stores'])) {
			$in = $condition['stores'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($in)) {
			return $mongo
				->where_in('store.id', $in)
				->get($this->collection);
		} else {
			return $mongo
				->where_in('store.id', array($condition['filter_by_store']))
				->get($this->collection);
		}
	}
	 

	
      public function getPti_vta_hd($condition = array(),$limit = 30, $offset = 0) {
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
		  if (!empty($condition['code_contract_disbursement'])) {
		  	$where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		  }
		  if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
				->where_in('type_pti', ['HD'])
                 ->limit($limit)
                ->offset($offset)
				->get($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			
			return $mongo->order_by($order_by)
				->where_in('type_pti', ['HD'])
			  ->limit($limit)
                ->offset($offset)
				->get($this->collection);
		}
    }
      public function getPti_vta_hd_total($condition = array()){
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
		  if (!empty($condition['code_contract_disbursement'])) {
			  $where['code_contract_disbursement'] = $condition['code_contract_disbursement'];
		  }
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('type_pti', ['HD'])
				->where_in('store.id',$in)
				->count($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('type_pti', ['HD'])
				->count($this->collection);
		}
    }
      public function getPti_vta_doi_soat($condition = array(),$limit = 30, $offset = 0) {
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
		
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
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
      public function getPti_vta_doi_soat_total($condition = array()){
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
		
		if(isset($condition['stores'])){
			$in = $condition['stores'];
			unset($condition['stores']);
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->where_in('store.id',$in)
				->count($this->collection);
		} else {
			$mongo = $mongo->set_where($where);
			return $mongo->order_by($order_by)
				->count($this->collection);
		}
    }

    public function list_pti($condition){

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

		$where['push_core_pti'] = array('$exists' => false);

		$where['status'] = 1;

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->get($this->collection);


	}

	public function find_by_select($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$where['customer_info.customer_phone'] = $condition['lead_phone'];
		$where['type_pti'] = "BN";
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->select(['status', 'price'])
					 ->get($this->collection);
	}

	public function getAll_excel($condition){

		$order_by = ["created_at"=>"DESC"];
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
			$where['status'] = ['$in' => ["1",1]];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (isset($condition['created_by'])) {
			$mongo = $mongo->or_where(array('created_by'=> $condition['created_by'], 'contract_info.created_by' => $condition['created_by']));
		}
		if (isset($condition['store.id'])) {
			$mongo = $mongo->or_where(array('store.id'=> $condition['store.id'], 'contract_info.store.id' => $condition['store.id']));
		}


		return $mongo->order_by($order_by)
			->get($this->collection);


	}

	/**
	* Nếu KH đã tồn tại bảo hiểm thì lấy giá trị NGAY_KT xa nhất
	* 
	*/
	public function findNgayKTByCCCD($cccd)
	{
		$ngay_kts = $this->mongo_db->set_where(array(
			'request.so_cmt' => $cccd, 
			'pti_info.code' => "000", 
			'pti_info.process' => "done"
			
		))->select(array('NGAY_KT'))->get($this->collection);

		if (count($ngay_kts) > 0) {
			$ngay_kt = $ngay_kts[0]["NGAY_KT"];
			foreach ($ngay_kts as $key => $value) {
				if (strtotime($ngay_kt) < strtotime($value["NGAY_KT"])) {
					$ngay_kt = $value["NGAY_KT"];
				}
			}
			return $ngay_kt;
		}
		return null;
	}

}

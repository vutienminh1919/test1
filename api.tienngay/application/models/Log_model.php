<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_model extends CI_Model
{

    private $collection = 'log';

    public function  __construct()
    {
        parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function findOne($condition){
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function count($condition){
        return $this->mongo_db->where($condition)->count($this->collection);
    }
    public function find_where($condition){
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
    public function find(){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
    }
    public function findOne_ghcc($condition)
    {
        return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
    }
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
    }
    
    public function find_where_not_in($condition, $field="", $in=""){
        if(empty($in)) {
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
	public function getLogs($condition){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}
    
    public function find_where_select($condition, $value){
        return $this->mongo_db
            ->select($value)
            ->get_where($this->collection, $condition);
    }
    
    public function insertLog($type="", $action="", $old="", $new="", $createdAt="", $createdBy="") {
        $data = array(
            "type" => $type,
            "action" => $action,
            "old" => $old,
            "new" => $new,
            "created_at" => $createdAt,
            "created_by" => $createdBy
        );
        $this->insert($data);
    }

	public function find_where_in($value)
	{

		$mongo = $this->mongo_db;


		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		$condition['code_contract'] = $value;



		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("8","6","5","3"));
		}

		return $mongo
			->select(array('contract_id','old.created_at', 'new.status','old.code_contract','old.store.name','old.customer_infor.customer_name','old.customer_infor.customer_identify','old.loan_infor.loan_product.text','old.loan_infor.amount_money','old.status','created_by','created_at','new.error_code','new.lead_cancel1_C1','new.lead_cancel1_C2','new.lead_cancel1_C3','new.lead_cancel1_C4','new.lead_cancel1_C5','new.lead_cancel1_C6','new.lead_cancel1_C7','new.exception1_value_detail','new.exception2_value_detail','new.exception3_value_detail','new.exception4_value_detail','new.exception5_value_detail','new.exception6_value_detail','new.exception7_value_detail','new.reason'))
			->order_by(array('created_at' => 'ASC'))->get($this->collection);
	}

	public function find_where_in_count($value)
	{
		$mongo = $this->mongo_db;
		$condition['code_contract'] = $value;
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('old.code_contract', array($condition['code_contract']));
		}
		if (isset($condition['code_contract'])) {
			$mongo = $mongo->where_in('new.status', array("8"));
		}
		return $mongo->count($this->collection);
	}

	public function get_type_fee_loan_action_update($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$mongo = $this->mongo_db;
		if (!empty($condition['start']) && !empty($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => $condition['start'],
				'$lte' => $condition['end']
			);
		}
		$where['type'] = 'fee_loan';
		$where['action'] = 'update';
		$mongo = $mongo->set_where($where);
		
		return $mongo->order_by(array('created_at' => 'ASC'))
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
    public function getUpdate_transaction_import($condition = array(), $limit = 30, $offset = 0)
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
            $where['data_post.code_contract_disbursement'] = $condition['code_contract_disbursement'];
        }
       
        $where['action_exten'] ='update_contract';
        if (!empty($where)) {
            $mongo = $mongo->set_where($where);
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

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}






}

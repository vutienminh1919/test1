<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contract_extend_model extends CI_Model
{

	private $collection = 'contract';
    private $collection_transaction = 'transaction';
	private $manager, $createdAt;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("temporary_plan_contract_model");
		$this->load->model("transaction_model");
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
	public function get_list_gh($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'ASC'];

		if (isset($condition['id_contract'])) {
			$mongo = $mongo->or_where(array("_id" => $condition["id_contract"]));
		}
		
        if (isset($condition['code_contract_parent_gh']) && !empty($condition['code_contract_parent_gh'])) {
			$mongo = $mongo->or_where(array("code_contract" => $condition["code_contract_parent_gh"]));
			$mongo = $mongo->or_where(array("code_contract_parent_gh" => $condition["code_contract_parent_gh"]));
			
		}
		if (isset($condition['code_contract']) && !empty($condition['code_contract'])) {
			$mongo = $mongo->or_where(array("code_contract_parent_gh" => $condition["code_contract"]));
		}
      
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->get($this->collection);
	}
		public function get_list_cc($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		$order_by = ['created_at' => 'ASC'];

		if (isset($condition['id_contract'])) {
			$mongo = $mongo->or_where(array("_id" => $condition["id_contract"]));
		}
		if (isset($condition['code_contract_parent_cc']) && !empty($condition['code_contract_parent_cc'])) {
			$mongo = $mongo->or_where(array("code_contract" => $condition["code_contract_parent_cc"]));
			$mongo = $mongo->or_where(array("code_contract_parent_cc" => $condition["code_contract_parent_cc"]));
		}
		if (isset($condition['code_contract']) && !empty($condition['code_contract'])) {
			$mongo = $mongo->or_where(array("code_contract_parent_cc" => $condition["code_contract"]));
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->get($this->collection);
	}
	 public function getMaHopDongVay($item) {
        $maHDVay = '';
       

        if(!empty($item['type_gh']) && !empty($item['code_contract_parent_gh'])) {
            if($item['type_gh']>=1)
            {

              $maHDVay=$this->find_one_select(['code_contract'=> $item['code_contract_parent_gh']],['code_contract_disbursement'])['code_contract_disbursement'];
            }
        }
        if(!empty($item['type_cc']) && !empty($item['code_contract_parent_cc'])) {
            if($item['type_cc']>=1)
            {
              $maHDVay=$this->find_one_select(['code_contract'=> $item['code_contract_parent_cc']],['code_contract_disbursement'])['code_contract_disbursement'];
            }
        }
        return $maHDVay;
    }
    
    public function getMaPhuLuc($item) {
        $maPhuLuc = "";
        if(!empty($item['type_gh']) && !empty($item['code_contract_parent_gh'])) {
            if($item['type_gh']>=1)
            {
              $maPhuLuc=$item['code_contract_disbursement'];
            }
        }
        if(!empty($item['type_cc']) && !empty($item['code_contract_parent_cc'])) {
            if($item['type_cc']>=1)
            {
              $maPhuLuc=$item['code_contract_disbursement'];
            }
        }
        return $maPhuLuc;
    }
     public function findOne_transaction($condition)
    {
        return $this->mongo_db->where($condition)->find_one($this->collection_transaction);
    }
    public function getStatusContract($condition) {
       $condition_tran=array(
                'code_contract'=>$condition['code_contract'],
                'status'=>$condition['status'],
                'type'=>$condition['type'],
                'date_pay'=> array(
                '$lte' => $condition['endMonth'])
       );
        $tran_tt=$this->findOne_transaction($condition_tran);

        $status = 'Đang vay';
    
        if(!empty( $tran_tt))
        {
            $status="Tất toán";
        }
        if(isset($condition['ky_tt_xa_nhat']) && isset($condition['endMonth']) && isset($condition['status_contract_origin']))
        {
              if($condition['status_contract_origin']==33  &&  $condition['ky_tt_xa_nhat']<$condition['endMonth'])
            {
           //Số tiền gốc còn lại
            $status="Gia hạn";

            } 
             if( $condition['status_contract_origin']==34 &&  $condition['ky_tt_xa_nhat']<$condition['endMonth'])
            {
           //Số tiền gốc còn lại
           $status="Cơ cấu";

            } 
        }
        return $status;
    }
}
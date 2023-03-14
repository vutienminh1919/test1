<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_lead_model extends CI_Model
{

    private $collection = 'log_lead';

    public function  __construct()
    {
        parent::__construct();
    }
    public function findOne($condition){
        return $this->mongo_db->order_by(array('lead_data.updated_at' => 'DESC'))->where($condition)->find_one($this->collection);
    }
   
      public function findOne_dk($condition = array()){
        $order_by = ['lead_data.updated_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['start']) && isset($condition['end'])){
            $where['lead_data.updated_at'] = array(
                '$gte' => $condition['start'],
                '$lte' => $condition['end']
            );
            unset($condition['start']);
            unset($condition['end']);
         }
         
         if(isset($condition['phone_number'])){
            $where['lead_data.phone_number'] = $condition['phone_number'];
        }
          if(isset($condition['id'])){
            $where['lead_data.id'] = $condition['id'];
        }
       if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
      
            return $mongo->order_by($order_by)
                ->find_one($this->collection);
        
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
           ->order_by(array('lead_data.updated_at' => 'DESC'))
            ->get($this->collection);
    }

    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
     public function getByRole($condition = array()){
        $order_by = ['lead_data.updated_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['cstart']) && isset($condition['cend'])){
            $where['lead_data.created_at'] = array(
                '$gte' => $condition['cstart'],
                '$lte' => $condition['cend']
            );
            unset($condition['cstart']);
            unset($condition['cend']);
         }
            if(isset($condition['ustart']) && isset($condition['uend'])){
            $where['lead_data.updated_at'] = array(
                '$gte' => $condition['ustart'],
                '$lte' => $condition['uend']
            );
            unset($condition['ustart']);
            unset($condition['uend']);
         }
         if(isset($condition['status_sale_fist'])){
            $where['old_data.status_sale'] = $condition['status_sale_fist'];
        }
        if(isset($condition['status_sale_last'])){
            $where['lead_data.status_sale'] = $condition['status_sale_last'];
        }
         
       
       if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
        if(!empty($condition['utm_source'])){
            $mongo = $mongo->like("old_data.utm_source",$condition['utm_source']);
        }
        if(!empty($condition['utm_campaign'])){
            $mongo = $mongo->like("old_data.utm_campaign",$condition['utm_campaign']);
        }
         if(!empty($condition['phone_number'])){
            $mongo = $mongo->like("old_data.phone_number",$condition['phone_number']);
        }
            return $mongo->order_by($order_by)
                ->get($this->collection);
        
    }
     public function getByRole_mkt($condition = array()){
        $order_by = ['lead_data.updated_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['start']) && isset($condition['end'])){
            $where['lead_data.updated_at'] = array(
                '$gte' => $condition['start'],
                '$lte' => $condition['end']
            );
            unset($condition['start']);
            unset($condition['end']);
         } 
        if(isset($condition['source'])){
            $where['old_data.source'] = $condition['source'];
        }
         if(isset($condition['area'])){
            $where['old_data.area'] = $condition['area'];
        }
          if(isset($condition['status_sale'])){
            $where['lead_data.status_sale'] = $condition['status_sale'];
        }
         if(isset($condition['status_sale_fist'])){
            $where['old_data.status_sale'] = $condition['status_sale_fist'];
        }
        if(isset($condition['status_sale_last'])){
            $where['lead_data.status_sale'] = $condition['status_sale_last'];
        }
         
       
       if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
        if(!empty($condition['utm_source'])){
            $mongo = $mongo->like("old_data.utm_source",$condition['utm_source']);
        }
        if(!empty($condition['utm_campaign'])){
            $mongo = $mongo->like("old_data.utm_campaign",$condition['utm_campaign']);
        }

        if(isset($condition['code_store'])){
            $mongo = $mongo->where_in('lead_data.id_PDG',$condition['code_store']);
        }
            return $mongo->order_by($order_by)
                ->get($this->collection);
        
    }

      public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
        public function getByRole_old($condition = array()){
        $order_by = ['lead_data.created_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['start']) ){
            $where['lead_data.created_at'] = array(
                '$lte' => $condition['start']
            );
            unset($condition['start']);
          
         }
         
        if(isset($condition['status'])){
            $where['status'] = $condition['status'];
        }
          if(isset($condition['status_sale'])){
            $where['status_sale'] = $condition['status_sale'];
        }
       
       
      if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
            return $mongo->order_by($order_by)
                ->get($this->collection);
        
    }

	public function leadLogHistory($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ["lead_data.updated_at" => "ASC"];
		$mongo = $this->mongo_db;
		
		if (isset($condition["phone_number"])) {
			$mongo = $mongo->where_in("lead_data.phone_number", array($condition["phone_number"]));
		}
		
		return $mongo->order_by($order_by)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
    }

    
}

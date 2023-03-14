<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_contract_tempo_model extends CI_Model
{

    private $collection = 'log_contract_tempo';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }

	public function findOne_kt($condition)
	{
		return $this->mongo_db->where($condition)->select(array("new.status","created_at"))->find_one($this->collection);
	}
    
}

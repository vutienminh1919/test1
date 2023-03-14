<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_contract_model extends CI_Model
{

    private $collection = 'log_contract';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function getLogs($condition){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get_where($this->collection, $condition);
	}

	public function findOne_ghcc($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('created_at' => 'DESC'))->limit('1')->get($this->collection);
	}


}

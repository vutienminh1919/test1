<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Log_vbee_missed_call_model extends CI_Model
{
	private $collection = "log_vbee_missed_call";

	public function  __construct()
	{
		parent::__construct();
	}

	public function insert($data){
		return $this->mongo_db->insert($this->collection, $data);
	}
}

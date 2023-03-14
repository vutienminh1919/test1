<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Webhook_vbee_model extends CI_Model
{
	private $collection = 'webhook_vbee';

	public function  __construct()
	{
		parent::__construct();
		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}
	public function insert($data){
		return $this->mongo_db->insert($this->collection, $data);
	}

}


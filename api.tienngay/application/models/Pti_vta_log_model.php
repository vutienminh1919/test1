<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Pti_vta_log_model extends CI_Model
{
	private $collection = 'log_pti_vta_fee';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}
}


<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Java_report_model extends CI_Model
{
	private $collection = 'java_report';

	public function  __construct()
	{
		parent::__construct();
	}

	public function find() {
		return $this->mongo_db->get($this->collection);
	}

	public function update($condition){
		return $this->mongo_db->where(['name' => $condition])->set(['time' => 0])->update($this->collection);
	}

	public function delete($condition){
		return $this->mongo_db->where(['name' => $condition])->delete($this->collection);
	}

}

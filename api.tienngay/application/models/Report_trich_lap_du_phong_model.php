
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report_trich_lap_du_phong_model extends CI_Model
{
	private $collection = 'report_prevent_bad_debt';

	public function  __construct()
	{
		parent::__construct();
	}

	public function find($condition){
		return $this->mongo_db
			->where($condition)
			->order_by(array('datetime' => 'ASC'))
			->order_by(array('group_dept' => 'ASC'))
			->order_by(array('store.id' => 'DESC'))
			->get($this->collection);
	}

}




<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Kpi_pgd_model extends CI_Model
{
	private $collection = 'kpi_pgd';

	public function  __construct()
	{
		parent::__construct();

		$this->manager = new MongoDB\Driver\Manager($this->config->item("mongo_db")['dsn'],$this->config->item("mongo_db")['options']);
		$this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
	}

	public function find(){
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
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
			return $this->mongo_db->where($condition)->get($this->collection);
	}
	public function find_where_in($field="", $in=array()){
		return $this->mongo_db
			->where_in($field, $in)->order_by(array('updated_at' => 'DESC'))->get($this->collection);
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


	public function post_api($url,$data,$headers) {
		$postdata = http_build_query(
			$data
		);
		if(empty($headers)){
			$headers = "Content-type: application/x-www-form-urlencoded\r\n";
		}
		$opts = array('http' =>
			array(
				'method' => 'POST',
				'header' => $headers,
				'content' => $postdata,
				'ignore_errors' => '1'
			)
		);
		$context = stream_context_create($opts);
		$result = file_get_contents($url, false, $context);
		$decodeResponse = json_decode($result);
		try{
			return $decodeResponse;
			// \Log::info($result);
		}catch(\Exception $e){
			return false;
		}
	}

	public function sum_where_total_mongo_read($condtion = array(), $get)
	{
		$ops = array(
			array(
				'$match' => $condtion
			),
			array(
				'$group' => array(
					'_id' => null,
					'total' => array('$sum' => $get),
				),
			),
		);
		$data = $this->mongo_db_read->aggregate($this->collection, $ops)->toArray();
		if (isset($data[0]['total'])) {
			return $data[0]['total'];
		} else {
			return 0;
		}

	}

}



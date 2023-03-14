<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
* PTI Bảo hiểm tai nạn con người model
*/
class Pti_bhtn_model extends CI_Model
{
	private $collection = 'pti_bhtn';

	public function __construct()
	{
		parent::__construct();
	}

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->order_by(array('number_item' => 'DESC'))->find_one($this->collection);
	}

	public function count($condition)
	{
		return $this->mongo_db->where($condition)->count($this->collection);
	}
	public function find_where($condition)
	{
		return $this->mongo_db
			->get_where($this->collection, $condition);
	}

	public function update($condition, $set)
	{
		return $this->mongo_db->where($condition)->set($set)->update($this->collection);
	}

	public function delete($condition)
	{
		return $this->mongo_db->where($condition)->delete($this->collection);
	}

	public function count_pti()
	{
		return $this->mongo_db
		  ->set_where(['type_pti'=>'BN'])
			->count($this->collection);
	}

	/**
	* Nếu KH đã tồn tại bảo hiểm thì lấy giá trị NGAY_KT xa nhất
	* 
	*/
	public function findNgayKTByCCCD($cccd)
	{
		$ngay_kts = $this->mongo_db->set_where(array(
			'pti_request.so_cmt' => $cccd, 
			'status' => "success", 
			
		))->select(array('pti_request.ngay_kt'))->get($this->collection);

		if (count($ngay_kts) > 0) {
			$ngay_kt = $ngay_kts[0]["pti_request"]["ngay_kt"];
			foreach ($ngay_kts as $key => $value) {
				if (strtotime($ngay_kt) < strtotime($value["pti_request"]["ngay_kt"])) {
					$ngay_kt = $value["pti_request"]["ngay_kt"];
				}
			}
			return $ngay_kt;
		}
		return null;
	}
}

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Main_property_model extends CI_Model
{

	private $collection = 'main_property';

	public function __construct()
	{
		parent::__construct();
	}

	public function insert($data)
	{
		return $this->mongo_db->insert($this->collection, $data);
	}

	public function findOne($condition)
	{
		return $this->mongo_db->where($condition)->find_one($this->collection);
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

	public function find()
	{
		return $this->mongo_db
			->order_by(array('created_at' => 'DESC'))
			->get($this->collection);
	}


	public function getPropertyValuation($searchLike, $where = array())
	{
		$mongo = $this->mongo_db;
		$order_by = [' ' => 'DESC'];
		if (isset($where['start']) && isset($where['end'])) {
			$where['create_at'] = array(
				'$gte' => $where['start'],
				'$lte' => $where['end']
			);
			unset($where['start']);
			unset($where['end']);
			unset($where['count']);
		}

		$mongo = $mongo->set_where($where);
		if (!empty($searchLike)) {
			// var_dump($field);die;
			foreach ($searchLike as $key => $value) {
				$mongo = $mongo->like($key, $value);

			}
		}
		return $mongo->order_by($order_by)->get($this->collection);
	}

	public function findPropertyByName($searchLike)
	{
		if (isset($searchLike)) {
			return $this->mongo_db->where("name", $searchLike)->get($this->collection);
		}
	}

	public function getPropertiesNoneDepreciations($data)
	{
		return $this->mongo_db->
		where(array("depreciations" => array('$exists' => false)))->get_where($this->collection, $data);

	}

	public function get_list_oto($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
//		if (!empty($condition['vehicles'])) {
//			$where['parent_id'] = $condition['vehicles'];
//		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['name_property'])) {
			$mongo = $mongo->where_text($condition['name_property'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}
		if (!empty($condition['list_id_main_oto'])) {
			$in_list_oto = $condition['list_id_main_oto'];
		}

		return $mongo->order_by($order_by)
			->where_in('parent_id', array_values($in_list_oto))
			->limit($limit)
			->offset($offset)
			->get($this->collection);


	}

	public function get_count_list_oto($condition = array())
	{
		$mongo = $this->mongo_db;
		$where = array();
//		if (!empty($condition['vehicles'])) {
//			$where['parent_id'] = $condition['vehicles'];
//		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['name_property'])) {
			$mongo = $mongo->where_text($condition['name_property'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['list_id_main_oto'])) {
			$in_list_oto = $condition['list_id_main_oto'];
		}

		return $mongo->where_in('parent_id', array_values($in_list_oto))
			->count($this->collection);

	}

	public function get_list_moto($condition = array(), $limit = 30, $offset = 0)
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
//		if (!empty($condition['vehicles'])) {
//			$where['parent_id'] = $condition['vehicles'];
//		}
		if (!empty($condition['list_id_main_moto'])) {
			$in_list_moto = $condition['list_id_main_moto'];
		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['name_property'])) {
			$mongo = $mongo->where_text($condition['name_property'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		return $mongo->order_by($order_by)
			->where_in('parent_id', array_values($in_list_moto))
			->limit($limit)
			->offset($offset)
			->get($this->collection);


	}

	public function get_count_list_moto($condition = array())
	{
		$mongo = $this->mongo_db;
		$where = array();
//		if (!empty($condition['vehicles'])) {
//			$where['parent_id'] = $condition['vehicles'];
//		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		if (!empty($condition['name_property'])) {
			$mongo = $mongo->where_text($condition['name_property'], ['$language' => 'none', '$caseSensitive' => false, '$diacriticSensitive' => false]);
		}

		if (!empty($condition['list_id_main_moto'])) {
			$in_list_moto = $condition['list_id_main_moto'];
		}

		return $mongo->where_in('parent_id', array_values($in_list_moto))
			->count($this->collection);

	}

	public function insertReturnId($data)
	{
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}

	public function get_list_moto_for_update_depreciation($condition = array())
	{
		$order_by = ['created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['list_id_main_moto'])) {
			$in_list_moto = $condition['list_id_main_moto'];
		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo->order_by($order_by)
			->where_in('parent_id', array_values($in_list_moto))
			->get($this->collection);
	}

	public function get_property_new($condition, $limit, $offset)
	{
		$order_by = ['status' => 'ASC','created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['phan_khuc_tai_san'])) {
			$where['phan_khuc'] = $condition['phan_khuc_tai_san'];
		}
		if (!empty($condition['loai_xe_tai_san'])) {
			$where['type_property'] = $condition['loai_xe_tai_san'];
		}
		if (!empty($condition['nam_san_xuat_tai_san'])) {
			$where['year_property'] = $condition['nam_san_xuat_tai_san'];
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			$where['parent_id'] = $condition['hang_xe_tai_san'];
		}
		if (!empty($condition['model_tai_san'])) {
			$where['slug_name'] = $condition['model_tai_san'];
		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			return $mongo->order_by($order_by)
				->limit($limit)
				->offset($offset)
				->get($this->collection);
		} else {
			if (!empty($condition['list_id_main'])) {
				$list_id_main = $condition['list_id_main'];
				return $mongo->order_by($order_by)
					->where_in('parent_id', array_values($list_id_main))
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			} else {
				return $mongo->order_by($order_by)
					->limit($limit)
					->offset($offset)
					->get($this->collection);
			}

		}
	}

	public function get_count_property_new($condition)
	{
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['phan_khuc_tai_san'])) {
			$where['phan_khuc'] = $condition['phan_khuc_tai_san'];
		}
		if (!empty($condition['loai_xe_tai_san'])) {
			$where['type_property'] = $condition['loai_xe_tai_san'];
		}
		if (!empty($condition['nam_san_xuat_tai_san'])) {
			$where['year_property'] = $condition['nam_san_xuat_tai_san'];
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			$where['parent_id'] = $condition['hang_xe_tai_san'];
		}
		if (!empty($condition['model_tai_san'])) {
			$where['slug_name'] = $condition['model_tai_san'];
		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			return $mongo
				->count($this->collection);
		} else {
			if (!empty($condition['list_id_main'])) {
				$list_id_main = $condition['list_id_main'];
				return $mongo
					->where_in('parent_id', array_values($list_id_main))
					->count($this->collection);
			} else {
				return $mongo
					->count($this->collection);
			}

		}
	}

	public function find_distinct_main($condition, $distinct)
	{
		$mongo = $this->mongo_db;
		return $mongo
			->where($condition)
			->distinct($this->collection, $distinct);
	}

	public function excel_property($condition)
	{
		$order_by = ['status' => 'ASC','created_at' => 'DESC'];
		$mongo = $this->mongo_db;
		$where = array();
		if (!empty($condition['phan_khuc_tai_san'])) {
			$where['phan_khuc'] = $condition['phan_khuc_tai_san'];
		}
		if (!empty($condition['loai_xe_tai_san'])) {
			$where['type_property'] = $condition['loai_xe_tai_san'];
		}
		if (!empty($condition['nam_san_xuat_tai_san'])) {
			$where['year_property'] = $condition['nam_san_xuat_tai_san'];
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			$where['parent_id'] = $condition['hang_xe_tai_san'];
		}
		if (!empty($condition['model_tai_san'])) {
			$where['slug_name'] = $condition['model_tai_san'];
		}
		if (!empty($condition['status'])) {
			$where['status'] = $condition['status'];
		}
		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		if (!empty($condition['hang_xe_tai_san'])) {
			return $mongo->order_by($order_by)
				->get($this->collection);
		} else {
			if (!empty($condition['list_id_main'])) {
				$list_id_main = $condition['list_id_main'];
				return $mongo->order_by($order_by)
					->where_in('parent_id', array_values($list_id_main))
					->get($this->collection);
			} else {
				return $mongo->order_by($order_by)
					->get($this->collection);
			}

		}
	}

}

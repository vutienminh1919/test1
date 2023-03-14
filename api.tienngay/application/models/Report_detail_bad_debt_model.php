
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report_detail_bad_debt_model extends CI_Model
{
	private $collection = 'report_detail_bad_dept';

	public function  __construct()
	{
		parent::__construct();
	}

	public function find($condition, $limit, $offset){
		return $this->mongo_db
			->where($condition)
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function findAll($condition) {
		return $this->mongo_db
			->where($condition)
			->get($this->collection);
	}

	public function count($condition) {
		return $this->mongo_db->where($condition)->count($this->collection);
	}

	public function findAggregate($operation)
	{
		return $this->mongo_db
			->aggregate($this->collection, $operation)->toArray();
	}

	public function get_thn_store($condition) {
		$ops = [];
		$query = $this->exe_condition($condition);
		if ( count($query) > 0 ) {
			array_push($ops, array(
				'$match' => $query
			));
		}
		array_push($ops, array(
			'$group' => array(
				'_id' => [
					'store' => '$store',
					'nhom_no' => '$nhom_no'
				],
				'count' => array('$sum' => '$du_no_dang_cho_vay')
			),
		));
		return $this->findAggregate($ops);
	}

	public function get_count_tien_vay($condition) {
		$ops = [];
		$query = $this->exe_condition($condition);
		if ( count($query) > 0 ) {
			array_push($ops, array(
				'$match' => $query
			));
		}
		array_push($ops, array(
			'$group' => array(
				'_id' => 'vfc',
				'count' => array('$sum' => '$so_tien_vay')
			),
		));
		$result = $this->findAggregate($ops);
		return isset($result[0]['count']) ? $result[0]['count'] : 0;
	}

	public function get_count_du_no_dang_cho_vay($condition) {
		$ops = [];
		$query = $this->exe_condition($condition);
		if ( count($query) > 0 ) {
			array_push($ops, array(
				'$match' => $query
			));
		}
		array_push($ops, array(
			'$group' => array(
				'_id' => 'vfc',
				'count' => array('$sum' => '$du_no_dang_cho_vay')
			),
		));
		$result = $this->findAggregate($ops);
		return isset($result[0]['count']) ? $result[0]['count'] : 0;
	}

	public function exe_condition($condition) {
		$query = [];
		if ( isset($condition['trang_thai']) && $condition['trang_thai'] == 'cho_vay') {
			$query['trang_thai'] = [
				'$nin' => [19],
				'$gte' => 17,
				'$lt' => 35
			];
		}
		if ( isset($condition['store']) ) {
			$listStore = explode(",", $condition['store']);
			$query['store.id'] = [
				'$in' => $listStore
			];
		}
		if ( isset($condition['nhom_no']) ) {
			$query['nhom_no'] = $condition['nhom_no'];
		}
		if ( isset($condition['vung_mien']) ) {
			$query['vung_mien'] = $condition['vung_mien'];
		}
		if ( isset($condition['fromdate']) && $condition['fromdate'] != '') {
			$query['ngay_giai_ngan'] = [
				'$gte' => strtotime($condition['fromdate'])
			];
		}
		if ( isset($condition['todate']) && $condition['todate'] != '' ) {
			$query['ngay_giai_ngan'] = [
				'$lte' => strtotime($condition['todate']. " 23:59:59")
			];
		}

		return $query;
	}

}
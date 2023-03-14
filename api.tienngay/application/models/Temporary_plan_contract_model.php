<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Temporary_plan_contract_model extends CI_Model
{

    private $collection = 'temporary_plan_contract';

    private $createdAt;

    public function  __construct()
    {
        parent::__construct();
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
	public function insertReturnId($data) {
		return $this->mongo_db->insertReturnId($this->collection, $data);
	}
    public function findOne($condition){
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function count($condition){
        return $this->mongo_db->where($condition)->count($this->collection);
    }
    public function find_where($condition){
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
    public function find_where_order_by($condition){
        return $this->mongo_db
            ->order_by(array("time_timestamp" => "ESC"))
            ->get_where($this->collection, $condition);
    }
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }
    public function delete($condition){
        return $this->mongo_db->where($condition)->delete($this->collection);
    }
    public function find(){
        return $this->mongo_db
            ->order_by(array('created_at' => 'DESC'))
            ->get($this->collection);
    }
    public function find_one_order_by($condition, $orderBy){
        return $this->mongo_db
            ->order_by($orderBy)
            ->limit(1)
            ->get_where($this->collection, $condition);
    }
    public function getBangLaiKy($contractCode) {
        //Step 1: Tìm tất cả các HĐ từ phòng giao dịch
        $conditions = [
            'aggregate' => "store",
            'pipeline' => [
                ['$lookup' =>
                    [
                        'from' => 'contract',
                        'let' => array(
                            "code_contract" => '$code_contract'
                        ),
                        'pipeline' => array(
                            array(
                                '$match' => array(
                                    '$expr' => array(
                                        '$and' => array(
                                            array(
                                                '$eq' => array('$code_contract', '$$code_contract')
                                            )
                                        )
                                    )
                                ),
                            ),
                            array(
                                '$project' => [
                                    'code_contract' => 1,
                                    'loan_infor.amount_money' => 1,
                                    'created_at' => 1
                                ]
                            )
                        ),
                        'as' => "contracts",
                    ]
                ],
                ['$project' =>
                    [
                        "name" => 1,
                    ]
                ],

            ],
            'cursor' => new stdClass,
        ];

        $command = new MongoDB\Driver\Command($conditions);
        $cursor = $this->manager->executeCommand($this->config->item("current_DB"), $command);
        return $cursor->toArray();
    }

    public function getCurrentPlan($contractCode,$date_pay) {
        $data = $this->find_one_order_by(
                array("code_contract" => $contractCode,
                       "ngay_ky_tra" => array('$lt' => $date_pay)
                   ),
                array("ngay_ky_tra" => "DESC"
        ));
        return $data;
    }
      public function getCurrentPlan_top($contractCode,$date_pay) {
        $data = $this->findOne(
                array("code_contract" => $contractCode,
                       "ngay_ky_tra" => array('$gt' => $date_pay))
            );
        return $data;
    }

    public function getKiPhaiThanhToanXaNhat($contractCode) {
        $data = $this->find_one_order_by(
                array("code_contract" => $contractCode),
                array("ngay_ky_tra" => "DESC"
        ));
        return $data;
    }
     public function getKiChuaThanhToanGanNhat($contractCode) {
        $data = $this->find_one_order_by(
                array("code_contract" => $contractCode,"status"=>1),
                array("ngay_ky_tra" => "ASC"
        ));
        return $data;
    }
    public function getKiDaThanhToanGanNhat($contractCode) {
        $data = $this->find_one_order_by(
                array("code_contract" => $contractCode,"status"=>2),
                array("ngay_ky_tra" => "DESC"
        ));
        return $data;
    }



    public function getCurrentPlanAfter($contractCode, $currentPlanId,$date_pay) {
        $condition = array(
            "code_contract" => $contractCode,
            "ngay_ky_tra" => array('$gt' => $date_pay
        ));
        $data = $this->mongo_db
                ->get_where($this->collection, $condition);
        foreach($data as $k=>$v) {
            if($v['_id'] == $currentPlanId) unset($data[$k]);
        }
        return $data;
    }

    public function getCurrentPlanBefore($contractCode, $currentPlanId,$date_pay) {
        $condition = array(
            "code_contract" => $contractCode,
            "ngay_ky_tra" => array('$lt' => $date_pay));
        $data = $this->mongo_db
                ->get_where($this->collection, $condition);
        foreach($data as $k=>$v) {
            if($v['_id'] == $currentPlanId) unset($data[$k]);
        }
        return $data;
    }
    //sau 24/11
     public function goc_chua_tra_den_thoi_diem_dao_han_2($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$gt' => $date_pay)
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_chua_tra_den_thoi_diem_dao_han' =>  array('$sum' => '$tien_goc_1ky_phai_tra')
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['goc_chua_tra_den_thoi_diem_dao_han'];
    }
    //trước 24/11
     public function goc_chua_tra_den_thoi_diem_dao_han_1($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lt' => $date_pay),
                    "status"=>2
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_chua_tra_den_thoi_diem_dao_han' =>  array('$sum' => '$tien_goc_1ky_phai_tra')
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['goc_chua_tra_den_thoi_diem_dao_han'];
    }


    public function goc_da_tra_den_thoi_diem_dao_han($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lte' => $date_pay),
                    "status"=>2
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_da_tra_den_thoi_diem_dao_han' =>  array('$sum' => '$tien_goc_1ky_phai_tra')
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['goc_da_tra_den_thoi_diem_dao_han'];
    }
    public function tong_tien_goc($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract
                    
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tong_tien_goc' =>  array('$sum' => '$tien_goc_1ky_phai_tra')
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['tong_tien_goc'];
    }
     public function tong_tien_goc_da_tra($codeContract="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract
                    
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tong_tien_goc' =>  array('$sum' => '$tien_goc_1ky_da_tra')
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['tong_tien_goc'];
    }
      public function tong_tien_phai_tra_den_thoi_diem_dao_han($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                 
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_tra_1ky_den_thoi_diem_dao_han' =>  array('$sum' => '$tien_tra_1_ky'),
          
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['tien_tra_1ky_den_thoi_diem_dao_han'];
    }
 
    public function tong_tien_phai_tra_den_thang($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lte' => $date_pay+5*24*60*60)
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_tra_1ky_den_thoi_diem_dao_han' =>  array('$sum' => '$tien_tra_1_ky'),
                    'tien_cham_tra' =>  array('$sum' => '$fee_delay_pay'),
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['tien_tra_1ky_den_thoi_diem_dao_han']+ $data[0]['tien_cham_tra'] ;
    }
     public function lai_phi_chua_tra_den_thoi_diem_hien_tai($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lt' => $date_pay),
                    "status"=>1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'lai_chua_tra_den_thoi_diem_hien_tai' =>  array('$sum' => '$tien_lai_1ky_phai_tra'),
                    'phi_chua_tra_den_thoi_diem_hien_tai' =>  array('$sum' => '$tien_phi_1ky_phai_tra')

                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }
      public function goc_lai_phi_con_lai_den_ngay_thanh_toan($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$gte' => $date_pay),  
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'lai_chua_tra' =>  array('$sum' => '$tien_lai_1ky_con_lai'),
                    'phi_chua_tra' =>  array('$sum' => '$tien_phi_1ky_con_lai'),
                    'goc_chua_tra' =>  array('$sum' => '$tien_goc_1ky_con_lai')

                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }
     public function goc_lai_phi_chua_tra($codeContract="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                   
                    
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'lai_chua_tra' =>  array('$sum' => '$tien_lai_1ky_con_lai'),
                    'phi_chua_tra' =>  array('$sum' => '$tien_phi_1ky_con_lai'),
                    'goc_chua_tra' =>  array('$sum' => '$tien_goc_1ky_con_lai')

                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }
       public function goc_lai_phi_da_tra($codeContract="",$date_pay="") {
       
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "status"=>1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'lai_da_tra' =>  array('$sum' => '$tien_lai_1ky_da_tra'),
                    'phi_da_tra' =>  array('$sum' => '$tien_phi_1ky_da_tra'),
                    'goc_da_tra' =>  array('$sum' => '$tien_goc_1ky_da_tra')

                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }

      public function tien_thua_thanh_toan($codeContract="") {

        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "status" => 1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_tra_thua' =>  array('$sum' => '$tien_goc_1ky_da_tra'),
                    'lai_tra_thua' =>  array('$sum' => '$tien_lai_1ky_da_tra'),
                    'phi_tra_thua' =>  array('$sum' => '$tien_phi_1ky_da_tra'),


                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }

     public function tien_goc_con_lai($codeContract="") {

        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_con_lai' =>  array('$sum' => '$tien_goc_1ky_con_lai'),
                    


                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['goc_con_lai'];
    }

    public function findAggregate($operation){
        return $this->mongo_db
            ->aggregate($this->collection, $operation)->toArray();
    }
    // sau 24/11
    public function lai_phi_con_no_cua_ki_tiep_theo_2($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$gt' => $date_pay)
                )
            ),
            array(
                '$project' => array(
                    'tien_lai_1ky_phai_tra' => 1,
                    'tien_phi_1ky_phai_tra' => 1,
                    'ngay_ky_tra' => 1,
                    'ky_tra' => 1
                )
            ),
            array(
                '$sort' => array(
                    'ngay_ky_tra' => 1
                )
            ),
            array(
                '$limit' => 1
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }
    //trước 24/11
    public function lai_phi_con_no_cua_ki_tiep_theo_1($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "status" => 1
                )
            ),
            array(
                '$project' => array(
                    'tien_lai_1ky_phai_tra' => 1,
                    'tien_phi_1ky_phai_tra' => 1,
                    'ngay_ky_tra' => 1,
                    'ky_tra' => 1
                )
            ),
            array(
                '$sort' => array(
                    'ngay_ky_tra' => 1
                )
            ),
            array(
                '$limit' => 1
            )
        );
        $data = $this->findAggregate($ops);
        return $data;
    }
	public function find_where1($condition){
		return $this->mongo_db
			->order_by(['created_at'=> 'DESC'])
			->get_where($this->collection, $condition);
	}
     public function get_tien_con_no($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lt' => $date_pay)
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'goc_con_lai_den_thoi_diem_thanh_toan' =>  array('$sum' => '$tien_goc_1ky_con_lai'),
                    'lai_con_lai_den_thoi_diem_thanh_toan' =>  array('$sum' => '$tien_lai_1ky_con_lai'),
                    'phi_con_lai_den_thoi_diem_thanh_toan' =>  array('$sum' => '$tien_phi_1ky_con_lai'),
                    'cham_tra_con_lai_den_thoi_diem_thanh_toan' =>  array('$sum' => '$tien_phi_cham_tra_1ky_con_lai'),
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0]['goc_con_lai_den_thoi_diem_thanh_toan']+$data[0]['lai_con_lai_den_thoi_diem_thanh_toan']+$data[0]['phi_con_lai_den_thoi_diem_thanh_toan']+$data[0]['cham_tra_con_lai_den_thoi_diem_thanh_toan'];
    }
        public function get_tien_da_tra_truoc_tat_toan_ki_tt($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "status"=>2,
                    "ngay_ky_tra" => array('$lte' => $date_pay)
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_goc_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_goc_1ky_phai_tra'),
                    'tien_lai_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_lai_1ky_phai_tra'),
                    'tien_phi_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_phi_1ky_phai_tra'),
                     'tien_cham_tra_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_phi_cham_tra_1ky_da_tra'),
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }
       public function get_tien_da_tra_truoc_tat_toan($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_goc_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_goc_1ky_da_tra'),
                    'tien_lai_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_lai_1ky_da_tra'),
                    'tien_phi_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_phi_1ky_da_tra'),
                     'tien_cham_tra_da_tra_truoc_tat_toan' =>  array('$sum' => '$tien_phi_cham_tra_1ky_da_tra'),
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }
      public function get_tien_da_tra_sau_thanh_toan($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract,
                    "ngay_ky_tra" => array('$lte' => $date_pay),
                     "status" => 2
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_goc_da_tra' =>  array('$sum' => '$tien_goc_1ky_da_tra'),
                    'tien_lai_da_tra' =>  array('$sum' => '$tien_lai_1ky_da_tra'),
                    'tien_phi_da_tra' =>  array('$sum' => '$tien_phi_1ky_da_tra'),
                    'tien_cham_tra_da_tra' =>  array('$sum' => '$tien_phi_cham_tra_1ky_da_tra'),

                    
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }
       public function get_tien_phai_tra_hop_dong($codeContract="",$date_pay="") {
        
        $ops = array(
            array (
                '$match' => array (
                    "code_contract"=> $codeContract
                   
                     
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$code_contract',
                    'tien_goc_phai_tra' =>  array('$sum' => '$tien_goc_1ky_phai_tra'),
                    'tien_lai_phai_tra' =>  array('$sum' => '$tien_lai_1ky_phai_tra'),
                    'tien_phi_phai_tra' =>  array('$sum' => '$tien_phi_1ky_phai_tra'),
                    'tien_cham_tra_phai_tra' =>  array('$sum' => '$fee_delay_pay'),

                    
                ),
            )
        );
        $data = $this->findAggregate($ops);
        return $data[0];
    }

	public function find_where_select($condition){


		return $this->mongo_db
			->order_by(array("ngay_ky_tra" => "ASC"))
			->limit(1)
			->select(['tien_tra_1_ky','ngay_ky_tra'])
			->get_where($this->collection, $condition);
	}

	public function find_where_select_excel($condition){


		return $this->mongo_db
			->order_by(array("ngay_ky_tra" => "ASC"))
			->select(['tien_tra_1_ky','ngay_ky_tra'])
			->get_where($this->collection, $condition);
	}

	public function find_where_select_check_rule($condition){

		return $this->mongo_db
			->order_by(array("ngay_ky_tra" => "ASC"))
//			->limit(1)
			->get_where($this->collection, $condition);
	}

	public function find_where_report($code_contract){
		$mongo = $this->mongo_db;
		$where = array();

		$where['code_contract'] = $code_contract;
		$where['ky_tra'] = ['$in' => [1, 2, 3]];

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->select(['status'])->get($this->collection);
	}

	public function find_where_payment_done($condition){
		return $this->mongo_db
			->order_by(['ky_tra'=> 'DESC'])
			->get_where($this->collection, $condition);
	}

}

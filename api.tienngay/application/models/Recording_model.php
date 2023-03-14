<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Recording_model extends CI_Model
{
  private $collection = 'recording';

  public function  __construct()
  {
    parent::__construct();
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
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
  public function find_Pagination_where($field="", $in=array(), $limit = 30, $offset = 0){
    return $this->mongo_db
      ->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->limit($limit)->offset($offset)->get($this->collection);
  }
  public function find_where_count($field="", $in=array()){
    return $this->mongo_db
      ->where_in($field, $in)->order_by(array('created_at' => 'DESC'))->count($this->collection);
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
   public function countByRole($condition = array()){
        $order_by = ['created_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['start']) && isset($condition['end'])){
            $where['created_at'] = array(
                '$gte' => (string)$condition['start'],
                '$lte' => (string)$condition['end']
            );
            unset($condition['start']);
            unset($condition['end']);
         }
        if(isset($condition['cskh'])){
            $where['fromUser.email'] = $condition['cskh'];
        }
        if(isset($condition['out_phone'])){
            $where['toNumber'] = $condition['out_phone'];
        }
        if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
      
            return $mongo->order_by($order_by)
                ->count($this->collection);
        
    }
 public function getByRole($condition = array()){
        $order_by = ['created_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
         if(isset($condition['start']) && isset($condition['end'])){
            $where['created_at'] = array(
                '$gte' => (string)$condition['start'],
                '$lte' => (string)$condition['end']
            );
            unset($condition['start']);
            unset($condition['end']);
         }
           if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
        
          if(isset($condition['sdt'])){
          $mongo = $mongo->or_where(array('fromNumber'=>$condition['sdt'],'toNumber'=>$condition['sdt']));
        }  
        if(isset($condition['cskh'])){
          $mongo = $mongo->or_where(array('fromUser.email'=>$condition['cskh'],'toUser.email'=>$condition['cskh']));
        }
            return $mongo->order_by($order_by)
                ->get($this->collection);
        
    }
    public function getByRole_lead($condition = array(),$limit = 30, $offset = 0){
        $order_by = ['created_at' => 'DESC'];
        $where = array();
        $in = array();
        $mongo = $this->mongo_db;
        $where['billDuration'] = array('$gte' => '1' );
        if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
        if(isset($condition['phone_number'])){
          $mongo = $mongo->or_where(array('fromNumber'=>$condition['phone_number'],'toNumber'=>$condition['phone_number']));
        }  
       
            return $mongo->order_by($order_by)
                 ->limit($limit)
                ->offset($offset)
                ->get($this->collection);
        
    }
  public function getGet_pt($condition = array(),$limit = 30, $offset = 0) {
    $order_by = ['startTime' => 'DESC'];
    $where = array();
    $in = array();
    $email = (isset($condition['email'])) ? $condition['email'] : array();
    $array_user = (isset($condition['array_user'])) ? $condition['array_user'] : array() ;
    $groupRoles = (isset($condition['groupRoles'])) ? $condition['groupRoles'] : array() ;
    $mongo = $this->mongo_db;
    if(isset($condition['start']) && isset($condition['end'])){
      $where['startTime'] = array(
        '$gte' => (string)$condition['start'],
        '$lte' => (string)$condition['end']
      );
      // unset($condition['start']);
      // unset($condition['end']);
      }
      //check ngày tháng
    else if (isset($condition['start'])) {
      $where['startTime'] =  array(
        '$gte' => (string)$condition['start'],
      );
      // unset($condition['start']);
    }
    else if (isset($condition['end'])) {
      $where['startTime'] =  array(
        '$lte' => (string)$condition['end']
      );
     // unset($condition['end']);
    }
    if(!empty($condition['missed']))
    {
      $where['fromUser'] ="";
      $where['toUser'] ="";
      $where['direction'] ="inbound";
    }
    // if(!empty($condition['email_nv']))
    // {
    //   $where['fromUser.email'] =$condition['email_nv'];
    // }
    if(!$condition['is_cskh'])
    {
      $where['fromGroup.name']=array('$ne'=>'Telesales');
    //           $where['fromUser.ext']=array('$nin'=>['10001','10002','10003','10004','10005','10006','10007','10008','10000','30062']);
    }
	  if($condition['phone_name'])
	  {
		  $where['toNumber']=$condition['phone_name'];

	  }
    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }

    //search_name_kh_by_phone_number
    if (!empty($condition['phone'])) {
      $mongo = $mongo->where_in('toNumber', $condition['phone']);
    }
    // check xem có thuộc nhóm cskh ko
    if (isset($condition['inbound'])) {
      // $time = time() - 15*24*60*60; // 15 ngày về trước tính từ ngày hiện tại
      if (!empty($condition['email_nv']) && in_array($condition['email_nv'], $condition['inbound'])) {
        if (isset($condition['start']) && isset($condition['end'])) {
          $where['startTime'] = [
            '$gte' => (string)$condition['start'],
            '$lte' => (string)$condition['end']
          ];

        } else if (isset($condition['start'])) {
          $where['startTime'] = [
            '$gte' => (string)$condition['start'],
          ];
        } else if (isset($condition['end'])) {
          $where['startTime'] = [
            '$lte' => (string)$condition['end']
          ];
        }
        $mongo = $mongo->or_where([
          'fromUser.email' => ['$in' => $condition['email']],
          'toUser.email' => ['$in' => $condition['email']],
        ]);
      } else if(!empty($condition['email_nv']) && !in_array($condition['email_nv'], $condition['inbound'])) {
        $arr = [
          "status" => "active",
          "hangupCause" => "ORIGINATOR_CANCEL",
          "direction" => "inbound",
          "billDuration" => "0",
          // "startTime" => ['$gte' => (string)$time],
          'toUser.email' => $condition['email_nv'],
          // 'toExt' => ['$regex' => '^([0-9][0-9][0-9])$'],
        ];
        if (isset($condition['start']) && isset($condition['end'])) {
          $arr['startTime'] = [
            '$gte' => (string)$condition['start'],
            '$lte' => (string)$condition['end']
          ];
          unset($condition['start']);
          unset($condition['end']);
        } else if (isset($condition['start'])) {
          $arr['startTime'] = ['$gte' => (string)$condition['start']];
          unset($condition['start']);
        } else if (isset($condition['end'])) {
          $arr['startTime'] = ['$lte' => (string)$condition['end']];
          unset($condition['end']);
        } else {
          // $arr['startTime'] = ['$gte' => (string)$time];
        }
        $mongo = $mongo->where($arr);
      } else {
        $arr = [

              'fromUser.email' => ['$in' => $condition['email']], 
              'toUser.email' => ['$in' => $condition['email']],
              // '$and' => [
                // ["status" => "active"],
              // "hangupCause" => "ORIGINATOR_CANCEL",
              //   ["direction" => "inbound"],
              //   ["billDuration" => "0"],
              //   // ["startTime" => ['$gte' => (string)$time]],
                "toExt" => ['$regex' => '^([0-9][0-9][0-9])$'],
              // ],
          ];

        $mongo = $mongo->or_where($arr);
      }


    } else if (!empty($condition['email'])) {
      $mongo = $mongo->or_where([
        'fromUser.email' => ['$in' => $condition['email']],
        'toUser.email' => ['$in' => $condition['email']],
      ]);
    }
    if(isset($condition['sdt'])){
      $mongo = $mongo->or_where(array('fromNumber'=>$condition['sdt'],'toNumber'=>$condition['sdt']));
    }
    if(empty($condition['missed']))
    {
      if(isset($condition['cskh'])){
        $mongo = $mongo->or_where(array('fromUser.email'=>$condition['cskh'],'toUser.email'=>$condition['cskh']));
      }

    }
    if (in_array('cua-hang-truong', $groupRoles) && !in_array('quan-ly-khu-vuc', $groupRoles)) {
      if(!empty($array_user))
      {
        $in=$array_user; 
      } else {
         $in=['***@***.vn'];

      }
    }

    if(empty($condition['count']))
    {

      if (isset($condition['email']) && $condition['email'] == false) {
        return [];
    }
    if (isset($condition['phone']) && $condition['phone'] == false) {
      return [];
    }
      return $mongo->order_by($order_by)
        ->limit($limit)
        ->offset($offset)
        ->get($this->collection);
    } else {

        if (isset($condition['email']) && $condition['email'] == false) {
          return 0;
      }
      if (isset($condition['phone']) && $condition['phone'] == false) {
          return 0;
    }
      return $mongo->order_by($order_by)
      ->count($this->collection);
}

      }
     public function getGet_pt_total($condition = array()){
        $order_by = ['created_at' => 'DESC'];
        $where = array();
        $in = array();
        $email = (isset($condition['email'])) ? $condition['email'] : '' ;
        $array_user = (isset($condition['array_user'])) ? $condition['array_user'] : array() ;
         $groupRoles = (isset($condition['groupRoles'])) ? $condition['groupRoles'] : array() ;
        $mongo = $this->mongo_db;
         if(isset($condition['start']) && isset($condition['end'])){
            $where['created_at'] = array(
                '$gte' => (string)$condition['start'],
                '$lte' => (string)$condition['end']
            );
            unset($condition['start']);
            unset($condition['end']);
         }
           if (!empty($where)) {
            $mongo = $mongo->set_where($where);
        }
        
          if(isset($condition['sdt'])){
          $mongo = $mongo->or_where(array('fromNumber'=>$condition['sdt'],'toNumber'=>$condition['sdt']));
        }  
        if(isset($condition['cskh'])){
          $mongo = $mongo->or_where(array('fromUser.email'=>$condition['cskh'],'toUser.email'=>$condition['cskh']));
        }
            if(empty($email))
            {
                if (in_array('tbp-cskh', $groupRoles) || in_array('tbp-thu-hoi-no', $groupRoles)) {
                  $in=$array_user;
                 }
               
           }else{
             $in=$email;
                 
                //return $email;
                 }
                 if(empty($in))
                 {
                  return $mongo->order_by($order_by)
                ->count($this->collection);
                 }else{
            return $mongo->order_by($order_by)
               ->where_in('fromUser.email',$in,'toUser.email',$in)
                ->count($this->collection);
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

  public function get_where_thn($condition, $limit = 30, $offset = 0)
  {
    $order_by = ['startTime' => 'DESC'];
    $mongo = $this->mongo_db;
    $where = array();

    if (isset($condition['fdate']) && isset($condition['tdate'])) {
      $where['startTime'] = array(
        '$gte' => (string)$condition['fdate'],
        '$lte' => (string)$condition['tdate']
      );
      unset($condition['fdate']);
      unset($condition['tdate']);
    }
    if (!empty($condition['hangupCause_search'])) {
      $where['hangupCause'] = $condition['hangupCause_search'];
    }

    if (!empty($condition['email'])) {
      $where['fromUser.email'] = array('$in' => $condition['email']);
    }

    if (!empty($condition['email_thn'])) {
      $where['fromUser.email'] = $condition['email_thn'];
    }

    if (!empty($condition['get_call'])) {
      if ($condition['get_call'] == "3"){
        $where['billDuration'] = array(
          '$gte' => 3,
          '$lt' => 10
        );
      }
      if ($condition['get_call'] == "10"){
        $where['billDuration'] = array(
          '$gte' => 10,
        );
      }
    }

    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }
    return $mongo
      ->order_by($order_by)
      ->select(['fromUser.email','toNumber','startTime','endTime','billDuration','hangupCause'])
      ->limit($limit)
      ->offset($offset)
      ->get($this->collection);

  }

  public function get_where_thn_excel($condition){

    $order_by = ['startTime' => 'DESC'];
    $mongo = $this->mongo_db;
    $where = array();

    if (isset($condition['fdate']) && isset($condition['tdate'])) {
      $where['startTime'] = array(
        '$gte' => (string)$condition['fdate'],
        '$lte' => (string)$condition['tdate']
      );
      unset($condition['fdate']);
      unset($condition['tdate']);
    }
    if (!empty($condition['hangupCause_search'])) {
      $where['hangupCause'] = $condition['hangupCause_search'];
    }

    if (!empty($condition['email'])) {
      $where['fromUser.email'] = array('$in' => $condition['email']);
    }

    if (!empty($condition['email_thn'])) {
      $where['fromUser.email'] = $condition['email_thn'];
    }

    if (!empty($condition['get_call'])) {
      if ($condition['get_call'] == "3"){
        $where['billDuration'] = array(
          '$gte' => 3,
          '$lt' => 10
        );
      }
      if ($condition['get_call'] == "10"){
        $where['billDuration'] = array(
          '$gte' => 10,
        );
      }
    }

    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }
    return $mongo
      ->order_by($order_by)
      ->select(['fromUser.email','toNumber','startTime','endTime','billDuration','hangupCause'])
      ->get($this->collection);


  }

  public function get_where_thn_count($condition)
  {
    $mongo = $this->mongo_db;
    $where = array();

    if (isset($condition['fdate']) && isset($condition['tdate'])) {
      $where['startTime'] = array(
        '$gte' => (string)$condition['fdate'],
        '$lte' => (string)$condition['tdate']
      );
      unset($condition['fdate']);
      unset($condition['tdate']);
    }

    if (!empty($condition['email'])) {
      $where['fromUser.email'] = array('$in' => $condition['email']);
    }
    if (!empty($condition['email_thn'])) {
      $where['fromUser.email'] = $condition['email_thn'];
    }
    if (!empty($condition['hangupCause_search'])) {
      $where['hangupCause'] = $condition['hangupCause_search'];
    }

    if (!empty($condition['get_call'])) {
      if ($condition['get_call'] == "3"){
        $where['billDuration'] = array(
          '$gte' => 3,
          '$lt' => 10
        );
      }
      if ($condition['get_call'] == "10"){
        $where['billDuration'] = array(
          '$gte' => 10,
        );
      }
    }

    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }
    return $mongo
      ->count($this->collection);

  }

  public function get_where_thn_dashboard($condition){
    $mongo = $this->mongo_db;
    $where = array();

    if (isset($condition['fdate']) && isset($condition['tdate'])) {
      $where['startTime'] = array(
        '$gte' => (string)$condition['fdate'],
        '$lte' => (string)$condition['tdate']
      );
      unset($condition['fdate']);
      unset($condition['tdate']);
    }

    if (!empty($condition['email'])) {
      $where['fromUser.email'] = array('$in' => $condition['email']);
    }
    if (!empty($condition['email_thn'])) {
      $where['fromUser.email'] = $condition['email_thn'];
    }
    if (!empty($condition['hangupCause'])) {
      $where['hangupCause'] = $condition['hangupCause'];
    }
    if (!empty($condition['hangupCause_search'])) {
      $where['hangupCause'] = $condition['hangupCause_search'];
    }

    if (!empty($condition['get_call'])) {
      if ($condition['get_call'] == "3"){
        $where['billDuration'] = array(
          '$gte' => 3,
          '$lt' => 10
        );
      }
      if ($condition['get_call'] == "10"){
        $where['billDuration'] = array(
          '$gte' => 10,
        );
      }
    }
    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }
    return $mongo
      ->count($this->collection);
  }

  public function find_select($list){
    $mongo = $this->mongo_db;
    $where = array();

    $where['fromUser.email'] = array('$in' => $list);

    if (!empty($where)) {
      $mongo = $mongo->set_where($where);
    }

    return $mongo
      ->select(['billDuration'])
      ->get($this->collection);
  }

  public function find_one_check_phone_vbee($condition)
	{
		$order_by = ['created_at' => 'DESC'];
		$where = array();
		$mongo = $this->mongo_db;

		if (!empty($condition)) {
			$where['fromNumber'] = $condition;
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}
		return $mongo
			->where(array("toExt" => ['$ne'=> '299']))
			->order_by($order_by)
			->get($this->collection);
	}

	public function get_missed_call($condition,$limit,$offset)
	{
		$mongo = $this->mongo_db;
		$where = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => (string)$condition['start'],
				'$lte' => (string)$condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (!empty($condition['sdt'])) {
			$where['fromNumber'] =  $condition['sdt'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}


		return $mongo->where(array("missed_call" => ['$in'=> ["1" ,"2","3"]]))
			->order_by(array('created_at' => 'DESC'))
			->limit($limit)
			->offset($offset)
			->get($this->collection);
	}

	public function get_count_miss_call($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => (string)$condition['start'],
				'$lte' => (string)$condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}

		if (!empty($condition['sdt'])) {
			$where['fromNumber'] =  $condition['sdt'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->where(array("missed_call" => ['$in'=> ["1" ,"2","3"]]))
			->count($this->collection);
	}

	public function get_missed_call_excel($condition)
	{
		$mongo = $this->mongo_db;
		$where = [];
		if (isset($condition['start']) && isset($condition['end'])) {
			$where['created_at'] = array(
				'$gte' => (string)$condition['start'],
				'$lte' => (string)$condition['end']
			);
			unset($condition['start']);
			unset($condition['end']);
		}


		if (!empty($condition['sdt'])) {
			$where['fromNumber'] =  $condition['sdt'];
		}

		if (!empty($where)) {
			$mongo = $mongo->set_where($where);
		}

		return $mongo->where(array("missed_call" => ['$in'=> ["1" ,"2","3"]]))
			->get($this->collection);
	}

}



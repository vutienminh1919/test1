<?php
class Session_model extends CI_Model{
    function __construct(){
        parent::__Construct();
        $this->collection = 'sessions';
    }
    private $collection;
    public function getSessionLeaseTime($api_key, $secret){
        $data = $this->findOne(array(
            'api_key' => $api_key,
            'secret' => $secret
        ));

        $lease_time = $data['lease_time'];
        $time_real = new MongoDB\BSON\UTCDateTime(strtotime(date("Y-m-d H:i:s")));
        $compare_time = strtotime($lease_time->toDateTime()->format('Y-m-d H:i:s'))- strtotime($time_real->toDateTime()->format('Y-m-d H:i:s'));
        if ($compare_time > 0) return true;
        else return false;
    }
    public function checkExists($accessToken) {
        $data = array(
            'access_token' => $accessToken
        );
        return $this->findOne($data);
    }
    public function count($where){
        return $this->mongo_db->where($where)->count($this->collection);
    }
    public function find(){
        return $this->mongo_db->get($this->collection);
    }
    public function find_where($condition){
        return $this->mongo_db
            ->get_where($this->collection, $condition);
    }
    public function findOne($condition){
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }
    public function updateAccessToken($condition, $data, $ins) {
        if($this->count($condition)==0)
            return $this->insert($ins);
        return $this->update($condition, $data);
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function update($condition, $set){
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }

//    public function getCheckAccessTokenExist($merchant_id, $access_token){
//        $data = $this->collection->findOne(array(
//            'merchant_id' => $merchant_id,
//            'access_token' => $access_token
//        ));
//
//        if (isset($data)) {
//            return TRUE;
//        } else {
//            return FALSE;
//        }
//    }
//
//    public function getAccessToken($merchant_id) {
//        $data = $this->collection->findOne(array(
//            'merchant_id' => $merchant_id
//        ));
//
//        $access_token = $data['access_token'];
//        $lease_time = $data['lease_time'];
//
//        $list = array (
//            'access_token' => $access_token ,
//            'lease_time' => $lease_time
//        );
//
//        return $list;
//    }
//

//
//    public function insertSession ($list) {
//        return $this->collection->insert($list);
//    }
//

//
//    public function checkExistsByPrivateKey($merchantId, $privateKey) {
//        return $this->collection->findOne(array(
//            'merchant_id' => $merchantId,
//            'private_key' => $privateKey
//        ));
//    }
}
?>

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class History_model extends CI_Model
{
    public function  __construct()
    {
        parent::__construct();
        $this->collection = 'history';
        $this->createdAt = $this->time_model->convertDatetimeToTimestamp(new DateTime());
    }
    private $collection, $createdAt;

    public function insert($data)
    {
        return $this->mongo_db->insert($this->collection, $data);
    }
    public function findTop($limit=1, $condition, $offset=1){
        $arr = $this->mongo_db
            ->order_by(array('created_at' => 'desc'))
            ->limit($limit)
            ->offset($offset)
            ->get_where($this->collection, $condition);
        return $arr;
    }
    public function find_where($condition)
    {
        return $this->mongo_db
        ->order_by(array('created_at' => 'DESC'))
        ->get_where($this->collection, $condition);
    }

    public function findOne($condition)
    {
        return $this->mongo_db->where($condition)->find_one($this->collection);
    }

    public function update($condition, $set)
    {
        return $this->mongo_db->where($condition)->set($set)->update($this->collection);
    }

    public function delete($condition)
    {
        return $this->mongo_db->where($condition)->delete($this->collection);
    }

    public function count($where)
    {
        return $this->mongo_db->where($where)->count($this->collection);
    }
    
    public function getHistory($inputs, $limit = 20, $offset = 0) {
        if(isset($inputs['count']) && $inputs['count']) {
            return count($this->find_where(array('user_id' => $inputs['user_id'])));
        }
        return $this->mongo_db
                    ->order_by(array('created_at' => 'DESC'))
                    ->limit($limit)
                    ->offset($offset)
                    ->get_where($this->collection, array('user_id' => $inputs['user_id']));
    }
    function historyAppLogin($id) {
        $this->user_model->update(
            array('_id'=> $id),
            array('app_login' => true)
        );
        $device = 'App';
        $ipAddress = explode (',', getIpAddress());
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ipAddress[0]}/json"));
        $this->history_model->insert(array(
            'user_id' => $id,
            'created_at' => $this->createdAt,
            'type' => 'Login',
            'ip_address' => $ipAddress[0],
            'device' => $device,
            'location' => $details->city
        ));
    }
}
?>
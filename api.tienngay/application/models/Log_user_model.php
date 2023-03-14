<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_user_model extends CI_Model
{

    private $collection = 'log_user';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    
}

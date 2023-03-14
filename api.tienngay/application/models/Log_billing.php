<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_billing extends CI_Model
{

    private $collection = 'log_billing';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    
}
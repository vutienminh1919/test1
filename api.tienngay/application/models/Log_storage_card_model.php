<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Log_storage_card_model extends CI_Model
{

    private $collection = 'Log_storage_card';

    public function  __construct()
    {
        parent::__construct();
    }
    public function insert($data){
        return $this->mongo_db->insert($this->collection, $data);
    }
    
}

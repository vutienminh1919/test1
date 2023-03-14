<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Originaly CodeIgniter i18n library by Jérôme Jaglale
 * http://maestric.com/en/doc/php/codeigniter_i18n
 */

/**
 * If you use without  the HMVC modular extension uncomment this and remove other lines load the MX_Loader
 */
class MY_Model extends CI_Model
{
    
    /**
     * Insert function to user model
     * 
     * @param array $data
     * @return bool
     */
    public function insert($data = array()){
        if(empty($data)){
            return false;
        }
        return $this->mongo_db->insert($this->collection, $data);
    }
    
    /**
     * Update colecttion
     *
     * @param array $wheres 
     * @param array $sets 
     * @return bool
     */
    public function update($wheres = array(), $sets = array()){
        return $this->mongo_db->where($wheres)->set($sets)->update($this->collection);
    }
    /**
     *  Select from collection
     * 
     * @param array $param
     * @return array
     */
    public function select($param = []) {
        return $this->mongo_db
                ->where($param)
                ->find_one($this->collection);
    }
}

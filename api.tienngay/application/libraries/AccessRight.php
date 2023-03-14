<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class AccessRight {
    public function checkRoleByUserId($accessRight="", $userId, $is_superadmin=0) {
        $haveRole = false;
        $this->CI =& get_instance();
        $this->CI->load->model('role_model');
        //1. Get role by userId
        $roles = $this->CI->role_model->getRoleByUserId($userId);
        //2. Check role
        if(in_array($accessRight, $roles['role_access_rights']) || $is_superadmin == 1) {
            $haveRole = true;
        }
        return $haveRole;
    }
}
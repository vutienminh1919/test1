<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class TripleDes {
    function Encrypt($data, $encryptkey, $method = 'AES-256-ECB')
    {
        $key = hash('sha256', $encryptkey);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);		
        // For storage/transmission, we simply concatenate the IV and cipher text
        $encrypted = base64_encode($iv . $encrypted);
        return $encrypted;
    }

    function Decrypt($data, $encryptkey, $method = 'AES-256-ECB')
    {
        $key = hash('sha256', $encryptkey);
        $data = base64_decode($data);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivSize);
        $data = openssl_decrypt(substr($data, $ivSize), $method, $key, OPENSSL_RAW_DATA, $iv);
        return $data;
    }
    
}
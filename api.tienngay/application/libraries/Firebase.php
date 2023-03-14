<?php
/**
 * Created by PhpStorm.
 * User: levan
 * Date: 4/22/2018
 * Time: 11:09 AM
 */

class Firebase{
    public function __construct(){
        $this->CI =& get_instance();
    }
    public function sendGCM($message, $id){
        $url = $this->CI->config->item('FCM_url');

        $fields = array (
            'registration_ids' => array (
                $id
            ),
            'data' => array (
                "message" => $message
            )
        );
        $fields = json_encode ( $fields );

        $headers = array (
            'Authorization: key=' . $this->CI->config->item('FCM_server_key'),
            'Content-Type: application/json'
        );

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

        $result = curl_exec ( $ch );
        echo $result;
        curl_close ( $ch );
    }
}
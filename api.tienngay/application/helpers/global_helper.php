<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
function getIpAddress()
{
    $ip = getenv('HTTP_CLIENT_IP') ?: getenv('HTTP_X_FORWARDED_FOR')?:getenv('HTTP_X_FORWARDED')?:getenv('HTTP_FORWARDED_FOR')?:getenv('HTTP_FORWARDED')?:getenv('REMOTE_ADDR');
    return $ip;
}
use Restserver\Libraries\REST_Controller;
use Twilio\Rest\Client;
function notify_token($flag){
    $CI =& get_instance();
    if ($flag == 1){
        $response = array(
            'status' => REST_Controller::HTTP_NOT_ACCEPTABLE,
            'message' => 'Token is invalid!'
        );
        $CI->set_response($response, REST_Controller::HTTP_NOT_ACCEPTABLE);
        return false;
    }
    else if($flag == 2){
        $response = array(
            'status' => REST_Controller::HTTP_NOT_ACCEPTABLE,
            'message' => 'Expired Token!'
        );
        $CI->set_response($response, REST_Controller::HTTP_NOT_ACCEPTABLE);
        return false;
    }
    return true;
}


function sendSMS($to, $message, $random_number) {
    $CI =& get_instance();
    $client = new Client($CI->config->item('sms_sid'),$CI->config->item('sms_token'));
    $data = array(
        'body'  => $message. " " .$random_number,
        'from'    => $CI->config->item('sms_from'),
    );
    if ($client->messages->create($to, $data)) {
        return true;

    } else {
        return false;
    }
}
function reCaptChar($g_response)
{
    $CI = &get_instance();
    $CI->load->config('config');
    $url_captchar = 'https://www.google.com/recaptcha/api/siteverify';
    $postdata = http_build_query(
        array(
            'secret'=> $CI->config->item('google_captcha_secret'),
            'response'=> $g_response,
            'remoteip'=> $_SERVER['REMOTE_ADDR']
        )
    );
    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata,
        ),
        'ssl' => array(
            'verify_peer' => false,
        ),
    );
    $context  = stream_context_create($opts);
    stream_context_set_default([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    return file_get_contents($url_captchar, false, $context);
}
if(!function_exists('str_random')){
    function str_random($length = 16){
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

}


function convertToMongoObject($arr) {
    $new = array();
    foreach($arr as $item) {
        array_push($new, new MongoDB\BSON\ObjectId($item));
    }
    return $new;
}

function slugify($text) {
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // transliterate
    $text = vn_to_str($text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // trim
    $text = trim($text, '-');
    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // lowercase
    $text = strtolower($text);
    if (empty($text)) {
      return 'n-a';
    }
    return $text;
}

function vn_to_str ($str){
    $unicode = array(
    'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
    'd'=>'đ',
    'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
    'i'=>'í|ì|ỉ|ĩ|ị',
    'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
    'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
    'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
    'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
    'D'=>'Đ',
    'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
    'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
    'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
    'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
    'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    );
    foreach($unicode as $nonUnicode=>$uni){
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    $str = str_replace(' ','_',$str);
    return $str;
}

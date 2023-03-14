<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Time_model extends CI_Model
{
    private $time;
    private $timestamp;
    private $set_exptime;
    private $now_time;
    function __construct()
    {
        parent::__construct();
		date_default_timezone_set('UTC');
    }

    public function create_date($timezone =+ 0)
    {
        try {
            $time = gmdate("Y/m/j H:i:s", time() + 3600*($timezone+date("I")));
        } catch (Exception $ex) {
            $time = 'now';
        }
        return $time;
    }
    public function checkTokenTime($token_time = '')
    {
        $now_time = $this->convertDatetimeToTimestamp(new DateTime());
        $set_exptime = 60*60*24;
        $expited = $now_time - $token_time;
        if ($expited > $set_exptime) {
            return false;
        } else {
            return true;
        }
    }
    public function exptime($token_time = '', $set_exptime = 60)
    {
        $now_time = $this->convertDatetimeToTimestamp(new DateTime());
        $expited = $now_time - $token_time;
        if ($expited < $set_exptime) {
            return false;
        } else {
            return true;
        }
    }
    public function subTime($set_exptime = 86400)
    {
        $now_time = $this->convertDatetimeToTimestamp(new DateTime());
//        $set_exptime = 60*60*24;
        $expited = $now_time - $set_exptime;
        return $expited;
    }
    public function convertDatetimeToTimestamp($datetime = '')
    {
        try {
            $timestamp = $datetime->format('U');
        } catch (Exception $ex) {
            $timestamp = '';
        }
        return (int)$timestamp;
    }

    public function timeNowStamp($datetime  = '')
    {
        try {
            $timestamp = $datetime->format('U');
        } catch (Exception $ex) {
            $timestamp = '';
        }
        return $timestamp;
    }

    public function convertTimestampToDatetime($timestamp  = '')
    {
        return date('Y/m/d H:i:s', $timestamp);
    }

    public function getTimePeriod($timeStampNow  = '', $timeStampPosted  = '')
    {
        $time = $timeStampPosted - $timeStampNow;
        return $time;
    }
}
?>

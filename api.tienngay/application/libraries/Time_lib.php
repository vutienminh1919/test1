<?php

class Time_lib{

    private $timeZone;
    private $strTimeZone = 'America/New_York';
    private $timestamp;
    /**
     * Time constructor.
     */
    public function __construct()
    {
        $this->timeZone =  new DateTimeZone($this->strTimeZone);
    }

    public function now()
    {
        $this->timestamp = (new DateTime('now',$this->timeZone))->getTimestamp();
        return $this;
    }

    public function addSecond($second = 1)
    {
        $this->timestamp += $second;
        return $this;
    }
    public function addMinute($minute = 1)
    {
        return $this->addSecond($minute * 60);
    }
    public function addHour($hour = 1)
    {
        return $this->addMinute($hour * 60);
    }

    /**
     * @param string $timeZone
     */
    public function setTimeZone($timeZone = 'Asia/Ho_Chi_Minh')
    {
        $this->strTimeZone = $timeZone;
        $this->timeZone = new DateTimeZone($this->strTimeZone);
    }
    /**
     * @param int $timestamp
     * @return Time
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = (new DateTime())
            ->setTimestamp($timestamp)
            ->setTimezone($this->timeZone)
            ->getTimestamp();
        return $this;
    }

    public function setTime($hour = 0, $minute = 0, $second = 0,$month = null, $day = null, $year = null)
    {
        if(is_null($day)){
            $day = date("d");
        }
        if(is_null($month)){
            $month = date("m");
        }
        if(is_null($year)){
            $year = date("Y");
        }
        date_default_timezone_set($this->strTimeZone);
        $this->timestamp = mktime($hour,$minute,$second,$month,$day,$year);
        return $this;
    }
    /**
     * @param DateTime $datetime
     * @return Time
     */
    public function setDate($datetime)
    {
        $this->timestamp = $datetime->setTimezone($this->timeZone)->getTimestamp();
        return $this;
    }

    public function setTimeFromString($time, $format = 'm/d/Y',$timezone = null)
    {
        //Create a DateTime object from the YYYY-MM-DD format.
        if($timezone == null){
            $timezone = $this->timeZone;
        }
        $dateObj= DateTime::createFromFormat($format, $time,$timezone);
        $this->timestamp = $dateObj->getTimestamp();
        return $this;
    }

    /**
     * @param MongoDate $time
     * @return Time
     */
    public function setMongoTime($time)
    {
        $timezoneOffset = $this->timezoneOffset($this->strTimeZone,'UTC');
        $date = $time->toDateTime()->modify(($timezoneOffset/3600).' hour');
        return $this->setDate($date);
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }



    public function getStartOfDay()
    {
        date_default_timezone_set($this->strTimeZone);
        $timestamp = mktime(0,0,0,date('m',$this->timestamp),date('d',$this->timestamp),date('Y',$this->timestamp));
        $time = new Time();
        return $time->setTimestamp($timestamp);
    }
    public function getEndOfDay()
    {
        date_default_timezone_set($this->strTimeZone);
        $timestamp = mktime(23,59,59,date('m',$this->timestamp),date('d',$this->timestamp),date('Y',$this->timestamp));
        $time = new Time();
        return $time->setTimestamp($timestamp);
    }
    /**
     * @return DateTime
     */
    public function toDateTime()
    {
        $date = new DateTime();
        $date->setTimeZone($this->timeZone);
        return $date->setTimestamp($this->timestamp);
    }

    public function toString($format = 'd-m-Y H:i:s')
    {
        return (new DateTime())
            ->setTimestamp($this->timestamp)
            ->setTimezone($this->timeZone)
            ->format($format);
    }

    public function toMongoDate()
    {
        $timezoneOffset = $this->timeZoneOffset('UTC',$this->strTimeZone);
        $datetime = (new DateTime())
            ->setTimestamp($this->timestamp)->modify(($timezoneOffset/3600).' hour')
            ->setTimezone(new DateTimeZone('UTC'));
        return (new MongoDB\BSON\UTCDateTime($datetime));
    }

    private function timeZoneOffset($remote_tz, $origin_tz = null) {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new DateTimeZone($origin_tz);
        $remote_dtz = new DateTimeZone($remote_tz);
        $origin_dt = new DateTime("now", $origin_dtz);
        $remote_dt = new DateTime("now", $remote_dtz);
        $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
        return $offset;
    }
}
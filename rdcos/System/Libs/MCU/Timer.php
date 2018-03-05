<?php
namespace MCU;
/**
* 定时器
*/
class Timer
{
    public static function add($interval,$fun)
    {
       return \Workerman\Lib\Timer::add($interval,$fun); 
    }

    public static function del($index)
    {
       return \Workerman\Lib\Timer::del($index); 
    }

    public static function one($interval,$fun)
    {
    	return \Workerman\Lib\Timer::add($interval,$fun,[],false);
    }
}
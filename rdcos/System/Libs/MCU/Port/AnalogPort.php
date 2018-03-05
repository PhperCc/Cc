<?php
namespace MCU\Port;

/**
 * I2cPort
 * @number string PIN 序号
 * @#coefficient int 转换系数 1
 * @#interval int 获取间隔 1
 */

class AnalogPort extends PortBase
{
    public $addr = null;
    public $coefficient = 1;
    public function __construct($option)
    {

        if(empty($option['number']))
        {
            self::$statistics['throw_exception']++;
            return false;
        }

        if(!empty($option['coefficient']))
        {
            $this->coefficient = $option['coefficient'];
        }

        self::$statistics['connection_count']++;
        $this->addr = $option['number'];

    }

    public function push($data)
    {
        $val = iio_adc(1.8, 'iio:device0', $this->addr);
        $res = $val*$this->coefficient;
        try {
            call_user_func($this->onMessage, $res);
        } catch (\Exception $e) {
            self::$statistics['throw_exception']++;
            if($this->onError!=null){
                call_user_func($this->onError,$e);
            }
            exit(250);
        } catch (\Error $e) {
            self::$statistics['throw_exception']++;
            if($this->onError!=null){
                call_user_func($this->onError,$e);
            }
            exit(250);
        }
    }

    public function __destruct()
    {
        self::$statistics['connection_count']--;
    }
}
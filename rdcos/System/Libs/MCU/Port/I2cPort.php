<?php
namespace MCU\Port;

/**
 * I2cPort
 * @addr string 查询地址
 * @#interval int 获取间隔 1
 */

class I2cPort extends PortBase
{
    //i2cget -f -y 0 0x24 0x0A b
    public $addr = null;
    public function __construct($option)
    {

        if(empty($option['addr']))
        {
            self::$statistics['throw_exception']++;
            return false;
        }
        self::$statistics['connection_count']++;
        $this->addr = $option['addr'];

    }

    public function push($data)
    {
        if($data != null)
        {
        	$res = \MCU\Utils\OperationSystem::exec('i2cget -f -y 0 '.$data.' b');
        }
        else
        {
        	$res = \MCU\Utils\OperationSystem::exec('i2cget -f -y 0 '.$this->addr.' b');
        }

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
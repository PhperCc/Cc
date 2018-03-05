<?php
use MCU\Sensor\SensorBase;

class Power extends SensorBase {

	protected $portType = 'I2cPort';
    protected $portOption = ['addr'=>'0x24 0x0A']; 
    protected $mode = 'passivity';
    protected $autoSave = false;

    private $CacheTime = 0;

    public function init()
    {
        $this->name = __CLASS__;
    }

    public function onMessage($data)
    {
        // 返回内容类似:  "0x88\n"
        $byteText = trim($data);
        $byteValue = hexdec($byteText);
        $powerSupply = ($byteValue >> 3) & 1;    // 第三位标识是否接外电, 1: 已接外电 0: 未接外电

        if($powerSupply == 0)
        {
            $this->CacheTime++;
        }
        else
        {
            $this->CacheTime = 0;
        }

        if($this->CacheTime>30)
        {
            \Channel\Client::publish('SYS.PowerOff', 'shutdown');
            MCU\Cache::save();
            \Channel\Client::publish('CoreSystemCommand', 'shutdown');
            //Utils\OperationSystem::power_off('power supply off > 30s');
        }   
    }   
}
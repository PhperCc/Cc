<?php
use MCU\Sensor\SensorBase;

class PulseRange extends SensorBase
{
    protected $portType = 'ModbusRTUPort';
    protected $mode = 'passivity';
    protected $name = 'PulseRange';
    protected $_repeat_command = [0x03,0x00,0x7D];

   
    public function init()
    {
        $this->interval = $this->getConfig('interval', 1);
        
        $this->portOption['name'] = $this->getConfig('port', '/dev/ttyO3');
        $this->portOption['baud'] = $this->getConfig('baud', 9600);
        $this->portOption['addr'] = $this->getConfig('addr', 0x01); // 数据地址
        $this->portOption['format'] = 125; //查询125个地址
    }

    public function decode($data)
    {
        $newData = [];
        foreach ($data as $k => $v) {
            if($v>0)
            {
              $newData[$k+1] = $v;
            }
        }
        return $newData;
    }
}
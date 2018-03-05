<?php
use MCU\Sensor\SensorBase;

class ShutdownKey extends SensorBase
{
    protected $portType = 'GpioPort';
    protected $autoSave = false;

    private $sleep_ms = 100;
    private $max_hit_count = 0;
    private $hit_count = 0;

    public function init()
    {
        $this->name = __CLASS__; 
        $this->portOption['port_num'] = $this->getConfig('gpio_number', 72);
        $power_off_wait_second = $this->getConfig('power_off_wait_second', 3);
        $this->max_hit_count = $power_off_wait_second * 1000 / $this -> sleep_ms;
    }

    public function onMessage($data)
    {
        $val = intval($data);
        if($val == 0)
        {
            $this->hit_count++;
            if($this->hit_count > $this->max_hit_count)
            {
                \Channel\Client::publish('SYS.PowerOff', 'Shutdown key press');
                MCU\Cache::save();
                \Channel\Client::publish('CoreSystemCommand', 'shutdown');
            }
        }
        else
        {
            $this->hit_count = 0;
        }
        usleep($this->sleep_ms * 1000);
    }  
}

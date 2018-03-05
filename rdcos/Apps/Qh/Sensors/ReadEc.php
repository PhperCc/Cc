<?php
use MCU\Sensor\SensorBase;
use MCU\Cache;

class ReadEc extends SensorBase
{
    protected $portType = 'ModbusRTUPort';
    protected $interval = 0;
    protected $name = 'Encoder';

    private $data_addr;
    private $aspect;
    private $read_failed_cache_seted;

    private $max_speed;
    private $last_speed     = 0;
    private $last_circle    = 0;
    private $correct_offset = 0;
    private $last_data_time = 0;

    public function init()
    {
        $this->aspect = $this->getConfig('aspect');    //正反转：true正转，false反转
        $this->data_addr = $this->getConfig('addr', '00');  // 数据地址
        $this->max_speed = $this->getConfig('max_speed', 100);  // 最大转速， 圈每秒

        $this->interval = $this->getConfig('interval', 1);
        $this->portOption['name'] = $this->getConfig('port', '/dev/ttyO3');
        $this->portOption['baud'] = $this->getConfig('baud', 19200);
        $this->portOption['addr'] = $this->data_addr;
    }

    public function decode($data)
    {
        $data_len = strlen($data);
        if($data_len == 0)
        {
            return null;
        }

        $circle = intval(substr($data, 5, 10)) / 100;
        if($circle < 0)
        {
            Cache::set('Sensor:Encoder:readFailed', 1);
            $this->read_failed_cache_seted = true;

            return null;
        }

        if($this->read_failed_cache_seted)
        {
            Cache::del('Sensor:Encoder:readFailed');
            $this->read_failed_cache_seted = false;
        }

        // 跳点校正
        $data_time = microtime(true);
        $time_span = $data_time - $this->last_data_time;
        $change = $circle - $this->last_circle;
        $speed = $change / $time_span;
        if($this->last_circle > 0 && abs($speed) > $this->max_speed)
        {
            $this->correct_offset -= $change;
            $this->correct_offset += $this->last_speed;
            $this->log("circle: $circle, last_circle: {$this->last_circle}, change = $change, speed = $speed > {$this->max_speed}, correct_offset changed to {$this->correct_offset}");
        }
        else
        {
            $this->last_speed = $speed;
        }
        $this->last_circle = $circle;
        $this->last_data_time = $data_time;
        $circle += $this->correct_offset;

        // 正反转
        if(!$this -> aspect)
        {
            $circle = 10000 - $circle;
        }

        $circle = round($circle, 2);

        // 用于给校准页面发送信号

        foreach($this->_worker->connections as $conn)
        {
            $conn->send($circle);
        }

        return $circle;
    }
}
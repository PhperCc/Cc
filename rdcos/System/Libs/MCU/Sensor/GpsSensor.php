<?php
namespace MCU\Sensor;

class GpsSensor extends SensorBase
{
	protected $_portHandle2 = null;
    protected $last_send2 = 0;
    protected $_status2 = 0;
    protected $_data = [['index'=>0],['index'=>1]];

    /**
     * 初始化传感器
     */
    public function __init()
    {
        $this->setStatus(static::STATUS_DISCONNECTED,'0');
        $port = 'MCU\\Port\\'.$this->portType;
        $this->_portHandle = new $port($this->portOption[0]);
        if(isset($this->portOption[1]) && $this->portOption[1] != '')
        {
            $this->setStatus(static::STATUS_DISCONNECTED,'1');
            $this->_portHandle2 = new $port($this->portOption[1]);
        }
        if($this->mode == 'passivity')
        {
            \Workerman\Lib\Timer::add($this->interval, function() use($port)
            {
                $data = $this->_portHandle->send();
                $data['index'] = '0';
                $data = $this->decode($data);
                $this->onMessage($data);
                unset($data['index']);
                $this->saveData($data,'0');
                if($this->_portHandle2 != null)
                {
                    $data = $this->_portHandle2->send();
                    $data['index'] = '1';
                    $data = $this->decode($data);
                    $this->onMessage($data);
                    unset($data['index']);
                    $this->saveData($data,'1');
                }
            });
        }
        else
        {
            $this->_portHandle->onConnect = function(){
                if($this->_status<static::STATUS_CONNECTED)
                {
                    $this->setStatus(static::STATUS_CONNECTED,'0');
                }
            };
            $this->_portHandle->onClose= function(){
                if($this->_status>static::STATUS_DISCONNECTED)
                {
                    $this->setStatus(static::STATUS_DISCONNECTED,'0');
                }
            };
            $this->_portHandle->onMessage = function($data)
            {
                $data = array('data'=>$data);
                $data['index'] = '0';
                $data = $this->decode($data);

                if($this->interval == 0)
                {
                    $this->onMessage($data);
                    unset($data['index']);
                    $this->saveData($data,'0'); 
                }
                else
                {
                    $now = time();
                    if($this->last_send == 0 || ($this->last_send - $now) >= $this->interval)
                    {
                        $this->onMessage($data);
                        unset($data['index']);
                        $this->saveData($data,'0');
                        $this->last_send = $now; 
                    }
                }
            };
            if($this->_portHandle2 != null)
            {
                $this->_portHandle2->onConnect = function(){
                    if($this->_status2<static::STATUS_CONNECTED)
                    {
                        $this->setStatus(static::STATUS_CONNECTED,'1');
                    }
                };
                $this->_portHandle2->onClose= function(){
                    if($this->_status2>static::STATUS_DISCONNECTED)
                    {
                        $this->setStatus(static::STATUS_DISCONNECTED,'1');
                    }
                };
                $this->_portHandle2->onMessage = function($data)
                {
                    $data = array('data'=>$data);
                    $data['index'] = '1';
                    $data = $this->decode($data);
                    if($this->interval == 0)
                    {
                        $this->onMessage($data);
                        unset($data['index']);
                        $this->saveData($data,'1'); 
                    }
                    else
                    {
                        $now = time();
                        if($this->last_send2 == 0 || ($this->last_send2 - $now) >= $this->interval)
                        {
                            $this->onMessage($data);
                            unset($data['index']);
                            $this->saveData($data,'1');
                            $this->last_send2 = $now; 
                        }
                    }
                };
            }
        }   
    }

    /**
     * 修正存储数据的方法
     * @data array 传感器的数据
     *
     * return data array 修正后的数据
     */
    public function decode($data)
    {
        if(isset($data['index']))
        {
            $index = $data['index'];
            unset($data['index']);
        }
        else
        {
            $index = 0;
            $data['data'] = null;
        }
        $gps_data = \MCU\Positioning\GpsData::parse($data['data']);
        if(isset($gps_data['lon']) && isset($gps_data['lat']) || isset($gps_data['gpstime']))
        {
            if($gps_data['lon']!=0 && $gps_data['lat']!=0)
            {
                if($index == '0' && $this->_status < static::STATUS_LOCATED || $index == '1' && $this->_status2 < static::STATUS_LOCATED)
                {
                    $this->setStatus(static::STATUS_LOCATED,$index);
                }
                
            }
            foreach ($gps_data as $k => $v) {
                $this->_data[$index][$k] = $v;
            }
            return $this->_data[$index];
        }
        else
        {
            return null;
        }
    }

    /**
     * 同步系统时间
     * @time string GPS时间
     */
    protected function _time_sync($gps_time_str)
    {
        $gps_time_val = strtotime($gps_time_str);
        if($gps_time_val - time() <= 5)
        {
            return;
        }
        \MCU\Utils\OperationSystem::exec("date -s '$gps_time_str'");
        $local_time_str = date('Y-m-d H:i:s');
        $this->log("local time changed to: $local_time_str");
    }
}

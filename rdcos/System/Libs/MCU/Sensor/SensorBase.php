<?php
namespace MCU\Sensor;
use \MCU\Cache;

class SensorBase extends \MCU\Process
{
	protected $tp  = 'sensor';

	protected $portType = '';
	protected $portOption = ''; 
	protected $mode = 'active';  //passivity  //模式
	protected $interval = 1;  //间隔 单位秒
    protected $autoSave = true;

    const STATUS_DISCONNECTED = 0; //未连接
    const STATUS_CONNECTED    = 1; //已连接
    const STATUS_DATA_RECIVED = 2; //已接收到数据
    const STATUS_LOCATED      = 3; //数据已准确

	public $_portHandle = null;
    protected $last_send = 0;
    protected $_status = 0;
    protected $_repeat_command = '';

    /**
     * 用户获取数据到回调方法
     * @data array 传感器的数据
     *
     * return data array 缓存值
     */
	public function onMessage($data){ }

    /**
     * 修正存储数据的方法
     * @data array 传感器的数据
     *
     * return data array 修正后的数据
     */
    public function decode($data){ return $data; }

	public function __init()
    {
        $this->setStatus(static::STATUS_DISCONNECTED);
        //打开Port
        $port = 'MCU\\Port\\'.$this->portType;
        $this->_portHandle = new $port($this->portOption);

        if($this->mode == 'passivity')
        {
        	$this->_portHandle->onMessage = function($data)
            {
                $data = $this->decode($data);
                $this->saveData($data);
                $this->onMessage($data);
            };

            if($this->_repeat_command != '' && $this->portType == 'ModbusRTUPort' || $this->portType != 'ModbusRTUPort')
            {
                \Workerman\Lib\Timer::add($this->interval, function() use($port)
                {
                    $this->_portHandle->push($this->_repeat_command);
                }); 
            }
        }
        else
        {
        	$this->_portHandle->onConnect = function(){
                if($this->_status<static::STATUS_CONNECTED)
                {
                    $this->setStatus(static::STATUS_CONNECTED);
                }
            };
            $this->_portHandle->onClose= function(){
                if($this->_status>static::STATUS_DISCONNECTED)
                {
                    $this->setStatus(static::STATUS_DISCONNECTED);
                }
            };
            $this->_portHandle->onMessage = function($data)
        	{
                $data = $this->decode($data);
                if($this->interval == 0)
                {
                    $this->saveData($data);
                    $this->onMessage($data); 
                }
                else
                {
                    $now = time();
                    if($this->last_send == 0 || ($this->last_send - $now) >= $this->interval)
                    {
                        $this->saveData($data);
                        $this->onMessage($data);
                        $this->last_send = $now; 
                    }
                }
        	};
        }
    }

    /**
     * 保存传感器数据到缓存
     * @data array 传感器的数据
     * @index string 传感器的序号
     */
    protected function saveData($data,$index='')
    {
        if($index=='1')
        {
            if($this->_status2<2)
            {
                $this->setStatus(static::STATUS_DATA_RECIVED,$index);
            }
        }
        else
        {
            if($this->_status<2)
            {
                $this->setStatus(static::STATUS_DATA_RECIVED,$index);
            }
        }

        if($this->autoSave)
        {
            Cache::set('Sensor:'.$this->name.$index.':data',$data,10);
        }   
    }

    /**
     * 保存传感器状态到缓存
     * @status int 状态值
     * @index string 传感器的序号
     */
    protected function setStatus($status,$index='')
    {
        if($index == '1')
        {
            $this->_status2 = $status;
        }
        else
        {
            $this->_status = $status;   
        }

        if($status == STATUS_DATA_RECIVED || $status == STATUS_LOCATED)
        {
           \Channel\Client::publish('SYS.SensorOpened',[$this->name.$index=>$status]); 
        }
        
        if($this->autoSave)
        {
            Cache::set('Sensor:'.$this->name.$index.':status',$status);
        }   
    }

    /**
     * 获取传感器数据
     * @index string 传感器的序号
     * return array 传感器的数据
     */
    protected function getData($index='')
    {
    	if($this->autoSave)
        {
            return Cache::get('Sensor:'.$this->name.$index.':data');
        }   
        else
        {
            return null;
        }
    }
}

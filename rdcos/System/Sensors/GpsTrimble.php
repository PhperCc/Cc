<?php
use MCU\Sys;
use MCU\Sensor\GpsSensor;

class GpsTrimble extends GpsSensor 
{   
    protected $name = 'GPS';
    protected $portType = 'TcpPort';
    protected $interval = 0;

    public function init()
    {
        $this->portOption[0] = $this->getConfig('url0', '');
        $this->portOption[1] = $this->getConfig('url1', '');  
    }

    public function onMessage($data)
    {
        if(isset($data['index']))
        {
           $index = $data['index'];
            unset($data['index']);
            if($data!=null)
            {
                Sys::record_report_status($this->name, $data);
                if($index == '0')
                {
                    $this->_time_sync($data['gpstime']); 
                } 
            } 
        }  
    }
}
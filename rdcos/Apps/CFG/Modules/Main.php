<?php
/**
 * 产品业务逻辑
 */
use MCU\Module;
use MCU\Cache;

class svc_Main extends Module
{
    public function start()
    {
        //在此写逻辑代码
        MCU\Timer::add($this->getConfig('interval'),function(){
        	$real_time_data = Cache::get("Sensor:CFG:data");
	        $data_number    = Cache::get("Sensor:CFG:data_number");//数据编号
	        if (!empty($real_time_data)&&!empty($data_number)) 
	        {
	            $real_time_data['data_type'] = 1;
	            // 数据存储
	            MCU\ProduceData::save($real_time_data);
	        }
        });  
    }
}
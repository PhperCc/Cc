<?php
/**
 * 碾压机器程序
 */
use MCU\Module;
use MCU\Cache;

class svc_Main extends Module
{
    private $last_point = null;
    private $too_close_point_count = 0;
   
    public function start()
    {
        global $selfVersion;
        $selfVersion = '2.0.0';
        
        MCU\Timer::add(10, function()
        {
            $car_name = MCU\SysConfig::get('device_name');
            $car_width = $this->getConfig('car_width');
            Cache::set('Svc:Roller:curinfo', ['no' => $car_name, 'width' => $car_width]);
        });

        MCU\Timer::add($this->getConfig('run_interval', 1), function()
        {
            $gnss = Cache::get('Sensor:GPS0:data');
            if($gnss === false) return;
            if($gnss['lon'] == 0) return;

            $dense_data = Cache::get('Sensor:Dense:data');
            if($dense_data === false) return;
            $ecv = array_key_exists('ecv', $dense_data) ? $dense_data['ecv'] : 0;
            $gnss["ecv"] = $ecv;

            $list = Cache::get('Svc:Roller:GpsList');
            if($list === false) return;
            if (count($list) > 100) array_shift($list);
            $list[] = $gnss;

            Cache::set('Svc:Roller:GpsList', $list);
        });

        //提交数据
        MCU\Timer::add($this->getConfig('interval', 2), function()
        {
            $gpsdata = Cache::get('Sensor:GPS0:data');
            if($gpsdata === false) return;
            if(array_key_exists("lon", $gpsdata) && $gpsdata['lon'] == 0) return;

            $useful_point = true;

            $point = new \Positioning\Gps\GpsPoint($gpsdata['lon'], $gpsdata['lat']);
            if($this->last_point !== null && $point->distance($this->last_point) < 0.2)
            {
                $this->too_close_point_count ++;
                $useful_point = $this->too_close_point_count < 10;
                if($useful_point) $this->too_close_point_count = 0;
            }
            $this->last_point = $point;

            if(!$useful_point) return;

            $gpsdata['gpst'] = date('Y-m-d H:i:s', $gpsdata['timestamp'] + 3600 * 8);
            $dense_data = Cache::get('Sensor:Dense:data');
            $ecv = array_key_exists('ext_ecv', $dense_data) ? $dense_data['ext_ecv'] : 0;
            $jump = array_key_exists('jump', $dense_data) ? $dense_data['jump'] : 0;
            $freq = array_key_exists('freq', $dense_data) ? $dense_data['freq'] : 0;
            $amp = array_key_exists('amp', $dense_data) ? $dense_data['amp'] : 0;
            $force = array_key_exists('force', $dense_data) ? $dense_data['force'] : 0;
            $zn_type = array_key_exists('zn_type', $dense_data) ? $dense_data['zn_type'] : 0;
            $data = $gpsdata;
            $data['ecv'] = $ecv;    // 密实
            $data['freq'] = $freq;   // 振频
            $data['amp'] = $amp;    // 振幅
            $data['jump'] = $jump;    // 振幅
            $ext_data = $data;
            $ext_data['force'] = $force;
            $ext_data['zn_type'] = $zn_type;
            
            $this->send($ext_data);
        });
    }
}
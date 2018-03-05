<?php
use MCU\Module;
use MCU\Cache;
use MCU\Sys;
use MCU\LocalFile;

class svc_CFGCalc extends Module
{
    private $config = null;
    private $state = 0;
    private $work_point;//当前工作点坐标
    private $sink_start_time = 0; //开始打桩时间/移车结束时间
    private $lift_end_time = 0; //开始拔管时间/开始灌注混凝土时间
    // private $start_yg_time = 0;//上一桩成桩时间/开始车辆移动时间

    public function start()
    {
        Cache::set("Svc:CFG:batch",0);
        Cache::set('work_temp_list', []);
        $history_data = $this->get_history_data();
        Cache::set("work_point_list",[$history_data]);
        Cache::set("Sensor:CFG:digitaltube", []);

        \Workerman\Lib\Timer::add($this->getConfig('run_interval', 0.1), function()
        {
            // 未获取定位信息，不采集数据。
            $master = Cache::get("Sensor:GPS0:data");
            $batch = Cache::get("Svc:CFG:batch");

            if(!empty($master['quality']))
            {
                if (empty($batch)) 
                {
                    $this->batch();
                    $thisCache::set("Sensor:CFG:start_gps", $master);
                } 
                $this->cfg_calc();
            }
        });
    }

   public function cfg_calc()
   {
        $data['data_number']          = Cache::get("Sensor:CFG:data_number");//数据编号
        $data['front_gnss']           = Cache::get("Sensor:GPS0:data");// 获取gnss前天线数据
        $data['cfg_Batch']            = Cache::get("Svc:CFG:batch");//  // 获取批次号
        $data['after_gnss']           = Cache::get("Sensor:GPS1:data");//后天线
        $data['site_coordinates']     = Cache::get("Sensor:CFG:xyz");//工地坐标
        $data['dip_angle']            = Cache::get("Sensor:Da:data");//倾角传感器
        $data['pressure_transmitter'] = Cache::get("Sensor:pt:data");//压力变送器传感器
        $data['electric_current']     = Cache::get("Sensor:An:data");// 获取电流表数据
        $data['pilesection']          = Cache::get("Sensor:Pilesection:data");//里程偏移
        $data['device_Status']        = $this->getDevStatus();//设备状态
        $data['creates_data_time']    = $this->udate('Y-m-d H:i:s.u');//产生此数据的时间
        $data['verticality']    = number_format($data['dip_angle']['h'], 4);//垂直度，允许范围 0.0000~1.0000
        $data['device_name']    = SysConfig::get('device_name');//设备编号
        $data['uid']            = Sys::get_uid();//设备唯一编码 
        $data['gpst']           = date('Y-m-d H:i:s', $data['front_gnss']['timestamp'] + 8 * 3600); 
        $data['deep_pile']      = 0;// 桩深
        $data['filling_volume'] = 0;// 水泥方量(混凝土灌入量)
        $data['newpoint']       = 0;//是否生成新桩
        

        // 工作成桩列表
        $work_point_list  = Cache::get("work_point_list");
        $work_point_count = count($work_point_list);
        $data['idx']      = sprintf('%04d', $work_point_count+1);
        // 获取开始坐标
        $start_gps = Cache::get("Sensor:CFG:start_gps");
        $dist = $this->get_gps_distance($data['front_gnss']['lon'], $data['front_gnss']['lat'], $start_gps['lon'], $start_gps['lat']);
        Cache::set("start_gps:dist",number_format($dist,6));// 当前定位与初始坐标的距离
        //实时桩深 
        $data['deep_pile'] = number_format($start_gps['elevation'] - $data['front_gnss']['elevation'], 3);
        if ($data['deep_pile'] < 0) 
        {
            $cfg_Depth_fx = 1;
            $data['deep_pile'] = 0;
            Cache::set("Sensor:CFG:start_gps", $data['front_gnss']);
        }
        else
        {
            $cfg_Depth_fx = 2; // 开始打桩
        }

        // 获取最小开始记录的深度
        $start_min_depth = $this->getConfig('CFGconfig/start_min_depth', 0.3);
        if ($this-> state == 0 && $data['electric_current'] > 50 && $data['deep_pile'] > $start_min_depth && $cfg_Depth_fx == 2 ) 
        {
            $this->state = 1;
            Cache::set("Sensor:CFG:start_gps", $data['front_gnss']);
            $data_number = "CFG_".$data['uid']."_".date('YmdHis', $data['front_gnss']['timestamp'] + 8 * 3600);//数据编号
            Cache::set("Sensor:CFG:data_number", $data_number);
            $this->sink_start_time = $data['front_gnss']['timestamp'];//下打桩时间
        }

         // 结束拔管
        if ($this-> state == 1 && $data['electric_current'] == 0 && $start_gps['elevation'] < $data['front_gnss']['elevation'] ) 
        { 
            // 成桩信息
            $hit_data['uid']            = $data['uid'];
            $hit_data['idx']            = $data['idx'];
            $hit_data['device_name']    = $data['device_name'];
            $hit_data['data_number']    = $data['data_number'];//数据编号
            $hit_data['longitude']      = $this->work_point['lon'];//成桩经度
            $hit_data['latitude']       = $this->work_point['lat'];//成桩纬度
            $hit_data['quality']        = $data['front_gnss']['quality'];//定位信号质量
            $hit_data['height']         = $this->work_point['elevation'];//定位高程
            $hit_data['batch']          = Cache::get("Svc:CFG:batch");
            $hit_data['locationtype']   = 1;//定位类型 
            $hit_data['piledepth ']     = $this->work_point['deep_pile'];//成桩深度
            $hit_data['piledepthcount'] = $this->work_point['piledepthcount'];//桩深度数据总条数
            $hit_data['sinkavgcurrent'] = number_format($this->work_point['avg_electric_current'],2);//平均沉管电流
            $hit_data['currentcount']   = $this->work_point['current_count'];//成桩电流数据条数
            $hit_data['sinkstarttime']  = $this->sink_start_time ;//沉管开始时间 ，YYYY-MM-DD HH:MM:SS
            $hit_data['sinkendtime']    = $this->work_point['sinkendtime'];//沉管结束时间 ，YYYY-MM-DD HH:MM:SS
            $hit_data['liftstarttime']  = $this->work_point['liftstarttime'];//拔管开始时间，YYYY-MM-DD HH:MM:SS
            $hit_data['liftendtime']    = $this->work_point['liftendtime'];//拔管结束时间，YYYY-MM-DD HH:MM:SS
            $hit_data['sinktotaltime']  = $this->work_point['sinkendtime'] - $this ->sink_start_time;//沉管总时长
            $hit_data['lifttotaltime']  = $this->work_point['liftendtime'] - $this->work_point['liftstarttime'];//拔管总时长
            $hit_data['timestamp']      = $this->work_point['liftendtime'] - $this ->sink_start_time;//成桩用时
            $hit_data['sinkavgspeed']   = $this->work_point['sinkavgspeed'];
            $hit_data['liftavgspeed']   = $this->work_point['liftavgspeed'];
            $hit_data['verticality']    = $this->work_point['verticality'];//垂直度，允许范围 0.0000~1.0000 ，
            $hit_data['fillingvolume']  = $this->work_point['fillingvolume'];
            $hit_data["A"]              = $this->work_point['A'];
            $hit_data["B"]              = $this->work_point['B'];
            $hit_data["H"]              = $this->work_point['H'];
            $hit_data["shifting"]       = $data['pilesection']["shifting"];//偏移量
            $hit_data['pile_start']     = $data['pilesection']['pile_start'];//开始里程和坐标
            $hit_data['pile_current']   = $data['pilesection']['pile_current'];//当天坐标和里程
            $hit_data['pile_end']       = $data['pilesection']['pile_end'];//结束里程和坐标
            $hit_data['data_type']      = 0;//数据类型 0表示成桩数据，1表示实时数据
            $hit_data['creates_data_time']        = $this->udate('Y-m-d H:i:s.u');//产生此数据的时间
            $hit_data['forcebearinglayercurrent'] = $this->work_point['fblcurrent'];//持力层电流 
            $hit_data['newpoint']       = 1;//新增点
             /******清空中间数据*****/
            $this->work_point      = null;
            $this->sink_start_time = 0;
            $this->lift_end_time   = 0;
            Cache::set('work_temp_list', []);
            // 写入缓存
            while(count($work_point_list) > 100) array_shift($work_point_list);  // 最多只保留最后 100 个记录
            // 如果桩机的沉管深度小于10m,不记录
            // 获取最小成桩的深度
            $min_piledepth = $this->getConfig('CFGconfig/min_piledepth', 5);
            if ($hit_data['piledepth '] > $min_piledepth) 
            {
                $work_point_list[$work_point_count-1]['newpoint'] = 0;
                $work_point_list[] = $hit_data;
                Cache::set('work_point_list', $work_point_list);
                $hit_data['newpoint'] = 0;
                $data_save_path    = "record/cfg_history.csv";
                $history_file_path = LocalFile::getFilePath($data_save_path);
                LocalFile::putLine($data_save_path, json_encode($hit_data), true);

                // 数据存储
                MCU\ProduceData::save($hit_data);
            }
            
            $this-> state = 0;
        }

         // 向下插入和上升过程中，将工作数据缓存起来
        if ($this-> state == 1) 
        {
            $work_temp_list   = Cache::get("work_temp_list");
            $work_temp_list[] = [
                "lon"               =>  $data['front_gnss']['lon'],
                "lat"               =>  $data['front_gnss']['lat'],
                "speed"             =>  $data['front_gnss']['speed'],
                "elevation"         =>  $data['front_gnss']['elevation'],
                "timestamp"         =>  $data['front_gnss']['timestamp'],
                "verticality"       =>  $data['verticality'],
                "electric_current"  =>  $data['electric_current'],
                "A"                 =>  $data['pilesection']['pile_current']['x'],
                "B"                 =>  $data['pilesection']['pile_current']['y'],
                "H"                 =>  $data['pilesection']['pile_current']['z'],
            ];
            Cache::set('work_temp_list', $work_temp_list);//写入缓存文件
            $this->work_point = $this->get_work_point_avg();
        }

        // 人机界面显示信息
        $digitaltube = [
            "idx"               => floatval($data['idx']),
            "verticality"       => number_format($data['dip_angle']['h'], 4),
            "cfgdepth"          => number_format(floatval($data['deep_pile']), 2),
            "fillingvolume"     => number_format(floatval($data['filling_volume']), 2),
            "electric_current"  => number_format(floatval($data['electric_current']), 2),
            "cfgspeed"          => number_format(floatval($data['front_gnss']['speed']), 4),
            "elevation"         => number_format(floatval($data['front_gnss']['elevation']), 2),
            "lat"               => number_format(floatval($data['front_gnss']['lat']), 8),
            "dkcode"            => floatval($data['pilesection']['dkcode']),
            "mileage"           => number_format(floatval($data['pilesection']['mileage']), 3),
            "lon"               => number_format(floatval($data['front_gnss']['lon']), 8),
            "shifting"          => number_format(floatval($data['pilesection']['shifting']), 2),
        ];
        
        $data['gridserver'] = Cache::get("Sensor:CFG:gridserver");
        
        // 写入缓存
        Cache::set("Sensor:CFG:data", $data);
        Cache::set('Sensor:CFG:state', $this-> state); // 工作状态
        Cache::set("deep_pile", $data['deep_pile']); 
        Cache::set("Sensor:CFG:digitaltube", $digitaltube);
   }

     // 获取提升时，GPS集合的中位值
    private function get_work_point_avg()
    {
        $list = Cache::get('work_temp_list');
        if(count($list) == 0) return ["lon" => 0,"lat" => 0,"elevation" => 0,"A" => 0,"B" => 0,"H" => 0,"deep_pile" => 0,"sinkendtime" => 0,"liftstarttime" => 0,"liftendtime" => 0,"fblcurrent" => 0,"verticality" => 0,"sinkavgspeed" => 0,"liftavgspeed" => 0,"current_count" => 0,"piledepthcount" => 0,"fillingvolume" => 0,"avg_electric_current" => 0];
        foreach ($list as $r)
        {
            $lon_sum  += $r['lon'];
            $lat_sum  += $r['lat'];
            $a_sum    += $r['A'];
            $b_sum    += $r['B'];
            $h_sum    += $r['H'];

            if ($r['electric_current'] != 0) 
            {
               $Depth_arr[] = $r['elevation'];
               $gps_data[]  = $r;
               $sink_speed_sum += $r['speed'];
            }
            else
            {
                $lift_speed_arr[] = $r['speed'];
                $lift_speed_sum += $r['speed'];
            }
        }
        // 获取实际成桩深度
        $start_gps   = Cache::get("Sensor:CFG:start_gps");//获取开始打桩定位信息（沉桩开始高程）
        $Depth_min   = array_search(min($Depth_arr), $Depth_arr);//获取上天线实时高程的最小值
        $sinkendtime = $liftstarttime = $gps_data[$Depth_min]['timestamp'];//沉管结束/拔管开始时间
        $Depth_min_elevation = $Depth_arr[$Depth_min];//上天线实时高程的最小值
        $Depth       = $start_gps['elevation'] - $Depth_min_elevation;//沉桩开始高程 - 上天线实时高程的最小值
        $last_list   = end($list);
        //获取沉管结束时间深度前0.5米时间到沉管结束时间的最大电流
        $avg_current_data = $this->get_avg_current_data($gps_data, $start_gps, $Depth_min_elevation);
        return [
            'lon'            => $lon_sum / count($list),//实际成桩经度
            'lat'            => $lat_sum / count($list), //实际成桩纬度
            'elevation'      => $start_gps['elevation'],//实际成桩高程
            "A"              => $a_sum / count($list),
            "B"              => $b_sum / count($list),
            "H"              => $h_sum / count($list),
            'deep_pile'      => $Depth,//实际成桩深度 
            "sinkendtime"    => $sinkendtime,//沉管结束
            "liftstarttime"  => $liftstarttime,//拔管开始时间
            "liftendtime"    => $last_list['timestamp'],//拔管结束时间
            "fblcurrent"     => $avg_current_data['fblcurrent'],//持力层电流
            "verticality"    => $avg_current_data['verticality'],//下管时候平均垂直度
            "sinkavgspeed"   => $sink_speed_sum / count($Depth_arr),//插管平均速度
            "liftavgspeed"   => $lift_speed_sum / count($lift_speed_arr),//拔管平均速度
            "current_count"  => $avg_current_data['current_count'],//成桩电流数据条数
            "piledepthcount" => count($Depth_arr),//桩深度数据总条数
            "fillingvolume"  => "123.5",
            "avg_electric_current" => $avg_current_data['avg_electric_current'],//平均沉管电流
        ];
    }

    // 获取电流信息
    private function get_avg_current_data($gps_data, $start_gps, $Depth_min)
    {
         // 获取最小成桩电流计算的深度
        $min_currentdepth = $this->getConfig('CFGconfig/min_currentdepth', 5);
         foreach ($gps_data as $key => $v) 
        {
            $start_h = $start_gps['elevation'] - $v['elevation'];
            $end_h   = $v['elevation'] - $Depth_min;

            if ($start_h > $min_currentdepth && $end_h > $min_currentdepth) 
            {
                $An_sum      += $v['electric_current'];
                $An_arr[]    =  $v['electric_current'];
                $verticality += $v['verticality'];
            }

            if($end_h < $min_currentdepth) 
            {
                $fb_an_arr[] = $v['electric_current'];
            }
        }
        $fblcurrent = array_search(max($fb_an_arr), $fb_an_arr);
        return [
            "fblcurrent"           => $fb_an_arr[$fblcurrent],//持力层电流
            "avg_electric_current" => $An_sum / count($An_arr),//平均沉管电流
            "current_count"        => count($An_arr),//成桩电流数据条数
            "verticality"          => $verticality / count($An_arr)
        ];
    }

    // 生成批次号
    public function batch()
    {
        $gnss_data = Cache::get("Sensor:GPS0:data");//前天线
        $batch = Cache::get("Svc:CFG:batch"); // 数据存储对列编
        if (!empty($gnss_data['timestamp']))
        {
            if (empty($batch) || $batch == null) 
            {
                $batch =  date('YmdHis', $gnss_data['timestamp'] + 8 * 3600);
                Cache::set("Svc:CFG:batch", 'cfg_'.$batch);
            }
        }

        return $batch;
    }

    /**
    * 获取设备状态
    */
    public function getDevStatus()
    {
        $gnss0_master = $Cache::get("Sensor:GPS0:data");
        $gnss1_master = $Cache::get("Sensor:GPS1:data");
        $net = Cache::get("sys:dialup");
        // 拨号状态
        $data['NET'] = Cache::get("sys:dialup:state");
        //主天线状态
        if($gnss0_master['quality']==5) $gnss0_master['quality'] = 3;
        empty($gnss0_master['quality']) ? $data['GNSS0'] = 0 : $data['GNSS0'] = $gnss0_master['quality'];
        // 从天线状态
        if($gnss1_master['quality']==5) $gnss1_master['quality'] = 3;
        empty($gnss1_master['quality']) ? $data['GNSS1'] = 0 : $data['GNSS1'] = $gnss1_master['quality'];
        // 电流表状态
        $data['AN'] = Cache::get("Sensor:An:status");
        $data['PT'] = Cache::get("Sensor:Pt:status");
        $data['DA'] = Cache::get("Sensor:Da:status");
        $data['DT'] = Cache::get("Sensor:Dt:status");
        // 服务器状态
        $data['CSQ'] = array_key_exists('status', $net) ? $net['status']['signal_quality'] : 0;
        return $data;
    }


     /**
     * 获取两个经纬度之间的距离
     *
     * @param $lon1
     * @param $lat1
     * @param $lon2
     * @param $lat2
     *
     * @return number
     */
    private function get_gps_distance($lon1, $lat1, $lon2, $lat2)
    {
        $p1 = new MCU\Positioning\GpsPoint($lon1, $lat1);
        $p2 = new MCU\Positioning\GpsPoint($lon2, $lat2);

        return $p1->distance($p2);
    }

   /** 格式化时间戳，精确到毫秒，x代表毫秒 */  
    private function microtime_format($tag, $time)  
    {  
       list($usec, $sec) = explode(".", $time);  
       $date = date($tag,$usec);  
       return str_replace('x', $sec, $date);  
    } 

   public function get_history_data()
    {
         $data_save_path = "record/cfg_history.csv";
        $history_file_path = LocalFile::getFilePath($data_save_path);
        if(!@file_exists($history_file_path)) 
        { 
            $data[] = $file;
        }
        else
        {
            $file = fopen($history_file_path,"r");
            while(! feof($file))
            {
                $line = fgets($file);
                if(empty($line))continue;
                $data[] = json_decode($line, true);
            }

            fclose($file);
        }

       if (count($data) > 1) 
        {
            Cache::set("work_point_list",$data);
        }
        elseif (count($data) == 1) 
        {
            Cache::set("work_point_list",[$data[0]]);
        }
        else
        {
           Cache::set("work_point_list",[]); 
        }
    }

    public function udate($format = 'u', $utimestamp = null) 
    {
       if (is_null($utimestamp))
       {
            $utimestamp   = microtime(true);
            $timestamp    = floor($utimestamp);
            $milliseconds = round(($utimestamp - $timestamp) * 1000000);
       }
       return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }
}
?>
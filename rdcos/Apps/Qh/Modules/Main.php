<?php
/**
 * 强夯数据计算提交模块
 */

use MCU\Sys;
use MCU\Module;
use MCU\Cache;
use MCU\LocalFile;
use MCU\ModuleConfig;
use MCU\Positioning\GpsPoint;

class svc_Main extends Module
{
    
    private $config = null;
    private $last_real_time_data = null;
    private $min_circle = 9999;
    private $lo_height_stage = false;
    private $hi_height_stage = false;
    private $hummer_drop_stage = false;


    public function start()
    {
        global $selfVersion;
        $selfVersion = '2.0.0';
        
        $this->config = $this->getConfig();
        $QH_config = $this->config['QHconfig'];
        $QH_config['device_name'] = MCU\SysConfig::get('device_name');
        Cache::set('Svc:QH:config', $QH_config);

        MCU\Timer::add($this->getConfig('run_interval', 1), function()
        {
            $this->qianghang_calc();
        });
    }

    /**
     * 根据累计数据，分析并计算夯击过程
     */
    public function qianghang_calc()
    {
        $QH_config = $this->config['QHconfig'];
        $positions = $this->get_positions();
        if($positions['lon'] == 0)
        {
            $this->log('lon is 0');

            return;
        }

        $circle = floatval(Cache::get('Sensor:Encoder:data'));
        if($circle <= 0)
        {
            $this->log('Encoder data is null');

            return;
        }

        $real_time_data = $positions;
        $real_time_data['circle'] = $circle;
        $real_time_data['microtime'] = microtime(true);
        $real_time_data['high'] = 0;
        $real_time_data['max_high'] = 0;
        $real_time_data['chenjiang'] = 0;

        if($this->last_real_time_data == null)
        {
            $this->last_real_time_data = $real_time_data;

            return;
        }
        if($real_time_data['gpst'] == $this->last_real_time_data['gpst'])
        {
            $this->log("gpst no change {$real_time_data['gpst']}");

            return;
        }

        // 提升或下降
        $circle_changed = $real_time_data['circle'] - $this->last_real_time_data['circle'];
        $up_down_direction = "eq";
        if($circle_changed > 0)
        {
            $up_down_direction = "up";
        }
        if($circle_changed < 0)
        {
            $up_down_direction = "down";
        }

        // 编码器实时转速
        // $cicle_speed = $circle_changed / ($real_time_data['microtime'] - $this->last_real_time_data['microtime']);

        // 找到本次开机以来记录的最小圈数值(锤的最低位置)
        $this->min_circle = min($this->min_circle, $real_time_data['circle']);

        // 获取当前锤高度
        $rel_circle = $real_time_data['circle'] - $this->min_circle;
        $high = $QH_config['jiaozhun_type'] == 2 ? $rel_circle * $QH_config['dist_rate'] : $this->get_height($rel_circle);
        $high = round($high, 3);
        $real_time_data['high'] = $high;
        $real_time_data['max_high'] = max($real_time_data['max_high'], $high);

        // 夯锤升降速度
        $hight_change = $high - $this->last_real_time_data['high'];
        $hight_change_speed = $hight_change / ($real_time_data['microtime'] - $this->last_real_time_data['microtime']);
        $hight_change_speed = round($hight_change_speed, 3);

        // 进入低位区时， 低位条件满足
        if($high < $QH_config['high_min'])
        {
            // 高低位条件都满足， 说明提升到足够高度但又未夯击， 此时又进入低位区， 重置 min_circle， 高位条件置为不满足
            // 解决从坑底提锤到地面， 又挪锤导致计算为一次夯击
            $this->lo_height_stage = true;
        }

        // 进入高位区， 且低位条件满足时， 高位条件满足
        if($this->lo_height_stage && $high >= $QH_config['high_min'])
        {
            $this->hi_height_stage = true;
        }

        // 高位条件满足且下降速度足够时， 落锤条件满足
        if($this->hi_height_stage && $hight_change_speed < -0.4)  // 下降时， 下降速度超过阈值。 方向向下时， 值为负值
        {
            $this->hummer_drop_stage = true;
        }

        // 前端显示高程
        Cache::set("high", $high);

        // 落锤条件满足时， 夯击
        if($this->hummer_drop_stage)
        {
            // 落锤后， 所有条件都置为不满足
            $this->lo_height_stage = false;
            $this->hi_height_stage = false;
            $this->hummer_drop_stage = false;

            Cache::set('high', 0); // 夯击完成降落时， 前端显示的锤高为0

            $hit_data = $real_time_data;
            $real_time_data['max_high'] = 0;
            $hit_data['high'] = $hit_data['max_high'];
            unset($hit_data['max_high']);

            // 高程太小， 本次不记录
            if($hit_data['high'] < $QH_config['high_min'])
            {
                return;
            }

            $hit_data['chenjiang'] = 0;
            $work_point_hit_list = Cache::get('work_point_hit_list', []);
            if(count($work_point_hit_list) > 0)
            {
                $last_high = $work_point_hit_list[count($work_point_hit_list) - 1]['high'];
                $hit_data['chenjiang'] = round($hit_data['high'] - $last_high, 2);
                if($hit_data['chenjiang'] < 0)
                {
                    $hit_data['chenjiang'] = 0;
                }
            }

            Cache::set('SVC:QH:hit:chenjiang', $hit_data['chenjiang']); // 沉降量

            $work_point_hit_list[] = $hit_data;
            Cache::setex('work_point_hit_list', $work_point_hit_list);

            $work_point = $this->get_work_point($hit_data, $QH_config);
            $hit_data['work_point_id'] = $work_point['id'];
            $hit_data['hit_index'] = $work_point['hit_count'];

            // 1: 点夯
            // 2: 置换
            // 3: 满夯
            $hit_data['hammer_type'] = ModuleConfig::get('QH', 'main', 'QHconfig/hammer_type', 2);

            // 记录夯击历史
            $data_save_path = 'record/qh_history.csv';
            $history_file_path = LocalFile::getFilePath($data_save_path);
            if(!file_exists($history_file_path))
            {
                $title = implode(',', array_keys($hit_data));
                LocalFile::putLine($data_save_path, $title);
            }
            $record_line = implode(',', array_values($hit_data));
            LocalFile::putLine($data_save_path, $record_line, true);

            $this->min_circle = 9999; // 所有高程重新开始判断

            //Yuantu::submit_qh($hit_data);
            $this->data_submit($hit_data);
        }

        $this->last_real_time_data = $real_time_data;
        $stage_info = " lo: " . ($this->lo_height_stage ? 'Y' : 'N');
        $stage_info .= " hi: " . ($this->hi_height_stage ? 'Y' : 'N');
        $stage_info .= " drop: " . ($this->hummer_drop_stage ? 'Y' : 'N');
        $stage_info .= " sped: $hight_change_speed m/s";
        $stage_info .= " drct: $up_down_direction";
        $stage_info .= " minc: " . $this->min_circle;

        Cache::set('Svc:QH:stage', $stage);
    }

    /**
     * 根据校准配置文件计算实际提锤高程
     *
     * @param $cur_circle
     *
     * @return int
     */
    private function get_height($cur_circle)
    {
        global $appRoot;
        if(null === $jiaozhun_config = LocalFile::getObject('config/qh/jiaozhun'))
        {
            $jiaozhun_config = include($appRoot . 'Modules/jiaozhun.php');
            LocalFile::putObject('config/qh/jiaozhun', $jiaozhun_config);
        }

        // 此处认为校准配置文件保存的 Key 值为从小到达排序
        // 可能需要数组排序
        // 需要确保所有的Key不重复， 否则可能产生除0错误
        $relative_jiaozhun_config = [];
        $kMin = 9999;

        // 找到校准文件中的最小圈数值
        foreach($jiaozhun_config as $k => $v)
        {
            $kMin = min(floatval($k), $kMin);
        }

        // 校准数据中的圈数， 都转化为相对圈数
        foreach($jiaozhun_config as $k => $v)
        {
            $newKey = strval(floatval($k) - $kMin);
            if(!array_key_exists($k, $relative_jiaozhun_config))
            {
                $relative_jiaozhun_config[$newKey] = $v;
            }
        }
        $jiaozhun_config = $relative_jiaozhun_config;

        $last_kValue = 0;
        $last_vValue = 0;
        foreach($jiaozhun_config as $k => $v)
        {
            $kValue = floatval($k);
            $vValue = floatval($v);

            if($kValue == $cur_circle)
            {
                return $vValue;
            }
            else
            {
                if($kValue < $cur_circle)
                {
                    $last_kValue = $kValue;
                    $last_vValue = $vValue;
                    continue;
                }
                else
                {
                    // a / b = x / y
                    // x = a * y / b
                    // 计算相对前一点的高差
                    $height = ($cur_circle - $last_kValue) * ($vValue - $last_vValue) / ($kValue - $last_kValue);
                    $height += $last_vValue;

                    return $height;
                }
            }
        }
        // 循环结束， 还未返回， 说明当前高程高于校准时测量的最高高程， 模拟计算
        $kArray = array_keys($jiaozhun_config);
        $vArray = array_values($jiaozhun_config);

        $kFirst = $kArray[0];
        $vFirst = $vArray[0];

        $kLast = array_pop($kArray);
        $vLast = array_pop($vArray);

        $kSpan = $kLast - $kFirst;
        $vSpan = $vLast - $vFirst;

        $rate = $vSpan / $kSpan;

        return $rate * ($cur_circle - $kFirst) + $vFirst;
    }

    /**
     * 获取当前夯击的坑位
     *
     * @param $hit
     * @param $QHconfig
     *
     * @return array|null
     */
    private function get_work_point($hit, $QHconfig)
    {
        $work_point_list = Cache::get('work_point_list',[]);

        $is_new_work_point = false;
        $work_point_index = 0;
        $work_point = null;
        if(empty($work_point_list))
        {
            $this->log("new work point: empty work point list");
            $is_new_work_point = true;
        }
        else
        {
            $last_work_point = $work_point_list[count($work_point_list) - 1];
            $work_point_index = intval($last_work_point['index']);
            $dist = $this->get_gps_distance($last_work_point['lon'], $last_work_point['lat'], $hit['lon'], $hit['lat']);
            //$this->log("distance: last_point_lon: {$last_work_point['lon']}, last_point_lat: {$last_work_point['lat']}, data_lon: {$hit['lon']}, data_lat: {$hit['lat']}");
            $this->log("distance to last workpoint: $dist m");
            if($dist > $QHconfig['gps_dist_min'])
            {
                $this->log("new work point: > {$QHconfig['gps_dist_min']}");
                $is_new_work_point = true;
            }
            else
            {
                $position_avg = $this->get_work_point_avg();
                $last_work_point['hit_count'] += 1;
                $last_work_point['chenjiang_total'] += $hit['chenjiang'];
                $last_work_point['lon'] = $position_avg['lon'];
                $last_work_point['lat'] = $position_avg['lat'];
                $last_work_point['elevation'] = $position_avg['elevation'];

                // 更新数据库
                $work_point_list[count($work_point_list) - 1] = $last_work_point;
                $work_point = $last_work_point;
            }
        }

        if($is_new_work_point)
        {
            // 添加数据库
            $work_point_index++;
            $work_point = ['id'              => $this->get_workpoint_id($work_point_index),
                           'index'           => $work_point_index,
                           'hit_count'       => 1,
                           'chenjiang_total' => $hit['chenjiang'],
                           'lon'             => $hit['lon'],
                           'lat'             => $hit['lat'],
                           'elevation'       => $hit['elevation'],
                           'gpst'            => $hit['gpst'],
                           'radius'          => $hit['radius'],
            ];
            $work_point_list[] = $work_point;

            Cache::set('work_point_hit_list', [$hit]); // 清空每夯点夯击位置列表， 并将本次加入
        }

        while(count($work_point_list) > 100)
        {
            array_shift($work_point_list);
        }  // 最多只保留最后 100 个夯点记录
        Cache::set('work_point_list', $work_point_list);

        return $work_point;
    }

    /**
     * 取工作点编号
     *
     * @param $index
     *
     * @return string
     */
    private function get_workpoint_id($index)
    {
        $device_name = str_replace('-', '',MCU\SysConfig::get('device_name'));
        $date = date('md');
        $index_pad = str_pad($index, 2, '0', STR_PAD_LEFT);

        return "$device_name$date$index_pad";
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
        $p1 = new GpsPoint($lon1, $lat1);
        $p2 = new GpsPoint($lon2, $lat2);

        return $p1->distance($p2);
    }

    // 获取提升时，GPS集合的中位值

    /**
     * 取工作点内所有GPS的平均点
     *
     * @return array
     */
    private function get_work_point_avg()
    {
        $list = Cache::get('work_point_hit_list', []);
       
        if(count($list) == 0)
        {
            return ["lon"       => 0,
                    "lat"       => 0,
                    "elevation" => 0
            ];
        }

        $lon_sum = 0;
        $lat_sum = 0;
        $elevation_sum = 0;

        foreach($list as $r)
        {
            $lon_sum += $r['lon'];
            $lat_sum += $r['lat'];
            $elevation_sum += $r['elevation'];
        }

        return ['lon'       => $lon_sum / count($list),
                'lat'       => $lat_sum / count($list),
                'elevation' => $elevation_sum / count($list)
        ];
    }

    /**
     * 根据主从天线坐标位置及车辆尺寸参数计算转轴坐标位置
     *
     * @return array
     */
    private function get_positions()
    {
        $gps0 = Cache::get('Sensor:GPS0:data');
        $gps1 = Cache::get('Sensor:GPS1:data');

        $position = [];
        $position['gpst'] = date('Y-m-d H:i:s', time() + 8 * 3600);
        $position['lon'] = 0;
        $position['lat'] = 0;
        $position['plon'] = 0;
        $position['plat'] = 0;
        $position['workAngle'] = 0;
        $position['phi'] = 0;
        $position['gnss_qulity'] = 0;

        if(empty($gps0['lon']) || empty($gps0['lat']) || empty($gps1['lon']) || empty($gps1['lat']))
        {
            Cache::set('Svc:QH:Positions', $position);

            return $position;
        }

        $frontPoint = new GpsPoint($gps0['lon'], $gps0['lat'], $gps0['elevation']);
        $backPoint = new GpsPoint($gps1['lon'], $gps1['lat'], $gps1['elevation']);

        $frontPointAtLeft = $this->config['QHconfig']['param']['frontPointAtLeft'];
        $frontPointX = $this->config['QHconfig']['param']['frontPointX'];
        $frontPointY = $this->config['QHconfig']['param']['frontPointY'];
        $backPointAtLeft = $this->config['QHconfig']['param']['backPointAtLeft'];
        $backPointX = $this->config['QHconfig']['param']['backPointX'];
        $backPointY = $this->config['QHconfig']['param']['backPointY'];
        $backPointHi = $this->config['QHconfig']['param']['backPointHi'];

        QhPoint::setupInfo($frontPointAtLeft, $frontPointX, $frontPointY, $backPointAtLeft, $backPointX, $backPointY);
        $PositionInfo = QhPoint::getPositionInfo($frontPoint, $backPoint);

        $position['gpst'] = date('Y-m-d H:i:s', $gps0['timestamp'] + 8 * 3600);
        $position['lon'] = $PositionInfo['workPoint']->lon;
        $position['lat'] = $PositionInfo['workPoint']->lat;
        $position['plon'] = $PositionInfo['centerPoint']->lon;
        $position['plat'] = $PositionInfo['centerPoint']->lat;
        $position['workAngle'] = $PositionInfo['workAngle'];
        $position['phi'] = $gps1['elevation'] - $backPointHi;
        $position['gnss_qulity'] = $gps0['quality'];

        Cache::set('Svc:QH:Positions', $position); // 5秒后实现未实现

        return $position;
    }
}
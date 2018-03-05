<?php
use MCU\Module;
use MCU\Cache;
/**
 * 位置坐标服务
 * Class svc_GnssServer
 */

class svc_GnssServer extends Module
{
    private $localcoord;
    private $offsetHI;

    /**
     * 读取原始NMEA数据
     */
    public function start()
    {
        $coord_params['l0'] = $this->getConfig("L0", 109.00);
        $coord_params['h0'] = $this->getConfig("H0", 390);
        $coord_params['dx'] = $this->getConfig("DX", 112.441842);
        $coord_params['dy'] = $this->getConfig("DY", 248.877852);
        $coord_params['dz'] = $this->getConfig("DZ", -222.295697);
        $coord_params['wx'] = $this->getConfig("WX", -0.0000468479);
        $coord_params['wy'] = $this->getConfig("WY", 0.0000036018);
        $coord_params['wz'] = $this->getConfig("WZ", -0.0000226568);
        $coord_params['k']  = $this->getConfig("K", -0.000005231748);
        
        // 本地坐标系类型
        $coord_type = $this->getConfig("coords",0);
        // 初始化坐标转换对象
        if($coord_type == 1)
        {
            $this->localcoord = new MCU\Positioning\XA80($coord_params);
        }
        else
        {
            $this->localcoord = new MCU\Positioning\CGCS2000($coord_params);
        }
        
        
        //高差，车辆高度
        $this->offsetHI = $this->getConfig("offsetHI", 3.2);

        MCU\Timer::add($this->getConfig('run_interval', 1), function()
        {   
            $this->trans_localcoord();
        });
    }

    /**
     * @param $str
     *
     * @return string
     */
    private function ptnl_parse($str)
    {
        $resp = '';
        $gps_arr = explode('$', $str);
        foreach($gps_arr as $key => $value)
        {
            trim($value);
            $arr = explode(',', $value);
            switch($arr[0])
            {
                case 'PTNL':
                    $resp .= $this->rebuild_pjk($arr);
                    break;
                case 'GPGGA':
                    $resp .= "$" . $value;
                    break;
                case 'GPRMC':
                    $resp .= "$" . $value;
                    break;
            }
        }

        return $resp;
    }
    /**
     * 重构$PTNL,PJK语句
     */
    private function rebuild_pjk($arr)
    {
        $arr[5] = 'N';
        $arr[7] = 'E';
        $pre_po = Cache::get("xy");//前一点
        $cur_po = $this->trans_localcoord(); //当前点

        $arr[4] = $cur_po['x'];
        $arr[6] = $cur_po['y'];
        $cur_str = implode(",", $arr);

        $arr[2] = '0' . ($arr[2] - 0.1) . '0';
        $arr[4] = round(($cur_po['x'] + $pre_po['x']) / 2, 4);
        $arr[6] = round(($cur_po['y'] + $pre_po['y']) / 2, 4);
        $arr[12] = substr($arr[12], 0, 3) . chr(rand(48, 57));
        $pre_str = implode(",", $arr);

        Cache::set("xy", $cur_po);

        return "$" . $pre_str . "\r\n$" . $cur_str;
    }

    /**
     * 获取本地localcoord坐标
     *
     * @return array["x","y"]
     */
    private function trans_localcoord()
    {
        $gnss = Cache::get("Sensor:GPS0:data");

        $gnss1 = Cache::get("Sensor:GPS1:data");
        //压实路面高程等于测量高程减去车高
        // $hi = $gnss['height'] - $this->offsetHI;
        $master = $this->localcoord ->getXY($gnss);
        $master['z'] = $gnss['height'] - 16;

        // 双天线方案
        if(!empty($gnss1))
        {
            $slave = $this->localcoord ->getXY($gnss1);
            // 计算车辆摆放的方向
            $car_drct = ($master['x'] - $slave['x'])/($master['y'] - $slave['y']);
            Cache::set("car_drct",$car_drct);
        }
        $this->get_Pile_section($master['x'], $master['y'], $master['z'], $car_drct);
        return $XY;
    }

   
    // 获取桩区间和计算偏移量
    public function get_Pile_section($x, $y, $h, $car_drct)
    {
        $mileages = MCU\SysConfig::get('mileages/coordinate');
        foreach ($mileages as $key => $v) 
        {
            if ($v['x'] > $x) 
            {
                if ($v['y'] < $y) {
                   $arr1[] = $v;
                }
            }else{
                if ($v['y'] >= $y) {
                   $arr2[] = $v;
                }
            }
           
        }
        
        $data['pile_start']        = end($arr2);
        $data['pile_current']['x'] = $x;
        $data['pile_current']['y'] = $y;
        $data['pile_current']['z'] = $h;
        $data['pile_current']['car_drct'] = $car_drct;
        $data['pile_end']          = $arr1[0];


        // 获取偏移在左边负还是右边正
        // 直线方程 (c-a)*n-(d-b)*s - b(c-a) + a(d-b) = 0
        // 把点的坐标,带入直线方程左边式子,大于0,点为直线上边,小于0在直线下边,等于0,在线上.
        $L = ($data['pile_end']['x']-$data['pile_start']['x'])*$data['pile_current']['y'] - ($data['pile_end']['y']-$data['pile_start']['y'])*$data['pile_current']['x']-$data['pile_current']['y']*($data['pile_end']['x']-$data['pile_start']['x'])+$data['pile_start']['x']*($data['pile_end']['y']-$data['pile_start']['y']);

        // 原理为点到直线的距离计算出偏移量
        // A(a,b) , B(c,d), C(s,n);
        // abs((c-a)*n-(d-b)*s - b(c-a) + a(d-b)) / sqrt(abs(pow((c-a),2)+pow((d-b),2))) = h
        $data['shifting'] = abs($L)/sqrt(abs(pow($data['pile_end']['x']-$data['pile_start']['x'],2)+pow($data['pile_end']['y']-$data['pile_start']['y'],2)));
        if ($L < 0) 
        {
           $data['shifting'] = -1*$data['shifting'];
        }
        elseif ($L == 0) 
        {
            $data['shifting'] = 0*$data['shifting'];
        }

        // 计算出里程
        // 2,计算出开始坐标到当前坐标的距离 $ac = sqrt(pow(s-a)+pow(s-a))
        // 3、通过勾股定理算出开始坐标到当前坐标在中心线垂直点的距离 a*a + b*b = c*c
        $ac = sqrt(pow(($data['pile_current']['x']-$data['pile_start']['x']),2)+pow(($data['pile_current']['y']-$data['pile_start']['y']),2));
        $data['dkcode']  = substr($data['pile_start']['mile'],0,3);
        $data['mileage'] = substr($data['pile_start']['mile'],3,6)+number_format(sqrt(abs(pow($ac,2)-pow($data['shifting'],2))),3);
        $data['pile_current']['mile'] = $data['dkcode'].$data['mileage'];



        Cache::set("Sensor:Pilesection:data", $data);
    }
}
<?php

namespace MCU\Positioning;

class GpsData
{
	public static function parse($str)
    {
    	$gps = [];
    	$gps_arr = explode('$', $str);
    	foreach ($gps_arr as $key => $value) {
    		$arr = explode(',', $value);
    		switch($arr[0])
    		{
    			case 'GPGGA':
    				self::gpgga_parse($gps,$arr);
    				break;
    			case 'GPRMC':
    				self::gprmc_parse($gps,$arr);
    				break;
    			default:
    				break;
    		}
    	}
    	return $gps;
    }

	private static function gpgga_parse(&$gps, $arr)
    {
        /*
        $GPGGA,080600.50,2942.87458519,N,10639.78727083,E,4,24,0.7,395.372,M,-29.529,M,1.5,0000*65
        字段0：$GPGGA，语句ID，表明该语句为Global Positioning System Fix Data（GGA）GPS定位信息
        字段1：UTC 时间，hhmmss.sss，时分秒格式
        字段2：纬度ddmm.mmmm，度分格式（前导位数不足则补0）
        字段3：纬度N（北纬）或S（南纬）
        字段4：经度dddmm.mmmm，度分格式（前导位数不足则补0）
        字段5：经度E（东经）或W（西经）
        字段6：GPS状态，0=未定位，1=非差分定位，2=差分定位，3=无效PPS，6=正在估算
        字段7：正在使用的卫星数量（00 - 12）（前导位数不足则补0）
        字段8：HDOP水平精度因子（0.5 - 99.9）
        字段9：海拔高度（-9999.9 - 99999.9）
        字段10：地球椭球面相对大地水准面的高度
        字段11：差分时间（从最近一次接收到差分信号开始的秒数，如果不是差分定位将为空）
        字段12：差分站ID号0000 - 1023（前导位数不足则补0，如果不是差分定位将为空）
        字段13：校验值
        */

        $gps['lat'] = 0;
        $gps['lon'] = 0;
        $gps['quality'] = 0;
        $gps['snum'] = 0;
        $gps['altitude'] = 0;
        $gps['height'] = 0;
        $gps['elevation'] = 0;
        $gps['rtkdelay'] = 0;

        if(count($arr) < 14) return;

        //纬度解析
        $lat 	= floatval($arr[2]) / 100;
        $lat_dd = floor($lat);
        $lat 	= $lat_dd + ($lat - $lat_dd) * 100 / 60;
        $gps['lat'] = round($lat, 9);

        //经度解析
        $lon 	= floatval($arr[4]) / 100;
        $lon_dd = floor($lon);
        $lon 	= $lon_dd + ($lon - $lon_dd) * 100 / 60;
        $gps['lon'] = round($lon, 9);

        //定位质量指示
        $gps['quality'] = intval($arr[6]);

        //使用卫星数量
        $gps['snum'] = intval($arr[7]);

        //水平精确度，0.5到99.9
        // $gps['hdop'] = $arr[8];

        //海拔高程
        $gps['altitude'] = round(floatval($arr[9]), 3);
        $gps['height'] = round(floatval($arr[9]), 3); // 逐渐弃用

        //椭球高程
        $gps['elevation'] = round($gps['altitude'] + floatval($arr[11]), 3);

        //基准信号延迟
        $gps['rtkdelay'] = floatval($arr[13]);
    }
    private static function gprmc_parse(&$gps,$arr)
    {
        /*
         * $GPRMC
            例：$GPRMC,085223.136,A,3957.6286,N,11619.2078,E,0.06,36.81,180908,,,A*57
            字段0：$GPRMC，语句ID，表明该语句为Recommended Minimum Specific GPS/TRANSIT Data（RMC）推荐最小定位信息
            字段1：UTC时间，hhmmss.sss格式
            字段2：状态，A=定位，V=未定位
            字段3：纬度ddmm.mmmm，度分格式（前导位数不足则补0）
            字段4：纬度N（北纬）或S（南纬）
            字段5：经度dddmm.mmmm，度分格式（前导位数不足则补0）
            字段6：经度E（东经）或W（西经）
            字段7：速度，节，Knots
            字段8：地面航向(000.0~359.9度，以正北为参考基准，前面的0也将被传输)
            字段9：UTC日期，DDMMYY格式
            字段10：磁偏角，（000 - 180）度（前导位数不足则补0）
            字段11：磁偏角方向，E=东W=西
            字段12：校验值
          一节=一海里/小时=1.852公里/小时 [节]
        */

        $gps['gpstime'] = '1970-01-01 00:00:00';
        $gps['timestamp'] = 0;
        $gps['drct'] = 0;
        $gps['speed'] = 0;

        if(count($arr) < 9) return;

        //定位状态 A=定位 V=未定位
        if($arr[2] =='V') return;
        //utc时间，hhmmss.sss格式
        $utc_time = str_split($arr[1],2);

        //utc日期，DDMMYY格式
        $utc_date = str_split($arr[9],2);

        //GPSTIME ("2009-10-21 16:00:10");
        $gps['gpstime'] = '20'.$utc_date[2].'-'.$utc_date[1].'-'.$utc_date[0].' '.$utc_time[0].':'.$utc_time[1].':'.$utc_time[2];
        $gps['timestamp'] = strtotime($gps['gpstime']);

        //速度
        $gps['speed'] = floatval($arr[7]) * 1.852;

        // 方向
        $gps['drct'] = floatval($arr[8]);

        //$gps['magnetic'] = $arr[11].$arr[10];

        
    }
}
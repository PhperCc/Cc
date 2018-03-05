<?php

namespace MCU\Positioning;

class GpsPoint
{
    const RE = 6371004;	// 地球半径，单位 米

    public $lon = 0;
    public $lat = 0;
    public $height = 0;

	public function __construct($lon, $lat, $height = 0)
	{
        if(is_array($lon))
        {
            $gps = $lon;
            if(array_key_exists("lon", $gps) && array_key_exists("lat", $gps))
            {
                $this -> lon = $gps["lon"];
                $this -> lat = $gps["lat"];
                if(array_key_exists("height", $gps)) $this -> height = $gps["height"];
                if(array_key_exists("hi", $gps)) $this -> height = $gps["hi"];
            }
            return $this;
        }
		$this -> lon = $lon;
        $this -> lat = $lat;
        $this -> height = $height;
	}

    // 返回到另一个GPS点的平面距离， 单位米
    public function distance(GpsPoint $otherGpsPoint)
    {
        $lonSpan = abs($this -> lon - $otherGpsPoint -> lon);
        $latSpan = abs($this -> lat - $otherGpsPoint -> lat);
        $middleLat = ($this -> lat + $otherGpsPoint -> lat) / 2;

        $EWmeters = $lonSpan * $this -> getMetersPerLon($middleLat);
        $NSmeters = $latSpan * $this -> getMetersPerLat();

        $distanceMeters = pow(pow($EWmeters, 2) + pow($NSmeters, 2), 0.5);
        return $distanceMeters;
    }

    // 返回从当前点到指定点的连线， 相对于正北的角度值
    public function getNorthAngle(GpsPoint $otherGpsPoint)
    {
        if($this -> lon == $otherGpsPoint -> lon) return $this -> lat <= $otherGpsPoint -> lat ? 0 : 180;
        if($this -> lat == $otherGpsPoint -> lat) return $this -> lon <= $otherGpsPoint -> lon ? 90 : 270;

        $lonSpan = abs($this -> lon - $otherGpsPoint -> lon);
        $latSpan = abs($this -> lat - $otherGpsPoint -> lat);
        $middleLat = ($this -> lat + $otherGpsPoint -> lat) / 2;

        $EWmeters = $lonSpan * $this -> getMetersPerLon($middleLat);
        $NSmeters = $latSpan * $this -> getMetersPerLat();

        $northRadian = atan($EWmeters / $NSmeters);
        $northAngle = rad2deg($northRadian); //$northRadian * 180 / pi();    // 弧度转角度

        if(($this -> lon < $otherGpsPoint -> lon) && ($this -> lat > $otherGpsPoint -> lat)) $northAngle = 180 - $northAngle;
        elseif (($this -> lon > $otherGpsPoint -> lon) && ($this -> lat > $otherGpsPoint -> lat)) $northAngle = $northAngle + 180;
        elseif (($this -> lon > $otherGpsPoint -> lon) && ($this -> lat < $otherGpsPoint -> lat)) $northAngle = 360 - $northAngle;
        while($northAngle < 0) $northAngle += 360;
        while($northAngle > 360) $northAngle -= 360;
        return $northAngle;
    }

    // 返回向指定角度移动指定距离后的点
    // angle: 表示以正北为0度， 顺时针旋转的角度值
    public function move($meters, $angle, $returnNewObj = false)
    {
        $radian = deg2rad($angle); // $angle * pi() / 180.0;  // 弧度
        $lonMeters = $meters * sin($radian);
        $latMeters = $meters * cos($radian);

        $latSpan = $latMeters / $this -> getMetersPerLat();
        $lonSpan = $lonMeters / $this -> getMetersPerLon($this -> lat + $latSpan / 2);

        if($returnNewObj) return new GpsPoint($this -> lon + $lonSpan, $this -> lat + $latSpan);
        $this -> lon += $lonSpan;
        $this -> lat += $latSpan;
        return $this;
    }

    // 1纬度的距离
    private $metersPerLat = 0;
    private function getMetersPerLat()
    {
        if($this -> metersPerLat == 0) $this -> metersPerLat = pi() * self::RE * 2.0 / 360.0;
        return $this -> metersPerLat;
    }

    // 在指定纬度下， 1经度的距离
    private function getMetersPerLon($lat)
    {
        $radian = deg2rad(abs($lat)); // abs($lat) * pi() / 180.0;  // 弧度
        $RE_lat = self::RE * cos($radian);
        return pi() * $RE_lat * 2.0 / 360.0;
    }
}


/*
$a = new GpsPoint(104.402227822, 30.319676002); // ENH定位
$b = new GpsPoint(104.402223881, 30.319671742); // 华测定位

echo "drct: " . $a -> getNorthAngle($b);
echo "\n";
echo "dist: " . $a -> distance($b);

// result: 
// drct: 218.61023081194
// dist: 0.60620065691837

*/
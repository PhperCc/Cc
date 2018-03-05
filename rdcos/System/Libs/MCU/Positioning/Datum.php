<?php

namespace MCU\Positioning;

/*
    椭球类
*/
class Datum
{
    private $_a = 0;
    private $_b = 0;
    private $_f = 0;
    private $_e12 = 0;
    private $_e22 = 0;

    private $_l0 = 0;
    private $_zoneWidth = 6;

    public function __get($property_name)
    {
        switch($property_name)
        {
            case "A": return $this -> _a;
            case "B": return $this -> _b;
            case "F": return $this -> _f;
            case "E12": return $this -> _e12;
            case "E22": return $this -> _e22;
            case "L0": return $this -> _l0;
            case "zoneWidth": return $this -> _zoneWidth;
        }
        throw new Exception("Datum类中不存在属性名 $property_name");
    }

    // public static $BJ54 = new Datum(6378245, 1/298.3);
    // public static $XA80 = new Datum(6378140, 1/298.257);
    // public static $WGS84 = new Datum(6378137, 1/298.257223563);

    public function Datum($a, $f)
    {
        $this -> _a = $a;
        $this -> _f = $f;   // $f = ($a - $b) / $a;
        $this -> _b = $b = $a - $f * $a;

        // $this -> _e12 = 2 * $f - $f * $f;
        $this -> _e12 = $e12 = ($a * $a - $b * $b) / ($a * $a);    // 第一偏心率平方

        // $this -> _e22 = $this -> _e12 * $a * $a / ($b * $b);
        $this -> _e22 = $e22 = ($a * $a - $b * $b) / ($b * $b);     // 第二偏心率平方
    }

    public function setL0($l0)
    {
        $this -> _l0 = $l0;
    }

    public function setZoneWidth($zoneWidth)
    {
        if($zoneWidth !== 6 && $zoneWidth !== 3)
            throw new Exception("带宽应当为6或者3");
        $this -> _zoneWidth = $zoneWidth;
    }

    public function getZoneNo($l)
    {
        // 6度带
        if($this -> _zoneWidth == 6) return intval($l / 6) + 1;
        // 3度带
        return intval($l - 1.5 / 3) + 1;
    }

    public static function getBJ54(){ return new Datum(6378245, 1/298.3); }
    public static function getXA80(){ return new Datum(6378140, 1/298.257); }
    public static function getWGS84(){ return new Datum(6378137, 1/298.257223563); }
    public static function getCGCS2000(){ return new Datum(6378137, 1/298.257222101); }
}
<?php

namespace MCU\Positioning;

/*
    地心直角坐标点
*/
class PointXYZ
{
    public $X = 0;
    public $Y = 0;
    public $Z = 0;

    public function PointXYZ($x, $y, $z)
    {
        $this -> X = $x;
        $this -> Y = $y;
        $this -> Z = $z;
    }

    public function toString()
    {
        return "X: {$this -> X}, Y: {$this -> Y}, Z: {$this -> Z}";
    }
}
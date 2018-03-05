<?php

namespace MCU\Positioning;

/*
    大地平面坐标点
*/
class Pointxy
{
    public $x = 0;
    public $y = 0;

    public function Pointxy($x, $y)
    {
        $this -> x = $x;
        $this -> y = $y;
    }

    public function toString()
    {
        return "x: {$this -> x}, y: {$this -> y}";
    }
}
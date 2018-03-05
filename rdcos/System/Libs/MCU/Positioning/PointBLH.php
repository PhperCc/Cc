<?php

namespace MCU\Positioning;

/*
    地心大地坐标点
*/
class PointBLH
{
    public $B = 0;
    public $L = 0;
    public $H = 0;

    public function PointBLH($b, $l, $h = 0)
    {
        $this -> B = $b;
        $this -> L = $l;
        $this -> H = $h;
    }

    public function toString()
    {
        return "B: {$this -> B}, L: {$this -> L}, H: {$this -> H}";
    }
}

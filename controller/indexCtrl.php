<?php
namespace controller;
use \classes\DBbase;
use \classes\Cache;

header("Content-type: text/html; charset=utf-8");
ini_set('max_execution_time', '0');
class indexCtrl
{
    public function index ()
    {
        Cache::hset('grids:3',1,2);
        Cache::hset('grids:4',1,2);
        Cache::hset('grids:5',1,2);
        Cache::hset('grids:6',1,2);
        // Cache::del('grids');
        // $t1 =  microtime(true);
        // for($x = 1; $x <= 20; $x++)
        // {
        //     for($y = -10;$y <=10;$y++)
        //     {
        //         Cache::hset("grids",$x.",".$y,"temp=".rand(80,100).",speed=".rand(80,100).",froct=".rand(80,100).",drct=".rand(80,100).",uid=98efde341f0e");
                
        //     }       
        // }
        // $t2 =  microtime(true);
        $all = Cache::hgetall('grids:*');
        // $t3 =  microtime(true);
        // p(microtime(true));
        // p($t2-$t1);
        // p($t3-$t2);
        p($all);

        

    }
}
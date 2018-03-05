<?php
/**
 * 产品业务逻辑
 */
use MCU\Module;

class svc_main extends Module
{
    public function start()
    {
        $ec = new TestLib();
        $ec->ec(); 
        //在此写逻辑代码
    }
}
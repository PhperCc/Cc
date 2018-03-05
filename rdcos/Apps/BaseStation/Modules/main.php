<?php
/**
 * 基站
 */
use MCU\Module;

class svc_main extends Module
{
    public function start()
    {
        global $selfVersion;
        $selfVersion = '2.0.0';
    }
}
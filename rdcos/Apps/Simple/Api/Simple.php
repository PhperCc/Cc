<?php
/**
 * Simple测试类
 */
class api_Simple extends MCU\ApiHelper\ApiBase
{
    /**
     * 设置WIFI名称和密码
     * @params string 测试参数
     */
    public function test($params)
    {
        if(false === get_param($params, "params"))
        {
            return R(false, "need param 'params'");
        }
        else
        {
           return R(true, "", time()); 
        }
    }  
}
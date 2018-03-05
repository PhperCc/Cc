<?php
use MCU\ModuleConfig;

/**
 * 服务配置相关接口
 */
class api_ServiceConfig extends MCU\ApiHelper\ApiBase
{
    /**
     * 取服务配置
     * @module string 模块名
     * @#service string 服务名, 若未指定， 则返回模块下所有配置内容
     * @#key string 配置键名, 若未指定， 则返回服务下所有配置内容
     * @#default mixed 若指定配置键名不存在时返回的默认值, 若未指定， 默认值为null
     *
     * return data mixed 配置值
     */
    public function get($params)
    {
        if(!array_key_exists("module", $params)) return R(false, "need param 'module'");

        $module = $params["module"];
        $service = array_key_exists("service", $params) ? $params["service"] : null;
        $key = array_key_exists("key", $params) ? $params["key"] : null;
        $default = array_key_exists("default", $params) ? $params["default"] : null;

        return R(true, "", ModuleConfig::get($module, $service, $key, $default));
    }

    /**
     * 取服务配置
     * @module string 模块名
     * @service string 服务名
     * @key string 配置键名
     * @val mixed 配置值
     *
     * return data mixed 修改后的配置信息
     */
    public function set($params)
    {
        if(!array_key_exists("module", $params)) return R(false, "need param 'module'");
        //if(!array_key_exists("service", $params)) return R(false, "need param 'service'");
        if(!array_key_exists("key", $params)) return R(false, "need param 'key'");
        if(!array_key_exists("val", $params)) return R(false, "need param 'val'");

        $module = $params["module"];
        $service = $params["service"];
        $key = $params["key"];
        $val = $params["val"];
        if(false === ModuleConfig::set($module, $service, $key, $val, true, true))
        {
            return R(false, "save faild", ['moduld' => $module, 'service' => $service, 'key' => $key, 'val' => $val]);
        }
        return R(true, "", ['moduld' => $module, 'service' => $service, 'key' => $key, 'val' => $val]);
    }
}
<?php
use MCU\Sys;

/**
 * 系统配置相关接口
 */
class api_SysConfig extends MCU\ApiHelper\ApiBase
{
    /**
     * 取系统配置
     * @#key string 配置键名, 若未指定， 则返回所有系统配置内容
     * @#default mixed 若指定系统配置键名不存在时返回的默认值, 若未指定， 默认值为null
     *
     * return data mixed 配置值
     */
    public function get($params)
    {
        $key = array_key_exists("key", $params) ? $params["key"] : null;
        $default = array_key_exists("default", $params) ? $params["default"] : null;

        return R(true, "", Sys::get_config($key, $default));
    }

    /**
     * 写系统配置
     * @key string 配置键名
     * @val mixed 配置值
     *
     * return data mixed 修改后的配置信息
     */
    public function set($params)
    {
        if(!array_key_exists("key", $params)) return R(false, "need param 'key'");
        if(!array_key_exists("val", $params)) return R(false, "need param 'val'");

        $key = $params["key"];
        $val = $params["val"];

        if(false === Sys::set_config($key, $val))
        {
            return R(false, "save faild", ['key' => $key, 'val' => $val]);
        }
        return R(true, "", ['key' => $key, 'val' => $val]);
    }
}
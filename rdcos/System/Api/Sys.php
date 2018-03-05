<?php
use MCU\Sys;

/**
 * 系统信息相关接口
 */
class api_Sys extends MCU\ApiHelper\ApiBase
{
    /**
     * 取设备UID
     *
     * return data string 设备UID
     */
    public function getUid($params)
    {
        $uid = Sys::get_uid();
        return R(true, "", $uid);
    }

    /**
     * 取版本信息
     *
     * return data array 版本信息:{core: 内核版本, release: 发行版本, build: 编译时间, app: 应用版本}
     */
    public function getVersionInfo($params)
    {
        $version_info = Sys::get_version_info();
        return R(true, "", $version_info);
    }

    /**
     * 重启设备
     * @#reason string 原因描述
     */
    public function reboot($params)
    {
        $reason = "";

        if (array_key_exists("reason", $params)) {
            $reason = $params['reason'];
        }
        \Channel\Client::publish('CoreSystemCommand', 'reboot');
        Sys::reboot($reason);

        return true;
    }

    /**
     * 重启服务
     * @type string 类型 core 内核 main 产品
     * @#reason string 原因描述
     */
    public function restart($params)
    {
        $reason = "";

        if (array_key_exists("reason", $params)) {
            $reason = $params['reason'];
        }
        Sys::restart($params['type']);

        return true;
    }

    /**
     * 上报代码运行错误/异常
     * @content string 上报内容
     */
    public function report_error($params)
    {
        if (!array_key_exists("content", $params)) {
            return R(false, "need param 'content'");
        }
        Sys::report_error($params['content']);
        return true;
    }
}

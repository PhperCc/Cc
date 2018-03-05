<?php

namespace MCU\Utils;
use \MCU\Logger;
use \MCU\Utils\FileSystem;

class OperationSystem
{   
    /**
     * 操作系统重启
     *
     * @param string $reason 操作理由
     *
     * @return void
     */
    public static function reboot($reason = "")
    {
        FileSystem ::refresh_disk_cache();
        Logger::log("reboot, reason: $reason", "OSCommand");
        static::exec("reboot");
    }

    /**
     * 操作系统关机
     *
     * @param string $reason 操作理由
     *
     * @return void
     */
    public static function power_off($reason = "")
    {
        FileSystem::refresh_disk_cache();
        Logger::log("shutdown, reason: $reason", "OSCommand");
        // exec("shutdown -t now");
        static::exec("echo o > /proc/sysrq-trigger");   // fast shut down
    }

    /**
     * 执行shell命令
     *
     * @param string $command 命令行
     * @param array  $output  命令输出内容
     *
     * @return string 命令输出内容的最后一行
     */
    public static function exec($command, &$output)
    {
        Logger::log("exec: $command", "OSCommand");
        $return = exec("$command 2>/dev/null", $output);

        return $return;
    }

    /**
     * 取网卡信息
     *
     * @param string $name    网卡名称
     * @param string $field   网卡信息字段名
     * @param string $def_val 默认值
     *
     * @return array|mixed|string
     */
    public static function get_network_info($name = "eth0", $field = "", $def_val = "")
    {
        static::exec("ifconfig $name", $lines);
        $network_info = ["mac" => "", "ip" => "", "state" => "down", "bcast" => "", "mask" => "", "rxb" => 0, "txb" => 0];
        foreach($lines as $line)
        {
            if(preg_match("/HWaddr [0-9a-f:]+/i", $line, $matches) > 0)
            {
                $network_info["mac"] = strtoupper(str_replace("HWaddr ", "", $matches[0]));
            }

            if(preg_match("/inet addr:([0-9\.]+)/i", $line, $matches) > 0)
            {
                $network_info["ip"] = str_replace("inet addr:", "", $matches[0]);
            }

            if(preg_match("/UP (?:\w+ )?RUNNING/i", $line, $matches) > 0)
            {
                $network_info["state"] = str_replace("State:", "", $matches[0]);
            }

            if(preg_match("/Bcast:([0-9\.]+)/i", $line, $matches) > 0)
            {
                $network_info["bcast"] = str_replace("Bcast:", "", $matches[0]);
            }

            if(preg_match("/Mask:([0-9\.]+)/i", $line, $matches) > 0)
            {
                $network_info["mask"] = str_replace("Mask:", "", $matches[0]);
            }

            if(preg_match("/RX bytes:([0-9]+)/i", $line, $matches) > 0)
            {
                $network_info["rxb"] = str_replace("RX bytes:", "", $matches[0]);
            }

            if(preg_match("/TX bytes:([0-9]+)/i", $line, $matches) > 0)
            {
                $network_info["txb"] = str_replace("TX bytes:", "", $matches[0]);
            }
        }
        if(!empty($field))
        {
            return array_key_exists($field, $network_info) ? $network_info[$field] : $def_val;
        }
        return $network_info;
    }
}
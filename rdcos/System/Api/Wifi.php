<?php
/**
 * WIFI操作接口
 */
class api_Wifi extends MCU\ApiHelper\ApiBase
{
    const CONFIG_FILE_PATH = "/etc/hostapd/hostapd.conf";

    /**
     * 设置WIFI名称和密码
     * @ssid string WIFI名称
     * @#pass string WIFI密码， 当留空时不修改密码
     * @#apply bool 修改后应用(会重启wifi服务), 默认为 true
     */
    public function set_auth($params)
    {
        if(false === get_param($params, "ssid"))
        {
            return R(false, "need param 'ssid'");
        }

        $config = $this->read_config();
        $config['ssid'] = $params['ssid'];
        if('' !== get_param($params, "pass", ''))
        {
            $config['wpa_passphrase'] = $params['pass'];
        }

        if($this->write_config($config))
        {
            if(get_param($params, "apply", true))
            {
                Sys::exec("systemctl restart hostapd");
            }

            return R(true, "", $keys);
        }
        else
        {
            return R(false, "write config file failed");
        }
    }

    /**
     * 获取WIFI名称
     *
     * return data string WIFI名称
     */
    public function get_ssid($params)
    {
        $config = $this -> read_config();
        return R(true, "", $config['ssid']);
    }

    /**
     * 获取WIFI密码
     *
     * return data string WIFI密码
     */
    public function get_pass($params)
    {
        $config = $this -> read_config();
        return R(true, "", $config['wpa_passphrase']);
    }

    /**
     * 写入WIFI配置信息
     */
    private function write_config($config)
    {
        if(!is_array($config)) return false;

        $config_lines = [];
        foreach($config as $k => $v)
        {
            $config_lines[] = "$k=$v";
        }
        $config_contents = implode("\n", $config_lines);

        $bytes = file_put_contents(static::CONFIG_FILE_PATH, $config_contents);
        return $bytes == strlen($config_contents);
    }

    /**
     * 读取WIFI配置信息
     */
    private function read_config()
    {
        $config_contents = file_get_contents(static::CONFIG_FILE_PATH);
        $config_lines = explode("\n", $config_contents);
        $config = [];
        foreach($config_lines as $line)
        {
            if(empty($line)) continue;

            list($key, $val) = explode("=", trim($line));
            $config[$key] = $val;
        }
        return $config;
    }
}
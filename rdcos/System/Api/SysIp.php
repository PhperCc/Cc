<?php
/**
 * lan ip操作接口
 */
class api_SysIp extends MCU\ApiHelper\ApiBase
{
    const CONFIG_FILE_PATH = '/etc/network/interfaces.d/eth0';
    private static $_prefix = ['auto eth0','iface eth0 inet static'];

    /**
     * 设置系统lan ip 地址
     * @address string ip地址
     * @#gateway string 网关， 当留空时不修改密码
     * @#netmask string 子网掩码， 当留空时不修改密码
     * @#apply bool 修改后应用(会重启网络), 默认为 true
     */
    public function set_ip($params)
    {
        if(false === get_param($params, "address"))
        {
            return R(false, "need param 'address'");
        }

        $config = read_config();
        $config['address'] = $params['address'];

        if('' !== $this -> get_param($params, "gateway", ''))
        {
            $config['gateway'] = $params['gateway'];
        }

        if('' !== $this -> get_param($params, "netmask", ''))
        {
            $config['netmask'] = $params['netmask'];
        }

        if($this -> write_config($config))
        {
            if(get_param($params, "apply", true))
            {
                Sys::exec('systemctl restart networking');
            }

            return R(true, '', $keys);
        }
        else
        {
            return R(false, 'write config file failed');
        }
    }

    /**
     * 获取ip信息
     *
     * return data string IP配置信息
     */
    public function get_ip($params)
    {
        $config = $this->read_config();
        return R(true, '', $config);
    }

    /**
     * 写入ip配置信息
     */
    private function write_config($config)
    {
        if(!is_array($config)) return false;

        $config_lines = static::$_prefix;
        foreach($config as $k => $v)
        {
            $config_lines[] = "$k $v";
        }
        $config_contents = implode("\n", $config_lines);

        $bytes = file_put_contents(static::CONFIG_FILE_PATH, $config_contents);
        return $bytes == strlen($config_contents);
    }

    /**
     * 读取ip配置信息
     */
    private function read_config()
    {
        $config_contents = file_get_contents(static::CONFIG_FILE_PATH);
        $config_lines = explode("\n", $config_contents);
        $config = [];

        foreach($config_lines as $line)
        {
            $line = trim($line);
            if(empty($line) || in_array($line, static::$_prefix) || substr($line,0,1) == '#') continue;

            list($key, $val) = explode(' ', trim($line));
            $config[trim($key)] = trim($val);
        }
        return $config;
    }
}
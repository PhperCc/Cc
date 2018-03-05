<?php
/**
 * lan ip操作接口
 */
class api_LanIp extends MCU\ApiHelper\ApiBase
{
    const CONFIG_FILE_PATH = '/etc/network/interfaces.d/eth';
    private static $_prefix = [['auto eth0','iface eth0 inet static'],['auto eth1','iface eth1 inet static']];

    /**
     * 设置系统lan ip 地址
     * @address string ip地址
     * @#gateway string 网关， 当留空时不修改
     * @#netmask string 子网掩码， 当留空时不修改
     * @#index int 网卡序号,默认0
     * @#apply bool 修改后应用(会重启网络), 默认为 true
     */
    public function set_ip($params)
    {
        if('' === get_param($params, 'address',''))
        {
            return R(false, "need param 'address'");
        }

        $config = $this->read_config();
        $config['address'] = $params['address'];
        $index = get_param($params, 'index',0);

        if('' !== get_param($params, "gateway", ''))
        {
            $config['gateway'] = $params['gateway'];
        }

        if('' !== get_param($params, "netmask", ''))
        {
            $config['netmask'] = $params['netmask'];
        }

        if($this->write_config($config,$index))
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
     * @#index int 网卡序号,默认0
     * return data string IP配置信息
     */
    public function get_ip($params)
    {
        $index = get_param($params, 'index',0);
        $config = $this->read_config($index);
        return R(true, '', $config);
    }

    /**
     * 写入ip配置信息
     */
    private function write_config($config,$index=0)
    {
        if(!is_array($config)) return false;

        $config_lines = static::$_prefix[$index];
        foreach($config as $k => $v)
        {
            $config_lines[] = "$k $v";
        }
        $config_contents = implode("\n", $config_lines);

        $bytes = file_put_contents(static::CONFIG_FILE_PATH.$index, $config_contents);
        return $bytes == strlen($config_contents);
    }

    /**
     * 读取ip配置信息
     */
    private function read_config($index = 0)
    {
        $config_contents = file_get_contents(static::CONFIG_FILE_PATH.$index);
        $config_lines = explode("\n", $config_contents);
        $config = [];

        foreach($config_lines as $line)
        {
            $line = trim($line);
            if(empty($line) || in_array($line, static::$_prefix[$index])) continue;

            list($key, $val) = explode(' ', trim($line));
            $config[trim($key)] = trim($val);
        }
        return $config;
    }
}
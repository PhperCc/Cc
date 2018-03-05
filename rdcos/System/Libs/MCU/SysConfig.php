<?php
namespace MCU;

class SysConfig
{
    private static $_default_config = [];
    private static $_custom_config = [];
    private static $_merged_config = [];

	public static function get($path = null, $default = null)
	{
        self::load();
        $path_nodes = [];
        if(!empty($path))
        {
            $path = trim($path, "/");
            $path_nodes = explode("/", $path);
        }

        $val = self::$_merged_config;
        foreach($path_nodes as $node)
        {
            if(empty($node)) continue;
            if(!array_key_exists($node, $val)) return $default;
            $val = $val[$node];
        }
        return $val;
	}

    public static function set($path, $val, $write_to_file = false, $publish = false)
    {
        $cur_val = self::get($path);
        if($val == $cur_val) return true;

        $path_nodes = [];
        if(!empty($path))
        {
            $path = trim($path, "/");
            $path_nodes = explode("/", $path);
        }

        $cur_path = &self::$_custom_config;
        foreach($path_nodes as $node)
        {
            if(empty($node)) continue;
            $cur_path = &$cur_path[$node];
        }
        $cur_path = $val;
        if($path == 'product')
        {
            if('BaseStation' == $val)
            {
                self::$_custom_config['rtkMode'] = 'base';
            }
            else
            {
                self::$_custom_config['rtkMode'] = 'moving';
            }
        }
        
        self::merge();
        
        if($write_to_file)
        {
            Logger::log('SysConfig: save: ' . json_encode(self::$_custom_config));
            if(false === LocalFile::putObject("config/sysconfig", self::$_custom_config))
            {
                Logger::log('SysConfig: save faild: ' . json_encode(self::$_custom_config));
                return false;
            }
        }
        if($publish)
        {
            \Channel\Client::publish("SYS.SysConfig.changed", ["path" => $path, "val" => $val]);
        }
        return true;
    }

    private static function load()
    {
        $default_config_file =  SYS_ROOT . 'Config.php';
        if(file_exists($default_config_file))
        {
            $tmp_config = include($default_config_file);
            foreach ($tmp_config as $key => $value) 
            {
                if(!in_array($key,['model','sensor','desc']))
                {
                    self::$_default_config[$key] = $value;
                }
            }
        }
        
        self::$_custom_config = LocalFile::getObject("config/sysconfig", []);
        self::merge();
    }

    private static function merge()
    {
        self::$_merged_config = array_deep_merge(self::$_default_config, self::$_custom_config);
    }
}
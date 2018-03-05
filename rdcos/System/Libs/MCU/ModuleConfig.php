<?php
namespace MCU;

class ModuleConfig
{
	private static $_default_config = [];
    private static $_custom_config = [];
    private static $_merged_config = [];

    public static $module_root =  "";

	public static function get($module, $service = null, $path = null, $default = null)
	{ 
        if(empty($module)) return $default;
        self::load($module);

        $path_nodes = [];
        $val = self::$_merged_config[$module];
        
        
        if(!empty($service))
        {
            $val = $val[$service];
        }

        if(!empty($path))
        {
            $path = trim($path, "/");
            $path_nodes = array_merge($path_nodes, explode("/", $path));

            foreach($path_nodes as $node)
            {
                if(empty($node)) continue;
                if(!array_key_exists($node, $val)) return $default;
                $val = $val[$node];
            }
        }   

        return $val;
	}


    public static function set($module, $service, $path, $val, $write_to_file = false, $publish = false)
    {
        if(empty($module)) return false;
        $cur_val = self::get($module,$service,$path);
        if($cur_val == $val) return true;

        $path_nodes = [$module, $service];
        if(!empty($path))
        {
            $path = trim($path, "/");
            $path_nodes = array_merge($path_nodes, explode("/", $path));
        }

        $cur_path = &self::$_custom_config;

        foreach($path_nodes as $node)
        {
            if(empty($node) || $node=='Apps') continue; 
            $cur_path = &$cur_path[$node];
        }
        $cur_path = $val;

        self::merge($module);
        if($write_to_file)
        {
            Logger::log('ModuleConfig: save: ' . json_encode(self::$_custom_config[$module]));
            if(false === LocalFile::putObject("config/$module", self::$_custom_config[$module]))
            {
                Logger::log('ModuleConfig: save faild: ' . json_encode(self::$_custom_config[$module]));
                return false;
            }
        }
        if($publish)
        {
            \Channel\Client::publish('ModuleConfigChanged', ["module" => $module, "service" => $service, "path" => $path, "val" => $val]);
        }
        return true;
    }


    private static function load($module)
    {
        if($module == 'System')
        {
            self::$module_root =  SYS_ROOT;
            
        }
        else
        {
            self::$module_root =  BASE_ROOT.'/Apps/'.$module.'/';
        }
        $default_config_file = self::$module_root . 'Config.php';
        self::$_default_config[$module] = file_exists($default_config_file) ? include($default_config_file) : [];
        self::$_custom_config[$module] = LocalFile::getObject("config/$module", []);
        self::merge($module);
        
    }

    private static function merge($module)
    {
        self::$_merged_config[$module] = array_deep_merge(self::$_default_config[$module], self::$_custom_config[$module]);
    }
}
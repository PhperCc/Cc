<?php
namespace MCU\Plugin;

class Helper
{
    private static $pluginDir = BASE_ROOT.'/Plugins/';

    /**
     * 取得所有插件信息
     */
    public static function getAll()
    {
        $plugins = [];

        foreach(glob(static::$pluginDir.'plugin*.php') as $filePath)
        {
            $pluginClassName = str_replace(static::$pluginDir, '', $filePath);
            $pluginClassName = str_replace('.php', '', $pluginClassName);

            if(class_exists($pluginClassName, false))
            {
                continue;
            }
            //if(!$pluginClassName instanceof IPlugin)
            //{
            //    continue;
            //}
            require_once($filePath);
            

            $plugins[$pluginClassName] = null;
        }
        return $plugins;
    }

    /**
     * 运行列表中的所有插件
     */
    public static function runAll($list)
    {
        global $appRoot;
        $isDis = [];
        $isDisFile = $appRoot.'disabled.php';
        if(file_exists($isDisFile))
        {
            $isDis = require_once($isDisFile);
            if(!is_array($isDis))
            {
                $isDis = [];
            }
        }

        foreach ($list as $name => $value) 
        {
            if(!in_array($name,$isDis))
            {
                $plugin = new $name();
                $plugin->start();
                $plugin->__setHooks(); //机制钩子

                $list[$name] = ['desc' => $plugin->getDesc(),'ver'  => $plugin->getVer()];
            }
            else
            {
                unset($list[$name]);
            }
            
        }
        
        return $list;
    }
}
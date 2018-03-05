<?php
use MCU\ModuleConfig;

/**
 * 服务相关接口
 */
class api_Services extends MCU\ApiHelper\ApiBase
{
    /**
     * 取模块列表
     *
     * return data array 模块列表
     */
    public function get_modules($params)
    {
        $modules = ['Core'=>'内核模块','BaseStation'=>'基站模块'];
        $appRoot = BASE_ROOT.'/Apps';
        $dir_handle = opendir($appRoot);
        while (false !== $module_name = @readdir($dir_handle)) {
   
            if ($module_name == "." || $module_name == "..") {
                continue;
            }
            if (!is_dir($appRoot.'/'.$module_name)) {
                continue;
            }
            $module_desc = ModuleConfig::get($module_name);
            $modules[$module_name] = $module_desc['desc'];
        }
        closedir($dir_handle);

        return R(true, "", $modules);
    }

    /**
     * 取服务列表
     * @module string 模块名
     * 
     * return data array 服务列表
     */
    public function get_services($params)
    {
        if (!array_key_exists("module", $params)) {
            return R(false, "need param 'module'");
        }
        $module = $params["module"];
        $module_config = ModuleConfig::get($module);
        $services = array_keys($module_config["model"]);
        $sensor = array_keys($module_config["sensor"]);
        foreach ($sensor as $val) {
            $services[] = 'sensor_'.$val;
        }

        return R(true, "", $services);
    }
}

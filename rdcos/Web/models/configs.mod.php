<?php
use MCU\Utils\Http\Request;
use MCU\ApiHelper\ApiClient;

class mod_configs extends WebModule
{
    public function __init()
    {
        $this->title = "配置管理";
        $this->powers = array();
    }

    public function view_index()
    {
        error_reporting(7);
        $config_name = Request::paramS('config_name', 'sys');

        $configs = [];
        if($config_name == 'sys')
        {
            $configs = ApiClient::req("SysConfig.get",['token'=>$_COOKIE['enhApiToken']]);
        }
        else
        {
            $module_name = ($config_name == 'Core')?'System':$config_name;
            $configs = ApiClient::req("ServiceConfig.get", ['module' => $module_name, 'service' => null,'token'=>$_COOKIE['enhApiToken']]);
        }

        if(!is_array($configs)) return;
        if($config_name == 'Core')
        {
            foreach ($configs as $k => $v) {
                if($k !== 'model' && $k !== 'sensor') unset($configs[$k]);
            }
        }

        $config_infos = self::get_config_infos($configs);

        foreach($config_infos as $path => &$info)
        {
            if($config_name == 'sys')
            {
                $info['allow_edit'] = false;
            }
            elseif($path == 'desc' || $path == 'protocol')
            {
                $info['allow_edit'] = false;
            }
            else
            {
                $info['allow_edit'] = true;
            }
        }

        //获取select
        $services = [];
        $modules = ApiClient::req('Services.get_modules',['token'=>$_COOKIE['enhApiToken']]);
        foreach($modules as $module_name => $module_desc)
        {
            $services[] = $module_name;
        }

        $this->data["config_name"] = $config_name;
        $this->data["services"] = $services;
        $this->data["config_infos"] = $config_infos;
    }

    private function get_config_infos($configs, $parent_path = "")
    {
        $config_info = [];
        foreach($configs as $key => $val)
        {
            $path = "$parent_path/$key";
            if(is_array($val))
            {
                $val_config_info = self::get_config_infos($val, $path);
                $config_info = array_merge($config_info, $val_config_info);
            }
            else
            {
                $data_type = 'string';
                if(is_bool($val)) $data_type = 'bool';
                else if(is_int($val)) $data_type = 'int';
                else if(is_float($val)) $data_type = 'float';

                $path = trim($path, '/');
                $config_info[$path] =  [
                    'value' => $val,
                    'desc' => $key,
                    'data_type' => $data_type,
                ];
            }
        }
        return $config_info;
    }
}

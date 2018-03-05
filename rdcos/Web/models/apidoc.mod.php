<?php
use MCU\ApiHelper\ApiReflection;

class mod_apidoc extends WebModule
{
    public function __init()
    {
        $this -> title = "API文档";
        $this -> ignore_login[] = "index";
    }

    public function view_index()
    {
        error_reporting(7);
        $api_root = SYS_ROOT . 'Api';
        $handler_files = glob("$api_root/*.php");
        $reflection_list = [];
        foreach($handler_files as $hadler_file)
        {
            if(false === $reflect_info = ApiReflection::reflect($api_root, $hadler_file))
            {
                continue;
            }

            $api_name = $reflect_info['api_name'];
            $reflection = $reflect_info['reflection'];
            $reflection_list[$api_name] = $reflection;
        }
        $Mods = MCU\Cache::get('sys:starters');
        if(isset($Mods[1]))
        {
            $api_root = BASE_ROOT.'/Apps/'.$Mods[1].'/Api';
            $handler_files = glob("$api_root/*.php");
            foreach($handler_files as $hadler_file)
            {
                if(false === $reflect_info = ApiReflection::reflect($api_root, $hadler_file))
                {
                    continue;
                }

                $api_name = $reflect_info['api_name'];
                $reflection = $reflect_info['reflection'];
                $reflection_list[$api_name] = $reflection;
            }
        }
        $this->data["handler_list"] = $reflection_list;
    }
}
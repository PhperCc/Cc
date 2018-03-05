<?php
use MCU\Utils\Http\Request;

class mod_apitest extends WebModule
{
    public function __init()
    {
        $this -> title = "API测试";
        $this -> ignore_login[] = "index";
    }

    public function view_index()
    {
        $api = Request::paramS('api', '');
        $method = Request::paramS('method', '');
        
        $api_root = SYS_ROOT . 'Api';
        $hadler_file = "$api_root/$api.php";
        if(!file_exists($hadler_file))
        {
            $Mods = MCU\Cache::get('sys:starters');
            if(isset($Mods[1]))
            {
                $api_root = BASE_ROOT.'/Apps/'.$Mods[1].'/Api';
                $hadler_file = "$api_root/$api.php";
            }
            else
            {
                header("HTTP/1.1 404 Not Found");
            } 
        }
        
        $action_reflection = [];
        if(false !== $reflect_info = MCU\ApiHelper\ApiReflection::reflect($api_root, $hadler_file))
        {
            $api_reflection = $reflect_info['reflection'];
            if(array_key_exists($method, $api_reflection['methods']))
            {
                $action_reflection = $api_reflection['methods'][$method];
            }
        }

        $this->data['api'] = $api;
        $this->data['method'] = $method;
        $this->data['reflection'] = $action_reflection; 
    }
}
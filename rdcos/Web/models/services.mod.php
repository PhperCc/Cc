<?php
use MCU\Cache;
class mod_services extends WebModule
{
    public function __init()
    {
        $this -> title = "服务管理";
        $this -> powers = array ();
    }

    public function view_index()
    {  
        $this -> data['starters'] = [];

        $starters = Cache::get('sys:starters');
        
        foreach($starters as $starter_name)
        {
            $this -> data["starters"][$starter_name] = Cache::get("sys:services:$starter_name");
        }

        
    }
}

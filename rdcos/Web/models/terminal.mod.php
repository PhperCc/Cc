<?php
class mod_terminal extends WebModule
{
    public function __init()
    {
        $this -> title = "终端";
        $this -> powers = array();
    }

    public function view_index()
    {
        foreach($_SERVER as $k=>$v)
        {
            //echo "$k: $v<br />";
        }
    }
}

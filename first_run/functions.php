<?php

//打印输出
function P($var)
{
    if(is_bool($var))
    {
        var_dump($var);
    }
    elseif(is_null($var))
    {
        var_dump(NULL);
    }
    else
    {
        echo "<pre style = 'position:relativel;z-index:9999;padding:10px;border:1px solid #aaa;fontsize:14px;background:#F5F5F5;line-height:18px;'>".print_r($var,true)."</pre>";
    }
}
//return 方法
function R($result, $info = "", $data = null)
{
    return array("result" => $result, "info" => $info, "data" => $data);
}
//M方法
function M($tbl_name, $aliase = 'default')
{
    return new \classes\DBbase\Operator($tbl_name, $aliase);
}


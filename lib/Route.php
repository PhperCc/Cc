<?php
namespace lib;

class Route
{
    public $controller;
    public $action;
    public function __construct()
    {
        //xxx.com/index.php/index/index
        /*
        *1.隐藏index.php
        *2。获取URL 的参数部分
        *3.返回对应的控制器及方法
        */
        $url_ca = $_SERVER['REQUEST_URI'];
        if(isset($url_ca) && $url_ca != '/')
        {
            //1.分解URL参数部分
            //2.验证并分析URL参数部分
            $url_ca_arr = explode("/",trim($url_ca,"/"));

            if(isset($url_ca_arr[0]))
            {
                $this -> controller = $url_ca_arr[0];
                unset($url_ca_arr[0]);
            }
            if(isset($url_ca_arr[1]))
            {
                $this -> action = $url_ca_arr[1];
                unset($url_ca_arr[1]);
            }
            else
            {
                $this -> action = "index";
            }
            //将URL参数去掉控制器和方法后的多余部分解析成$_GET参数
            $count = count($url_ca_arr);
            if($count < 2) return;
            for($i = 2; $i < $count+2; $i += 2)
            {
                if(isset($url_ca_arr[$i + 1]))
                {
                    $_GET[$url_ca_arr[$i]] = $url_ca_arr[$i + 1];
                }
            }
            p($_GET);
        }
        else
        {
            $this -> controller = "index";
            $this -> action = "index";
        }
        
    }
}
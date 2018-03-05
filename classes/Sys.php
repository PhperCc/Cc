<?php
namespace classes;

class Sys
{
    static public $classArr = array();

    static public function run()
    {
        //URL使用路由类解析获得controller对象名称和action方法
        $Route  = new \lib\Route;
        $ctrl_name = $Route -> controller;
        $action = $Route -> action;
        //拼装文件路径
        $ctrl_path = CTRL_ROOT.$ctrl_name."Ctrl.php";
        //控制器名称 （含命名空间）
        $ctrl_class_name = "\controller\\" .$ctrl_name . "Ctrl";

        if(is_file($ctrl_path))
        {
            //加载控制器文件
            require_once $ctrl_path;
            //实例化控制器对象
            $ctrl =  new $ctrl_class_name;
            //调用方法
            $ctrl -> $action();
        }
        else
        {
            p("controller is not found");
        }

    }

    static public function load($class_name)
    {
      
    
        //自动加载类库
        //new \classes\Scene();
        //$class_name = \classes\Scene();
        //APP_ROOT.'/class/Scene.php';
        if(isset(self::$classArr[$class_name]))
        {
         
            return true;
        }else
        {
            $class = str_replace("\\","/",$class_name);
            
            $file_path = APP_ROOT.$class.".php";

           
            if(is_file($file_path))
            {
                
                require ($file_path);
                self :: $classArr[$class_name] = $class_name;

            }else
            {
                
                return false;
            }
        }
    }
}
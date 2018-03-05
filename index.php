<?php
/*
*入口文件
*
*git za yong a 
*1.定义常量
*2.加载函数库
*3.启动框架
*/

header("Content-Type:text/html;charset=utf-8");

//框架根目录
define("APP_ROOT",str_replace('\\', '/', __DIR__."/"));

//控制器目录
define("CTRL_ROOT", APP_ROOT."controller/");

//文件路径
define('FILE_PATH', str_replace("\\", "/", __FILE__));

//调试模式
define("DEBUG",TRUE);

date_default_timezone_set("PRC");
//加载配置文件
require_once APP_ROOT."/config/config.php";

//加载常用方法
require_once APP_ROOT."/first_run/functions.php";
// P($db_config);

// try {
//     $conn = new PDO("mysql:host=127.0.0.1:3306;dbname=rdc_site",'root','clsdir');
//     echo "连接成功"; 
// }
// catch(PDOException $e)
// {
//     echo $e->getMessage();
// }
// $abc = $conn -> exec("select * from paver_data");
// $conn = null;
// P($abc);

//加载框架基础类库
require_once APP_ROOT."/classes/Sys.php";

//是否开PHP启调试模式
if(DEBUG)
{
    ini_set('display_error','On');
}
else
{
    ini_set('display_error','Off');
}

//实例化失败自动加载方法
spl_autoload_register('\classes\Sys::load');

\classes\DBbase\Operator::set_config(['dsn' => "mysql:host=127.0.0.1:3306;dbname=rdc_site",'user'=>'root','password'=>'clsdir']);
//\classes\Model::set_db_config($db_config);
\classes\Sys::run();
// require 


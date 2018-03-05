<?php
session_start();
use MCU\Utils\Http\Request;
define('APP_ROOT', str_replace("\\", "/", __DIR__).'/');
define('TMP', APP_ROOT.'tmp/');///tmp/smarty/templates

define('SYS_NAME', 'ENHRDC');
define('SYS_VERSION', '2.0.0');

define('COPYRIGHT', "西安依恩驰网络技术有限公司 Xi'an ENH Technology Co.,ltd");
define('POWEREDBY', "ENH TECH");


$path_split = explode('Web/',APP_ROOT);
define('BASE_ROOT', $path_split[0]);
define('SYS_ROOT', $path_split[0].'System/');

require  SYS_ROOT.'Libs/Common.php';
$config = require(APP_ROOT.'/common/config.php');

error_reporting(6);
//ini_set('display_errors', '1');
date_default_timezone_set('PRC'); 
$um = strtolower(Request::param('um', 'sys'));
$ua = strtolower(Request::param('ua', 'index'));

if($_SESSION['uid'] == '' && $ua!= 'login' && $ua!= 'get_token')
{
	$_GET['um'] = $um = 'sys';
	$_GET['ua'] = $ua = 'login';
}
//加载逻辑处理
$model_file = APP_ROOT . "models/$um.mod.php";
if(!file_exists($model_file))
{
    echo "model $um not found";
    return;
}
require $model_file;
$model_class_name = 'mod_'.$um;
$model = new $model_class_name($config);
$model->um = $um;
$model->ua = $ua;
$model->class_name = $model_class_name;
$model->url_host = getenv("HTTP_HOST");

$url_file = getenv("SCRIPT_NAME");
$model->url_path = substr($url_file, 0, strrpos($url_file, "/") + 1);
$model->print_log("um: " . $model -> um . ", ua: " . $model -> ua);


//返回类型判断
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
	$action_method_name = 'ajax_' . $model->ua;
    if (method_exists($model, $action_method_name))
    {
        $model -> $action_method_name();
    }
}else{
	$action_method_name = "view_" . $model -> ua;
    if (method_exists($model, $action_method_name))
    {
        $model -> $action_method_name();
    }

    if(in_array(substr($action_method_name,5,strlen($action_method_name)-5),$model->ignore_view)) exit;

	$req_method = 'view';
	//加载模版
	require  APP_ROOT.'Libs/Smarty/Smarty.class.php';
	$smarty = new \Smarty;

	// 添加自定义调节器
	$smarty -> registerPlugin("modifier", "e", "htmlspecialchars");
	$smarty -> registerPlugin("modifier", "trim", "trim");
	// 添加权限插件
	//$smarty -> registerPlugin("block", "power", "P");

	// 临时目录
	$smarty -> template_dir =TMP.'templates';
	$smarty -> compile_dir =TMP.'templates_c';
	$smarty -> config_dir = TMP.'config';
	$smarty -> cache_dir =TMP.'cache';

	//定义标识符
	$smarty -> left_delimiter = '{ ';
	$smarty -> right_delimiter = ' }';
	$action_view = APP_ROOT . "/views/$um/$ua.html";

	foreach ($model -> data as $k => $v) $smarty -> assign($k, $v);
    $smarty -> assign('host', $_SERVER['HTTP_HOST']);
    $smarty -> assign('system_config', $config['system_config']);
    $smarty -> assign('action_view', $action_view);
    $smarty -> assign('model', $model -> um);
    $smarty -> assign('action', $model -> ua);
    $smarty -> assign('title', $model -> title);
    $smarty -> assign('action_view', $action_view);
    $smarty -> assign('timeused', 0);

	if($ua == 'login')
	{
		$smarty -> display( APP_ROOT . "/views/sys/login.html");
	}
	else
	{
		$smarty -> display( APP_ROOT . "/views/layout/main.html");
	}
}


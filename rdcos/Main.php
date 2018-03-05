<?php
define('ENVIRONMENT', 'development'); //production

if((version_compare(PHP_VERSION, '5.3', '<'))){
	die('php version is not support.');
}

if(ENVIRONMENT == 'development'){
	ini_set('display_errors', 0);
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
}else{
	error_reporting(-1);
	ini_set('display_errors', 1);
}

define('BASE_ROOT', str_replace("\\", "/", __DIR__));
define('APP_ROOT', BASE_ROOT.'/Apps/');
define('SYS_ROOT', BASE_ROOT.'/System/');

require_once(SYS_ROOT.'Libs/Common.php');
$selfVersion = 0;
MCU\Sys::app_run();
<?php
defined('SYS_ROOT') OR exit('No direct script access allowed');
define('CHANNEL_PORT', 2206);

const RDC_CORE_VERSION = '2.0.0';
date_default_timezone_set("Asia/Shanghai");
require_once(SYS_ROOT.'Libs/Function.php');

spl_autoload_register(function ($class) {
    global $appRoot;
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	if(file_exists(SYS_ROOT.'Libs/'.$class.'.php'))
	{
		require_once(SYS_ROOT.'Libs/'.$class.'.php');
	}
	else if(file_exists($appRoot.'Libs/'.$class.'.php'))
	{
		
		require_once($appRoot.'Libs/'.$class.'.php');
	}
});

<?php
use MCU\Sys;

define('STARTER_NAME', 'core');
define('ENVIRONMENT', 'development'); //production
$path_split = explode('/System',str_replace("\\", "/", __DIR__));

define('BASE_ROOT', $path_split[0]);
define('SYS_ROOT', BASE_ROOT.'/System/');

require_once(SYS_ROOT.'Libs/Common.php');
//统计启动次数
$rebootCount = MCU\LocalFile::getObject("record/reboot_count", 0);
MCU\LocalFile::putObject("record/reboot_count", ++$rebootCount);

Sys::init();
//开启通道
new \Channel\Server('127.0.0.1', CHANNEL_PORT); 
//启动内核
Sys::start('System');
<?php 
use MCU\DbOperator;
class  DbClient
{
	public static $config =[
		'dsn' => 'mysql:host=127.0.0.1:3306;dbname=rdc_site',
		'user'=> 'root',
		'password' => 'clsdir'
	];
	public static function M($tbl_name, $aliase = 'default')
	{
		return  new \MCU\DbOperator($tbl_name, $aliase);
	}
}
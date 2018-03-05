<?php
define('APP_ROOT', str_replace("\\", "/", __DIR__).'/');
$path_split = explode('Web/',APP_ROOT);
define('BASE_ROOT', $path_split[0]);
define('SYS_ROOT', $path_split[0].'System/');
error_reporting(6);
require  SYS_ROOT.'Libs/Common.php';

$action = $_GET['action'];
$method = $_GET['method'];
$token  = $_GET['token'];
if($action != '' && $action != '' && $token != '')
{
	$token_val = intval(MCU\Cache::get('enhtoken:'.$token));
    if($token_val == 0)
    {
    	header('HTTP/1.1 403 Forbidden');
    }
    else
    {
    	
    	if(file_exists(SYS_ROOT.'Api/'.$action.'.php'))
		{
			require(SYS_ROOT.'Api/'.$action.'.php');
			$apiClass = 'api_'.$action;
			$apiMod = new $apiClass();
			if(method_exists($apiMod,$method))
			{
				$res = $apiMod->$method($_POST);
				echo json_encode($res);
			}
			else
			{
				header("HTTP/1.1 404 Not Found");
			}
		}
		else
		{
			$Mods = MCU\Cache::get('sys:starters');
			if(isset($Mods[1]))
			{
				if(file_exists($path_split[0].'Apps/'.$Mods[1].'/Api/'.$action.'.php'))
				{
					require($path_split[0].'Apps/'.$Mods[1].'/Api/'.$action.'.php');
					$apiClass = 'api_'.$action;
					$apiMod = new $apiClass();
					if(method_exists($apiMod,$method))
					{
						$res = $apiMod->$method($_POST);
						echo json_encode($res);
					}
					else
					{
						header("HTTP/1.1 404 Not Found");
					}
				}else{
					header("HTTP/1.1 404 Not Found");  
				}
			}
			else
			{
				header("HTTP/1.1 404 Not Found");  
			}
			
		}
    }
	
}
else
{
	header("HTTP/1.1 404 Not Found");
}
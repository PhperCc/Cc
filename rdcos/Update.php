<?php
//更新程序
define('BASE_ROOT', str_replace("\\", "/", __DIR__));
define('SYS_ROOT', BASE_ROOT.'/System/');
define('BACK_PATH','/home/system/data/pocket');
define('TAR_PATH','/home/system/data/pocket/tarTmp');
define('BACK_TMP','/home/system/data/pocket/temp');

spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	if(file_exists(SYS_ROOT.'Libs/'.$class.'.php'))
	{
		require_once(SYS_ROOT.'Libs/'.$class.'.php');
	}
});

$tarName = '';
$serverStatus = [0,0];
$version = [];
$last_time = [time(),time()];
//
//获取版本号
//检查程序
//功能
//1，守护主程序
$worker = new Workerman\Worker();
$worker->name = 'rdcUpdate';
$worker->count = 1;
$worker->onWorkerStart = function()
{
    Channel\Client::connect('127.0.0.1', 2206);

    //检查主程序是否存活
    Workerman\Lib\Timer::add(3, function()
    {
        global $serverStatus,$last_time;
        $now = time();
        //Channel\Client::publish('ResponseUpdate', $now);
        $last = $now-5;
        if($last_time[0] < $last)
        {
        	rebootApp(0);
        	$serverStatus[0] = 0;
        	$last_time[0] = $now+3;
        }
        if($last_time[1] < $last)
        {
        	rebootApp(1);
        	$serverStatus[1] = 0;
        	$last_time[1] = $now+3;
        }
    });
    Channel\Client::on('ResponseSystem', function($body)
    {
    	monitorStatus($body,0);
    });
    Channel\Client::on('ResponseApp', function($body)
    {
    	monitorStatus($body,1);
    });
    //更新通道监听
    Channel\Client::on('SYS.ServerAction.update', function($body)
    {
    	global $version;
    	if($body['type'] == 'core')
	    {
	    	if($body['ver'] > $version['System'])
	    	{
	    		updateCode($body['url'],$body['md5']);
	    	}
	    }
	    else
	    {
	    	if(isset($version[$body['type']]) && $body['ver'] > $version[0])
	    	{
	    		updateCode($body['url'],$body['md5']);
	    	}
	    }

    	
    });
    //updateCode('http://116.62.31.23/package/System-3.tar','3e353bb18416e418cded4e8f5ab5bc89');
    
    //托管执行系统脚本
    Channel\Client::on('CoreSystemCommand', function($body)
    {
    	if($body['commond'] == 'restart')
    	{
    		if('core' == $body['action'])
    		{
    			rebootApp(0);
    			$last_time[0] = time() + 5;
    		}
    		else
    		{
    			rebootApp(2);
    		}
    	}
    	else if($body['commond'] == 'reboot')
    	{
    		if('system' == $body['action'])
    		{
    			consoleExec('reboot');
    			$last_time[0] = time() + 50;
    			$last_time[1] = time() + 50;
    		}
    	}
    	else if($body['commond'] == 'shutdown')
    	{
    		if('system' == $body['action'])
    		{
    			consoleExec('echo o > /proc/sysrq-trigger');
    			$last_time[0] = time() + 50;
    			$last_time[1] = time() + 50;
    		}
    	}
    });
};

Workerman\Worker::runAll();

//检查主进程存活
function monitorStatus($body,$type)
{
	global $version,$serverStatus,$last_time;
	$now = time();
	$last_time[$type] = $now;

	unset($body['time']);
	if(!empty($body))
	{
		foreach ($body as $key => $value) {
			$version[$key] = $value;
		}
	}
	$serverStatus[$type] = 1;
		
}

//2，下载更新代码
function updateCode($url,$md5)
{
	global $tarName;
	
	if(substr($url,0,4) == 'http')
	{
		$file = downTar($url);
	}
	else
	{
		if(file_exists($url))
		{
			$tarName = substr($url ,strrpos($url ,'/') +1);
			$file = $url;
		}
	}

	if($file)
	{
		if(check($file,$md5))
		{
			if($tarName != '')
			{
				$pathex = explode('-', $tarName);
				if($pathex[0] == 'core')
				{
					$incremental = 'System';
					$rdcpath = BASE_ROOT;
				}
				else
				{
					$incremental = '';
					$rdcpath = BASE_ROOT.'/'.$pathex[0];
				}

				if(is_dir($rdcpath)){
					backcode($rdcpath,$incremental);
					if($pathex[0]=='core')
					{
						$last_time[0] = time() + 5;
						rebootApp(0);
					}
					else
					{
						$last_time[1] = time() + 5;
						rebootApp(1);
					}
					return true;
				}
			}
		}
	}

	return false;
}

//3，回滚代码
function rollback($path)
{
	//删除多余的文件
	$delList = array();
	if(file_exists(BACK_TMP.'/del.txt'))
	{
		$fileContent = file_get_contents(BACK_TMP.'/del.txt');
		if($fileContent != '')
		{
			$delList = json_decode($fileContent);
			if(!empty($delList))
			{
				foreach ($delList as $file) {
					delfile(BACK_TMP.$path.$file);
				}
			}
		}
	}
	//覆盖最后备份
	$basep = BACK_TMP;
	$tarList = array();
	read_all_file($basep,$tarList);
	if(!empty($tarList))
	{
		foreach ($tarList as $file) {
			cpfile(BACK_TMP.$file,$path.$file);
			//backcode();
		}
	}
}


function downTar($url)
{
	global $tarName;
	$tarName = substr($url ,strrpos($url ,'/') +1);
	if(!is_dir(BACK_PATH))
	{
		mkdir(BACK_PATH);
	}
	$temp_file_path = BACK_PATH.'/'.$tarName;
	$temp_file = fopen($temp_file_path, 'wb');

	$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-Agent: ENH Endpoint/2.0','Cache-Control: max-age=0']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_FILE, $temp_file);
    $content = curl_exec($curl);
    $errno = curl_errno($curl);
    $response_info = curl_getinfo($curl);
    curl_close($curl);
    fclose($temp_file);

    if($response_info['http_code'] != 200 && $response_info['http_code'] != 206)
    {
        return false;
    }
    if(substr($content, 0, 3) == '!!!')
    {
        return false;
    }
    if($response_info['download_content_length'] == 0)
    {
        return false;
    }

    return $temp_file_path;
}

//验证代码包
function check($file,$md5){
	return md5_file($file) == $md5;
}

//解压更新包、备份现有代码 （读取更新增量列表）
function backcode($path,$incremental = '')
{
	global $basep,$tarName;
	if($incremental == '')
	{
		dozip(BACK_PATH.'/back_'.$tarName,$path);
	}
	else
	{
		if(!is_dir(BACK_TMP))
		{
			mkdir(BACK_TMP);
		}
		delfile(BACK_TMP.'/*');
		unzip(BACK_PATH.'/'.$tarName,TAR_PATH);
		$backList = $delList = $tarList = $addList = array();
		if(file_exists(TAR_PATH.'/del.txt'))
		{
			$fileContent = file_get_contents(TAR_PATH.'/del.txt');
			if($fileContent != '')
			{
				$delList = json_decode($fileContent);
				if(!empty($delList))
				{
					$backList = $delList;
				}
			}
		}
		$basep = TAR_PATH;
		if(!is_dir($basep))
		{
			mkdir($basep);
		}
		read_all_file($basep,$tarList);
		if(!empty($tarList))
		{
			$backList = array_merge($backList,$tarList);
		}

		foreach ($backList as $file) 
		{
			if(!file_exists($path.$file))
			{
				$addList[] = $file;
			}
			$filePath = pathinfo($file);
			if(!is_dir(BACK_TMP.$filePath['dirname']))
			{
				mkdir(BACK_TMP.$filePath['dirname'],0777,true);
			}
			cpfile($path.$file,BACK_TMP.$file);
		}

		if(!empty($addList))
		{
			file_put_contents(BACK_TMP.'/del.txt',json_encode($addList));
		}
		dozip(BACK_PATH.'/back_'.$tarName,BACK_TMP);
		//delfile($tmpBack.'/*');
		//delfile($tmpTar.'/*');
		coverCode($path,$tarList,$delList);
	}
}

//覆盖更新包
function coverCode($path,$list,$del = [])
{
	if(!empty($list))
	{
		if(!empty($del))
		{
			foreach ($del as $delfile) {
				delfile($path.$delfile);
			}
		}
		foreach ($list as $file) {
			cpfile(TAR_PATH.$file,$path.$file);
		}
	}
	return false;
}

//读取目录里包含的文件
function read_all_file($dir,&$res)
{
    global $basep; 
    $handle = opendir($dir);
    if ( $handle )
    {
        while ( ( $file = readdir ( $handle ) ) !== false )
        {
            if ( $file != '.' && $file != '..')
            {
                $cur_path = $dir . DIRECTORY_SEPARATOR . $file;
                if ( is_dir ( $cur_path ) )
                {
                    read_all_file($cur_path,$res);
                }
                else
                {
                    $tmpPath = str_replace($basep,'',$cur_path);
                    $res[] = $tmpPath;
                }
            }
        }
        closedir($handle);
    }

}
//重启服务
function rebootApp($name)
{
	if($name == 0)
	{
		consoleExec('/usr/bin/sudo -u system /usr/local/bin/php /home/system/rdcos/System/Core.php restart -d');
	}
	else if($name == 1)
	{
		consoleExec('/usr/bin/sudo -u system /usr/local/bin/php /home/system/rdcos/Main.php restart -d');
	}
	else if($name == 2)
	{
		consoleExec('php /home/system/rdcos/Update.php restart -d');
	}

}
function unzip($file,$path)
{
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	if('tar' == $ext)
	{
		consoleExec("tar -xzvf $file -C $path");
	}
}

function dozip($file,$path)
{
	consoleExec("tar -czvf $file -C $path .");
}

function cpfile($src,$dsc)
{
	consoleExec("cp -rf $src $dsc");
}

function delfile($src)
{
	consoleExec("rm -rf $src");
}

function consoleExec($str)
{
	$res = null;
	//var_dump($str);
	$re = exec($str,$res);
	//var_dump($re);
	//var_dump($res);
	return $res;
}


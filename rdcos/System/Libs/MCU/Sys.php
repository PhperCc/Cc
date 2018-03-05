<?php
namespace MCU;

use \MCU\Utils\FileSystem;
use \MCU\Utils\OperationSystem;
use \MCU\Plugin\Helper;

/**
 * 系统类
 */
class Sys
{
    /**
     * @var string 标识是否已经启动
     */
    private static $_start_already_run = false;

    /**
     * @var string 用于缓存设备唯一标识
     */
    private static $_uid = null;

    /**
     * @var string 用于缓存设备唯一标识
     */
    private static $_config = null;

    private static $_module_name = null;

    private static $_isRun = false;

    /**
     * 系统进程初始化
     *
     * @return void
     */
    public static function init()
    {
        \MCU\Logger::$onLog = function($content, $category, $log_level)
        {
            if(empty($category)) $category = 'service';
            echo "log: $content\n";
            $time = date("Y-m-d H:i:s");
            LocalFile::putLine("log/$category.log", "$time\t$content", true);
        };

        FileSystem::create_dir("/tmp/workerman/");
        // workerman startup
        if(defined('STARTER_NAME'))
        {
            $pid_file_path = LocalFile::getFilePath("/tmp/workerman/worker_pid_" . STARTER_NAME . ".pid");
        }
        else
        {
            $pid_file_path = LocalFile::getFilePath("/tmp/workerman/worker_pid_app.pid");
        }
        
        FileSystem::create_dir(dirname($pid_file_path));  // 确保目录存在
        \Workerman\Worker ::$pidFile = $pid_file_path;

        FileSystem::create_dir(LocalFile::getDir("log/"));
        \Workerman\Worker ::$logFile = LocalFile::getFilePath("log/worker_log.log");
        \Workerman\Worker ::$stdoutFile = LocalFile::getFilePath("log/worker_stdout.log");

        set_error_handler([__CLASS__,"error_handler"]);
        set_exception_handler([__CLASS__,"exception_handler"]);
        register_shutdown_function([__CLASS__,"shutdown_handler"]);
    }
    /**
     * 载入指定的模块， 启动系统服务
     *
     * @param string $module_name 模块名称
     *
     * @throws Exception
     */
    public static function start($module_name)
    {
        global $appRoot;
        if(!defined(STARTER_NAME))
        {
            define(STARTER_NAME,$module_name);
        }
        if(self::$_start_already_run === true)
        {
            throw new Exception("系统已经启动， 不能重复启动");
        }

        if(null === $starters = Cache::get('sys:starters'))
        {
            $starters = [];
        }
        
        if(STARTER_NAME == 'core')
        {
            $keys = Cache::keys();
            foreach ($keys as $key) {
                $ex = substr($key,0,6);
                if('error_' ==  $ex || 'Sensor' == $ex || 'enhtok'  == $ex)
                {
                    Cache::del($key);
                }
            }
            $starters[0] = 'core'; 
        }
        else
        {
            if($starters[1] != STARTER_NAME)
            {
                Cache::del('sys:services:'.$starters[1]);
            }
            $starters[1] = STARTER_NAME;
        }

        self::$_start_already_run = true;
        self::$_module_name = $module_name;

        if($module_name === 'System')
        {
            $module_dir = SYS_ROOT . "Modules";
            $sensor_dir = SYS_ROOT . "Sensors";
        }
        else
        {
            $module_dir = $appRoot . "Modules";
            $sensor_dir = $appRoot . "Sensors";
        }
        if(!is_dir($module_dir))
        {
            Logger::log("配置的产品类型 $module_name 功能不存在");
            exit("配置的产品类型 $module_name 功能不存在");
        }

        self::$_config = ModuleConfig::get($module_name);
        if(!isset(self::$_config['model']))  exit("模块配置文件不正确");

        if($module_name == 'System')
        {
            Sys::load_servers(self::$_config['model'],$module_dir,'sys_');
            Sys::load_servers(self::$_config['sensor'],$sensor_dir);
        }
        else
        {   
            if(isset(self::$_config['sensor']))
            {
                Sys::load_servers(self::$_config['sensor'],$sensor_dir);
            }
            Sys::load_servers(self::$_config['model'],$module_dir,'svc_');
        }

        if($module_name!= 'System')
        { 
            //加载插件
            $plugin = Helper::getAll();
            $plugin = Helper::runAll($plugin);

            Cache::set('sys:plugin:list',$plugin);
        }
        

        $workers = \Workerman\Worker::getAllWorkers();
        $workers_info = [];
        foreach($workers as $worker)
        {
            $workers_info[] = ["name"     => $worker -> name,
                               "protocol" => $worker -> getSocketName(),
                               "desc"     => isset($worker -> description) ? $worker -> description : "",
            ];
        }

        Cache::set("sys:starters", $starters);
        Cache::set('sys:services:' . STARTER_NAME, $workers_info);

        \Workerman\Worker::runAll();
    }

    private static function load_servers($arr,$path,$pre='')
    {
        $mode = Sys::get_config('rtkMode', 'moving');
        foreach($arr as $svc_name => $svc_config)
        {
            $enabled = array_key_exists("enabled", $svc_config) ? $svc_config["enabled"] : true;
            if($enabled == false || $enabled == 0)
            {
                Sys ::safeEcho("service [$svc_name] disabled in module [$module_name]\r\n");
                continue;
            }

            $protocol = array_key_exists("protocol", $svc_config) ? $svc_config["protocol"] : '';
            if($mode == 'base' && $svc_name == 'RtkAdapter')
            {
                $protocol = '';
            }  
            $worker = new \Workerman\Worker($protocol);
            $worker->name = self::$_module_name."/$svc_name";
            $worker->description = $svc_config["desc"];

            $service_file_path = $path.'/'.$svc_name.'.php';
            include_once($service_file_path);
            $svc_cls_name = $pre.$svc_name;
            if(!class_exists($svc_cls_name, false))
            {
                Sys ::safeEcho("service [$svc_name] parse failed: class $svc_cls_name not found\r\n");
                continue;
            }

            $service = null;
            $worker->onWorkerStart = function($worker) use ($svc_cls_name, $module_name, $svc_name, &$service)
            {
                $service= new $svc_cls_name(self::$_module_name, $svc_name);
                $service->_worker = $worker;
                $service->_start();
                $service->start();

                if($svc_name == 'Main' && self::$_isRun == false)
                {
                   self::$_isRun = true;

                   \Workerman\Lib\Timer::add(3, function()
                    {
                        global $selfVersion;
                        $version = [self::$_module_name => $selfVersion];
                        $plugin = Cache::get('sys:plugin:list');
                        if(!empty($plugin))
                        {
                            foreach ($plugin as $key => $value) {
                               $version[$plugin] =  $value;
                            }
                        }
                        \Channel\Client::publish('ResponseApp', $version);
                    }); 
                }
            };

            $worker->onWorkerReload = function($worker) use (&$service)
            {
                $service->reload();
            };

            $worker->onWorkerStop = function($worker) use (&$service)
            {
                $service->stop();
            };
        }
    }
    
    public static function app_run(){
        global $appRoot;
        $appName = Sys::get_config('product', '');
        if($appName != '')
        {
            $appRoot = BASE_ROOT.'/Apps/'.$appName.'/';
            Sys::init();
            Sys::start($appName);
        }
    }

    public static function exec($str){
        OperationSystem::exec($str);
    }

    /**
     * 重启系统服务
     *
     * @param string $type 重启类型
     *
     * @return void
     */
    public static function restart($type = 'core')
    {
        Cache::save();
        if($type == 'core')
        {
            \Channel\Client::publish('CoreSystemCommand', ['commond'=>'restart','action'=>'core']);
        }
        else if($type == 'update')
        {
            \Channel\Client::publish('CoreSystemCommand', ['commond'=>'restart','action'=>'update']);
        }
        else
        {
            
            $keys = Cache::keys();
            foreach ($keys as $key) {
                if(substr($key,0,10) == 'com485port')
                {
                    Cache::del($key);
                }   
            }
            OperationSystem::exec("php /home/system/rdcos/Main.php restart -d"); 
        }  
    }


     /**
     * 重启系统
     *
     * @param string $reason 重启原因
     *
     * @return void
     */
    public static function reboot($reason)
    {
        $keys = Cache::keys();
        foreach ($keys as $key) {
            if(substr($key,0,10) == 'com485port')
            {
                Cache::del($key);
            }   
        }
        Cache::save();
        \Channel\Client::publish('CoreSystemCommand', ['commond'=>'reboot','action'=>'system']);
    }

    /**
     * 取设备唯一标识
     *
     * @return string 系统唯一标识
     */
    public static function get_uid()
    {
        if(empty(self::$_uid))
        {
            $network_info = get_network_info();
            $mac = strtoupper($network_info["mac"]);
            $mac = str_replace(":", "", $mac);
            $mac = str_replace("-", "", $mac);
            self ::$_uid = strtoupper($mac);
        }

        return self::$_uid;
    }

    public static function get_config($key,$default=false)
    {
        
        if($key == 'guid')
        {
            $res = self::get_uid();
        }
        else if($key == '')
        {
           $res = SysConfig::get($key,$default);
           $res['guid'] = self::get_uid();
        }
        else
        {
            $res = SysConfig::get($key,$default);
        }

        return $res;
    }

    public static function set_config($key,$val)
    {
        return SysConfig::set($key,$val,true);
    }

    /**
     * 取设备版本信息
     *
     * @return array 设备版本信息
     */
    public static function get_version_info()
    {
        $sys_info = php_uname();
        $core = preg_replace("/.*?(\d[^~]*).*/", "$1", $sys_info);
        $release = preg_replace("/.*~([\.\-\w]*).*/", "$1", $sys_info);
        $build_time = preg_replace("/.*([a-z]{3} [a-z]{3} \d{1,2} \d{2}\:\d{2}\:\d{2} [a-z]{3} \d{4}).*/i", "$1", $sys_info);

        $build_time = strtotime($build_time);
        $build_time = date("YmdHis", $build_time);

        $app = Sys::get_config('version');

        return ["core"       => $core,
                "release"    => $release,
                "build_time" => $build_time,
                "app"        => $app,
        ];
    }

    /**
     * 记录需要定时上报给服务端的设备状态
     *
     * @param string $key 状态名
     * @param string $val 状态值
     *
     * @return void
     */
    public static function record_report_status($key, $value)
    {
        $status = LocalFile::getObject("/tmp/sys/report_status", []);
        $status[$key] = $value;
        LocalFile::putObject("/tmp/sys/report_status", $status);
    }

    public static function get_status($key=''){
       $res = LocalFile::getObject("/tmp/sys/report_status", []);
       if($key=='')
       {
            return $res;
       }
       else
       {
            return isset($res[$key])?$res[$key]:false;
       } 
    }

    /**
     * 向服务端上报代码异常
     *
     * @param string $error_content 异常内容
     *
     * @return void
     */
    public static function report_error($error_content)
    {
        $sign = "error_report::" . md5($error_content);
        $last_sign_time = Cache::get($sign);
        if($last_sign_time != null)
        {
            // 30秒内， 相同错误不上报
            if(time() - $last_sign_time <= 30)
            {
                return;
            }
        }
        
        $body = ["action" => "Common.report_exception",
                 "params" => [$error_content]
        ];
        \Channel\Client::publish('SendData', $body);
        Cache::set($sign, time());
    }

    public static function error_handler($level, $message, $file, $line, $context = null)
    {
        if($level == E_NOTICE || $level == E_WARNING)
        {
            return;
        }

        $level_name = self ::get_syserror_name($level);
        $loginfo = "Error: \n";
        $loginfo .= "level: $level ( $level_name ) \n";
        $loginfo .= "message: $message\n";
        $loginfo .= "file: $file\n";
        $loginfo .= "line: $line\n";
        Logger::log($loginfo);
        self ::report_error($loginfo);
    }

    public static function exception_handler($exception)
    {
        $level = isset($exception -> level) ? $exception -> level : E_USER_WARNING;
        if($level == E_NOTICE || $level == E_WARNING)
        {
            return;
        }

        $loginfo = "Exception: \n";
        $loginfo .= "Message: " . $exception -> getMessage() . "\n";
        $loginfo .= "Trace: " . $exception -> getTraceAsString() . "\n";
        Logger::log($loginfo);
        self ::report_error($loginfo);
    }
    //web验证登录
    public static function auth($user)
    {
        $res = false;
        $password = LocalFile::getObject('config/password',[]);

        if(!isset($password['password']))
        {
            $password['password'] = md5(substr(md5('enhroot'),5,15));
        }

        if($user['username'] == 'admin' && md5(substr(md5($user['password']),5,15)) == $password['password'])
        {
            $res = md5('enh'.time());
            Cache::set('enhtoken:'.$res,time());
            
        }

        return $res;
    }
    //清除token
    public static function del_token($token)
    {
        Cache::del('enhtoken:'.$token);
    }
    //api获取token
    public static function auth_token($user)
    {
        $res = ['status'=>0,'msg'=>'auth error'];
        $pre = substr($user['did'],0,3);
        
        if($pre == 'pad')
        {   
            if(md5(substr(md5($user['did']),5,13).'enH') == $user['key'])
            {
                $token = md5(self::get_uid().time());
                Cache::set('enhtoken:'.$token,time());
                $res['token'] = $token;
                $res['status'] = 1;
            }
        }
        else if($pre == 'web')
        {
            if(self::auth(['username'=>$user['did'],'password'=>$user['key']]))
            {
                $token = md5(self::get_uid().time());
                Cache::set('enhtoken:'.$token,time());
                $res['token'] = $token;
                $res['status'] = 1;
            }
        }
        return json_encode($res);
    }
    //修改密码
    public static function set_password($password)
    {
        LocalFile::putObject('config/password', ['password'=>md5(substr(md5($password),5,15))]);
        return true;
    }

    public static function shutdown_handler()
    {
        $error = error_get_last();
        if(!$error || !is_array($error))
        {
            return;
        }

        $loginfo = "Shutdown: \n";
        foreach($error as $k => $v)
        {
            if($k == 'type')
            {
                $v = "$v ( " . self ::get_syserror_name($v) . " )";
            }
            $loginfo .= "$k: $v\n";
        }
        Logger::log($loginfo);
        self ::report_error($loginfo);
    }

    private static function get_syserror_name($number)
    {
        $errors = ["E_ERROR",
                   "E_WARNING",
                   "E_PARSE",
                   "E_NOTICE",
                   "E_CORE_ERROR",
                   "E_CORE_WARNING",
                   "E_COMPILE_ERROR",
                   "E_COMPILE_WARNING",
                   "E_USER_ERROR",
                   "E_USER_WARNING",
                   "E_USER_NOTICE",
                   "E_STRICT",
                   "E_RECOVERABLE_ERROR",
                   "E_DEPRECATED",
                   "E_USER_DEPRECATED",
        ];

        $index = log($number, 2);

        return $errors[$index];
    }

    /**
     * 安全输出, 判断当前终端如果是可输出终端， 再输出内容， 否则不输出
     * 若当前终端不是可输出终端， 某些情况下会写入 Worker 日志中， 也有一些情况下会导致进程崩溃
     *
     * @param string $msg 输出内容
     *
     * @return void
     */
    public static function safeEcho($msg)
    {
        if(!function_exists('posix_isatty') || posix_isatty(STDOUT))
        {
            echo $msg;
        }
    }
}
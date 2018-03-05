<?php
/**
 * 系统资源监控服务
 */

use MCU\Kernel;
use MCU\Sys;
use MCU\SysConfig;
use MCU\LocalFile;
use MCU\Port\GpioPort;

class sys_Monitors extends Kernel
{
    private $state_count = 0;
    private $last_state = false;
    private $update_last_time = 0;
    private $led_hander = [];
    private $led_timer = []; 
    private $led_status = [];

	public function start()
	{       
        $this->update_last_time = time();
        $host = $this->getConfig('host','');
        if($host!=''){
           $this -> _ping($host); 
        }
        $led_port = $this->getConfig('led_port','');
        if($led_port!=''){
           $this -> _led($led_port); 
        }

        //检查更新程序是否存活
        \MCU\Timer::add(3, function()
        {
            Channel\Client::publish('ResponseSystem', ['time'=>time(),'System'=>SysConfig::get('version', '')]);
            if($this->update_last_time<(time()-5))
            {
                //Sys::exec('php /home/system/rdcos/update.php restart -d');
            }
        });
        \Channel\Client::on('ResponseUpdate', function($body)
        {
            $this->update_last_time = $body;
        });
        
        \MCU\Timer::add(60, function()
        {
            $this -> _monit_disk_spaces();
        });

        \MCU\Timer::add(20, function()
        {
            $this -> _monit_log_size();
        });

        // 定时将磁盘缓存强制写入磁盘
        \MCU\Timer::add(30, function()
        {
            MCU\Utils\FileSystem::refresh_disk_cache();
        });
        //定时上报服务器状态
        \MCU\Timer::add(600, function()
        {

            $status = [];
            $status["hver"] = php_uname();
            $status["sver"] = SysConfig::get("version", "");
            $status["name"] = SysConfig::get("guid", "");
            
            $status["local_network"] = get_network_info();
            $status["mobile_network"] = get_network_info('usb0');
            $status = array_merge($status, LocalFile::getObject("/tmp/sys/report_status", []));

            $this->command('Common.report_status',$status);
        });
        //系统指令
        \Channel\Client::on('SYS.ServerAction.set_svc_config', function($params)
        {
            $module_name = $params["mod"];
            $svc_name = $params["svc"];
            $key = $params["key"];
            $val = $params["val"];

            if(empty($module_name) || empty($svc_name) || empty($key)) return;

            \MCU\ModuleConfig::set($module_name, $svc_name, $key, $val, true, true);
            $this -> log("$module_name/$svc_name config $key seted to $val");
        });

        \Channel\Client::on('SYS.ServerAction.reboot', function($body)
        {
            $this->log("system reboot by server command");
            Sys::reboot('by server command"');
        });
	}

    /**
     * 
     * 监测磁盘空间， 记录到报告中， 准备上报给服务器
     *
     * @return void
     */
    private function _monit_disk_spaces()
    {
        Sys::record_report_status("disk_space", ["total" => @disk_total_space("/"), "free" => @disk_free_space("/")]);
    }

    /**
     * 监测日志文件尺寸， 文件尺寸过大则删除
     *
     * @return void
     */
    private function _monit_log_size()
    {
        $max_size = $this -> getConfig("log_max_size", 5);
        $min_size = $this -> getConfig("log_min_size", 1);
        $max_size = $max_size * 1024 * 1024;
        $min_size = $min_size * 1024 * 1024;
        $paths = $this -> getConfig("log_paths", []);
        foreach($paths as $path)
        {
            if(false !== $dir_handle = @opendir($path))
            {
                while (false !== $file = @readdir($dir_handle))
                {
                    if($file == "." || $file == "..") continue;
                    $full_file_path = "$path/$file";
                    $file_size = @filesize($full_file_path);
                    if($file_size > $max_size)
                    {
                        $fd = fopen($full_file_path, 'r');
                        fseek($fd, -1 * $min_size);
                        $left_content = fread($fd, $min_size);
                        fclose($fd);

                        @file_put_contents($full_file_path, $left_content, LOCK_EX);

                        /*
                        $min_content = substr(@file_get_contents($full_file_path), -1 * $min_size);
                        @file_put_contents($full_file_path, $min_content, LOCK_EX);
                        */
                        $file_size_text = size_text($file_size);
                        $min_size_text = size_text($min_size);
                        $this -> log("$full_file_path cut from $file_size_text to $min_size_text");
                    }
                }
                closedir($dir_handle);
            }
        }
    }

    /**
     * 网络联通监控
     *
     * @return void
     */
    private function _ping($host)
    {
        $timeout = 1;

        /* ICMP ping packet with a pre-calculated checksum */
        if(false === $socket  = socket_create(AF_INET, SOCK_RAW, 1))
        {
            $error_no = socket_last_error();
            $error_info = socket_strerror($error_no);
            $this -> log("create ping socket failed: ($error_no) $error_info");
            return;
        }

        socket_set_block($socket);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        @socket_connect($socket, $host, 0);
        $this -> log("Start Ping host: $host, timeout: $timeout");

        \MCU\Timer::add(1, function() use ($socket, $host)
        {
            $time_start = microtime(true);
            $packet = new \Network\IcmpPacket(64);
            $request = $packet -> get_request();
            if(false === socket_send($socket, $request, strlen($request), 0))
            {
                $error_no = socket_last_error();
                $error_info = socket_strerror($error_no);
                $this -> log("ping packet to $host failed : ($error_no) $error_info\n");
                return;
            }

            if (false !== $reply = @socket_read($socket, 255))
            {
                $response = $packet -> reply_parse($reply);
                $time_used = round(microtime(true) - $time_start, 6) * 1000;
                // $reply_size = strlen($reply) - 28;

                // Sys::safeEcho("ping $host reply from {$response['ip_source']} time=$time_used ms, ttl={$response['ip_ttl']}\n");
                if($response['ip_source'] == $host)
                {
                    $this -> _log(true);
                    return;
                }
                $this -> _log(false);
            }
        });
    }

    /**
     * led控制服务
     *
     * @return void
     */
    private function _led($port_str)
    {
        $ports = explode(',', $port_str);
        $i = 0;
        foreach ($ports as $post_num) {
            $this->led_hander[$i] = new GpioPort(['port_num'=>$post_num,'port_type'=>'out']);
            $this->led_status[$i] = 0;
            $this->led_hander[$i]->send('1');
            $i++;
        }
        Channel\Client::on('LedControl', function($body)
        {
            if(count($body)==2)
            {
                $body['index'] = intval($body['index']);
                if(isset($this->led_hander[$body['index']]))
                {
                    if(isset($this->led_timer[$body['index']]))
                    {
                        \MCU\Timer::del($this->led_timer[$body['index']]);
                        unset($this->led_timer[$body['index']]);
                    }
                    if($body['type'] == '1' || $body['type'] == '0')
                    {
                        
                        $this->led_status[$body['index']] = ($body['type'] == 0)?1:0;
                        $this->led_hander[$body['index']]->send($this->led_status[$body['index']]); 
                    }
                    else
                    {
                        $num = $body['type']/2;
                        $this->led_timer[$body['index']] = \MCU\Timer::add($num, function() use($body)
                        {
                            if($this->led_status[$body['index']] == 0)
                            {
                                $this->led_status[$body['index']] = 1;     
                            }
                            else
                            {
                                $this->led_status[$body['index']] = 0;
                            }
                            $this->led_hander[$body['index']]->send($this->led_status[$body['index']]);
                        });
                    }
                }
            }
        });
    }

    private function _log($new_state)
    {
        $this -> state_count ++;
        if($this -> last_state == $new_state && $this -> state_count < 20) return;

        $host = $this -> getConfig('host');
        $last_state_text = $this -> last_state ? 'succeed' : 'failed';
        $this -> log("ping $host $last_state_text total {$this -> state_count} times");

        if($this -> last_state != $new_state)
        {
            $this -> last_state = $new_state;
        }
        $this -> state_count = 0;
    }

    
}
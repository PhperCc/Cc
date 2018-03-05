<?php
/*
前景            背景              颜色
---------------------------------------
30                40              黑色
31                41              紅色
32                42              綠色
33                43              黃色
34                44              藍色
35                45              紫紅色
36                46              青藍色
37                47              白色
*/

use MCU\Sys;
use MCU\Kernel;

class sys_Terminal extends Kernel
{
    private $endpoint_name = '';
    private $user = '';


    private $process = null;
    private $process_stdin = null;
    private $process_stdout = null;
    private $process_stderr = null;
    private $input_buffer = "";
    private $cmd_line_history = [];
    private $cmd_line_jump_history = [];
    private $input_position = 0;
    private $cwd = "/";
    private $executing = false;

	public function start()
	{
        $this->endpoint_name = Sys::get_uid();
        $this->user = @get_current_user();

        //订阅远程操作控制台主题
        \Channel\Client::on('SYS.ServerAction.remote_terminal_onConnect', function($body)
        {   
            $this->process = null;
            $this->process_stdin = null;
            $this->process_stdout = null;
            $this->process_stderr = null;
            $this->input_buffer = "";
            $this->cmd_line_history = [];
            $this->cmd_line_jump_history = [];
            $this->input_position = 0;
            $this->cwd = "/";
            $this->remote_send("\033[32mWelcom to ENHRDC Endpoint Terminal\033[0m\r\n");
            $this->cwd_tip($this);
        });

        \Channel\Client::on('SYS.ServerAction.remote_terminal_onMessage', function($body)
        {   
            $this->onMessage($body,$this);
        });

        $this->_worker->onConnect = function ($conn)
        {
            $conn -> process = null;
            $conn -> process_stdin = null;
            $conn -> process_stdout = null;
            $conn -> process_stderr = null;
            $conn -> input_buffer = "";
            $conn -> cmd_line_history = [];
            $conn -> cmd_line_jump_history = [];
            $conn -> input_position = 0;
            $conn -> cwd = "/";
            $conn -> send("\033[32mWelcom to ENHRDC Endpoint Terminal\033[0m\r\n");
            $this -> cwd_tip($conn);
        };

        $this->_worker->onMessage = function ($conn, $input)
        {
            $this->onMessage($input,$conn);
        };

        $this->_worker->onClose = function($conn)
        {
            if($conn->process_stdout != null)
            {
                $conn->process_stdout -> close();
                $conn->process_stdout = null;
            }
            $conn -> input_buffer = "";
        };

        $this->_worker->onWorkerStop = function($worker)
        {
            foreach($worker->connections as $connection)
            {
                $connection->close();
            }
        };
	}

    private function execute($conn, $cmd_line)
    {
        $cmd_line = trim($cmd_line);
		if(empty($cmd_line)) return $this -> cwd_tip($conn);
		$cmd_params = explode(" ", preg_replace("/\s+/", " ", $cmd_line));
        $cmd = array_shift($cmd_params);

        if($cmd == "cd") return $this -> change_cwd($conn, $cmd_params);

        $conn -> executing = true;

        unset($_SERVER['argv']);
        unset($_SERVER['PHP_SELF']);
        unset($_SERVER['SCRIPT_NAME']);
        unset($_SERVER['SCRIPT_FILENAME']);
        unset($_SERVER['PATH_TRANSLATED']);
        unset($_SERVER['DOCUMENT_ROOT']);
        unset($_SERVER['REQUEST_TIME_FLOAT']);
        unset($_SERVER['REQUEST_TIME']);
        unset($_SERVER['argc']);

        // 应用启动较快时， 环境变量可能尚未准备完全， 导致有些功能用不了， 比如 nano
        // 所以这里自行定义了 TERM 变量
        $env = array_merge(['COLUMNS'=>130, 'LINES'=> 26, 'TERM' => 'xterm'], $_SERVER);

        /*
        $env: [
          'COLUMNS' => 130,
          'LINES' => 26,
          'XDG_SESSION_ID' => '1842',
          'HOSTNAME' => 'iZbp1csavu80ksoxrwks4rZ',
          'TERM' => 'xterm',
          'SHELL' => '/bin/bash',
          'HISTSIZE' => '100',
          'SSH_CLIENT' => '118.114.140.117 14899 22',
          'SSH_TTY' => '/dev/pts/0',
          'USER' => 'root',
          'LS_COLORS' => 'rs=0:di=01;34:ln=01;36:mh=00:pi=40;33:so=01;35:do=01;35:bd=40;33;01:cd=40;33;01:or=40;31;01:mi=01;05;37;41:su=37
            ;41:sg=30;43:ca=30;41:tw=30;42:ow=34;42:st=37;44:ex=01;32:*.tar=01;31:*.tgz=01;31:*.arc=01;31:*.arj=01;31:*.taz=01;31:*.lha=01;31:
            *.lz4=01;31:*.lzh=01;31:*.lzma=01;31:*.tlz=01;31:*.txz=01;31:*.tzo=01;31:*.t7z=01;31:*.zip=01;31:*.z=01;31:*.Z=01;31:*.dz=01;31:*.
            gz=01;31:*.lrz=01;31:*.lz=01;31:*.lzo=01;31:*.xz=01;31:*.bz2=01;31:*.bz=01;31:*.tbz=01;31:*.tbz2=01;31:*.tz=01;31:*.deb=01;31:*.rp
            m=01;31:*.jar=01;31:*.war=01;31:*.ear=01;31:*.sar=01;31:*.rar=01;31:*.alz=01;31:*.ace=01;31:*.zoo=01;31:*.cpio=01;31:*.7z=01;31:*.
            rz=01;31:*.cab=01;31:*.jpg=01;35:*.jpeg=01;35:*.gif=01;35:*.bmp=01;35:*.pbm=01;35:*.pgm=01;35:*.ppm=01;35:*.tga=01;35:*.xbm=01;35:
            *.xpm=01;35:*.tif=01;35:*.tiff=01;35:*.png=01;35:*.svg=01;35:*.svgz=01;35:*.mng=01;35:*.pcx=01;35:*.mov=01;35:*.mpg=01;35:*.mpeg=0
            1;35:*.m2v=01;35:*.mkv=01;35:*.webm=01;35:*.ogm=01;35:*.mp4=01;35:*.m4v=01;35:*.mp4v=01;35:*.vob=01;35:*.qt=01;35:*.nuv=01;35:*.wm
            v=01;35:*.asf=01;35:*.rm=01;35:*.rmvb=01;35:*.flc=01;35:*.avi=01;35:*.fli=01;35:*.flv=01;35:*.gl=01;35:*.dl=01;35:*.xcf=01;35:*.xw
            d=01;35:*.yuv=01;35:*.cgm=01;35:*.emf=01;35:*.axv=01;35:*.anx=01;35:*.ogv=01;35:*.ogx=01;35:*.aac=01;36:*.au=01;36:*.flac=01;36:*.
            mid=01;36:*.midi=01;36:*.mka=01;36:*.mp3=01;36:*.mpc=01;36:*.ogg=01;36:*.ra=01;36:*.wav=01;36:*.axa=01;36:*.oga=01;36:*.spx=01;36:
            *.xspf=01;36:',
          'MAIL' => '/var/spool/mail/root',
          'PATH' => '/usr/local/nginx/sbin:/usr/local/php/bin:/usr/local/mysql/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/root/bin',
          'PWD' => '/root',
          'LANG' => 'en_US.UTF-8',
          'HISTCONTROL' => 'ignoredups',
          'SHLVL' => '1',
          'HOME' => '/root',
          'LOGNAME' => 'root',
          'SSH_CONNECTION' => '118.114.140.117 14899 116.62.30.141 22',
          'LESSOPEN' => '||/usr/bin/lesspipe.sh %s',
          'PROMPT_COMMAND' => '{ msg=$(history 1 | { read x y; echo $y; });user=$(whoami); echo $(date "+%Y-%m-%d %H:%M:%S"):$user:`pwd`/:
            $msg ---- $(who am i); } >> /tmp/`hostname`.`whoami`.history-timestamp',
          'XDG_RUNTIME_DIR' => '/run/user/0',
          '_' => '/usr/local/php/bin/php',
        ]
        */

        $descriptorspec  = [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]];

        $conn -> process = proc_open($cmd_line, $descriptorspec, $pipes, $conn -> cwd, $env);
        stream_set_blocking($pipes[0], 0);
        // $conn -> send(var_export($pipes, true));
        $conn -> process_stdin = $pipes[0];
        $conn -> process_stdout = new \WorkerMan\Connection\TcpConnection($pipes[1]);
        $conn -> process_stdout -> onMessage = function($process_connection, $data) use($conn, $cmd, $cmd_params)
        {
            if($cmd == "ls" && count($cmd_params) == 0)
            {
                $entries = explode("\n", $data);
                $entry_col_width = 0;
                foreach($entries as $entry)
                {
                    $entry_col_width = max(strlen($entry), $entry_col_width);
                }
                $entry_col_width += 3;
                $entry_col_count = intval(130 / $entry_col_width);
                // $conn -> send(" entry_col_width: $entry_col_width, entry_col_count: $entry_col_count");
                $entry_col_pos = 0;
                foreach($entries as &$entry)
                {
                    $full_path = $conn -> cwd . "/$entry";
                    $entry = str_pad($entry, $entry_col_width);
                    if(is_dir($full_path)) $entry = "\x1b[34m$entry\x1b[0m";    // 目录 蓝色
                    if(is_link($full_path)) $entry = "\x1b[35m$entry\x1b[0m";   // 软连接 紫红色
                    if(is_executable($full_path)) $entry = "\x1b[32m$entry\x1b[0m";  // 可执行文件 绿色
                    $entry_col_pos ++;
                    if($entry_col_pos % $entry_col_count == 0)
                    {
                        $entry .= "\r\n";
                    }
                }
                $data = implode("", $entries);
            }
            
            $data = str_replace("\n", "\r\n", $data);
            if($conn == $this)
            {
                $this->remote_send($data);
            }
            else
            {
                $conn->send($data); 
            }
        };

        $conn -> process_stdout -> onClose = function($process_connection) use($conn)
        {
            proc_terminate($conn -> process);
            // proc_close($conn -> process);
            // $conn -> send("process set to null");
            $conn -> process = null;

            fclose($conn -> process_stdin);
            $conn -> process_stdin = null;

            if($conn -> process_stderr != null)
            {
                $conn -> process_stderr -> close();
            }
            $this -> cwd_tip($conn);
        };

        $conn -> process_stderr = new \WorkerMan\Connection\TcpConnection($pipes[2]);
        $conn -> process_stderr -> onMessage = function($process_connection, $data) use($conn)
        {
            $data = str_replace("\n", "\r\n", $data);
            if($conn == $this)
            {
                $this->remote_send($data);
            }
            else
            {
                $conn->send($data); 
            }
            
        };

        $conn -> process_stderr -> onClose = function($process_connection) use($conn)
        {
            fclose($conn -> process_stderr);
            $conn -> process_stderr = null;
        };
    }

    private function onMessage($input,$conn=null)
    {
        $len = strlen($input);
        if($len === 0) return;

        $chr0 = substr($input, 0, 1);
        $chr1 = substr($input, 1, 1);
        $chr2 = substr($input, 2, 1);
        $chr3 = substr($input, 3, 1);
        $code0 = ($chr0 === false) ? false : ord($chr0);
        $code1 = ($chr1 === false) ? false : ord($chr1);
        $code2 = ($chr2 === false) ? false : ord($chr2);
        $code3 = ($chr3 === false) ? false : ord($chr3);
        
        if($conn -> executing)
        {
            if($len === 1 && $code0 === 3)  // ^C
            {
                $process_status = proc_get_status($conn -> process);
                posix_kill($process_status["pid"], SIGINT);     // 正常终止

                // 2秒后还未终止， 强行终止
                \Workerman\Lib\Timer::add(2, function() use($conn){
                    if($conn -> process_stdout != null)
                    {
                        $conn -> process_stdout -> close();
                        $conn -> process_stdout = null;
                    }
                }, null, false);
            }
            else
            {
                fwrite($conn -> process_stdin, $input);
            }
            return;
        }

        if($len == 3 && $code0 == 27 && $chr1 == '[' && $chr2 == 'A')   // ↑
        {
            $tip_cmd_line = array_pop($conn -> cmd_line_history);
            if($tip_cmd_line === null)
            {
                $tip_cmd_line = "";
            }
            else
            {
                $conn -> cmd_line_jump_history[] = $tip_cmd_line;
            }
            $backspace = str_repeat(chr(8), strlen($conn -> input_buffer));
            $conn -> input_buffer = $tip_cmd_line;
            $conn -> input_position = strlen($conn -> input_buffer);
            if ($conn == $this) 
            {
                return $this->remote_send("$backspace$tip_cmd_line");
            }
            else
            {
                return $conn->send("$backspace$tip_cmd_line");
            }  
        }
        else if($len == 3 && $code0 == 27 && $chr1 == '[' && $chr2 == 'B')   // ↓
        {
            $tip_cmd_line = array_pop($conn -> cmd_line_jump_history);
            if($tip_cmd_line === null)
            {
                $tip_cmd_line = "";
            }
            else
            {
                $conn -> cmd_line_history[] = $tip_cmd_line;
            }
            $backspace = str_repeat(chr(8), strlen($conn -> input_buffer));
            $conn -> input_buffer = $tip_cmd_line;
            $conn -> input_position = strlen($conn -> input_buffer);
            if ($conn == $this) 
            {
                return $conn->remote_send("$backspace$tip_cmd_line");
            }
            else
            {
                return $conn->send("$backspace$tip_cmd_line");
            }
        }
        else if($len == 3 && $code0 == 27 && $chr1 == '[' && $chr2 == 'D')   // ←
        {
            if($conn -> input_position <= 0) return;
            $conn -> input_position --;
            if ($conn == $this) 
            {
                return $conn->remote_send($input);
            }
            else
            {
                return $conn->send($input);
            }
        }
        else if($len == 3 && $code0 == 27 && $chr1 == '[' && $chr2 == 'C')   // →
        {
            if($conn -> input_position >= strlen($conn -> input_buffer)) return;
            $conn -> input_position ++;
            if ($conn == $this) 
            {
                return $conn->remote_send($input);
            }
            else
            {
                return $conn->send($input);
            }
        }
        else if($len == 1 && $chr0 == "\t")   // TAB
        {
            if($conn -> input_position < strlen($conn -> input_buffer)) return;
            $input_nodes = explode(" ", $conn -> input_buffer);
            $cmd = "";
            if(count($input_nodes) > 1) $cmd = $input_nodes[0];
            $path_all = $input_nodes[count($input_nodes) - 1];
            // $conn -> send("<path_all:$path_all>");

            $last_split_pos = strrpos($path_all, "/");
            $last_split_pos = ($last_split_pos === false) ? -1 : $last_split_pos;
            $path_tocomplete = substr($path_all, $last_split_pos + 1);
            $path_basedir = substr($path_all, 0, $last_split_pos + 1);

            if($path_tocomplete == null) $path_tocomplete = "";
            if($path_basedir == null) $path_basedir = "";

            // $conn -> send("<path_tocomplete:$path_tocomplete>");
            // $conn -> send("<path_basedir:$path_basedir>");

            $path_basedir = substr($path_basedir, 0, 1) == "/" ? $path_basedir : $conn -> cwd . "/" . $path_basedir;
            // $conn -> send("<path_basedir:$path_basedir");
            
            $path_basedir_handle = @opendir($path_basedir);
            if($path_basedir_handle === false) return;
            
            $path_entries = [];
            // $i = 0;
            while(false !== ($path_entry = readdir($path_basedir_handle)))
            {
                // if($i++ > 50) break;
                // $conn -> send("\r\n" . var_export($path_entry, true));
                if($path_entry == "." || $path_entry == "..") continue;
                if($cmd == "cd" && is_file("$path_basedir$path_entry")) continue;
                if(empty($path_tocomplete) || strpos($path_entry, $path_tocomplete) === 0) $path_entries[] = $path_entry;
            }
            closedir($path_basedir_handle);
            if(count($path_entries) == 0)
            {
                // $conn -> send("mei zhao dao");
                return;
            }
            else
            {
                $same_part = array_reduce($path_entries, function($rtn, $val)
                {
                    for($i=0; ($i < strlen($rtn) && $i < strlen($val)); $i++)
                    {
                        if($rtn{$i} != $val{$i}) { $rtn = substr($rtn, 0, $i); break; }
                    }
                    return $rtn;
                }, $path_entries[0]);

                $input = substr($same_part, strlen($path_tocomplete));

                $completed_path = $path_basedir . $path_tocomplete . $input;
                if(is_dir($completed_path) && substr($input,  -1) != "/") $input .= "/";
                else if(is_file($completed_path)) $input .= " ";

                // $conn -> send("zhao dao: $input");
            }
        }

        for($i = 0; $i < strlen($input); $i++)
        {
            $chr = substr($input, $i, 1);
            if($chr == chr(13)) // 回车
            {
                if ($conn == $this) 
                {
                    $this->remote_send("\r\n");
                }
                else
                {
                    $conn->send("\r\n");
                }
                $cmd_line = $conn -> input_buffer;
                
                while(false !== $pos = @array_search($cmd_line, $conn -> cmd_line_history))
                {
                    array_splice($conn -> cmd_line_history, $pos, 1);
                }
                while(false !== $pos = array_search($cmd_line, $conn -> cmd_line_jump_history))
                {
                    array_splice($conn -> cmd_line_jump_history, $pos, 1);
                }
                $conn -> cmd_line_history[] = $cmd_line;
                $conn -> input_buffer = "";
                $conn -> input_position = 0;
                $this -> execute($conn, $cmd_line);
            }
            elseif($chr == chr(8))  // 退格
            {
                $before_position_buffer = substr($conn -> input_buffer, 0, $conn -> input_position - 1);
                $after_position_buffer = substr($conn -> input_buffer, $conn -> input_position);
                if($conn -> input_position <= 0) return;
                if ($conn == $this) 
                {
                    $this->remote_send($chr);
                }
                else
                {
                    $conn->send($chr);
                }
                //$before_position_buffer = substr($conn -> input_buffer, 0, $conn -> input_position - 1);
                //$after_position_buffer = substr($conn -> input_buffer, $conn -> input_position);
                $conn -> input_buffer = "$before_position_buffer$after_position_buffer";
                // $conn -> input_buffer = substr($conn -> input_buffer, 0, strlen($conn -> input_buffer) - 1);
                $conn -> input_position --;
            }
            else
            {
                $before_position_buffer = substr($conn -> input_buffer, 0, $conn -> input_position);
                $after_position_buffer = substr($conn -> input_buffer, $conn -> input_position);
                if($after_position_buffer === null) $after_position_buffer = "";
                $conn -> input_buffer = "$before_position_buffer$chr$after_position_buffer";    // 在光标位置插入
                if ($conn == $this) 
                {
                    $this->remote_send("$chr$after_position_buffer");    // 输出当前输入内容， 同时输出后续内容覆盖后部
                    $this->remote_send(str_repeat(chr(27) . "[D", strlen($after_position_buffer)));  // 位置挪回
                }
                else
                {
                    $conn->send("$chr$after_position_buffer");    // 输出当前输入内容， 同时输出后续内容覆盖后部
                    $conn->send(str_repeat(chr(27) . "[D", strlen($after_position_buffer)));  // 位置挪回
                } 
                $conn->input_position ++;
            }
        }
    }

    private function change_cwd($conn, $cwd_params)
    {
		if(count($cwd_params) == 0) return $this -> cwd_tip($conn);;
		$new_cwd = $cwd_params[0];

		if(substr($new_cwd, 0, 1) != "/") $new_cwd = $conn -> cwd . "/$new_cwd";
        if(!@chdir($new_cwd))
        {
            return $this -> cwd_tip($conn, "could not change to $new_cwd");
        }
		$conn -> cwd = getcwd();
		return $this -> cwd_tip($conn);
    }

    private function cwd_tip($conn, $info = "")
    {
        $output = "$info\r\n";
        $output .= "[\033[32m" . $this -> endpoint_name . "\033[0m \033[33m" . $conn -> cwd . "\033[0m]# ";

        if($conn == $this)
        {
            $conn -> remote_send($output);
        }
        else
        {
            $conn -> send($output); 
        }
        
        $conn -> executing = false;
    }

    protected function remote_send($body)
    {
        \Channel\Client::publish('remote_terminal_send', $body);
    }
}
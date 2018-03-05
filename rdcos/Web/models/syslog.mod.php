<?php
use MCU\Utils\Http\Request;

class mod_syslog extends WebModule
{
    public function __init()
    {
        $this -> title = "系统日志";
        $this -> powers = array ();
    }

    public function view_index()
    {
        $file_name  = Request::param('file_name');
        $file_pos   = Request::paramI('file_pos', 0);
        $action     = Request::paramS('action');
        if($action == 'delete')
        {
            if(file_exists($file_name)) unlink($file_name);
            $file_name = '';
        }

        // 取日志文件列表
        $log_files = [];
        $log_path_root = MCU\LocalFile::getFilePath("log");
		if(false !== $dir_handle = opendir($log_path_root))
		{
			while (false !== $file = @readdir($dir_handle))
			{
				if($file == "." || $file == "..") continue;
				$full_file_path = "$log_path_root/$file";
                $file_size = @filesize($full_file_path);
				$size = size_text($file_size);
				$log_files[] = array('name' => $full_file_path, 'size' => $size);
			}
			closedir($dir_handle);
		}

        $this -> data['log_files'] = $log_files;
        $lines = [];
        $page_size = 200;

        if(empty($file_name) && count($log_files) > 0)
        {
            $file_name = $log_files[0]['name'];
        }

		if(empty($file_name))
		{
			$lines = ['没有发现日志文件'];
		}
		else if(!file_exists($file_name))
        {
            $lines = ['文件不存在'];
        }
        else if(false === $fd = @fopen($file_name, 'r'))
        {
            $lines = ['文件打开失败!'];
        }
        else
        {
            fseek($fd, $file_pos);
            while(!feof($fd) && count($lines) <= $page_size)
            {
                $lines[] = fgets($fd);
            }
            $file_pos = ftell($fd);
            fclose($fd);
        }

        $this -> data['file_name'] = $file_name;
        $this -> data['file_pos'] = $file_pos;
        $this -> data['lines'] = $lines;
    }
}

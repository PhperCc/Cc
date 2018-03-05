<?php
use MCU\Sys;

class mod_sys extends WebModule
{
    public function __init()
    {
        $this -> title = "系统";
        $this -> powers = array ();

        $this->ignore_login = ['download','get_token'];
        $this->ignore_view = ['download','get_token'];
    }

    public function view_index()
    {
        $total_space = disk_total_space("/home/system/data");
        $free_space = disk_free_space("/home/system/data");

        $version_history = [];
        foreach(MCU\LocalFile::getLines("record/upgrade/history") as $line)
        {
            $version_history[] = json_decode($line, true);
        }
        $this -> data['version_history'] = $version_history;

        $this -> data['total_space'] = size_text($total_space);
        $this -> data['free_space'] = size_text($free_space);
        $this -> data['used_percent'] = number_format(($total_space - $free_space) * 100 / $total_space, 2);

        $this -> data['memory_usage'] = size_text(memory_get_usage());
        $this -> data['memory_peak_usage'] = size_text(memory_get_peak_usage());

        $uptime = explode(" ", @file_get_contents("/proc/uptime"));
        $this -> data['sys_start_time'] = seconds_text(trim($uptime[0]));
        $this -> data['sys_used_time'] = seconds_text(trim($uptime[1]));
        $this -> data['sys_reboot_count'] = MCU\LocalFile::getObject("record/reboot_count", 0);
        $this -> data['sys_useage'] = number_format($uptime[1] * 100 / $uptime[0], 2);
    }

    public function view_login()
    {
        $account = trim($_POST['account']); 
        $password = trim($_POST['password']);

        if($account!='' && $password != '')
        {
            if($account=='')
            {
                $this->data['error'] = '用户名不能为空';
                return;
            } 
            if($password=='')
            {
                $this->data['error'] = '密码不能为空';
                return;
            } 
            $res = Sys::auth(['username'=>$account,'password'=>$password]);

            if($res != false){
                $_SESSION['uid'] = 'admin';
                setcookie("enhApiToken",$res, time()+3600*24);
                ob_clean();
                header("Location: ./");
            }
            else
            {
                $this->data['error'] = '用户名密码错误';
            }

        }
        
    }

    public function view_newapp()
    {
        $name = trim($_POST['appname']); 
        $desc = trim($_POST['appdesc']);
        if($name != '' && $desc != '')
        {
            error_reporting(7);
            $sname =  $_POST['Sname'];
            $sensor = [];
            $sensorConf = [];
            foreach ($sname as $k => $val) {
                $sensor[$k]['name'] = trim($val);
                $sensor[$k]['desc'] = trim($_POST['Sdesc'][$k]);
                $sensor[$k]['portType'] = trim($_POST['SportType'][$k]);
                $sensor[$k]['autoSave'] = trim($_POST['SautoSave'][$k]);
                $sensor[$k]['portOption'] = $_POST['SportOption'.$k];
                $sensorConf[$sensor[$k]['name']]['enabled'] = true;
                $sensorConf[$sensor[$k]['name']]['desc'] = $sensor[$k]['desc'];
                foreach ($sensor[$k]['portOption'] as $key => $value) 
                {
                    if($key == '_repeat_command') continue;
                    $sensorConf[$sensor[$k]['name']][$key] = $value;
                }
            }
            //生成代码业务 
            $appRoot = substr(SYS_ROOT,0,strlen(SYS_ROOT)-7).'Apps/';
            $appDir = $appRoot.$name.'/';
    
            if(!is_dir($appDir)) mkdir($appRoot.$_POST['appname']);
            if(!is_dir($appDir.'Libs')) mkdir($appDir.'Libs');
            if(!is_dir($appDir.'Modules')) mkdir($appDir.'Modules');
            if(!is_dir($appDir.'Sensors')) mkdir($appDir.'Sensors');
            $conf = ['desc'=>$desc,'model'=>['Main'=>['enabled'=>true,'desc'=>'主要业务']]];
            $conf['sensor'] = $sensorConf;
            file_put_contents($appDir.'Config.php', '<?php'."\n".'return '.var_export($conf, true).';');
            copy($appRoot.'Simple/Modules/Main.php', $appDir.'Modules/Main.php');
            //生成传感器文件
            foreach ($sensor as $k => $val) {
                $str = "<?php\nclass ".$val['name']." extends MCU\\Sensor\\SensorBase\n{\n";
                $str.="\tprotected ".'$name = "'.$val['name']."\";\n";
                $str.="\tprotected ".'$portType'." = '".$val['portType']."';\n";
                $str.="\tprotected ".'$autoSave'." = ".$val['autoSave'].";\n";
                $mode = false;
                if($val['portType'] == 'CanPort' || $val['portType'] == 'TcpPort')
                {
                    $str.="\tprotected ".'$interval'." = 0;\n";
                }
                else
                {
                    $interval = 1;
                    $str.="\tprotected ".'$mode'." = 'passivity';\n";
                    if(isset($val['portOption']['_repeat_command']))
                    {
                        $str.="\tprotected ".'$_repeat_command'." = ".$val['portOption']['_repeat_command'].";\n"; 
                        unset($val['portOption']['_repeat_command']);
                    }
                }
                $str .= "\n\tpublic function init()\n\t{\n";
                foreach ($val['portOption'] as $ks => $vs) {
                    if($ks == 'interval')
                    {
                        $str .= "\t\t".'$this->interval = ';
                    }
                    else
                    {
                        $str .= "\t\t".'$this->portOption['."'".$ks."'] = "; 
                    }
                    if(is_numeric($vs))
                    {
                        $str .= 'intval($this->getConfig("'.$ks.'", '.$vs.'));'."\n";
                    }
                    else
                    {
                        $str .= '$this->getConfig("'.$ks.'", "'.$vs.'");'."\n";
                    }   
                }
                $str .= "\t}\n\n\t".'public function decode($data)'."\n\t{\n\t\t//解析传感器数据代码\n\t\treturn ".'$data'.";\n\t}\n}";
                file_put_contents($appDir.'Sensors/'.$val['name'].'.php', $str);
                header("Location: ./");
            }

        }
        else
        {
            $port_root = SYS_ROOT . 'Libs/MCU/Port';
            $handler_files = glob("$port_root/*.php");
      
            $portArr = [];
            foreach($handler_files as $hadler_file)
            {
                $port_name = str_replace("$port_root/", '', $hadler_file);
                $port_name = str_replace('.php', '', $port_name);
                $reflector = new ReflectionClass('MCU\\Port\\'.$port_name);
                $this->data['port'] = [];
                $comment = $reflector -> getDocComment();
                if($comment!=false)
                {
                   $lines = explode("\n", $comment);
                   $initData = [];
                   foreach ($lines as $line) {
                       $lineArr = explode('@', $line);
                       if(isset($lineArr[1]))
                       {
                            
                            $lineArr[1] = trim($lineArr[1]);
                            if($lineArr[1]!='')
                            {
                                $isNull = (substr($lineArr[1],0,1) == '#');
                                if($isNull)
                                {
                                    $lineArr[1] = trim(substr($lineArr[1],1,strlen($lineArr[1])-1));   
                                }
                                
                            }
                            $lineArr = explode(' ', $lineArr[1]);
                            $newArr = [];
                            foreach ($lineArr as $val) {
                                if(trim($val) != '')
                                {
                                   $newArr[] = trim($val);
                                }
                            }
                            $initData[$newArr[0]] = ['isNull'=>$isNull];
                            $initData[$newArr[0]]['isNull'] = $isNull;
                            $initData[$newArr[0]]['type'] = $newArr[1];
                            $initData[$newArr[0]]['name'] = $newArr[2];
                            $initData[$newArr[0]]['default'] = $isNull?$newArr[3]:null;
                       }
                   }
                   $portArr[$port_name] = json_encode($initData,JSON_UNESCAPED_UNICODE);
                }
            }
            $this->data['port'] = $portArr;
        }
    }

    public function view_update()
    {
        $upRoot = '/home/system/data/pocket/';
        if(!is_dir($upRoot)) mkdir($upRoot);
        if($_FILES['file']['size']>0 && $_FILES['file']['type'] == 'application/x-tar')
        {
            $file = $upRoot.$_FILES['file']['name'];
            move_uploaded_file($_FILES['file']['tmp_name'],$file);
            //发送更新指令
            $fileArr = explode('-',$_FILES['file']['name']);
            if($fileArr[0] == $_POST['type'])
            {
                if($_POST['type'] != 'core'){
                    $type = $fileArr[0].'-'.$fileArr[1];
                }
                else
                {
                    $type = 'core';
                }
                $ver = substr($fileArr[2],0,strlen($fileArr[2])-4);
                $version = ['type'=>$type,'ver'=>$ver,'url'=>$file,'md5'=>md5_file($file)];
                $this->publish('ServerAction.update',$version);
                header("Location: ./");
            }
            else
            {
                $this->data['update_error'] = '包不正确';
            } 
        }
    }
    public function view_get_token()
    {
        $did = trim($_POST['did']); 
        $key = trim($_POST['key']);

        echo Sys::auth_token(['did'=>$did,'key'=>$key]);
        return;
    }

    public function ajax_change_password()
    {
        $oldPassword  = $_POST['oldPassword'];
        $newPassword1 = $_POST['newPassword1'];
        $newPassword2 = $_POST['newPassword2'];

        if(empty($oldPassword) || empty($newPassword1) || empty($newPassword2))
        {
            return $this->ajax_return(0, null, "密码不允许为空");
        }
        if(!Sys::auth(['username'=>'admin','password'=>$oldPassword]))
        {
            return $this->ajax_return(0, null, "原始密码不正确");
        }
        if($newPassword1 != $newPassword2)
        {
            return $this->ajax_return(0, null, "两次输入新密码不一致");
        }

        //修改密码
        Sys::set_password($newPassword1);

        return $this->ajax_return(1, null, '修改成功');
    }

    public function view_logout()
    {
        unset($_SESSION['uid']);
        Sys::del_token($_SESSION['token']);
        unset($_SESSION['token']);
        ob_clean();
        header("Location: ./");
    }

    public function view_ping()
    {
        echo "ok " . date("Y-m-d H:i:s");
    }

    public function view_download()
    {
        $file_path = Http::paramS("file_path");
        $show_name = Http::paramS("show_name");
        $start_pos = Http::paramI("start_pos", 0);

        if(empty($file_path)) return;

        $file_path = decrypt($file_path, "download");
        if(empty($file_path)) return;
        if(!file_exists($file_path)) exit("文件不存在");

        ob_clean();
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        //header("Accept-Ranges: bytes");
        header ("Expires: 0");
        Header("Content-Length: " . filesize($file_path));

        if(empty($show_name))
        {
            $show_name = basename($file_path);
            $show_name = str_ireplace(" ", "_", $show_name);
        }
        header("Content-Disposition: attachment; filename=$show_name");
        set_time_limit(0);
        $file = fopen($file_path, "r");
        if($start_pos > 0) fseek($file, $start_pos, SEEK_SET);
        while(!feof($file))
        {
            echo fread($file, 4096);
            ob_flush();
        }
        fclose($file);
    }
}

<?php
use MCU\Port\ComPort;
/**
 * GNSS芯片操作接口
 */
class api_GNSS extends MCU\ApiHelper\ApiBase
{
    const CONFIG_FILE_PATH = "/etc/rtk/rtkrcv.conf";
    //'38400'
    ///dev/ttyO2  /dev/ttyO5
    //修改ip   02 00 AE len data  [Sum bytes (status + type + length + data bytes) and modulo 256 the summation]  03
    //
    //00h: Get DHCP and IP Address l
    //01h: Response DHCP and IP Address l
    //02h: Set DHCP and IP Address l
    //0Ch: Get IP Port Summary
    //0Dh: Return IP Port Summary
    //0Eh: Get Individual IP Port Configuration
    //0Fh: Return Individual IP Port Configuration
    //10h: Set Individual IP Port Configuration
    //
    //重启 02 00 58 07 FF 00 52 55 53 55 54   03

    /**
     * 获取GNSS ip信息
     * @type string 芯片类型和和序号
     * return data string IP配置信息
     */
    public function get_ip($params)
    {
        global $port;
        $res = $this->check_type($params);
        if($res !== true)
        {
            return R(false, $res);
        }
        if($params['type'] == 'tb')
        {
            $cmd = pack('CCCCC', 2,0,0xAE,1,0);
            $cmd = $cmd.$this->check_sum($cmd);
            $res = $port[$params['index']]->send($cmd,0.5,-3);
            $res = $this->decode_ip($res);
        }
        
        return R(true, '', $res);
    }

    /**
     * 设置GNSS ip地址
     * @type string 芯片类型和和序号
     * @address string ip地址 
     * @#gateway string 网关， 当留空时系统默认
     * @#netmask string 子网掩码， 当留空时系统默认
     * @#dns string DNS， 当留空时系统默认
     * @#apply bool 修改后应用(会重启网络), 默认为 true
     */
    public function set_ip($params)
    {
        global $port;
        $res = $this->check_type($params);
        if($res !== true)
        {
            return R(false, $res);
        }
        if(false === get_param($params, "address"))
        {
            return R(false, "need param 'address'");
        }

        $config['address'] = $params['address'];

        if('' !== get_param($params, "gateway", ''))
        {
            $config['gateway'] = $params['gateway'];
        }

        if('' !== get_param($params, "netmask", ''))
        {
            $config['netmask'] = $params['netmask'];
        }

        if('' !== get_param($params, "dns", ''))
        {
            $config['dns'] = $params['dns'];
        }

        if($params['type'] == 'tb')
        {
            $cmd = pack('CCCCCC', 2,0,0xAE,0x16,2,0);
            $cmd.= $this->encode_ip($config);
            $cmd = $cmd.$this->check_sum($cmd);
            $res = $port[$params['index']]->send($cmd,0.5,-3);
            $res = bin2hex($res) == '06';
        }

        
        return R($res, '', $res);
    }

    /**
     * 获取GNSS 端口信息
     * @type string 芯片类型和和序号
     * @#number int 需要， 当留空默认0 (0,1)
     * return data string RTK提供数据ip:port或获取数据ip:port 
     */
    public function get_port($params)
    {
        global $port;
        $res = $this->check_type($params);
        if($res !== true)
        {
            return R(false, $res);
        }
        $index = '';
        if('' !== get_param($params, 'number', ''))
        {
            $index = $params['number'];
        }

        if($params['type'] == 'tb')
        {
            $index = ($index == '1')?0x16:0x15;
            $cmd = pack('CCCCCC', 2,0,0xAE,2,0x0E,$index);
            $cmd = $cmd.$this->check_sum($cmd);
            $res = $port[$params['index']]->send($cmd,0.5,-3);
            $res = $this->decode_port($res);
        }
        else
        {
            $res = ['mode'=>'off','ip'=>'','port'=>''];
            $config = $this->read_config();
            $index++;
            if($config['outstr'.$index.'-type']=='off')
            {
                if($config['inpstr'.$index.'-type'] == 'tcpcli')
                {
                    $ip_port = explode(':',$config['inpstr'.$index.'-path']);
                    $res['ip'] = $ip_port[0];
                    $res['port'] = $ip_port[1];
                    $res['mode'] = 'input'; 
                }
                
            }
            else
            {
                if($config['outstr'.$index.'-type'] == 'tcpsvr')
                {
                    $ip_port = explode(':',$config['outstr'.$index.'-path']);
                    $res['ip'] = '';
                    $res['port'] = $ip_port[1];
                    $res['mode'] = 'output';
                }
            }
            
        }
        
        return R(true, '', $res);
    }

    /**
     * 设置GNSS 端口信息
     * @type string 芯片类型和和序号
     * @port string TCP数据输出port或TCP输入ip:port
     * @#index int 需要， 当留空默认0 (0,1)
     * return data string IP配置信息
     */
    public function set_port($params)
    {
        global $port;

        $res = $this->check_type($params);
        if($res !== true)
        {
            return R(false, $res);
        }

        if(false === get_param($params, 'port'))
        {
            return R(false, "need param 'port'");
        }

        if('' !== get_param($params, 'number', ''))
        {
            $index = $params['number'];
        }
 
        if($params['type'] == 'tb')
        { 
            $bin = $this->encode_port($params);
            $cmd = pack('CCCCC', 2,0,0xAE,strlen($bin)+1,0x10);
            $cmd.= $bin;
            $cmd = $cmd.$this->check_sum($cmd);
            $res = $port[$params['index']]->send($cmd,0.5,-3);
            $res = bin2hex($res) == '06';
        }
        else
        {
            $config = $this->read_config();
            $ip_port = explode(':',$params['port']);
            $index++;

            $format = ($index==1)?'nmea':'rtcm3';
            if(count($ip_port) == 2)
            {
                $config['outstr'.$index.'-type'] = 'off';
                $config['inpstr'.$index.'-type'] = 'tcpcli';
                $config['inpstr'.$index.'-path'] = $params['port'];
                $config['inpstr'.$index.'-format'] = $format;
            }
            else
            {
                
                $config['inpstr'.$index.'-type'] = 'off';
                $config['outstr'.$index.'-type'] = 'tcpsvr';
                $config['outstr'.$index.'-path'] = '0.0.0.0:'.$ip_port[0];
                $config['outstr'.$index.'-format'] = $format;
            }
            $res = $this->write_config($config); 
        }
        
        return R($res, '', $res); 
    }

    /**
     * 重启定位芯片
     * @type string 芯片类型和和序号
     * return data bool 是否成功提交
     */
    public function reboot($params)
    {
        global $port;
        //重启芯片
        $res = $this->check_type($params);
        if($res !== true)
        {
            return R(false, $res);
        }

        if($params['type'] == 'tb')
        {
            $type = 0;
            $cmd = pack('CCCCCC', 2,0,0x58,7,0xFF,$type);
            $cmd.= 'RESET';
            $cmd = $cmd.$this->check_sum($cmd);
            $res = $port[$params['index']]->send($cmd,0.5,-3);
            $res = bin2hex($res) == '06';
        }
        else
        {
            Sys::exec("systemctl restart rtk");
        }

        return R($res, '', $res);
    }


    /**
     * 写入配置信息
     */
    private function write_config($config)
    {
        if(!is_array($config)) return false;

        $config_lines = [];
        foreach($config as $k => $v)
        {
            $config_lines[] = "$k=$v";
        }
        $config_contents = implode("\n", $config_lines);

        $bytes = file_put_contents(static::CONFIG_FILE_PATH, $config_contents);
        return $bytes == strlen($config_contents);
    }

    /**
     * 读取配置信息
     */
    private function read_config()
    {
        $config_contents = file_get_contents(static::CONFIG_FILE_PATH);
        $config_lines = explode("\n", $config_contents);
        $config = [];
        foreach($config_lines as $line)
        {
            $line = trim($line);
            if(empty($line) || substr($line,0,1) == '#') continue;
            $line = explode('#',trim($line));
            list($key, $val) = explode("=", trim($line[0]));
            $config[trim($key)] = trim($val);
        }
        return $config;
    }

    /**
     * 校验和
     */
    private function check_sum($data)
    {
        $sum = 0;
        $data = bin2hex($data);
        for($i = 2; $i < strlen($data); $i+=2)
        {
            $sum+= hexdec(substr($data,$i,2));
        }
        
        return pack('CC',($sum % 256),3);
    }

    /**
     * 验证类型和序号
     */
    private function check_type(&$params)
    { 
        global $port;

        if('' === get_param($params, 'type',''))
        {
            return "need param 'type'";
        }

        $params['type'] = trim($params['type']);
        if(strlen($params['type'])>2)
        {
            $params['index'] = intval(substr($params['type'],2,1));
            $params['type'] = substr($params['type'],0,2);
        }
        else
        {
            $params['index'] = 0;
        }

        if($params['type'] != 'ub' && $params['type'] != 'tb' )
        {
            return 'type is undefined';
        }

        if($params['type'] == 'tb')
        {
            if($port[$params['index']] == null)
            {
                $dio_name = ($params['index'] == 0)?'/dev/ttyO2':'/dev/ttyO5';
                
                $port[$params['index']] = new ComPort(['name'=>$dio_name,'baud'=>38400]);
            }
        }
        
        return true;
    }

    private function bin2hexstr($bin)
    {
        $hex = strtoupper(bin2hex($bin));
        $bytes = [];
        for($i = 0; $i < strlen($hex); $i+=2)
        {
            $bytes[] = $hex{$i} . $hex{$i + 1};
        }
        return implode(" ", $bytes);
    }
    /**
     * ip包转数组
     */
    private function decode_ip($data)
    {
        $res = [];
        $len = strlen($data);
        if($len>24)
        {
           $data = bin2hex($data);
           if (substr($data,11,1)==0)
           {
                $res['address'] = hexdec(substr($data,12,2)).'.'.hexdec(substr($data,14,2)).'.'.hexdec(substr($data,16,2)).'.'.hexdec(substr($data,18,2));
                $res['netmask'] = hexdec(substr($data,20,2)).'.'.hexdec(substr($data,22,2)).'.'.hexdec(substr($data,24,2)).'.'.hexdec(substr($data,26,2));
                $res['gateway'] = hexdec(substr($data,36,2)).'.'.hexdec(substr($data,38,2)).'.'.hexdec(substr($data,40,2)).'.'.hexdec(substr($data,42,2));
                $res['dns'] = hexdec(substr($data,44,2)).'.'.hexdec(substr($data,46,2)).'.'.hexdec(substr($data,48,2)).'.'.hexdec(substr($data,50,2));
           }
           else
           {
               $res['address'] = 'dns'; 
           }
        }

        return $res;
    }
    /**
     * 数组转ip包
     */
    private function encode_ip($data)
    {
        $res = '';
        if(isset($data['address']))
        {
            $data['address'] = explode('.',$data['address']);
            $res.= pack('CCCC',$data['address'][0],$data['address'][1],$data['address'][2],$data['address'][3]);
            if(isset($data['netmask']))
            {
                $data['netmask'] = explode('.',$data['netmask']);
            }
            else
            {
                $data['netmask'] = [255,255,255,0];
            }
            $res.= pack('CCCC',$data['netmask'][0],$data['netmask'][1],$data['netmask'][2],$data['netmask'][3]);

            $data['broadcast'] = $data['address'];
            $data['broadcast'][3] = 255;
            $res.= pack('CCCC',$data['broadcast'][0],$data['broadcast'][1],$data['broadcast'][2],$data['broadcast'][3]);

            if(isset($data['gateway']))
            {
                $data['gateway'] = explode('.',$data['gateway']);
            }
            else
            {
                $data['gateway'] = $data['address'];
                $data['gateway'][3] = 1;
            }
            $res.= pack('CCCC',$data['gateway'][0],$data['gateway'][1],$data['gateway'][2],$data['gateway'][3]);
            if(isset($data['dns']))
            {
                $data['dns'] = explode('.',$data['dns']);
            }
            else
            {
                $data['dns'] = $data['gateway'];
            }
            $res.= pack('CCCC',$data['dns'][0],$data['dns'][1],$data['dns'][2],$data['dns'][3]);
        }

        return $res;
    }
    /**
     * 端口包转数组
     */
    private function encode_port($data)
    {
        $data['number'] = ($data['number'] == 1)?0x16:0x15;
        $res = pack('CC',$data['number'],1);
        $split = explode(':',$data['port']);
        if(count($split) == 2)
        {
            //input
            $split[1] = intval($split[1]);
            $res.= pack('nCCCCCn',$split[1],0,0x3c,0,0,1,$split[1]);
            $res.= pack('CCCCCCC',0,0,0,0,0,0,0);
            $res.= pack('C',strlen($split[0]));
            $res.= $split[0];
        }
        else
        {   
            //output
            $res.= pack('nCCCCCn',$split[0],0,0x3c,1,0,0,0);
            $res.= pack('CCCCCCC',0,0,0,0,0,0,0);
            $res.= pack('C',0);
        }
        return $res;
    }
    /**
     * 数组转端口包
     */
    private function decode_port($data)
    {
        $res = [];
        $len = strlen($data);
        if($len>24)
        {
            $res['ip'] = substr($data,24,$len - 26); 
            $data = bin2hex($data);
            $res['mode'] = (substr($data,23,1)=='0')?'input':'output';
            $res['port'] = hexdec(substr($data,14,4));  
        }
        return $res;
    }
}
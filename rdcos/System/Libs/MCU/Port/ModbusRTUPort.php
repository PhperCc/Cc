<?php
namespace MCU\Port;

/**
 * ModbusRTUPort
 * @#name string 端口 /dev/ttyO3
 * @#baud int 波特率 9600
 * @#bits int 数据位 8
 * @#stop int 停止位长度 1
 * @#parity int 奇偶校验 0
 * @#addr int 从机地址 1
 * @#format string 解包格式 1
 * @#interval int 获取间隔 1
 * @#_repeat_command string 执行指令 ""
 */
use \MCU\Cache;
class ModbusRTUPort extends ComPort
{

    private $addr = '';
    private $valueCount = 1;
    private $pack_format = '';

    private $port_key = '';
    private $master = false;
    private $send_list = [];


    public static $table = [
        0x0000, 0xC0C1, 0xC181, 0x0140, 0xC301, 0x03C0,
        0x0280, 0xC241, 0xC601, 0x06C0, 0x0780, 0xC741,
        0x0500, 0xC5C1, 0xC481, 0x0440, 0xCC01, 0x0CC0,
        0x0D80, 0xCD41, 0x0F00, 0xCFC1, 0xCE81, 0x0E40,
        0x0A00, 0xCAC1, 0xCB81, 0x0B40, 0xC901, 0x09C0,
        0x0880, 0xC841, 0xD801, 0x18C0, 0x1980, 0xD941,
        0x1B00, 0xDBC1, 0xDA81, 0x1A40, 0x1E00, 0xDEC1,
        0xDF81, 0x1F40, 0xDD01, 0x1DC0, 0x1C80, 0xDC41,
        0x1400, 0xD4C1, 0xD581, 0x1540, 0xD701, 0x17C0,
        0x1680, 0xD641, 0xD201, 0x12C0, 0x1380, 0xD341,
        0x1100, 0xD1C1, 0xD081, 0x1040, 0xF001, 0x30C0,
        0x3180, 0xF141, 0x3300, 0xF3C1, 0xF281, 0x3240,
        0x3600, 0xF6C1, 0xF781, 0x3740, 0xF501, 0x35C0,
        0x3480, 0xF441, 0x3C00, 0xFCC1, 0xFD81, 0x3D40,
        0xFF01, 0x3FC0, 0x3E80, 0xFE41, 0xFA01, 0x3AC0,
        0x3B80, 0xFB41, 0x3900, 0xF9C1, 0xF881, 0x3840,
        0x2800, 0xE8C1, 0xE981, 0x2940, 0xEB01, 0x2BC0,
        0x2A80, 0xEA41, 0xEE01, 0x2EC0, 0x2F80, 0xEF41,
        0x2D00, 0xEDC1, 0xEC81, 0x2C40, 0xE401, 0x24C0,
        0x2580, 0xE541, 0x2700, 0xE7C1, 0xE681, 0x2640,
        0x2200, 0xE2C1, 0xE381, 0x2340, 0xE101, 0x21C0,
        0x2080, 0xE041, 0xA001, 0x60C0, 0x6180, 0xA141,
        0x6300, 0xA3C1, 0xA281, 0x6240, 0x6600, 0xA6C1,
        0xA781, 0x6740, 0xA501, 0x65C0, 0x6480, 0xA441,
        0x6C00, 0xACC1, 0xAD81, 0x6D40, 0xAF01, 0x6FC0,
        0x6E80, 0xAE41, 0xAA01, 0x6AC0, 0x6B80, 0xAB41,
        0x6900, 0xA9C1, 0xA881, 0x6840, 0x7800, 0xB8C1,
        0xB981, 0x7940, 0xBB01, 0x7BC0, 0x7A80, 0xBA41,
        0xBE01, 0x7EC0, 0x7F80, 0xBF41, 0x7D00, 0xBDC1,
        0xBC81, 0x7C40, 0xB401, 0x74C0, 0x7580, 0xB541,
        0x7700, 0xB7C1, 0xB681, 0x7640, 0x7200, 0xB2C1,
        0xB381, 0x7340, 0xB101, 0x71C0, 0x7080, 0xB041,
        0x5000, 0x90C1, 0x9181, 0x5140, 0x9301, 0x53C0,
        0x5280, 0x9241, 0x9601, 0x56C0, 0x5780, 0x9741,
        0x5500, 0x95C1, 0x9481, 0x5440, 0x9C01, 0x5CC0,
        0x5D80, 0x9D41, 0x5F00, 0x9FC1, 0x9E81, 0x5E40,
        0x5A00, 0x9AC1, 0x9B81, 0x5B40, 0x9901, 0x59C0,
        0x5880, 0x9841, 0x8801, 0x48C0, 0x4980, 0x8941,
        0x4B00, 0x8BC1, 0x8A81, 0x4A40, 0x4E00, 0x8EC1,
        0x8F81, 0x4F40, 0x8D01, 0x4DC0, 0x4C80, 0x8C41,
        0x4400, 0x84C1, 0x8581, 0x4540, 0x8701, 0x47C0,
        0x4680, 0x8641, 0x8201, 0x42C0, 0x4380, 0x8341,
        0x4100, 0x81C1, 0x8081, 0x4040
    ];

    public function __construct($option)
    {
        $option['baud'] = isset($option['baud'])?$option['baud']:9600;

        $this->addr = isset($option['addr'])?$option['addr']:0x01;
        $dio_num = str_replace('/dev/','',$option['name']);
        $this->port_key = 'com485port:'.$dio_num.':used';

        if(isset($option['format']))
        {
            if(is_numeric($option['format']))
            {
                $this->valueCount = intval($option['format']);
                if($this->valueCount < 1)
                {
                    self::$statistics['throw_exception']++;
                    if($this->onError!=null){
                        call_user_func($this->onError,$e);
                    }
                    exit(250);
                }
            }
            else
            {
                $this->$pack_format = $option['format'];
            }  
        }

        if (!$this->checkMaster())
        {
            $this->master = true;
            $this->setMaster();
            $this->open($option);
            //订阅发送消息
            \Channel\Client::on($this->port_key.'send', function($body)
            {
                array_push($this->send_list,$body); 
            });
        }
        else
        {
            //订阅返回消息
            \Channel\Client::on($this->port_key, function($body)
            {
                $this->baseRead($body);
                $this->doWork();
            });  
        }
    }

    //接收消息回调
    private function receive($data)
    {
        $first_byte = ord($data{0});

        if(ENVIRONMENT == 'development')
        {
            var_dump('receive:'.$this->bin2hexstr($data));
        }
        
        if($first_byte == intval($this->addr))
        {
            $this->baseRead($data);
        }
        else
        {
            \Channel\Client::publish($this->port_key, $data);
        }
    }

    //执行发送任务
    private function doWork()
    {
        if(count($this->send_list)>0)
        {
            $bin = array_shift($this->send_list);
            if(ENVIRONMENT == 'development')
            {
                var_dump('send:'.$this->bin2hexstr($bin));  
            }
            dio_write($this->fd, $bin);
            $data = '';
            $i=0;
            $isNotEnd = true;
            while($isNotEnd) {
                usleep(100000);
                $i++;
                $bin = dio_read($this->fd, 256);
                if($bin != null)
                {
                    $tmphex = bin2hex($bin);
                    if(substr($tmphex,2,1) == 0 && substr($tmphex,3,1) == 8)
                    {
                        $isNotEnd = false;
                        $data = $bin;
                    }
                    else
                    {
                        $data .= $bin;
                    }

                    if($this->valueCount < 5 || ($this->valueCount*2 < strlen($data) && $this->valueCount > 5))
                    {
                        $isNotEnd = false;
                    }
                }
                if($i>15)
                {
                    $isNotEnd = false;
                }
            }
            $this->receive($data);
        }
    }

    /**
     * 设置端口发送缓存
     *
     */
    private function setBufferLen($num)
    {
        Cache::set($this->port_key,$num,5);
    }
    
    /**
     * 设置是主进程
     *
     */
    private function setMaster()
    {
        Cache::set($this->port_key,1,2);
    }

    /**
     * 检查主线程
     * return data bool 是否主进程
     */
    private function checkMaster()
    {
        usleep($this->addr*100000);
        $val = Cache::get($this->port_key);
        return ($val===1);
    }

    /**
     * 获取解包的格式
     * return data string 解包格式
     */
    private function getDecodeFormat()
    {
        if($this->pack_format == '')
        {
            if($this->valueCount>1)
            {
                $pack_format = 'n'.$this->valueCount.'value';
            }
            else
            {
                $pack_format = 'nvalue';
            }
        }
        else
        {
            $pack_format = $this->pack_format;
        }
        
        return $pack_format;
    }

    /**
     * 发送数据到端口队列
     * @data array 发送到端口的数据
     *
     * return res bool 状态
     */
    public function push($data,$encode=true,$crc=true)
    {
        
        if(count($data) == 3 && $encode == true)
        {
            //起始位 设备地址 功能代码 数据 校验
            $bin = pack('CCnn', $this->addr,$data[0],$data[1],$data[2]);
        }
        else
        {
            if($encode == false && $data!='')
            {
                $bin = $data;
            }
            else
            {
                return false;
            }  
        }
        
        if($crc)
        {
            $bin .= pack('v', $this->crc($bin));
        }
        if($this->master)
        {
            array_push($this->send_list,$bin);
            $this->setBufferLen(count($this->send_list));
            $this->doWork();  
        }
        else
        {
            \Channel\Client::publish($this->port_key.'send', $bin);
        }

        return true;
    }

    /**
     * CRC校验数据
     * @data string 需要校验的数据
     *
     * return csc string 计算后的校验位
     */
    public function crc($data)
    {
        $crc = 0xFFFF;
        for($i = 0; $i < strlen($data); $i++)
        {
            $chr = substr($data, $i, 1);
            $asc = ord($chr);
            $crc = static::$table[($asc ^ $crc) & 0xFF] ^ ($crc >> 8 & 0xFF);
        }
        $crc = $crc ^ 0x00;

        return $crc & 0xFFFF;
    }

    /**
     * 异步接收数据
     * @bin string 接收到的数据
     *
     */
    public function baseRead($bin)
    {
        $cache_len = strlen($bin);

        if($cache_len == 0)
        {
            return;
        }

        $first_byte = ord($bin{0});
        if($first_byte === intval($this->addr))
        {
           //判断是否是错误包
            $tmphex = bin2hex($bin);
            if(substr($tmphex,2,1) == 0 && substr($tmphex,3,1) == 8)
            {
                self::$statistics['throw_exception']++;
                if($this->onError!=null){
                    call_user_func($this->onError,$this->bin2hexstr($bin));
                }  
                return; 
            } 
            else
            {
                
                //进行校验 
                $cache_data = substr($bin, 0, $cache_len - 2);   // 除去尾部crc的数据区
                $cache_crc = substr($bin, -2);   // crc
                $verify_crc = pack('v', $this->crc($cache_data));   // 根据数据计算出的crc
                if($cache_crc == $verify_crc)
                {
                    try {
                        $cache_data = substr($bin, 3, $cache_len - 5);
                        $pack_format = $this->getDecodeFormat();
                        if(false !== $data = unpack($pack_format, $cache_data))
                        {
                            if($this->pack_format == '')
                            {
                                $tmpData = [];
                                foreach ($data as $value) {
                                    $tmpData[] = $value;
                                }
                                if(!empty($tmpData))
                                {
                                   call_user_func($this->onMessage, $tmpData); 
                                }  
                            }
                            else
                            {
                                call_user_func($this->onMessage, $data); 
                            }
                            
                        } 
                    } catch (\Exception $e) {
                        self::$statistics['throw_exception']++;
                        if($this->onError!=null){
                            call_user_func($this->onError,$e);
                        }
                        return;
                    } catch (\Error $e) {
                        self::$statistics['throw_exception']++;
                        if($this->onError!=null){
                            call_user_func($this->onError,$e);
                        }
                        return;
                    }
                }    

            }
        }  
    }

}

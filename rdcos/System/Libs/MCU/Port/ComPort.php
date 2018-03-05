<?php
namespace MCU\Port;

/**
 * ComPort
 * @name string 地址
 * @#baud int 波特率 9600
 * @#bits int 数据位 8
 * @#stop int 停止位长度 1
 * @#parity int 奇偶校验 0
 * @#interval int 获取间隔 1
 */

class ComPort extends PortBase
{
    public $fp = null;
    public static $portIsBusy = false;

    public function __construct($dio_options)
    {
        $this->open($dio_options);
    }

    public function open($dio_options)
    {
        $dio_name = $dio_options['name'];
        unset($dio_options['name']);
        $dio_options['baud'] = isset($dio_options['baud'])?$dio_options['baud']:9600;
        $dio_options['bits'] = isset($dio_options['bits'])?$dio_options['bits']:8;
        $dio_options['stop'] = isset($dio_options['stop'])?$dio_options['stop']:1;
        $dio_options['parity'] = isset($dio_options['parity'])?$dio_options['parity']:0;
        if($dio_name == '') {
            self::$statistics['throw_exception']++;
            return false;
        }
        
        self::$statistics['connection_count']++;

        while((false === $this->fd = dio_open($dio_name, O_RDWR|O_NOCTTY|O_NONBLOCK)) || !is_resource($this -> fd))
        {
            self::$statistics['throw_exception']++;
            sleep(2);
        }
        dio_tcsetattr($this->fd, $dio_options);

        if($this->onConnect!=null){
            call_user_func($this->onConnect);
        }
    }

    /**
     * 执行AT指令
     *
     * @param $command
     * @param float $timeout
     *
     * @return string
     */
    public function send($command, $timeout = 0.1, $index = -2)
    {  
        if($command == "") return "";
        while(self::$portIsBusy)
        {
            usleep(pow(10, 6) * 0.1);
        }

        self::$portIsBusy = true;
        if(substr($command, 0, 2) !== "AT" && $index>-3) $command = "AT$command";
        if(substr($command, -1) !== "\r" && $index>-3) $command .= "\r";
        if(ENVIRONMENT == 'development')
        {
            if($index<-2)
            {
                var_dump('send:'.$this->bin2hexstr($command));
            }
            else
            {
                var_dump('send:'.$this->$command);
            } 
        }

        dio_write($this->fd, $command);
        self::$portIsBusy = false;
        $return = '';
        $isNotEnd = true;
        $send_code = substr(bin2hex($command),4,2);

        $i=0;
        while($isNotEnd) 
        {
            usleep(100000);
            $i++;
            if($i>100)
            {
                break;
            }
            $bin = dio_read($this->fd, 256);
            if($index == -3)
            {
                if(strlen($bin)>1){
                   
                   $code = substr(bin2hex($bin),4,2);
                   if($code == $send_code)
                   {
                        if(ENVIRONMENT == 'development')
                        {
                            var_dump('receive:'.$this->bin2hexstr($bin));
                        }
                        $return .= $bin;
                        $isNotEnd = false; 
                   }
                }
                else if(strlen($bin) == 1)
                {
                    $code = bin2hex($bin);
                    if($code == '06' || $code == '15')
                    {
                        if(ENVIRONMENT == 'development')
                        {
                            var_dump('receive:'.$this->bin2hexstr($bin));
                        }
                        $return = $bin;
                        $isNotEnd = false;  
                    } 
                } 
            }
            else
            {
                if($bin != '')
                {
                    $return .= $bin;
                    $isNotEnd = false; 
                }
                usleep(pow(10, 6) * $timeout);
            }
            
        }
        
        if($index>-2){
           $return = $this-> parse_AT_return($return,$index);
           if(ENVIRONMENT == 'development')
           {
               var_dump('receive:'.$return);
           }
        }
        self::$statistics['total_request']++;
        return $return;
    }

    /**
     * 从AT指令返回内容中取得数据
     *
     * @param $return
     * @param int $index
     *
     * @return bool|string
     */
    public function parse_return($return, $index = -1)
    {
        // 去掉前后换行
        $return = trim($return);

        // 分割每行
        $lines = explode("\r\n", $return);
        $line = $lines[0];
        if($index == -1) return trim($line);

        if(false === $line = substr($line, strpos($line, ":") + 1)) return false;
        $data_nodes = explode(",", $line);
        if($index > count($data_nodes) - 1) return false;
        return trim($data_nodes[$index]);
    }

    /**
     * 二进制流转16进制值字符
     * @bin string 二进制流
     *
     * return data string 16进制值字符
     */
    protected function bin2hexstr($bin)
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
     * 关闭操作句柄
     */
    public function close()
    {
        dio_close($this->fd);
        self::$statistics['connection_count']--;
    }

    public function __destruct()
    {
        dio_close($this->fd);
        self::$statistics['connection_count']--;
    }
}

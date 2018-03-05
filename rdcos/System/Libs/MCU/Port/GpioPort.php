<?php
namespace MCU\Port;
use \Workerman\Worker;

/**
 * GpioPort
 * @port_num int 针脚号
 * @#port_type string 信号类型 in
 * @#interval int 获取间隔 1
 */

class GpioPort extends PortBase
{
    public $fp = null;
    
    public function __construct($option)
    {
        self::$statistics['connection_count']++;
        $this->open($option);

        $option['port_type'] = isset($option['port_type'])?$option['port_type']:'in';
        $file_path = '/sys/class/gpio/gpio'.$option['port_num'].'/value';

        if($option['port_type'] == 'in')
        {
            $this->fd = fopen($file_path, 'r');
            $conn = new \WorkerMan\Connection\TcpConnection($this->fd);
            $connect->onConnect = function($conn)
            {
                self::$statistics['connection_count']++;
                if($this->onConnect != null)
                {
                    call_user_func($this->onConnect);
                }
            }; 
            $conn->onMessage = function($conn, $data)
            {
                call_user_func($this->onMessage,$data);
                fseek($this->fd, 0);
            };
            $connect->onError = function($conn, $code, $msg)
            {
                self::$statistics['throw_exception']++;
                if($this->onError != null)
                {
                    call_user_func($this->onError,$msg);
                }
            };

            $connect->onClose = function($conn)
            {
                self::$statistics['connection_count']--;
                if($this->onClose != null)
                {
                    call_user_func($this->onClose);
                }
            };
        }
        else
        {
            $this->fd = fopen($file_path, 'w'); 
        }
    }

    public function send($val)
    {
        fwrite($this->fd, $val);
    }

    /**
     * 打开Gpio控制口
     */
    public function open($option){
        //open gpio
        $gpio_number = $option['port_num'];
        if(!file_exists("/sys/class/gpio/gpio$gpio_number/"))
        {
            \MCU\Utils\OperationSystem::exec("echo $gpio_number > /sys/class/gpio/export");
        }
        if(!file_exists("/sys/class/gpio/gpio$gpio_number/"))
        {
            return false;
        }
        \MCU\Utils\OperationSystem::exec("echo ".$option['port_type']." > /sys/class/gpio/gpio$gpio_number/direction");

        return true;
    }

    public function __destruct()
    {
        self::$statistics['connection_count']--;
    }
}

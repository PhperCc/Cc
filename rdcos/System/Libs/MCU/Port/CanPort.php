<?php
namespace MCU\Port;
use \MCU\Utils\OperationSystem;
/**
 * CanPort
 * @name string 地址
 * @#baud int 波特率 250000
 */

class CanPort extends PortBase
{
    public $fp = null;
    public static $portIsBusy = false;
    public static $data = array();


    public function __construct($options)
    {

        if($options['name'] == '') {
            self::$statistics['throw_exception']++;
            return false;
        }
        $options['baud'] = empty($options['baud'])?250000:$options['baud'];
        self::$statistics['connection_count']++;

        while ($this->open($options) == false) {
            sleep(1);
        }
        if($this->onConnect!=null){
            call_user_func($this->onConnect);
        }
        \Workerman\Worker::$globalEvent->add($this->fp, \Workerman\Events\EventInterface::EV_READ, function($_socket){
            $this->baseRead($_socket);
        });
    }

    /**
     * 打开can口
     */
    public function open($options){
        //init can
        $can_up_count = 0;
        $can_device = $options['name'];
        while(OperationSystem::get_network_info($can_device, "state", 'down') == 'down')
        {
            OperationSystem::exec("ifconfig $can_device down");
            usleep(1000);   // 1ms
            OperationSystem::exec("ip link set $can_device type can bitrate ".$options['baud']);
            OperationSystem::exec("ifconfig $can_device up");
            usleep(10000);  // 10ms
            $can_up_count++;
        }

        //open can
        $can = CanOpen("msd", $can_device, 0, "can_id1:0");
        if(!is_resource($can))
        {
            self::$statistics['throw_exception']++;
            return false;
        }
        $this->fp = CanGetSocket($can);
        return true;
    }

    /**
     * 收到数据回调
     */
    public function baseRead($_socket)
    {
        if(false !== $bin = @socket_read($_socket, 255))
        {
            self::$statistics['total_request']++;
            $struct = unpack('Ican_id/Clen/Cflags/CRes0/CRes1/fval1/fval2', $bin);
            if (!$this->onMessage) {
                continue;
            }
            try {
                call_user_func($this->onMessage, $struct);
            } catch (\Exception $e) {
                self::$statistics['throw_exception']++;
                if($this->onError!=null){
                    call_user_func($this->onError,$e);
                }
                exit(250);
            } catch (\Error $e) {
                self::$statistics['throw_exception']++;
                if($this->onError!=null){
                    call_user_func($this->onError,$e);
                }
                exit(250);
            }
        }
    }


    public function __destruct()
    {
        self::$statistics['connection_count']--;
        if($this->onClose!=null){
            call_user_func($this->onClose);
        }
    }
}

<?php
namespace MCU\Port;

/**
 * TcpPort
 * @url string 监听地址
 */

class TcpPort extends PortBase
{
    public function __construct($url)
    {
        $connect = new \Workerman\Connection\AsyncTcpConnection($url);
        $connect->onConnect = function($conn)
        {
            self::$statistics['connection_count']++;
            if($this->onConnect != null)
            {
                call_user_func($this->onConnect);
            }
        };
        $connect->onMessage = function(\Workerman\Connection\AsyncTcpConnection $conn, $data)
        {
            self::$statistics['total_request']++;
            call_user_func($this->onMessage,$data);
        };
        $connect->onError = function(\Workerman\Connection\AsyncTcpConnection $conn, $code, $msg)
        {
            self::$statistics['throw_exception']++;
            if($this->onError != null)
            {
                call_user_func($this->onError,$msg);
            }
        };

        $connect->onClose = function(\Workerman\Connection\AsyncTcpConnection $conn)
        {
            self::$statistics['connection_count']--;
            if($this->onClose != null)
            {
                call_user_func($this->onClose);
            }
            $conn->reConnect(2);
        };
        $connect->connect();
    }

    public function __destruct()
    {
        self::$statistics['connection_count']--;
    }
}

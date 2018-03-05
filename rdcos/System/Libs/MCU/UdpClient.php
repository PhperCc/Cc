<?php
namespace MCU;
/**
 * Class UdpClient
 */
class UdpClient
{
    const BCAST_ALL = '255.255.255.255';

    private $socket = null;

    public function __construct($broad_cast = false)
    {
        $this -> socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if($broad_cast)
        {
            //设置为广播方式
            socket_set_option($this -> socket, SOL_SOCKET, SO_BROADCAST, 1);
        }
    }

    public function send($host, $port, $data)
    {
        if(empty($host))
        {
            throw new Exception("host not allowed empty");
        }

        if(false === $ip = gethostbyname($host))
        {
            throw new Exception("unknow hostname: $host");
        }

        if($port <= 0 || $port > 0xFFFF)
        {
            throw new Exception("invalid udp port: $port");
        }

        socket_sendto($this -> socket, $data, strlen($data), 0, $ip, $port);
    }

    public function __destruct()
    {
        socket_close($this -> socket);
    }
}
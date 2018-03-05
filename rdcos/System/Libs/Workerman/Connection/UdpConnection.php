<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Connection;

/**
 * UdpConnection.
 */
class UdpConnection extends ConnectionInterface
{
    /**
     * Application layer protocol.
     * The format is like this Workerman\\Protocols\\Http.
     *
     * @var \Workerman\Protocols\ProtocolInterface
     */
    public $protocol = null;

    /**
     * Udp socket.
     *
     * @var resource
     */
    protected $_socket = null;

    /**
     * Remote address.
     *
     * @var string
     */
    protected $_remoteAddress = '';

    /**
     * Construct.
     *
     * @param resource $socket
     * @param string   $remote_address
     */
    public function __construct($socket, $remote_address)
    {
        $this->_socket        = $socket;
        $this->_remoteAddress = $remote_address;
    }

    /**
     * Sends data on the connection.
     *
     * @param string $send_buffer
     * @param bool   $raw
     * @return void|boolean
     */
    public function send($send_buffer, $raw = false)
    {
        if (false === $raw && $this->protocol) {
            $parser      = $this->protocol;
            $send_buffer = $parser::encode($send_buffer, $this);
            if ($send_buffer === '') {
                return null;
            }
        }
        return strlen($send_buffer) === stream_socket_sendto($this->_socket, $send_buffer, 0, $this->_remoteAddress);
    }

    /**
     * Get remote IP.
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $pos = strrpos($this->_remoteAddress, ':');
        if ($pos) {
            return trim(substr($this->_remoteAddress, 0, $pos), '[]');
        }
        return '';
    }

    /**
     * Get remote port.
     *
     * @return int
     */
    public function getRemotePort()
    {
        if ($this->_remoteAddress) {
            return (int)substr(strrchr($this->_remoteAddress, ':'), 1);
        }
        return 0;
    }

    /**
     * Close connection.
     *
     * @param mixed $data
     * @param bool  $raw
     * @return bool
     */
    public function close($data = null, $raw = false)
    {
        if ($data !== null) {
            $this->send($data, $raw);
        }
        return true;
    }
}

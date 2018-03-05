<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */

namespace Channel;
use Workerman\Worker;
/**
 * Channel server.
 */
class Server
{
    /**
     * Worker instance.
     * @var Worker
     */
    protected $_worker = null;

    /**
     * Construct.
     * @param string $ip
     * @param int $port
     */
    public function __construct($ip = '0.0.0.0', $port = 2206)
    {
        $worker = new Worker("frame://$ip:$port");
        $worker->count = 1;
        $worker->name = 'ChannelServer';
        $worker -> description = "内部消息推送通知服务";
        $worker->channels = array();
        $worker->onMessage = array($this, 'onMessage') ;
        $worker->onClose = array($this, 'onClose'); 
        $this->_worker = $worker;
    }

    /**
     * onClose
     * @return void
     */
    public function onClose($connection)
    {
        if(empty($connection->channels))
        {
            return;
        }
        foreach($connection->channels as $channel)
        {
            unset($this->_worker->channels[$channel][$connection->id]);
            if(empty($this->_worker->channels[$channel]))
            {
                unset($this->_worker->channels[$channel]);
            }
        }
    }

    /**
     * onMessage.
     * @param TcpConnection $connection
     * @param string $data
     */
    public function onMessage($connection, $data)
    {
        if(!$data)
        {
            return;
        }
        $worker = $this->_worker;
        $data = unserialize($data);
        $type = $data['type'];
        $channels = $data['channels'];
        switch($type)
        {
            case 'subscribe':
                foreach($channels as $channel)
                {
                    $connection->channels[$channel] = $channel;
                    $worker->channels[$channel][$connection->id] = $connection;
                }
                break;
            case 'unsubscribe':
                foreach($channels as $channel)
                {
                    if(isset($connection->channels[$channel]))
                    {
                        unset($connection->channels[$channel]);
                    }
                    if(isset($worker->channels[$channel][$connection->id]))
                    {
                        unset($worker->channels[$channel][$connection->id]);
                        if(empty($worker->channels[$channel]))
                        {
                            unset($worker->channels[$channel]);
                        }
                    }
                }
                break;
            case 'publish':
                foreach($channels as $channel)
                {
                    if(empty($worker->channels[$channel]))
                    {
                        continue;
                    }
                    $buffer = serialize(array('channel'=>$channel, 'data' => $data['data']))."\n";
                    foreach($worker->channels[$channel] as $connection)
                    {
                        $connection->send($buffer);
                    }
                }
                break;
        }
    }
}

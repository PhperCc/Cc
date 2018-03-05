<?php
namespace MCU\ApiHelper;

/**
 * API基类
 */
class ApiBase
{    
    //public function __construct()
    //{
    //    \Channel\Client::connect('127.0.0.1', CHANNEL_PORT);
    //}
    //protected function publish($topic,$body)
    //{
    //    \Channel\Client::publish('SYS.'.$topic,$body);
    //}
    
    protected function publish($topic,$body)
    {
        $topic = 'SYS.'.$topic;
        $fp=stream_socket_client('tcp://127.0.0.1:'.CHANNEL_PORT, $errno, $errstr);
        if(!$fp)
        {
          return  "erreur : $errno - $errstr";
        }
        else
        {
            $data = serialize(array('type' => 'publish', 'channels'=>(array)$topic, 'data' => $body));
            $total_length = 4 + strlen($data);
            $data = pack('N', $total_length) . $data;
            fwrite($fp,$data);
            fclose($fp);
            return true;
        }
    }
}
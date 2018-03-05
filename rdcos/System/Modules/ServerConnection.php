<?php
use MCU\Sys;
use MCU\Kernel;
use MCU\Cache;
use MQTT\MQTT;
use MQTT\MessageHandler;
use MQTT\Message\PUBACK;
use MQTT\Message\PUBCOMP;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * Class svc_ServerConnection
 * 网络链接和rtk处理
 */
class sys_ServerConnection extends Kernel
{

    const TOPICS_PRE = 'enh/terminal/';
    const TOPICS_SEND = 'enh/server/';

    protected $MQTT = null;
    protected $RtkTopic = '';
    protected $RtkType = '';
    
    /**
     * 启动服务
     */
    public function start()
    {
        Cache::set('sys:command:connected',0);
        $server_url = Sys::get_config('serverLink');
        $keep_alive = $this->getConfig('keep_alive', 10);
        $this->RtkType = Sys::get_config('rtkMode');
        $guid = Sys::get_config('guid');
        if($this->RtkType == 'moving')
        {
            $this->RtkTopic = Sys::get_config('rtkTopic');
            Cache::set('rtkTopic',$this->RtkTopic);
            if($this->RtkTopic != '')
            {
                $topics['enh/rtk/'.$this->RtkTopic] = 1; 
            } 
        }
        else
        {
            $this->RtkTopic = self::TOPICS_PRE.'rtk/'.$guid;
            \Channel\Client::on("SendRtk", function($body)
            {   
                $this->MQTT->publish_sync($this->RtkTopic, $body, 1, 0);
            });
        }

        $sleep = 2;
        while(!$this->connectMqtt($server_url,$keep_alive)){
            sleep($sleep);
            if($sleep<60){
              $sleep += 2;  
            }else{
              $this -> log('MQTT connect error');  
            }
        }

        $msghander = new ServerMessages($this->RtkTopic);
        $topics[self::TOPICS_PRE.$guid.'/#'] = 2;
        $this->MQTT->subscribe($topics);
        $this->MQTT->setHandler($msghander);
        list($last_subscribe_msgid, $last_subscribe_topics) = $this->MQTT->do_subscribe();
        $this->MQTT->subscribe_awaits[$last_subscribe_msgid] = $last_subscribe_topics;
        Cache::set('sys:command:connected',1);

        //发送服务
        \Channel\Client::on('SendData', function($body) use($guid)
        {    
            $MsgID = 0;
            if(is_array($body)){
                if(isset($body['MsgID']))
                {
                    $MsgID = $body['MsgID'];
                    unset($body['MsgID']);
                }
                $body = json_encode($body,JSON_UNESCAPED_UNICODE);
            }
            $rec = $this->MQTT->publish_sync(self::TOPICS_SEND.$guid.'/'.$this->RtkType, $body, 1, 0, $MsgID);
        });
        \Channel\Client::on('SendCommand', function($body) use($guid)
        {   
            $rec = $this->MQTT->publish_sync(self::TOPICS_SEND.$guid.'/'.$this->RtkType, json_encode($body,JSON_UNESCAPED_UNICODE), 2, 0);
        });
        //远程控制台返回
        \Channel\Client::on('remote_terminal_send', function($body) use($guid)
        {   
            $rec = $this->MQTT->publish_sync(self::TOPICS_SEND.$guid.'/terminal', $body, 1, 0);
        });   
    
        //添加消息回调
        \MCU\Timer::add(0.5, function() use($topics)
        {
            try {
                $this->MQTT->keepalive();
                $this->MQTT->handle_message();
            } catch (Exception\NetworkError $e) {
                Cache::set('sys:command:connected',0);
                $this->MQTT->reconnect();
                $this->MQTT->subscribe($topics);
                Cache::set('sys:command:connected',1);
            } catch (\Exception $e) {
                Cache::set('sys:command:connected',0);
                //throw $e;
            }
        }); 
    }

    public function connectMqtt($server_url,$keep_alive){
        try{
            $this->MQTT = new MQTT($server_url);
            $context = stream_context_create();
            $this->MQTT->setSocketContext($context);
            //$this->MQTT->setAuth($configs['rdc']['type'], $configs['rdc']['token']);
            $this->MQTT->setKeepalive($keep_alive);
            $connected = $this->MQTT->connect();  
        }catch (Exception $e) { 
            return false;
        }
        if (!$connected) {
            return false;
        }
        return true;
    }
}

/**
 * Class ServerMessages
 */
class ServerMessages extends MessageHandler
{

    private $RtkTopic;
    
    public function __construct($RtkTopic)
    {
        $this->RtkTopic = $RtkTopic;
    }

    public function publish(MQTT $MQTT, \MQTT\Message\PUBLISH $publish_object)
    {
        $actList = explode('/',$publish_object->getTopic());
        if($actList[3] == 'rtk')
        {
            \Channel\Client::publish('SendRtk', $publish_object->getMessage());
        }
        else if($actList[3] == 'changeTopic')
        {
            $memTopic = Cache::get('rtkTopic');
            if($memTopic!='')
            {
               $MQTT->unsubscribe(['enh/rtk/'.$this->RtkTopic]); 
            }
            $this->RtkTopic = $publish_object->getMessage();
            if($this->RtkTopic!='')
            {
                Cache::set('rtkTopic',$this->RtkTopic);
                $MQTT->subscribe(['enh/rtk/'.$this->RtkTopic=>1]);
                \Channel\Client::publish('SYS.Rtk.changeBase', $this->RtkTopic);

            }     
        }
        else
        {
            $msg = $publish_object->getMessage();
            $msgobj = json_decode($msg,true);
            if(is_null($msgobj)){
                $msgobj = $msg;
            }
            \Channel\Client::publish('SYS.ServerAction.'.$actList[3], $msgobj); //操作
        }
        
    }

    public function puback(MQTT $MQTT, PUBACK $puback_object)
    {
        \Channel\Client::publish('SYS.ProduceData.submit.success', $puback_object->getMsgID());
    }

    public function pubcomp(MQTT $MQTT, PUBCOMP $pubcomp_object)
    {
        \Channel\Client::publish('SYS.ProduceData.submit.success', $puback_object->getMsgID());
    }
}
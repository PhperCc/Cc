<?php
use MCU\Kernel;
use MCU\Sys;
use MCU\Cache;
use \Workerman\Connection\AsyncTcpConnection;


class sys_RtkAdapter extends Kernel
{
    private $status = [
        'local_connected' => 0,
        'local_connect_count' => 0,
        'packages' => 0,
        'bytes' => 0,
        'last_time' => 0,
    ];

    public function start()
    {
        //发送服务连接
        $mode = Sys::get_config('rtkMode');
        if($mode == 'moving') //移动站
        {
            $this->_worker->onConnect = function($conn)
            {
                $client_addr = $conn -> getRemoteIp() . ':' . $conn -> getRemotePort();
                $this -> status['client'][$client_add] = 1;
                $this->sync_state($mode);
            };

            $this->_worker->onClose = function($conn)
            {
                $client_addr = $conn -> getRemoteIp() . ':' . $conn -> getRemotePort();
                unset($this -> status['client'][$client_addr]);
                $this->sync_state($mode); 
            };

            \Channel\Client::on("SendRtk", function($rtk)
            {   
                $rtk = base64_decode($rtk);
                $this->status['packages'] ++;
                $this->status['bytes'] += strlen($rtk);
                $this->status['last_time'] = time();
                $this->sync_state($mode);

                foreach($worker->connections as $connection)
                {
                    $connection->send($rtk);
                }
            });
            //上报位置更新基站监听
            \MCU\Timer::add(10, function()
            {
                $gps = Cache::get('Sensor:GPS0:data');
                $rtk = Cache::get('rtkTopic');
                //$rtk = '';
                //if($rtkTopic!= '' && $rtkTopic != false)
                //{
                //    $rtkArr = explode('/',$rtkTopic);
                //    if(isset($rtkArr[3]))
                //    {
                //        $rtk = $rtkArr[3];
                //    }
                //}
                if(isset($gps['lon']))
                {
                    $lon = $gps['lon'];
                    $lat = $gps['lat'];  
                }
                else
                {
                    $lon = 0;
                    $lat = 0;  
                }

                $this->command('rtk',['lon' => $lon, 'lat' => $lat,'rtk' => $rtk]);
            });
        }
        else //基站
        {
            //gpsRTK管道连接
            $client_addr = $this->getConfig('rtk_addr', 'tcp://192.168.0.36:5017');

            $client_conn = new AsyncTcpConnection($client_addr);
            $client_conn -> onMessage = function(AsyncTcpConnection $conn, $data)
            {
                $this -> status['packages'] ++;
                $this -> status['bytes'] += strlen($data);
                
                \Channel\Client::publish('SendRtk', base64_encode($data));
                //5秒发送基站心跳数据
                if((time() - $this->status['last_time'])>5)
                {
                    $this -> status['last_time'] = time();
                    $gps_data = Cache::set('Sensor:GPS0:data');
                    if(isset($gps_data['lon']) && isset($gps_data['lat']))
                    {
                        \Channel\Client::publish('SendData',['lon'=>$gps_data['lon'],'lat'=>$gps_data['lat']]);  
                    }
                }
            };

            $client_conn -> onConnect = function(AsyncTcpConnection $conn) {
                $this -> status['local_connected'] = 1;
                $this -> status['local_connect_count'] ++;
                $this->sync_state($mode);
            };

            $client_conn -> onClose = function(AsyncTcpConnection $conn) {
                $this -> status['local_connected'] = 0;
                $this->sync_state();
                $conn -> reConnect(2);
            };
            $client_conn -> connect($mode);
            //上报位置
            \MCU\Timer::add(10, function()
            {
                
                $gps = Cache::get('Sensor:GPS0:data');
                if(isset($gps['lon']))
                {
                    $lon = $gps['lon'];
                    $lat = $gps['lat'];
                    $this->send(['lon' => $lon, 'lat' => $lat],'SendData');  
                }
                
            });
        }
    }

    private function sync_state($mode){
        Cache::set('Sensor:rtk'.$mode.':status', $this->status);
    }
}

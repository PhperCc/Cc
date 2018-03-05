<?php
/**
 * 拨号操作
 */
use MCU\Kernel;
use MCU\Cache;
use MCU\Sys;
use MCU\Port\ComPort;

class sys_Dialup extends Kernel
{
    /**
     * @var
     */
    private $port;
    /**
     * @var int
     */
    private $dialup_count = 0;
    /**
     * @var array
     */
    private $status_info = [];


    /**
     * 启动服务
     */
    public function start()
    {
        Cache::set("sys:dialup:state", 0);
        $dio_name = $this->getConfig('dio_name', '/dev/ttyUSB2');
        $dio_options = $this->getConfig('dio_options', ['baud' => 115200, 'bits' => 8, 'stop'  => 1, 'parity' => 0]);
        $redial_interval = $this->getConfig('redial_interval', 10);

        $dio_options['name'] = $dio_name;
        $this->port = new ComPort($dio_options);

        $this->device_init(); //打开网卡
        $this->dial_up();
        // 监测拨号状态
        MCU\Timer::add(5, function(){
            $network_info = get_network_info('usb0');
            $this->status_info["network"] = $network_info;
            $this->set_status_info();

            // 如果没有MAC地址， 设备重新初始化
            if(empty($network_info['mac'])) $this -> device_init();
            // 如果未分配到IP地址， 重新拨号
            elseif(empty($network_info['ip']))
            {
                $this->dial_up();
            }
            else
            {
                $this->set_status_info('stage', '拨号成功');
            }
        });

        // 刷新网络状态
        $this -> _net_status_refresh();
        MCU\Timer::add($redial_interval, function(){
            $this -> _net_status_refresh();
        });
    }

    /**
     *初始化， 设置和连接硬件
     */
    private function device_init()
    {
        $this->port->send('E0'); // 去除回显

        $this -> set_status_info("device", [
            "name" => $this->port->parse_return($this->port->send('+CGMI')), // 查询模块厂商标识
            "imei" => $this->port->parse_return($this->port->send('+CGSN')), // 查询IMEI
        ]);

        $this->port->send('+CREG=2'); // 设置 CREG 返回值模式为完全模式
        // $this->port->send('+CGREG=2'); // 设置 CGREG 返回值模式为完全模式
        // $this->port->send('+CEREG=2'); // 设置 CEREG 返回值模式为完全模式

        // $this->port->send('+MODODR=5');   // 设置搜网模式, 2: 自动， 4G-3G-2G， 3: GSM ONLY, 4: GSM优先, 5: LTE ONLY
        // $this->port->send('+CGDCONT=1,"IPV4V6","CMNET"');   // 设置移动APN
        
    }

    /**
     * 拨号
     */
    private function dial_up()
    {
        Cache::set('sys:dialup:state', 0);
        $this->set_status_info('stage', $this -> dialup_count == 0 ? '正在拨号...' : '正在重新拨号...');
        $this->port->send('+MODODR=2', 2);   // 设置搜网模式, 2: 自动， 4G-3G-2G， 3: GSM ONLY, 4: GSM优先, 5: LTE ONLY
        $this->port->send('+CGDCONT=1,"IPV4V6","CMNET"', 2);   // 设置移动APN
        $return = $this->port->send('$QCRMCALL=1,1,1,2,1', 3); // 拨号
        // 拨号后返回内容(^代表\r\n):
        // ^^DATACONNECT^^$QCRMCALL: 1, V4^^OK^^M   // 拨号成功返回
        // ^^DATACONNECT^^OK^^M                     // 拨号成功返回
        // ^^DATACONNECT^^+CME ERROR: unknown^      // 拨号失败返回
        // ^^DATACONNECT^   // 另一种返回， 貌似也是拨号失败
        //
        // 未插卡返回^ERROR^
        $line_return = str_replace("\r\n", "^", $return);
        $this->set_status_info("stage", "拨号结果: $line_return");
        $this->dialup_count ++;   // 拨号次数累加

        $success_line_return = ['^OK^', '^$QCRMCALL: 1, V4^^OK^', '^^DATACONNECT^^$QCRMCALL: 1, V4^^OK^'];
        if(!in_array($line_return, $success_line_return))
        {
            $this->set_status_info("stage", "拨号失败({$this -> dialup_count}): $line_return");
            $this->log("dial up failed({$this -> dialup_count}): $line_return");
            return;
        }

        $this->set_status_info('stage', '正在获取IP地址 ...');
        Sys::exec('udhcpc -i usb0 -s /usr/share/udhcp/simple.script', $udhcpc);
        $this->log("udhcpc: " . var_export($udhcpc, true));
        $this->set_status_info("stage", "拨号成功");
        Cache::set('sys:dialup:state', 1);
        Channel\Client::publish('SYS.Connected','Dialup ok');
    }

    /**
     * 刷新网络状态
     */
    private function _net_status_refresh()
    {
        $statusAT = [];
        $statusAT['CSQ']         = $this->port->send('+CSQ');          // 查看网络信号质量
        $statusAT['DATASTATUS']  = $this->port->send('+DATASTATUS');   // 数据状态
        $statusAT['CREG']        = $this->port->send('+CREG?');        // 网络注册状态
        // $statusAT['CGREG']    = $this->port->send('+CGREG?');       // 2G网络注册状态
        $statusAT['CEREG']       = $this->port->send('+CEREG?');       // LTE网络注册状态
        $statusAT['PSRAT']       = $this->port->send('+PSRAT');        // 查询当前注册的网络

        $status = [];
        // 信号质量: 应在 10 到 31 之间，数值越大表明信号质量越好(99代表 unknown)
        $status['rssi'] = $this->port->parse_return($statusAT['CSQ'], 0);

        // 真正的CSQ值, 单位是 dBm， 必然是负值
        $status['dbm'] = $status['rssi'] * 2 - 113;

        // 信号格数
        $status['signal_quality'] = 0;
        if($status['dbm'] >= 0)
        {
            $status['dbm'] = 0;
            $status['signal_quality'] = 0;
        }
        elseif($status['dbm'] > -85) $status['signal_quality'] = 5;
        elseif($status['dbm'] > -90) $status['signal_quality'] = 4;
        elseif($status['dbm'] > -95) $status['signal_quality'] = 3;
        elseif($status['dbm'] > -100) $status['signal_quality'] = 2;
        elseif($status['dbm'] > -105) $status['signal_quality'] = 1;

        // 误码率: 值在 0 到 99 之间(99代表 unknown)， 否则应检查天线或 SIM 卡是否正确安装
        $status['bit_error_rate'] = $this->port->parse_return($statusAT["CSQ"], 1);
        // 0 未找到运营商网络
        // 1 已注册到本地网络,
        // 2 已找到运营商但未注册网络,
        // 3 注册被拒绝,
        // 4 未知的数据, 
        // 5 注册在漫游状态.
        $status['reg_state']    = $this->port->parse_return($statusAT['CREG'], 1);
        // 网络区域编号
        $status['net_area']     = $this->port->parse_return($statusAT['CREG'], 2);
        // 网络小区域编号
        $status['net_sub_area'] = $this->port->parse_return($statusAT['CREG'], 3);
        // 数据是否已经连通, 0 或 1
        $status['data_status']  = $this->port->parse_return($statusAT['DATASTATUS'], 0);
        // 当前注册的网络名称， 对于中国移动网络， 通常是 TDD LTE 或者 EDGE
        $status['net_name']     = $this->port->parse_return($statusAT['PSRAT'], 0);
        $this->status_info['status'] = $status;
        MCU\Sys::record_report_status('mobile_signal', $status);
    }

    /**
     * 将状态写入内存
     */
    private function set_status_info($key = '', $val = '')
    {
        if($key !== '' && $val !== '')
        {
            $this->status_info[$key] = $val;   
        }

        Cache::set("sys:dialup", $this->status_info);
    }
}
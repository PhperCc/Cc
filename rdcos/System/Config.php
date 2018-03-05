<?php
//默认内核基础配置文件
return [
    'guid' => 'default',
    'version' => '2.0.0',
    'product' => 'Simple',
    'serverLink'=>'tcp://116.62.31.23:1883', //cloud2.enhrdc.net
    'rtkMode'=>'moving',//base  moving
    'rtkTopic' => '',//rtk监听主题
    'desc' => '内核模块',
    'model' => [
    	'Dialup' => [
            'enabled' => false,
            'desc' => '移动网络拨号及监控',
            'redial_interval' => 10,    // 断线后重拨间隔， 单位秒
			'dio_name' => '/dev/ttyUSB2',
            'dio_options' => ['baud' => 115200, 'bits' => 8, 'stop'  => 1, 'parity' => 0]
        ],
        'ServerConnection' => [
			'enabled' => false,
            'desc' => '服务器MQTT服务链接',
			'keep_alive' => 30,    // 心跳包频率 单位秒
		],
		'Monitors' => [
			'enabled' => true,
            'desc' => '对本地网络、磁盘空间、资源占用等实时监测,系统命令执行',
            'log_max_size' => 0.2,   // 日志文件最大尺寸， 单位M
            'log_min_size' => 0.001,   // 缩减到的尺寸， 单位M， 0为直接删除
            'led_port' =>'46,47',
			'log_paths' => [     // 需要监测的目录
                '/home/system/data/log'
            ],
            'ping_ip' => '', //ping主机ip
		],
		'ApiServer' => [
            'enabled' => true,
            'protocol' => 'websocket://0.0.0.0:1228',
            'http_pro' => 'http://0.0.0.0:1226',
            'desc' => '提供缓存API接口功能',
        ],
		'Terminal' => [
			'enabled' => true,
            'desc' => '提供远程终端服务',
            'protocol' => 'websocket://0.0.0.0:1158',
		],
        'ProduceDataSubmit' => [
            'enabled' => false,
            'desc' => '生产数据上传服务',
            'sleep_interval' => 2,
            'read_count' => 50,
        ],
        'RtkAdapter' => [
            'enabled' => false,
            'desc' => 'RTK数据管道',
            'protocol' => 'tcp://0.0.0.0:5017',
            'rtk_addr' => 'tcp://192.168.0.26:5017',
        ],
    ],
    'sensor' => [
        'ShutdownKey' => [
            'enabled' => false,
            'desc' => '关机按钮监控',
            'gpio_number' => 72,
            'power_off_wait_second' => 3,
        ],
        'Power' => [
            'enabled' => false,
            'desc' => '电源管理',
        ],
        'GpsTrimble' => [
              'enabled' => false,
              'desc' => 'TCP方式读取进口GPS解析并上传',
              'url0' => 'tcp://192.168.0.226:5018',
              'url1' => 'tcp://192.168.0.227:5018',
        ],
    ],
];

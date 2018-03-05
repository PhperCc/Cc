<?php
//默认产品配置

return [
    'desc' => '协同中心',
    'model' => [
        'Main' => [
            'enabled' => true,
            'desc' => '车辆数据分析',
            'run_interval' => 1,
            'data_analysis_num' => 50,
            'process_ping' => 600,
        ],
        'db_maintain' => [
            'enabled' => false,
            'desc' => '数据库维护服务',
        ],
        'power_management' => [
            'enabled' => false,
            'desc' => '电源管理',
        ],
    ],
    'sensor' => [ 
        'PulseRange' => [
            'enabled' => false,
            'desc' => '高频脉冲测距数据采集',
            'port' => '/dev/ttyO3',
            'baud' => 9600,
            'interval'=> 1,
            'addr' => 0x02,
        ],
    ]
];

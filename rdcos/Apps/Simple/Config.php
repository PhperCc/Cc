<?php
//默认产品配置

return [
    'desc' => 'Simple',
    'model' => [
        'Main' => [
            'enabled' => true,
            'desc' => '主要业务',
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

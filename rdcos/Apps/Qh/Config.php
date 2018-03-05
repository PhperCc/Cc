<?php
//默认强夯基础配置文件

return [
    'desc' => '强夯机',
    'model' => [
        'Main' => [
            'enabled' => true,
            'desc' => '计算强夯结果数据',
            'run_interval' => 1,
            'QHconfig' => [
                'high_min' => 4,
                'gps_dist_min' => 2,        // 换坑间距阈值
                'jiaozhun_type' => 1,       // 1: 使用拉线编码器校准， 2： 使用比率校准
                'dist_rate' => 0.04,        // 校准比率， 编码器转动一圈对应的夯锤高程变化量
                'points_spac' => 4,         // 夯点间距
                'points_arrangement' => 2,  // 排点形状 1:正方形, 2:正三角形
                'radius' => 1.5,            // 夯锤直径
                'car_type' => 2,            // 1:不带龙门架, 2:带龙门架
                'hammer_type' => 1,         // 1:点夯, 2:强夯置换, 3:满夯
                'param' => [
                    'frontPointAtLeft' => false, 
                    'frontPointX' => 0, 
                    'frontPointY' => 0, 
                    'backPointAtLeft' => true, 
                    'backPointX' => 0.6, 
                    'backPointY' => 3,
                    'backPointHi' => 6,     // 副天线安装距地面高度
                ],
            ],
        ],
       
    ],
    'sensor' => [
        'ReadEc' => [
            'enabled' => true,
            'desc' => '滑轮编码器数据采集',
            'protocol' => 'websocket://0.0.0.0:5001',
            'port' => '/dev/ttyO3',
            'baud' => 19200,
            'addr' => '00',
            'interval' => 0.2,
            'resetPo'   => 'D00LM=',
            'aspect'    => false,
            'enable_correct' => true,  // 是否允许对读值进行修正， 当发现明显跳点时， 进行偏移量修正
            'max_speed' => 100,  // 最大转速， 圈每秒
        ],
    ]
];
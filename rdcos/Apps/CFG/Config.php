<?php
return  [
  'desc' => 'CFG桩机',
  'model' => 
   [
    'Main' => 
     [
      'enabled' => true,
      'desc' => '主要业务',
      'interval'=>1,
    ],
    "GnssServer" => [
        "enabled" => true,
        "desc" => "坐标转换计算",
        "baud" => 38400,
        "gnss_url" => "tcp://192.168.0.225:5019",
        "L0" => 109.00,
        "H0" => 390,
        "DX" => 112.441842,
        "DY" => 248.877852,
        "DZ" => -222.295697,
        "WX" => -0.0000468479,
        "WY" => 0.0000036018,
        "WZ" => -0.0000226568,
        "K" => -0.000005231748,
        "run_interval" => 1,
    ],
    "CFGCalc" => [
        "enabled" => true,
        "desc" => "计算CFG桩数据",
        "run_interval" => 1,
        "CFGconfig" =>[
            "min_current" => 50,
            "min_piledepth" => 5,
            "start_min_depth" => 0.3,
            "min_currentdepth" => 0.5,
        ],
    ],
  ],
  'sensor' => 
   [
    'ReadAn' => 
     [
      'enabled' => true,
      'desc' => '电流互感器',
      'name' => '/dev/ttyO3',
      'baud' => 9600,
      'bits' => 8,
      'stop' => 1,
      'parity' => 0,
      'addr' => 2,
      'format' => 1,
      'interval' => 0.5,
    ],
    'Pressure' => 
     [
      'enabled' => false,
      'desc' => '压力变送器读取数据',
      'name' => '/dev/ttyO3',
      'baud' => 9600,
      'bits' => 8,
      'stop' => 1,
      'parity' => 0,
      'addr' => 1,
      'format' => 1,
      'interval' => 1,
    ],
    'DipAngle' => 
     [
      'enabled' => false,
      'desc' => '读取倾角传感器数据',
      'name' => '/dev/ttyO3',
      'baud' => 9600,
      'bits' => 8,
      'stop' => 1,
      'parity' => 0,
      'addr' => 4,
      'format' => 2,
      'interval' => 1,
    ],
    'DigitalTube' => 
     [
      'enabled' => true,
      'desc' => '人机交互设备',
      'name' => '/dev/ttyO3',
      'baud' => 9600,
      'bits' => 8,
      'stop' => 1,
      'parity' => 0,
      'addr' => 3,
      'format' => 1,
      'interval' => 1,
    ],
  ],
];
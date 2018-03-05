<?php
//默认振碾机基础配置文件
return [
    "desc" => "振碾机",
    "model" => [
        'Main'=>[
            "enabled" => true,
            "desc" => "为平板界面组织数据和数据提交",
            "run_interval" => 1,
            "car_width" => 2.44,
            "interval" => 2,
        ]   
    ],
	"sensor" => [
		"DensityCan" => [
            "enabled" => true,
            "desc" => "密实度采集",
			"can_device" => "can0",
            "read_interval" => 0.1,
            "revise" => 1,// 是否修正数据
            "zn_type" => 0,//震碾类型: 0:<=30T    1：32T    2：36T
            "force_type"=> 0,//激振力计算方式
			"min_amp" => 300,	// 有效振幅最小值
			"max_amp"=> 600,	//  最大振幅值
            "ratio_freq"=> 0.8, //车辆频率对应系数
            "ratio_ecv" => 8, //ecv放大系数
            "max_force" => 425, //最大激振力
            "max_hz" => 28, //最大频率
        ],
	]
];
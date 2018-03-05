<?php
use MCU\Plugin\IPlugin;
include_once($appRoot.STARTER_NAME.'/Plugins/Yuantu.php');

class pluginQhYuantu extends IPlugin
{
	protected $submitHooks = true;

    public function onSubmit($data)
    {
        if(!is_array($data)) return self::log("提交的数据必须为数组", false);
        if(!array_key_exists("gpst", $data))  return self::log("数据必须包含 gpst 字段，夯击时间", false);
        if(!array_key_exists("lon", $data))  return self::log("数据必须包含 lon 字段，夯点经度", false);
        if(!array_key_exists("lat", $data))  return self::log("数据必须包含 lat 字段，夯点纬度", false);
        if(!array_key_exists("hit_index", $data))  return self::log("数据必须包含 hit_index 字段，夯击次数", false);
        if(!array_key_exists("work_point_id", $data))  return self::log("数据必须包含 work_point_id 字段，夯点编号", false);
        if(!array_key_exists("hammer_type", $data))  return self::log("数据必须包含 hammer_type 字段，夯点编号", false);
        if(!array_key_exists("plon", $data))  return self::log("数据必须包含 plon 字段，车辆中心经度", false);
        if(!array_key_exists("plat", $data))  return self::log("数据必须包含 plat 字段，车辆中心纬度", false);
        if(!array_key_exists("phi", $data))  return self::log("数据必须包含 phi 字段，地面椭球基准高程", false);
        if(!array_key_exists("high", $data))  return self::log("数据必须包含 high 字段，夯锤落距", false);

        $submit = [
            "lon" => $data["lon"],
            "lat" => $data["lat"],
            "x" => $data["plon"],
            "y" => $data["plat"],
            "number" => $data["hit_index"],
            "holeID" => $data["work_point_id"],
            "type" => $data["hammer_type"],
            "height" => $data["phi"],
            "drop" => $data["high"],
            "tamp_time" => $data["gpst"],
            "gf" => "03",
        ];

        $result = Yuantu::submit($submit, "GetDynamicCompactionData", $info);
        return $result;
    }

    public function getVer()
    {
       return '1.0.0'; 
    }
}
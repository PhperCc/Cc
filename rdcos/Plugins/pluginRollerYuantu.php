<?php
use MCU\Plugin\IPlugin;
include_once($appRoot.STARTER_NAME.'/Plugins/Yuantu.php');

class pluginRollerYuantu extends IPlugin
{
	protected $submitHooks = true;

    public function onSubmit($data)
    {
        if(!is_array($data)) return self::log("提交的数据必须为数组", false);
        if(!array_key_exists("lon", $data))  return self::log("数据必须包含 lon 字段，夯点经度", false);
        if(!array_key_exists("lat", $data))  return self::log("数据必须包含 lat 字段，夯点纬度", false);
        if(!array_key_exists("elevation", $data))  return self::log("数据必须包含 elevation 字段，椭球高程", false);
        if(!array_key_exists("gpst", $data))  return self::log("数据必须包含 gpst 字段，GPS时间", false);
        if(!array_key_exists("drct", $data))  return self::log("数据必须包含 drct 字段，位移方向", false);
        if(!array_key_exists("speed", $data))  return self::log("数据必须包含 speed 字段，位移速度", false);
        if(!array_key_exists("ecv", $data))  return self::log("数据必须包含 ecv 字段，密实度值", false);
        if(!array_key_exists("freq", $data))  return self::log("数据必须包含 freq 字段，振频", false);
        if(!array_key_exists("amp", $data))  return self::log("数据必须包含 amp 字段，振幅", false);

        $submit = [
            "lon" => $data["lon"],
            "lat" => $data["lat"],
            "height" => $data["elevation"],
            "ew" => "e",
            "ns" => "n",
            "gps_time" => $data["gpst"],
            "gps_drct" => $data["drct"],
            "speed" => $data["speed"],
            "gps_state" => $data["quality"],
            "compacrate" => $data["ecv"],   // 密实度    
            "frequency" => $data["freq"],   // 振频
            "amplitude" => $data["amp"],    // 振幅
            "force"=>$data["force"],//激振力
            "zn_type"=>$data["zn_type"],//压路机类型
            "remark" => "ENH",
            "gf" => "02",
        ];
        $result = true;
        //$result = Yuantu::submit($submit, "GetDynamicCompactionData", $info);
        return $result;
    }

    public function getVer()
    {
       return '1.0.0'; 
    }
}
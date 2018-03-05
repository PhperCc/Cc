<?php
use MCU\Cache;
/**
 * CenterGetData获取数据
 */
class api_WebData extends MCU\ApiHelper\ApiBase
{
    /**
     * 获取当前压实工艺
     *@data string 车辆ID
     */
    public function GetTechnology($params)
    {
        if($params['data'] !== false && !empty($params['data']) )
        {
            $reginfo_data  = cache::get('reginfoData');
            if($reginfo_data == null || $reginfo_data == false)
            {
                return R(false,"I don't have your car info",$params['data']);
            }

            $process = '';
            foreach($reginfo_data as $v)
            {
                //carid 相等 更新缓存的最后通讯时间及工艺
                if($v['car_id'] = $params)
                {
                    $process = $v['process'];
                }
            }
            return R(true,'ok',$process);
        }
        else 
        {
            return R(false,'error');
        }

    }
    /**
     * 获取所有工艺信息
     * @data str 'GetAllProcessInfo'
     * 
     */
    // private static $registrt_car;

    public function GetAllProcessInfo($params)
    {
        if($params['data'] !== false && $params['data'] == 'GetAllProcessInfo')
        {
            $process_info = Cache::get("AllProcessInfo");
            if($process_info == null || $process_info == false)
            {
                return R(false,"I don't have AllProcessInfo");
            }
            return R(true,'ok',$process_info);
        }
        else 
        {
            return R(false,'error');
        }
    }  
    /**
     * 获取所有车辆详情
     * @data string 'GetAllCarInfo'
     */
    public function GetAllCarInfo($params)
    {
        if($params['data'] !== false && !empty($params['data']))
        {
            $AllCarInfo = Cache::get('reginfoData');
            if($AllCarInfo == null || $AllCarInfo == false)
            {
                return R(false,"I don't have  car info",$params['data']);
            }
            return R(true,"ok",$AllCarInfo);
        }
        else 
        {
            return R(false,'error');
        }
    }
    /**
     * 获取车辆最新作业数据（平板）
     * @data string 车辆ID
     */  
    public function GetNewlyData($params)
    {
        if($params['data'] !== false)
        {
            $num = Cache::llen($params['data']);
            if($num > 50) $num = 50;
            if($num == null || $num == 0)
            {
                return R(false,"I don't have your car data",$params['data']);
            }
            $reault = [];
            for($i=1;$i <= $num;$i++)
            {
                $row = Cache::lpop($params['data']);
                $reault[] = json_decode($row,true);
            }
            return R(true,"ok",$reault);
        }
        else 
        {
            return R(false,'error');
        }
    }
}
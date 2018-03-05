<?php
/**
 * 协同中心数据上传
 */
use MCU\Cache;
use MCU\DbOperator;
define('WORK_DATA',6676);//工作数据
define('REGISTER_INFO',6677);//注册信息

class api_CenterUpload extends MCU\ApiHelper\ApiBase
{
    /**
     * 所有工艺信息上传
     * @data JSON 上传数据
     * 
     */
    // private static $registrt_car;

    public function SendAllProcessInfo($params)
    {
        if($params['data'] !== false)
        {
            Cache::set("AllProcessInfo",$params['data']);
            return R(true,'ok');
        }
        else 
        {
            return R(false,'error');
        }
    }
    /**
     * 实时数据上传及注册车辆
     * @data JSON 上传数据
     * 
     */
    // private static $registrt_car;

    public function send_data($params)
    {

        $info = json_decode($params["data"],true);
        Cache::set('info',$info);
        if($info == NULL || $info == FALSE) return R(false,'The upload parameters do not match',$params["data"]);
        $final_info = $this -> CheckInfo($info); 
        if($final_info['result'] != true) return R($final_info['result'],$final_info['info']);
       
        //获取已注册所有车辆信息
        // \MCU\DbOperator::setConfig(DbClient::$config);
        // $site_reg_info = DbClient::M('site_reg_info');
        // $reginfo_data =  $site_reg_info -> reset()  ->select();
        // unset($site_reg_info);
        $config =[
            'dsn' => 'mysql:host=127.0.0.1:3306;dbname=rdc_site',
            'user'=> 'root',
            'password' => 'clsdir'
        ];

       
        

        if($info['data_type'] == REGISTER_INFO)
        {
            //注册信息从数据库获取车辆列表
            \MCU\DbOperator::setConfig($config);
            $site_reg_info = new \MCU\DbOperator('site_reg_info');
            $reginfo_data =  $site_reg_info -> reset()  ->select();     
            if($reginfo_data == NULL) $reginfo_data =[];

            //释放不需要的字段
            unset($info['data_type']);

            //验证是否已经注册过
            $reg_check = $this -> CheckCar($reginfo_data,$info['car_id']);
            if($reg_check == true)
            {
                Cache::set("reginfoData",$reginfo_data);
                //启用当前车辆
                return R(true,'The car is on');
            }
            
            //重组车辆信息元素
            $final_info = [
                'client_ip'    => $info['client_ip'],
                'car_name'     => $info['car_name'],
                'car_id'       => $info['car_id'],
                'car_type'     => $info['car_type'],
                'reg_time'	   => $info['reg_time'],
                'Premigration' => $info['Premigration'],
                'car_width'    => $info['car_width'],
                'car_length'   => $info['car_length'],
                'last_conn_time' => 0,
                'work_level'   => 0,
                'process'      => 0,
                'data_flow'    => 0,
                'enabled'      => 1
            ];
            array_push($reginfo_data,$final_info);
            Cache::set("reginfoData",$reginfo_data);

            //将注册信息写入车辆注册表
            $site_reg_info ->reset()-> insert($final_info);
            Cache::set("get_last_error",date("Y-m-d H:i:s",time()).":".$site_reg_info ->get_last_error());
            Cache::set("get_last_query",date("Y-m-d H:i:s",time()).":".json_encode($site_reg_info ->get_last_query()));
            //释放数据库连接
            unset($site_reg_info);
            return R(true,'The car reg');
        }

        if($info['data_type'] == WORK_DATA)
        {        
            //释放数据库连接
            unset($site_reg_info);
            //工作数据 从Cache获取车辆列表
            $reginfo_data = Cache::get("reginfoData");
            
            $reg_check = $this -> CheckCar($reginfo_data,$info['data'][0]['car_id']);
            
            if($reg_check == false) return R(false,'The car not register or off',$info['data'][0]['car_id']);
        }
        
        // $DecomposeData = $this -> DecomposeData($final_info);
       
        foreach($info['data'] as $k => $v)
        {
            Cache::rpush('Mainlist',json_encode($v));
        }
        return R(true,'data ok');
    }  
    //验证车辆是否注册
    private function CheckCar($data,$car_id)
    {
        $check = false;
        foreach($data as $k => $v)
        { 
           
            if($v['car_id'] == $car_id)
            {
                $check = true;
                break;
            }
        }
        return $check;
    }
  
    //验证数据信息
    private function CheckInfo($params)
    {
        
        if(!array_key_exists('data_type',$params)) return R(false,"Data Need DataType");
        switch($params['data_type'])
        {
            case WORK_DATA:
            if(!array_key_exists('data',$params) && is_array($params['data'])) return R(false,"Data Need data");
            foreach($params['data'] as $k =>$v)
            {
                if(!array_key_exists('car_type',$v) && !empty($v['car_type']) && is_numeric($v['car_type'])) return R(false,"Data Need car_type");
                if(!array_key_exists('date_time',$v) && !empty($v['date_time']) ) return R(false,"Data Need date_time");
                if(!array_key_exists('car_id',$v) && !empty($v['car_id'])) return R(false,"Data Need CarID");
                if(!array_key_exists('center',$v) && is_array($v['center']) && !empty($v['center'])) return R(false,"Data Need center");
                if(!array_key_exists('speed',$v) && !empty($v['speed'])) return R(false,"Data Need speed");
                if(!array_key_exists('temp',$v) && !empty($v['temp'])) return R(false,"Data Need temp");
                if(!array_key_exists('drct',$v) && !empty($v['drct'])) return R(false,"Data Need drct");
                if(!array_key_exists('force',$v) ) return R(false,"Data Need force");
                if(!array_key_exists('gps_time',$v) && !empty($v['gps_time'])) return R(false,"Data Need gps_time");
                if(!array_key_exists('height',$v) && !empty($v['height'])) return R(false,"Data Need height");
                if(!array_key_exists('site_x',$v) && !empty($v['site_x'])) return R(false,"Data Need site_x");
                if(!array_key_exists('site_y',$v) && !empty($v['site_y'])) return R(false,"Data Need site_y");
                if(!array_key_exists('lon',$v) && !empty($v['lon'])) return R(false,"Data Need lon");
                if(!array_key_exists('lat',$v) && !empty($v['lat'])) return R(false,"Data Need lat");
                if(!array_key_exists('transport_car_id',$v)) return R(false,"Data Need transport_car_id");
                if(!array_key_exists('gps_left_width',$v) && !empty($v['gps_left_width'])) return R(false,"Data Need gps_left_width");
                if(!array_key_exists('gps_right_width',$v) && !empty($v['gps_right_width'])) return R(false,"Data Need gps_right_width");
                if(!array_key_exists('grids',$v) && !empty($v['grids'])) return R(false,"Data Need grid");
            }
            
            break;
            case REGISTER_INFO:
            if(!array_key_exists('reg_time',$params)&& !empty($v['reg_time'])) return R(false,"Data Need reg_time");
            if(!array_key_exists('car_id',$params)&& !empty($v['car_id'])) return R(false,"Data Need CarID");
            if(!array_key_exists('client_ip',$params)&& !empty($v['client_ip'])) return R(false,"Data Need client_ip");
            if(!array_key_exists('car_name',$params)) return R(false,"Data Need car_name");
            if(!array_key_exists('car_type',$params)&& !empty($v['car_type'])) return R(false,"Data Need car_type");
            if(!array_key_exists('Premigration',$params)) return R(false,"Data Need Premigration");
            if(!array_key_exists('car_width',$params)&& !empty($v['car_width'])) return R(false,"Data Need car_width");
            if(!array_key_exists('car_length',$params)&& !empty($v['car_length'])) return R(false,"Data Need car_length");
            break;
            default:
            return R(false,"The upload data do not match");
            break;
        }
        return R(true,'check_ok');
    }
  
}

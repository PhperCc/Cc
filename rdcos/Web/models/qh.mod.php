<?php
use MCU\Cache;
use MCU\ApiHelper\ApiClient;

class mod_qh extends WebModule
{
    private $QHconfig;
    public function __init()
    {
        $this->title = "强夯机车载平板";
        $this->powers = array ();
        $this->ignore_main[] = "work";
        $this->ignore_main[] = "ipad";
        $this->ignore_main[] = "status";
        $this->hide_menu[] = 'jiaozhun';
        $this->hide_menu[] = 'jiaozhun2';

        $this->QHconfig = Cache::get("Svc:QH:config");

    }

    /**
    * 获取状态
    */
    public function ajax_getDevStatus()
    {
        $master = Cache::get("Sensor:GPS0:data");
        $slave  = Cache::get("Sensor:GPS1:data");
        $EC     = Cache::get("Sensor:Encoder:data");
        //主天线
        if($master['quality']==5) $master['quality'] = 3;
        if(empty($master['quality']))
            $data['GNSS1'] = 0;
        else
            $data['GNSS1'] = $master['quality'];
        //从天线
        if(empty($slave['quality']))
            $data['GNSS2'] = 0;
        else
            $data['GNSS2'] = $slave['quality'];

        //编码器
        if(empty($EC))
            $data['EC'] = 0;
        else
            $data['EC'] = 1;

        $net = Cache::get("sys:dialup");
        $data['NET'] = array_key_exists('status', $net) ? $net['status']['data_status'] : 0;
        $data['CSQ'] = array_key_exists('status', $net) ? $net['status']['signal_quality'] : 0;
        json_return($data);
    }

    /**
    * 获取夯击数据
    */
    public function ajax_getdata()
    {
        // 夯击点实时坐标
        $gnss = Cache::get("Svc:QH:Positions");
        $gnss['radius'] = $this -> QHconfig['radius'];
        // 转轴坐标
        $final_list = Cache::get("work_point_list");
        $hit_list = Cache::get("hit_list");

        /*计算沉降*/

        $last_chenjiang = 0;
        $final_list_count = count($final_list);
        $last_work_point = $final_list[$final_list_count-1];
        $sum_chenjiang = $last_work_point['chenjiang_total'];
        if(!empty(Cache::get("SVC:QH:hit:chenjiang")))$last_chenjiang = Cache::get("SVC:QH:hit:chenjiang");

        /*end*/
        if(empty($gnss['lon']) || empty($gnss['lat']) || empty($gnss['plon']) || empty($gnss['plat']))
            $result = 0;
        else
            $result = 1;
        $data = array(
            "result"=>$result,  //返回数据状态 1表示ok,0表示请求出错
            'data' => array(
                "config"        => $this -> QHconfig,
                "final_list"    => $final_list,//夯点列表
                "hit_list"      => $hit_list,//每一次的夯击列表
                "gnss"          => $gnss,//当前定位信息
                "high"          => Cache::get("high"),//实时拉高
                "last_chenjiang"     => round($last_chenjiang,3),//沉降信息
                "sum_chenjiang"     => round($sum_chenjiang,3),//沉降信息
            )
        );

    	json_return($data);
    }

    public function view_conf(){
        $conf_list = ApiClient::req("ServiceConfig.get", ["module" => "QH", "services" => "QHCalc", "key" => "QHconfig"]);
        $conf_list['aspect'] = ApiClient::req("ServiceConfig.set", ["module" => "QH", "services" => "ReadEc", "key" => "aspect"]);

        $this -> data['QHconfig'] = $conf_list;
        $this -> ignore_main[] = "conf";
    }

    // 车辆参数配置
    public function ajax_conf(){
        $aspect = Http::paramI('aspect', 0);
        $return = ApiClient::request("ServiceConfig.set", ["module" => "QH", "services" => "ReadEc", "key" => "aspect", "val" => $aspect]);
        if($return["result"] == true)
        $return = ApiClient::request("ServiceConfig.set", ["module" => "QH", "services" => "QHCalc", "key" => "QHconfig", "val" => $_POST]);
        if($return["result"] == true) return json_return(1);
        return $this -> show_error("数据保存失败: {$return['info']}");
    }


    public function view_hammer(){
        $this -> data['QHconfig'] = ApiClient::req("ServiceConfig.get", ["module" => "QH", "services" => "QHCalc", "key" => "QHconfig/param"]);
        $this -> ignore_main[] = "hammer";
    }

    // 强夯机天线安装和偏移参数配置
    public function ajax_hammer(){
        return ApiClient::req("ServiceConfig.set", ["module" => "QH", "services" => "QHCalc", "key" => "QHconfig/param", $_POST]);
        if($return["result"] == true) return json_return(1);
        return $this -> show_error("数据保存失败: {$return['info']}");
    }

    /**
    * 获取引导点排布方式
    *
    */
    public function ajax_getArrange()
    {
        // if(type == '-1')json_return(-1);
        $data['lon'] = 104; //引导点基点经度
        $data['lat'] = 30;  //引导点基点纬度
        $data['angle'] = 0; //排布角度方向
        $data['dist'] = 4;  //引导点间距
        $data['pattern'] = 1; //引导点排布方式,pattern:图案
        $data['radius'] = 1.2;//锤径
        json_return($data);
    }

    /**
    * 更新引导点排布方式
    */
    public function ajax_updateArrange()
    {

    }


    /**
    * 获取夯击历史
    */
    public function ajax_get_history_data()
    {
        $data = $this -> get_qh_cvsdata();
        foreach ($data as $k => $v)
        {
            $v['chenjiang'] = round($v['chenjiang'],3);
            $point[$v['work_point_id']]["detail"][] = $v;
        }

        foreach ($point as $k => $v) {
            $point[$k]['count'] = count($v['detail']);
            $point[$k]["id"] = $k;
            $last_time = strtotime($v['detail'][$point[$k]['count']-1]['gpst']);
            $first_time = strtotime($v['detail'][0]['gpst']);

            $costtime = seconds_text($last_time - $first_time);
            $point[$k]['costtime'] = $costtime;

            $cj = '';
            $cjlast = '';
            $cjlast2 = '';
            if(isset($v['detail'][$point[$k]['count']-1]['chenjiang']))$cjlast = $v['detail'][$point[$k]['count']-1]['chenjiang'];
            if(isset($v['detail'][$point[$k]['count']-2]['chenjiang']))$cjlast2 = $v['detail'][$point[$k]['count']-2]['chenjiang'];
            $cj = array_sum(array_column($v['detail'], 'chenjiang'));
            $lj = array_sum(array_column($v['detail'], 'high'));

            $point[$k]['cjsum'] = round($cj,3); //总沉降
            $point[$k]['cjlast'] = round($cjlast,3); //最后沉降
            $point[$k]['cjlast2'] = round($cjlast2,3); //次后沉降
            $point[$k]['ljavg'] = round($lj/$point[$k]['count'],3);//平均落距
        }
        json_return($point);

    }
    
    /**
    * 获取夯击历史点
    */
    public function ajax_get_history_point()
    {
        $data = $this -> get_qh_cvsdata();
        json_return($data);
    }

    /**
    * 获取cvs数据
    */
    public function get_qh_cvsdata()
    {
        $data_save_path = "record/qh_history.csv";
        $history_file_path = MCU\LocalFile::getFilePath($data_save_path);
        $file = fopen($history_file_path,"r");
        $key = fgetcsv($file);
        $k_con = count($key);
        while(! feof($file))
        {
            $csv = fgetcsv($file);
            $count = count($csv);
            if ($count==11) {
                
                if ($k_con==11) {
                    $key = array("gpst","lon","lat","plon","plat","workAngle","phi","circle","microtime","high","chenjiang","work_point_id","hit_index","hammer_type");
                     $key = array("gpst","lon","lat","plon","plat","workAngle","phi","circle","high","chenjiang","work_point_id");
                }
                $list[] = array_combine($key, $csv);
            }elseif ($count>=14) {
                if ($k_con!=14) {
                     $key = array("gpst","lon","lat","plon","plat","workAngle","phi","circle","microtime","high","chenjiang","work_point_id","hit_index","hammer_type");
                }
                $list[] = array_combine($key, $csv);
            }
        }

        fclose($file);
        $i = 1;
        foreach ($list as $k => $v) {
            $v['work_point_id'] = $i;
            if( isset($list[$k+1]) && $list[$k]['work_point_id'] != $list[$k+1]['work_point_id']) $i++;
            $data[] = $v;
        }
        return $data;
   }

    // 工作界面
    public function view_work()
    {

        $qh_config = ApiClient::req("ServiceConfig.get", ['module' => "QH", 'service' => "QHCalc"]);
        $this -> data['QHconf'] = $qh_config['QHconfig'];
    }

    // 用户选择相关内容保存提交
    public function ajax_hammer_pAngle_type()
    {
        $qh_config = MCU\LocalFile::getObject("config/qh/config", []);
        $qh_result = $qh_config['QHconf'];
        if (!empty($qh_result)) {
           $qh_config['QHconf'] = array_merge($qh_config['QHconf'], $_POST);
        }else {
            $qh_config['QHconf'] = $_POST;
        }
        
        if(false === MCU\LocalFile::putObject("config/qh/config", $qh_config)) return $this -> show_error("排点数据保存失败");
        json_return(1);
    }

    // 获取夯击方式 1满夯 2点夯 3置换 \历史排点参数
    public function ajax_get_hammer_type()
    {
        $qh_config = MCU\LocalFile::getObject("config/qh/config", []);
        $qh_config['result'] = array_key_exists("QHconf", $qh_config) ? 1 : 0;
        json_return($qh_config);
    }

    // 高低差校准
    public function view_jiaozhun2()
    {
        $this -> title = "强夯机校准2 - ENH";
    }

    // 拉线编码器校准
    public function view_jiaozhun()
    {
        $this -> title = "强夯机校准 - ENH";
        $file_path = MCU\LocalFile::getFilePath("config/qh/jiaozhun");
        if(!file_exists($file_path)) $file_path  = "/home/system/rdcos/Apps/Qh/Modules/jiaozhun.php";
        $this-> data['file_path'] = encrypt($file_path, "download");
    }

    // 保存拉线编码器校准数据
    public function ajax_jiaozhun_save()
    {
        $json = Http::param("json");
        $records = json_decode($json, true);
        if(!is_array($records)) exit("数据格式错误， 解析失败");

        $list = [];
        foreach($records as $record)
        {
            $key = strval($record["ec"]);
            $val = strval($record["hi"]);
            $list[$key] = $val;
        }
        ksort($list, SORT_NUMERIC);

        if(false === MCU\LocalFile::putObject("config/qh/jiaozhun", $list)) exit("配置文件保存失败");
        echo "ok";
    }

    // 下载
     public function view_download()
    {
        $file_path = Http::paramS("file_path");
        $show_name = Http::paramS("show_name");

        if(empty($file_path)) return;

        $file_path = decrypt($file_path, "download");
        if(empty($file_path)) return;
        if(!file_exists($file_path)) exit("文件不存在");

        ob_clean();
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($file_path));

        if(empty($show_name))
        {
            $show_name = basename($file_path);
            $show_name = str_ireplace(" ", "_", $show_name);
        }
        Header("Content-Disposition: attachment; filename=$show_name");

        $file = fopen($file_path, "r");
        set_time_limit(0);
        while(!feof($file))
        {
            echo fread($file, 1024);
        }
        fclose($file);
    }

}
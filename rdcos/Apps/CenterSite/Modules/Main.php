<?php
/**
 * 产品业务逻辑
 */
use MCU\Module;
use MCU\Cache;
use MCU\DbOperator;
define('INITIAL_PRESSURE',3219);//初压
define('COMPLEX_PRESSURE',3220);//复压
define('FINAL_PRESSURE',3221);//终压
define('PAVING_PRESSURE',3222);//摊铺机

define('SINGLE_DRUM',8861);//单钢轮
define('RUBBER_TRIE',8862);//胶轮
define('DOUBLE_DRUN',8863);//双钢轮
define('PAVING',8860);//摊铺机


class svc_main extends Module
{

    private $car_list = []; //车辆数据信息表
    private $rubber_center_array = [];//胶轮压路机center;
    private $rubber_process_average = [];//胶轮工艺平均线;
    private $double_center_array = [];//双钢轮工艺array;
    private $paving_process_average = [];//摊铺机最新栅格点;


    private $site_reg_info; //注册表
    private $site_paving;//摊铺机数据表
    private $site_final_initial;//初压数据表
    private $site_final_conplex;//复压数据表
    private $site_final_final;//终压数据表

    public function __construct()
    {
        //建立数据库连接   分别建立所有表连接
        

        \MCU\DbOperator::setConfig(DbClient::$config);
        $this -> site_reg_info = DbClient::M('site_reg_info');
        // $data = $this -> site_reg_info -> count();
        // var_dump($data);
        $this -> site_final_final =DbClient::M('site_final_final') ;
        $this -> site_final_initial =DbClient::M('site_final_initial') ;
        $this -> site_paving =DbClient::M('site_paving');
        $this -> site_final_conplex =DbClient::M('site_final_conplex') ;
      
        // Cache::rpush('car_num','444444');
        
    }

    //维护统计平均X,Y范围及计算该车辆的X,Y平均线
    /*
    * $old_datalist = [
    *                   [
    *                       'time' => 时间戳,
    *                       'center' =>[
    *                                   'x' => 1,
    *                                   'y' => 1
    *                                  ],
    *                    ],
    *                    [
    *                       'time' => 时间戳,
`   *                       'center' =>[
    *                                   'x' => 1,
    *                                   'y' => 1
    *                                  ],
    *                    ],
    *                    [...]
    *                 ]
    *
    *
    */
    private function maintains_range (&$old_datalist,$range)
    {
        $x_sum;
        $y_sum;
        //遍历该车辆的历史平均线数据
        foreach($old_datalist as $k => $v)
        {
            $cha = time()-$range;
            //当数据时间不在设定范围内时释放该数据
            if($v['time'] <= $cha)
            {
                unset($old_datalist[$k]);
                continue;
            }

            //计算出范围内X,Y总和
            $x_sum += $v['center']['x'];
            $y_sum += $v['center']['y'];
        }

        // echo  $x_sum."###". $y_sum."###"."\r\n";
        //计算该车辆X,Y平均线
        $old_count = count($old_datalist);
        $final['x'] = round($x_sum / $old_count);
        $final['y'] = round($y_sum / $old_count);
        // echo  $final['x']."###". $final['y']."###".$old_count."\r\n";
        return $final;
    }


    //计算压实遍数并修改对应数据表 table_link 传入相应的表连接
    private function data_analysis($table_link,$data)
    {
     //验证该栅格是否存在对应工艺的数据表中
     $where = "x = ".$data['x']." and y = ".$data['y'];
     $find = $table_link -> fields('id','x','y','times') -> reset() -> where($where) -> find();
    
       //验证该栅格是否存在对应工艺的数据表中 （伪代码）
     if($find['id'] == null)
       {
           //当前数据中加入压实遍数
           $data['times'] = 1;
         
           //插入数据库
           $table_link-> reset() -> insert($data);
       }
       else 
       {
           //当前数据中加入压实遍数
           $data['times'] = $find['times']+1;
           //组织修改数据
           $array = [
               'speed' => $data['speed'],
               'temp' => $data['temp'],
               'force' => $data['force'],
               'date_time' => $data['date_time'],
               'gps_time' => $data['gps_time'],
               'times' => $find['times']+1
           ];
           //更新数据
           $table_link-> reset() -> where('id = '.$find['id']) -> update($array);
       }
       $get_last_query = $table_link-> reset() -> get_last_query();
       $get_last_error = $table_link-> reset() -> get_last_error();
       Cache::set('get_last_query',date("Y-m-d H:i:s",time()).":".json_encode($get_last_query));
       Cache::set('get_last_error',date("Y-m-d H:i:s",time()).":".$get_last_error);
       //返回数据
       return $data;
    }


    //将数据派送给当前所有工艺作业车辆  
    private function data_delivery($data,$process)
    {
        //查询所有当前工艺车辆
        $reginfo_data  = cache::get('reginfoData');
        foreach($reginfo_data as $k => $v)
        {
            if($v['process'] == $process)
            {
               
                //当工艺相同时  给该车辆list追加数据
                Cache::rpush($v['car_id'],json_encode($data));
            }
        }
    }

    //更新REDIS及注册表的最后通讯时间及工艺
    private function data_update($car_id,$new_time,$process)
    {
        //查询所有当前工艺车辆
        $reginfo_data  = cache::get('reginfoData');
        foreach($reginfo_data as &$v )
        {
            //carid 相等 更新缓存的最后通讯时间及工艺
            if($v['car_id'] == $car_id)
            {
                
                $v['last_conn_time'] = $new_time;
                $v['process'] = $process;
                
            }
        }
        unset($v);
        //更新缓存的最后通讯时间及工艺
        cache::set('reginfoData',$reginfo_data);
        //更新注册表最后工艺及最后通讯时间
        $array = [
            'last_conn_time' => $new_time,
            'process' => $process
            ];
        $where = "`car_id` = "."'".$car_id."'";
        $this ->site_reg_info ->reset()->where($where)->update($array);

    }

    //计算距离那一个摊铺机最近 返回摊铺机ID  传入 y  和 摊铺机最新栅格
    private function group_job($y,$paving_list)
    {
        //条件 摊铺机id为键摊铺机center为值（可为多个）
        //     车辆center
        //使用车辆Y与摊铺机Y的差的绝对值做判断，越小则代表距离哪个摊铺机越近
        
        if(!empty($paving_list))
        {
            $final_offset = 99999;
            $final_car_id = '';
            foreach($paving_list as $k => $v)
            {

                $offset_y = abs($y - $v['y']);
               
                if($final_offset < $offset_y)
                {
                    $final_offset = $final_offset;
                    $final_car_id = $final_car_id;
                }
                else
                {
                    $final_offset = $offset_y;
                    $final_car_id = $k;
                }
            }
            return $final_car_id;
        }
    }
    public function start()
    {
        $this->car_list['Mainlist'] = 0;

        MCU\Timer::add($this -> getConfig('run_interval', 1),function(){
            $get_msg_num = $this -> getConfig('data_analysis_num', 50);//每次处理数据量
            for($i = 1;$i <= $get_msg_num;$i++ )
            {
                echo "1111111111";
                //获取队列第一条数据
                
                $row = Cache::lpop('Mainlist');
                
                if($row == null || $row == false) return;
                $arr_row = json_decode($row,true);
                //缓存记录多有list长度
                $this->car_list[$arr_row['car_id']] = 0;
                foreach($this->car_list as $k => &$v)
                {
                    $v = Cache::llen($k);
                }
                unset($v);
                Cache::set('Center_list_info',$this->car_list);
                
                //每条数据拆分为以栅格为单位的数据列
                $solo_grids = [];
                foreach($arr_row['grids'] as $k => $v)
                {
                    $solo_grids[] = [
                        'x' =>$v['x'],
                        'y' =>$v['y'],
                        'center_x' => $arr_row['center']['x'],
                        'center_y' => $arr_row['center']['y'],
                        'car_id'                 => $arr_row['car_id'],
                        'car_type'               => $arr_row['car_type'],
                        'date_time'              => $arr_row['date_time'],
                        'speed'                  => $arr_row['speed'],
                        'drct'					 => $arr_row['drct'],
                        'temp'                   => $arr_row['temp'],
                        'force'                  => $arr_row['force'],
                        'work_level'             => $arr_row['work_level'],  //层级
                        'gps_time'               => $arr_row['gps_time'],
                        'height'                 => $arr_row['height'], 
                        'site_x'                 => $arr_row['site_x'],
                        'site_y'                 => $arr_row['site_y'],
                        'lon'                    => $arr_row['lon'],
                        'lat'                    => $arr_row['lat'],
                        'transport_car_id'       => $arr_row['transport_car_id'],
                        'gps_left_width'         => $arr_row['gps_left_width'],
                        'gps_right_width'        => $arr_row['gps_right_width']
                    ];
                }
                $solo_grids_num = count($solo_grids);


                //通过车辆类型区别计算方式
                //定义：单钢轮  初压；胶轮  复压； 双钢轮 初压或者终压
                switch($arr_row['car_type'])
                {
                    case PAVING://摊铺机
                    //更新该摊铺机最新移动点
                    $paving_id = $arr_row['car_id'];
                    $this ->paving_process_average[$paving_id] = $arr_row['center'];
                    //更新当前车辆工艺
                    //平板
                    $arr_row['process'] = PAVING_PRESSURE;
                    //注册表及缓存
                    $this -> data_update($paving_id,$arr_row['date_time'],PAVING_PRESSURE);

                    //循环栅格计算遍数
                    for($i=0;$i < $solo_grids_num;$i++)
                    {
                        echo "22222222";
                        //添加车辆工艺
                        $solo_grids[$i]['process'] = PAVING_PRESSURE;
                        //计算压实遍数并修改对应数据表
                        $paving_row = $this->data_analysis($this -> site_paving,$solo_grids[$i]);
                        //平板数据加入压实遍数
                        $arr_row['grids'][$i]['times'] = $paving_row['times'];
                    }
                    //将数据派送给当前所有工艺作业车辆
                    $this -> data_delivery($arr_row,PAVING_PRESSURE);
                    break;
                    case SINGLE_DRUM://单钢轮
                        $single_id = $arr_row['car_id'];
                        //更新当前车辆工艺
                        //平板
                        $arr_row['process'] = INITIAL_PRESSURE;
                        //注册表及缓存
                        $this -> data_update($single_id,$arr_row['date_time'],INITIAL_PRESSURE);

                        //循环栅格计算遍数
                        for($i=0;$i < $solo_grids_num;$i++)
                        {
                            //添加车辆工艺
                            $solo_grids[$i]['process'] = INITIAL_PRESSURE;
                            //计算压实遍数并修改对应数据表
                            $single_row = $this->data_analysis($this -> site_final_initial,$solo_grids[$i]);
                            //平板数据加入压实遍数
                            $arr_row['grids'][$i]['times'] = $paving_row['times'];
                        }
                        //将数据派送给当前所有工艺作业车辆
                        $this -> data_delivery($arr_row,INITIAL_PRESSURE);
                    break;
                    case RUBBER_TRIE://胶轮
                    $rubber_id = $arr_row['car_id'];
                     //将数据的时间与center 存入以car_id为键的压路机center数组中
                    $array = [
                        'time' => $arr_row['date_time'],
                        'center' => $arr_row['center']
                    ];
                    array_push($this ->rubber_center_array,$array); 
                    //计算胶轮压路机平均X,Y
                    $range_center = $this -> maintains_range($this ->rubber_center_array,$this -> getConfig('process_ping', 60));
                    var_dump($range_center);
                    //***此时必须有摊铺机数据才能计算胶轮机跟随那一台摊铺机***//
                   if(!empty($this ->paving_process_average))
                   {
                        //通过y计算最近摊铺机id  
                         $rubber_job_pav_id = $this -> group_job($range_center['y'],$this ->paving_process_average);
                         var_dump($rubber_job_pav_id);
                        //以摊铺机id记录跟随摊铺机复压平均X
                         $this -> rubber_process_average[$rubber_job_pav_id] = $range_center['x'];
                   }

                    
                    //更新当前车辆工艺
                    //平板数据工艺修改
                    $arr_row['process'] = COMPLEX_PRESSURE;
                    //注册表及缓存数据工艺及最后连接时间修改
                    $this -> data_update($rubber_id,$arr_row['date_time'],COMPLEX_PRESSURE);
                  
                   
                    for($i=0;$i < $solo_grids_num;$i++)
                    {
                        //添加车辆工艺
                        $solo_grids[$i]['process'] = COMPLEX_PRESSURE;
                        //计算压实遍数并修改对应数据表
                        $rubber_row = $this->data_analysis($this -> site_final_conplex,$solo_grids[$i]);
                        //平板数据加入压实遍数
                        $arr_row['grids'][$i]['times'] = $rubber_row['times'];
                    }


                    $this -> data_delivery($arr_row,COMPLEX_PRESSURE);

                    break;
                    case DOUBLE_DRUN://双钢轮
                    $double_id = $arr_row['car_id'];

                    //判断双钢轮工艺数组中不存在当前车辆，建立以车辆id为键的数组  每个单钢轮都需要单独计算X
                    if(!array_key_exists($double_id,$this ->double_center_array))$this ->double_center_array[$double_id]=[];
                    //将数据的时间与center 存入以car_id为键的压路机center数组中
                    $array = [
                        'time' => $arr_row['date_time'],
                        'center' => $arr_row['center']
                    ];
                    array_push($this ->double_center_array[$double_id],$array); 
                    //计算压路机平均X,Y
                    $range_center = $this -> maintains_range($this ->double_center_array[$double_id],$this -> getConfig('process_ping', 60));

                    //双钢轮工艺计算开始
                    $process = '';
                    //***此时必须有摊铺机数据才能计算双钢轮机跟随那一台摊铺机***//
                    if(!empty($this ->paving_process_average))
                    {
                        //通过y计算最近摊铺机id  
                        $double_job_pav_id = $this -> group_job($range_center['y'],$this ->paving_process_average);
                        $pav_rubber_x = $this -> rubber_process_average[$double_job_pav_id];
                        $pav_x = $this ->paving_process_average[$double_job_pav_id]['x'];
                        //***此时必须有跟随该摊铺机的胶轮压路机才能计算出双钢轮的工艺 ***/
                        if(!empty($pav_rubber_x))
                        {
                            //判断  摊铺机最新X - 胶轮压路机的平均X > 0
                            //双钢轮压路机X >  胶轮压路机的平均X  为初压
                            //双钢轮压路机X <  胶轮压路机的平均X  为终压
                            //判断  摊铺机最新X - 胶轮压路机的平均X < 0
                            //双钢轮压路机X <  胶轮压路机的平均X  为初压
                            //双钢轮压路机X >  胶轮压路机的平均X  为终压
                            // define('INITIAL_PRESSURE',1);//初压
                            // define('FINAL_PRESSURE',3);//终压
                            if($pav_x - $pav_rubber_x > 0)
                            {
                                $process = ($range_center['x'] > $pav_rubber_x)?INITIAL_PRESSURE:FINAL_PRESSURE;
                            }
                            else
                            {
                                $process = ($range_center['x'] > $pav_rubber_x)?FINAL_PRESSURE:INITIAL_PRESSURE;
                            }
                        }
                    }
                    //******若不满足双钢轮工艺计算则默认所有双钢轮为初压******
                    if($process == '')$process == INITIAL_PRESSURE;
                  
                    //更新当前车辆工艺
                    //平板数据工艺修改
                    $arr_row['process'] = $process;
                    //注册表及缓存数据工艺及最后连接时间修改
                    $this -> data_update($rubber_id,$arr_row['date_time'],$process);

                    if($process == INITIAL_PRESSURE)
                    {
                                      
                        for($i=0;$i < $solo_grids_num;$i++)
                        {
                            //添加车辆工艺
                            $solo_grids[$i]['process'] = $process;
                            //计算压实遍数并修改对应数据表
                            $rubber_row = $this->data_analysis($this -> site_final_initial,$solo_grids[$i]);
                            //平板数据加入压实遍数
                            $arr_row['grids'][$i]['times'] = $rubber_row['times'];
                        }

                    }
                    else
                    {
                        for($i=0;$i < $solo_grids_num;$i++)
                        {
                            //添加车辆工艺
                            $solo_grids[$i]['process'] = $process;
                            //计算压实遍数并修改对应数据表
                            $rubber_row = $this->data_analysis($this -> site_final_final,$solo_grids[$i]);
                            //平板数据加入压实遍数
                            $arr_row['grids'][$i]['times'] = $rubber_row['times'];
                        }
                    }
                    //向所有相同层级车辆派送数据
                    $this -> data_delivery($arr_row,$process);
                    break;
                    default:
                    echo "undefined the ".$arr_row['car_type'];
                    break;
                }
            }
        });  
    }
}
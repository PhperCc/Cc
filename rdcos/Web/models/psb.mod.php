<?php
use MCU\Cache;
class mod_psb extends WebModule
{
    private $PSBconfig;
    public function __init()
    {
        $this->title = "排水板车载平板";
        $this->powers = array ();
        $this->ignore_main[] = "show";
        $this->ignore_main[] = "status";
        $this->hide_menu[] = 'jiaozhun';
        $this->config = Cache::get("Svc:PSB:config");

    }
    /**
    * 获取桩点
    */
    public function ajax_get_point_data()
    {
        $data = Cache::get("work_point_list");
        $file = "/home/system/data/log/work_point_list.log";
        file_put_contents($file, var_export($data,true));
        json_return($data);
    }

    /**
    * 获取工艺
    */
    public function ajax_get_tech()
    {
        $config = $this -> config;
        json_return($config['tech_depth']);
    }
    /**
    * 获取状态
    */
    public function ajax_getDevStatus()
    {
        $master = Cache::get("Sensor:GPS0:data");
        $EC     = Cache::get("Sensor:Encoder:data");
        //主天线
        if($master['quality']==5) $master['quality'] = 3;
        if(empty($master['quality']))
            $data['GNSS1'] = 0;
        else
            $data['GNSS1'] = $master['quality'];

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





    public function view_show(){

    }

    
    /**
    * 获取夯击历史点
    */
    public function ajax_get_history_point()
    {
        $data = $this -> get_history_data();
        json_return($data);
    }

    /**
    * 获取cvs数据
    */
    public function get_history_data()
    {
        $data_save_path = "record/psb_history.csv";
        $history_file_path = MCU\LocalFile::getFilePath($data_save_path);
        $file = fopen($history_file_path,"r");
        while(! feof($file))
        {
            $line = fgets($file);
            if(empty($line))continue;
            $data[] = json_decode($line, true);
        }

        fclose($file);

        return $data;
   }


    // 拉线编码器校准
    public function view_jiaozhun()
    {
        $this -> title = "排水板校准 - ENH";
    }
}
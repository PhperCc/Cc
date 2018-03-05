<?php
/**
 * 生产数据提交队列维持
 */

use MCU\Cache;
use MCU\ProduceData;
use MCU\Kernel;

/**
 * Class svc_ProduceDataSubmit
 */
class sys_ProduceDataSubmit extends Kernel
{
    private $success_count = 0;
    private $failed_count = 0;
    private $submit_count = 0;

    /**
     * 启动服务
     */
    public function start()
    {
        // 定时将磁盘生成数据队列上传到云端
        $sleep_interval = $this->getConfig('sleep_interval', 2);
        $read_count = $this->getConfig('read_count', 50);

        \MCU\Timer::add($sleep_interval, function() use($read_count)
        {
            // 未连接到服务端时， 不提交数据
            if(Cache::get('sys:command:connected')  == 0)
            {
                return;
            }

            
            $data_list = ProduceData::get($read_count);
            foreach($data_list as $data_row)
            {
                $data_id = $data_row['id'];
                $data = json_decode($data_row['data'], true);
                $this->command('Data.submit', $data);
                Cache::set('ProduceData:submit:submit_count', ++ $this->submit_count);
            }
        });

        //监听上传成功回调
        \Channel\Client::on('SYS.ProduceData.submit.success', function($data_id)
        {    
            Cache::set('ProduceData:submit:success_count', ++ $this->success_count);
            ProduceData::del($data_id);
        });
    }
}

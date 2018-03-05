<?php
namespace MCU\Plugin;

class IPlugin
{
    protected $submitHooks = false;

    /**
     * 钩子程序的初始化
     */
    public function __setHooks()
    {
        $isConnect = false;
    	if($submitHooks)
    	{	
    		if($isConnect) 
    		{
    			\Channel\Client::connect('127.0.0.1', 2206);
                $isConnect = true;
    		}
		    \Channel\Client::on('ProduceData.create', function($data){
		    	if($data['action'] == 'Data.submit')
		    	{
		    		$this->onSubmit($data['params']);
		    	}
		    });
    	}
    }
    
    /**
     * 提交数据钩子
     */
    public function onSubmit($data)
    {
    	return true;
    }

    /**
     * 获取插件描述
     */
    public function getDesc()
    {
        return '';
    }

    /**
     * 获取插件版本
     */
    public function getVer()
    {
       return ''; 
    }

    /**
     * 启动插件
     */
    public function start()
    {
    	return true;
    }

    /**
     * 关闭插件
     */
    public function stop()
    {
        return true;
    }
}

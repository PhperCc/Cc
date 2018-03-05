<?php
namespace MCU;

/**
 * 基础服务类方法
 */
abstract class Process
{
    /**
     * @var string 模块名称
     */
    protected $_module_name = '';
    /**
     * @var string 服务名称
     */
    protected $_svc_name = '';
    
    /**
     * @var worker wokerman对象
     */
    public $_worker;
    /**
     * ServiceBase constructor.
     * @param $module_name
     * @param $svc_name
     * @param $config
     */
    public function __construct($module_name, $svc_name, $config)
	{
        global $appRoot;
        $this->_module_name = $module_name;
        $this->_svc_name = $svc_name;
        $this->init();
		$this->__init();
	}

    /**
     * 初始化
     */
    public function __init(){ }
    public function init(){ }
    /**
     * 启动
     */
    public function start() { }

    /**
     * 重载
     */
    public function reload() { }

    /**
     * 停止
     */
    public function stop() { }

    /**
     * 启动基础服务
     */
    public function _start()
    {
        // STDOUT 不存在时， 打开输出缓冲区， 并监测转移缓冲
        if (function_exists('posix_isatty') && !posix_isatty(STDOUT))
        {
            ob_start();
            \Workerman\Lib\Timer::add(0.5, function()
            {
                $ob_contents = ob_get_contents();
                ob_clean();
                if(!empty($ob_contents)) @file_put_contents(\Workerman\Worker::$stdoutFile, $ob_contents, FILE_APPEND);
            });
        }
        \Channel\Client::connect('127.0.0.1', CHANNEL_PORT);
        //控制事件监听
        \Channel\Client::on('ControlCommand', function($body)
        {
            if(!array_key_exists("action", $body)) return;
            $action = $body["action"];
            $method_name = 'cmd_'.$body['action'];
            if(!method_exists($this, $method_name)) return;
            $params = array_key_exists("params", $body) ? $body["params"] : null;
            $this -> $method_name($params);
        });
    }

    /**
     * 记录日志
     */
    protected function log($content)
    {
        Logger::log($content, $this->_module_name . '_' . $this->_svc_name);
    }

    /**
     * 取服务配置信息
     */
    protected function getConfig($key = null, $default = null)
    {
        return ModuleConfig::get($this->_module_name, $this->tp, $this->_svc_name.'/'.$key, $default);
    }

    /**
     * 设置配置信息
     */
    protected function setConfig($key, $val = "")
    {
        return ModuleConfig::set($this ->_module_name, $this->tp, $this->_svc_name.'/'.$key, $val, true, false);
    }

    /**
     * 发送数据
     */
    protected function send($body,$type='SendData')
    {
        \Channel\Client::publish($type, $body);
        return true;
    }

    /**
     * 向服务端发送指令
     */
    protected function command($action, $params = null)
    {
        $body = [
			"action" => $action,
			"params" => $params
		];
        return $this->send($body);
    }

    /**
     * 向服务端提交生产数据
     */
    protected function data_submit($data)
	{
        return Product::save($data);
	}
}
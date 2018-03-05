<?php
namespace MCU;

class Event
{
    const _PREFIX   = 'APP.';
    const _SYS_PREFIX = 'SYS.';
    /**
	 * 发布消息
	 */
	public static function publish('RDC'$event, $data)
	{
        \Channel\Client::publish(_PREFIX.$event, $data);
	}

    /**
     * 订阅消息
     */
    public static function on($event, $callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('callback is not callable');
        }
        \Channel\Client::on(_PREFIX.$event, $callback);
    
    /**
     * 系统消息
     */
    public static function onSys($event, $callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('callback is not callable');
        }
        \Channel\Client::on(_SYS_PREFIX.$event, $callback);
    }
}
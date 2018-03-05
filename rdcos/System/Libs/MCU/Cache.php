<?php
namespace MCU;
use \MCU\Cache\RedisCache;
use \MCU\Cache\MemcachedCache;
/**
 * Cache.
 */
class  Cache
{
    const CACHE_TYPE   = 'Redis'; //Memcached
    public static $options;
    public static $fp;

    public static function setConfig($options)
    {
        self::$options = $options;
    }

    public static function initCache($pid)
    {
        
        if(self::CACHE_TYPE == 'Redis')
    	{
            if(empty(self::$options))
            {
                self::$options = ['host'=>'127.0.0.1','port'=>6379];
            }
            self::$fp[$pid] = new RedisCache(self::$options);
    	}
    	else
    	{
    		if(empty(self::$options))
            {
                self::$options = ['host'=>'127.0.0.1','port'=>11211];
            }
            for ($i=0; $i < self::LINK_NUM; $i++) { 
                self::$fp[$i] = new MemcachedCache(self::$options);
            }
    	}
    }

    public static function __callStatic($name, $arguments) 
    {
        $pid = posix_getpid();
        if(!isset(self::$fp[$pid]))
        {
            self::initCache($pid);
        }

        return call_user_func_array([self::$fp[$pid], $name], $arguments);
    }
 
}
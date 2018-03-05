<?php
namespace MCU\Cache;

/**
 * RedisCache.
 */
class RedisCache extends  CacheBase
{
	public $redis = null;
    
    public function __construct($options)
    {
    	$this->redis = new \Redis();
		$this->redis->connect($options['host'], $options['port']);
		if(isset($options['auth'])){
			$this->redis->auth($options['auth']);
		}
		//$this->redis->select(0);
    }

    public function set($key, $val, $expiry = 0)
    {
    	if(is_array($val) || is_object($val))
        {
            $val = json_encode($val);
        }

    	if($expiry>0)
    	{
            $return = $this->redis->setex($key,$expiry, $val);
    	}
        else
        {
            $return = $this->redis->set($key,$val);
        }
        return $return;
    }

    public function get($key)
    {
    	$str = $this->redis->get($key);
    	$obj = json_decode($str,true);
 		return (json_last_error() == JSON_ERROR_NONE)?$obj:$str;
    }

    public function del($key)
    {
    	return $this->redis->del($key);
    }

    public function save()
    {
    	return $this->redis->bgsave();
    }

    public function keys()
    {
        return $this->redis->keys('*');
    }

    function __call($method_name, $args)
    {
        return call_user_func_array([$this->mem, $method_name], $args);
    }  
}
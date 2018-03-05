<?php
namespace MCU\Cache;

use \MCU\LocalFile;
/**
 * MemcachedCache.
 */
class MemcachedCache extends  CacheBase
{
	public $mem = null;
	public $flag = true;
	public static $keysList = 'MemcacheAdapter:keys';
    
    public function __construct($options)
    {
    	if(true) //'Memcached'
    	{
    		$this->mem = new \Memcached();
			$this->mem->addServer($options['host'], $options['port']);
    	}
    	else
    	{
    		$this->mem = new \Memcache();
			$this->mem->connect($options['host'], $options['port']);
    	}
    }

    public function set($key, $val, $expiry = 0)
    {
        $return = $this->mem->set($key, $val,$this->flag,$expiry);
    	if($return)
        {
            if(false === $mem_keys = $this->mem->get(static::$keysList)) $mem_keys = [];
            if(!in_array($key, $mem_keys)) $mem_keys[] = $key;
            $this -> mem -> set(static::$keysList, $mem_keys);
        }

        return $return;
    }

    public function get($key)
    {
    	return $this->mem->get($key);
    }

    public function del($key)
    {
    	$return = $this->mem->delete($key, 0);
    	if($return)
        {
            if(false === $mem_keys = $this->mem->get(static::$keysList)) $mem_keys = [];
            if(in_array($key, $mem_keys)) $mem_keys = array_diff($mem_keys, [$key]);
            $this->mem->set(static::$keysList, $mem_keys);
        }

        return $return;
    }

    public function save()
    {	$mem_keys = $this->mem->get("MemcacheAdapter:keys");
    	if(false === $mem_keys) return false;
    	$data = [];
    	foreach ($mem_keys as $val) {
    		$data[$val] = $this->mem->get($val);
    	}
    	LocalFile::putObject('memcache/data.cache', $data);
    	unset($data);

    	return true;	
    }

    /**
     * 缓存持久化恢复
     */
    public function recover()
    {
        $data = LocalFile::getObject('memcache/data.cache');
        foreach ($data as $key => $val) {
        	$this->set($key,$val);
        }
        unset($data);

        return true;
    }

    public function keys()
    {
        return $this->mem->get(static::$keysList);
    }

    function __call($method_name, $args)
    {
        return call_user_func_array([$this->mem, $method_name], $args);
    }  
}
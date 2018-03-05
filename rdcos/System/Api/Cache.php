<?php
use MCU\Sys;
use MCU\Cache;
/**
 * 缓存操作接口
 */
class api_Cache extends MCU\ApiHelper\ApiBase
{

    /**
     * 查询所有缓存键名列表
     * @#prefix string 键名前缀
     *
     * return data array 键名列表
     */
    public function keys($params)
    {
        $prefix = get_param($params, "prefix");
        $keys = Cache::keys();
        if(!empty($prefix))
        {
            $filter_keys = [];
            foreach($keys as $key)
            {
                if(substr($key, 0, strlen($prefix)) == $prefix)
                {
                    $filter_keys[] = $key;
                }
            }
            $keys = $filter_keys;
        }
        sort($keys);

        return R(true, "", $keys);
    }

    /**
     * 取缓存值
     * @key string 缓存键名
     *
     * return data mixed 缓存值
     */
    public function get($params)
    {
        $key =  get_param($params, "key");
        if($key == '')
        {
            return R(false, "need param 'key'");
        }
        else
        {
            $val = Cache::get($key);
        }

        return R(true, $params["key"], $val);
    }

    /**
     * 取多个缓存值
     * @keys array 缓存键名
     *
     * return data array 缓存值
     */
    public function gets($params)
    {
        $key =  get_param($params, "keys");
        if($key == '')
        {
            return R(false, "need param 'keys'");
        }
        else
        {
            $vals = [];
            foreach($key as $keyitem)
            {
                $vals[$keyitem] = Cache::get($keyitem);
            }
        }

        return R(true, "", $vals);
    }

    /**
     * 设置缓存值
     * @key array 缓存键名
     * @val array 缓存值
     */
    public function set($params)
    {
        if(!array_key_exists("key", $params))
        {
            return R(false, "need param 'key'");
        }
        if(!array_key_exists("val", $params))
        {
            return R(false, "need param 'val'");
        }

        Cache::set($params["key"], $params["val"]);

        return true;
    }

    /**
     * 设置持久缓存值
     * @key array 缓存键名
     * @val array 缓存值
     */
    public function setex($params)
    {
        if(!array_key_exists("key", $params))
        {
            return R(false, "need param 'key'");
        }
        if(!array_key_exists("val", $params))
        {
            return R(false, "need param 'val'");
        }

        $val = Cache::set($params["key"], $params["val"]);

        return true;
    }

    /**
     * 删除缓存
     * @key array 缓存键名
     */
    public function del($params)
    {
        if(!array_key_exists("key", $params))
        {
            return R(false, "need param 'key'");
        }

        Cache::del($params["key"]);

        return true;
    }
}
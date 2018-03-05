<?php
namespace MCU\Cache;
/**
 * CacheInterface.
 */
abstract class  CacheBase
{

    /**
     * 设置缓存值
     */
    abstract public function set($key, $val, $expiry = 0);

    /**
     * 获取缓存值
     */
    abstract public function get($key);

    /**
     * 删除缓存键值
     */
    abstract public function del($key);

    /**
     * 缓存持久化到磁盘
     */
    abstract public function save();

    /**
     * 获取缓存所有key
     */
    abstract public function keys();
}
<?php
namespace MCU;
use \MCU\Utils\FileSystem;

class LocalFile
{
    const DATA_ROOT = "/home/system/data/";
    const TEMP_DIR = "/tmp/";

    public static function get($path, $default = null)
    {
        $file_path = self::getFilePath($path);
        if(!@file_exists($file_path)) return $default;

        if(false === $content = @file_get_contents($file_path)) return $default;
        return $content;
    }

    public static function getLines($path, $default = [])
    {
        $file_path = self::getFilePath($path);
        if(!@file_exists($file_path)) return $default;

        if(false === $lines = @file($file_path, FILE_IGNORE_NEW_LINES)) return $default;
        return $lines;
    }

    public static function getObject($path, $default)
    {
        if(null === $content = self::get($path, null)) return $default;
        return json_decode($content, true);
    }

	public static function put($path, $data, $append = false)
	{
        if(self::isLocked()) return false;
        $file_path = self::getFilePath($path);
        FileSystem::create_dir(dirname($file_path));
        $put_result = @file_put_contents($file_path, $data, LOCK_EX | ($append ? FILE_APPEND : 0));
        return $put_result;
    }

    public static function putObject($path, $data)
    {
        return self::put($path, json_encode($data), false);
    }

    public static function putLine($path, $data, $append = false)
    {
        return self::put($path, "$data\n", $append);
    }

    public static function del($path)
    {
        $file_path = self::getFilePath($path);
        if(@file_exists($file_path)) @unlink($file_path);
    }

    public static function getSize($path)
    {
        $file_path = self::getFilePath($path);
        @clearstatcache();
        if(false === $filesize = @filesize($file_path)) return 0;
        return $filesize;
    }

    public static function exists($path)
    {
        $file_path = self::getFilePath($path);
        return @file_exists($file_path);
    }

    public static function getExistsPath($path)
    {
        $file_path = self::getFilePath($path);
        if(@file_exists($file_path)) return $file_path;
        return false;
    }

    public static function getDir($dir)
    {
        $true_dir = $dir;
        if(substr($true_dir, 0, 5) != static::TEMP_DIR)
        {
            $true_dir = static::DATA_ROOT . $dir;
        }
        return $true_dir;
    }

    public static function getFilePath($path)
    {
        if(substr($path, 0, strlen(static::TEMP_DIR)) == static::TEMP_DIR) return $path;
        $path = trim($path, "/");
        return static::DATA_ROOT . $path;
    }

    public static function lock()
    {
        self::put(static::TEMP_DIR . "LocalFile/put.lock", "1");
    }

    public static function isLocked()
    {
        return self::exists(static::TEMP_DIR . "LocalFile/put.lock");
    }

    public static function unlock()
    {
        return self::del(static::TEMP_DIR . "LocalFile/put.lock");
    }
}
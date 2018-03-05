<?php
namespace classes;

class Logger
{
    const LOG_LEVEL_ALL   = 0;
    const LOG_LEVEL_DEBUG = 1;
    const LOG_LEVEL_INFO  = 2;
    const LOG_LEVEL_WARN  = 3;
    const LOG_LEVEL_ERROR = 4;
    const LOG_LEVEL_FATAL = 5;

    public static $level = self::LOG_LEVEL_ALL;

    public static $onLog = null;

    /**
	 * 记录日志
	 */
	public static function log($content, $category = '', $level = self::LOG_LEVEL_INFO)
	{
        if(empty($content) || self::$onLog == null || !is_callable(self::$onLog)) return;
        call_user_func(self::$onLog, $content, $category, $level);

	}
}
<?php

namespace MCU\Utils\Http;

/*
 * 静态工具类
 */
class Request
{
	/*
	 * 获取页面传递的参数
	 */
	public static function param($param, $default = null)
	{
        if(empty($param)) return $default;

		$val = $default;
		if (isset($_REQUEST[$param]))
		{
			$val = $_REQUEST[$param];
		}

        if(is_string($val)) $val = trim($val);
		return $val;
	}

    /*
	 * 获取参数，返回文本，如为空或异常返回空字符串
	 */
	public static function paramS($param, $default = '')
	{
		$val = strval(self::param($param, $default));
        return str_replace("'", "", $val);
	}

	/*
	 * 获取参数，返回整数，如为空或异常返回0
	 */
	public static function paramI($param, $default = 0)
	{
		$val = self::param($param, $default);
		return intval($val);
	}

	/*
	 * 获取参数，返回浮点数，如为空或异常返回0
	 */
	public static function paramF($param, $default = 0)
	{
		$val = self::param($param, $default);
		return floatval($val);
	}
    /*
	 * 获取参数，返回日期字符串，如为空或异常返回今天
	 */
	public static function paramD($param, $default = '')
	{
		$val = self::param($param, $default);
        if(empty($val)) $val = date('Y-m-d');
        if(false === $date_val = strtotime($val)) return $val;
		return date('Y-m-d', $date_val);
	}

    /*
	 * 获取参数，返回日期时间字符串，如为空或异常返回当前时间
	 */
	public static function paramT($param, $default = '')
	{
		$val = self::param($param, $default);
        if(empty($val)) $val = date('Y-m-d H:i:s');
        if(false === $date_val = strtotime($val)) return $val;
		return date('Y-m-d H:i:s', $date_val);
	}

    /*
	 * 获取参数，返回数组，如为空或异常返回空数组
	 */
	public static function paramA($param, $default = array())
	{
		$val = self::param($param, null);
        if($val === null) return $default;

        if(is_array($val))
        {
            return $val;
        }
        else
        {
            return array($val);
        }
	}
}
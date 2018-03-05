<?php
defined('SYS_ROOT') OR exit('No direct script access allowed');
/**
 * json 返回
 */
function json_return($data = null)
{
	header('Content-Type:application/json; charset=utf-8');
	if(empty($data)) $data = null;
	echo json_encode($data);
}

/**
 * array 深度合并
 */
function array_deep_merge($source, $dest)
{
    if(!is_array($source)) return $dest;
    $merged = $source;
    if(!is_array($dest))
    {
        $merged[] = $dest;
        return $merged;
    }

    foreach($dest as $k => $v)
    {
        if(array_key_exists($k, $merged) && is_array($merged[$k]) && is_array($dest[$k]))
        {
            $merged[$k] = array_deep_merge($merged[$k], $v);
        }
        else
        {
            $merged[$k] = $v;
        }
    }
    return $merged;
}

/**
 * 取网卡信息
 *
 * @param string $name    网卡名称
 * @param string $field   网卡信息字段名
 * @param string $def_val 默认值
 *
 * @return array|mixed|string
 */
function get_network_info($name = "eth0", $field = "", $def_val = "")
{
    @exec("ifconfig $name", $lines);
    $network_info = ["mac"   => "",
                     "ip"    => "",
                     "state" => "down",
                     "bcast" => "",
                     "mask"  => "",
                     "rxb"   => 0,
                     "txb"   => 0
    ];
    foreach($lines as $line)
    {
        if(preg_match("/HWaddr [0-9a-f:]+/i", $line, $matches) > 0)
        {
            $network_info["mac"] = strtoupper(str_replace("HWaddr ", "", $matches[0]));
        }

        if(preg_match("/inet addr:([0-9\.]+)/i", $line, $matches) > 0)
        {
            $network_info["ip"] = str_replace("inet addr:", "", $matches[0]);
        }

        if(preg_match("/UP (?:\w+ )?RUNNING/i", $line, $matches) > 0)
        {
            $network_info["state"] = str_replace("State:", "", $matches[0]);
        }

        if(preg_match("/Bcast:([0-9\.]+)/i", $line, $matches) > 0)
        {
            $network_info["bcast"] = str_replace("Bcast:", "", $matches[0]);
        }

        if(preg_match("/Mask:([0-9\.]+)/i", $line, $matches) > 0)
        {
            $network_info["mask"] = str_replace("Mask:", "", $matches[0]);
        }

        if(preg_match("/RX bytes:([0-9]+)/i", $line, $matches) > 0)
        {
            $network_info["rxb"] = str_replace("RX bytes:", "", $matches[0]);
        }

        if(preg_match("/TX bytes:([0-9]+)/i", $line, $matches) > 0)
        {
            $network_info["txb"] = str_replace("TX bytes:", "", $matches[0]);
        }
    }
    if(!empty($field))
    {
        return array_key_exists($field, $network_info) ? $network_info[$field] : $def_val;
    }

    return $network_info;
}


/**
 * 字符串对称加密
 *
 * @param string $source 原文本
 * @param string $key    密钥
 *
 * @return string
 */
function encrypt($source, $key)
{
    if(!is_string($source) || $source == '')
    {
        return '';
    }
    if(!is_string($key) || $key == '')
    {
        $key = 'key';
    }
    $true_key = md5($key);
    $key_length = strlen($true_key);

    $xor_str = "";
    for($i = 0, $j = 0; $i < strlen($source); $i++, $j = $i % $key_length)
    {
        $xor = $source[$i] ^ $true_key[$j];
        if(ord($xor) == 0)
        {
            $xor = $source[$i];
        }
        $xor_str .= $xor;
    }

    return base64_encode($xor_str);
}

/**
 * 字符串对称解密
 *
 * @param string $source 加密内容
 * @param string $key   密钥
 *
 * @return string
 */
function decrypt($source, $key)
{
    if(!is_string($source) || $source == '')
    {
        return '';
    }
    $source = str_replace(' ', '+', $source);
    $source = base64_decode($source);

    if(!is_string($key) || $key == '')
    {
        $key = 'key';
    }
    $true_key = md5($key);
    $key_length = strlen($true_key);

    $xor_str = "";
    for($i = 0, $j = 0; $i < strlen($source); $i++, $j = $i % $key_length)
    {
        $xor = $source[$i] ^ $true_key[$j];
        if(ord($xor) == 0)
        {
            $xor = $source[$i];
        }
        $xor_str .= $xor;
    }

    return $xor_str;
}

/**
 * 检查PHP脚本格式是否正确
 *
 * @param string $file_path php文件路径
 *
 * @return array|bool
 */
function check_php_syntax($file_path)
{
    if(!file_exists($file_path))
    {
        return false;
    }
    $code_content = file_get_contents($file_path);
    if(substr($code_content, 0, 5) == "<?php")
    {
        $code_content = substr($code_content, 5);
    }
    elseif(substr($code_content, 0, 2) == "<?")
    {
        $code_content = substr($code_content, 2);
    }

    $current_error_level = error_reporting();
    error_reporting(0); // 禁止输出任何错误
    ob_start();

    $error = true;
    $syntax_check_code = "return true;\r\n$code_content";
    if(eval($syntax_check_code) !== true)
    {
        $error = error_get_last();
        $error['line'] += 1;

        return $error;
    }
    ob_end_clean();
    error_reporting($current_error_level);    // 恢复之前的报错级别

    return $error;
}

/**
 * 检查数组是否包含某些键
 *
 * @param string|array $keys        键名
 * @param array        $array       被检查数组
 * @param bool         $check_empty 是否检查空值
 *
 * @return bool
 */
function check_keys($keys, $array, $check_empty)
{
    $keys_array = [];
    if(is_string($keys))
    {
        $keys = preg_replace("/\s+/", ",", trim($keys));
        $keys = preg_replace("/,+/", ",", $keys);
        $keys_array = implode(preg_replace("/\s+/", ",", $keys), ",");
    }
    else
    {
        if(is_array($keys))
        {
            $keys_array = $keys;
        }
    }

    foreach($keys_array as $key)
    {
        if(!array_key_exists($key, $array))
        {
            return false;
        }
        $array_val = trim($array[$key]);
        if($check_empty && empty($array_val))
        {
            return false;
        }
    }

    return true;
}

/**
 * tar打包
 *
 * @param string $path        源目录路径
 * @param string $target_path 目标文件保存路径
 *
 * @return bool
 */
function tar($path, $target_path)
{
    if(!file_exists($path))
    {
        return false;
    }
    $dir = dirname($path);
    \MCU\Utils\OperationSystem::exec("tar cvf $target_path -C $dir $path");
    return true;
}

function prettyHex($buffer)
{
    $hex = bin2hex($buffer);
    $bytes = [];
    for($i = 0; $i < strlen($hex); $i += 2)
    {
        $bytes[] = strtoupper($hex[$i] . $hex[$i + 1]);
    }

    return implode(" ", $bytes);
}

function size_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value))
    {
        $show_value = 0;
    }
    $units = ['B',
              'KB',
              'MB',
              'GB',
              'TB',
              'PB',
              'EB',
              'ZB',
              'YB'
    ];
    $unit_index = 0;
    while($show_value > 1000)
    {
        $show_value = $show_value / 1024;
        $unit_index++;
        if($unit_index >= count($units) - 1)
        {
            break;
        }
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];

    return "$show_value $show_unit";
}

function weight_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value))
    {
        $show_value = 0;
    }
    $units = ['KG',
              '吨',
              '千吨'
    ];
    $unit_index = 0;
    while($show_value > 1000)
    {
        $show_value = $show_value / 1000;
        $unit_index++;
        if($unit_index >= count($units) - 1)
        {
            break;
        }
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];

    return "$show_value $show_unit";
}

function seconds_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value))
    {
        $show_value = 0;
    }
    $units = ['秒',
              '分',
              '小时'
    ];
    $unit_index = 0;
    while($show_value > 60)
    {
        $show_value = $show_value / 60;
        $unit_index++;
        if($unit_index >= count($units) - 1)
        {
            break;
        }
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];

    return "$show_value $show_unit";
}

function R($result, $info = '', $data = null)
{
    return array('result' => $result, 'info' => $info, 'data' => $data);
}

function get_param($params, $key, $default = false)
{
    if(!is_array($params))
    {
        return $default;
    }

    if(!array_key_exists($key, $params))
    {
        return $default;
    }

    return $params[$key];
}
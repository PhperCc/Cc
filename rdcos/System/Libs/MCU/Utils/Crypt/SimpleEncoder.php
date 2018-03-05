<?php

namespace MCU\Utils\Crypt;

class SimpleEncoder
{
    public static function encrypt($source, $key)
    {
        if(!is_string($source) || $source == '') return '';
        if(!is_string($key) || $key == '') $key = 'key';
        $true_key = md5($key);
        $key_length = strlen($true_key);

        $xor_str = "";
        for ($i = 0, $j = 0;  $i < strlen($source); $i++, $j = $i % $key_length)
        {
            $xor = $source[$i] ^ $true_key[$j];
            if(ord($xor) == 0) $xor = $source[$i];
            $xor_str .= $xor;
        }
        return base64_encode($xor_str);
    }

    // 字符串解密
    public static function decrypt($source, $key)
    {
        if(!is_string($source) || $source == '') return '';
        $source = str_replace(' ', '+', $source);
        $source = base64_decode($source);

        if(!is_string($key) || $key == '') $key = 'key';
        $true_key = md5($key);
        $key_length = strlen($true_key);

        $xor_str = "";
        for ($i = 0, $j = 0;  $i < strlen($source); $i++, $j = $i % $key_length)
        {
            $xor = $source[$i] ^ $true_key[$j];
            if(ord($xor) == 0) $xor = $source[$i];
            $xor_str .= $xor;
        }
        return $xor_str;
    }
}
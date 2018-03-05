<?php
function check_keys($keys, $array, $check_empty)
{
    $keys_array = [];
    if(is_string($keys))
    {
        $keys = preg_replace("/\s+/", ",", trim($keys));
        $keys = preg_replace("/,+/", ",", $keys);
        $keys_array = implode(preg_replace("/\s+/", ",", $keys), ",");
    }
    else if(is_array($keys))
    {
        $keys_array = $keys;
    }

    foreach($keys_array as $key)
    {
        if(!array_key_exists($key, $array)) return false;
        $array_val = trim($array[$key]);
        if($check_empty && empty($array_val)) return false;
    }
    return true;
}

function size_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value)) $show_value = 0;
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $unit_index = 0;
    while($show_value > 1000)
    {
        $show_value = $show_value / 1024;
        $unit_index ++;
        if($unit_index >= count($units) - 1) break;
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];
    return "$show_value $show_unit";
}

function weight_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value)) $show_value = 0;
    $units = ['KG', '吨', '千吨'];
    $unit_index = 0;
    while($show_value > 1000)
    {
        $show_value = $show_value / 1000;
        $unit_index ++;
        if($unit_index >= count($units) - 1) break;
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];
    return "$show_value $show_unit";
}

function seconds_text($value = 0)
{
    $show_value = $value;
    if(!is_numeric($show_value)) $show_value = 0;
    $units = ['秒', '分', '小时'];
    $unit_index = 0;
    while($show_value > 60)
    {
        $show_value = $show_value / 60;
        $unit_index ++;
        if($unit_index >= count($units) - 1) break;
    }
    $show_value = number_format($show_value, 2, '.', '');
    $show_unit = $units[$unit_index];
    return "$show_value $show_unit";
}


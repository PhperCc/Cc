<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:19
         compiled from "/home/system/rdcos/Web/views/caches/index.html" */ ?>
<?php /*%%SmartyHeaderCode:17725904795a79043b53e5c4-16445241%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd9d6f8f1d6164511d9ba6981415e6b47cf722174' => 
    array (
      0 => '/home/system/rdcos/Web/views/caches/index.html',
      1 => 1517878362,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '17725904795a79043b53e5c4-16445241',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043b548055_36423328',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043b548055_36423328')) {function content_5a79043b548055_36423328($_smarty_tpl) {?><legend>
    <button type="button" class="btn btn-primary" id="btnPause"><i class="icon-pause icon-white"></i> 暂停</button>
    <button type="button" class="btn btn-primary" id="btnPlay" style="display:none;"><i class="icon-play icon-white"></i> 监控</button>
</legend>
<form class="form-horizontal" onsubmit="return false;">
<table class="table table-bordered table-hover" id="cache_list">
    <tr>
        <th>键名</th>
        <th>值</th>
    </tr>
</table>
</form>

<style type="text/css">
.led{margin-right:5px;}
</style>
<script type="text/javascript" src='./public/js/ServiceApi.js'></script>
<script type="text/javascript">
var play_status = true;
$(function(){
    $('#btnPause').click(function(){
        play_status = false;

        $('#btnPause').hide();
        $('#btnPlay').show();
    });

    $('#btnPlay').click(function(){
        play_status = true;

        $('#btnPlay').hide();
        $('#btnPause').show();
    });
});


ServiceApi.request("Cache.keys", {}, function($reqSeq, $result, $info, $keys)
{
    if(!$result) return;
    if(!play_status) return;
    for(var $i = 0; $i < $keys.length; $i++)
    {
        var $key = $keys[$i];
        var $validKey = validKey($key);
        var $row = $('#cache_' + $validKey);
        if($row.length == 0)
        {
            var time = ($key.split(':')[0] == 'Sensor')?300:3000;
            ServiceApi.request("Cache.get", {'key': $key}, function($reqSeq, $result, $info, $val){
                if(!$result) return;
                if(!play_status) return;
                var $valHtml = JSON.stringify($val);
                var validKey = $info.replace(/[^\w]/g, "__");
                $('#cache_val_' + validKey).html($valHtml);
            },time);

            var $line_html = '<tr class="cache" key="' + $validKey + '" id="cache_' + $validKey + '">';
            $line_html += '<td class="key" id="cache_key_' + $validKey + '">' + $key + '</td>';
            $line_html += '<td class="val" id="cache_val_' + $validKey + '" key="' + $key + '"></td>';
            $line_html += '</tr>';
            $('#cache_list').append($line_html);
        }
    }
}, 1000);

function validKey($key)
{
    return $key.replace(/[^\w]/g, "__");
}
</script><?php }} ?>
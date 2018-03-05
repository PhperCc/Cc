<?php /* Smarty version Smarty-3.1.13, created on 2013-01-03 04:58:48
         compiled from "/home/system/rdcos/Web/views/apitest/index.html" */ ?>
<?php /*%%SmartyHeaderCode:63263429650e49f88ef8075-98116435%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '1d3ac464f97a91029f871cef9a224eca2296793c' => 
    array (
      0 => '/home/system/rdcos/Web/views/apitest/index.html',
      1 => 1517878362,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '63263429650e49f88ef8075-98116435',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'reflection' => 0,
    'param_name' => 0,
    'param' => 0,
    'api' => 0,
    'method' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_50e49f89058864_88101207',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_50e49f89058864_88101207')) {function content_50e49f89058864_88101207($_smarty_tpl) {?><script type="text/javascript" src="./public/js/GpsPoint.js"></script>
<form class="form-horizontal">
<div class="page-header"><h4><?php echo $_smarty_tpl->tpl_vars['reflection']->value['desc'];?>
</h4></div>
<i><?php echo $_smarty_tpl->tpl_vars['reflection']->value['sign'];?>
</i>
<table class="table table-bordered table-hover">
    <thead>
        <tr><th style="width:150px;">参数</th><th style="width:200px;">值</th><th style="width:50px;">可省略</th><th style="width:100px;">类型</th><th>说明</th></tr>
    </thead>
    <tbody>
        <?php  $_smarty_tpl->tpl_vars['param'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['param']->_loop = false;
 $_smarty_tpl->tpl_vars['param_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['reflection']->value['params']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['param']->key => $_smarty_tpl->tpl_vars['param']->value){
$_smarty_tpl->tpl_vars['param']->_loop = true;
 $_smarty_tpl->tpl_vars['param_name']->value = $_smarty_tpl->tpl_vars['param']->key;
?>
        <tr>
            <td><?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
</td>
            <td><input type="text" <?php if ($_smarty_tpl->tpl_vars['param']->value['omitable']!=1){?>required<?php }?> id="param_<?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
" class="input-xlarge" /></td>
            <td style="text-align:center;"><?php if ($_smarty_tpl->tpl_vars['param']->value['omitable']==1){?>是<?php }?></td>
            <td style="text-align:center"><?php echo $_smarty_tpl->tpl_vars['param']->value['type'];?>
</td>
            <td><?php echo $_smarty_tpl->tpl_vars['param']->value['desc'];?>
</td>
        </tr>
        <?php } ?>
        <tr>
            <td colspan="3" style="text-align:center">填写参数后， 点击按钮执行</td>
            <td style="text-align:center"><a type="button" id="execute" class="btn btn-primary"><i class="icon-chevron-right icon-white"></i> 执行</a></td>
            <td id="execute_info"></td>
        </tr>
    </table>
    <table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>提交内容</th>
            <th>返回内容</th>
    </thead>
        <tr>
            <td style="width:400px;"><pre><?php echo $_smarty_tpl->tpl_vars['reflection']->value['example'];?>
</pre></td>
            <td><textarea id="execute_result" style="width:98%;height:200px;"></textarea></td>
        </tr>
    </tbody>
</table>

</form>
<script type="text/javascript" src='./public/js/ServiceApi.js'></script>
<script type="text/javascript">

var $execute_start_time = 0;

$(function(){
    $('#execute').click(function()
    {
        $execute_start_time = new Date();
        var $params = {};
        <?php  $_smarty_tpl->tpl_vars['param'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['param']->_loop = false;
 $_smarty_tpl->tpl_vars['param_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['reflection']->value['params']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['param']->key => $_smarty_tpl->tpl_vars['param']->value){
$_smarty_tpl->tpl_vars['param']->_loop = true;
 $_smarty_tpl->tpl_vars['param_name']->value = $_smarty_tpl->tpl_vars['param']->key;
?>
        <?php if ($_smarty_tpl->tpl_vars['param']->value['omitable']!=1){?>
        if($('#param_<?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
').val().trim() == '')
        {
            $('#param_<?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
').focus();
            return;
        }
        <?php }?>
        $params.<?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
 = $('#param_<?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
').val();
        <?php } ?>
        ServiceApi.request("<?php echo $_smarty_tpl->tpl_vars['api']->value;?>
.<?php echo $_smarty_tpl->tpl_vars['method']->value;?>
", $params, function($reqSeq, $result, $info, $data)
        {
            var $used_ms = new Date().getTime() - $execute_start_time.getTime();
            $('#execute_info').html("执行耗时: " + $used_ms + " ms");
            var $execute_result = "";
            $execute_result += "seq: " + $reqSeq + "\n";
            $execute_result += "result: " + $result + "\n";
            $execute_result += "info: " + $info + "\n";
            // $execute_result += "data: " + jsonStringify($data, 8) + "\n";
            $execute_result += "data: " + JSON.stringify($data) + "\n";

            $('#execute_result').val($execute_result);
            $('#execute').prop('disabled', false);
        });
        
        $('#execute_info').html('正在执行...');
        $('#execute').prop('disabled', true);
        $('#execute_result').val('正在执行...');
    });
});

function jsonStringify(data,space){
    var seen=[];
    return JSON.stringify(data,function(key,val){
        if(!val||typeof val !=='object'){
            return val;
        }
        if(seen.indexOf(val)!==-1){
            return '[Circular]';
        }
        seen.push(val);
        return val;
    },space);
}
</script><?php }} ?>
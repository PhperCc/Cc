<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:21
         compiled from "/home/system/rdcos/Web/views/configs/index.html" */ ?>
<?php /*%%SmartyHeaderCode:14836384085a79043dd5a792-64936178%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '78c2100c0d8c6888582a27b534c2ad50dd7a5fc2' => 
    array (
      0 => '/home/system/rdcos/Web/views/configs/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '14836384085a79043dd5a792-64936178',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'model' => 0,
    'action' => 0,
    'services' => 0,
    'service' => 0,
    'config_infos' => 0,
    'path' => 0,
    'config' => 0,
    'config_name' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043dde34a6_23632559',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043dde34a6_23632559')) {function content_5a79043dde34a6_23632559($_smarty_tpl) {?><form id="frmItem" class="form-horizontal" method="get">
<input type="hidden" name="um" value="<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
" />
<input type="hidden" name="ua" value="<?php echo $_smarty_tpl->tpl_vars['action']->value;?>
" />
<div class="control-group">
    <span>选择配置文件: </span>
    <select id="config_name" name="config_name">
        <option value="sys">SysConfig</option>
        <?php  $_smarty_tpl->tpl_vars['service'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['service']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['services']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['service']->key => $_smarty_tpl->tpl_vars['service']->value){
$_smarty_tpl->tpl_vars['service']->_loop = true;
?>
        <option value="<?php echo $_smarty_tpl->tpl_vars['service']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['service']->value;?>
</option>
        <?php } ?>
    </select>
    <input class='btn btn-success' type='button' id='btnSave' value='提交保存' />
    <span class="label label-warning">* 除非明确知道修改的后果， 否则不要擅自改动配置， 所有配置升级后不会被覆盖</span>
</div>
<table class="table table-bordered table-hover">
    <thead>
    <tr>
        <th>路径</th>
        <th>说明</th>
        <th>类型</th>
        <th>值</th>
    </tr>
    </thead>
    <?php  $_smarty_tpl->tpl_vars['config'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['config']->_loop = false;
 $_smarty_tpl->tpl_vars['path'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['config_infos']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['config']->key => $_smarty_tpl->tpl_vars['config']->value){
$_smarty_tpl->tpl_vars['config']->_loop = true;
 $_smarty_tpl->tpl_vars['path']->value = $_smarty_tpl->tpl_vars['config']->key;
?>
    <tr>
        <td style='font-weight:bold;'><?php echo $_smarty_tpl->tpl_vars['path']->value;?>
</td>
        <td><?php echo $_smarty_tpl->tpl_vars['config']->value['desc'];?>
</td>
        <td><?php echo $_smarty_tpl->tpl_vars['config']->value['data_type'];?>
</td>
        <td>
        <?php if ($_smarty_tpl->tpl_vars['config']->value['allow_edit']){?>
        <?php if ($_smarty_tpl->tpl_vars['config']->value['data_type']=='string'){?>
            <input type='text' ovalue='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' value='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' path='<?php echo $_smarty_tpl->tpl_vars['path']->value;?>
' data_type='<?php echo $_smarty_tpl->tpl_vars['config']->value['data_type'];?>
' class='config_key input-xlarge' />
        <?php }elseif($_smarty_tpl->tpl_vars['config']->value['data_type']=='int'){?>
            <input type='number' step='any' ovalue='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' value='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' path='<?php echo $_smarty_tpl->tpl_vars['path']->value;?>
' data_type='<?php echo $_smarty_tpl->tpl_vars['config']->value['data_type'];?>
' class='config_key input-xlarge' />
        <?php }elseif($_smarty_tpl->tpl_vars['config']->value['data_type']=='float'){?>
            <input type='number' step='any' ovalue='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' value='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' path='<?php echo $_smarty_tpl->tpl_vars['path']->value;?>
' data_type='<?php echo $_smarty_tpl->tpl_vars['config']->value['data_type'];?>
' class='config_key input-xlarge' />
        <?php }elseif($_smarty_tpl->tpl_vars['config']->value['data_type']=='bool'){?>
            <select ovalue='<?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
' path='<?php echo $_smarty_tpl->tpl_vars['path']->value;?>
' data_type='<?php echo $_smarty_tpl->tpl_vars['config']->value['data_type'];?>
' class='config_key input-small'>
                <option value='1' <?php if ($_smarty_tpl->tpl_vars['config']->value['value']){?>selected<?php }?>>TRUE</option>
                <option value='0' <?php if (!$_smarty_tpl->tpl_vars['config']->value['value']){?>selected<?php }?>>FALSE</option>
            </select>
        <?php }?>
        <span class='help-inline'></span>
        <?php }else{ ?>
        <span class="uneditable-input input-xlarge"><?php echo $_smarty_tpl->tpl_vars['config']->value['value'];?>
</span>
        <?php }?>
        </td>
    </tr>
    <?php } ?>
</table>
</form>

<script type="text/javascript" src='./public/js/ServiceApi.js'></script>
<script type="text/javascript">
$(function(){
    $('#config_name').val('<?php echo $_smarty_tpl->tpl_vars['config_name']->value;?>
');

    $('#config_name').change(function(){
        $('#frmItem').submit();
    });

    $('#btnSave').click(function(){
        var $api_action = 'ServiceConfig.set';
        var $api_params = {};
        var $module = null, $service = null;

        var $config_name = $('#config_name').val();
        if($config_name == 'sys') $api_action = 'SysConfig.set';
        else
        {
            var $module_service = $config_name.split('/');
            $module = $module_service[0];
            $service = $module_service[1];
        }

        $('.config_key').each(function(){
            var $val = $(this).val();
            var $oval = $(this).attr('ovalue');
            if($val == $oval) return;

            var $data_type = $(this).attr('data_type');
            switch($data_type)
            {
                case 'int': $val = parseFloat($val); break;
                case 'float': $val = parseFloat($val); break;
                case 'bool': $val = $val == '1'; break;
            }
            var $api_params = {'module': $module, 'service': $service, 'key': $(this).attr('path'), 'val': $val};
            $(this).next('span').show().html('正在保存...');
            $(this).attr('reqSeq', ServiceApi.request($api_action, $api_params, function($reqSeq, $result, $info, $data){
                var o = $('[reqSeq=' + $reqSeq + ']');
                if($result)
                {
                    o.attr('ovalue', o.val());
                    o.next('span').html('已保存').fadeOut(2000);
                }
                else
                {
                    o.next('span').html('保存失败: ' + $info);
                }
            }));
        });
    });
});
</script><?php }} ?>
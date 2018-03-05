<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:20
         compiled from "/home/system/rdcos/Web/views/services/index.html" */ ?>
<?php /*%%SmartyHeaderCode:12634608555a79043c2177c2-14521447%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'e7a6bba1aafe0e9008243d01a6698869e0cc4595' => 
    array (
      0 => '/home/system/rdcos/Web/views/services/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '12634608555a79043c2177c2-14521447',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'starters' => 0,
    'starter_name' => 0,
    'services' => 0,
    'service' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043c2398b5_48453048',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043c2398b5_48453048')) {function content_5a79043c2398b5_48453048($_smarty_tpl) {?><h1>服务列表</h1>
<table class="table table-bordered table-hover">
    <tr>
        <th>Name</th>
        <th>protocol</th>
        <th>Description</th>
    </tr>
    <?php  $_smarty_tpl->tpl_vars['services'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['services']->_loop = false;
 $_smarty_tpl->tpl_vars['starter_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['starters']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['services']->key => $_smarty_tpl->tpl_vars['services']->value){
$_smarty_tpl->tpl_vars['services']->_loop = true;
 $_smarty_tpl->tpl_vars['starter_name']->value = $_smarty_tpl->tpl_vars['services']->key;
?>
    <tr>
        <td colspan="4"><b><?php echo $_smarty_tpl->tpl_vars['starter_name']->value;?>
</b></td>
    </tr>
    <?php  $_smarty_tpl->tpl_vars['service'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['service']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['services']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['service']->key => $_smarty_tpl->tpl_vars['service']->value){
$_smarty_tpl->tpl_vars['service']->_loop = true;
?>
    <tr>
        <td><?php echo $_smarty_tpl->tpl_vars['service']->value['name'];?>
</td>
        <td><?php echo $_smarty_tpl->tpl_vars['service']->value['protocol'];?>
</td>
        <td><?php echo $_smarty_tpl->tpl_vars['service']->value['desc'];?>
</td>
    </tr>
    <?php } ?>
    <?php } ?>
</table>
<?php }} ?>
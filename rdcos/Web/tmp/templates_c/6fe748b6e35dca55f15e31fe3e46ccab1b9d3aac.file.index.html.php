<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:22
         compiled from "/home/system/rdcos/Web/views/syslog/index.html" */ ?>
<?php /*%%SmartyHeaderCode:13185006815a79043eb4ca39-46081920%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '6fe748b6e35dca55f15e31fe3e46ccab1b9d3aac' => 
    array (
      0 => '/home/system/rdcos/Web/views/syslog/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '13185006815a79043eb4ca39-46081920',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'model' => 0,
    'action' => 0,
    'log_files' => 0,
    'file' => 0,
    'file_name' => 0,
    'lines' => 0,
    'line' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043eb7a432_35612744',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043eb7a432_35612744')) {function content_5a79043eb7a432_35612744($_smarty_tpl) {?><form id="frmItem" class="form-search" method="get">
    <input type="hidden" name="um" value="<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
" />
    <input type="hidden" name="ua" value="<?php echo $_smarty_tpl->tpl_vars['action']->value;?>
" />
    <select id="file_name" name="file_name"  class="input-xlarge">
    <?php  $_smarty_tpl->tpl_vars['file'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['file']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['log_files']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['file']->key => $_smarty_tpl->tpl_vars['file']->value){
$_smarty_tpl->tpl_vars['file']->_loop = true;
?>
    <option value="<?php echo $_smarty_tpl->tpl_vars['file']->value['name'];?>
" <?php if ($_smarty_tpl->tpl_vars['file_name']->value==$_smarty_tpl->tpl_vars['file']->value['name']){?>selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['file']->value['name'];?>
 <?php echo $_smarty_tpl->tpl_vars['file']->value['size'];?>
</option>
    <?php } ?>
    </select>

    <input type="hidden" id="action" name="action" value="" />
    <input type="button" value="删除" id="delete" title="删除这个文件" class="btn btn btn-danger"/>
</form>
<script type="text/javascript">
$('#file_name').change(function(){
    $('#frmItem').submit();
});
$('#delete').click(function(){
    $('#action').val('delete');
    $('#frmItem').submit();
});
</script>

<textarea class="input-xxxlarge" style="height:460px;">
<?php  $_smarty_tpl->tpl_vars['line'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['line']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['lines']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['line']->key => $_smarty_tpl->tpl_vars['line']->value){
$_smarty_tpl->tpl_vars['line']->_loop = true;
?><?php echo $_smarty_tpl->tpl_vars['line']->value;?>
<?php } ?>
</textarea>
<?php }} ?>
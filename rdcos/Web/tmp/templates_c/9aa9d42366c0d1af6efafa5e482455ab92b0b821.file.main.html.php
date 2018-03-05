<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:10
         compiled from "/home/system/rdcos/Web/views/layout/main.html" */ ?>
<?php /*%%SmartyHeaderCode:6204973305a790432cd5af2-23364678%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '9aa9d42366c0d1af6efafa5e482455ab92b0b821' => 
    array (
      0 => '/home/system/rdcos/Web/views/layout/main.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '6204973305a790432cd5af2-23364678',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'title' => 0,
    'hide_menu' => 0,
    'system_config' => 0,
    'menu' => 0,
    'timeused' => 0,
    'model' => 0,
    'action' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a790432d57b36_93140634',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a790432d57b36_93140634')) {function content_5a790432d57b36_93140634($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/home/system/rdcos/Web/Libs/Smarty/plugins/modifier.date_format.php';
?><!DOCTYPE html>
<html>
<head>
<title><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="./public/css/bootstrap.css" rel="stylesheet" media="screen" />
<link rel="stylesheet" href="./public/fonts/font-awesome/css/font-awesome.min.css"> 
<style type="text/css">
body, html{padding:0px; margin:0px; min-height:100%;}
body {padding-top: 60px;}
.sidebar-nav {padding: 9px 0;}

@media (max-width: 980px) {
    /* Enable use of floated navbar text */
    .navbar-text.pull-right {
        float: none;
        padding-left: 5px;
        padding-right: 5px;
    }
}
</style>
<link href="./public/css/bootstrap-responsive.css" rel="stylesheet" />
<link href="./public/css/docs-cn.css" rel="stylesheet" media="screen" />
<script src="./public/js/jquery.min.js"></script>
<script src="./public/js/bootstrap.min.js"></script>
<!--[if lt IE 9]>
    <script src="./public/js/html5shiv.js"></script>
<![endif]-->
<!--[if lte IE 6]>
    <link rel="stylesheet" type="text/css" href="./public/css/bootstrap-ie6.min.css">
    <link rel="stylesheet" type="text/css" href="./public/css/bootstrap-ie6-2.css">
<![endif]-->
<script src="./public/js/public.js" type="text/javascript"></script>
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
		<div class="container-fluid">
			<a class="brand" href="./"><?php echo @constant('SYS_NAME');?>
 VER <?php echo @constant('SYS_VERSION');?>
</a>
			<div class="nav-collapse collapse">
                <ul class="nav pull-right">
                    <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-user icon-white"></i> admin <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a href="#myModal" data-toggle="modal"><i class="icon-edit"></i>修改密码</a></li>
                        <li class="divider"></li>
                        <li><a href="./sys..logout"><i class="icon-off"></i>退出登录</a></li>
                    </ul>
                    </li>
                </ul>
			</div><!--/.nav-collapse -->
		</div>
		</div>
	</div>

	<div class="container-fluid">

	<!-- Docs nav ================================================== -->
	<div class="row-fluid">
        <?php if ($_smarty_tpl->tpl_vars['hide_menu']->value==0){?>
		<div class="span2 bs-docs-sidebar">
			<ul class="nav nav-list bs-docs-sidenav" id="list_menu">
                <?php  $_smarty_tpl->tpl_vars['menu'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['menu']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['system_config']->value['menus']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['menu']->key => $_smarty_tpl->tpl_vars['menu']->value){
$_smarty_tpl->tpl_vars['menu']->_loop = true;
?>
                    <li id="nav_<?php echo $_smarty_tpl->tpl_vars['menu']->value['id'];?>
"><a href="<?php echo $_smarty_tpl->tpl_vars['menu']->value['url'];?>
" target="<?php echo $_smarty_tpl->tpl_vars['menu']->value['target'];?>
"><i class="icon-chevron-right"></i><?php echo $_smarty_tpl->tpl_vars['menu']->value['text'];?>
</a></li>
                <?php } ?>
			</ul>
		</div><!--/span-->
        <?php }?>
		<div class="span10">
		<div style="min-height:500px;" id="main_content">
		<?php echo $_smarty_tpl->getSubTemplate (((string)$_smarty_tpl->tpl_vars['action_view']->value), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array(), 0);?>

		</div>
		</div>
	</div>

	<!-- Footer ================================================== -->
		<footer>
		<hr />
		<p>

		<?php echo @constant('SYS_NAME');?>
 VER <?php echo @constant('SYS_VERSION');?>

        <?php if (@constant('COPYRIGHT')!=''){?> <em class="muted">版权所有: </em><?php echo @constant('COPYRIGHT');?>
 &emsp;<?php }?>
        <?php if (@constant('POWEREDBY')!=''){?> <em class="muted">powered by <?php echo @constant('POWEREDBY');?>
</em> &emsp;<?php }?>
		<span style="color:#aaa;">
        系统时间: <?php echo smarty_modifier_date_format(time(),'%Y-%m-%d %H:%M:%S');?>

        执行耗时: <?php echo $_smarty_tpl->tpl_vars['timeused']->value;?>
 ms
        </span> <br />
		</p>
		</footer>

	</div>

	<!-- Modal chanage password -->
	<div id="myModal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel">修改密码</h3>
		</div>
		<div class="modal-body">
		<form id="frmChanagePassword" action="./sys..change_password" method="post" class="form-horizontal">
			<div class="control-group">
			<label class="control-label" for="oldPassword">原始密码: </label>
			<div class="controls">
				<input name="oldPassword" id="oldPassword" type="password" required placeholder="原始密码" />
			</div>
			</div>
			<div class="control-group">
			<label class="control-label" for="newPassword1">新密码: </label>
			<div class="controls">
				<input name="newPassword1" id="newPassword1" type="password" required placeholder="设置新密码" />
			</div>
			</div>
			<div class="control-group">
			<label class="control-label" for="newPassword2">重复输入: </label>
			<div class="controls">
				<input name="newPassword2" id="newPassword2" type="password" required placeholder="再输入一次新密码" />
			</div>
			</div>
		</form>
			<div id="spanPwdError" class="alert alert-error hide" style="margin-top:20px;">
				<strong>Alert:</strong> <span>原始密码错误!</span>
			</div>
			<div id="spanPwdSuccess" class="alert alert-success hide" style="margin-top:20px;">
				<strong>Info:</strong> <span>修改密码成功!</span>
			</div>
		</div>
		<div class="modal-footer">
		<button class="btn btn-primary" data-loading-text="正在提交..." onclick="$('#frmChanagePassword').submit();">Submit</button>
		<button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
		</div>
	</div>

	<!-- Modal confirm -->
	<div id="divMsgConfirm" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3>操作确认</h3>
		</div>
		<div class="modal-body">
		<img src="./public/img/alert.png" /> <span></span>
		</div>
		<div class="modal-footer">
		<a class="btn btn-primary">确定</a>
		<button class="btn" data-dismiss="modal" aria-hidden="true">取消</button>
		</div>
	</div>
	
	<!-- Modal alert -->
	<div id="divMsgAlert" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3>提示</h3>
		</div>
		<div class="modal-body">
		<img src="./public/img/alert.png" /> <span></span>
		</div>
		<div class="modal-footer">
		<button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">确定</button>
		</div>
	</div>
<script type="text/javascript">
if($('#nav_<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
_<?php echo $_smarty_tpl->tpl_vars['action']->value;?>
').length > 0)
{
    $('#nav_<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
_<?php echo $_smarty_tpl->tpl_vars['action']->value;?>
').addClass('active');
}
else if($('#nav_<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
').length > 0)
{
    $('#nav_<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
').addClass('active');
}
else
{
    $('#list_menu').append('<li><a href=""><i class="icon-chevron-right"></i><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</a></li>');
    $('#list_menu li:last').addClass('active');
}
</script>
<!--[if lte IE 6]>
    <script type="text/javascript" src="./js/bootstrap-ie.js"></script>
<![endif]-->
</body>
</html>
<?php }} ?>
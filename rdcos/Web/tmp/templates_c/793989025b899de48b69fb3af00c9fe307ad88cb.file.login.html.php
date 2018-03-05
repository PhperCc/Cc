<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:22:12
         compiled from "/home/system/rdcos/Web/views/sys/login.html" */ ?>
<?php /*%%SmartyHeaderCode:10224841905a790344706798-24158977%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '793989025b899de48b69fb3af00c9fe307ad88cb' => 
    array (
      0 => '/home/system/rdcos/Web/views/sys/login.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '10224841905a790344706798-24158977',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'error' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a7903447ab6f4_93963148',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a7903447ab6f4_93963148')) {function content_5a7903447ab6f4_93963148($_smarty_tpl) {?><!DOCTYPE html>
<html>
<head>
<title><?php echo @constant('SYS_NAME');?>
 | 登录</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link href="./public/css/bootstrap.min.css" rel="stylesheet" media="screen" />
<link href="./public/css/bootstrap-responsive.css" rel="stylesheet" />
<style type="text/css">
body {
padding: 40px;
padding-top: 100px;
background-color: #f5f5f5;
}

.form-entry 
{
max-width: 300px;
padding: 19px 29px 29px;
margin: 0 auto 20px;
background-color: #fff;
border: 1px solid #e5e5e5;
-webkit-border-radius: 5px;
    -moz-border-radius: 5px;
        border-radius: 5px;
-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
        box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.form-entry .form-entry-heading,
.form-entry .checkbox {
margin-bottom: 10px;
}
.form-entry input[type="text"],
.form-entry input[type="password"] {
font-size: 16px;
height: auto;
margin-bottom: 15px;
padding: 7px 9px;
}
</style>
</head>
<body>
	<div class="container">
		<form class="form-entry" method="post" action="?uc=sys&ua=login">
			<h3 class="form-entry-heading"><?php echo @constant('SYS_NAME');?>
 | 登录</h3>
			用户名 : <input name="account" type="text" class="input-block-level " required autofocus placeholder="用户名" />
			密码 : <input name="password" type="password" class="input-block-level" required placeholder="密码" />
			<button class="btn btn-primary btn-large " style="margin:10px 0 0 225px"  type="submit">登录</button>
            <?php if ($_smarty_tpl->tpl_vars['error']->value!=''){?>
			<div class="alert alert-error" style="margin-top:20px;">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<strong>alarm:</strong> <?php echo $_smarty_tpl->tpl_vars['error']->value;?>

			</div>
            <?php }?>
		</form>
	</div> <!-- /container -->

	<script src="./public/js/jquery.min.js"></script>
	<script src="./public/js/bootstrap.min.js"></script>
	<!--[if lt IE 9]>
	  <script src="./js/html5shiv.js"></script>
	<![endif]-->
</body>
</html>
<?php }} ?>
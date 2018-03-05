<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:24
         compiled from "/home/system/rdcos/Web/views/apidoc/index.html" */ ?>
<?php /*%%SmartyHeaderCode:9214225675a790440504e02-99312824%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'f63bc82df76b456b61956c63952817c146aa3271' => 
    array (
      0 => '/home/system/rdcos/Web/views/apidoc/index.html',
      1 => 1517878362,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '9214225675a790440504e02-99312824',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'title' => 0,
    'handler_list' => 0,
    'handler_name' => 0,
    'handler' => 0,
    'method_name' => 0,
    'method' => 0,
    'param_name' => 0,
    'param' => 0,
    'return_name' => 0,
    'return' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79044058c5f3_10621409',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79044058c5f3_10621409')) {function content_5a79044058c5f3_10621409($_smarty_tpl) {?><ul class="breadcrumb">
	<li><a href="./">主页</a> <span class="divider">/</span></li>
	<li class="active"> <?php echo $_smarty_tpl->tpl_vars['title']->value;?>
 </li>
    &nbsp;&nbsp;&nbsp;&nbsp;
    <div class="btn-group">
        <a class="btn btn-primary btn-small dropdown-toggle" data-toggle="dropdown" href="#">文档快速导航 <span class="caret"></span></a>
        <ul class="dropdown-menu">
        <?php  $_smarty_tpl->tpl_vars['handler'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['handler']->_loop = false;
 $_smarty_tpl->tpl_vars['handler_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['handler_list']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['handler']->key => $_smarty_tpl->tpl_vars['handler']->value){
$_smarty_tpl->tpl_vars['handler']->_loop = true;
 $_smarty_tpl->tpl_vars['handler_name']->value = $_smarty_tpl->tpl_vars['handler']->key;
?>
            <li class="dropdown-submenu">
                <a href="#handler_<?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
<br /><?php echo $_smarty_tpl->tpl_vars['handler']->value['desc'];?>
</a>
                <ul class="dropdown-menu bs-docs-sidenav">
                <?php  $_smarty_tpl->tpl_vars['method'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['method']->_loop = false;
 $_smarty_tpl->tpl_vars['method_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['handler']->value['methods']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['method']->key => $_smarty_tpl->tpl_vars['method']->value){
$_smarty_tpl->tpl_vars['method']->_loop = true;
 $_smarty_tpl->tpl_vars['method_name']->value = $_smarty_tpl->tpl_vars['method']->key;
?>
                    <li><a href="#method_<?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
_<?php echo $_smarty_tpl->tpl_vars['method_name']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['method']->value['sign'];?>
<br /><?php echo $_smarty_tpl->tpl_vars['method']->value['desc'];?>
</a></li>
                <?php } ?>
                </ul>
            </li>
        <?php } ?>
        </ul>
    </div>
</ul>

<section>
    <a name="summary"></a>
    <div class="page-header"><h2>概述</h2></div>
    <h4>接口地址</h4>
    <table class="table table-bordered table-hover">
        <thead>
            <tr><th style="width:150px;">协议类型</th><th>接口地址</th></tr>
        </thead>
        <tbody>
            <tr><td>HTTP</td><td>http://<span class="host"></span>:1226/?req=</td></tr>
            <tr><td>Websocket</td><td>websocket://<span class="host"></span>:1228</td></tr>
        </tbody>
    </table>

    <h4>协议格式</h4>
    <table class="table table-bordered table-hover">
        <tbody>
            <tr>
                <td colspan="2">
                    <p>1. 协议的调用方式为 请求 / 返回 的问答式交互， 协议的正常交互内容为基于标准 JSON 格式组织的文本内容</p>
                    <p>2. 当请求指令格式不正确时， 服务端将返回异常信息</p>
                    <p>3. 基于 HTTP 协议的 API 调用， 将请求指令使用 GET 方式提交， 请求指令的键名为 <b>req</b></p>
                    <p>4. 基于 Websocket 协议的 API 调用， 直接 Send / Recive 交互。</p>
                </td>
            </tr>
            <tr>
                <td style="width:150px;">请求指令格式</td>
                <td>
<pre>
{
    "seq":1482852377,           // 请求指令的序列号， 值必须为大于0的正整数， 由api调用方自行维护。
    "action":"Endpoint.add",    // 请求所调用的api方法
    "params":{                  // 请求所调用的api方法参数， 参数名由具体方法定义
        "uid":"endpoint_uid"
    }
}
</pre>
                </td>
            </tr>
            <tr>
                <td>返回指令格式</td>
                <td>
<pre>
{
    "seq":1482852377,       // 请求指令的序列号， 原样返回调用方
    "result":true,          // api方法执行结果， true 为成功， false为失败。
    "info":"",              // api方法执行返回的文本消息， 参数值及意义由具体方法定义
    "data":null             // api方法执行返回的数据内容， 参数值及意义由具体方法定义
}
</pre>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<?php  $_smarty_tpl->tpl_vars['handler'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['handler']->_loop = false;
 $_smarty_tpl->tpl_vars['handler_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['handler_list']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['handler']->key => $_smarty_tpl->tpl_vars['handler']->value){
$_smarty_tpl->tpl_vars['handler']->_loop = true;
 $_smarty_tpl->tpl_vars['handler_name']->value = $_smarty_tpl->tpl_vars['handler']->key;
?>
<a name="handler_<?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
"></a>
<section>
    <div class="page-header"><h2><?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
: <?php echo $_smarty_tpl->tpl_vars['handler']->value['desc'];?>
</h2></div>
    <?php  $_smarty_tpl->tpl_vars['method'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['method']->_loop = false;
 $_smarty_tpl->tpl_vars['method_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['handler']->value['methods']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['method']->key => $_smarty_tpl->tpl_vars['method']->value){
$_smarty_tpl->tpl_vars['method']->_loop = true;
 $_smarty_tpl->tpl_vars['method_name']->value = $_smarty_tpl->tpl_vars['method']->key;
?>
    <a name="method_<?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
_<?php echo $_smarty_tpl->tpl_vars['method_name']->value;?>
"></a>
    <h4><?php echo $_smarty_tpl->tpl_vars['method']->value['desc'];?>
</h4>
    <i><?php echo $_smarty_tpl->tpl_vars['method']->value['sign'];?>
</i> <a target="_blank" href="./?um=apitest&api=<?php echo $_smarty_tpl->tpl_vars['handler_name']->value;?>
&method=<?php echo $_smarty_tpl->tpl_vars['method_name']->value;?>
">点击测试</a>
    <table class="table table-bordered table-hover">
        <thead>
            <tr><th style="width:150px;">参数</th><th style="width:50px;">可省略</th><th style="width:100px;">类型</th><th>说明</th></tr>
        </thead>
        <tbody>
            <?php  $_smarty_tpl->tpl_vars['param'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['param']->_loop = false;
 $_smarty_tpl->tpl_vars['param_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['method']->value['params']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['param']->key => $_smarty_tpl->tpl_vars['param']->value){
$_smarty_tpl->tpl_vars['param']->_loop = true;
 $_smarty_tpl->tpl_vars['param_name']->value = $_smarty_tpl->tpl_vars['param']->key;
?>
            <tr>
                <td><?php echo $_smarty_tpl->tpl_vars['param_name']->value;?>
</td>
                <td style="text-align:center;"><?php if ($_smarty_tpl->tpl_vars['param']->value['omitable']==1){?>是<?php }?></td>
                <td><?php echo $_smarty_tpl->tpl_vars['param']->value['type'];?>
</td>
                <td><?php echo $_smarty_tpl->tpl_vars['param']->value['desc'];?>
</td>
            </tr>
            <?php } ?>
            <?php  $_smarty_tpl->tpl_vars['return'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['return']->_loop = false;
 $_smarty_tpl->tpl_vars['return_name'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['method']->value['returns']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['return']->key => $_smarty_tpl->tpl_vars['return']->value){
$_smarty_tpl->tpl_vars['return']->_loop = true;
 $_smarty_tpl->tpl_vars['return_name']->value = $_smarty_tpl->tpl_vars['return']->key;
?>
            <tr><td><b class="text-error">return</b> <?php echo $_smarty_tpl->tpl_vars['return_name']->value;?>
</td><td></td><td><?php echo $_smarty_tpl->tpl_vars['return']->value['type'];?>
</td><td><?php echo $_smarty_tpl->tpl_vars['return']->value['desc'];?>
</td></tr>
            <?php } ?>
            <tr><td><b>调用示例:</b></td><td colspan="3"><pre><?php echo $_smarty_tpl->tpl_vars['method']->value['example'];?>
</pre></td></tr>
        </tbody>
    </table>
    <?php } ?>
</section>
<?php } ?>

<script type="text/javascript">
$(function(){
    $('.host').html(window.location.host);
});
</script>
<?php }} ?>
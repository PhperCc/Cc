<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:10
         compiled from "/home/system/rdcos/Web/views/sys/index.html" */ ?>
<?php /*%%SmartyHeaderCode:7556476245a790432d5b538-35264151%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '8c6f876269de2d3b2470fd0328d1bbc0d4db8113' => 
    array (
      0 => '/home/system/rdcos/Web/views/sys/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7556476245a790432d5b538-35264151',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'total_space' => 0,
    'free_space' => 0,
    'used_percent' => 0,
    'memory_usage' => 0,
    'memory_peak_usage' => 0,
    'sys_start_time' => 0,
    'sys_used_time' => 0,
    'sys_useage' => 0,
    'sys_reboot_count' => 0,
    'version_history' => 0,
    'version' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a790432d8eee6_62775525',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a790432d8eee6_62775525')) {function content_5a790432d8eee6_62775525($_smarty_tpl) {?><?php if (!is_callable('smarty_modifier_date_format')) include '/home/system/rdcos/Web/Libs/Smarty/plugins/modifier.date_format.php';
?><form class="form-horizontal">
<fieldset>
<legend>概况</legend>
    <div class="control-group">
        <label for="customer_name" class="control-label">设备编号: </label>
        <div class="controls">
            <span class="uneditable-input input-xlarge" id="spanUid"></span>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">设备名称: </label>
        <div class="controls input-append" style="margin-left:20px;">
            <span id="spanDeviceName" class="uneditable-input input-xlarge"></span>
            <input type="text" id="txtDeviceName" class="input-xlarge" style="display:none;" />
            <button id="btnDeviceNameEdit" class="btn btn-primary"> 修改 </button>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">产品类型: </label>
        <div class="controls input-append" style="margin-left:20px;">
            <span id="spanProductType" class="uneditable-input input-xlarge"></span>
            <select id="selProductType" class="input-xlarge" style="display:none;"/></select>
            <button id="btnProductTypeChange" class="btn btn-primary"> 切换 </button>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">磁盘空间: </label>
        <div class="controls">
            <span class="uneditable-input input-xxlarge">
                总计: <?php echo $_smarty_tpl->tpl_vars['total_space']->value;?>
 / 剩余: <?php echo $_smarty_tpl->tpl_vars['free_space']->value;?>
  [ 已使用 <?php echo $_smarty_tpl->tpl_vars['used_percent']->value;?>
 % ]
            </span>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">内存占用: </label>
        <div class="controls">
            <span class="uneditable-input input-xxlarge">
                当前: <?php echo $_smarty_tpl->tpl_vars['memory_usage']->value;?>
 / 峰值: <?php echo $_smarty_tpl->tpl_vars['memory_peak_usage']->value;?>

            </span>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">运行信息: </label>
        <div class="controls">
            <span class="uneditable-input input-xxlarge">
                运行时间: <?php echo $_smarty_tpl->tpl_vars['sys_start_time']->value;?>
 / 空闲时间: <?php echo $_smarty_tpl->tpl_vars['sys_used_time']->value;?>
  [ 空闲率: <?php echo $_smarty_tpl->tpl_vars['sys_useage']->value;?>
 % ], 启动累计 <?php echo $_smarty_tpl->tpl_vars['sys_reboot_count']->value;?>
 次
            </span>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">系统时间: </label>
        <div class="controls">
            <span class="uneditable-input input-xxlarge">
                <?php echo smarty_modifier_date_format(time(),'%Y-%m-%d %H:%M:%S');?>

            </span>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">版本信息: </label>
        <div class="controls">
            app: <button class="btn btn-link" id="btnSysVersion"><span id="spanSysVersion"></span></button>
            core: <a class="btn btn-link"><span id="spanCoreVersion"></span></a> 
            release: <a class="btn btn-link"><span id="spanReleaseVersion"></span></a> 
            build: <a class="btn btn-link"><span id="spanBuildTime"></span></a>
        </div>
    </div>
	<div class="control-group">
        <label for="customer_name" class="control-label">更多信息: </label>
        <div class="controls">
            <a class="btn-link uneditable-input" href="./public/tz.php" target="_blank">查看详情</a>
        </div>
    </div>
</fieldset>
<fieldset>
<legend>操作</legend>
    <div class="control-group">
        <label for="customer_name" class="control-label">操作系统: </label>
        <div class="controls">
            <a type="button" id="os_reboot" class="btn btn-primary"><i class="icon-off icon-white"></i> 重启设备</a>
            <a type="button" id="core_restart" class="btn btn-primary"><i class="icon-repeat icon-white"></i> 重启内核</a>
            <a type="button" id="service_restart" class="btn btn-primary"><i class="icon-repeat icon-white"></i> 重启服务</a>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name" class="control-label">开发者工具: </label>
        <div class="controls">
            <a type="button" class="btn btn-primary" href="/sys..newapp" id="add_app"><i class="icon-plus icon-white"></i> 新建应用</a>
            <a type="button" class="btn btn-primary" target="_blank" href="/IDE.php"><i class="icon-wrench icon-white"></i> 管理代码</a>
            <a type="button" id="service_update" class="btn btn-primary"><i class="icon-upload icon-white"></i> 离线升级</a>
             
        </div>
    </div>
</fieldset>
</form>

<div id="modalVersion" class="modal hide fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 >版本更新历史</h3>
    </div>
    <div class="modal-body">
        <?php  $_smarty_tpl->tpl_vars['version'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['version']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['version_history']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['version']->key => $_smarty_tpl->tpl_vars['version']->value){
$_smarty_tpl->tpl_vars['version']->_loop = true;
?>
        <div><?php echo $_smarty_tpl->tpl_vars['version']->value['time'];?>
&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $_smarty_tpl->tpl_vars['version']->value['version'];?>
</div>
        <?php } ?>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
    </div>
</div>

<div id="modalWaitPing" class="modal hide fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-header">
        <h3>正在执行操作...</h3>
    </div>
    <div class="modal-body">
        <p><span class="label label-warning">请等待，操作完成后本页会自动刷新</span></p>
    </div>
</div>

<!-- Modal update -->
<div id="myUpdate" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="myUpdateLabel" aria-hidden="true">
    <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myUpdateLabel">离线升级</h3>
    </div>
    <div class="modal-body">
    <form id="frmUpdate" action="./sys..update" method="post" enctype="multipart/form-data" class="form-horizontal">
        <div class="control-group">
        <label class="control-label">模块类型: </label>
        <div class="controls">
            <select name="type">
                <option value="plugin">插件</option>
                <option value="app">应用</option>
                <option value="core">内核</option>
                <option value="update">升级程序</option>
                <option value="web">Web</option>
            </select>
        </div>
        </div>
        <div class="control-group">
        <label class="control-label">立即应用: </label>
        <div class="controls">
            <select name="reload">
                <option value="true">是</option>
                <option value="false">否</option>
            </select>
        </div>
        </div>
        <div class="control-group">
        <label class="control-label" >上传升级包: </label>
        <div class="controls">
            <input name="file" type="file" id="upfile"/>
        </div>
        </div>
    </form>
    </div>
    <div class="modal-footer">
    <button class="btn btn-primary" data-loading-text="正在提交..." onclick="if($('#upfile').val()!=''){$('#frmUpdate').submit();}">提交</button>
    <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
    </div>
</div>

<script type="text/javascript" src="./public/js/ServiceApi.js"></script>
<script type="text/javascript">
$(function(){
    ServiceApi.request("SysConfig.get", {"key": ""}, function(seq, result, info, config){
        if(result === true)
        {
            if(config.hasOwnProperty("device_name"))
            {
                $('#spanDeviceName').text(config.device_name);
                $('#txtDeviceName').val(config.device_name); 
            }
            
        }
    });

    ServiceApi.request("Sys.getUid", {}, function(seq, result, info, uid){
        if(result === true)
        {
            $('#spanUid').text(uid);
        }
    });

    ServiceApi.request("Sys.getVersionInfo", {}, function(seq, result, info, versionInfo){
        if(result === true)
        {
            $('#spanSysVersion').text(versionInfo.app);
            $('#spanCoreVersion').text(versionInfo.core);
            $('#spanReleaseVersion').text(versionInfo.release);
            $('#spanBuildTime').text(versionInfo.build_time);
        }
    });

    ServiceApi.request("Services.get_modules", {}, function(seq, result, info, modules){
        if(result === true)
        {
            for(module_name in modules)
            {
                if(module_name == 'Core') continue;
                $('#selProductType').append('<option value="' + module_name + '">' + module_name + ' - ' + modules[module_name] + '</option>');
            }

            ServiceApi.request("SysConfig.get", {"key": "product"}, function(seq, result, info, product){
                if(result === true)
                {
                    $('#selProductType').val(product);
                    $('#spanProductType').text($('#selProductType option:selected').text());
                }
            });
        }
    });

    $('#btnSysVersion').click(function(){
        $('#modalVersion').modal();
        return false;
    });

    $('#os_reboot').click(function(){
        $(this).addClass('disabled');
        $('#modalWaitPing').find('h3').html("设备正在重启...")
        $('#modalWaitPing').modal();

        ServiceApi.request("Sys.reboot",{});
        window.setTimeout(ping, 2000);
    });

    $('#core_restart').click(function(){
        $(this).addClass('disabled');
        $('#modalWaitPing').find('h3').html("服务正在重启...")
        $('#modalWaitPing').modal();

        ServiceApi.request("Sys.restart",{type:'core'});
        window.setTimeout(ping, 5000);
    });

    $('#service_restart').click(function(){
        $(this).addClass('disabled');
        $('#modalWaitPing').find('h3').html("服务正在重启...")
        $('#modalWaitPing').modal();

        ServiceApi.request("Sys.restart",{type:'main'});
        window.setTimeout(ping, 5000);
    });
    $('#service_update').click(function(){
        $('#myUpdate').modal();
        //window.setTimeout(ping, 5000);
    });

    $('#btnProductTypeChange').click(function(){
        var stage = $(this).attr('stage');
        if(!stage || stage == '') stage = 'edit';
        else if(stage == 'edit') stage = 'save';

        $(this).attr('stage', stage);

        if(stage == 'edit')
        {
            $('#spanProductType').hide();
            $('#selProductType').show();

            $(this).text(" 保存 ");
        }
        else if(stage == 'save')
        {
            var new_value = $('#selProductType').val();
            $('#spanProductType').text($('#selProductType option:selected').text()).show();
            $('#selProductType').hide();

            $(this).text(' 正在提交... ').addClass('disabled');
            ServiceApi.request("SysConfig.set", {"key": "product", "val": new_value}, function(seq, result, info, data){
                $('#btnProductTypeChange').text(' 切换 ').attr('stage', '').removeClass('disabled');
                if(result !== true)
                {
                    alert('保存失败: ' + info);
                }
            });
        }

        return false;
    });

    $('#btnDeviceNameEdit').click(function(){
        var stage = $(this).attr('stage');
        if(!stage || stage == '') stage = 'edit';
        else if(stage == 'edit') stage = 'save';

        $(this).attr('stage', stage);

        if(stage == 'edit')
        {
            $('#spanDeviceName').hide();
            $('#txtDeviceName').show();

            $(this).text(" 保存 ");
        }
        else if(stage == 'save')
        {
            var new_name = $('#txtDeviceName').val();
            $('#spanDeviceName').text(new_name).show();
            $('#txtDeviceName').hide();

            $(this).text(' 正在提交... ').addClass('disabled');
            ServiceApi.request("SysConfig.set", {"key": "device_name", "val": new_name}, function(seq, result, info, data){
                $('#btnDeviceNameEdit').text(' 修改 ').attr('stage', '').removeClass('disabled');
                if(result !== true)
                {
                    alert('保存失败: ' + info);
                }
            });
        }
        return false;
    });
});


function ping()
{
    $.ajax({
        url: "?um=sys&ua=ping",
        timeout: 1000,
        success: function(){
            window.location.href = window.location.href;
        },
        error: ping
    });
}
</script><?php }} ?>
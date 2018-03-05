<?php /* Smarty version Smarty-3.1.13, created on 2013-01-01 12:13:38
         compiled from "/home/system/rdcos/Web/views/sys/newapp.html" */ ?>
<?php /*%%SmartyHeaderCode:84750433050e262723b9591-36976125%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '1ea34fa2da92f9d4b2caa7901fba25f630ad45c2' => 
    array (
      0 => '/home/system/rdcos/Web/views/sys/newapp.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '84750433050e262723b9591-36976125',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'port' => 0,
    'k' => 0,
    'v' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_50e2627241a0d1_59474358',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_50e2627241a0d1_59474358')) {function content_50e2627241a0d1_59474358($_smarty_tpl) {?><div id="myAlert" class="alert alert-error" style="display: none;">
    <a class="close" data-dismiss="alert">×</a>
    <strong>Error!</strong><span id="errmsg"></span>
</div>
<form class="form-horizontal" method="post" action="">
<fieldset>
<legend>新建应用</legend>
    <div class="control-group">
        <label for="customer_name" class="control-label">应用名称: </label>
        <div class="controls">
            <input type="text" name="appname" class="input-xlarge" id="addname"/>
        </div>
    </div>
    <div class="control-group">
        <label for="customer_name"  class="control-label">应用描述: </label>
        <div class="controls">
            <input type="text" name="appdesc" class="input-xlarge"/>
            <a type="button" class="btn btn-primary" id="add_sensor"><i class="icon-plus icon-white"></i> 添加传感器</a> 
        </div>
    </div>
    <div class="control-group sensor-data">
        <div class="control-group">
            <label for="customer_name" class="control-label" style="font-weight: bold; text-align: center;">传感器</label>
            <div class="controls " style="padding-left:288px;"><a type="button" class="delbut btn  btn-danger"><i class="icon-trash icon-white"></i> 删除</a></div>
        </div>
        <div class="control-group">
            <label for="customer_name" class="control-label">名称: </label>
            <div class="controls">
                <input type="text" name="Sname[]" class="input-xlarge"/>
            </div>
        </div>
        <div class="control-group">
            <label for="customer_name" class="control-label">描述: </label>
            <div class="controls">
                <input type="text" name="Sdesc[]" class="input-xlarge"/>
            </div>
        </div>
        <div class="control-group">
            <label for="customer_name" class="control-label">Port类型: </label>
            <div class="controls">
                <select class="input-xlarge portType" name="SportType[]" style="width: 284px;" />
                <option value=""></option>
                <?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_smarty_tpl->tpl_vars['k'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['port']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value){
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['k']->value = $_smarty_tpl->tpl_vars['v']->key;
?>
                <option value="<?php echo $_smarty_tpl->tpl_vars['k']->value;?>
" data-opt='<?php echo $_smarty_tpl->tpl_vars['v']->value;?>
'><?php echo $_smarty_tpl->tpl_vars['k']->value;?>
</option>
                <?php } ?>
                </select>

            </div>
        </div>
        <div class="control-group">
            <label for="customer_name" class="control-label">Port参数: </label>
            <div class="controls option-box"></div>
        </div>
        <div class="control-group">
            <label for="customer_name" class="control-label">自动保存数据: </label>
            <div class="controls">
                <select style="width: 284px;" name="SautoSave[]" />
                    <option value="true">是</option>
                    <option value="false">否</option>
                </select>
            </div>
        </div>   
    </div>
    <div class="control-group">
        <div class="controls">
        <button class="btn btn-danger" type="submit">生成应用</button>
        </div>
    </div>
</fieldset>
</form>

<div id="modalWaitPing" class="modal hide fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-header">
        <h3>正在执行操作...</h3>
    </div>
    <div class="modal-body">
        <p><span class="label label-warning">请等待，操作完成后本页会自动刷新</span></p>
    </div>
</div>

<script type="text/javascript" src="./public/js/ServiceApi.js"></script>
<script type="text/javascript">
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}
var appArr = [];
$(function(){
    $("#add_sensor").click(function(){
        var obj = $(".sensor-data:last").clone();
        obj.find("input").val("");
        obj.find("select option:first").prop("selected",true);
        $(".sensor-data:last").after(obj);
        $(".delbut").show();
    });
    
    $(".form-horizontal").on("click",".delbut",function(){
        
        if($(".delbut").length > 1)
        {  
           $(this).parents(".sensor-data").remove();
           if($(".delbut").length==1){
                $(".delbut").hide();
            } 
        }
        
    });

    $(".form-horizontal").on("change",".portType",function(){
        var obox = $(this).parents(".sensor-data").find(".option-box");
        var index = $(".sensor-data").index($(this).parents(".sensor-data"));
        obox.html("");
        if($(this).val()!="")
        {
            var opt = $(this).find("option:selected").data("opt");
            var j = 0;
            for(var i in opt)
            {
                var v = "";
                if (opt[i]["default"] !=null)
                {
                   v = opt[i]["default"];
                }
                var shtm = (j>0)?' ':'';
                shtm += opt[i]["name"]+'：<input type="text" name="SportOption'+index+'['+i+']" value="'+v+'" class="input-small"/>';
                obox.append(shtm);
                j++;
            }
        }
        
    });
    $(".delbut").hide();
    $("#addname").blur(function(){
        if(inArray($(this).val().toLowerCase(),appArr))
        {
            $(this).focus();
            $("#errmsg").text('已经存在此应用！')
            $("#myAlert").show().delay (5000).fadeOut();
        }
    });
    $(".form-horizontal").submit(function(){
        var msg = '';
        $(".input-xlarge,.input-small").each(function(){
            if($(this).val() == "")
            {
                msg = $(this).parents(".control-group").find(".control-label").text().replace(":",'').replace(' ','') + "不能为空！";
                $(this).focus();
                return false;
            }
        });
        if(msg!="")
        {
            $("#errmsg").text(msg);
            $("#myAlert").show().delay (5000).fadeOut();
            return false;
        }
    });
    

    ServiceApi.request("Services.get_modules", {}, function(seq, result, info, modules){
        if(result === true)
        {
            for(module_name in modules)
            {
                appArr.push(module_name.toLowerCase())
            }
        }
    });
    
});

</script><?php }} ?>
<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:23
         compiled from "/home/system/rdcos/Web/views/terminal/index.html" */ ?>
<?php /*%%SmartyHeaderCode:3001371775a79043f6c4c53-55428422%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '15309360e07202c1e441264358ae9502a7f42d3a' => 
    array (
      0 => '/home/system/rdcos/Web/views/terminal/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '3001371775a79043f6c4c53-55428422',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'host' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043f6daf37_24064081',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043f6daf37_24064081')) {function content_5a79043f6daf37_24064081($_smarty_tpl) {?><style type="text/css">
html {background: #555;}
h1 {margin-bottom: 20px;font: 20px/1.5 sans-serif;}
.terminal {float: left;border: #000 solid 5px;font-family: Consolas;font-size: 14px;color: #f0f0f0;background: #000;}
.terminal-cursor {color: #000;background: #f0f0f0;}
.led{display:inline-block;height:10px;width:10px;margin-bottom:-1px;border:1px solid #ccc;}
.green{background-color:#050;border:1px solid #080;}
.green.light{background-color:#0d0;border:1px solid #0f0;}
.red{background-color:#500;border:1px solid #800;}
.red.light{background-color:#d00;border:1px solid #f00;}
</style>
<script type="text/javascript" src="./public/js/term.js"></script>
<script type="text/javascript">
var term = null, socket = null;
var addr = "<?php echo (($tmp = @$_smarty_tpl->tpl_vars['host']->value)===null||$tmp==='' ? '192.168.0.35' : $tmp);?>
:1158";
function led_flash(o)
{
    o.addClass("light");
    setTimeout(function(){
        o.removeClass("light")
    }, 200);
}
function connect()
{
    $('#connectState').text("正在连接...");
    socket = new WebSocket("ws://" + addr);
    term.write("Connecting to " + addr + " ...");
    socket.onopen = function() {
        $('#rebootModal').modal('hide');
        $('#btnShowConnectWin').prop('disabled', true);
        term.write("\033[32mSucceed!\033[0m\r\n");
        socket.onclose = function() {
            term.reset();
            term.write("\r\n\r\n\x1b[31mConnection closed.\x1b[m\r\n");
            $('#connectState').text("连接已断开");
            $('#btnConnect').prop('disabled', false);
            $('#rebootModal').modal('show');
            $('#btnShowConnectWin').prop('disabled', false);
            socket = null;
        };
    };
    socket.onmessage = function(data) {
        led_flash($("#rcv_led"));
        var d = data.data;
        var log = "";
        for(var i = 0; i < d.length; i++)
        {
            var di = d[i];
            log += di;
            if(!/\w/i.test(di)) log += '[' + di.charCodeAt(0) + ']';
        }
        console.log('rcv: ' + log);
        term.write(data.data);
    };
    socket.onclose = function() {
        term.write("\x1b[31mFaild!\x1b[m\r\n");
        $('#connectState').text("连接已断开");
        $('#btnConnect').prop('disabled', false);
        $('#rebootModal').modal('show');
        socket = null;
    };
    setTimeout(function() {
        if(socket && socket.readyState != 1) socket.close();
    }, 3000);
}
function start() {
    if(!window.WebSocket) alert("Browser do not support WebSocket.");
    window.addEventListener('load', function()
    {
        term = new Terminal({
            cols: 130,
            rows: 26,
            cursorBlink: false
        });
        //term.open(document.body);
        term.open($('#terminalBox')[0]);
        term.on('data', function(data) {
            var d = data;
            var log = "";
            for(var i = 0; i < d.length; i++)
            {
                var di = d[i];
                log += di;
                if(!/\w/i.test(di)) log += '[' + di.charCodeAt(0) + ']';
            }
            console.log('snd: ' + log);

            socket.send(data);
            led_flash($("#snd_led"));
        });
        connect();
    }, false);
}
start();
$(function(){
    $('#btnConnect').click(function(){
        $('#btnConnect').prop('disabled', true);
        var newAddr = $('#txtAddr').val();
        if(newAddr == '')
        {
            $('#txtAddr').val(addr);
            newAddr = addr;
        }
        addr = newAddr;
        connect();
    });
    $('#btnShowConnectWin').click(function(){
        $('#rebootModal').modal('show');
    });
});
</script>
<span><input type="button" value=" 连接 " id="btnShowConnectWin" /></span>
<span class="led_panel">SND: <div id="snd_led" class="led green"></div> RCV: <div id="rcv_led" class="led red"></div></span>
<div id="terminalBox"></div>
<div id="rebootModal" class="modal hide fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="connectState">连接已断开</h3>
    </div>
    <div class="modal-body">
        <p>
            <label>终端地址: 
            <input type="text" id="txtAddr" value="<?php echo (($tmp = @$_smarty_tpl->tpl_vars['host']->value)===null||$tmp==='' ? '192.168.0.35' : $tmp);?>
:1158" />
            <input type="button" value=" 连接 " id="btnConnect" /></label>
        </p>
    </div>
</div>
<?php }} ?>
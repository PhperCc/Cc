<?php /* Smarty version Smarty-3.1.13, created on 2018-02-06 09:26:18
         compiled from "/home/system/rdcos/Web/views/gps/index.html" */ ?>
<?php /*%%SmartyHeaderCode:21187070865a79043a64e6e6-34634033%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '65bf16b2ea93646012d4e073fb97a12dccbe4aa8' => 
    array (
      0 => '/home/system/rdcos/Web/views/gps/index.html',
      1 => 1517878363,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '21187070865a79043a64e6e6-34634033',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_5a79043a65bef7_96544642',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5a79043a65bef7_96544642')) {function content_5a79043a65bef7_96544642($_smarty_tpl) {?><ul id="myTab" class="nav nav-tabs">
    <li class="active">
        <a href="#status" data-toggle="tab">状态</a>
    </li>
    <li>
        <a href="#hit" data-toggle="tab">打点</a>
    </li>
    <li>
        <a href="#tools" data-toggle="tab">转换工具</a>
    </li>
</ul>
<div id="myTabContent" class="tab-content">
    <div class="tab-pane fade in active" id="status">
        <form class="form-horizontal">
        <fieldset>
            <div class="control-group">
                <label class="control-label">服务端连接状态: </label>
                <div class="controls input-append" style="display:block;">
                    <span id="rtk_connected" class="uneditable-input input-xlarge"></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">接收数据: </label>
                <div class="controls input-append" style="display:block;">
                    <span id="rtk_recived" class="uneditable-input input-xlarge"></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">客户端连接: </label>
                <div class="controls input-append" style="display:block;">
                    <span id="rtk_clients" class="uneditable-input input-xlarge"></span>
                </div>
            </div>
        </fieldset>

        <fieldset id="panel_template" style="display:none;">
            <legend style="font-size: 14px;line-height: 30px;"><span class="gps_name"></span><span class="gps_status" style="margin-left:10px;"></span></legend>
            <div class="control-group">
                <label class="control-label">状态: </label>
                <div class="controls" >
                    <img src="public/img/satellite_q0.png" class="quality_img" />
                    <span class="quality_info">尚未收到信号</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">卫星: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large snum"></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">经度: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large lon"></span><span class="add-on">°</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">纬度: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large lat"></span><span class="add-on">°</span>
                </div>
            </div>
            <!--
            <div class="control-group">
                <label class="control-label">平面移动: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large point_move"></span><span class="add-on">米</span>
                </div>
            </div>
            -->
            <div class="control-group">
                <label class="control-label">高程: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large height"></span><span class="add-on">米</span>
                </div>
            </div>
            <!--
            <div class="control-group">
                <label class="control-label">高程变化: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large height_move"></span><span class="add-on">米</span>
                </div>
            </div>
            -->
            <div class="control-group">
                <label class="control-label">RTK延迟: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large rtkdelay"></span><span class="add-on">秒</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">时间: </label>
                <div class="controls">
                    <span class="uneditable-input input-large gpstime"></span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">方向: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large drct"></span><span class="add-on">°</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">速度: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="uneditable-input input-large speed"></span><span class="add-on">km/h</span>
                </div>
            </div>
        </fieldset>

        <fieldset id="gnss_status" style="display: none">
            <legend style="font-size: 14px;line-height: 30px;">天线安装位置检测</legend>
            <div class="control-group">
                <label class="control-label">安装状态: </label>
                <div class="controls input-append" style="display:block;">
                    <span class="label" id="gps_install_status">正在检测中......</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">水平距离: </label>
                <div class="controls input-append" style="display:block;">
                    <span id="gps_distance" class="uneditable-input input-large speed">0</span><span class="add-on">米</span>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">垂直距离: </label>
                <div class="controls input-append" style="display:block;">
                    <span id="gps_height_diff" class="uneditable-input input-large speed">0</span><span class="add-on">米</span>
                </div>
            </div>
        </fieldset>
        </form>
    </div>
    <div class="tab-pane fade" id="hit">
    打点工具还在开发中
    </div>
    <div class="tab-pane fade" id="tools">
    转换工具还在开发中
    </div>
<script type="text/javascript" src='./public/js/ServiceApi.js'></script>
<script type="text/javascript" src="./public/js/GpsPoint.js"></script>
<script type="text/javascript">

var $positions = {GPS0: {lon: 0, lat: 0, height: 0}, GPS1: {lon: 0, lat: 0, height: 0}};

getGpsInfo('GPS1');
getGpsInfo('GPS0');

function getGpsInfo($gps_name)
{
    var $panel_id = 'panel_' + $gps_name;
    $('#panel_template').after($('#panel_template').clone().prop('id', $panel_id).show());

    var $panel = $('#' + $panel_id);
    $panel.find("legend .gps_name").html($gps_name);

    ServiceApi.request("Cache.get", {"key": "Sensor:" + $gps_name + ":status"}, function($seq, $result, $info, $gps_status){
        $gps_status_text = "未连接";
        switch($gps_status)
        {
            case 1: $gps_status_text = "已连接"; break;
            case 2: $gps_status_text = "已收到数据"; break;
            case 3: $gps_status_text = "已定位"; break;
            default: $gps_status_text = "未连接"; break;
        }
        $panel.find("legend .gps_status").html($gps_status_text);
    }, 1000);

    ServiceApi.request("Cache.get", {"key": "Sensor:" + $gps_name + ":data"}, function($seq, $result, $info, $gps_data){
        if(!$gps_data) return;

        for($field in $gps_data)
        {
            var $val = $gps_data[$field];

            if($field == "quality")
            {
                var $quality_infos = ["等待定位", "单点定位", "码差分", "非法解", "精准解", "浮动解"];
                $panel.find(".quality_info").html($quality_infos[$val]);
                $panel.find(".quality_img").prop("src", "public/img/satellite_q" + $val + ".png");
                continue;
            }

            if($field == "lon")
            {
                $positions[$gps_name]['lon'] = $val;
            }

            if($field == "lat")
            {
                $positions[$gps_name]['lat'] = $val;
            }

            if($field == "height")
            {
                $positions[$gps_name]['height'] = $val;
            }

            console.log("data callback for " + $panel.prop('id'));
            $panel.find("." + $field).html($val);
        }
        gps_install_status();
    }, 1000);
}


// 天线安装位置信息
function gps_install_status()
{
    var Point0 = new GpsPoint($positions['GPS0'].lon, $positions['GPS0'].lat);     // 前天线坐标
    var Point1 = new GpsPoint($positions['GPS1'].lon, $positions['GPS1'].lat);    // 后天线坐标

    if(Point0.lon == 0 || Point1.lon == 0) 
    {
        $("#gnss_status").hide();
        return;
    }

    $distance = Point0.distance(Point1);   // 水平距离
    $height_diff = $positions['GPS0'].height - $positions['GPS1'].height;   // 高差

    $("#gps_distance").html($distance);
    $("#gps_height_diff").html($height_diff);

    if ($height_diff > 10 && $height_diff < 50 && $distance > 20 && $distance < 50)
    {
        $("#gps_install_status").html("天线位置正确");
        $("#gps_install_status").addClass("label-success");
    }
    else
    {
        $("#gps_install_status").html("天线位置异常");
        $("#gps_install_status").addClass("label-warning");
    }
    $("#gnss_status").show();  
}

var $rtk_bytes = 0;
var $rtk_packages = 0;
var $rtk_reconnect_count = 0;

ServiceApi.request("Cache.get", {'key': 'Sensor:Rtk:connected'}, function($seq, $result, $info, $data){
    $('#rtk_connected').html($data == 1 ? "已连接" : "正在连接..." + $rtk_reconnect_count);
}, 200);

ServiceApi.request("Cache.get", {'key': 'Sensor:Rtk:reconnect_count'}, function($seq, $result, $info, $data){
    $rtk_reconnect_count = $data;
}, 200);

ServiceApi.request("Cache.get", {'key': 'Sensor:Rtk:packages'}, function($seq, $result, $info, $data){
    $rtk_packages = $data;
    $('#rtk_recived').html(bytes2text($rtk_bytes) + ', packages: ' + $rtk_packages);
}, 200);

ServiceApi.request("Cache.get", {'key': 'Sensor:Rtk:bytes'}, function($seq, $result, $info, $data){
    $rtk_bytes = $data;
    $('#rtk_recived').html(bytes2text($rtk_bytes) + ', packages: ' + $rtk_packages);
}, 200);

ServiceApi.request("Cache.get", {'key': 'Sensor:Rtk:clients'}, function($seq, $result, $info, $data){
    var $client_names = [];
    for($client in $data)
    {
        $client_names.push($client);
    }
    $('#rtk_clients').html($client_names.join(', '));
}, 200);

</script><?php }} ?>
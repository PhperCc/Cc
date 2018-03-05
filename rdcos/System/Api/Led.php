<?php
/**
 * 控制LED接口
 */
class api_Led extends MCU\ApiHelper\ApiBase
{
    
    /**
     * 控制LED状态
     * @index string LED序号 0，1
     * @type string 显示模式 0 灭 1 常亮 其他值以固定时间间隔闪烁
     */
    public function set($params)
    {
        if(false === get_param($params, "index"))
        {
            return R(false, "need param 'index'");
        }

        if(false === get_param($params, "type"))
        {
            return R(false, "need param 'type'");
        }

        $body = ['index'=>$params['index'],'type'=>$params['type']];
        Channel\Client::publish('LedControl', $body);

        return R(true, '', true);
    }
}
<?php
use MCU\Sensor\SensorBase;

class DensityCan extends SensorBase 
{
    protected $portType = 'CanPort';
    protected $interval = 0;
    protected $name = 'Dense';

    public $data = ['amp'=>0,'freq'=>0,'ecv'=>0,'jump'=>0];
    private $revise = 1;
    private $min_amp = 0;
    private $max_amp = 0;
    private $ratio_freq = 0; 
    private $ratio_ecv = 0;
    private $max_hz = 0;
    private $max_force = 0;
    private $force_type = 0;
    private $zn_type = 0;


    public function init()
    {
        $this->revise = $this->getConfig('revise', 1);
        $this->portOption['name'] = $this->getConfig('can_device', 'can0');
        if($this->revise)
        {
            $this->min_amp = $this->getConfig('min_amp', 300);      // 振幅阀值
            $this->max_amp = $this->getConfig('max_amp', 700);       // 最大振幅
            $this->ratio_freq = $this->getConfig('ratio_freq', 0.8);   // 采集振频与车量振频的比率系数
            $this->ratio_ecv = $this->getConfig('ratio_ecv', 8);      // ecv 调整系数
            $this->max_hz = $this->getConfig('max_hz', 28);        // 最大振频
            $this->max_force = $this->getConfig('max_force', 425);    // 最大激振力
            $this->force_type = $this->getConfig('force_type', 0);       // 激振力计算方式
            $this->zn_type = $this->getConfig('zn_type', 0);        // 振碾类型
        }
    }

    public function decode($data)
    {
        $can_id = $data['can_id'];
        if($can_id == 803)
        {
            $this->data['amp'] = $data['val1'];   // 振幅
            $this->data['freq'] = $data['val2'];  // 振频
        }
        else if($can_id == 804)
        {
            $this->data['ecv'] = $data['val1'];   // 密实度
            $this->data['jump'] = $data['val2'];  // 跳点
        }

        if(1 == $this->revise)
        {
            $this->revise($this->data);
        }

        return $this->data;
    }

    /**
     * 数据修正
     * sensor:$data['freq'],$data['amp'],$data['ecv'],$data['jump'],$data['force'],$data['zn_type']
     * "revise" => 1,// 是否修正数据
     * "zn_type" => 1,//震碾类型，1：普通压路机| 2：36吨压路机
     * "min_amp" => 300,   // 有效振幅最小值
     * "ratio_freq"=> 0.8, //车辆频率对应系数
     * "ratio_ecv" => 8, //ecv放大系数
     * "max_force" => 425, //最大激振力
     * "max_hz" => 28, //最大频率
     *
     * @param mixed $data
     *
     * @return mixed
     */
    private function revise(&$data)
    {
        
        if($data['amp'] < $this->min_amp)
        {
            $data['freq'] = 0;
            $data['ext_ecv'] = 0;
            $data['force'] = 0;
        }
        else
        {
            $data['ext_ecv'] = $data['ecv'] * $this->ratio_ecv;
            $hz = $data['freq'] / $this->ratio_freq;  //计算得到车辆的标定频率
            if($this->force_type == 1)
            {
                $data['force'] = round($this->max_force * pow($hz, 2) / pow($this->max_hz, 2));
            }
            else
            {
                //$data['force'] = $data['amp'] / $ratio_freq;//$max_force
                $data['force'] = $this->max_force * $data['amp'] / $this->max_amp;
            }
            $data['hz'] = $hz;
        }
        $data['zn_type'] = $this->zn_type;

        return true;
    }
}
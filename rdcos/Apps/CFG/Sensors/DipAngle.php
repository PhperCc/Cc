<?php
class DipAngle extends MCU\Sensor\SensorBase
{
	protected $name = "DipAngle";
	protected $portType = 'ModbusRTUPort';
	protected $autoSave = true;
	protected $mode = 'passivity';
	protected $_repeat_command = [0x03, 0x01, 0x02];

	public function init()
	{
		$this->portOption['name'] = $this->GetConfig("name", "/dev/ttyO3");
		$this->portOption['baud'] = $this->GetConfig("baud", "9600");
		$this->portOption['bits'] = $this->GetConfig("bits", "8");
		$this->portOption['stop'] = $this->GetConfig("stop", "1");
		$this->portOption['parity'] = $this->GetConfig("parity", "0");
		$this->portOption['addr'] = $this->GetConfig("addr", "4");
		$this->portOption['format'] = $this->GetConfig("format", "1");
		$this->interval = $this->GetConfig("interval", "1");
	}

	public function decode($data)
	{
		//解析传感器数据代码
		return $data;
	}
}
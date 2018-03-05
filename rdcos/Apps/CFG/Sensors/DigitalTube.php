<?php
class DigitalTube extends MCU\Sensor\SensorBase
{
	protected $name = "DigitalTube";
	protected $portType = 'ModbusRTUPort';
	protected $autoSave = false;
	protected $mode = 'passivity';
	protected $snd_count = 0;

	public function init()
	{
		$this->portOption['name'] = $this->GetConfig("name", "/dev/ttyO3");
		$this->portOption['baud'] = $this->GetConfig("baud", 9600);
		$this->portOption['bits'] = $this->GetConfig("bits", 8);
		$this->portOption['stop'] = $this->GetConfig("stop", 1);
		$this->portOption['parity'] = $this->GetConfig("parity", 0);
		$this->portOption['addr'] = $this->GetConfig("addr", 3);
		$this->portOption['format'] = $this->GetConfig("format", 1);
		$this->wirte_interval = $this->GetConfig("interval", "1");


		MCU\Timer::add($this->wirte_interval, function()
        {
            
            $cfg_data = MCU\Cache::get("Sensor:CFG:digitaltube");
            if(!empty($cfg_data))
            {
            	$bin = $this->get_bin($this->portOption['addr'], 0x10, 0, $cfg_data);
	            $this->_portHandle->push($bin, false);
	            MCU\Cache::set("Sensor:Dt:snd_count", ++$this->snd_count);
	            MCU\Cache::set("Sensor:Dt:status", 2);
            }
        });

	}


	private function get_bin($slave_addr, $func_code, $reg_addr, $data)
    {
        $each_data_len = 4;
        $data_pack_type = 'f';
        $need_reorder = true;

        $data_list = is_array($data) ? $data : [$data];
        $data_count = count($data_list);

        $data_len = $data_count * $each_data_len;
        $reg_count = $data_len / 2;     //寄存器数量: 每个寄存器占用 2 个字节
        $data_arr = array($slave_addr, $func_code, $reg_addr, $reg_count, $data_len);
        $bin = pack("CCnnC", $slave_addr, $func_code, $reg_addr, $reg_count, $data_len);
        foreach($data_list as $data)
        {
            $data_bin = pack($data_pack_type, $data);
            if($need_reorder)
            {
                $data_bin = $data_bin[1].$data_bin[0].$data_bin[3].$data_bin[2];
            }
            $bin .= $data_bin;
        }   
        return $bin;
    }
}
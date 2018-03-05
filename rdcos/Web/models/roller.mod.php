<?php
use MCU\Cache;
class mod_roller extends WebModule
{
    public function __init()
    {
        $this->title = "碾压机车载平板";
        $this->powers = array ();
        $this->ignore_main = array('pad', 'sqlite', 'index');
        $this->hide_menu[] = 'dense';
    }

	public function view_index()
	{

	}

   	// 平板显示
    public function view_pad()
    {
        /*
		$car = Cache::get("Svc:Roller:curinfo");
        $this -> data['car_no'] = $car['no'];
        $this -> data['car_width'] = $car['width'];
        */
    }

	// 获取平板数据
	public function ajax_get_pad_data()
	{
		// gpstime, lon, lat, drct, speed, ecv

		// $last_time = $_POST['last_time'];
        $last_time = Http::param('last_time');

		$car = Cache::get("Svc:Roller:curinfo");
		$list = Cache::get("Svc:Roller:GpsList", []);
		Cache::set("Svc:Roller:GpsList", []);

		$last_id = $last_time;
		$arr = array();
		foreach ($list as $r)
		{
			$arr[] = array(
				'car_no'  => $car['no'],
				'id'      => $r['gpstime'],
				'lon'     => floatval($r['lon']),
				'lat'     => floatval($r['lat']),
				'deg'     => floatval($r['drct']),
				'last_id' => $last_id,
				'data'    => round($r['speed'], 2) . ',' . floatval($r['ecv']),
			);
			$last_id = $r['gpstime'];
		}

		$res = array(
			'result' => 1,
			'data' => $arr,
		);

		ob_clean();
		echo json_encode($res);
		return;
	}

	//密实度校准
	public function view_dense()
	{
		$this -> title = "密实度校准 - ENH";
	}

    // 保存密实度校准值(系数)
    public function ajax_dense_save()
    {
        if(empty($_POST))exit("系数不能空");
        // if(!is_array($records)) exit("数据格式错误，解析失败");

        if(false === MCU\LocalFile::putObject("services/moduleconfig/Roller/SubmitData",$_POST)) exit("配置文件保存失败");
        echo "ok";
    }
}
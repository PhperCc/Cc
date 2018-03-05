<?php
/**
* 经纬度到80坐标系的转换
*/
class XA80
{
	private $datum84; 	//84椭球
	private $datum80; //2000椭球
	private $l0;		//中央子午线
	private $h0;		//投影高程
	private $dx;		//坐标转换七参数
	private $dy;
	private $dz;
	private $wx;
	private $wy;
	private $wz;
	private $k;

	function __construct($params)
	{
		$this->l0 = $params['l0'];
		$this->h0 = $params['h0'];
		$this->dx = $params['dx'];
		$this->dy = $params['dy'];
		$this->dz = $params['dz'];
		$this->wx = $params['wx'];
		$this->wy = $params['wy'];
		$this->wz = $params['wz'];		
		$this->k = $params['k'];
		$this->datum84 = Datum::getWGS84();
		$this->datum84 -> setL0($this->l0);
		$this->datum80 = Datum::getXA80();
		$this->datum80->setL0($this->l0);
	}

	public function getXY($gnss)
	{
		$p = new PointBLH($gnss['lat'], $gnss['lon'], $gnss['hi']);
		$p_XYZ = CoordTrans::BLH2XYZ($p, $this->datum84);
		$p_XYZ2 = CoordTrans::XYZ2XYZ($p_XYZ, $this->dx, $this->dy, $this->dz, $this->wx, $this->wy, $this->wz, $this->k);
		$p_BLH = CoordTrans::XYZ2BLH($p_XYZ2, $this->datum80);
		$po =  CoordTrans::BL2xy($p_BLH, $this->datum80, 0, 500000, $this->h0);

		return $po;
	}
}
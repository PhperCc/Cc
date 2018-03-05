<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
use MCU\Positioning\GpsPoint;
class QhPoint
{
    /**
     * @var bool 安装信息是否已设置
     */
    private static $_hasSetup = false;

    /**
     * @var bool 前天线是否安装在中轴线左边
     */
    private static $frontPointAtLeft = true;

    /**
     * @var int 前天线到夯锤中心横向距离， 单位米
     */
    private static $frontPointX = 0;

    /**
     * @var int 前天线到夯锤中心纵向距离， 单位米
     */
    private static $frontPointY = 0;

    /**
     * @var bool 后天线是否安装在中轴线左边
     */
    private static $backPointAtLeft = false;

    /**
     * @var int 后天线到车体中心横向距离
     */
    private static $backPointX = 0;

    /**
     * @var int 后天线到车体中心纵向距离
     */
    private static $backPointY = 0;

    /**
     * 设置安装信息
     *
     * @param $frontPointAtLeft
     * @param $frontPointX
     * @param $frontPointY
     * @param $backPointAtLeft
     * @param $backPointX
     * @param $backPointY
     */
    public static function setupInfo($frontPointAtLeft, $frontPointX, $frontPointY, $backPointAtLeft, $backPointX, $backPointY)
    {
        self::$frontPointAtLeft = $frontPointAtLeft;
        self::$frontPointX = $frontPointX;
        if($frontPointAtLeft)
        {
            self::$frontPointX = -1 * self::$frontPointX;
        }
        self::$frontPointY = $frontPointY;

        self::$backPointAtLeft = $backPointAtLeft;
        self::$backPointX = $backPointX;
        if($backPointAtLeft)
        {
            self::$backPointX = -1 * self::$backPointX;
        }
        self::$backPointY = $backPointY;

        self::$_hasSetup = true;
    }

    /**
     * 计算其它相关位置信息
     *
     * @param GpsPoint $frontPoint  前天线GPS点
     * @param GpsPoint $backPoint   后天线GPS点
     *
     * @return array
     * @throws Exception
     */
    public static function getPositionInfo(GpsPoint $frontPoint, GpsPoint $backPoint)
    {
        if(!self::$_hasSetup)
        {
            throw new Exception("QhPoint must setup before");
        }

        // 以前后天线连接线为斜边， 过前天线平行于大臂的直线为一条直角边， 过后天线垂直于大臂的直线为另一条直角边。
        // 前天线为A点， 后天线为B点， 直角点为C点。

        $ab = $frontPoint -> distance($backPoint);
        $bc = abs(self::$frontPointX - self::$backPointX);  // BC两点连接线距离
        $ac = pow(pow($ab, 2) - pow($bc, 2), 0.5);
        $radian_bac = asin($bc / $ab);
        $angle_bac = $radian_bac * 180.0 / pi();    // 弧度转角度
        if(self::$frontPointX > self::$backPointX)
        {
            $angle_bac = -1 * $angle_bac;
        }

        // 后天线到前天线的连线， 相对于正北的角度
        $northAngle_ab = $frontPoint -> getNorthAngle($backPoint);

        // 后天线到车辆中心的连线（即大臂指向的反方向）， 相对于正北的角度
        $northAngle_ac = $northAngle_ab + $angle_bac;

        // 大臂指向方向
        $workAngle = $northAngle_ac - 180;
        while($workAngle < 0)
        {
            $workAngle += 360;
        }

        while($workAngle > 360)
        {
            $workAngle -= 360;
        }

        // 中心点
        $centerPoint = $frontPoint -> move($ac - self::$backPointY, $northAngle_ac, true);

        // 夯锤落点
        $workPoint = $frontPoint -> move(self::$frontPointX, $workAngle, true)
            -> move(self::$frontPointY, self::$frontPointAtLeft ? $workAngle + 90 : $workAngle - 90);

        return ["workAngle"   => $workAngle,
                "centerPoint" => $centerPoint,
                "workPoint"   => $workPoint
        ];
    }
}
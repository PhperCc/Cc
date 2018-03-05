<?php

/*
从GPS中接收到84坐标系下的地心大地坐标
↓
使用84坐标系的椭球参数转换为84坐标系下的地心直角坐标
↓
使用七参数转换为54坐标系下的地心直角坐标
↓
使用54坐标系的椭球参数转换为54坐标系下的地心大地坐标
↓
使用投影参数转换为54坐标系下的平面直角坐标
↓
使用四参数转换为其它区域坐标系下的平面直角坐标
*/


/*
54坐标系椭球坐标参数如下：
长半轴=6378245m
扁率α=1/298.3
*/

/*
80坐标系椭球坐标参数如下：
长半轴a=6378140±5m
扁率α=1/298.257
*/

/*
84坐标系椭球坐标参数如下：
长半径：a=6378137±2m
扁率f=0.003352810664
*/

/*
椭圆的长半轴 a
椭圆的短半轴 b
椭圆的扁率 alpha=(a-b)/a
椭圆的第一偏心率 e=(a^2-b^2)^0.5/a
椭圆的第二偏心率 e'=(a^2-b^2)^0.5/b
*/

/*
  参考:
    http://rf-gsm.com/news/GPS/376.html
    http://blog.csdn.net/jax_lee/article/details/6764360
    http://www.cnblogs.com/joetao/articles/1895574.html
    http://netclass.csu.edu.cn/JPKC2007/CSU/02GPSjpkch/jiao-an/2.2.htm
    http://tian0226.blog.sohu.com/142843049.html    墨卡托投影、高斯-克吕格投影、UTM投影
*/


namespace MCU\Positioning;

/*
    坐标转换类
*/
class CoordTrans
{
    /*
        度分秒转度
    */
    public static function dms2deg($d, $m, $s)
    {
        return $d + $m / 60 + $s / 3600;
    }

    /*
        度转度分秒
    */
    public static function deg2dms($deg)
    {
        $d = intval($deg);
        $m = intval(($deg - $d) * 60);
        $s = round(($deg - $d - $m / 60) * 3600, 5);
        return ['d' => $d, 'm' => $m, 's' => $s];
    }

    /*
        七参数转换
        不同椭球参数下， 地心直角坐标系之间转换
        $dX, $dY, $dZ: 三个坐标方向的平移参数
        $wX, $wY, $wZ: 三个方向的旋转角参数
        $Kppm: 尺度参数， 单位是ppm，如果是以米为单位， 需要在传参前 除以1000000
    */
    public static function XYZ2XYZ(PointXYZ $source, $dX, $dY, $dZ, $wX = 0, $wY = 0, $wZ = 0, $Kppm = 0)
    {
        $X = $source -> X;
        $Y = $source -> Y;
        $Z = $source -> Z;

        $destX = $dX + $Kppm * $X - $wY * $Z + $wZ * $Y + $X;
        $destY = $dY + $Kppm * $Y + $wX * $Z - $wZ * $X + $Y;
        $destZ = $dZ + $Kppm * $Z - $wX * $Y + $wY * $X + $Z;

        return new PointXYZ($destX, $destY, $destZ);
    }

    /*
        四参数转换
        相同椭球参数下， 平面坐标系之间转换
        常用于国家坐标系到地方坐标系转换
    */
    public static function xy2xyLocal(Pointxy $source, $dX, $dY, $wX, $Kppm)
    {
        $x = $source -> x;
        $y = $source -> y;

        $destx = $x * $Kppm * cos($wX) - $y * $Kppm * sin($wX) + $dX;
        $desty = $x * $Kppm * sin($wX) + $y * $Kppm * cos($wX) + $dY;

        return new Pointxy($destx, $desty);
    }

    /*
        地心大地坐标系 转换到 地心直角坐标系
    */
    public static function BLH2XYZ(PointBLH $pointBLH, Datum $datum)
    {
        $a = $datum -> A;
        $e12 = $datum -> E12;
        $radB = deg2rad($pointBLH -> B);
        $radL = deg2rad($pointBLH -> L);
        $H = $pointBLH -> H;

        $N = $a / sqrt(1 - $e12 * sin($radB) * sin($radB)); // 卯酉圈半径

        $X = ($N + $H) * cos($radB) * cos($radL);
        $Y = ($N + $H) * cos($radB) * sin($radL);
        $Z = ($N * (1 - $e12) + $H) * sin($radB);
        return new PointXYZ($X, $Y, $Z);
    }

    /*
        地心直角坐标系 转换到 地心大地坐标系
    */
    public static function XYZ2BLH(PointXYZ $pointXYZ, Datum $datum)
    {
        // 参考 https://wenku.baidu.com/view/30a08f9ddd88d0d233d46a50.html
        // 用直接法2 计算
        $X = $pointXYZ -> X;
        $Y = $pointXYZ -> Y;
        $Z = $pointXYZ -> Z;

        $L = atan($Y / $X);
        $degL = rad2deg($L);    // 弧度转角度
        if($Y > 0)  // Y值为正， 东半球， 否则西半球
        {
            while($degL < 0) $degL += 180;
            while($degL > 180) $degL -= 180;
        }
        else
        {
            while($degL > 0) $degL -= 180;
            while($degL < -180) $degL += 180;
        }

        $a = $datum -> A;
        $b = $datum -> B;
        $e12 = $datum -> E12;
        $e22 = $datum -> E22;

        $tgU = $Z / (sqrt($X * $X + $Y * $Y) * sqrt(1 - $e12));
        $U = atan($tgU);

        $tgB = ($Z + $b * $e22 * pow(sin($U), 3)) / (sqrt($X * $X + $Y * $Y) - $a * $e12 * pow(cos($U), 3));
        $B = atan($tgB);
        $degB = rad2deg($B);    // 弧度转角度
        if($Z > 0)  // Z值为正， 北半球， 否则南半球
        {
            while($degB < 0) $degB += 90;
            while($degB > 90) $degB -= 90;
        }
        else
        {
            while($degB > 0) $degB -= 90;
            while($degB < -90) $degB += 90;
        }

        while($degB < 0) $degB += 360;
        while($degB > 360) $degB -= 360;

        $N = $a / sqrt(1 - $e12 * sin($B) * sin($B)); // 卯酉圈半径
        $H = 0;
        if(abs($degB) > 80) // B接近极区， 在±90°附近
        {
            $H = $Z / sin($B) - $N * (1 - $e12);
        }
        else
        {
            $H = sqrt($X * $X + $Y * $Y) / cos($B) - $N;
        }

        $B = round($degB, 9);
        $L = round($degL, 9);
        $H = round($H, 4);
        return new pointBLH($B, $L, $H);
    }

    /*
        地心大地坐标系 转换到 大地平面坐标系
        $prjHeight: 投影面高程
    */
    public static function BL2xy(PointBLH $pointBLH, Datum $datum, $offsetX = 0, $offsetY = 500000, $prjHeight = 0)
    {
        // http://www.cnblogs.com/imeiba/p/5696967.html

        $a = $datum -> A;
        $b = $datum -> B;
        $e12 = $datum -> E12;
        $e22 = $datum -> E22;

        $L0 = $datum -> L0;
        if($L0 == 0)
        {
            $zoneNo = $datum -> getZoneNo($pointBLH -> L);
            $L0 = ($zoneNo - 0.5) * $datum -> zoneWidth;
        }
        $radL0 = deg2rad($L0);

        $radB = deg2rad($pointBLH -> B);
        $radL = deg2rad($pointBLH -> L);

        $N = $a / sqrt(1 - $e12 * sin($radB) * sin($radB)); // 卯酉圈半径
        $T = tan($radB) * tan($radB);
        $C = $e22 * cos($radB) * cos($radB);
        $A = ($radL - $radL0) * cos($radB);
        $M = $a * (
            (1 - $e12 / 4 - 3 * $e12 * $e12 / 64 - 5 * $e12 * $e12 * $e12 / 256) * $radB
            - (3 * $e12 / 8 + 3 * $e12 * $e12 / 32 + 45 * $e12 * $e12 * $e12 / 1024) * sin(2 * $radB)
            + (15 * $e12 * $e12 / 256 + 45 * $e12 * $e12 * $e12 / 1024) * sin(4 * $radB)
            - (35 * $e12 * $e12 * $e12 / 3072) * sin(6 * $radB)
            );

        //x,y的计算公式见孔祥元等主编武汉大学出版社2002年出版的《控制测量学》的第72页
        //书的的括号有问题，( 和 [ 应该交换
        
        $x = $M + $N * tan($radB) * (
            $A * $A / 2 + (5 - $T + 9 * $C + 4 * $C * $C) * $A * $A * $A * $A / 24
            + (61 - 58 * $T + $T * $T + 600 * $C - 330 * $e22) * $A * $A * $A * $A * $A * $A / 720
            );
        $y = $N * ($A + ( 1 - $T + $C) * $A * $A * $A / 6 + (5 - 18 * $T * $T * $T + 72 * $C - 58 * $e22) * $A * $A * $A * $A * $A / 120);

        // $x += $offsetX;
        // $y += $offsetY;

        $x = $offsetX + $x * ($b + $prjHeight) / $b;
        $y = $offsetY + $y * ($b + $prjHeight) / $b;
        // echo "a: $a \n";
        // echo "N: $N \n";
        // echo "b: $b \n";

        $x = round($x, 4);
        $y = round($y, 4);
        $p['x'] = $x;
        $p['y'] = $y;
        // return new PointXY($x, $y);
        return $p;
    }

    /*
        大地平面坐标系 转换到 地心大地坐标系
        $prjHeight: 投影面高程
    */
    public static function xy2BL(Pointxy $pointxy, Datum $datum, $offsetX = 0, $offsetY = 500000, $prjHeight = 0)
    {
        // http://www.cnblogs.com/imeiba/p/5696967.html

        $a = $datum -> A;
        $b = $datum -> B;
        $e12 = $datum -> E12;
        $e22 = $datum -> E22;
        $e1 = (1 - sqrt(1 - $e12)) / (1 + sqrt(1 - $e12));

        $L0 = $datum -> L0;
        $radL0 = deg2rad($L0);

        // 带内大地坐标
        $x = ($pointxy -> x - $offsetX) * $b / ($b + $prjHeight);
        $y = ($pointxy -> y - $offsetY) * $b / ($b + $prjHeight);

        $u = $x / ($a * (1 - $e12 / 4 - 3 * $e12 * $e12 / 64 - 5 * $e12 * $e12 * $e12 / 256));
        $fai = $u + (3 * $e1 / 2 - 27 * $e1 * $e1 * $e1 / 32) * sin(2 * $u)
            + (21 * $e1 * $e1 / 16 - 55 * $e1 * $e1 * $e1 * $e1 / 32) * sin(4 * $u)
            + (151 * $e1 * $e1 * $e1 / 96) * sin(6 * $u)
            + (1097 * $e1 * $e1 * $e1 * $e1 / 512) * sin(8 * $u);
        $C = $e22 * cos($fai) * cos($fai);
        $T = tan($fai) * tan($fai);
        $N = $a / sqrt(1 - $e12 * sin($fai) * sin($fai));
        $R = $a * (1 - $e12) / sqrt((1 - $e12 * sin($fai) * sin($fai)) * (1 - $e12 * sin($fai) * sin($fai)) * (1 - $e12 * sin($fai) * sin($fai)));
        $D = $y / $N;

        $L = $radL0
            + ($D - (1 + 2 * $T + $C) * $D * $D * $D / 6 + (5 - 2 * $C + 28 * $T - 3 * $C * $C + 8 * $e22 + 24 * $T * $T) * $D * $D * $D * $D * $D / 120)
            / cos($fai);
        $B = $fai -($N * tan($fai) / $R)
            * ($D * $D / 2 - (5 + 3 * $T + 10 * $C - 4 * $C * $C - 9 * $e22) * $D * $D * $D * $D / 24 + (61 + 90 * $T + 298 * $C + 45 * $T * $T - 256 * $e22 - 3 * $C * $C) * $D * $D * $D * $D * $D * $D / 720);

        $B = round(rad2deg($B), 9);
        $L = round(rad2deg($L), 9);

        return new pointBLH($B, $L, 0);
    }
}


// $BJ54 = Datum::getBJ54();
// $XA80 = Datum::getXA80();
// $WGS84 = Datum::getWGS84();
// $WGS84 = Datum::getCGCS2000();

/*
$datum = Datum::getBJ54();
$L0 = CoordTrans::dms2deg(114, 0, 0);
echo "L0: $L0 \n";
$datum -> setL0($L0);

$pointxy = new pointxy(2836154.404, 405740.074);
echo $pointxy -> toString();
echo "\n";

$pointBLH = CoordTrans::xy2BL($pointxy, $datum, 0, 500000, 85);
echo $pointBLH -> toString();
echo "\n";

$dmsB = CoordTrans::deg2dms($pointBLH -> B);
$dmsL = CoordTrans::deg2dms($pointBLH -> L);
echo "B: " . $dmsB['d'] . ":" . $dmsB['m'] . ":" . $dmsB['s'] . ", L: " . $dmsL['d'] . ":" . $dmsL['m'] . ":" . $dmsL['s'];
echo "\n";
*/
/*
$pointBLH = new pointBLH(25.627318430, 113.061566634);
echo $pointBLH -> toString();
echo "\n";

$pointxy = CoordTrans::BL2xy($pointBLH, $datum, 0, 500000, 500);
echo $pointxy -> toString();
echo "\n";
*/

/*
$pointXYZ = CoordTrans::BLH2XYZ($pointBLH, $datum);
echo $pointXYZ -> toString();
echo "\n";

$pointXYZ84 = CoordTrans::XYZ2XYZ($pointXYZ, 0, 0, 0);
echo $pointXYZ84 -> toString();
echo "\n";

$pointBLH84 = CoordTrans::XYZ2BLH($pointXYZ84, $datum);
echo $pointBLH84 -> toString();
echo "\n";

$pointxy84 = CoordTrans::BL2xy($pointBLH84, $datum, 0, 500000, 0);
echo $pointxy84 -> toString();
echo "\n";
*/


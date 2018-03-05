<?php
use MCU\LocalFile;
use MCU\Sys;

class Yuantu
{
	private static $_cerFilePath = "/lib/Yuantu.cer";    // RSA公钥文件， 相对于 APP_ROOT 的目录
	private static $_pfxFilePath = "/lib/Yuantu.pfx";    // RSA私钥文件， 相对于 APP_ROOT 的目录
	private static $_pfxPass = "password";  // RSA私钥密码
	private static $_desKey = "ENHDESKey";  // DES密钥
	private static $_spliter = "<--->";
    // private static $_serverUrl = "http://117.176.15.113:9002/Services/DataService.asmx";

    private static function log($content, $return = null)
    {
        $time = date("Y-m-d H:i:s");
        LocalFile::putLine("log/yuantu.log", "$time\t$content", true);
        return $return;
    }
    
    public static function submit($data, $soapMethod, &$info)
    {
        if($data['lon'] == 0 || $data['lat'] == 0)
        {
            $info = "定位数据有误: lon: {$data['lon']}, lat: {$data['lat']}";
            return false;
        }
        $uid = Sys::get_uid();

        $data["seqid"] = microtime(true) * 10000;
        $data["unit_id"] = "ENH_$uid";
        $data["remark"] = "ENH";

        // 元图要求所有的值都加双引号， php默认是数字和bool不加双引号
        foreach($data as $k => $v)
        {
            if(is_bool($v)) $v = $v ? "true" : "false";
            $data[$k] = strval($v);
        }

        if(false === $encreptedData = self::encrypt(json_encode($data)))
        {
            $info = "error when encrypt";
            self::log("error when encrypt");
            return false;
        }

        self::log("push data: " . json_encode($data));

        // 添加到队列中
        // Sys::log("push data: /$soapMethod?jsonStr=$encreptedData", "Common_Yuantu");   // 分析问题， 记录日志
        return MCU\LocalFile::putLine("yuantu/submit_faild.log", "/$soapMethod?jsonStr=$encreptedData", true);
    }

	public static function encrypt($data, $cerFilePath = "")
	{
		global $appRoot;
		$iv = \Utils\Crypt\DES::getIV();
		$dataEncrypted = base64_encode(\Utils\Crypt\DES::encrypt($data, self::$_desKey, $iv));

		$keyIv = base64_encode(self::$_desKey) . self::$_spliter . base64_encode($iv);

        if(empty($cerFilePath)) $cerFilePath = $appRoot . self::$_cerFilePath;
        if(false === $keyIvEncrypted = self::RSAPubKeyEncrypt($keyIv, $cerFilePath)) return false;
		$keyIvEncrypted = base64_encode($keyIvEncrypted);

		return base64_encode($dataEncrypted . self::$_spliter . $keyIvEncrypted);
	}

	public static function decrypt($data, $pfxFilePath = "")
	{
		global $appRoot;
		if(empty($data)) return false;
        if(false === $data = base64_decode($data, true)) return false;
		list($dataEncrypted, $keyIvEncrypted) = explode(self::$_spliter, $data, 2);
		if(empty($dataEncrypted)) return false;
		if(empty($keyIvEncrypted)) return false;

		if(false === $dataEncrypted = base64_decode($dataEncrypted)) return false;
		if(false === $keyIvEncrypted = base64_decode($keyIvEncrypted)) return false;

        if(empty($pfxFilePath)) $pfxFilePath = $appRoot . self::$_pfxFilePath;
        if(false === $keyIv = self::RSAPrivKeyDecrypt($keyIvEncrypted, $pfxFilePath)) return false;
		list($key, $iv) = explode(self::$_spliter, $keyIv, 2);
		if(empty($key)) return false;
		if(empty($iv)) return false;
		if(false === $key = base64_decode($key)) return false;
		if(false === $iv = base64_decode($iv)) return false;

		$decrypted = \Utils\Crypt\DES::decrypt($dataEncrypted, $key, $iv);
		return $decrypted;
	}

	public static function RSAPubKeyEncrypt($source, $cerFilePath)
	{
		if(false === $pubKey = \Utils\Crypt\RSA::getPubKey($cerFilePath)) return false;
		if(false === $encrypted = \Utils\Crypt\RSA::encrypt($source, $pubKey)) return false;
		return $encrypted;
	}

	public static function RSAPrivKeyDecrypt($source, $pfxFilePath)
	{
		if(false === $privKey = \Utils\Crypt\RSA::getPrivKey($pfxFilePath, self::$_pfxPass)) return false;
		if(false === $encrypted = \Utils\Crypt\RSA::decrypt($source, $privKey, false)) return false;
		return $encrypted;
	}
}
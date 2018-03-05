<?php

namespace MCU\Utils\Crypt;

class DES
{
	const KEY_SIZE = 24;
	public static function getIV()
	{
		$iv_size = mcrypt_get_iv_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_CBC);
		return mcrypt_create_iv($iv_size, MCRYPT_RAND);
	}

	private static function fitKey($key)
	{
		$oKey = $key;
		while(strlen($oKey) < self::KEY_SIZE)
		{
			$oKey .= $key;
		}
		$fitKey = substr($oKey, 0, self::KEY_SIZE);
		return $fitKey;
	}

	public static function encrypt($source, $key, $iv)
	{
		$blocksize = mcrypt_get_block_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_CBC);
        $source = self::pkcsPad($source, $blocksize);

		return mcrypt_encrypt(MCRYPT_TRIPLEDES, self::fitKey($key), $source, MCRYPT_MODE_CBC, $iv);
	}

	public static function decrypt($source, $key, $iv)
	{
		$result = mcrypt_decrypt(MCRYPT_TRIPLEDES, self::fitKey($key), $source, MCRYPT_MODE_CBC, $iv);
		$result = self::pkcsUnpad($result);
		return $result;
	}

	private static function pkcsPad($source, $blocksize) {
        $pad = $blocksize - (strlen($source) % $blocksize);
        return $source . str_repeat(chr($pad), $pad);
    }

	private static function pkcsUnpad($source) {
        $pad = ord($source {strlen($source) - 1});
        if ($pad > strlen($source))
            return false;
 
        if (strspn($source, chr($pad), strlen($source) - $pad) != $pad)
            return false;
 
        return substr($source, 0, - 1 * $pad);
    }
}
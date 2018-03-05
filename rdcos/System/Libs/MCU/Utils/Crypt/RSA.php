<?php

namespace MCU\Utils\Crypt;

class RSA
{
	const MAX_ENCRYPT_LENGTH = 117;
	const MAX_DECRYPT_LENGTH = 128;

	public static function getPubKey($cerFilePath)
	{
		if(!file_exists($cerFilePath)) return false;
		$cerContent = file_get_contents($cerFilePath);

		$pem = chunk_split(base64_encode($cerContent), 64, PHP_EOL);//转换为pem格式的公钥
		$pem = "-----BEGIN CERTIFICATE-----" . PHP_EOL . $pem . "-----END CERTIFICATE-----" . PHP_EOL;

		return $pem;
		//return openssl_get_publickey($pem);
	}

	public static function getPrivKey($pfxFilePath, $password)
	{
		if(!file_exists($pfxFilePath)) return false;
		$pfxFileContent = file_get_contents($pfxFilePath);

        if(!openssl_pkcs12_read($pfxFileContent, $certs, $password)) return false;
		return $certs["pkey"];
	}

	public static function encrypt($source, $key, $usePub = true)
	{
		$output = "";
		while($source)
		{
			$input = substr($source, 0, self::MAX_ENCRYPT_LENGTH);
			$source = substr($source, self::MAX_ENCRYPT_LENGTH);
			if($usePub)
			{
				$ok = openssl_public_encrypt($input, $encrypted, $key);
			}
			else
			{
				$ok = openssl_private_encrypt($input, $encrypted, $key);
			}
			if(!$ok) return false;
			$output .= $encrypted;
		}
		if(is_resource($key)) openssl_free_key($key);
		return $output;
    }

	public static function decrypt($source, $key, $usePub = true)
	{
		$output = "";
		while($source)
		{
			$input = substr($source, 0, self::MAX_DECRYPT_LENGTH);
			$source = substr($source, self::MAX_DECRYPT_LENGTH);
			if($usePub)
			{
				$ok = openssl_public_decrypt($input, $encrypted, $key);
			}
			else
			{
				$ok = openssl_private_decrypt($input, $encrypted, $key);
			}
			if(!$ok) return false;
			$output .= $encrypted;
		}
		if(is_resource($key)) openssl_free_key($key);
		return $output;
	}

    public static function sign($source, $key)
    {
		if (!openssl_sign($source, $signed, $key, OPENSSL_ALGO_SHA1)) return false;
		return $key;
	}
}
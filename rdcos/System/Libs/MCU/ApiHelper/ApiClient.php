<?php
namespace MCU\ApiHelper;
use \MCU\Cache;
/**
 * API调用类
 */
class ApiClient
{    
    public static function req($action, $params = null,$url='http://127.0.0.1/')
    {
        $response = self::request($action, $params,$url);
        if($response['result'] == false)
        {
        	return false;
        }
        else
        {
        	return $response['data'];
        }
    }

    public static function request($action, $params = null,$url='http://127.0.0.1/')
    {
    	$actArr = explode('.', $action);

        if(isset($params['token']))
        {
            $token = $params['token'];
            unset($params['token']);
        }
        else
        {
            $token = Cache::get('apiToken:'.md5($url));
        }
    	
    	if($token === false || $token == '' || $token == null )
    	{
    		$token = self::getToken($url);
    	}

    	if(count($actArr) == 2)
    	{
    		$url .= 'api.php?action='.$actArr[0].'&method='.$actArr[1].'&token='.$token;
    		$content = self::httpRequest($url,$params);
	        if($content !== false)
	        {
	        	$content = json_decode($content,true);
	        }
	        if(is_array($content))
	        {	
	        	return $content;
	        }
	        else
	        {
	        	return ['result'=>false];
	        }    
    	}
    	else
    	{
    		return ['result'=>false];
    	}
    }

    public static function getToken($url)
    {
    	$data['did'] = 'pad'.md5(time());
    	$data['key'] = md5(substr(md5($data['did']),5,13).'enH');
    	$content = self::httpRequest($url.'sys..get_token',$data);
    	$res = json_decode($content,true);
    	if(isset($res['token']))
    	{	
    		Cache::set('apiToken:'.md5($url),$res['token']);
    		return $res['token'];
    	}
    	else
    	{
    		return false;
    	}
    }

    public static function httpRequest($url,$postData)
    {
    	$curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, [
	        "User-Agent: ENHRDC Endpoint/1.0",
	        "Cache-Control: max-age=0",
	    ]);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // exec 时， 以文本流返回, 若设置为0则自动输出内容， 同时返回true
	    curl_setopt($curl, CURLOPT_HEADER, 0);  // 启用时会将头文件的信息作为数据流输出。
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	    if(!empty($postData))
	    {
	    	curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
	    }
	    $content = curl_exec($curl);
	    $response_info = curl_getinfo($curl);
	    curl_close($curl);
	    return $content;
    }
}
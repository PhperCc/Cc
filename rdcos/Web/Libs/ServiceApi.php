<?php
class ServiceApi
{
    public static function req($action, $params = null)
    {
        $response = self::request($action, $params);
        if($response["result"] == false) return false;
        return $response["data"];
    }

    public static function request($action, $params = null)
    {
        $request = ["seq" => time(), "action" => $action, "params" => $params];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "http://127.0.0.1:1226/?req=" . json_encode($request));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "User-Agent: ENHRDC Endpoint/1.0",
            "Cache-Control: max-age=0",
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // exec 时， 以文本流返回, 若设置为0则自动输出内容， 同时返回true
        curl_setopt($curl, CURLOPT_HEADER, 0);  // 启用时会将头文件的信息作为数据流输出。
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $content = curl_exec($curl);
        $response_info = curl_getinfo($curl);
        curl_close($curl);

        if($content === false) return ["result" => false, "info" => "", "data" => $response_info];

        return json_decode($content, true);
    }
}
<?php

/**
 * 系统APi服务
 */
use MCU\Kernel;
use MCU\Cache;

class sys_ApiServer extends Kernel
{
    public function start()
    {
        $this->_worker->onMessage = function($conn, $message)
        {
            if(isset($message['get']['req']))
            {
                $message = $message['get']['req'];
            }
            /**
             * API 调用消息最外层是 JSON 编码格式
             */
            if(null === $request = json_decode($message, true))
            {
                $conn -> send(json_encode(["seq" => 0, "result" => false, "info" => "invalid request format"]));
                return;
            }
            /**
             * 消息内容必须包含 action， 表达需要执行的 API 方法
             */
            if(!array_key_exists("action", $request))
            {
                $conn -> send(json_encode(["seq" => 0, "result" => false, "info" => "invalid request format: param 'action' not found"]));
                return;
            }

            $seq = array_key_exists('seq', $request) ? $request['seq'] : null;
            $action = $request["action"];
            $params = array_key_exists("params", $request) ? $request["params"] : null;

            /**
             * action 是由具体的 API 类名与 API 方法名组合而成， 中间用 . 分割
             */
            list($action_class, $action_method) = explode(".", $action);
            if(empty($action_class) || empty($action_method))
            {
                return;
            }
            /**
             * 加载 API 实现类文件
             */
            $action_class_file = SYS_ROOT."Api/$action_class.php";
            if(!file_exists($action_class_file))
            {
                $Mods = Cache::get('sys:starters');
                if(isset($Mods[1]))
                {
                    $action_class_file = BASE_ROOT."/Apps/".$Mods[1]."/Api/$action_class.php";
                    if(!file_exists($action_class_file))
                    {
                        $conn->send(json_encode(["seq" => $seq, "result" => false, "info" => "invalid action $action: $action_class not found"]));
                        return;
                    }
                }
                else
                {
                    $conn->send(json_encode(["seq" => $seq, "result" => false, "info" => "invalid action $action: $action_class not found"]));
                    return; 
                }
                
            }

            include_once($action_class_file);
            $action_class_name = "api_$action_class";
            $api = new $action_class_name();
            if(!method_exists($api, $action_method))
            {
                $conn -> send(json_encode(["seq" => $seq, "result" => false, "info" => "invalid action $action: $action_method not found in $action_class"]));
                return;
            }
            //验证token
            if(isset($params['token']))
            {
                $token_val = intval(Cache::get('enhtoken:'.$params['token']));
                if($token_val == 0)
                {
                    $conn -> send(json_encode(["seq" => $seq, "result" => false, "info" => "verification failure"]));
                    return; 
                }
                unset($params['token']);
            }
            else
            {
                $conn -> send(json_encode(["seq" => $seq, "result" => false, "info" => "token is empty"]));
                return;
            }

            $info = "";
            $data = null;
            /**
             * 调用 API 类中的 action 方法
             */
            $result = $api -> $action_method($params);

            if($result !== true && $result !== false)
            {
                $info = $result["info"];
                $data = $result["data"];
                $result = $result["result"];
            }

            /**
             * 向调用方返回处理结果
             */
            $conn -> send(json_encode(["seq" => $seq, "result" => $result, "info" => $info, "data" => $data]));
        };
    }
}

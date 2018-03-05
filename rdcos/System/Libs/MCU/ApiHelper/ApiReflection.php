<?php
namespace MCU\ApiHelper;

/**
 * 类结构反射信息类
 */
class ApiReflection
{
    private $_content = "";

    private $_desc = "";
    private $_params = [];
    private $_returns = [];
    private $_examples = [];

    public static function reflect($api_root, $api_file_path)
    {
        if(!file_exists($api_file_path))
        {
            throw new Exception("Reflect file not found: $api_file_path");
        }

        $api_name = str_replace("$api_root/", "", $api_file_path);
        $api_name = str_replace(".php", "", $api_name);
        if($api_name == "ApiBase") return false;

        include_once($api_file_path);

        $reflection = self::do_reflect($api_name);
        return [
            'api_name' => $api_name,
            'reflection' => $reflection,
        ];
    }

    private static function do_reflect($api_name)
    {
        $class_name = "api_$api_name";
        if(!class_exists($class_name))
        {
            throw new Exception("Reflect class not found: $class_name");
        }

        $reflection = [];

        $reflector = new \ReflectionClass($class_name);
        $comment = $reflector -> getDocComment();
        $comment_info = new ApiCommentInfo($comment);
        $reflection["desc"] = $comment_info -> desc;

        //遍历所有的方法
        $reflection["methods"] = [];
        $methods = $reflector -> getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method)
        {
            $method_name = $method -> getName();
            $method_name = str_replace("method_", "", $method_name);
            if(substr($method_name,0,2) == '__') continue;
            $comment_info = new ApiCommentInfo($method -> getDocComment());
            $comment_desc = $comment_info -> desc;
            $comment_params = $comment_info -> params;
            $comment_returns = $comment_info -> returns;
            $comment_examples = $comment_info -> examples;
            if(!array_key_exists("result", $comment_returns))
            {
                $comment_returns["result"] = ["desc" => "操作是否成功", "type" => "bool"];
            }

            

            $reflection["methods"][$method_name] = [];

            $method_param_signs = [];
            foreach($comment_params as $param_name => $param_info)
            {
                $sign = "<i>{$param_info['type']}</i> <b>$param_name</b>";
                if($param_info["omitable"] == 1) $sign = "[$sign]";
                $method_param_signs[] = $sign;
            }
            $method_sign = "<b>$api_name.$method_name</b>(" . implode(", ", $method_param_signs) . ")";

            $example_html = "";
            if(count($comment_examples) > 0)
            {
                foreach($comment_examples as $example)
                {
                    if($example["is_title"])
                    {
                        $example_html .= "<b>{$example['content']}</b><br />";
                    }
                    else
                    {
                        $example_html .= "{$example['content']}<br />";
                    }
                }
            }
            else
            {
                //{"seq":123344,"uid":"enh","body":{"action":"Endpoint.online_list"}}
                // 这里是提交协议的一个示例， 返回当前在线的设备列表。
                $method_example_body = ["seq" => time(), "action" => "$api_name.$method_name"];
                if(count($comment_params) > 0)
                {
                    $method_example_body["params"] = [];
                    foreach($comment_params as $param_name => $param_info)
                    {
                        $param_example_value = "__$param_name" . "__";
                        if($param_name == "uid") $param_example_value = "__uid__";
                        $method_example_body["params"][$param_name] = $param_example_value;
                    }
                }

                $example_html = json_encode($method_example_body, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            }

            $reflection["methods"][$method_name]["desc"] = $comment_desc;
            $reflection["methods"][$method_name]["params"] = $comment_params;
            $reflection["methods"][$method_name]["returns"] = $comment_returns;
            $reflection["methods"][$method_name]["sign"] = $method_sign;
            $reflection["methods"][$method_name]["example"] = $example_html;
        }

        return $reflection;
    }
}
<?php
namespace MCU\ApiHelper;

/**
 * 注释分析类
 */
class CommentInfo
{
    private $_content = "";

    private $_desc = "";
    private $_params = [];
    private $_returns = [];
    private $_examples = [];

    public function __construct($comment_content)
    {
        $this -> _content = $comment_content;
        $lines = explode("\n", $comment_content);
        foreach($lines as &$line)
        {
            $line = $this -> clear($line);
            if(empty($line)) continue;

            if(false !== $line_param_info = $this -> get_param_info($line))
            {
                $param_name = $line_param_info["name"];
                unset($line_param_info["name"]);
                $this -> _params[$param_name] = $line_param_info;
                continue;
            }

            if(false !== $line_return_info = $this -> get_return_info($line))
            {
                $return_name = $line_return_info["name"];
                unset($line_return_info["name"]);
                $this -> _returns[$return_name] = $line_return_info;
                continue;
            }

            if(false !== $line_example_info = $this -> get_example_info($line))
            {
                $this -> _examples[] = $line_example_info;
                continue;
            }

            $this -> _desc .= "$line\n";
        }
    }

    public function __get($property)
    {
        switch($property)
        {
            case "desc": return $this -> _desc;
            case "params": return $this -> _params;
            case "returns": return $this -> _returns;
            case "examples": return $this -> _examples;
            default: return null;
        }
    }

    private function clear($line)
    {
        $cleared = preg_replace("/^\/+\*+/", "", trim($line));
        $cleared = preg_replace("/^\*+\/+/", "", $cleared);
        return preg_replace("/^[\s\*]*/", "", $cleared);
    }

    private function get_param_info($line)
    {
        $matched = preg_match("/^[\*\s@]+([^\s]+)(?:\s+([^\s]+)(?:\s+(.*))?)?/", $line, $matches);
        if($matched === false || $matched === 0) return false;

        $info = ["name" => "", "type" => "mixed", "omitable" => false, "desc" => ""];
        $matcheds_count = count($matches) - 1;
        if($matcheds_count >= 1) $info["name"] = $matches[1];
        if($matcheds_count >= 2) $info["desc"] = $matches[2];
        if($matcheds_count >= 3)
        {
            $info["type"] = $matches[2];
            $info["desc"] = $matches[3];
        }
        if(substr($info["name"], 0, 1) == "#")
        {
            $info["name"] = substr($info["name"], 1);
            $info["omitable"] = 1;
        }
        return $info;
    }

    private function get_return_info($line)
    {
        $matched = preg_match("/^return\s+([^\s]+)(?:\s+([^\s]+)(?:\s+(.*))?)?/", $line, $matches);
        if($matched === false || $matched === 0) return false;

        $info = ["name" => "", "type" => "", "desc" => ""];
        $matcheds_count = count($matches) - 1;
        if($matcheds_count >= 1) $info["name"] = $matches[1];
        if($matcheds_count >= 2) $info["desc"] = $matches[2];
        if($matcheds_count >= 3)
        {
            $info["type"] = $matches[2];
            $info["desc"] = $matches[3];
        }
        if($info["name"] == "result") $info["type"] = "bool";
        if($info["name"] == "info") $info["type"] = "string";
        if($info["name"] == "data" && empty($info["type"])) $info["type"] = "mixed";

        return $info;
    }

    private function get_example_info($line)
    {
        $matched = preg_match("/^[\*\s!]+(#)?(.*)/", $line, $matches);
        if($matched === false || $matched === 0) return false;

        return ["is_title" => ($matches[1] == "#"), "content" => $matches[2]];
    }
}
<?php
class WebModule
{
    public $config = array();
    public $url_host = '';
    public $url_path = '';
    public $title = '';
    public $uc = '';
    public $ua = '';
    public $req_method = '';
    public $class_name = '';
    public $table_name = '';
    public $powers = array();
    public $is_debug = false;

    public $data = array();

    public $ignore_view = array();
    public $ignore_login = array();   // 不需要登录的action
    public $ignore_main = array();
    public $hide_menu = array();

    private $__time_start = 0;

    public function __construct ($config)
    {
        if(defined('IS_DEBUG') && IS_DEBUG) $this -> is_debug = true;
        $this -> __time_start = microtime(true);
        if(is_array($config)) $this -> config = $config;
        $this -> __init();
        if(empty($this -> title))      $this -> title      = str_replace("ctrl_", "", get_class($this));
        if(empty($this -> table_name)) $this -> table_name = str_replace("ctrl_", "", get_class($this));
    }

    public function __init()
    { }

    public function __before_render()
    { }

    public function set_view($ua, $uc = '')
    {
        $this -> ua = $ua;
        if(!empty($uc)) $this -> uc = $uc;
    }

    public function show_error($msg, $data = null)
    {
        if($this -> req_method == 'ajax')
        {
            return $this -> ajax_return(0, $data, $msg);
        }
        else
        {
            $this -> set_view('error', 'sys');
            $this -> data['msg'] = $msg;
        }
    }

    public function ajax_return($success = 1, $data = null, $message = '')
    {
        $real_success = $success;
        if(is_bool($real_success))
        {
            $real_success = $real_success ? 1 : 0;
        }
        if(is_numeric($real_success))
        {
            $real_success = $real_success > 0 ? 1 : 0;
        }
        else if(is_string($real_success))
        {
            $real_success = empty($real_success) ? 0 : 1;
        }
        else
        {
            $real_success = $real_success == null ? 0 : 1;
        }

        $rtn = array(
            'result' => $real_success,
            'data'   => ($data == null ? array() : $data),
            'msg'    => $message
        );
        echo json_encode($rtn);
    }

    public function __time_log()
    {
        $microtime_span = round((microtime(true) - $this -> __time_start) * 1000000) / 1000;
        $this -> print_log("耗时 $microtime_span 毫秒");
    }

    public function print_log($content, $important = false)
    {
        if($this -> is_debug)
        {
            $info = $content;
            if($important) $info = "<span style='color:#f00'>$info</span>";
            $microtime = round((microtime(true) - ST) * 1000000) / 1000;
            echo "<span style='color:#00f;'>$microtime ms</span> &nbsp;&nbsp; $info <br /> \r\n";
        }
    }
    /**
     * 发送通知数据
     */
    protected function publish($topic,$body)
    {
        $topic = 'SYS.'.$topic;
        $fp=stream_socket_client('tcp://127.0.0.1:'.CHANNEL_PORT, $errno, $errstr);
        if(!$fp)
        {
          return  "erreur : $errno - $errstr";
        }
        else
        {
            $data = serialize(array('type' => 'publish', 'channels'=>(array)$topic, 'data' => $body));
            $total_length = 4 + strlen($data);
            $data = pack('N', $total_length) . $data;
            fwrite($fp,$data);
            fclose($fp);
            return true;
        }
    }
    
}
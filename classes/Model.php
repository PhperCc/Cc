<?php
namespace classes;
// 异常处理类型
define('MODEL_EXCEPTION_MODE_NONE'  , 0x00);    // 静默
define('MODEL_EXCEPTION_MODE_THROW' , 0x01);    // 抛出异常
define('MODEL_EXCEPTION_MODE_LOG'   , 0x10);    // 记录日志
class Model
{
    private static $exception_mode = MODEL_EXCEPTION_MODE_LOG;
    private static $db_configs = array();
    private static $safe_db_configs = array();
    private static $db_cache = array();

    //获取数据库配置文件
    public static function set_db_config($config, $aliase = 'default')
    {
        if(false === self :: check_config($config)) return false;

        self::$db_configs[$aliase] = $config;
        // p(self::$db_configs);
    }

    public static function set_safe_db_config($config, $aliase = 'default')
    {
        if(false === self::check_config($config)) return false;
        self::$safe_db_configs[$aliase] = $config;
    }

    public static function get_safe_db_config($aliase = 'default')
    {
        return array_key_exists($aliase, self::$safe_db_configs) ? self::$safe_db_configs[$aliase] : null;
    }

    //验证数据库配置文件
    private static function check_config(&$config)
    {
        if(!is_array($config) ||
            !array_key_exists('dbname', $config) ||
            !array_key_exists('user', $config) ||
            !array_key_exists('password', $config)
        )
        {
            echo "数据库连接配置中应包含: dbname, user, password";
            return false;
        }
        $adapter = 'Mysql';
        if(array_key_exists("adapter", $config)) $adapter = ucfirst($config['adapter']);

        if(!array_key_exists('host', $config))
        {
            echo "Mysql 数据库连接配置中应包含: host";
            return false;
        }
        $config['adapter'] = $adapter;

        $config['model_cache_hash'] = md5(json_encode($config));
        return true;
    }

    //？？？？
    public static function get_db_config($aliase = 'default')
    {
        return array_key_exists($aliase, self::$db_configs) ? self::$db_configs[$aliase] : null;
    }

    private $db_config       = null;
    private $safe_db_config  = null;

    private $table_name      = '';
    private $fields          = '';
    private $join            = '';
    private $where           = '';
    private $orderby         = '';
    private $groupby         = '';
    private $page_start      = 0;
    private $limit           = 0;
    private $param           = array();
    private $debug           = false;
    private $last_sql_error = '';
    /**
     * FETCH_ASSOC : 2
     * FETCH_NUM   : 3
     * FETCH_BOTH  : 4
    */
    private $query_fetch_type = \PDO::FETCH_ASSOC;


    private $last_query = array('sql' => '', 'params' => array());

    
    function __construct($tableName = '', $db_aliase = 'default')
	{
        // if(defined(IS_DEBUG) && IS_DEBUG) $this -> debug();
		$this -> table_name = $tableName;
        $this -> change_db($db_aliase);
        // $this -> __init();
	}

    //select
    public function select($where = '')
    {
        if(!empty($where)) $this -> where = $where;
        $sql = $this -> create_select_sql();
        P($sql);
        return $this -> query($sql, $this -> param);
    }


    //创建select 语句
    private function create_select_sql()
    {
        $true_table_name = $this -> table_name;
        $true_fields  = empty($this -> fields)   ? "*" :  $this -> fields;
        $true_join    = empty($this -> join)     ? ""  :  $this -> join;
        $true_where   = empty($this -> where)    ? ""  : "WHERE "     . $this -> where;
        $true_orderby = empty($this -> orderby)  ? ""  : "ORDER BY "  . $this -> orderby;
        $true_groupby = empty($this -> groupby)  ? ""  : "GROUP BY "  . $this -> groupby;
        $true_page    = ($this -> limit <= 0)    ? ""  : "LIMIT " . $this -> page_start . ", " . $this -> limit;

        return "SELECT $true_fields FROM $true_table_name $true_join $true_where $true_groupby $true_orderby $true_page";
    }

    public function query($sql, $params = null, $is_write_query = false)
    {
        $db_entity = $this -> get_db_entity();
        if($db_entity == null)
        {
            echo "数据库连接未设置";
            die;
        }
        $rtn = $this -> do_query($sql, $params, $db_entity);

        if($rtn !== false && $is_write_query)
        {
            $db_entity = $this -> get_db_entity(true);
            if($db_entity !== null)
            {
                $this -> title .= " [备份写入]";
                $rtn = $this -> do_query($sql, $params, $db_entity);
            }
        }

        return $rtn;
    }

    private function do_query($sql, $params, $db_entity)
    {
       
        $rtn = $this -> __before_query($sql, $params, $db_entity);
        if($rtn !== true)
        {
            if($this -> debug) echo "__before_query 返回 false， 不执行查询 <br />\r\n";
        }

        $db_entity -> set_fatch_type($this -> query_fetch_type);
		
		// zjfree @ 2015-09-07 将执行时间多于1秒的查询写入日志文件
		$run_begin_time = round(microtime(TRUE), 3);
        $rtn = $db_entity -> query($sql, $params);
		$run_end_time = round(microtime(TRUE), 3);
		$run_time = $run_end_time - $run_begin_time;
		if ($run_time > 0.5)
		{
			$run_time = round($run_time, 3);
			$log_path = APP_ROOT . "/log";
			Tool::create_dir($log_path);
			$log_path = "$log_path/sql_time_over_" . date('Y_m_d') . ".log";
			$backtrace = debug_backtrace();
			$trace_info = "trace: \r\n";
			foreach($backtrace as $t)
			{
				$file = array_key_exists('file', $t) ? $t['file'] : '';
				$file = str_replace("\\", "/", $file);
				$line = array_key_exists('line', $t) ? $t['line'] : '';
				$function = array_key_exists('function', $t) ? $t['function'] : '';

				$trace_info .= "$file ($line) $function(); \r\n";
			}

			$str_log = date('Y-m-d H:i:s') . " 执行用时: $run_time\r\n$sql\r\n$trace_info\r\n";
			file_put_contents($log_path, $str_log, FILE_APPEND|LOCK_EX);
		}
		
        $this -> last_query = array(
            'sql'    => $sql,
            'params' => $params
        );

        if($rtn === false)
        {
            $this -> last_sql_error = $db_entity -> get_last_error();

            $error_info = "SQL Error: " . $this -> last_sql_error . " \r\n\r\n";
            $error_info .= "SQL : $sql \r\n";
            if(count($params) > 0)
            {
                $error_info .= "Params : \r\n";
                foreach($params as $k => $v) $error_info .= "\t$k: $v \r\n";
            }
            $error_info .= "\r\n";

            if(Model::$exception_mode == MODEL_EXCEPTION_MODE_LOG)
            {
                $backtrace = debug_backtrace();
                $trace_info = "trace: \r\n";
                foreach($backtrace as $t)
                {
                    $file = array_key_exists('file', $t) ? $t['file'] : '';
                    $file = str_replace("\\", "/", $file);
                    $line = array_key_exists('line', $t) ? $t['line'] : '';
                    $function = array_key_exists('function', $t) ? $t['function'] : '';

                    $trace_info .= "$file ($line) $function(); \r\n";
                }

                $error_info = date('Y-m-d H:i:s') . "\r\n$error_info\r\n$trace_info\r\n";
                $error_info .= "query: " . $_SERVER["QUERY_STRING"]."\r\n";
                $error_info .= "refer: " . $_SERVER["HTTP_REFERER"]."\r\n";

                $log_path = APP_ROOT . "/log";
                Tool::create_dir($log_path);
                $log_file_path = "$log_path/sql_error_" . date('Y_m_d') . ".log";
                if(strpos($this -> last_sql_error, "Duplicate entry") > 0)
                {
                    $log_file_path = "$log_path/sql_error_duplicate_" . date('Y_m_d') . ".log";
                }
                file_put_contents($log_file_path, $error_info, FILE_APPEND|LOCK_EX);
            }
            else if(Model::$exception_mode == MODEL_EXCEPTION_MODE_THROW)
            {
                throw new Exception($error_info);
            }
        }

        if($this -> debug)
        {
            echo "<style type='text/css'>";
            echo "ul{list-style:none;padding-left:5px;}";
            echo "ul li{list-style:none;}";
            echo ".sql_tbl{width:1000px;background-color:#ccc;margin-top:10px;line-height:22px;font-size:12px;}";
            echo ".sql_tbl td{padding:2px 5px;min-height:22px;background-color:#fff;}";
            echo ".sql_tbl td.key{width:100px;}";
            echo ".sql_tbl td.title{font-weight:bold;}";
            echo ".bold{font-weight:bold;}";
            echo ".red{color:#f00;}";
            echo ".blue{color:#00f;}";
            echo ".green{color:#0f0;}";
            echo ".sql_tbl td .inner{background-color:#ffe;}";
            echo "</style>";
            echo "<table class='sql_tbl' cellspacing='1' cellpadding='0'>";
            echo "<tr><td class='key title'>SQL</td>";
            echo "<td>$sql</td></tr>";
            if(!empty($this -> title))
            {
                echo "<tr><td class='key title'>TITLE</td>";
                echo "<td class='bold'>" . $this -> title . "</td></tr>";
            }

            if($rtn === false)
            {
                echo "<tr><td class='key title'>ERROR</td>";
                echo "<td class='red'>" . $this -> last_sql_error . "</td></tr>";
            }
            else
            {
                echo "<tr><td class='key title'>RETURN</td>";
                $rtn_text = $rtn;
                if(is_array($rtn))
                {
                    $rtn_text = "ARRAY [" . count($rtn) . "]";
                    if(count($rtn) == 1)
                    {
                        $row = $rtn[0];
                        $fields = array_keys($row);
                        $rtn_text .= "<ul class='inner'>";
                        foreach($fields as $field)
                        {
                            if(is_int($field)) continue;

                            $value = $row[$field];
                            if($value == null) $value = '[NULL]';
                            $rtn_text .= "<li><span class='blue bold'>$field</span> : " . $row[$field] . "</li>";
                        }
                        $rtn_text .= "<ul>";
                    }
                }
                else if(is_int($rtn))
                {
                    $rtn_text = "影响行数 : $rtn";
                }
                echo "<td>$rtn_text</td></tr>";

                echo "<tr><td class='key title'>USED TIME</td>";
                echo "<td>" . round($db_entity -> cmd_info[count($db_entity -> cmd_info) - 1]['time'] * 1000000) / 1000 . " ms</td></tr>";
            }

            if(is_array($params))
            {
                foreach($params as $k=>$v)
                {
                    echo "<tr><td class='key'>$k</td>";
                    echo "<td>$v</td></tr>";
                }
            }
            echo "</table>";
        }
        
        return $rtn;
    }


    private function get_db_entity($is_safe_db = false)
    {
        P( $this -> db_config);
        $db_config = $is_safe_db ? $this -> safe_db_config : $this -> db_config;
        P($db_config);
        if($db_config == null) return null;

        $db_entity = null;
        $cache_key = $db_config['model_cache_hash'];
        if(!array_key_exists($cache_key, Model::$db_cache))
        {
            $db_cls_name = "DB" . $db_config['adapter'];
            $db_entity = new DBMysql($db_config);
            Model::$db_cache[$cache_key] = $db_entity;
        }
        else
        {
            $db_entity = Model::$db_cache[$cache_key];
        }
        return $db_entity;
    }

    public function change_db($aliase = 'default')
    {
        if(array_key_exists($aliase, Model::$db_configs))
        {
            $this -> db_config = Model::$db_configs[$aliase];
        }

        if(array_key_exists($aliase, Model::$safe_db_configs))
        {
            $this -> safe_db_config = Model::$safe_db_configs[$aliase];
        }
        return $this;
    }

    public function __before_query(&$sql, $params, $db_entity)
    {
        return true;
    }


}
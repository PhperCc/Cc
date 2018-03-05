<?php
namespace classes\DBbase;
use \classes\Logger;

/**
 * Class Operator
 */
class Operator
{
    // 异常处理类型
    const EXCEPTION_MODE_NONE  = 0x00;   // 静默
    const EXCEPTION_MODE_THROW = 0x00;   // 抛出异常
    const EXCEPTION_MODE_LOG   = 0x00;   // 记录日志

    private static $exception_mode = self::EXCEPTION_MODE_LOG;
    private static $db_configs = [];
    private static $connection_cache = [];

    public static function set_config($config, $name = 'default')
    {
        if(!is_array($config) || !array_key_exists('dsn', $config))
        {
            throw new \Exception("db config needs param 'dsn'");
            return false;
        }

        $config['config_hash_key'] = md5(json_encode($config));
        self::$db_configs[$name] = $config;
   
        return true;
    }

    public function get_config($name = 'default')
    {
        return array_key_exists($name, self::$db_configs) ? self::$db_configs[$name] : null;
    }

    /*
     * 设置异常处理类型
    */
    public function set_exception_mode($mode)
    {
        self::$exception_mode = $mode;
    }

    private function log($content)
    {
        Logger::log($content, 'DatabaseOperator');
    }

    private $db_config       = null;

    private $table_name      = '';
    private $fields          = '';
    private $join            = '';
    private $where           = '';
    private $orderby         = '';
    private $groupby         = '';
    private $page_start      = 0;
    private $limit           = 0;
    private $title           = '';   // 仅调试时有用， 用于标识一条语句的作用
    private $param           = array();
    private $debug           = false;

    /**
     * FETCH_ASSOC : 2
     * FETCH_NUM   : 3
     * FETCH_BOTH  : 4
    */
    private $query_fetch_type = \PDO::FETCH_ASSOC;
    private $last_sql_error = '';

    private $last_query = array('sql' => '', 'params' => array());

    function __construct($tableName = '', $name = 'default')
	{
		$this -> table_name = $tableName;
        $this -> change_db($name);
        $this -> __init();
	}

    public function __init()
    { }

    public function debug($debugmod = true)
    {
        $this -> debug = $debugmod;
        return $this;
    }

    public function __before_query(&$sql, $params, $db_connection)
    {
        return true;
    }

    private function get_db_connection()
    {
        $db_connection = null;
        $cache_key = $this -> db_config['config_hash_key'];
        if(!array_key_exists($cache_key, static::$connection_cache))
        {
            $dns        = $this -> db_config['dsn'];
            $user       = array_key_exists('user', $this -> db_config) ? $this -> db_config['user'] : '';
            $password   = array_key_exists('password', $this -> db_config) ? $this -> db_config['password'] : '';

            $db_connection = new Adapter($dns, $user, $password);
            static::$connection_cache[$cache_key] = $db_connection;
        }
        else
        {
            $db_connection = static::$connection_cache[$cache_key];
        }
        return $db_connection;
    }

    public function change_db($name = 'default')
    {
        if(!array_key_exists($name, static::$db_configs))
        {
            throw new \Exception("dbconfig named $name is not reged!");
        }

        $this -> db_config = static::$db_configs[$name];
        return $this;
    }

    public function query($sql, $params = null)
    {
        $db_connection = $this -> get_db_connection();
        $rtn = $this -> do_query($sql, $params, $db_connection);

        return $rtn;
    }

    private function do_query($sql, $params, $db_connection)
    {
        $rtn = $this -> __before_query($sql, $params, $db_connection);
        if($rtn !== true)
        {
            return;
        }

        $db_connection -> set_fatch_type($this -> query_fetch_type);
        $rtn = $db_connection -> query($sql, $params);

        $this -> last_query = array(
            'sql'    => $sql,
            'params' => $params
        );

        if($rtn === false)
        {
            $this -> last_sql_error = $db_connection -> get_last_error();

            $error_info = "SQL Error: " . $this -> last_sql_error . " \n";
            $error_info .= "SQL : $sql \n";
            $error_info .= "DSN : {$this -> db_config['dsn']} \n";

            $params_count = count($params);
            $error_info .= "Params : (count: $params_count)\n";
            if($params_count > 0)
            {
                $error_info .= "Params : \n";
                foreach($params as $k => $v) $error_info .= "\t$k: $v \n";
            }
            $error_info .= "\n";

            if(static::$exception_mode == static::EXCEPTION_MODE_LOG)
            {
                $backtrace = debug_backtrace();
                $trace_info = "trace: \n";
                foreach($backtrace as $t)
                {
                    $file = array_key_exists('file', $t) ? $t['file'] : '';
                    $file = str_replace("\\", "/", $file);
                    $line = array_key_exists('line', $t) ? $t['line'] : '';
                    $function = array_key_exists('function', $t) ? $t['function'] : '';

                    $trace_info .= "$file ($line) $function(); \n";
                }

                static::log("$error_info\n$trace_info");
            }
            else if(static::$exception_mode == static::EXCEPTION_MODE_THROW)
            {
                throw new \Exception($error_info);
            }
        }

        if($this -> debug)
        {
            $debug_info = [];
            $debug_info['sql'] = $sql;
            $debug_info['sql'] = $sql;

            if(!empty($this -> title))
            {
                $debug_info['title'] = $this -> title;
            }

            if($rtn === false)
            {
                $debug_info['error'] = $this -> last_sql_error;
            }
            else
            {
                $debug_info['return'] = $rtn;
            }

            if(is_array($params))
            {
                $debug_info['params'] = $params;
            }
            static::log(print_r($debug_info, true));
        }

        return $rtn;
    }

    public function get_last_error()
    {
        return $this -> last_sql_error;
    }

    public function clear_last_error()
    {
        return $this -> last_sql_error = '';
    }

    public function get_last_query()
    {
        return $this -> last_query;
    }

    public function fields()
    {
        $params = func_get_args();
        $params_count = count($params);

        if($params_count == 0)
        {
            $this -> fields = '*';
        }
        else if($params == 1)
        {
            $this -> fields = $params[0];
        }
        else
        {
            $this -> fields = implode(', ', $params);
        }
        return $this;
    }

    public function join($join = '')
    {
        if($join != '')
        {
            $this -> join = $this -> join . " " . $join;
        }
        return $this;
    }

    public function where($where = '')
    {
        $this -> where = $where;
        return $this;
    }

    public function orderby($orderby = '')
    {
        $this -> orderby = $orderby;
        return $this;
    }

    public function groupby($groupby = '')
    {
        $this -> groupby = $groupby;
        return $this;
    }

    /*
     * limit($top)
     * limit($page_start, $limit)
    */
    public function limit($limit1, $limit2 = 0)
    {
        if(!is_int($limit1)) $limit1 = intval($limit1);
        if(!is_int($limit2)) $limit2 = intval($limit2);

        if($limit2 <= 0 && $limit1 > 0)
        {
            $this -> page_start = 0;
            $this -> limit = $limit1;
        }
        if($limit2 > 0 && $limit1 >= 0)
        {
            $this -> page_start = $limit1;
            $this -> limit = $limit2;
        }

        return $this;
    }

    public function param($param = array(), $param_val = null)
    {
        if(is_array($param) && count($param) > 0)
        {
            foreach($param as $k=>$v) $this -> param[$k] = $v;
        }

        if(is_string($param) && $param_val != null)
        {
            $this -> param[$param] = $param_val;
        }
        return $this;
    }

    public function reset($property = null)
    {
        $reset_properties = array(
            'fields'    => "",
            'join'      => "",
            'where'     => "",
            'orderby'   => "",
            'groupby'   => "",
            'limit'     => 0,
            'title'     => ""
        );
        if(is_string($property))
        {
            if(array_key_exists($property, $reset_properties)) $this -> $property = $reset_properties[$property];
        }
        else
        {
            foreach($reset_properties as $property => $default_val)
            {
                $this -> $property = $default_val;
            }
        }
        return $this;
    }

    public function title($title)
    {
        $this -> title = $title;
        return $this;
    }

    public function update($paramsOrFieldName, $filedValue = null)
    {
        $update_info = array();
        $params = $this -> param;
        $update_data = array();

        if(is_array($paramsOrFieldName))
        {
            $update_data = $paramsOrFieldName;
        }
        else if(is_string($paramsOrFieldName))
        {
            $update_data[$paramsOrFieldName] = $filedValue;
        }

        if(count($update_data) == 0) return 0;

        foreach($update_data as $field=>$val)
        {
            if(stripos($val, '::') === 0)    // "::NOW()"
            {
                $update_info[] =  "`$field` = " . substr($val, 2);
            }
            else
            {
                $true_param_name = "f___" . count($params);

                $update_info[] = "`$field` = :$true_param_name";
                $params[$true_param_name] = $val;
            }
        }

        $true_table_name = $this -> table_name;
        $true_where      = empty($this -> where) ? "" : "WHERE " . $this -> where;

        $update = implode(', ', $update_info);
        $sql = "UPDATE $true_table_name SET $update $true_where";
        return $this -> query($sql, $params, true);
    }

    public function insert($params)
    {
        $insert_fields = array();
        $insert_vals = array();
        if(!is_array($params)) return 0;

        $true_params = array();

        foreach($params as $field=>$val)
        {
            $insert_fields[] = "`$field`";

            if(stripos($val, '::') === 0)    // "::NOW()"
            {
                $insert_vals[] = substr($val, 2);
                // unset($params[$field]);
            }
            else
            {
                $true_param_name = "f___" . count($true_params);

                $true_params[$true_param_name] = $val;
                $insert_vals[] = ":$true_param_name";
            }
        }

        if(count($insert_fields) == 0) return 0;

        $true_table_name = $this -> table_name;

        $fields = implode(', ', $insert_fields);
        $vals = implode(', ', $insert_vals);

        $sql = "INSERT INTO $true_table_name ($fields) VALUES ($vals)";
        return $this -> query($sql, $true_params, true);
    }

    public function delete($where = '')
    {
        $true_table_name = $this -> table_name;

        if(!empty($where)) $this -> where = $where;
        $true_where   = empty($this -> where)    ? ""  : "WHERE "     . $this -> where;

        $sql = "DELETE FROM $true_table_name $true_where";
        return $this -> query($sql, $this -> param, true);
    }

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

    /**
     * 取查询结果
    */
    public function select($where = '')
    {
        if(!empty($where)) $this -> where = $where;
        $sql = $this -> create_select_sql();
        return $this -> query($sql, $this -> param);
    }

    /**
     * 取查询结果首条记录
    */
    public function find($where = '')
    {
        $this -> limit(1);
        $dataSet = $this -> select($where);
        if(!is_array($dataSet)) return false;
        if(count($dataSet) == 0) return null;

        return $dataSet[0];
    }

    /**
     * 取查询结果首条记录的指定字段值， 若记录不存在或查询失败返回默认值
    */
    public function get_field($field, $default = null)
    {
        $this -> fields("$field AS val");
        $row = $this -> find();
        if(!is_array($row)) return $default;

        return $row['val'];
    }

    /**
     * 统计符合指定条件的记录数
    */
    public function count($where = '')
    {
        if(!empty($where)) $this -> where($where);
        $this -> fields("COUNT(1) AS val");
        $this -> orderby("");
        $this -> groupby("");

        $row = $this -> find();
        if(!is_array($row) || count($row) == 0) return false;
        return intval($row['val']);
    }

    /**
     * 判断符合指定条件的记录是否存在
    */
    public function exists($where = '')
    {
        return $this -> count($where) > 0;
    }

    /**
     * 取指定字段总和
    */
    public function sum($field, $default = 0)
    {
        $this -> fields("SUM($field) AS val");
        $this -> orderby("");
        $this -> groupby("");

        $row = $this -> find();
        if(!is_array($row) || count($row) == 0) return $default;
        return floatval($row['val']);
    }

    /**
     * 取指定字段平均值
    */
    public function avg($field, $default = 0)
    {
        $this -> fields("AVG($field) AS val");
        $this -> orderby("");
        $this -> groupby("");

        $row = $this -> find();
        if(!is_array($row) || count($row) == 0) return $default;
        return floatval($row['val']);
    }

    /**
     * 取指定字段最大值
    */
    public function max($field, $default = null)
    {
        $this -> fields("MAX($field) AS val");
        $this -> orderby("");
        $this -> groupby("");

        $row = $this -> find();
        if(!is_array($row) || count($row) == 0) return $default;
        return $row['val'];
    }

    /**
     * 取指定字段最小值
    */
    public function min($field, $default = null)
    {
        $this -> fields("MIN($field) AS val");
        $this -> orderby("");
        $this -> groupby("");

        $row = $this -> find();
        if(!is_array($row) || count($row) == 0) return $default;
        return $row['val'];
    }

    /**
     * 判断表是否存在
     * 若未指定参数， 则判断当前实例表是否存在
    */
    public function table_exists($other_table_name = '')
    {
        $true_table_name = $other_table_name;
        if($true_table_name == '') $true_table_name = $this-> table_name;

        $sql = "SHOW TABLES LIKE '$true_table_name'";
        $dataSet = $this -> query($sql, null);
        if(!is_array($dataSet) || count($dataSet) == 0) return false;

        return true;
    }

    /**
     * 删除表
    */
    public function drop($other_table_name = '')
    {
        $true_table_name = $other_table_name;
        if($true_table_name == '') $true_table_name = $this -> table_name;

        $sql = "DROP TABLE '$true_table_name'";
        return $this -> query($sql, null, true);
    }

    /**
     * 查询匹配指定SQL通配符的表名列表
     * 若未指定参数， 使用当前表明作为通配符
    */
    public function get_tables_like($other_table_name_match = '')
    {
        $true_table_name_match = $other_table_name_match;
        if($true_table_name_match == '') $true_table_name_match = $this -> table_name;

        $table_names = array();

        $sql = "SHOW TABLES LIKE '$true_table_name_match'";


        $old_fetch_type = $this -> query_fetch_type;
        $this -> query_fetch_type = \PDO::FETCH_NUM;   // 临时修改 fetch_type
        $dataSet = $this -> query($sql, null);
        $this -> query_fetch_type = $old_fetch_type;

        if(is_array($dataSet))
        {
            foreach($dataSet as $row) $table_names[] = $row[0];
        }

        return $table_names;
    }

    /**
     * 执行查询并将查询结果填充到新表
    */
    public function select_to_table($to_table_name)
    {
        $this -> title .= " -> 查询目标表是否存在";
        $operation = $this -> table_exists($to_table_name) ? "INSERT" : "CREATE TABLE";
        $select_sql = $this -> create_select_sql();
        $sql = "$operation $to_table_name $select_sql";
        $this -> title .= " -> 执行";
        return $this -> query($sql, $this -> param, true);
    }

    /**
     * 拷贝表结构， 并创建新表
    */
    public function copy_struct_to($new_table_name, $rebuild_if_exists = false)
    {
        if(empty($new_table_name)) return false;

        $create_sql = "";

        $this -> title .= " -> 查询目标表是否存在";
        if($this -> table_exists($new_table_name))
        {
            if(!$rebuild_if_exists) return false;

            $this -> title .= " -> 删除目标表";
            $create_sql = "DROP TABLE IF EXISTS $new_table_name;";
        }

        $true_table_name = $this -> table_name;
        $sql = "show create table $true_table_name";
        $this -> title .= " -> 取得母表创建语句";

        $old_fetch_type = $this -> query_fetch_type;
        $this -> query_fetch_type = \PDO::FETCH_NUM;
        $dataSet = $this -> query($sql, null);
        $this -> query_fetch_type = $old_fetch_type;

        if(!is_array($dataSet) || count($dataSet) == 0) return false;
        $create_sql = $create_sql . $dataSet[0][1];

        $create_sql = str_ireplace("CREATE TABLE $true_table_name", "CREATE TABLE $new_table_name", $create_sql);
        $create_sql = preg_replace("/AUTO_INCREMENT=\d+/i", "AUTO_INCREMENT=1", $create_sql);
        $this -> title .= " -> 执行";
        return $this -> query($create_sql, null, true);
    }

    public function add_field($field_name, $after = '', $type = 'varchar(255)', $allow_null = true, $default = '')
    {
        if(empty($field_name)) return false;
        $true_table_name = $this -> table_name;

        $sql_null = $allow_null ? "NULL" : "NOT NULL";
        $sql_after =  empty($after) ? "" : "AFTER $after";
        $sql = "ALTER TABLE $true_table_name ADD COLUMN $field_name $type $sql_null DEFAULT '$default' $sql_after";
        return $this -> query($sql, null, true);
    }

    public function delete_field($field_name)
    {
        if(empty($field_name)) return false;
        $true_table_name = $this -> table_name;
        $sql = "ALTER TABLE $true_table_name DROP COLUMN $field_name;";
        return $this -> query($sql, null, true);
    }

    /**
     * 添加表分区
    */
    public function add_partition($partition_name, $max_value)
    {
        if(empty($partition_name)) return false;

        $true_table_name = $this -> table_name;
        $sql = "ALTER TABLE $true_table_name ADD PARTITION (PARTITION $partition_name VALUES LESS THAN ($max_value))";
        return $this -> query($sql, null, true);
    }
}
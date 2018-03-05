<?php
namespace classes;

class DBMysql
{
	//\PDO对象
	public $conn = null;

    private $fatch_type = \PDO::FETCH_ASSOC;

	public $con_count = 0;
	public $sql_count = 0;
	public $sql_info = array();
	public $cmd_info = array();

    private $last_error = '';

	function __construct($config = null)
	{
        if($config == null)
        {
            exit("请检查数据库连接设置");
        }

        $options = array(\PDO::ATTR_PERSISTENT => true);
        $init_command = "";
        if(array_key_exists("charset", $config)) $init_command .= "SET NAMES " . $config['charset'] . ";";
        if($init_command !== "") $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = $init_command;

		$this -> conn = new \PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'], $config['user'], $config['password'], $options);

		$this -> conn -> setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this -> conn -> setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		$this -> con_count ++;
	}

	/*
	 * 执行SQL操作
	 */
	public function query($sql, $para = null)
	{
		$sql_start_time = microtime(true);
		$sql = trim($sql);

		$arr = explode(' ', $sql);
		$sqlType = strtoupper($arr[0]);

		if (strpos($sql, 'INTO OUTFILE') !== false)
		{
			$sqlType = 'OUTFILE';
		}

		try
		{
			$cmd = $this->conn->prepare($sql);
			$cmd_start_time = microtime(true);
			if ($para == null)
			{
				$cmd->execute();
			}
			else
			{
				$cmd->execute($para);
			}

			$this->cmd_info[] = array('time' => microtime(true) - $cmd_start_time, 'sql' => $cmd->queryString);
		}
		catch (Exception $ex)
		{
            $this->last_error = $ex -> getMessage();
			return false;
		}

		$return = null;
		if($sqlType == "SELECT" || $sqlType == "SHOW")
		{
			$return = $cmd->fetchAll($this->fatch_type);
		}
		else if($sqlType == "INSERT")
		{
			$return = $this->conn->lastInsertId();
		}
		else
		{
			$return = $cmd->rowCount();
		}

		$this->sql_count ++;
		$this->sql_info[] = array('time' => microtime(TRUE) - $sql_start_time);
		return $return;
	}

    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * FETCH_LAZY  : 1
     * FETCH_ASSOC : 2
     * FETCH_NUM   : 3
     * FETCH_BOTH  : 4
     * FETCH_OBJ   : 5
     * FETCH_BOUND : 6
    */
    public function set_fatch_type($type)
    {
        if($type > 0) $this->fatch_type = $type;
    }
}
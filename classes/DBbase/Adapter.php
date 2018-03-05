<?php
namespace classes\DBbase;

/**
 * Class Adapter
 */
class Adapter
{
    /**
     * PDO对象
     *
     * @var null|PDO
     */
    protected $pdo = null;

    /**
     * @var int
     */
    protected $con_count = 0;

    /**
     * @var int
     */
    protected $exec_count = 0;

    /**
     * @var string
     */
    protected $last_error = '';

    /**
     * constructor.
     *
     * @param null|array $config
     */
    public function __construct($dsn, $user = '', $password = '')
	{
        $connect_options = [
            \PDO::ATTR_PERSISTENT => true,   // 长连接
        ];

		$this -> pdo = new \PDO($dsn, $user, $password, $connect_options);

		$this -> pdo -> setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);   // 错误模式， 抛出异常
		$this -> pdo -> setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);

		$this -> con_count ++;
	}

    /**
     * 执行SQL查询
     *
     * @param string  $sql
     * @param null|array $param
     *
     * @return array|bool|int|null|string
     */
    public function query($sql, $param = null)
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
            $this -> pdo -> beginTransaction();
			$cmd = $this -> pdo -> prepare($sql);
			if ($param == null)
			{
				$cmd -> execute();
			}
			else
			{
				$cmd -> execute($param);
			}
            $this -> pdo -> commit();
		}
		catch (\Exception $ex)
		{
            $this -> last_error = $ex -> getMessage();
            $this -> pdo -> rollBack();

			return false;
		}

		$return = null;
		if($sqlType == "SELECT" || $sqlType == "SHOW" || $sqlType == 'CHECK')
		{
			$return = $cmd -> fetchAll(\PDO::FETCH_ASSOC);
		}
		else if($sqlType == "INSERT")
		{
			$return = intval($this -> pdo -> lastInsertId());
		}
		else
		{
			$return = intval($cmd -> rowCount());
		}

		$this -> exec_count ++;
		return $return;
	}

    /**
     * 取最后错误信息
     *
     * @return string
     */
    public function get_last_error()
    {
        return $this -> last_error;
    }

    /**
     * FETCH_LAZY  : 1
     * FETCH_ASSOC : 2
     * FETCH_NUM   : 3
     * FETCH_BOTH  : 4
     * FETCH_OBJ   : 5
     * FETCH_BOUND : 6
     *
     * @param int  $type
    */
    public function set_fatch_type($type)
    {
        if($type > 0) $this -> fatch_type = $type;
    }
}
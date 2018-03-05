<?php
namespace MCU;

/**
 * Class ProduceData
 */
class ProduceData
{
    const EMPTY_DB_FILE_PATH = SYS_ROOT . 'Libs/produce_data.db';
    const DB_FILE_PATH = 'record/produce_data.db';

    private static $data_batch_index = 0;

    public static function save($data)
	{
        global $sysCache;

        \Channel\Client::publish('SYS.ProduceData.create', $data);

        $db_operator = static::getOperator();

        $product = Sys::getConfig('product', '');

        if(static::$data_batch_index == 0)
        {
            static::$data_batch_index = $db_operator -> where("product = '$product'") -> max('batch_index', 0) + 1;
            $sysCache->set('ProduceData:batch:index', static::$data_batch_index);
        }

        $db_data = [
            'product' => $product,
            'batch_index' => static::$data_batch_index,
            'timestamp' => time(),
            'data' => json_encode($data),
        ];
        $new_id = $db_operator -> insert($db_data);
        $sysCache -> set('ProduceData:last:time', date('Y-m-d H:i:s'));
        $sysCache -> set('ProduceData:last:index', $new_id);

        return $new_id;
	}

    public static function get($count = 50)
    {
        $db_operator = static::getOperator();
        $list = $db_operator -> reset() -> orderby('timestamp ASC') -> limit($count) -> select();
        if(count($list) == 0)
        {
            \Channel\Client::publish('SYS.ProduceData.empty', $id);
        }
        return $list;
    }

    public static function del($id)
    {
        $db_operator = static::getOperator();
        return $db_operator -> reset() -> delete("id = $id");
    }

    public static function delBatch($batch_index)
    {
        $db_operator = static::getOperator();
        return $db_operator -> reset() -> delete("batch_index = $batch_index");
    }

    public static function getBatchs()
    {
        $db_operator = static::getOperator();
        $batches = $db_operator -> reset() -> fields('batch_index') -> groupby('batch_index') -> select();

        $batches_list = [];
        foreach($batches as $batch)
        {
            $batch_index = $batch['batch_index'];
            $batches_list[$batch_index] = $db_operator
                -> reset()
                -> fields('MIN(timestamp) AS time_start, MAX(timestamp) AS time_end, SUM(LENGTH(data)) AS size')
                -> find("batch_index = $batch_index");

            $batches_list[$batch_index]['time_start'] = date('Y-m-d H:i:s', $batches_list[$batch_index]['time_start']);
            $batches_list[$batch_index]['time_end']   = date('Y-m-d H:i:s', $batches_list[$batch_index]['time_end']);
        }
        return $batches_list;
    }

    public static function getBatchData($batch_index)
    {
        $db_operator = static::getOperator();
        $list = $db_operator -> reset() -> select("batch_index = $batch_index");
        return $list;
    }

    public static function getOperator($str = 'produce_data')
    {
        $db_file_path = LocalFile::getFilePath(static::DB_FILE_PATH);
        if(!file_exists($db_file_path))
        {
            copy(static::EMPTY_DB_FILE_PATH, $db_file_path);
        }

        if(null === DbOperator::getConfig($str))
        {
            DbOperator::setConfig(['dsn' => "sqlite:$db_file_path"], $str);
        }

        return new DbOperator($str, $str);
    }
}
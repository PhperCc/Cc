<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Protocols;

use Workerman\Connection\TcpConnection;

/**
 * Frame Protocol.
 */
class Frame
{
    /**
     * Check the integrity of the package.
     *
     * @param string        $buffer
     * @param TcpConnection $connection
     * @return int
     */
    public static function input($buffer, TcpConnection $connection)
    {
        if (strlen($buffer) < 4) {
            return 0;
        }
        $unpack_data = unpack('Ntotal_length', $buffer);
        return $unpack_data['total_length'];
    }

    /**
     * Decode.
     *
     * @param string $buffer
     * @return string
     */
    public static function decode($buffer)
    {
        return substr($buffer, 4);
    }

    /**
     * Encode.
     *
     * @param string $buffer
     * @return string
     */
    public static function encode($buffer)
    {
        $total_length = 4 + strlen($buffer);
        return pack('N', $total_length) . $buffer;
    }
}

<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Events\React;

/**
 * Class StreamSelectLoop
 * @package Workerman\Events\React
 */
class StreamSelectLoop extends \React\EventLoop\StreamSelectLoop
{
    /**
     * Add signal handler.
     *
     * @param $signal
     * @param $callback
     * @return bool
     */
    public function addSignal($signal, $callback)
    {
        if(PHP_EOL !== "\r\n") {
            pcntl_signal($signal, $callback);
        }
    }

    /**
     * Remove signal handler.
     *
     * @param $signal
     */
    public function removeSignal($signal)
    {
        if(PHP_EOL !== "\r\n") {
            pcntl_signal($signal, SIG_IGN);
        }
    }

    /**
     * Emulate a stream_select() implementation that does not break when passed
     * empty stream arrays.
     *
     * @param array        &$read   An array of read streams to select upon.
     * @param array        &$write  An array of write streams to select upon.
     * @param integer|null $timeout Activity timeout in microseconds, or null to wait forever.
     *
     * @return integer|false The total number of streams that are ready for read/write.
     * Can return false if stream_select() is interrupted by a signal.
     */
    protected function streamSelect(array &$read, array &$write, $timeout)
    {
        if ($read || $write) {
            $except = null;
            // Calls signal handlers for pending signals
            pcntl_signal_dispatch();
            // suppress warnings that occur, when stream_select is interrupted by a signal
            return @stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
        }

        // Calls signal handlers for pending signals
        if(PHP_EOL !== "\r\n") {
            pcntl_signal_dispatch();
        }
        $timeout && usleep($timeout);

        return 0;
    }
}

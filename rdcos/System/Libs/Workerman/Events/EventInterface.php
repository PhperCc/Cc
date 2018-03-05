<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Events;

interface EventInterface
{
    /**
     * Read event.
     *
     * @var int
     */
    const EV_READ = 1;

    /**
     * Write event.
     *
     * @var int
     */
    const EV_WRITE = 2;

    /**
     * Signal event.
     *
     * @var int
     */
    const EV_SIGNAL = 4;

    /**
     * Timer event.
     *
     * @var int
     */
    const EV_TIMER = 8;

    /**
     * Timer once event.
     *
     * @var int
     */
    const EV_TIMER_ONCE = 16;

    /**
     * Add event listener to event loop.
     *
     * @param mixed    $fd
     * @param int      $flag
     * @param callable $func
     * @param mixed    $args
     * @return bool
     */
    public function add($fd, $flag, $func, $args = null);

    /**
     * Remove event listener from event loop.
     *
     * @param mixed $fd
     * @param int   $flag
     * @return bool
     */
    public function del($fd, $flag);

    /**
     * Remove all timers.
     *
     * @return void
     */
    public function clearAllTimer();

    /**
     * Main loop.
     *
     * @return void
     */
    public function loop();
}

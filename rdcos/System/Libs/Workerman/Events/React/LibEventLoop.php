<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Events\React;

/**
 * Class LibEventLoop
 * @package Workerman\Events\React
 */
class LibEventLoop extends \React\EventLoop\LibEventLoop
{
    /**
     * Event base.
     *
     * @var event_base resource
     */
    protected $_eventBase = null;

    /**
     * All signal Event instances.
     *
     * @var array
     */
    protected $_signalEvents = array();

    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct();
        $class = new \ReflectionClass('\React\EventLoop\LibEventLoop');
        $property = $class->getProperty('eventBase');
        $property->setAccessible(true);
        $this->_eventBase = $property->getValue($this);
    }

    /**
     * Add signal handler.
     *
     * @param $signal
     * @param $callback
     * @return bool
     */
    public function addSignal($signal, $callback)
    {
        $event = event_new();
        $this->_signalEvents[$signal] = $event;
        event_set($event, $signal, EV_SIGNAL | EV_PERSIST, $callback);
        event_base_set($event, $this->_eventBase);
        event_add($event);
    }

    /**
     * Remove signal handler.
     *
     * @param $signal
     */
    public function removeSignal($signal)
    {
        if (isset($this->_signalEvents[$signal])) {
            $event = $this->_signalEvents[$signal];
            event_del($event);
            unset($this->_signalEvents[$signal]);
        }
    }
}

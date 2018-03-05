<?php
/**
 * Copyright Xi'An ENH Technology Co.,Ltd(c) 2017. And all rights reserved.
 * 西安依恩驰网络技术有限公司(c) 版权所有 2017， 并保留所有权利。
 */
namespace Workerman\Events\React;

/**
 * Class ExtEventLoop
 * @package Workerman\Events\React
 */
class ExtEventLoop extends \React\EventLoop\ExtEventLoop
{
    /**
     * Event base.
     *
     * @var EventBase
     */
    protected $_eventBase = null;

    /**
     * All signal Event instances.
     *
     * @var array
     */
    protected $_signalEvents = array();

    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $class = new \ReflectionClass('\React\EventLoop\ExtEventLoop');
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
        $event = \Event::signal($this->_eventBase, $signal, $callback);
        if (!$event||!$event->add()) {
            return false;
        }
        $this->_signalEvents[$signal] = $event;
    }

    /**
     * Remove signal handler.
     *
     * @param $signal
     */
    public function removeSignal($signal)
    {
        if (isset($this->_signalEvents[$signal])) {
            $this->_signalEvents[$signal]->del();
            unset($this->_signalEvents[$signal]);
        }
    }
}

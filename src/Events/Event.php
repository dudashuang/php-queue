<?php
namespace Lily\Events;

use Lily\DispatchAble\IDispatchAble;

/**
 * Class Event
 *
 * @package Lily\Events
 */
class Event implements IDispatchAble {

    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $event_id;

    /**
     * @return string
     * @throws
     */
    public function prepare_data(): string {
        $this->queue = $this->get_short_name();
        $this->event_id = $this->event_id ?? hash('sha256', $this->get_short_name() . microtime(true) . mt_rand());

        return serialize($this);
    }

    /**
     * @return string
     */
    public function get_event_id() {

        return $this->event_id;
    }

    /**
     * @return string
     */
    public function get_queue() {

        return $this->queue;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function set_queue(string $queue) {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public function get_short_name() {
        return (new \ReflectionClass($this))->getShortName();
    }
}
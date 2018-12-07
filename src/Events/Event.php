<?php

namespace Lily\Events;

use Lily\DispatchAble\IDispatchAble;

/**
 * Class Event.
 */
class Event implements IDispatchAble
{
    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $event_id;

    /**
     * delayed seconds.
     *
     * @var int
     */
    private $delay = 0;

    /**
     * @throws
     *
     * @return string
     */
    public function prepare_data(): string
    {
        $this->get_event_id();

        return serialize($this);
    }

    /**
     * @return string
     */
    public function get_event_id()
    {
        $this->event_id = $this->event_id ?? hash('sha256', $this->get_short_name().microtime(true).mt_rand());

        return $this->event_id;
    }

    /**
     * @return string
     */
    public function get_queue()
    {
        $this->queue = $this->queue ?? $this->get_short_name();

        return $this->queue;
    }

    /**
     * @param string $queue
     *
     * @return $this
     */
    public function set_queue(string $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function delay(int $seconds)
    {
        $this->delay = $seconds;

        return $this;
    }

    /**
     * clear delayed time.
     */
    public function clear_delayed_time()
    {
        $this->delay = 0;
    }

    /**
     * get delayed time.
     *
     * @return int
     */
    public function get_delayed_time()
    {
        return $this->delay;
    }

    /**
     * @throws
     *
     * @return string
     */
    public function get_short_name()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}

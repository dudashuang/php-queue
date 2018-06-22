<?php
namespace Lily\Events;

use Lily\DispatchAble\IDispatchAble;

class Event implements IDispatchAble {
    protected $queue;

    protected $event_id;

    public function __construct(array $params = []) {
        $this->event_id = str_random(64);
        $this->queue    = $this->get_short_name();
        $keys           = array_keys(get_object_vars($this));

        foreach ($params as $key => $value) {
            if (in_array($key, $keys)) {
                $this->{$key} = $value;
            }
        }
    }


    public function prepare_data(): string {

        return json_encode([
            'event'  => static::class,
            'params' => get_object_vars($this),
        ]);
    }

    public function get_queue() {

        return $this->queue;
    }

    public function set_queue(string $queue) {
        $this->queue = $queue;

        return $this;
    }

    public function get_short_name() {
        return (new \ReflectionClass($this))->getShortName();
    }
}
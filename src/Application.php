<?php
namespace Lily;

use Lily\DispatchAble\IDispatchAble;
use Lily\Drivers\IDriver;

/**
 * Class Application
 *
 * @property string $dead_queue
 * @property string $failed_queue
 * @property string $default_queue
 * @property IDriver $driver
 * @method IDriver dispatch(IDispatchAble $message)
 * @method IDriver consume(string $queue)
 * @method IDriver listen(string $listener, array $events)
 *
 * @package Lily
 */
class Application {
    /**
     * @var IDriver
     */
    public $driver;

    /**
     * default queue name
     *
     * @var string
     */
    public $default_queue = 'default-queue';

    /**
     * default failed queue name
     *
     * @var string
     */
    public $failed_queue = 'failed-queue';

    /**
     * default dead queue name
     *
     * @var string
     */
    public $dead_queue = 'dead-queue';

    /**
     * Application constructor.
     *
     * @param IDriver $driver
     * @param array $options
     */
    public function __construct(IDriver $driver, array $options = []) {
        foreach ($options as $key => $value) {
            if (in_array($key, ['default_queue', 'failed_queue', 'dead_queue'])) {
                $this->{$key} = $value;
            }
        }

        $this->driver = $driver;
        $this->driver->set_app($this);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        return call_user_func_array([$this->driver, $name], $arguments);
    }

    /**
     * consume default queue.
     */
    public function consume_default() {
        $this->consume($this->default_queue);
    }

    /**
     * consume failed queue.
     */
    public function consume_failed() {
        $this->consume($this->failed_queue);
    }

}
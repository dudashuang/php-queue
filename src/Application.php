<?php
namespace Lily;

use Lily\Connectors\IConnector;
use Lily\Connectors\KafkaConnector;
use Lily\Connectors\RabbitMQConnector;
use Lily\Connectors\RedisConnector;
use Lily\DispatchAble\IDispatchAble;
use Lily\Drivers\IDriver;
use Lily\Drivers\Kafka;
use Lily\Drivers\RabbitMQ;
use Lily\Drivers\Redis;
use Lily\Exceptions\UnknownDriverException;
use Lily\Listeners\Listener;

/**
 * Class Application
 *
 * @property string $dead_queue
 * @property string $failed_queue
 * @property string $default_queue
 * @property IConnector $connector
 * @property IDriver $driver
 * @method IDriver dispatch(IDispatchAble $message)
 * @method IDriver consume(string $queue)
 * @method IDriver listen(Listener $listener, array $events)
 * @package Lily
 */
class Application {
    /**
     * @var IConnector
     */
    public $connector;

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
     * @param array $options
     * @throws UnknownDriverException
     */
    public function __construct(array $options) {
        if (!array_key_exists('driver', $options)) {
            throw new UnknownDriverException('the driver is required!');
        }

        $keys = get_object_vars($this);
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $keys)) {
                $this->{$key} = $value;
            }
        }

        switch ($options['driver']) {
            case 'rabbitmq':
                $this->connector = new RabbitMQConnector($options);
                $this->driver    = new RabbitMQ($this);
                break;
            case 'kafka':
                $this->connector = new KafkaConnector($options);
                $this->driver    = new Kafka($this);
                break;
            case 'redis':
                $this->connector = new RedisConnector($options);
                $this->driver    = new Redis($this);
                break;
            default:
                throw new UnknownDriverException('the driver should be one of rabbitmq, kafka and redis.');
                break;
        }

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
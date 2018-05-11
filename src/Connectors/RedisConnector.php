<?php
namespace Lily\Connectors;

use Predis\Client;

final class RedisConnector implements IConnector {

    protected static $redis;

    /**
     * @param array $options
     * @return \Predis\Client
     */
    public static function get_connection($options = []) {
        if (static::$redis === null) {
            static::$redis = new Client(array_merge([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ], (array) $options));
        }

        return static::$redis;
    }

    public function __construct() {
    }

    public function __clone() {
        // TODO: Implement __clone() method.
    }

    public function __wakeup() {
        // TODO: Implement __wakeup() method.
    }
}
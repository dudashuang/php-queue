<?php

namespace Lily\Connectors;

use Predis\Client;

class RedisConnector implements IConnector
{
    private $scheme = 'tcp';

    private $host = '127.0.0.1';

    private $port = 6379;

    private $read_write_timeout = 0;

    public function __construct(array $options = [])
    {
        $keys = get_object_vars($this);

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $keys)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return Client
     */
    public function get_connection()
    {
        return new Client([
            'scheme'             => $this->scheme,
            'host'               => $this->host,
            'port'               => $this->port,
            'read_write_timeout' => $this->read_write_timeout,
        ]);
    }
}

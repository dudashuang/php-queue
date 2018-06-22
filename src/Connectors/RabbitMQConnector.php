<?php
namespace Lily\Connectors;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConnector implements IConnector {
    private $host = 'localhost';

    private $port = 5672;

    private $username = 'guest';

    private $password = 'guest';

    public function __construct(array $options = []) {
        $keys = get_object_vars($this);

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $keys)) {
                $this->{$key} = $value;
            }
        }
    }

    public function get_connection() {
        return new AMQPStreamConnection($this->host, $this->port, $this->username, $this->password);
    }

}
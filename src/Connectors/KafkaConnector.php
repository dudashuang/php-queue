<?php
namespace Lily\Connectors;

class KafkaConnector implements IConnector {
    private $brokers = [
        ['host' => 'localhost', 'port' => 9092],
    ];

    public function __construct(array $options = []) {
        if (array_key_exists('brokers', $options) && is_array($options['brokers']) && !empty($options['brokers'])) {
            $this->brokers = $options['brokers'];
        }
    }

    public function get_connection() {
        $broker_list = '';

        foreach ($this->brokers as $broker) {
            $broker_list .= $broker['host'] . ':' . $broker['port'] . ',';
        }

        return $broker_list;
    }
}
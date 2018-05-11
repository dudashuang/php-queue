<?php
namespace Lily;

use Lily\Connectors\RedisConnector;
use Lily\Jobs\Job;
use Lily\Queue\RedisQueue;

trait DispatchAble {

    /**
     * push a job to redis.
     *
     * @param Job $job
     * @param string $queue_name
     * @param array $redis_connection
     */
    public function dispatch(Job $job, $queue_name = 'default', $redis_connection = []) {
        $redis = RedisConnector::get_connection($redis_connection);
        $queue = new RedisQueue($redis, $queue_name);
        $queue->push($job);
    }
}
<?php
namespace Lily\Queue;

use Lily\Jobs\Job;
use Predis\Client;

class RedisQueue {

    /**
     * redis client.
     * @var Client
     */
    protected $redis;

    /**
     * queue name
     *
     * @var string
     */
    protected $queue_name;

    public function __construct(Client $redis, $queue_name) {
        $this->redis = $redis;
        $this->queue_name = $queue_name;
    }

    /**
     * pop a job.
     *
     * @return string
     */
    public function pop() {
        return $this->redis->lpop($this->queue_name);
    }

    /**
     * push a job.
     *
     * @param Job $job
     */
    public function push(Job $job) {
        $data = $this->prepare_data($job);
        $this->redis->rpush($this->queue_name, $data);
    }

    /**
     * serialize data.
     *
     * @param Job $job
     * @return array
     */
    public function prepare_data(Job $job) {

        $job->set_queue($this->queue_name);

        $params = get_object_vars($job);

        $data = [
            'params' => $params,
            'job' => get_class($job),
        ];

        return json_encode($data);
    }
}
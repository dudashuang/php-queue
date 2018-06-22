<?php
namespace Lily\Jobs;

use Lily\DispatchAble\IDispatchAble;

abstract class Job implements IDispatchAble {
    protected $job_id;

    protected $is_deleted = false;

    protected $retry_num = 0;

    protected $queue;

    protected $delay;

    public function __construct(array $params = []) {
        $this->job_id = str_random(64);
        $keys         = array_keys(get_object_vars($this));

        foreach ($params as $key => $value) {
            if (in_array($key, $keys)) {
                $this->{$key} = $value;
            }
        }

    }

    abstract public function handle();

    public function prepare_data(): string {
        return json_encode([
            'job'    => static::class,
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

    public function delete() {
        $this->is_deleted = true;
    }

    public function make_as_failed() {
        $this->retry_num += 1;

    }

    public function check_can_retry() {
        return $this->retry_num < 3 && $this->is_deleted === false;
    }
}
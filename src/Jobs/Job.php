<?php
namespace Lily\Jobs;

use Lily\DispatchAble\IDispatchAble;

/**
 * Class Job
 *
 * @package Lily\Jobs
 */
abstract class Job implements IDispatchAble {

    /**
     * @var string
     */
    private $job_id;

    /**
     * @var bool
     */
    private $is_deleted = false;

    /**
     * @var int
     */
    private $try_num = 0;

    /**
     * @var string
     */
    private $queue;

    /**
     * the job will do something entrance.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * @return string
     */
    public function prepare_data(): string {
        $this->job_id = $this->job_id ?? hash('sha256', $this->get_short_name() . microtime(true) . mt_rand());

        return serialize($this);
    }

    /**
     * @return string
     */
    public function get_queue() {
        return $this->queue;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function set_queue(string $queue) {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return int
     */
    public function get_try_num() {
        return $this->try_num;
    }

    /**
     * @return string
     */
    public function get_job_id() {
        return $this->job_id;
    }

    /**
     * @return bool
     */
    public function check_is_deleted() {
        return $this->is_deleted;
    }

    /**
     * mark the job failed.
     */
    public function make_as_failed() {
        $this->try_num += 1;
    }

    /**
     * mark the job be deleted, should not retry.
     */
    public function delete() {
        $this->is_deleted = true;
    }

    /**
     * check the job could retry.
     *
     * @return bool
     */
    public function check_can_retry() {
        return $this->try_num < 3 && !$this->check_is_deleted();
    }

    /**
     * @return string
     * @throws
     */
    public function get_short_name() {
        return (new \ReflectionClass($this))->getShortName();
    }
}
<?php
namespace Lily\Jobs;

use Lily\DispatchAble;

abstract class Job {
    use DispatchAble;

    /**
     * failed times.
     *
     * @var int
     */
    public $failed_times = 0;

    /**
     * queue name
     *
     * @var string
     */
    public $queue;

    /**
     * has delayed.
     *
     * @var bool
     */
    public $delay = false;

    /**
     * delayed to.
     *
     * @var string
     * eg: "2018-05-11 00:00:00"
     */
    public $delayed_to;

    /**
     * is deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * job execute entrance.
     * all logic code should in this func.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * mark this job deleted.
     */
    public function delete() {
        $this->deleted = true;
    }

    /**
     * check job is deleted.
     *
     * @return bool
     */
    public function is_deleted() {
        return $this->deleted;
    }

    /**
     * mark this job execute failed.
     */
    public function mark_as_failed() {
        $this->failed = true;
        $this->failed_times += 1;

        if ($this->get_failed_times() <= 3 && !$this->is_deleted()) {

            $this->dispatch($this, $this->get_queue());
        }
    }

    /**
     * check job has failed.
     *
     * @return bool
     */
    public function has_failed() {
        return $this->failed;
    }

    /**
     * get job queue.
     *
     * @return string
     */
    public function get_queue() {
        return $this->queue;
    }

    /**
     * set job queue.
     *
     * @param $queue_name string
     */
    public function set_queue($queue_name) {
        $this->queue = $queue_name;
    }

    /**
     * get job failed times.
     *
     * @return int
     */
    public function get_failed_times() {
        return $this->failed_times;
    }

    /**
     * delay execute the job.
     *
     * @param $execute_at
     * @return $this job
     */
    public function execute_at($execute_at) {
        if ($execute_at > date('Y-m-d H:i:s')) {
            $this->delay      = true;
            $this->delayed_to = $execute_at;
        }

        return $this;
    }
}
<?php

namespace Lily\Jobs;

use Lily\DispatchAble\IDispatchAble;

/**
 * Class Job.
 */
abstract class Job implements IDispatchAble
{
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
     * delayed seconds.
     *
     * @var int
     */
    private $delay = 0;

    /**
     * the job will do something entrance.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * @return string
     */
    public function prepare_data(): string
    {
        $this->get_job_id();

        return serialize($this);
    }

    /**
     * @return string
     */
    public function get_queue()
    {
        return $this->queue;
    }

    /**
     * @param string $queue
     *
     * @return $this
     */
    public function set_queue(string $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return int
     */
    public function get_try_num()
    {
        return $this->try_num;
    }

    /**
     * @return string
     */
    public function get_job_id()
    {
        $this->job_id = $this->job_id ?? hash('sha256', $this->get_short_name().microtime(true).mt_rand());

        return $this->job_id;
    }

    /**
     * @return bool
     */
    public function check_is_deleted()
    {
        return $this->is_deleted;
    }

    /**
     * mark the job failed.
     */
    public function mark_as_failed()
    {
        $this->try_num += 1;
    }

    /**
     * mark the job be deleted, should not retry.
     */
    public function delete()
    {
        $this->is_deleted = true;
    }

    /**
     * check the job could retry.
     *
     * @return bool
     */
    public function check_can_retry()
    {
        return $this->try_num < 3 && !$this->check_is_deleted();
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function delay(int $seconds)
    {
        $this->delay = $seconds;

        return $this;
    }

    /**
     * clear delayed time.
     */
    public function clear_delayed_time()
    {
        $this->delay = 0;
    }

    /**
     * get delayed time.
     *
     * @return int
     */
    public function get_delayed_time()
    {
        return $this->delay;
    }

    /**
     * @throws
     *
     * @return string
     */
    public function get_short_name()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}

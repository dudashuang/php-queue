<?php
namespace GPK\Services\MessageQueue\Jobs;

use Lily\Jobs\Job;

class TestJob extends Job {
    public $a;
    public $b;
    public function handle() {
        echo 'this is a job:' . $this->job_id;
        echo 'a:' . $this->a . "\n";
        echo "\n";
        echo $this->retry_num . "\n";
        throw new \Exception('test failed');
    }
}
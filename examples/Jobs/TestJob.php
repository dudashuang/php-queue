<?php
namespace LilyTest\Jobs;

use Lily\Jobs\Job;
use LilyTest\TestModel;

class TestJob extends Job {
    public $a;
    public $b;

    public function __construct($a, TestModel $b) {
        $this->a = $a;

        $this->b = $b;
    }

    public function handle() {
        echo 'this is a job:' . $this->get_job_id();
        echo 'a:' . "\n";
        var_dump($this->a);
        echo "\n";
        echo 'b:' . "\n";
        var_dump($this->b->show());
        echo "\n";
        echo $this->get_try_num() . "\n";
        throw new \Exception('test failed');
    }
}
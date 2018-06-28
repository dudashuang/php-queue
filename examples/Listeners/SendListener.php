<?php
namespace LilyTest\Listeners;

use Lily\Listeners\Listener;

class SendListener extends Listener {

    public function handle() {
        echo $this->get_job_id() . "\n";
        echo "I will do send message. \n";
        echo "ready to send a message to user_id: {$this->get_event()->user_id} \n";
        echo "send failed \n";
        echo $this->get_try_num() . "\n";
        throw new \Exception('test failed');
    }
}
<?php
namespace GPK\Services\MessageQueue\Listeners;

use Lily\Listeners\Listener;

class SendListener extends Listener {
    public function handle() {
        echo "I will do send message. \n";
        echo "ready to send a message to user_id: {$this->event->user_id} \n";
        echo "send failed \n";
        echo $this->retry_num . "\n";
        throw new \Exception('test failed');
    }
}
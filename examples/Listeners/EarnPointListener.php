<?php
namespace LilyTest\Listeners;

use Lily\Listeners\Listener;

class EarnPointListener extends Listener {
    public function handle() {
        echo "I will do earn point. \n";
        echo "order_id: {$this->event->order_id} \n";
        echo "earn {$this->event->pay_amount} points. \n";
    }
}
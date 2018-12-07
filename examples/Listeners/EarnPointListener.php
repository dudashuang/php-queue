<?php

namespace LilyTest\Listeners;

use Lily\Listeners\Listener;

class EarnPointListener extends Listener
{
    public function handle()
    {
        echo 'job_id:'.$this->get_job_id()."\n";
        echo "I will do earn point. \n";
        echo "order_id: {$this->get_event()->order_id} \n";
        echo "earn {$this->get_event()->pay_amount} points. \n";
        echo $this->get_event()->model->show();
        echo "\n";
    }
}

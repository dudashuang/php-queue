<?php

namespace LilyTest\Events;

use Lily\Events\Event;

class PayFailedEvent extends Event
{
    public $order_id;

    public $pay_amount;

    public $user_id;

    public function __construct($order_id, $pay_amount, $user_id)
    {
        $this->order_id = $order_id;
        $this->pay_amount = $pay_amount;
        $this->user_id = $user_id;
    }
}

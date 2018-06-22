<?php
namespace LilyTest\Events;

use Lily\Events\Event;

class PayFailedEvent extends Event {
    public $order_id;

    public $pay_amount;

    public $user_id;
}
<?php
namespace GPK\Services\MessageQueue\Events;

use Lily\Events\Event;

class PayFailedEvent extends Event {
    public $order_id;

    public $pay_amount;

    public $user_id;
}
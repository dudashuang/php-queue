<?php
namespace GPK\Services\MessageQueue\Events;

use Lily\Events\Event;

class PaySuccessEvent extends Event {
    public $order_id;

    public $pay_amount;

    public $user_id;
}
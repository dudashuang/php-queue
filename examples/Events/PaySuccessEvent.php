<?php
namespace LilyTest\Events;

use Lily\Events\Event;

class PaySuccessEvent extends Event {
    public $order_id;

    public $pay_amount;

    public $user_id;
}
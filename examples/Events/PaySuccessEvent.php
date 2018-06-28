<?php
namespace LilyTest\Events;

use Lily\Events\Event;
use LilyTest\TestModel;

class PaySuccessEvent extends Event {
    public $order_id;

    public $pay_amount;

    public $user_id;

    public $model;

    public function __construct($order_id, $pay_amount, $user_id, TestModel $model) {
        $this->order_id = $order_id;
        $this->pay_amount = $pay_amount;
        $this->user_id = $user_id;
        $this->model = $model;
    }
}
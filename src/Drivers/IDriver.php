<?php
namespace Lily\Drivers;

use Lily\DispatchAble\IDispatchAble;
use Lily\Listeners\Listener;

interface IDriver {
    public function dispatch(IDispatchAble $message);

    public function consume(string $queue);

    public function listen(Listener $listener, array $events);
}
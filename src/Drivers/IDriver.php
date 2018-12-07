<?php

namespace Lily\Drivers;

use Lily\Application;
use Lily\DispatchAble\IDispatchAble;

interface IDriver
{
    public function dispatch(IDispatchAble $message);

    public function consume(string $queue);

    public function listen(string $listener, array $events);

    public function set_app(Application $app);
}

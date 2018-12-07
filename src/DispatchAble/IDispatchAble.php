<?php

namespace Lily\DispatchAble;

interface IDispatchAble
{
    public function prepare_data(): string;

    public function get_queue();

    public function set_queue(string $queue);

    public function delay(int $seconds);

    public function clear_delayed_time();

    public function get_delayed_time();
}

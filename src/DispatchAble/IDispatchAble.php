<?php
namespace Lily\DispatchAble;

interface IDispatchAble {
    public function prepare_data(): string;

    public function get_queue();

    public function set_queue(string $queue);
}
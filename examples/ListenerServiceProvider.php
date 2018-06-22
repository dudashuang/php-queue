<?php
namespace GPK\Services\MessageQueue;

class ListenerServiceProvider {
    private static $listeners = [
        'EarnPointListener' => [
            'PaySuccessEvent',
        ],
        'SendListener' => [
            'PaySuccessEvent',
            'PayFailedEvent',
        ],
    ];

    /**
     * @param $listener
     * @return mixed
     * @throws \Exception
     */
    public static function get_listener($listener) {
        if (!array_key_exists($listener, static::$listeners)) {
            throw new \Exception('the listener was not found!');
        }

        return static::$listeners[$listener];
    }
}
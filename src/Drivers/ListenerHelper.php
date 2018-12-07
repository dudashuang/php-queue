<?php

namespace Lily\Drivers;

use Lily\Exceptions\ListenerCanNotInstantiableException;
use Lily\Listeners\Listener;

trait ListenerHelper
{
    /**
     * @param string $listener
     * @param array  $params
     *
     * @throws ListenerCanNotInstantiableException
     * @throws \ReflectionException
     *
     * @return object
     */
    public function get_new_instance_by_listener(string $listener, array $params)
    {
        $refl = new \ReflectionClass($listener);

        if (!$refl->isSubclassOf(Listener::class)) {
            throw new ListenerCanNotInstantiableException(sprintf('the listener must be %s subclass', Listener::class));
        }

        if (!$refl->isInstantiable()) {
            throw new ListenerCanNotInstantiableException('the listener was not instantiable.');
        }

        return $refl->newInstanceArgs($params);
    }

    /**
     * @param string $listener
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    public function get_short_name(string $listener)
    {
        return (new \ReflectionClass($listener))->getShortName();
    }
}

<?php
namespace Lily\Listeners;

use Lily\Events\Event;
use Lily\Jobs\Job;

/**
 * Class Listener
 *
 * @package Lily\Listeners
 */
class Listener extends Job {

    /**
     * @var Event
     */
    private $event;

    /**
     * Listener constructor.
     *
     * @param Event $event
     */
    final public function __construct(Event $event) {
        $this->event = $event;
    }

    /**
     * @return mixed|void
     */
    public function handle() {
        // TODO: Implement handle() method.
    }

    public function get_event() {
        return $this->event;
    }
}
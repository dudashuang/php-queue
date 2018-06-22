<?php
namespace Lily\Listeners;

use Lily\Events\Event;
use Lily\Jobs\Job;

class Listener extends Job {
    /**
     * @var Event
     */
    protected $event;

    public function __construct(array $params = []) {
        parent::__construct($params);

        if (!empty($this->event)) {
            $event       = (array)json_decode($this->event);
            $this->event = new $event['event']((array)$event['params']);
        }
    }

    public function handle() {
        // TODO: Implement handle() method.
    }

    public function prepare_data(): string {
        $params = get_object_vars($this);

        if ($this->event instanceof Event) {
            $params['event'] = $this->event->prepare_data();
        }

        return json_encode([
            'job'    => static::class,
            'params' => $params,
        ]);
    }

    public function get_short_name() {
        return (new \ReflectionClass($this))->getShortName();
    }
}
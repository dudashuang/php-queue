<?php
namespace Lily\Drivers;

use Lily\Application;
use Lily\DispatchAble\IDispatchAble;
use Lily\Events\Event;
use Lily\Exceptions\UnknownDispatchException;
use Lily\Jobs\Job;
use Lily\Listeners\Listener;

class Redis implements IDriver {

    /**
     * @var Application
     */
    public $app;

    /**
     * Redis constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * dispatch a job or event.
     *
     * @param IDispatchAble $message
     * @throws UnknownDispatchException
     */
    public function dispatch(IDispatchAble $message) {
        if ($message instanceof Job) {
            $this->_dispatch_job($message);
        } elseif ($message instanceof Event) {
            $this->_dispatch_event($message);
        } else {
            throw new UnknownDispatchException('only job or event can dispatch.');
        }
    }

    /**
     * create a consumer to listen a queue.
     * consume jobs.
     *
     * @param string $queue
     * @throws UnknownDispatchException
     */
    public function consume(string $queue) {
        $connection = $this->app->connector->get_connection();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        while (true) {
            $item = $connection->lpop($queue);

            if (!$item) {
                continue;
            }

            $data = json_decode($item);
            $job  = new $data->job((array)$data->params);

            try {
                $job->handle();

            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
                $job->make_as_failed();
                $this->dispatch($job->set_queue($job->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
            }
        }
    }

    /**
     * create a consumer to listen events.
     * consume listener.
     *
     * @param Listener $listener
     * @param array $events
     * @throws UnknownDispatchException
     */
    public function listen(Listener $listener, array $events) {
        $connection = $this->app->connector->get_connection();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $pubsub = $connection->pubSubLoop();

        $pubsub->subscribe($events);

        foreach ($pubsub as $message) {
            switch ($message->kind) {
                case 'subscribe':
                    echo "Subscribed to {$message->channel}\n";
                    break;
                case 'message':
                    $listener_name = get_class($listener);
                    $listener      = new $listener_name(['event' => $message->payload]);

                    try {
                        $listener->handle();
                    } catch (\Exception $e) {
                        echo $e->getMessage() . "\n";
                        $listener->make_as_failed();
                        $this->dispatch($listener->set_queue($listener->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                    }
                    break;
            }
        }
    }

    /**
     * dispatch a job.
     *
     * @param Job $job
     */
    private function _dispatch_job(Job $job) {
        $connection = $this->app->connector->get_connection();

        if ($job->get_queue()) {
            $queue = $job->get_queue();
        } else {
            $queue = $this->app->default_queue;
            $job->set_queue($queue);
        }

        $connection->rpush($queue, $job->prepare_data());
    }

    /**
     * dispatch a event.
     *
     * @param Event $event
     */
    private function _dispatch_event(Event $event) {
        $connection = $this->app->connector->get_connection();

        $connection->publish($event->get_queue(), $event->prepare_data());
    }
}
<?php

namespace Lily\Drivers;

use Lily\Application;
use Lily\Connectors\RedisConnector;
use Lily\DispatchAble\IDispatchAble;
use Lily\Events\Event;
use Lily\Exceptions\UnknownDispatchException;
use Lily\Jobs\Job;

class Redis implements IDriver
{
    use ListenerHelper;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var RedisConnector
     */
    private $connector;

    /**
     * Redis constructor.
     *
     * @param RedisConnector $connector
     */
    public function __construct(RedisConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @param Application $app
     */
    public function set_app(Application $app)
    {
        $this->app = $app;
    }

    /**
     * dispatch a job or event.
     *
     * @param IDispatchAble $message
     *
     * @throws UnknownDispatchException
     */
    public function dispatch(IDispatchAble $message)
    {
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
     *
     * @throws UnknownDispatchException
     */
    public function consume(string $queue)
    {
        $connection = $this->connector->get_connection();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        while (true) {
            $item = $connection->lpop($queue);

            if (!$item) {
                continue;
            }

            $job = unserialize($item);

            try {
                $job->handle();
            } catch (\Exception $e) {
                $job->mark_as_failed();
                $this->dispatch($job->set_queue($job->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                echo date('Y-m-d H:i:s').' job_id:'.$job->get_job_id().' error:'.$e->getMessage().' at:'.$e->getFile().':'.$e->getLine()."\n";
            }
        }
    }

    /**
     * create a consumer to listen events.
     * consume listener.
     *
     * @param string $listener_name
     * @param array  $events
     *
     * @throws
     */
    public function listen(string $listener_name, array $events)
    {
        $connection = $this->connector->get_connection();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $pubsub = $connection->pubSubLoop();

        $pubsub->subscribe($events);

        foreach ($pubsub as $message) {
            switch ($message->kind) {
                case 'subscribe':
                    echo "Subscribed to {$message->channel}\n";
                    break;
                case 'message':

                    $listener = $this->get_new_instance_by_listener($listener_name, [unserialize($message->payload)]);

                    try {
                        $listener->handle();
                    } catch (\Exception $e) {
                        $listener->mark_as_failed();
                        $this->dispatch($listener->set_queue($listener->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                        echo date('Y-m-d H:i:s').' job_id:'.$listener->get_job_id().' error:'.$e->getMessage().' at:'.$e->getFile().':'.$e->getLine()."\n";
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
    private function _dispatch_job(Job $job)
    {
        $connection = $this->connector->get_connection();

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
    private function _dispatch_event(Event $event)
    {
        $connection = $this->connector->get_connection();

        $data = $event->prepare_data();

        $connection->publish($event->get_queue(), $data);
    }
}

<?php
namespace Lily\Drivers;

use Lily\Application;
use Lily\DispatchAble\IDispatchAble;
use Lily\Events\Event;
use Lily\Exceptions\UnknownDispatchException;
use Lily\Jobs\Job;
use Lily\Listeners\Listener;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ implements IDriver {

    /**
     * @var Application
     */
    public $app;

    /**
     * RabbitMQ constructor.
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
     */
    public function consume(string $queue) {
        $connection = $this->app->connector->get_connection();
        $channel = $connection->channel();
        $channel->queue_declare($queue, false, true, false, false);

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function ($msg) {
            $data = json_decode($msg->body);
            $job  = new $data->job((array)$data->params);

            try {
                $job->handle();
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
                $job->make_as_failed();
                $this->dispatch($job->set_queue($job->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));

                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
            }

        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

    }

    /**
     * create a consumer to listen events.
     * consume listener.
     *
     * @param Listener $listener
     * @param array $events
     */
    public function listen(Listener $listener, array $events) {
        $connection = $this->app->connector->get_connection();
        $channel = $connection->channel();
        $queue_name = $listener->get_short_name();
        $channel->queue_declare($queue_name, false, true, false, false);

        foreach ($events as $event) {
            $channel->exchange_declare($event, 'fanout', false, true, false);
            $channel->queue_bind($queue_name, $event);
        }

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function ($msg) use ($listener) {
            $listener_name = get_class($listener);
            $listener      = new $listener_name(['event' => $msg->body]);

            try {
                $listener->handle();
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
                $listener->make_as_failed();
                $this->dispatch($listener->set_queue($listener->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));

                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
            }
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue_name, '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    /**
     * dispatch a event
     *
     * @param Event $event
     */
    private function _dispatch_event(Event $event) {
        $connection = $this->app->connector->get_connection();
        $channel = $connection->channel();

        $channel->exchange_declare($event->get_queue(), 'fanout', false, true, false);

        $msg = new AMQPMessage(
            $event->prepare_data(),
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $channel->basic_publish($msg, $event->get_queue());

        $channel->close();
        $connection->close();
    }

    /**
     * dispatch a job.
     *
     * @param Job $job
     */
    private function _dispatch_job(Job $job) {
        $connection = $this->app->connector->get_connection();
        $channel = $connection->channel();

        if ($job->get_queue()) {
            $queue_name = $job->get_queue();
        } else {
            $queue_name = $this->app->default_queue;
            $job->set_queue($queue_name);
        }

        $channel->queue_declare($queue_name, false, true, false, false);

        $msg = new AMQPMessage(
            $job->prepare_data(),
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $channel->basic_publish($msg, '', $queue_name);

        $channel->close();
        $connection->close();
    }
}
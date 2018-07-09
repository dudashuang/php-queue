<?php
namespace Lily\Drivers;

use Lily\Application;
use Lily\Connectors\RabbitMQConnector;
use Lily\DispatchAble\IDispatchAble;
use Lily\Events\Event;
use Lily\Exceptions\UnknownDispatchException;
use Lily\Jobs\Job;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQ implements IDriver {
    use ListenerHelper;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var RabbitMQConnector
     */
    private $connector;

    /**
     * RabbitMQ constructor.
     *
     * @param RabbitMQConnector $connector
     */
    public function __construct(RabbitMQConnector $connector) {
        $this->connector = $connector;
    }

    /**
     * @param Application $app
     */
    public function set_app(Application $app) {
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
        $connection = $this->connector->get_connection();
        $channel = $connection->channel();
        $channel->queue_declare($queue, false, true, false, false);

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function ($msg) {

            $job = unserialize($msg->body);

            try {
                $job->clear_delayed_time();
                $job->handle();
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $job->mark_as_failed();
                $this->dispatch($job->set_queue($job->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                echo date('Y-m-d H:i:s') . ' job_id:' . $job->get_job_id() . ' error:'. $e->getMessage() . ' at:' . $e->getFile() . ':' . $e->getLine(). "\n";

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
     * @param string $listener_name
     * @param array $events
     * @throws
     */
    public function listen(string $listener_name, array $events) {
        $connection = $this->connector->get_connection();
        $channel = $connection->channel();
        $queue_name = $this->get_short_name($listener_name);
        $channel->queue_declare($queue_name, false, true, false, false);

        foreach ($events as $event) {
            $channel->exchange_declare($event, 'fanout', false, true, false);
            $channel->queue_bind($queue_name, $event);
        }

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        $callback = function ($msg) use ($listener_name) {
            $listener = $this->get_new_instance_by_listener($listener_name, [unserialize($msg->body)]);

            try {
                $listener->clear_delayed_time();
                $listener->handle();
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $listener->mark_as_failed();
                $this->dispatch($listener->set_queue($listener->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                echo date('Y-m-d H:i:s') . ' job_id:' . $listener->get_job_id() . ' error:'. $e->getMessage() . ' at:' . $e->getFile() . ':' . $e->getLine(). "\n";

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
     * @throws
     */
    private function _dispatch_event(Event $event) {
        $connection = $this->connector->get_connection();
        $channel = $connection->channel();

        if ($event->get_delayed_time() > 0) {
            $table = new AMQPTable();
            $table->set('x-dead-letter-exchange', $event->get_queue());

            $channel->queue_declare($event->get_queue() . '-delay',false,true,false,false,false, $table);

            $msg = new AMQPMessage(
                $event->prepare_data(),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'expiration' => $event->get_delayed_time() * 1000,
                ]
            );

            $channel->basic_publish($msg, '', $event->get_queue() . '-delay');

        } else {

            $msg = new AMQPMessage(
                $event->prepare_data(),
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );

            $channel->exchange_declare($event->get_queue(), 'fanout', false, true, false);

            $channel->basic_publish($msg, $event->get_queue());
        }

        $channel->close();
        $connection->close();
    }

    /**
     * dispatch a job.
     *
     * @param Job $job
     */
    private function _dispatch_job(Job $job) {
        $connection = $this->connector->get_connection();
        $channel = $connection->channel();

        if ($job->get_queue()) {
            $queue_name = $job->get_queue();
        } else {
            $queue_name = $this->app->default_queue;
            $job->set_queue($queue_name);
        }

        if ($job->get_delayed_time() > 0) {
            $table = new AMQPTable();
            $table->set('x-dead-letter-exchange', '');
            $table->set('x-dead-letter-routing-key', $queue_name);

            // queue live to time.
            // $table->set('x-message-ttl',$job->get_delayed_time() * 1000);

            $channel->queue_declare($queue_name . '-delay',false,true,false,false,false, $table);

            $msg = new AMQPMessage(
                $job->prepare_data(),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'expiration' => $job->get_delayed_time() * 1000,
                ]
            );

            $channel->basic_publish($msg, '', $queue_name . '-delay');

        } else {

            $channel->queue_declare($queue_name, false, true, false, false);

            $msg = new AMQPMessage(
                $job->prepare_data(),
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );

            $channel->basic_publish($msg, '', $queue_name);
        }

        $channel->close();
        $connection->close();
    }
}
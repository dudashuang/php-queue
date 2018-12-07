<?php

namespace Lily\Drivers;

use Lily\Application;
use Lily\Connectors\KafkaConnector;
use Lily\DispatchAble\IDispatchAble;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\TopicConf;

class Kafka implements IDriver
{
    use ListenerHelper;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var KafkaConnector
     */
    private $connector;

    /**
     * Kafka constructor.
     *
     * @param KafkaConnector $connector
     */
    public function __construct(KafkaConnector $connector)
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
     */
    public function dispatch(IDispatchAble $message)
    {
        $producer = new Producer();
        $producer->setLogLevel(LOG_DEBUG);

        // bind brokers
        $producer->addBrokers($this->connector->get_connection());

        $data = $message->prepare_data();

        // bind topic
        if ($message->get_queue()) {
            $topic = $producer->newTopic($message->get_queue());
        } else {
            $message->set_queue($this->app->default_queue);
            $topic = $producer->newTopic($this->app->default_queue);
        }

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $data);
        $producer->poll(0);

        while ($producer->getOutQLen() > 0) {
            $producer->poll(50);
        }
    }

    /**
     * create a consumer to listen a queue.
     * consume jobs.
     *
     * @param string $queue
     *
     * @throws \Throwable
     */
    public function consume(string $queue)
    {
        $conf = $this->_get_kafka_conf();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', $queue);

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $this->connector->get_connection());

        $consumer = new KafkaConsumer($conf);

        // Subscribe to default queue topic
        $consumer->subscribe([$queue]);

        while (true) {
            $message = $consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:

                    $job = unserialize($message->payload);

                    try {
                        $job->handle();
                    } catch (\Exception $e) {
                        $job->mark_as_failed();
                        $this->dispatch($job->set_queue($job->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                        echo date('Y-m-d H:i:s').' job_id:'.$job->get_job_id().' error:'.$e->getMessage().' at:'.$e->getFile().':'.$e->getLine()."\n";
                    }

                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
//                    echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
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
     * @throws \Throwable
     */
    public function listen(string $listener_name, array $events)
    {
        $conf = $this->_get_kafka_conf();

        echo " [*] Waiting for messages. To exit press CTRL+C\n";

        // Configure the group.id. All consumer with the same group.id will consume different partitions.
        $conf->set('group.id', $this->get_short_name($listener_name));

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $this->connector->get_connection());

        $consumer = new KafkaConsumer($conf);

        // Subscribe to default queue topic
        $consumer->subscribe($events);

        while (true) {
            $message = $consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:

                    $listener = $this->get_new_instance_by_listener($listener_name, [unserialize($message->payload)]);

                    try {
                        $listener->handle();
                    } catch (\Exception $e) {
                        $listener->mark_as_failed();
                        $this->dispatch($listener->set_queue($listener->check_can_retry() ? $this->app->failed_queue : $this->app->dead_queue));
                        echo date('Y-m-d H:i:s').' job_id:'.$listener->get_job_id().' error:'.$e->getMessage().' at:'.$e->getFile().':'.$e->getLine()."\n";
                    }

                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    /**
     * get kafka config.
     *
     * @return Conf
     */
    private function _get_kafka_conf(): Conf
    {
        $conf = new Conf();

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo 'Assign: ';
                    var_dump($partitions);
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo 'Revoke: ';
                    var_dump($partitions);
                    $kafka->assign(null);
                    break;

                default:
                    throw new \Exception($err);
            }
        });

        $topicConf = new TopicConf();

        /*
         * Set where to start consuming messages when there is no initial offset in
         * offset store or the desired offset is out of range.
         * 'smallest': start from the beginning
         */
        $topicConf->set('auto.offset.reset', 'smallest');

        // Set the configuration to use for subscribed/assigned topics
        $conf->setDefaultTopicConf($topicConf);

        return $conf;
    }
}

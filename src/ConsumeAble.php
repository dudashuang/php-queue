<?php
namespace Lily;

use Lily\Connectors\RedisConnector;
use Lily\Queue\RedisQueue;

trait ConsumeAble {

    /**
     * listen a queue.
     *
     * @param string $queue_name
     * @param array $redis_connection
     * @throws RedisQueueException
     */
    public function consume($queue_name = 'default', $redis_connection = []) {
        $redis = RedisConnector::get_connection($redis_connection);
        $queue = new RedisQueue($redis, $queue_name);

        while (true) {
            $item = $queue->pop();

            if (!$item) {
                continue;
            }

            $data = json_decode($item);
            $job  = new $data->job((array)$data->params);

            if ($job->delay && $job->delayed_to > date('Y-m-d H:i:s')) {
                $job->dispatch($job, $job->get_queue());
                continue;
            }

            try {

                $job->handle();

            } catch (\Exception $e) {
                $job->mark_as_failed();

                $msg = 'Error: ';
                $msg .= date('Y-m-d H:i:s');
                $msg .= ' job: ';
                $msg .= $data->job;
                $msg .= " message: ";
                $msg .= $e->getMessage();
                $msg .= ' line: ';
                $msg .= $e->getFile() . ': ' . $e->getLine();
                throw new RedisQueueException($msg);
            }
        }
    }
}
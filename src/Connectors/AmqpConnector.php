<?php

namespace Forumhouse\LaravelAmqp\Connectors;

use Forumhouse\LaravelAmqp\Queue\AMQPQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use PhpAmqpLib\Connection\AMQPConnection;

class AmqpConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return \Illuminate\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        $connection = new AMQPConnection($config);

        if (!isset($config['exchange_type'])) {
            $config['exchange_type'] = AMQPQueue::EXCHANGE_TYPE_DIRECT;
        }

        if (!isset($config['exchange_flags'])) {
            $config['exchange_flags'] = ['durable' => true];
        }

        return new AMQPQueue(
            $connection,
            $config['queue'],
            $config['exchange_name'],
            $config['exchange_type'],
            $config['exchange_flags']
        );
    }
}

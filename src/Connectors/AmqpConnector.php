<?php

namespace Forumhouse\LaravelAmqp\Connectors;

use Forumhouse\LaravelAmqp\Queue\AMQPQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use PhpAmqpLib\Connection\AMQPConnection;

/**
 * Class AmqpConnector
 *
 * @package Forumhouse\LaravelAmqp
 */
class AmqpConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return \Illuminate\Queue\Queue
     */
    public function connect(array $config)
    {
        $connection = new AMQPConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            isset($config['vhost']) ? $config['vhost'] : '/'
        );

        if (!isset($config['exchange_type'])) {
            $config['exchange_type'] = AMQPQueue::EXCHANGE_TYPE_DIRECT;
        }

        if (!isset($config['exchange_flags'])) {
            $config['exchange_flags'] = ['durable' => true];
        }

        return new AMQPQueue(
            $connection,
            $config['queue'],
            $config['queue_flags'],
            $config['message_properties'],
            $config['channel_id'],
            $config['exchange_name'],
            $config['exchange_type'],
            $config['exchange_flags']
        );
    }
}

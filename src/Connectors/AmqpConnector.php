<?php

namespace Forumhouse\LaravelAmqp\Connectors;

use Forumhouse\LaravelAmqp\Queue\AMQPQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

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
        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            isset($config['vhost']) ? $config['vhost'] : '/',
            false,
            'AMQPLAIN',
            null,
            'en_US',
            isset($config['connection_timeout']) ? $config['connection_timeout'] : 3,
            isset($config['read_write_timeout']) ? $config['read_write_timeout'] : 3,
            null,
            isset($config['keepalive']) ? $config['keepalive'] : false,
            isset($config['heartbeat']) ? $config['heartbeat'] : 0
        );

        if (!isset($config['exchange_type'])) {
            $config['exchange_type'] = AMQPQueue::EXCHANGE_TYPE_DIRECT;
        }

        if (!isset($config['exchange_flags'])) {
            $config['exchange_flags'] = ['durable' => true];
        }

        return new AMQPQueue(
            $connection, $config['queue'], $config['queue_flags'], isset($config['declare_queues']) ? $config['declare_queues'] : true,
            $config['message_properties'], $config['channel_id'],
            $config['exchange_name'], $config['exchange_type'], $config['exchange_flags'], (isset($config['retry_after']) ? $config['retry_after'] : 0)
        );
    }
}

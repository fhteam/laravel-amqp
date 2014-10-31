<?php

namespace Forumhouse\LaravelAmqp\Queue;

use Forumhouse\LaravelAmqp\Exception\AMQPException;
use Forumhouse\LaravelAmqp\Jobs\AMQPJob;
use Illuminate\Queue\Queue;
use Illuminate\Queue\QueueInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPQueue extends Queue implements QueueInterface
{
    const EXCHANGE_TYPE_DIRECT = 'direct';

    const EXCHANGE_TYPE_TOPIC = 'topic';

    const EXCHANGE_TYPE_HEADERS = 'headers';

    const EXCHANGE_TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $defaultQueueName;

    /**
     * @var string
     */
    protected $exchangeName;

    /**
     * @var string
     */
    private $defaultChannelId;

    /**
     * @param AMQPConnection $connection
     * @param string         $defaultQueueName
     * @param                $defaultChannelId
     * @param string         $exchangeName
     * @param mixed          $exchangeType
     * @param mixed          $exchangeFlags
     */
    public function __construct(
        AMQPConnection $connection,
        $defaultQueueName = null,
        $defaultChannelId = null,
        $exchangeName = '',
        $exchangeType = null,
        $exchangeFlags = null
    ) {
        $this->connection = $connection;
        $this->defaultQueueName = $defaultQueueName;
        $this->defaultChannelId = $defaultChannelId;

        $this->exchangeName = $exchangeName;
        $this->channel = $connection->channel($this->defaultChannelId);

        if ($exchangeName !== null) {
            $this->declareExchange($exchangeName, $exchangeType, $exchangeFlags);
        }
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed  $data
     * @param  string $queue
     *
     * @throws AMQPException
     * @return bool
     */
    public function push($job, $data = '', $queue = null)
    {
        $queue = $this->getQueueName($queue);
        $this->declareQueue($queue);
        $payload = new AMQPMessage($this->createPayload($job, $data));
        $this->channel->basic_publish($payload, $this->exchangeName, $queue);
        return true;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array  $options
     *
     * @return bool
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $queue = $this->getQueueName($queue);
        $this->declareQueue($queue);
        $payload = new AMQPMessage($payload);
        $this->channel->basic_publish($payload, $this->exchangeName, $queue);
        return true;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string        $job
     * @param  mixed         $data
     * @param  string        $queue
     *
     * @return bool
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $queue = $this->getQueueName($queue);
        $this->declareQueue($queue);
        $payload = new AMQPMessage($this->createPayload($job, $data));
        $this->declareDelayedQueue($queue, $delay);
        $this->channel->basic_publish($payload, $this->exchangeName, $queue);
        return true;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return \Illuminate\Queue\Jobs\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueueName($queue);
        $this->declareQueue($queue);
        $envelope = $this->channel->basic_get($queue);

        if ($envelope instanceof AMQPMessage) {
            return new AMQPJob($this->container, $queue, $envelope);
        }

        return null;
    }

    /**
     * @param string $exchangeName  The name of the exchange. For example, 'logs'
     * @param string $exchangeType  The type of the exchange. See EXCHANGE_TYPE_* constants for details
     * @param array  $exchangeFlags The flags of the exchange. See \PhpAmqpLib\Channel\AMQPChannel::exchange_declare
     *                              (from third parameter onwards). Must be an assoc array. Default flags can be omitted
     *
     * @see \PhpAmqpLib\Channel\AMQPChannel::exchange_declare
     * @return void
     */
    public function declareExchange($exchangeName, $exchangeType, array $exchangeFlags = [])
    {
        $arguments = [$exchangeName, $exchangeType];

        $flags = array_replace([
            'passive' => false,
            'durable' => false,
            'auto_delete' => true,
            'internal' => false,
            'nowait' => false,
            'arguments' => null,
            'ticket' => null
        ], $exchangeFlags);

        $arguments = array_merge($arguments, $flags);
        call_user_func_array([$this->channel, 'exchange_declare'], $arguments);
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function declareQueue($name)
    {
        $queue = $this->getQueueName($name);
        $this->channel->queue_declare($queue); //TODO: add options support
    }

    /**
     * @param string $destination
     * @param int    $delay
     *
     * @return void
     */
    public function declareDelayedQueue($destination, $delay)
    {
        $destination = $this->getQueueName($destination);
        $name = $destination . '_deferred_' . $delay;
        $arguments = [
            'x-dead-letter-exchange' => $this->exchangeName,
            'x-dead-letter-routing-key' => $destination,
            'x-message-ttl' => $delay * 1000,
        ];

        $this->channel->queue_declare($name, false, true, false, true, false, $arguments);
    }

    /**
     * Helper to return a default queue name in case passed param is empty
     *
     * @param string|null $name
     *
     * @throws AMQPException
     * @return string
     */
    protected function getQueueName($name)
    {
        $name = $name ?: $this->defaultQueueName;
        if ($name === null) {
            throw new AMQPException('Default nor specific queue names given');
        }
        return $name;
    }
}

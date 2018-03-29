<?php

namespace Forumhouse\LaravelAmqp\Queue;

use Forumhouse\LaravelAmqp\Exception\AMQPException;
use Forumhouse\LaravelAmqp\Jobs\AMQPJob;
use Forumhouse\LaravelAmqp\Utility\ArrayUtil;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class representing AMQP Queue
 *
 * @package Forumhouse\LaravelAmqp\Queue
 */
class AMQPQueue extends Queue implements QueueContract
{
    const EXCHANGE_TYPE_DIRECT = 'direct';

    const EXCHANGE_TYPE_TOPIC = 'topic';

    const EXCHANGE_TYPE_HEADERS = 'headers';

    const EXCHANGE_TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPConnection Connection to amqp compatible server
     */
    protected $connection;

    /**
     * @var AMQPChannel Channel, that is used for communication
     */
    protected $channel;

    /**
     * @var string Default queue name to be used when passed queue = null
     */
    protected $defaultQueueName;

    /**
     * @var string Exchange name, if used
     */
    protected $exchangeName;

    /**
     * @var string Default channel id if needed
     */
    private $defaultChannelId;

    /**
     * @var array
     */
    private $queueFlags;

    /**
     * @var array
     */
    private $messageProperties;

    /**
     * @var bool
     */
    private $declareQueues;

    /**
     * @param AMQPStreamConnection $connection
     * @param string               $defaultQueueName  Default queue name
     * @param array                $queueFlags        Queue flags See a list of parameters to
     *                                                \PhpAmqpLib\Channel\AMQPChannel::queue_declare. Parameters should
     *                                                be passed like for call_user_func_array in this parameter
     * @param bool                 $declareQueues     If we should declare queues before actually trying to send a
     *                                                message
     * @param array                $messageProperties This is passed as a second parameter to
     *                                                \PhpAmqpLib\Message\AMQPMessage constructor
     * @param string               $defaultChannelId  Default channel id
     * @param string               $exchangeName      Exchange name
     * @param mixed                $exchangeType      Exchange type
     * @param mixed                $exchangeFlags     Exchange flags
     */
    public function __construct(
        AMQPStreamConnection $connection,
        $defaultQueueName = null,
        $queueFlags = [],
        $declareQueues = true,
        $messageProperties = [],
        $defaultChannelId = null,
        $exchangeName = '',
        $exchangeType = null,
        $exchangeFlags = []
    ) {
        $this->connection = $connection;
        $this->defaultQueueName = $defaultQueueName ?: 'default';
        $this->queueFlags = $queueFlags;
        $this->declareQueues = $declareQueues;
        $this->messageProperties = $messageProperties;
        $this->defaultChannelId = $defaultChannelId;
        $this->exchangeName = $exchangeName;
        $this->channel = $connection->channel($this->defaultChannelId);

        if ($exchangeName !== null) {
            $this->declareExchange($exchangeName, $exchangeType, $exchangeFlags);
        }
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
    protected function declareExchange($exchangeName, $exchangeType, array $exchangeFlags = [])
    {
        $flags = array_replace([
            'exchange'    => $exchangeName,
            'type'        => $exchangeType,
            'passive'     => false,
            'durable'     => false,
            'auto_delete' => true,
            'internal'    => false,
            'nowait'      => false,
            'arguments'   => null,
            'ticket'      => null,
        ], $exchangeFlags);

        call_user_func_array([$this->channel, 'exchange_declare'], $flags);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job   Job implementation class name
     * @param  mixed  $data  Job custom data. Usually array
     * @param  string $queue Queue name, if different from the default one
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     * @throws AMQPException
     * @return bool Always true
     */
    public function push($job, $data = '', $queue = null)
    {
        $queue = $this->prepareQueue($queue);
        $amqpMessage = $this->priorityMessage($job, $data);
        $this->channel->basic_publish($amqpMessage, $this->exchangeName, $this->getRoutingKey($queue));

        return true;
    }

    /**
     * Adds the message to internal buffer to be sent later in a batch
     *
     * @param string $job   Job implementation class name
     * @param mixed  $data  Job custom data. Usually array
     * @param string $queue Queue name, if different from the default one
     *
     * @return bool
     * @throws \Illuminate\Queue\InvalidPayloadException
     * @throws AMQPException
     */
    public function addMessageToBatch($job, $data = '', $queue = null)
    {
        $queue = $this->prepareQueue($queue);
        $amqpMessage = $this->priorityMessage($job, $data);
        $this->channel->batch_basic_publish($amqpMessage, $this->exchangeName, $this->getRoutingKey($queue));

        return true;
    }

    /**
     * Publishes and internal buffer of messages, that were added before with {@see addMessageToBatch}
     *
     * @return void
     */
    public function pushBatch()
    {
        $this->channel->publish_batch();
    }

    /**
     * Helper to return a default queue name in case passed param is empty
     *
     * @param string|null $name Queue name. If null, default will be used
     *
     * @throws AMQPException
     * @return string Queue name to be used in AMQP calls
     */
    protected function getQueueName($name)
    {
        $name = $name ?: $this->defaultQueueName;
        if ($name === null) {
            throw new AMQPException('Default nor specific queue names given');
        }
        return $name;
    }

    /**
     * Declares a queue to the AMQP library
     *
     * @param string $name The name of the queue to declare
     *
     * @return QueueInfo
     * @throws AMQPException
     */
    public function declareQueue($name)
    {
        $queue = $this->getQueueName($name);
        $flags = array_replace_recursive([
            'queue'       => $queue,
            'passive'     => false,
            'durable'     => false,
            'exclusive'   => false,
            'auto_delete' => true,
            'nowait'      => false,
            'arguments'   => null,
            'ticket'      => null,
        ], $this->getQueueFlags($name));

        return QueueInfo::createFromDeclareOk(call_user_func_array([$this->channel, 'queue_declare'], $flags));
    }

    /**
     * @param string      $queueName
     * @param null|string $deferredQueueName
     * @param null|int    $deferredQueueDelay
     *
     * @return array
     */
    protected function getQueueFlags($queueName, $deferredQueueName = null, $deferredQueueDelay = null)
    {
        $args = func_get_args();
        $result = ArrayUtil::arrayMapRecursive(function ($value) use ($args) {
            return is_callable($value) ? call_user_func_array($value, $args) : $value;
        }, $this->queueFlags);

        $result = ArrayUtil::removeNullsRecursive($result);

        return $result;
    }

    /**
     *  Get routing key from config or use default one (queue name)
     *
     * @param $queue string
     *
     * @return string Routing key name
     */
    protected function getRoutingKey($queue)
    {
        return empty($this->queueFlags['routing_key']) ? $queue : $this->queueFlags['routing_key'];
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload Job payload
     * @param  string $queue   Queue name, if different from the default one
     * @param  array  $options Currently unused
     *
     * @return bool Always true
     * @throws AMQPException
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        // NB: DYNAMIC PRIORITY IS NOT IMPLEMENTED FOR RAW MESSAGES,
        // NB: NEED TO SET THE $this->messageProperties['priority'] FIELD

        $queue = $this->prepareQueue($queue);
        $amqpPayload = new AMQPMessage($payload, $this->messageProperties);
        $this->channel->basic_publish($amqpPayload, $this->exchangeName, $queue);
        return true;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay Delay
     * @param  string        $job   Job implementation class name
     * @param  mixed         $data  Job custom data. Usually array
     * @param  string        $queue Queue name, if different from the default one
     *
     * @return bool Always true
     * @throws \Illuminate\Queue\InvalidPayloadException
     * @throws AMQPException
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($delay instanceof \DateTime) {
            $delay = $delay->getTimestamp() - time();
        }

        $queue = $this->prepareQueue($queue);
        $delayedQueueName = $this->declareDelayedQueue($queue, $delay);

        $amqpMessage = $this->priorityMessage($job, $data);
        $this->channel->basic_publish($amqpMessage, $this->exchangeName, $delayedQueueName);
        return true;
    }

    /**
     * Declares delayed queue to the AMQP library
     *
     * @param string $destinationQueueName Queue destination
     * @param int    $delay                Queue delay in seconds
     *
     * @return string Deferred queue name for the specified delay
     * @throws AMQPException
     */
    public function declareDelayedQueue($destinationQueueName, $delay)
    {
        $destinationQueueName = $this->getQueueName($destinationQueueName);
        $deferredQueueName = $destinationQueueName . '_deferred_' . $delay;

        $flags = array_replace_recursive([
            'queue'       => '',
            'passive'     => false,
            'durable'     => false,
            'exclusive'   => false,
            'auto_delete' => true,
            'nowait'      => false,
            'arguments'   => null,
            'ticket'      => null,
        ], $this->getQueueFlags($destinationQueueName, $deferredQueueName, $delay), [
            'queue'     => $deferredQueueName,
            'durable'   => true,
            'arguments' => new AMQPTable([
                'x-dead-letter-exchange'    => '',
                'x-dead-letter-routing-key' => $destinationQueueName,
                'x-message-ttl'             => $delay * 1000,
            ]),
        ]);

        call_user_func_array([$this->channel, 'queue_declare'], $flags);
        return $deferredQueueName;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue Queue name if different from the default one
     *
     * @return \Illuminate\Queue\Jobs\Job|null Job instance or null if no unhandled jobs available
     * @throws AMQPException
     */
    public function pop($queue = null)
    {
        $queue = $this->prepareQueue($queue);
        $envelope = $this->channel->basic_get($queue);

        if ($envelope instanceof AMQPMessage) {
            return new AMQPJob($this->container, $queue, $this->channel, $envelope);
        }

        return null;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     *
     * @return int
     * @throws AMQPException
     */
    public function size($queue = null)
    {
        $data = $this->declareQueue($this->getQueueName($queue));
        return $data->getJobs();
    }

    /**
     * @return array
     */
    public function getMessageProperties()
    {
        return $this->messageProperties;
    }

    /**
     * @param array $messageProperties
     *
     * @return AMQPQueue
     */
    public function setMessageProperties(array $messageProperties)
    {
        $this->messageProperties = $messageProperties;

        return $this;
    }

    /**
     * Prepares a queue for later use. Declares it if needed
     *
     * @param string $queue
     *
     * @return string
     * @throws AMQPException
     */
    private function prepareQueue($queue)
    {
        $queue = $this->getQueueName($queue);
        if ($this->declareQueues) {
            $this->declareQueue($queue);
        }

        return $queue;
    }



    // FNX - ADD DYNAMIC PRIORITY TO MESSAGE
    /**
     * @param string 	$job
     * @param mixed 	$data
     *
     * @return AMQPMessage
     */
    protected function priorityMessage($job, $data)
    {
        $payloadJson = $this->createPayload($job, $data);
        $arrPayload = json_decode($payloadJson, true);

        // OVERRIDE PRIORITY
        // NB: IMPLEMENT QUEUE DEFAULT PRIORITY USING THE FIELD `message_properties['priority']` IN THE CONFIG
        $props = $this->messageProperties;
        if (!empty($arrPayload['priority']) && $arrPayload['priority'] > 0)
            $props['priority'] = $arrPayload['priority'];

        $amqpMessage = new AMQPMessage($payloadJson, $props);
        return $amqpMessage;

    }

    // FNX - OVERRIDE PAYLOAD GENERATION
    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return [
            'data' => [
                'command' => serialize(clone $job),
                'commandName' => get_class($job),
            ],
            'displayName' => $this->getDisplayName($job),
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => $job->tries ?? null,
            'timeout' => $job->timeout ?? null,
            'timeoutAt' => $this->getJobExpiration($job),
            // PRIORITY
            'priority' => $job->priority ?? null,
        ];
    }
    /**
     * Create a typical, string based queue payload array.
     *
     * @param  string  	$job
     * @param  mixed  	$payload
     *
     * @return array
     */
    protected function createStringPayload($job, $payload)
    {
        return [
            'displayName' => is_string($job) ? explode('@', $job)[0] : null,
            'job' => $job,
            'maxTries' 	=> $payload['maxTries'],
            'timeout' 	=> $payload['timeout'],
            'data' 		=> $payload['data'],
            // BUG-FIX FOR ATTEMPTS HERE (OTHERWISE THIS FUNCTION IS THE SAME AS THE BASE IMPLEMENTATION):
            'attempts' 	=> $payload['attempts'],
            'priority'  => $payload['priority'] ?? null,
        ];
    }
}
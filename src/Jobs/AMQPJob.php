<?php

namespace Forumhouse\LaravelAmqp\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Jobs\Job;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Job class for AMPQ jobs
 *
 * @package Forumhouse\LaravelAmqp\Jobs
 */
class AMQPJob extends Job implements \Illuminate\Contracts\Queue\Job
{
    /**
     * @var string
     */
    protected $queue;

    /**
     * @var AMQPMessage
     */
    protected $amqpMessage;
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param Container $container
     * @param string    $queue
     * @param           $channel
     * @param string    $amqpMessage
     */
    public function __construct($container, $queue, $channel, $amqpMessage)
    {
        $this->container = $container;
        $this->queue = $queue;
        $this->amqpMessage = $amqpMessage;
        $this->channel = $channel;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->amqpMessage->body;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     *
     * @return void
     */
    public function release($delay = 0)
    {
        $this->delete();

        $body = $this->amqpMessage->body;
        $body = json_decode($body, true);

        $body['attempts'] = $this->attempts() + 1;
        $job = $body['job'];

        //if retry_after option is set use it on failure instead of traditional delay
        if(isset($body['data']['retryAfter']) && $body['data']['retryAfter'] > 0)
            $delay = $body['data']['retryAfter'];

        /** @var QueueContract $queue */
        $queue = $this->container['queue']->connection();
        if ($delay > 0) {
            $queue->later($delay, $job, $body, $this->getQueue());
        } else {
            $queue->push($job, $body, $this->getQueue());
        }

        // TODO: IS THIS NECESSARY?
        parent::release();
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();
        $this->channel->basic_ack($this->amqpMessage->delivery_info['delivery_tag']);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        $body = json_decode($this->amqpMessage->body, true);

        return isset($body['attempts']) ? $body['attempts'] : 0;
    }

    /**
     * Get queue name
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        try {
            return $this->amqpMessage->get('message_id');
        } catch (\OutOfBoundsException $exception){
            return null;
        }
    }

}

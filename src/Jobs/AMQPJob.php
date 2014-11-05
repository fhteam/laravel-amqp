<?php

namespace Forumhouse\LaravelAmqp\Jobs;

use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\QueueInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPJob extends Job
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
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire(json_decode($this->amqpMessage->body, true));
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
     * Get queue name
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
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

        $attempts = $this->attempts();

        // write attempts to body
        $body['data']['attempts'] = $attempts + 1;

        $job = $body['job'];
        $data = $body['data'];

        /** @var QueueInterface $queueInterface */
        $queueInterface = $this->container['queue']->connection();
        if ($delay > 0) {
            $queueInterface->later($delay, $job, $data, $this->getQueue());
        } else {
            $queueInterface->push($job, $data, $this->getQueue());
        }
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        $body = json_decode($this->amqpMessage->body, true);

        return isset($body['data']['attempts']) ? $body['data']['attempts'] : 0;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->amqpMessage->get('message_id');
    }
}

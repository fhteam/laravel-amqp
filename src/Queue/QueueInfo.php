<?php

namespace Forumhouse\LaravelAmqp\Queue;

/**
 * Class providing information about a queue
 */
class QueueInfo
{
    /**
     * @var string The name of the queue
     */
    private $name;

    /**
     * @var int The number of ready messages in the queue
     */
    private $jobs;

    /**
     * @var int The number of consumers currently connected to read from queue
     */
    private $consumers;

    public function __construct($name, $jobs, $consumers)
    {
        $this->name = $name;
        $this->jobs = $jobs;
        $this->consumers = $consumers;
    }

    /**
     * Creates an instance from the data returned from queue_declare call
     *
     * @param array $declareOkData An array of data returned by queue_declare
     *
     * @return static
     */
    public static function createFromDeclareOk(array $declareOkData)
    {
        list($name, $jobs, $consumers) = $declareOkData;
        return new static($name, $jobs, $consumers);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * @return int
     */
    public function getConsumers()
    {
        return $this->consumers;
    }
}
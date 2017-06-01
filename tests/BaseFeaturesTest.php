<?php

namespace Forumhouse\LaravelAmqp\Tests;

use Forumhouse\LaravelAmqp\Jobs\AMQPJob;
use Illuminate\Queue\Jobs\Job;

/**
 * Class BaseFeaturesTest
 *
 * @package Forumhouse\LaravelAmqp\Tests
 */
class BaseFeaturesTest extends LaravelAmqpTestBase
{

    /**
     * Respect PHP 5.4 here (no ::class)
     */
    const TEST_JOB_CLASS = 'Forumhouse\LaravelAmqp\Tests\TestJobHandler';

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
    }


    /**
     * Test for a job, ending with a delete() call (deleted from queue)
     */
    public function testSimpleDelete()
    {
        $this->app['queue']->push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true]);
        /** @var AMQPJob $job */
        $job = $this->app['queue']->pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        $noJobs = $this->app['queue']->pop();
        $this->assertNull($noJobs);
    }

    /**
     * Test for a job, ending with a release() call (released back into queue)
     */
    public function testSimpleRelease()
    {
        $this->app['queue']->push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'release' => true]);

        /** @var AMQPJob $job */
        $job = $this->app['queue']->pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $this->assertEquals(0, $job->attempts());
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        //Popping released job
        $job = $this->app['queue']->pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $this->assertEquals(1, $job->attempts());
        $job->delete();

        $noJobs = $this->app['queue']->pop();
        $this->assertNull($noJobs);
    }

    /**
     * Test for several simultaneous queues
     */
    public function testCustomQueues()
    {
        $this->app['queue']->push(
            self::TEST_JOB_CLASS,
            ['test1' => 1, 'test2' => 2, 'delete' => true],
            'custom_queue_1'
        );

        $this->app['queue']->push(
            self::TEST_JOB_CLASS,
            ['test1' => 1, 'test2' => 2, 'delete' => true],
            'custom_queue_2'
        );

        /** @var Job $job */
        $job = $this->app['queue']->pop('custom_queue_1');
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
        $noJobs = $this->app['queue']->pop('custom_queue_1');
        $this->assertNull($noJobs);

        $job = $this->app['queue']->pop('custom_queue_2');
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
        $noJobs = $this->app['queue']->pop('custom_queue_2');
        $this->assertNull($noJobs);
    }

    /**
     * Test for delayed queue messages
     */
    public function testDelayedQueue()
    {
        $this->app['queue']->later(5, self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true]);
        $noJobs = $this->app['queue']->pop();
        $this->assertNull($noJobs);
        sleep(6);
        
        /** @var Job $job */
        $job = $this->app['queue']->pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
    }
}

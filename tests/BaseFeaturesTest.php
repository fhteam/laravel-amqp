<?php

namespace Forumhouse\LaravelAmqp\Tests;

use Illuminate\Queue\Jobs\Job;
use Queue;

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
        Queue::push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true]);
        /** @var Job $job */
        $job = Queue::pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        $noJobs = Queue::pop();
        $this->assertNull($noJobs);
    }

    /**
     * Test for a job, ending with a release() call (released back into queue)
     */
    public function testSimpleRelease()
    {
        Queue::push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'release' => true]);

        /** @var Job $job */
        $job = Queue::pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $this->assertEquals(0, $job->attempts());
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        //Popping released job
        $job = Queue::pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $this->assertEquals(1, $job->attempts());
        $job->delete();

        $noJobs = Queue::pop();
        $this->assertNull($noJobs);
    }

    /**
     * Test for several simultaneous queues
     */
    public function testCustomQueues()
    {
        Queue::push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true], 'custom_queue_1');
        Queue::push(self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true], 'custom_queue_2');

        /** @var Job $job */
        $job = Queue::pop('custom_queue_1');
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
        $noJobs = Queue::pop('custom_queue_1');
        $this->assertNull($noJobs);

        $job = Queue::pop('custom_queue_2');
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
        $noJobs = Queue::pop('custom_queue_2');
        $this->assertNull($noJobs);
    }

    /**
     * Test for delayed queue messages
     */
    public function testDelayedQueue()
    {
        Queue::later(5, self::TEST_JOB_CLASS, ['test1' => 1, 'test2' => 2, 'delete' => true]);
        $noJobs = Queue::pop();
        $this->assertNull($noJobs);
        sleep(6);

        $job = Queue::pop();
        $this->assertInstanceOf('Illuminate\Queue\Jobs\Job', $job);
        $job->delete();
    }
}

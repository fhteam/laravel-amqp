<?php

namespace Forumhouse\LaravelAmqp\Tests;

use Illuminate\Queue\Jobs\Job;
use Queue;

class BaseFeaturesTest extends LaravelAmqpTestBase
{

    public function testSimpleDelete()
    {
        Queue::push(TestJobHandler::class, ['test1' => 1, 'test2' => 2, 'delete' => true]);
        /** @var Job $job */
        $job = Queue::pop();
        $this->assertInstanceOf(Job::class, $job);
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        $noJobs = Queue::pop();
        $this->assertNull($noJobs);
    }

    public function testSimpleRelease()
    {
        Queue::push(TestJobHandler::class, ['test1' => 1, 'test2' => 2, 'release' => true]);

        /** @var Job $job */
        $job = Queue::pop();
        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals(0, $job->attempts());
        $job->fire();
        $jobdata = json_decode($job->getRawBody(), true);
        $this->assertEquals(1, $jobdata['data']['test1']);
        $this->assertEquals(2, $jobdata['data']['test2']);

        //Popping released job
        $job = Queue::pop();
        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals(1, $job->attempts());
        $job->delete();

        $noJobs = Queue::pop();
        $this->assertNull($noJobs);
    }

    public function testCustomQueues()
    {
        Queue::push(TestJobHandler::class, ['test1' => 1, 'test2' => 2, 'delete' => true], 'custom_queue_1');
        Queue::push(TestJobHandler::class, ['test1' => 1, 'test2' => 2, 'delete' => true], 'custom_queue_2');

        /** @var Job $job */
        $job = Queue::pop('custom_queue_1');
        $this->assertInstanceOf(Job::class, $job);
        $job->delete();
        $noJobs = Queue::pop('custom_queue_1');
        $this->assertNull($noJobs);

        $job = Queue::pop('custom_queue_2');
        $this->assertInstanceOf(Job::class, $job);
        $job->delete();
        $noJobs = Queue::pop('custom_queue_2');
        $this->assertNull($noJobs);
    }
}

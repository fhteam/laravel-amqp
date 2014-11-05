<?php

namespace Forumhouse\LaravelAmqp\Tests;

use Exception;
use Illuminate\Queue\Jobs\Job;

class TestJobHandler
{
    /**
     * @var Job
     */
    protected $job;

    /**
     * @var bool Call delete on the job after completion
     */
    protected $delete;

    /**
     * @param Job   $job
     * @param array $data
     *
     * @throws Exception
     */
    public function fire($job, $data)
    {
        $this->job = $job;
        if (($data['test1'] != 1) || ($data['test2'] != 2)) {
            throw new Exception("Parameters passed incorrectly" . var_export($data, true));
        }

        if (isset($data['delete'])) {
            $this->job->delete();
            return;
        }

        if (isset($data['release'])) {
            $this->job->release();
            return;
        }
    }
}

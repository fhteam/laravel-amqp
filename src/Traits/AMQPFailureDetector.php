<?php

namespace Forumhouse\LaravelAmqp\Traits;
use Exception;
use Illuminate\Support\Str;

trait AMQPFailureDetector
{

    public function catchAMQPConnectionFailure(Exception $exception)
    {
        if(\App::runningInConsole() && $this->causedByLostConnection($exception) && extension_loaded('posix')){
            posix_kill(getmypid(), SIGTERM);
        }
    }

    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ]);
    }
}
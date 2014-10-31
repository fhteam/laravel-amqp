<?php

namespace Forumhouse\LaravelAmqp;

use Forumhouse\LaravelAmqp\Connectors\AmqpConnector;
use Illuminate\Support\ServiceProvider;

class LaravelAmqpServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booted(function () {

            $this->app['queue']->extend('amqp', function () {
                return new AmqpConnector;
            });
        });
    }
}

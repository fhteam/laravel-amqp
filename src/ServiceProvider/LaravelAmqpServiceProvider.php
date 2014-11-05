<?php

namespace Forumhouse\LaravelAmqp\ServiceProvider;

use Forumhouse\LaravelAmqp\Connectors\AmqpConnector;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for AMQP queues
 *
 * @package Forumhouse\LaravelAmqp\ServiceProvider
 */
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

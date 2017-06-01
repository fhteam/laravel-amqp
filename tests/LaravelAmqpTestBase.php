<?php

namespace Forumhouse\LaravelAmqp\Tests;

use Orchestra\Testbench\TestCase;

/**
 * Class LaravelAmqpTestBase
 *
 * @package Forumhouse\LaravelAmqp\Tests
 */
class LaravelAmqpTestBase extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $app['config'];
        // reset base path to point to our package's src directory
        $config->set('queue.connections.amqp', [
            'driver'             => 'amqp',
            'host'               => 'localhost',
            'port'               => '5672',
            'user'               => 'guest',
            'password'           => 'guest',
            'vhost'              => '/',
            'queue'              => null,
            'queue_flags'        => ['durable' => true],
            'declare_queues'     => true,
            'message_properties' => ['delivery_mode' => 2],
            'channel_id'         => null,
            'exchange_name'      => null,
            'exchange_type'      => null,
            'exchange_flags'     => null,
        ]);

        $config->set('queue.default', 'amqp');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Forumhouse\LaravelAmqp\ServiceProvider\LaravelAmqpServiceProvider',
        ];
    }
}

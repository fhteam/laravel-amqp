<?php

namespace LaravelAmqp\Test;

use Orchestra\Testbench\TestCase;

class TestCaseTest extends TestCase
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
        $config->set('queue.connections.amqp', array(
            'driver' => 'amqp',
            'host' => '',
            'port' => '',
            'user' => '',
            'password' => '',
            'queue' => '',
            'exchange_name' => null,
            'exchange_type' => null,
            'exchange_flags' => null,
        ));

        $config->set('queue.default', 'amqp');
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @return array
     */
    protected function getPackageProviders()
    {
        return array(
            'Forumhouse\LaravelAmqp\LaravelAmqpServiceProvider',
        );
    }

    public function testQueueGeneral()
    {

    }
}

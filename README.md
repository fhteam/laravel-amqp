laravel-amqp
============

AMQP driver for Laravel queue.


Configuration
------------

In your ```config/queue.php``` file you have to provide the following:

```php

'default' => 'amqp',

'connections' => array(
    'amqp' => array(
        'driver' => 'amqp',
        'host' => 'localhost',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'queue' => null,
        'channel_id' => null,
        'exchange_name' => null,
        'exchange_type' => null,
        'exchange_flags' => null,
        ),
),
```

In your ```config/app.php``` add ```'Forumhouse\LaravelAmqp\ServiceProvider\LaravelAmqpServiceProvider'``` to the list of service 
providers registered.

Usage
------------

To find out how to use Laravel Queues, please refer to the following official documentation: http://laravel.com/docs/queues
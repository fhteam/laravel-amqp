laravel-amqp
============

AMQP driver for Laravel queue. This driver uses popular AMQPLib for PHP: https://github.com/videlalvaro/php-amqplib
(This library is a pure PHP implementation of the AMQP protocol so it may be used to connect to a number of
queue managers around)


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

Null values may be omitted.

In your ```config/app.php``` add ```'Forumhouse\LaravelAmqp\ServiceProvider\LaravelAmqpServiceProvider'``` to the list of service 
providers registered.

Usage
------------

To find out how to use Laravel Queues, please refer to the following official documentation: http://laravel.com/docs/queues
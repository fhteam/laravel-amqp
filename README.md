laravel-amqp [![PHP version](https://badge.fury.io/ph/fhteam%2Flaravel-amqp.svg)](http://badge.fury.io/ph/fhteam%2Flaravel-amqp) [![Code Climate](https://codeclimate.com/github/forumhouseteam/laravel-amqp/badges/gpa.svg)](https://codeclimate.com/github/forumhouseteam/laravel-amqp) [![Laravel compatibility](https://img.shields.io/badge/laravel-5-green.svg)](http://laravel.com/)
============

AMQP driver for Laravel queue. This driver uses popular AMQPLib for PHP: https://github.com/videlalvaro/php-amqplib
(This library is a pure PHP implementation of the AMQP protocol so it may be used to connect to a number of
queue managers around)

Installation
------------

*Please do note, that package name has changed to fhteam/laravel-amqp.* Old name should still work, though it will not be
maintained.

 - Simple composer installation is ok: ```composer require fhteam/laravel-amqp:~1.0 ```
 (set version requirement to your favourite)
 - Note, that mbstring and bcmath extensions are required for php-amqplib to work properly. The first is not yet listed
 in library's composer.json (https://github.com/videlalvaro/php-amqplib/issues/229)

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
        'vhost' => '/',
        'queue' => null,
        'queue_flags' => ['durable' => true, 'routing_key' => null], //Durable queue (survives server crash)
        'declare_queues' => true, //If we need to declare queues each time before sending a message. If not, you will have to declare them manually elsewhere
        'message_properties' => ['delivery_mode' => 2], //Persistent messages (survives server crash)
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

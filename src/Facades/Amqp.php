<?php

namespace Anik\Laravel\Amqp\Facades;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Queues\Queue;
use Anik\Amqp\Qos\Qos;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Anik\Laravel\Amqp\Amqp connection(?string $name = null)
 * @method static bool publish($messages, string $routingKey = '', ?Exchange $exchange = null, array $options = [])
 * @method static void consume($handler, string $bindingKey = '', ?Exchange $exchange = null, ?Queue $queue = null, ?Qos $qos = null, array $options = [])
 */
class Amqp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'amqp';
    }
}

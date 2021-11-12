<?php

namespace Anik\Laravel\Amqp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Anik\Laravel\Amqp\Amqp connection(?string $name = null)
 * @method static bool publish($messages, string $routingKey = '', ?Anik\Amqp\Exchanges\Exchange $exchange = null, array $options = []);
 */
class Amqp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'amqp';
    }
}

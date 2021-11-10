<?php

namespace Anik\Laravel\Amqp\Facades;

use Illuminate\Support\Facades\Facade;

class Amqp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'amqp';
    }
}

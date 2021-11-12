<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\Exchanges\Exchange;

interface AmqpPubSub
{
    public function publish($messages, string $routingKey = '', ?Exchange $exchange = null, array $options = []): bool;
}

<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;

interface AmqpPubSub
{
    public function publish($messages, string $routingKey = '', ?Exchange $exchange = null, array $options = []): bool;

    public function consume(
        $handler,
        string $bindingKey = '',
        ?Exchange $exchange = null,
        ?Queue $queue = null,
        ?Qos $qos = null,
        array $options = []
    ): void;
}

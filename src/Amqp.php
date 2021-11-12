<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Illuminate\Support\Arr;
use PhpAmqpLib\Connection\AbstractConnection;

class Amqp implements AmqpPubSub
{
    private $connection;

    private $config;

    public function __construct(AbstractConnection $connection, array $config = [])
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    public function getProducer(): Producer
    {
        return app()->make(Producer::class, ['connection' => $this->connection]);
    }

    public function publish($messages, string $routingKey = '', ?Exchange $exchange = null, array $options = []): bool
    {
        if ($messageProperties = $options['message'] ?? $this->getMessageDefaultOptions()) {
            unset($options['message']);
        }

        $producibleMessages = [];
        foreach (Arr::wrap($messages) as $msg) {
            $producibleMessages[] = $this->ensureMessageIsProducibleInstance($msg, $messageProperties);
        }

        if (is_null($exchange) && !isset($options['exchange'])) {
            $options['exchange'] = $this->getExchangeOptions();
        }

        if (!isset($options['publish']) && isset($this->config['publish'])) {
            $options['publish'] = $this->config['publish'];
        }

        return $this->getProducer()->publishBatch($producibleMessages, $routingKey, $exchange, $options);
    }

    protected function defaultExchangeOptions(): array
    {
        return [
            'name' => 'amq.direct',
            'declare' => false,
            'type' => 'topic',
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
            'internal' => false,
            'no_wait' => false,
            'arguments' => [],
            'ticket' => null,
        ];
    }

    protected function getExchangeOptions(): array
    {
        return $this->config['exchange'] ?? $this->defaultExchangeOptions();
    }

    protected function getMessageDefaultOptions(): array
    {
        return $this->config['message'] ?? [];
    }

    protected function ensureMessageIsProducibleInstance($msg, array $options = []): Producible
    {
        return !$msg instanceof Producible ? new ProducibleMessage($msg, $options) : $msg;
    }
}

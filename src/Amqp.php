<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\Consumable;
use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use Illuminate\Support\Arr;
use PhpAmqpLib\Connection\AbstractConnection;

class Amqp implements AmqpPubSub
{
    private $connection;

    private $config;

    private Producer $producer;

    public function __construct(AbstractConnection $connection, array $config = [])
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    public function getProducer(): Producer
    {
        return $this->producer ??= app()->make(Producer::class, ['connection' => $this->connection]);
    }

    public function getConsumer(array $options = []): Consumer
    {
        return app()->make(
            Consumer::class,
            ['connection' => $this->connection, 'channel' => null, 'options' => $options]
        );
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

        if (!isset($options['publish']) && ($publishOptions = $this->getPublishOptions())) {
            $options['publish'] = $publishOptions;
        }

        return $this->getProducer()->publishBatch($producibleMessages, $routingKey, $exchange, $options);
    }

    public function consume(
        $handler,
        string $bindingKey = '',
        ?Exchange $exchange = null,
        ?Queue $queue = null,
        ?Qos $qos = null,
        array $options = []
    ): void {
        $handler = $this->ensureHandlerIsConsumableInstance($handler);

        if (is_null($exchange) && !isset($options['exchange'])) {
            $options['exchange'] = $this->getExchangeOptions();
        }

        if (is_null($queue) && !isset($options['queue'])) {
            $options['queue'] = $this->getQueueOptions();
        }

        if (!isset($options['bind']) && ($bind = $this->getBindOptions())) {
            $options['bind'] = $bind;
        }

        if (is_null($qos) && !isset($options['qos']) && ($qosOptions = $this->getQosOptions())) {
            $options['qos'] = $qosOptions;
        }

        if (!isset($options['consumer']) && ($consumerOptions = $this->getConsumerOptions())) {
            $options['consumer'] = $consumerOptions;
        }

        $this->getConsumer()->consume($handler, $bindingKey, $exchange, $queue, $qos, $options);
    }

    protected function getExchangeOptions(): array
    {
        return $this->config['exchange'] ?? [];
    }

    protected function getQueueOptions(): array
    {
        return $this->config['queue'] ?? [];
    }

    protected function getPublishOptions(): array
    {
        return $this->config['publish'] ?? [];
    }

    protected function getBindOptions(): array
    {
        return $this->config['bind'] ?? [];
    }

    protected function getQosOptions(): array
    {
        $qos = $this->config['qos'] ?? [];

        return ($qos['enabled'] ?? false) ? $qos : [];
    }

    protected function getConsumerOptions(): array
    {
        return $this->config['consumer'] ?? [];
    }

    protected function getMessageDefaultOptions(): array
    {
        return $this->config['message'] ?? [];
    }

    protected function ensureMessageIsProducibleInstance($msg, array $options = []): Producible
    {
        return !$msg instanceof Producible ? new ProducibleMessage($msg, $options) : $msg;
    }

    protected function ensureHandlerIsConsumableInstance($handler): Consumable
    {
        return !$handler instanceof Consumable ? new ConsumableMessage($handler) : $handler;
    }
}

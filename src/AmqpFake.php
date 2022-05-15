<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use Anik\Laravel\Amqp\Exceptions\LaravelAmqpException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

class AmqpFake extends AmqpManager implements AmqpPubSub
{
    protected $messages;
    protected $activeConnection;
    protected $connections;

    public function __construct(Container $app)
    {
        parent::__construct($app);
        $this->messages = [];
        $this->connections = [];
    }

    public function __call($method, $arguments)
    {
        throw new LaravelAmqpException(sprintf('Call to undefined method %s::%s', self::class, $method));
    }

    public function connection(?string $name = null): AmqpPubSub
    {
        $this->activeConnection = $name = $name ?? $this->getDefaultConnection();

        return $this->connections[$name] ?? ($this->connections[$name] = $this);
    }

    private function getDefaultValueForConnection(string $key, ?string $connection = null)
    {
        return $this->config(sprintf('amqp.connections.%s.%s', $connection ?? $this->getDefaultConnection(), $key));
    }

    public function publish($messages, string $routingKey = '', ?Exchange $exchange = null, array $options = []): bool
    {
        foreach (Arr::wrap($messages) as $msg) {
            $this->messages[] = [
                'connection_name' => $this->activeConnection ?? $this->getDefaultConnection(),
                'exchange_name' => $exchange
                    ? $exchange->getName()
                    : ($options['exchange']['name'] ?? $this->getDefaultValueForConnection('exchange.name')),
                'exchange_type' => $exchange
                    ? $exchange->getType()
                    : ($options['exchange']['type'] ?? $this->getDefaultValueForConnection('exchange.type')),
                'routing_key' => $routingKey,
                'message' => $msg,
            ];
        }

        $this->activeConnection = null;

        return true;
    }

    public function consume(
        $handler,
        string $bindingKey = '',
        ?Exchange $exchange = null,
        ?Queue $queue = null,
        ?Qos $qos = null,
        array $options = []
    ): void {
        throw new LaravelAmqpException('You cannot consume AMQP using fake');
    }

    protected function filterMessage(?string $key = null, $value = null): Collection
    {
        return collect($this->messages)->when(
        // When key is specified, only the filter the values
            $key,
            function (Collection $collection, $key) use ($value) {
                return $collection->filter(
                    function ($item) use ($key, $value) {
                        // if the value is callable, then let it decide if the value is legit
                        // dd($key, $value, $item, data_get($item, $key), $value);
                        return is_callable($value) ? $value($item) : (data_get($item, $key) === $value);
                    }
                );
            }
        );
    }

    protected function numberOfMessagesWhen(?string $key = null, $value = null): int
    {
        return $this->filterMessage($key, $value)->count();
    }

    protected function filterOnPublishedMessage($message): Collection
    {
        return $this->filterMessage(
            'message',
            function ($item) use ($message) {
                if ($item['message'] === $message) {
                    return true;
                }

                if (class_exists($message) && get_class($item['message']) === $message) {
                    return true;
                }

                if (interface_exists($message) && is_subclass_of($item['message'], $message)) {
                    return true;
                }

                return false;
            }
        );
    }

    public function assertPublishedOnConnection(string $name): void
    {
        Assert::assertGreaterThan(
            0,
            $this->numberOfMessagesWhen('connection_name', $name),
            sprintf('Message is not published on expected connection "%s"', $name)
        );
    }

    public function assertPublishedOnExchange(string $name): void
    {
        Assert::assertGreaterThan(
            0,
            $this->numberOfMessagesWhen('exchange_name', $name),
            sprintf('Message is not published on expected exchange "%s"', $name)
        );
    }

    public function assertPublishedOnExchangeType(string $type): void
    {
        Assert::assertGreaterThan(
            0,
            $this->numberOfMessagesWhen('exchange_type', $type),
            sprintf('Message is not published on expected exchange type "%s"', $type)
        );
    }

    public function assertPublishedWithRoutingKey(string $key): void
    {
        Assert::assertGreaterThan(
            0,
            $this->numberOfMessagesWhen('routing_key', $key),
            sprintf('Message is not published with expected routing key "%s"', $key)
        );
    }

    public function assertPublished($message = null): void
    {
        if (is_null($message)) {
            Assert::assertGreaterThan(0, $this->numberOfMessagesWhen(), 'No message was published');

            return;
        }

        Assert::assertGreaterThan(
            0,
            $this->filterOnPublishedMessage($message)->count(),
            sprintf('No message was published that matches "%s"', $message)
        );
    }

    public function assertPublishedCount(int $count, $message = null): void
    {
        if (is_null($message)) {
            Assert::assertEquals($count, $this->numberOfMessagesWhen(), 'Message Count was not ' . $count);

            return;
        }

        Assert::assertEquals(
            $count,
            $this->filterOnPublishedMessage($message)->count(),
            sprintf('Message Count was not ' . $count . " that matches %s", $message)
        );
    }

    public function assertNotPublished($message = null): void
    {
        if (is_null($message)) {
            Assert::assertCount(0, $this->messages, 'Some messages were published');

            return;
        }

        Assert::assertEquals(
            0,
            $this->filterOnPublishedMessage($message)->count(),
            sprintf('Some messages were published that matches "%s"', $message)
        );
    }
}

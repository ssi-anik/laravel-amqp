<?php

namespace Anik\Laravel\Amqp;

use Anik\Amqp\AmqpConnectionFactory;
use Anik\Laravel\Amqp\Exceptions\LaravelAmqpException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AmqpManager
{
    protected $app;

    protected $connections;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function connection(?string $name = null): AmqpPubSub
    {
        $name = $name ?? $this->getDefaultConnection();

        return $this->connections[$name] ?? $this->connections[$name] = $this->resolve($name);
    }

    public function __call($method, $arguments)
    {
        return $this->connection()->$method(...$arguments);
    }

    protected function getDefaultConnection(): string
    {
        return $this->config('amqp.default', 'rabbitmq');
    }

    protected function config($key, $default = null)
    {
        try {
            $repository = $this->app->get('config');
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return $default;
        }

        return $repository instanceof Repository ? $repository->get($key, $default) : $default;
    }

    protected function resolve(string $name): AmqpPubSub
    {
        $config = $this->config(sprintf('amqp.connections.%s', $name), []);
        $connection = $config['connection'] ?? [];

        if (empty($connection)) {
            throw new LaravelAmqpException(
                sprintf('Did you forget to set connection for "amqp.connections.%s"?', $name)
            );
        }

        if (empty($hosts = $connection['hosts'] ?? []) || !is_array($hosts)) {
            throw new LaravelAmqpException(
                sprintf('Invalid hosts for connection "%s". Hosts must be an array.', $name)
            );
        }

        return new Amqp(
            AmqpConnectionFactory::makeFromArray(
                $hosts,
                $connection['options'] ?? [],
                $connection['class'] ?? AMQPLazySSLConnection::class
            ),
            $config
        );
    }
}

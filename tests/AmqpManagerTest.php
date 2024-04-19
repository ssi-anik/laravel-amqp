<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Laravel\Amqp\Amqp;
use Anik\Laravel\Amqp\AmqpManager;
use Anik\Laravel\Amqp\Exceptions\LaravelAmqpException;
use Closure;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;

class AmqpManagerTest extends PackageBaseTestCase
{
    public static function connectionConfigurationDataProvider(): array
    {
        return [
            'config is empty' => [[]],
            'hosts is empty' => [['connection' => ['hosts' => []]]],
            'hosts is not an array' => [['connection' => ['hosts' => 'not-array']]],
        ];
    }

    public function testConnectionMethodCanResolveAnyValidConnection()
    {
        app('config')->set(
            'amqp.connections.new.connection',
            [
                'hosts' => [
                    [
                        'host' => 'localhost',
                        'port' => 5672,
                        'user' => 'invalid-user',
                        'password' => 'invalid-password',
                        'vhost' => '/',
                    ],
                ],
            ]
        );

        app('config')->set('amqp.default', 'new');

        $this->assertInstanceOf(Amqp::class, app('amqp')->connection());
        $this->assertInstanceOf(Amqp::class, \Anik\Laravel\Amqp\Facades\Amqp::connection());
        $this->assertInstanceOf(Amqp::class, app('amqp')->connection('rabbitmq'));
    }

    public function testConnectionMethodUsesClassFromConfigWhenCreatingAbstractConnection()
    {
        app('config')->set('amqp.connections.rabbitmq.connection.class', AMQPLazySocketConnection::class);

        $connection = \Anik\Laravel\Amqp\Facades\Amqp::connection();

        $abstractConnection = Closure::fromCallable(
            function () {
                return $this->connection;
            }
        )->call($connection);

        $this->assertInstanceOf(AMQPSocketConnection::class, $abstractConnection);
    }

    /**
     * @dataProvider connectionConfigurationDataProvider
     *
     * @param array $data
     */
    public function testConnectionMethodThrowsExceptionIfConfigurationIsInvalid(array $data)
    {
        app('config')->set('amqp.connections.new', $data);
        app('config')->set('amqp.default', 'new');

        $this->expectException(LaravelAmqpException::class);
        \Anik\Laravel\Amqp\Facades\Amqp::connection();
    }

    public function testManagerCanForwardCallsByResolvingConnection()
    {
        $this->app->make(AmqpManager::class)->getProducer();
        $this->app->make(AmqpManager::class)->getConsumer();
    }
}

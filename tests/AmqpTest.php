<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Anik\Laravel\Amqp\Amqp;
use Orchestra\Testbench\TestCase;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpTest extends TestCase
{
    protected const EXCHANGE_NAME = 'anik.laravel.amqp.exchange.topic';
    protected const EXCHANGE_TYPE = 'topic';

    protected $connection;
    protected $channel;
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getAbstractConnectionMock();
        $this->channel = $this->getAmqpChannelMock();

        $this->connection->method('channel')->willReturn($this->channel);
        $this->channel->method('getChannelId')->willReturn(1);

        $this->config = $this->app['config']->get('amqp.connections.rabbitmq');
    }

    protected function getAbstractConnectionMock(): AbstractConnection
    {
        return $this->getMockBuilder(AMQPLazySSLConnection::class)->disableOriginalConstructor()->getMock();
    }

    protected function getAmqpChannelMock(): AMQPChannel
    {
        return $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
    }

    protected function getAmqpInstance(?AbstractConnection $connection = null, ?array $config = null): Amqp
    {
        return new Amqp($connection ?? $this->connection, $config ?? $this->config ?? []);
    }

    protected function getProducerMock(?AbstractConnection $connection = null): Producer
    {
        return $this->getMockBuilder(Producer::class)->setConstructorArgs(['connection' => $connection])->getMock();
    }

    protected function bindProducerToApp($producer): void
    {
        $this->app->bind(
            Producer::class,
            function () use ($producer) {
                return $producer;
            }
        );
    }

    public function publishMessageTestDataProvider(): array
    {
        return [
            'publish single message of scalar' => [
                [
                    'msg' => 'message',
                ],
            ],
            'publish multiple messages of scalar' => [
                [
                    'msg' => [
                        'message',
                        1,
                        2,
                    ],
                ],
            ],
            'publish single message of publishable' => [
                [
                    'msg' => new ProducibleMessage('message'),
                ],
            ],
            'publish multiple messages of publishable' => [
                [
                    'msg' => [
                        new ProducibleMessage('message'),
                        new ProducibleMessage(1),
                        new ProducibleMessage(12.2),
                    ],
                ],
            ],
        ];
    }

    public function exchangeTestDataProvider(): array
    {
        return [
            'when exchange is set option is empty - uses exchange' => [
                [
                    'exchange' => Topic::make(['name' => 'my.topic']),
                ],
            ],
            'when exchange is null and options is empty - uses default config' => [
                [
                    'expectations' => [
                        'exchange.name' => self::EXCHANGE_NAME,
                        'exchange.type' => self::EXCHANGE_TYPE,
                    ],
                ],
            ],
            'when exchange is null and options has exchange config - uses option exchange config' => [
                [
                    'options' => [
                        'exchange' => [
                            'name' => self::EXCHANGE_NAME,
                            'type' => self::EXCHANGE_TYPE,
                        ],
                    ],

                    'expectations' => [
                        'exchange.name' => self::EXCHANGE_NAME,
                        'exchange.type' => self::EXCHANGE_TYPE,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider publishMessageTestDataProvider
     *
     * @param array $data
     */
    public function testPublishFormatsMessagesToProducible(array $data)
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $message = $data['msg'];

        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->callback(
                    function ($messages) {
                        foreach ($messages as $message) {
                            if (!$message instanceof Producible) {
                                return false;
                            }
                        }

                        return true;
                    }
                ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->publish($message);
    }

    public function testPublishUsesMessageKeyToUseAsPropertiesWhenBuildingProducible()
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $message = 'my message';
        $properties = ['application_headers' => new AMQPTable(['keys' => 'value'])];

        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->callback(
                    function ($msgs) use ($message, $properties) {
                        return $msgs[0] == new ProducibleMessage(
                                $message, $properties
                            );
                    }
                ),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->publish(
            $message,
            '',
            null,
            ['message' => $properties]
        );
    }

    public function testPublishPassesExpectedBindingKey()
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->anything(),
                'my-binding-key',
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->publish(
            'my message',
            'my-binding-key'
        );
    }

    /**
     * @dataProvider exchangeTestDataProvider
     *
     * @param array $data
     */
    public function testPublishMessageProcessesExchange(array $data)
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->anything(),
                $this->anything(),
                $data['exchange'] ?? null,
                $this->callback(
                    function ($options) use ($expectation) {
                        if (empty($expectation)) {
                            return true;
                        }

                        foreach ($expectation as $key => $value) {
                            if (data_get($options, $key) !== $value) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            );

        $this->getAmqpInstance(null, ['exchange' => ['name' => self::EXCHANGE_NAME, 'type' => 'topic']])
             ->publish(
                 'my message',
                 'my-binding-key',
                 $data['exchange'] ?? null,
                 $data['options'] ?? []
             );
    }
}

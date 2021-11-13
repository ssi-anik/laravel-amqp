<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Amqp\Consumable;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exchanges\Topic;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
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
    protected const QUEUE_NAME = 'anik.laravel.queue.name';
    protected const QOS_PREFETCH_SIZE = 1;

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

    protected function getProducerMock(AbstractConnection $connection = null): Producer
    {
        return $this->getMockBuilder(Producer::class)->setConstructorArgs(['connection' => $connection])->getMock();
    }

    protected function getConsumerMock(AbstractConnection $connection = null, array $options = []): Consumer
    {
        return $this->getMockBuilder(Consumer::class)->setConstructorArgs(
            ['connection' => $connection, 'channel' => null, 'options' => $options]
        )->getMock();
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

    protected function bindConsumerToApp($consumer): void
    {
        $this->app->bind(
            Consumer::class,
            function () use ($consumer) {
                return $consumer;
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

    public function publishOptionsDataProvider(): array
    {
        return [
            'when publish option is passed, should not use config value' => [
                [
                    'options' => [
                        'publish' => [
                            'batch_count' => 23,
                        ],
                    ],
                    'expectations' => [
                        'publish.batch_count' => 23,
                    ],
                ],
            ],
            'when publish option is not passed, should use config value if available' => [
                [
                    'config' => [
                        'publish' => [
                            'batch_count' => 37,
                        ],
                    ],
                    'expectations' => [
                        'publish.batch_count' => 37,
                    ],
                ],
            ],
            'when publish option and config both are unavailable' => [
                [
                    'expectations' => [
                        'publish.batch_count' => null,
                    ],
                ],
            ],
        ];
    }

    public function exchangeTestDataProvider(): array
    {
        return [
            'when exchange is set and option is empty - uses exchange' => [
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

    public function queueTestDataProvider(): array
    {
        return [
            'when queue is set and option is empty - uses queue' => [
                [
                    'queue' => Queue::make(['name' => 'my.topic.queue', 'declare' => false]),
                ],
            ],
            'when queue is null and options is empty - uses default config' => [
                [
                    'expectations' => [
                        'queue.name' => self::QUEUE_NAME,
                    ],
                ],
            ],
            'when queue is null and options has queue config - uses option queue config' => [
                [
                    'options' => [
                        'queue' => [
                            'name' => self::QUEUE_NAME,
                        ],
                    ],

                    'expectations' => [
                        'queue.name' => self::QUEUE_NAME,
                    ],
                ],
            ],
        ];
    }

    public function qosTestDataProvider(): array
    {
        return [
            'when qos is set and option is empty - uses qos' => [
                [
                    'qos' => Qos::make([]),
                ],
            ],
            'when qos is null and options is empty - uses default config with enabled' => [
                [
                    'enabled' => true,
                    'expectations' => [
                        'qos.prefetch_size' => self::QOS_PREFETCH_SIZE,
                    ],
                ],
            ],
            'when qos is null and options is empty - does not use default config with disabled' => [
                [
                    'expectations' => [
                        'qos.prefetch_size' => null,
                    ],
                ],
            ],
            'when qos is null and options has qos config - uses option qos config' => [
                [
                    'options' => [
                        'qos' => [
                            // make sure that it's reading from options, not default config
                            'prefetch_count' => 5,
                        ],
                    ],

                    'expectations' => [
                        'qos.prefetch_count' => 5,
                    ],
                ],
            ],
        ];
    }

    public function queueBindTestDataProvider(): array
    {
        $headers = new AMQPTable(['key' => 'value']);

        return [
            'when bind is passed, should not use config value' => [
                [
                    'options' => [
                        'bind' => [
                            'arguments' => ['application_headers' => $headers],
                        ],
                    ],
                    'expectations' => [
                        'bind.arguments.application_headers' => $headers,
                    ],
                ],
            ],
            'when bind is not passed, should use config value if available' => [
                [
                    'config' => [
                        'bind' => [
                            'arguments' => [
                                'application_headers' => $headers,
                                'content_type' => 'text/plain',
                            ],
                        ],
                    ],
                    'expectations' => [
                        'bind.arguments.application_headers' => $headers,
                        'bind.arguments.content_type' => 'text/plain',
                    ],
                ],
            ],
            'when bind and config both are unavailable' => [
                [
                    'expectations' => [
                        'bind.arguments' => null,
                    ],
                ],
            ],
        ];
    }

    public function consumerConfigTestDataProvider(): array
    {
        return [
            'when consumer option is passed, should not use config value' => [
                [
                    'options' => [
                        'consumer' => [
                            'tag' => 'my.tag',
                        ],
                    ],
                    'expectations' => [
                        'consumer.tag' => 'my.tag',
                    ],
                ],
            ],
            'when consumer option is not passed, should use config value if available' => [
                [
                    'config' => [
                        'consumer' => [
                            'tag' => 'not.my.tag',
                        ],
                    ],
                    'expectations' => [
                        'consumer.tag' => 'not.my.tag',
                    ],
                ],
            ],
            'when consumer option and config both are unavailable' => [
                [
                    'expectations' => [
                        'consumer.tag' => null,
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

    public function testPublishPassesExpectedRoutingKey()
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->anything(),
                'my-routing-key',
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->publish(
            'my message',
            'my-routing-key'
        );
    }

    /**
     * @dataProvider publishOptionsDataProvider
     *
     * @param array $data
     */
    public function testPublishPassesPublishOptionsIfAvailable(array $data)
    {
        $this->bindProducerToApp($producer = $this->getProducerMock($this->connection));

        $expectations = $data['expectations'];

        $producer
            ->expects($this->once())
            ->method('publishBatch')
            ->with(
                $this->anything(),
                'my-routing-key',
                $this->anything(),
                $this->callback(
                    function ($options) use ($expectations) {
                        foreach ($expectations as $key => $value) {
                            if (data_get($options, $key) !== $value) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            );

        $this->getAmqpInstance(null, ['publish' => $data['config']['publish'] ?? []])->publish(
            'my message',
            'my-routing-key',
            null,
            $data['options'] ?? []
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

    public function testConsumeHandlerIsChangesCallableToConsumable()
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->callback(
                    function ($handler) {
                        return $handler instanceof Consumable;
                    }
                ),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->consume(
            function () {
            }
        );
    }

    public function testConsumerPassesExpectedBindingKey()
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                'my-binding-key',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->getAmqpInstance()->consume(
            function () {
            },
            'my-binding-key'
        );
    }

    /**
     * @dataProvider exchangeTestDataProvider
     *
     * @param array $data
     */
    public function testConsumerProcessesExchange(array $data)
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                $this->anything(),
                $data['exchange'] ?? null,
                $this->anything(),
                $this->anything(),
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
             ->consume(
                 function () {
                 },
                 'my-binding-key',
                 $data['exchange'] ?? null,
                 null,
                 null,
                 $data['options'] ?? []
             );
    }

    /**
     * @dataProvider queueTestDataProvider
     *
     * @param array $data
     */
    public function testConsumerProcessesQueue(array $data)
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $data['queue'] ?? null,
                $this->anything(),
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

        $this->getAmqpInstance(null, ['queue' => ['name' => self::QUEUE_NAME,]])
             ->consume(
                 function () {
                 },
                 'my-binding-key',
                 null,
                 $data['queue'] ?? null,
                 null,
                 $data['options'] ?? []
             );
    }

    /**
     * @dataProvider qosTestDataProvider
     *
     * @param array $data
     */
    public function testConsumerProcessesQos(array $data)
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $data['qos'] ?? null,
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

        $this->getAmqpInstance(
            null,
            [
                'qos' => [
                    'enabled' => $data['enabled'] ?? false,
                    'prefetch_size' => self::QOS_PREFETCH_SIZE,
                ],
            ]
        )->consume(
            function () {
            },
            'my-binding-key',
            null,
            null,
            $data['qos'] ?? null,
            $data['options'] ?? []
        );
    }

    /**
     * @dataProvider queueBindTestDataProvider
     *
     * @param array $data
     */
    public function testConsumerProcessesQueueBind(array $data)
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(
                    function ($options) use ($expectation) {
                        foreach ($expectation as $key => $value) {
                            if (data_get($options, $key) !== $value) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            );

        $this->getAmqpInstance(
            null,
            [
                'bind' => $data['config']['bind'] ?? [],
            ]
        )->consume(
            function () {
            },
            'my-binding-key',
            null,
            null,
            null,
            $data['options'] ?? []
        );
    }

    /**
     * @dataProvider consumerConfigTestDataProvider
     *
     * @param array $data
     */
    public function testConsumerProcessesConsumerConfig(array $data)
    {
        $this->bindConsumerToApp($consumer = $this->getConsumerMock($this->connection));

        $expectation = $data['expectations'] ?? [];
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(
                    function ($options) use ($expectation) {
                        foreach ($expectation as $key => $value) {
                            if (data_get($options, $key) !== $value) {
                                return false;
                            }
                        }

                        return true;
                    }
                )
            );

        $this->getAmqpInstance(
            null,
            [
                'consumer' => $data['config']['consumer'] ?? [],
            ]
        )->consume(
            function () {
            },
            'my-binding-key',
            null,
            null,
            null,
            $data['options'] ?? []
        );
    }
}

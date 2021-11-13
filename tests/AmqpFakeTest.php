<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Anik\Laravel\Amqp\AmqpPubSub;
use Anik\Laravel\Amqp\Exceptions\LaravelAmqpException;
use Anik\Laravel\Amqp\Facades\Amqp;

class AmqpFakeTest extends PackageBaseTestCase
{
    protected const EXCHANGE_NAME = 'anik.laravel.amqp.exchange.topic';
    protected const EXCHANGE_TYPE = 'topic';

    protected function setUp(): void
    {
        parent::setUp();

        Amqp::fake();
    }

    public function messagePublishedDataProvider(): array
    {
        $message = 'my message';

        return [
            'check if any message was published' => [
                [
                    'message' => $message,
                ],
            ],
            'check message with scalar type' => [
                [
                    'message' => $message,
                    'expectation' => $message,
                ],
            ],
            'check message with ProducibleMessage::class' => [
                [
                    'message' => new ProducibleMessage($message),
                    'expectation' => ProducibleMessage::class,
                ],
            ],
            'check message with interface Producible::class' => [
                [
                    'message' => new ProducibleMessage($message),
                    'expectation' => Producible::class,
                ],
            ],
        ];
    }

    public function messageNotPublishedDataProvider(): array
    {
        return [
            'check if no message was published' => [[]],
            'check if no message was published with scalar type' => [
                [
                    'message' => 'my message',
                    'expectation' => 'also not my message',
                ],
            ],
            'check if no message was published with class name' => [
                [
                    'message' => new ProducibleMessage('my message'),
                    'expectation' => Amqp::class, // wrong class name make sure it's not there
                ],
            ],
            'check if no message was published with interface name' => [
                [
                    'message' => new ProducibleMessage('my message'),
                    'expectation' => AmqpPubSub::class, // wrong interface name to make sure it's not there
                ],
            ],
        ];
    }

    public function exchangeNameTestDataProvider(): array
    {
        return [
            'use exchange from package config' => [
                [
                    'expectation' => 'amq.direct',
                ],
            ],
            'user provided exchange object' => [
                [
                    'exchange' => Exchange::make(['name' => self::EXCHANGE_NAME, 'type' => self::EXCHANGE_TYPE]),
                    'expectation' => self::EXCHANGE_NAME,
                ],
            ],
            'user provided exchange options' => [
                [
                    'options' => ['exchange' => ['name' => self::EXCHANGE_NAME, 'type' => self::EXCHANGE_TYPE]],
                    'expectation' => self::EXCHANGE_NAME,
                ],
            ],
        ];
    }

    public function exchangeTypeTestDataProvider(): array
    {
        return [
            'use exchange from package config' => [
                [
                    'expectation' => 'direct',
                ],
            ],
            'user provided exchange object' => [
                [
                    'exchange' => Exchange::make(['name' => self::EXCHANGE_NAME, 'type' => self::EXCHANGE_TYPE]),
                    'expectation' => self::EXCHANGE_TYPE,
                ],
            ],
            'user provided exchange options' => [
                [
                    'options' => ['exchange' => ['name' => self::EXCHANGE_NAME, 'type' => self::EXCHANGE_TYPE]],
                    'expectation' => self::EXCHANGE_TYPE,
                ],
            ],
        ];
    }

    /**
     * @dataProvider messagePublishedDataProvider
     *
     * @param array $data
     */
    public function testAssertThatMessagePublished(array $data)
    {
        Amqp::publish($data['message']);
        Amqp::assertPublished($data['expectation'] ?? null);
    }

    /**
     * @dataProvider messageNotPublishedDataProvider
     *
     * @param array $data
     */
    public function testAssertThatMessageWasNotPublished(array $data)
    {
        if ($data['message'] ?? false) {
            Amqp::publish($data['message']);
        }

        Amqp::assertNotPublished($data['expectation'] ?? null);
    }

    /**
     * @dataProvider exchangeNameTestDataProvider
     *
     * @param array $data
     */
    public function testAssertMessagePublishedOnExchange(array $data)
    {
        Amqp::publish('text', '', $data['exchange'] ?? null, $data['options'] ?? []);
        Amqp::assertPublishedOnExchange($data['expectation']);
    }

    /**
     * @dataProvider exchangeTypeTestDataProvider
     *
     * @param array $data
     */
    public function testAssertMessagePublishedOnExchangeType(array $data)
    {
        Amqp::publish('text', '', $data['exchange'] ?? null, $data['options'] ?? []);
        Amqp::assertPublishedOnExchangeType($data['expectation']);
    }

    public function testAssertPublishedWithRoutingKey()
    {
        Amqp::publish('text', 'my.routing.key');
        Amqp::assertPublishedWithRoutingKey('my.routing.key');
    }

    public function testAssertPublishedOnConnection()
    {
        Amqp::connection('new')->publish('text');
        Amqp::assertPublishedOnConnection('new');

        Amqp::publish('text');
        Amqp::assertPublishedOnConnection('rabbitmq');
    }

    public function testCannotConsumeUsingFacadeWhenTestingWithAmqpFake()
    {
        $this->expectException(LaravelAmqpException::class);
        Amqp::consume(
            function () {
            }
        );
    }

    public function testAmqpFacadeBlocksMethodCallForwardingWhenTestingWithAmqpFake()
    {
        $this->expectException(LaravelAmqpException::class);
        $this->expectExceptionMessage('Call to undefined method Anik\Laravel\Amqp\AmqpFake::getProducer');
        Amqp::getProducer();
    }
}

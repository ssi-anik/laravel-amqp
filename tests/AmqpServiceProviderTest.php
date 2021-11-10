<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Laravel\Amqp\AmqpManager;
use Anik\Laravel\Amqp\AmqpPubSub;
use Anik\Laravel\Amqp\Providers\AmqpServiceProvider;
use Orchestra\Testbench\TestCase;

class AmqpServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AmqpServiceProvider::class,
        ];
    }

    public function testFacadeReturnsManager()
    {
        $this->assertInstanceOf(AmqpManager::class, $this->app->make('amqp'));
    }

    public function testPubSubInterfaceReturnsManager()
    {
        $this->assertInstanceOf(AmqpManager::class, $this->app->make(AmqpPubSub::class));
    }
}

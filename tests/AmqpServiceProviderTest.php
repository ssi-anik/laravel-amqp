<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Laravel\Amqp\AmqpManager;
use Anik\Laravel\Amqp\AmqpPubSub;

class AmqpServiceProviderTest extends PackageBaseTestCase
{
    public function testFacadeReturnsManager()
    {
        $this->assertInstanceOf(AmqpManager::class, $this->app->make('amqp'));
    }

    public function testPubSubInterfaceReturnsManager()
    {
        $this->assertInstanceOf(AmqpManager::class, $this->app->make(AmqpPubSub::class));
    }
}

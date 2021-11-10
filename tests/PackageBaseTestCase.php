<?php

namespace Anik\Laravel\Amqp\Test;

use Anik\Laravel\Amqp\Providers\AmqpServiceProvider;
use Orchestra\Testbench\TestCase;

class PackageBaseTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AmqpServiceProvider::class,
        ];
    }
}

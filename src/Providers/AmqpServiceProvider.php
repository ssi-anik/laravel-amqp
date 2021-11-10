<?php

namespace Anik\Laravel\Amqp\Providers;

use Anik\Laravel\Amqp\AmqpManager;
use Anik\Laravel\Amqp\AmqpPubSub;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AmqpServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        $path = realpath(__DIR__ . '/../config/amqp.php');

        if ($this->app->runningInConsole() && (false === $this->isLumen())) {
            $this->publishes(
                [
                    $path => config_path('amqp.php'),
                ]
            );
        }

        $this->mergeConfigFrom($path, 'amqp');
    }

    public function register(): void
    {
        $this->registerManagers();
        $this->registerFacades();
        $this->registerAliases();
    }

    public function registerFacades(): void
    {
        $this->app->bind(
            'amqp',
            function ($app) {
                return app(AmqpManager::class);
            }
        );
    }

    public function registerAliases(): void
    {
        $this->app->alias(AmqpManager::class, AmqpPubSub::class);
    }

    public function registerManagers(): void
    {
        $this->app->singleton(
            AmqpManager::class,
            function ($app) {
                return new AmqpManager($app);
            }
        );
    }

    public function provides(): array
    {
        return ['amqp', AmqpManager::class, AmqpPubSub::class];
    }

    private function isLumen(): bool
    {
        return Str::contains($this->app->version(), 'Lumen');
    }
}

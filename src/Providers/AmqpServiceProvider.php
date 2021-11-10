<?php

namespace Anik\Laravel\Amqp\Providers;

use Anik\Laravel\Amqp\AmqpManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AmqpServiceProvider extends ServiceProvider
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
        $this->app->bind(
            'amqp',
            function ($app) {
                return app(AmqpManager::class);
            }
        );

        $this->app->alias(
            'amqp',
            function ($app) {
                return app(AmqpManager::class);
            }
        );

        $this->app->singleton(
            AmqpManager::class,
            function ($app) {
                return new AmqpManager($app);
            }
        );
    }

    public function provides(): array
    {
        return ['amqp', AmqpManager::class,];
    }

    private function isLumen(): bool
    {
        return Str::contains($this->app->version(), 'Lumen');
    }
}

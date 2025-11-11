<?php

namespace Kyorion\MqBridge\Providers;

use Illuminate\Support\ServiceProvider;
use Kyorion\MqBridge\Console\RabbitMQListen;

class MqBridgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/mq_bridge.php' => config_path('mq_bridge.php'),
        ], 'mq-bridge');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQListen::class
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mq_bridge.php',
            'mq_bridge'
        );
    }
}
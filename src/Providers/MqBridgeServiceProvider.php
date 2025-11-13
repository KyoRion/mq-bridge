<?php

namespace Kyorion\MqBridge\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Kyorion\MqBridge\Console\MakeConsumer;
use Kyorion\MqBridge\Console\MqConsume;
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
                RabbitMQListen::class,
                MakeConsumer::class,
                MqConsume::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mq_bridge.php',
            'mq_bridge'
        );

        $this->loadConsumers();
    }

    protected function loadConsumers(): void
    {
        $consumerPath = app_path('Consumers');

        if (!File::exists($consumerPath)) {
            return;
        }

        $phpFiles = File::allFiles($consumerPath);

        foreach ($phpFiles as $file) {
            require_once $file->getPathname();
        }
    }
}
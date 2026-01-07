<?php

namespace Kyorion\MqBridge\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Kyorion\MqBridge\Console\MakeConsumer;
use Kyorion\MqBridge\Console\MqConsume;
use Kyorion\MqBridge\Console\RabbitMQListen;
use Kyorion\MqBridge\Consumers\ConsumerLifecycle;
use Kyorion\MqBridge\Consumers\DefaultLifecycle;
use Kyorion\MqBridge\Metrics\MetricsExporter;
use Kyorion\MqBridge\Metrics\PrometheusExporter;
use Kyorion\MqBridge\Runtime\ConsumerRuntime;
use Kyorion\MqBridge\Runtime\HeartbeatManager;

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

        $this->loadMetricsRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mq_bridge.php',
            'mq_bridge'
        );

        $this->registerCoreBindings();

        $this->loadConsumers();
    }

    protected function registerCoreBindings(): void
    {
        // Metrics
        $this->app->singleton(MetricsExporter::class, PrometheusExporter::class);

        // Heartbeat
        $this->app->singleton(HeartbeatManager::class, function ($app) {
            return new HeartbeatManager(
                $app->make('cache.store')
            );
        });

        // Lifecycle
        $this->app->singleton(ConsumerLifecycle::class, function ($app) {
            return new DefaultLifecycle(
                $app->make(MetricsExporter::class),
                $app->make(HeartbeatManager::class),
            );
        });


        // Runtime
        $this->app->singleton(ConsumerRuntime::class, function ($app) {
            return new ConsumerRuntime(
                $app->make(ConsumerLifecycle::class)
            );
        });
    }

    /* =========================
     |  METRICS ROUTES
     ========================= */

    protected function loadMetricsRoutes(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (!config('mq_bridge.metrics.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(
            __DIR__ . '/../Http/routes.php'
        );
    }

    /* =========================
     |  AUTO LOAD CONSUMERS
     ========================= */

    protected function loadConsumers(): void
    {
        $consumerPath = app_path('Consumers');

        if (!File::exists($consumerPath)) {
            return;
        }

        foreach (File::allFiles($consumerPath) as $file) {
            require_once $file->getPathname();
        }
    }
}
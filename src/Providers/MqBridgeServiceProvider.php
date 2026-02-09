<?php

namespace Kyorion\MqBridge\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Kyorion\MqBridge\Console\MakeConsumer;
use Kyorion\MqBridge\Console\MqConsume;
use Kyorion\MqBridge\Console\RabbitMQListen;
use Kyorion\MqBridge\Consumers\ConsumerLifecycle;
use Kyorion\MqBridge\Consumers\DefaultLifecycle;
use Kyorion\MqBridge\Consumers\MessageConsumer;
use Kyorion\MqBridge\Metrics\MetricsExporter;
use Kyorion\MqBridge\Metrics\PrometheusExporter;
use Kyorion\MqBridge\Registry\ConsumerRegistry;
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

        // Auto-discover và register consumers SAU khi boot
        $this->discoverAndRegisterConsumers();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mq_bridge.php',
            'mq_bridge'
        );

        $this->app->singleton(ConsumerRegistry::class);

        $this->registerCoreBindings();
    }

    protected function registerCoreBindings(): void
    {
        // Metrics
        $this->app->bind(MetricsExporter::class, function () {
            return new PrometheusExporter();
        });
        
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
     |  AUTO DISCOVER & REGISTER CONSUMERS
     ========================= */

    protected function discoverAndRegisterConsumers(): void
    {
        $consumerPath = app_path('Consumers');

        if (!File::exists($consumerPath)) {
            return;
        }

        $registry = $this->app->make(ConsumerRegistry::class);

        foreach (File::allFiles($consumerPath) as $file) {
            // Require file để class available
            require_once $file->getPathname();

            // Lấy class name từ file
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className)) {
                // Kiểm tra có phải MessageConsumer không
                if (is_subclass_of($className, MessageConsumer::class)) {
                    $registry->register($className);
                }
            }
        }
    }

    /**
     * Lấy fully qualified class name từ file PHP
     */
    protected function getClassNameFromFile(\SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getPathname());

        // Extract namespace
        $namespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $contents, $matches)) {
            $className = trim($matches[1]);
            return $namespace ? "{$namespace}\\{$className}" : $className;
        }

        return null;
    }
}
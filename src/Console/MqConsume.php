<?php

namespace Kyorion\MqBridge\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Kyorion\MqBridge\Consumers\ConsumerLifecycle;
use Kyorion\MqBridge\Consumers\DebugConsumerLifecycle;
use Kyorion\MqBridge\Consumers\MessageConsumer;
use Kyorion\MqBridge\Runtime\ConsumerRuntime;

class MqConsume extends Command
{
    protected $signature = 'mq:consume {consumer : Consumer class name in app/Consumers} {--debug}';
    protected $description = 'Start consuming RabbitMQ messages using a specific consumer class';

    /**
     * @throws \Throwable
     */
    public function handle(): int
    {
        $consumerName = $this->argument('consumer');

        $class = str_contains($consumerName, '\\')
            ? $consumerName
            : "App\\Consumers\\{$consumerName}";

        if (!class_exists($class)) {
            $this->error("❌ Consumer class not found: {$class}");
            return self::FAILURE;
        }

        $consumer = app($class);

        if (!$consumer instanceof MessageConsumer) {
            $this->error("❌ {$class} is not a MessageConsumer");
            return self::FAILURE;
        }

        $this->info("🟢 Starting consumer: {$class}");

        $lifecycle = app(ConsumerLifecycle::class);

        if ($this->option('debug')) {
            $logger = new ConsoleLogger(
                fn ($msg) => $this->line($msg) // 👈 dùng line cho long-running
            );

            $lifecycle = new DebugConsumerLifecycle(
                $lifecycle,
                $logger
            );
        }

        $runtime = new ConsumerRuntime($lifecycle);

        if ($this->option('debug')) {
            $payloadLogger = new DebugPayloadLogger(
                fn ($msg) => $this->line($msg)
            );

            $runtime->enablePayloadDebug($payloadLogger);
        }

        $runtime->run($consumer);

        return self::SUCCESS;
    }
}
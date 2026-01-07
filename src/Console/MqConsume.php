<?php

namespace Kyorion\MqBridge\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MqConsume extends Command
{
    protected $signature = 'mq:consume {consumer : Consumer class name in app/Consumers} {--debug}';
    protected $description = 'Start consuming RabbitMQ messages using a specific consumer class';

    public function handle()
    {
        $consumerName = $this->argument('consumer');

        $class = "App\\Consumers\\{$consumerName}";

        if (!class_exists($class)) {
            $this->error("❌ Consumer class not found: {$class}");
            return;
        }

        $consumer = app($class);

        $this->info("🟢 Starting consumer: {$class}");

        if ($this->option('debug')) {
            $consumer->setConsoleLogger( fn ($msg) => $this->info($msg));
        }

        $consumer->listen();
    }
}
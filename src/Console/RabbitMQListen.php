<?php

namespace Kyorion\MqBridge\Console;

use Illuminate\Console\Command;
use Kyorion\MqBridge\Consumers\ConsumerLifecycle;
use Kyorion\MqBridge\Consumers\DebugConsumerLifecycle;
use Kyorion\MqBridge\Registry\ConsumerRegistry;
use Kyorion\MqBridge\Runtime\ConsumerRuntime;

class RabbitMQListen extends Command
{
    protected $signature = 'mq:listen 
                            {--debug : Enable debug mode with payload logging}
                            {--list : List all registered consumers without starting}';
                            
    protected $description = 'Start all registered MQ consumers';

    public function handle(): int
    {
        $registry = app(ConsumerRegistry::class);
        $consumers = $registry->all();

        // Nếu chỉ muốn list consumers
        if ($this->option('list')) {
            return $this->listConsumers($consumers);
        }

        // Kiểm tra có consumer nào không
        if (empty($consumers)) {
            $this->warn('⚠️  No consumers registered.');
            $this->info('💡 Create a consumer with: php artisan mq:make-consumer {name}');
            $this->info('💡 Consumers in app/Consumers will be auto-discovered.');
            return self::SUCCESS;
        }

        // Tạo instances
        $instances = collect($consumers)
            ->map(fn ($consumerClass) => app($consumerClass))
            ->all();

        // Hiển thị danh sách consumers sẽ được start
        $this->displayStartupInfo($consumers);

        // Setup lifecycle
        $lifecycle = app(ConsumerLifecycle::class);

        if ($this->option('debug')) {
            $logger = new ConsoleLogger(
                fn ($msg) => $this->line($msg)
            );

            $lifecycle = new DebugConsumerLifecycle(
                $lifecycle,
                $logger
            );
        }

        // Create runtime và start
        $runtime = new ConsumerRuntime($lifecycle);

        if ($this->option('debug')) {
            $payloadLogger = new DebugPayloadLogger(
                fn ($msg) => $this->line($msg)
            );

            $runtime->enablePayloadDebug($payloadLogger);
        }

        $this->info('🚀 Starting all consumers... Press Ctrl+C to stop.');
        $this->newLine();

        $runtime->runMultiple($instances);

        return self::SUCCESS;
    }

    /**
     * Hiển thị danh sách consumers
     */
    protected function listConsumers(array $consumers): int
    {
        if (empty($consumers)) {
            $this->warn('⚠️  No consumers registered.');
            return self::SUCCESS;
        }

        $this->info('📋 Registered Consumers:');
        $this->newLine();

        $tableData = [];
        foreach ($consumers as $consumerClass) {
            try {
                $tableData[] = [
                    'class' => $consumerClass,
                    'service' => $consumerClass::service(),
                    'name' => $consumerClass::name(),
                    'queue' => $consumerClass::queue(),
                    'bindings' => implode(', ', $consumerClass::bindings()),
                ];
            } catch (\Throwable $e) {
                $tableData[] = [
                    'class' => $consumerClass,
                    'service' => '❌ Error',
                    'name' => $e->getMessage(),
                    'queue' => '-',
                    'bindings' => '-',
                ];
            }
        }

        $this->table(
            ['Class', 'Service', 'Name', 'Queue', 'Bindings'],
            $tableData
        );

        return self::SUCCESS;
    }

    /**
     * Hiển thị thông tin startup
     */
    protected function displayStartupInfo(array $consumers): void
    {
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║           🔗 MQ-Bridge Multi Consumer Listener          ║');
        $this->info('╠════════════════════════════════════════════════════════╣');
        
        foreach ($consumers as $index => $consumerClass) {
            try {
                $name = $consumerClass::name();
                $queue = $consumerClass::queue();
                $this->info("║  ✓ [{$name}] → {$queue}");
            } catch (\Throwable $e) {
                $this->error("║  ✗ {$consumerClass} → Error: {$e->getMessage()}");
            }
        }
        
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('debug')) {
            $this->warn('🐛 Debug mode: ON');
        }
    }
}
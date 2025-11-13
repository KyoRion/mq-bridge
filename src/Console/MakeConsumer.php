<?php

namespace Kyorion\MqBridge\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeConsumer extends Command
{
    protected $signature = 'mq:make-consumer {name : Consumer class name}';

    protected $description = 'Generate a RabbitMQ Consumer class';

    public function handle(): void
    {
        $name = Str::studly($this->argument('name'));

        $consumerClass = "{$name}Consumer";
        $namespace = "App\\Consumers";
        $directory = app_path('Consumers');
        $path = "{$directory}/{$consumerClass}.php";

        // Ensure directory exists
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($path)) {
            $this->error("❌ Consumer already exists: {$consumerClass}");
            return;
        }

        // Load stub
        $stub = file_get_contents(__DIR__ . '/stubs/consumer.stub');

        // Replace variables
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $consumerClass],
            $stub
        );

        // Write file
        File::put($path, $content);

        $this->info("✅ Consumer created: {$path}");
    }

    private function formatArray(array $data): string
    {
        // Pretty PHP array export
        return var_export($data, true);
    }
}
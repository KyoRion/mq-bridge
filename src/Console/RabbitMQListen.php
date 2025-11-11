<?php

namespace Kyorion\MqBridge\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kyorion\MqBridge\Helpers\RabbitMQHelper;

class RabbitMQListen extends Command
{
    protected $signature = 'mq:listen {queues? : Comma-separated list of queues, e.g. "orders,payments"}';
    protected $description = 'Listen to one or multiple RabbitMQ queues and dispatch events';


    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        $queuesArg = $this->argument('queues');

        $queues = $queuesArg
            ? array_map('trim', explode(',', $queuesArg))
            : explode(',', ''); // default from .env if not passed

        // 3️⃣ Log what’s being listened to
        $this->info('Listening on queues: ' . implode(', ', $queues));

        // 4️⃣ Call your helper
        RabbitMQHelper::listen($queues);
    }
}

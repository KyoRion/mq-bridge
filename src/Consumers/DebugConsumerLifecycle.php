<?php

namespace Kyorion\MqBridge\Consumers;

use Kyorion\MqBridge\Console\ConsoleLogger;
use Kyorion\MqBridge\Metadata\ConsumerMetadata;
use Throwable;

class DebugConsumerLifecycle implements ConsumerLifecycle
{
    public function __construct(
        private ConsumerLifecycle $inner,
        private ConsoleLogger $logger
    ) {}

    public function onStart(ConsumerMetadata $meta): void
    {
        $this->logger->log('🟢 Consumer started', [
            'service' => $meta->service,
            'consumer' => $meta->consumer,
            'queue' => $meta->queue,
            'pid' => $meta->pid,
        ]);

        $this->inner->onStart($meta);
    }

    public function onMessage(ConsumerMetadata $meta): void
    {
        $this->logger->log('📥 Message received', [
            'consumer' => $meta->consumer,
        ]);

        $this->inner->onMessage($meta);
    }

    public function onError(ConsumerMetadata $meta, \Throwable $e): void
    {
        $this->logger->log('❌ Consumer error', [
            'consumer' => $meta->consumer,
            'error' => $e->getMessage(),
        ]);

        $this->inner->onError($meta, $e);
    }

    public function onStop(ConsumerMetadata $meta): void
    {
        $this->logger->log('🔴 Consumer stopped', [
            'consumer' => $meta->consumer,
        ]);

        $this->inner->onStop($meta);
    }
}
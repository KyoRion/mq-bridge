<?php

namespace Kyorion\MqBridge\Consumers;

use Kyorion\MqBridge\Metadata\ConsumerMetadata;
use Kyorion\MqBridge\Metrics\MetricsExporter;
use Kyorion\MqBridge\Runtime\HeartbeatManager;

class DefaultLifecycle implements ConsumerLifecycle
{
    public function __construct(
        private MetricsExporter $metrics,
        private HeartbeatManager $heartbeat
    ) {}

    public function onStart(ConsumerMetadata $meta): void
    {
        $this->metrics->consumerUp($meta, true);
        $this->metrics->heartbeat($meta);

        $this->heartbeat->beat($meta);
    }

    public function onMessage(ConsumerMetadata $meta): void
    {
        $this->metrics->incMessages($meta);
        $this->metrics->heartbeat($meta);

        $this->heartbeat->beat($meta);
    }

    public function onError(ConsumerMetadata $meta, Throwable $e): void
    {
        $this->metrics->incErrors($meta);

        // vẫn beat để phân biệt "lỗi nhưng còn sống"
        $this->heartbeat->beat($meta);
    }

    public function onStop(ConsumerMetadata $meta): void
    {
        $this->metrics->consumerUp($meta, false);
    }
}
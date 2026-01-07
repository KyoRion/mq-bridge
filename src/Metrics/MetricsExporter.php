<?php

namespace Kyorion\MqBridge\Metrics;

use Kyorion\MqBridge\Metadata\ConsumerMetadata;

interface MetricsExporter
{
    public function consumerUp(ConsumerMetadata $meta, bool $up): void;

    public function incMessages(ConsumerMetadata $meta): void;

    public function incErrors(ConsumerMetadata $meta): void;

    public function heartbeat(ConsumerMetadata $meta): void;
}
<?php

namespace Kyorion\MqBridge\Consumers;

use Kyorion\MqBridge\Metadata\ConsumerMetadata;

interface ConsumerLifecycle
{
    public function onStart(ConsumerMetadata $meta): void;
    public function onMessage(ConsumerMetadata $meta): void;
    public function onError(ConsumerMetadata $meta, \Throwable $e): void;
    public function onStop(ConsumerMetadata $meta): void;
}
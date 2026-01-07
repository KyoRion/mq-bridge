<?php

namespace Kyorion\MqBridge\Metadata;

use Kyorion\MqBridge\Consumers\MessageConsumer;

final class ConsumerMetadata
{
    public function __construct(
        public readonly string $service,
        public readonly string $consumer,
        public readonly string $queue,
        public readonly array  $routingKeys,
        public readonly string $host,
        public readonly int    $pid,
    ) {}

    public static function from(MessageConsumer $consumer): self
    {
        return new self(
            service: $consumer::service(),
            consumer: $consumer::name(),
            queue: $consumer::queue(),
            routingKeys: $consumer::bindings(),
            host: gethostname(),
            pid: getmypid(),
        );
    }
}
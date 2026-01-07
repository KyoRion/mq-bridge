<?php

namespace Kyorion\MqBridge\Metadata;

use Kyorion\MqBridge\Consumers\MessageConsumer;

class MetadataResolver
{
    public static function resolve(MessageConsumer $consumer)
    {
        return new ConsumerMetadata(
            service: $consumer::service(),
            consumer: $consumer::name(),
            queue: $consumer::queue(),
            routingKeys: $consumer::bindings(),
            host: gethostname(),
            pid: getmypid(),
        );
    }
}
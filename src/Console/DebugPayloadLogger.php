<?php

namespace Kyorion\MqBridge\Console;

final class DebugPayloadLogger
{
    public function __construct(
        private \Closure $writer
    ) {}

    public function log(array $payload): void
    {
        ($this->writer)(
            '📦 Payload: ' . json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );
    }
}
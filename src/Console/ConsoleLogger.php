<?php

namespace Kyorion\MqBridge\Console;

final class ConsoleLogger
{
    public function __construct(
        private \Closure $writer
    ) {}

    public function log(string $message, array $context = []): void
    {
        ($this->writer)(
            $context
                ? $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE)
                : $message
        );
    }
}
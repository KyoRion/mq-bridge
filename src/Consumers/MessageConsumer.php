<?php

namespace Kyorion\MqBridge\Consumers;

use Kyorion\MqBridge\Runtime\ConsumerRuntime;

abstract class MessageConsumer
{
    /* =========================
     |  REQUIRED METADATA
     ========================= */

    abstract public static function service(): string;

    abstract public static function name(): string;

    /* =========================
     |  RABBITMQ DEFINITION
     ========================= */

    abstract public static function exchanges(): array;

    abstract public static function queue(): string;

    abstract public static function bindings(): array;

    /* =========================
     |  BUSINESS HANDLER
     ========================= */

    abstract public function handle(array $payload): void;

    /* =========================
     |  RUNTIME ENTRYPOINT
     ========================= */

    /**
     * @throws \Throwable
     */
    final public function start(): void
    {
        app(ConsumerRuntime::class)->run($this);
    }
}
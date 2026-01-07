<?php

namespace Kyorion\MqBridge\Metrics;

use Kyorion\MqBridge\Metadata\ConsumerMetadata;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Storage\Redis;

final class PrometheusExporter implements MetricsExporter
{
    private CollectorRegistry $registry;

    private Gauge $consumerUp;
    private Counter $messagesTotal;
    private Counter $errorsTotal;
    private Gauge $lastHeartbeat;

    public function __construct(?CollectorRegistry $registry = null)
    {
        $this->registry = new CollectorRegistry(
            $this->createStorage()
        );

        $this->registerMetrics();
    }

    private function createStorage()
    {
        $driver = config('mq_bridge.prometheus.storage', 'redis');

        if ($driver !== 'redis') {
            throw new \RuntimeException(
                'mq-bridge only supports Redis prometheus storage'
            );
        }

        return new Redis([
            'host'     => config('database.redis.default.host', '127.0.0.1'),
            'port'     => config('database.redis.default.port', 6379),
            'password' => config('database.redis.default.password', null),
            'database' => config('mq_bridge.prometheus.redis_database', 1),
            'timeout'  => 0.1,
        ]);
    }

    private function registerMetrics(): void
    {
        $labels = ['service', 'consumer', 'queue', 'routing_keys'];

        $this->consumerUp = $this->registry->getOrRegisterGauge(
            'mq_bridge',
            'consumer_up',
            'Consumer process up (1) or down (0)',
            $labels
        );

        $this->messagesTotal = $this->registry->getOrRegisterCounter(
            'mq_bridge',
            'consumer_messages_total',
            'Total messages processed by consumer',
            $labels
        );

        $this->errorsTotal = $this->registry->getOrRegisterCounter(
            'mq_bridge',
            'consumer_errors_total',
            'Total errors thrown by consumer',
            $labels
        );

        $this->lastHeartbeat = $this->registry->getOrRegisterGauge(
            'mq_bridge',
            'consumer_last_heartbeat_timestamp',
            'Last heartbeat unix timestamp',
            $labels
        );
    }

    /* =========================
     |  Public API
     ========================= */

    public function consumerUp(ConsumerMetadata $meta, bool $up): void
    {
        $this->consumerUp->set(
            $up ? 1 : 0,
            $this->labels($meta)
        );
    }

    public function incMessages(ConsumerMetadata $meta): void
    {
        $this->messagesTotal->inc(
            $this->labels($meta)
        );
    }

    public function incErrors(ConsumerMetadata $meta): void
    {
        $this->errorsTotal->inc(
            $this->labels($meta)
        );
    }

    public function heartbeat(ConsumerMetadata $meta): void
    {
        $this->lastHeartbeat->set(
            time(),
            $this->labels($meta)
        );
    }

    /* =========================
     |  Helpers
     ========================= */

    private function labels(ConsumerMetadata $meta): array
    {
        return [
            $meta->service,
            $meta->consumer,
            $meta->queue,
            $this->normalizeRoutingKeys($meta->routingKeys),
        ];
    }

    private function normalizeRoutingKeys(array $keys): string
    {
        sort($keys);

        return sha1(implode('|', $keys));
    }

    public function registry(): CollectorRegistry
    {
        return $this->registry;
    }
}
<?php

namespace Kyorion\MqBridge\Runtime;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Kyorion\MqBridge\Metadata\ConsumerMetadata;

class HeartbeatManager
{
    public function __construct(
        private CacheRepository $cache
    ) {}

    public function beat(ConsumerMetadata $meta): void
    {
        $key = $this->key($meta);

        $this->cache->put(
            $key,
            time(),
            now()->addSeconds($this->ttl())
        );
    }

    public function lastBeat(ConsumerMetadata $meta): ?int
    {
        return $this->cache->get(
            $this->key($meta)
        );
    }

    public function isAlive(ConsumerMetadata $meta): bool
    {
        return $this->cache->has(
            $this->key($meta)
        );
    }

    private function key(ConsumerMetadata $meta): string
    {
        return sprintf(
            'mq:consumer:heartbeat:%s:%s:%d',
            $meta->service,
            $meta->consumer,
            $meta->pid
        );
    }

    private function ttl(): int
    {
        return config('mq_bridge.heartbeat.ttl', 60);
    }
}
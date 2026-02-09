<?php

namespace Kyorion\MqBridge\Registry;

class ConsumerRegistry
{
    protected array $consumers = [];

    public function register(string $consumerClass): void
    {
        if (!in_array($consumerClass, $this->consumers, true)) {
            $this->consumers[] = $consumerClass;
        }
    }

    /**
     * Đăng ký nhiều consumer classes
     */
    public function registerMany(array $consumerClasses): void
    {
        foreach ($consumerClasses as $consumerClass) {
            $this->register($consumerClass);
        }
    }

    /**
     * Lấy tất cả consumers đã đăng ký
     */
    public function all(): array
    {
        return $this->consumers;
    }
    /**
     * Kiểm tra consumer đã được đăng ký chưa
     */
    public function has(string $consumerClass): bool
    {
        return in_array($consumerClass, $this->consumers, true);
    }
    /**
     * Đếm số lượng consumers
     */
    public function count(): int
    {
        return count($this->consumers);
    }
    /**
     * Xóa một consumer khỏi registry
     */
    public function unregister(string $consumerClass): void
    {
        $this->consumers = array_values(
            array_filter($this->consumers, fn ($c) => $c !== $consumerClass)
        );
    }
    /**
     * Xóa tất cả consumers
     */
    public function clear(): void
    {
        $this->consumers = [];
    }
}
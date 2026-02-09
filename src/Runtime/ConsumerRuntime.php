<?php

namespace Kyorion\MqBridge\Runtime;

use Illuminate\Support\Facades\Config;
use Kyorion\MqBridge\Console\DebugPayloadLogger;
use Kyorion\MqBridge\Consumers\ConsumerLifecycle;
use Kyorion\MqBridge\Consumers\MessageConsumer;
use Kyorion\MqBridge\Metadata\ConsumerMetadata;
use Kyorion\MqBridge\Metadata\MetadataResolver;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

final class ConsumerRuntime
{
    private ?DebugPayloadLogger $payloadLogger = null;

    public function __construct(
        private ConsumerLifecycle $lifecycle
    ) {}

    public function runMultiple(array $consumers)
    {
        while (true)
        {
            try {
                $this->boot($consumers);
            } catch (Throwable $e) {
                logger()->error('MQ runtime crashed: ' . $e->getMessage());
                sleep(5);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function boot(array $consumers): void
    {
        $connection = new AMQPStreamConnection(
            Config::get('mq_bridge.connection.host'),
            Config::get('mq_bridge.connection.port'),
            Config::get('mq_bridge.connection.user'),
            Config::get('mq_bridge.connection.password'),
            Config::get('mq_bridge.connection.vhost'),
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            3.0,
            null,
            true,
            60 // 🔥 heartbeat
        );

        $channel = $connection->channel();

        // 🔥 QoS (very important)
        $channel->basic_qos(null, 10, null);

        foreach ($consumers as $consumer) {
            $this->registerConsumer($channel, $consumer);
        }

        // Graceful shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => exit(0));
        pcntl_signal(SIGINT, fn () => exit(0));

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    private function registerConsumer($channel, MessageConsumer $consumer): void
    {
        $meta = ConsumerMetadata::from($consumer);

        $this->lifecycle->onStart($meta);

        $channel->queue_declare(
            $consumer::queue(),
            false,
            true,
            false,
            false
        );

        foreach ($consumer::exchanges() as $ex) {
            $channel->exchange_declare(
                $ex['name'],
                $ex['type'],
                false,
                true,
                false
            );

            foreach ($consumer::bindings() as $routingKey) {
                $channel->queue_bind(
                    $consumer::queue(),
                    $ex['name'],
                    $routingKey
                );
            }
        }

        $channel->basic_consume(
            $consumer::queue(),
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($consumer, $meta) {

                $payload = json_decode($msg->body, true);

                if ($this->payloadLogger) {
                    $this->payloadLogger->log($payload);
                }

                try {
                    $this->lifecycle->onMessage($meta);

                    $consumer->handle($payload);

                    $msg->ack();

                } catch (Throwable $e) {

                    $this->lifecycle->onError($meta, $e);

                    $msg->nack(false, false);

                    logger()->error($e);
                }
            }
        );
    }

    public function enablePayloadDebug(DebugPayloadLogger $logger): void
    {
        $this->payloadLogger = $logger;
    }

    /**
     * @throws Throwable
     */
    public function run(MessageConsumer $consumer): void
    {
        $meta = ConsumerMetadata::from($consumer);

        $this->lifecycle->onStart($meta);

        try {
            $this->listen($consumer, $meta);
        } catch (\Throwable $e) {
            $this->lifecycle->onError($meta, $e);
            throw $e;
        } finally {
            $this->lifecycle->onStop($meta);
        }
    }

    /**
     * @throws \Exception
     */
    private function listen(MessageConsumer $consumer, ConsumerMetadata $meta): void
    {
        $connection = new AMQPStreamConnection(
            Config::get('mq_bridge.connection.host'),
            Config::get('mq_bridge.connection.port'),
            Config::get('mq_bridge.connection.user'),
            Config::get('mq_bridge.connection.password'),
            Config::get('mq_bridge.connection.vhost')
        );

        $channel = $connection->channel();

        // 1️⃣ Declare queue
        $channel->queue_declare(
            $consumer::queue(),
            false,
            true,
            false,
            false
        );

        // 2️⃣ Declare exchanges + bindings
        foreach ($consumer::exchanges() as $ex) {
            $channel->exchange_declare(
                $ex['name'],
                $ex['type'],
                false,
                true,
                false
            );

            foreach ($consumer::bindings() as $routingKey) {
                $channel->queue_bind(
                    $consumer::queue(),
                    $ex['name'],
                    $routingKey
                );
            }
        }

        // 3️⃣ Consume
        $channel->basic_consume(
            $consumer::queue(),
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($consumer, $meta) {

                $payload = json_decode($msg->body, true);

                if ($this->payloadLogger) {
                    $this->payloadLogger->log($payload);
                }

                try {
                    // 🔥 runtime hook BEFORE business
                    $this->lifecycle->onMessage($meta);

                    // 🎯 business
                    $consumer->handle($payload);

                    // ✅ ACK only if success
                    $msg->ack();
                } catch (\Throwable $e) {
                    // 🚨 runtime error hook
                    $this->lifecycle->onError($meta, $e);

                    // ❌ no ack → requeue
                    throw $e;
                }
            }
        );

        // 4️⃣ Graceful shutdown
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => exit(0));
        pcntl_signal(SIGINT, fn () => exit(0));

        // 5️⃣ Loop
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
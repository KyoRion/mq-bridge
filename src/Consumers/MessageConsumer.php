<?php

namespace Kyorion\MqBridge\Consumers;

use Illuminate\Support\Facades\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class MessageConsumer
{
    abstract public static function exchanges(): array;

    abstract public static function queue(): string;

    abstract public static function bindings(): array;

    abstract public function handle(array $payload): void;

    protected $consoleLogger = null;

    public function setConsoleLogger(callable $logger): void
    {
        $this->consoleLogger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function listen(): void
    {
        $connection = new AMQPStreamConnection(
            Config::get('mq_bridge.connection.host'),
            Config::get('mq_bridge.connection.port'),
            Config::get('mq_bridge.connection.user'),
            Config::get('mq_bridge.connection.password'),
            Config::get('mq_bridge.connection.vhost')
        );

        $channel = $connection->channel();

        // 1) Declare queue first
        $channel->queue_declare(
            static::queue(),
            false,
            true,
            false,
            false
        );

        // 2) Bind queue to multiple exchanges
        foreach (static::exchanges() as $ex) {

            $channel->exchange_declare(
                $ex['name'],
                $ex['type'],
                false,
                true,
                false
            );

            foreach (static::bindings() as $routingKey) {
                $channel->queue_bind(
                    static::queue(),
                    $ex['name'],
                    $routingKey
                );
            }
        }

        // 3) Consume queue
        $channel->basic_consume(
            static::queue(),
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) {
                $payload = json_decode($msg->body, true);

                if ($this->consoleLogger) {
                    ($this->consoleLogger)(
                        "📥 Received MQ message on queue [" . static::queue() . "]"
                    );
                    ($this->consoleLogger)(json_encode($payload));
                }

                $this->handle($payload);

                if ($this->consoleLogger) {
                    ($this->consoleLogger)(
                        "✅ Message processed successfully."
                    );
                }

                $msg->ack();
            }
        );

        echo "Listening on queue: " . static::queue() . "\n";

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
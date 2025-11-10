<?php

namespace Kyorion\MqBridge\Publishers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessagePublisher
{
    public static function publish(string $service, string $event, array $payload, ?array $user = null): void
    {
        $config = Config::get("mq_bridge.services.$service");

        if (!$config) {
            Log::error("MQ Service [$service] not found in config.");
            return;
        }

        $exchange = $config['exchange'];
        $routingKey = $config['routing_key'];

        $meta = [
            'event' => $event,
            'origin' => Config::get('app.name'),
            'timestamp' => Carbon::now()->timestamp,
        ];

        $message = [
            'meta' => $meta,
            'payload' => $payload,
            'user' => $user,
        ];

        $message['signature'] = hash_hmac(
            'sha256',
            json_encode($message),
            Config::get('mq_bridge.hmac_secret')
        );

        try {
            $connection = new AMQPStreamConnection(
                Config::get('mq_bridge.connection.host'),
                Config::get('mq_bridge.connection.port'),
                Config::get('mq_bridge.connection.user'),
                Config::get('mq_bridge.connection.password'),
                Config::get('mq_bridge.connection.vhost')
            );

            $channel = $connection->channel();
            $channel->exchange_declare($exchange, 'direct', false, true, false);

            $msg = new AMQPMessage(json_encode($message));
            $channel->basic_publish($msg, $exchange, $routingKey);

            Log::info("📤 Published [$event] to [$service]", [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
            ]);

            $channel->close();
            $connection->close();
        } catch (\Throwable $e) {
            Log::error("❌ MQ Publish failed", [
                'service' => $service,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
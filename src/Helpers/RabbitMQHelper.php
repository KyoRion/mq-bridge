<?php

namespace Kyorion\MqBridge\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQHelper
{
    /**
     * @throws \Exception
     */
    protected static function connection(): AMQPStreamConnection
    {
        $host = Config::get('mq_bridge.connection.host');
        $port = Config::get('mq_bridge.connection.port');
        $user = Config::get('mq_bridge.connection.user');
        $password = Config::get('mq_bridge.connection.password');
        $vhost = Config::get('mq_bridge.connection.vhost', '/');

        return new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $password,
            $vhost
        );
    }

    /**
     * @throws \Exception
     */
    public static function publish(string $queue, array $data, string $exchange = ''): void
    {
        $connection = self::connection();
        $channel = $connection->channel();

        $channel->queue_declare($queue, false, true, false, false);

        $msg = new AMQPMessage(json_encode($data), [
            'content_type' => 'application/json',
            'delivery_mode' => 2, // persistent
        ]);

        if ($exchange) {
            $channel->basic_publish($msg, $exchange);
        } else {
            $channel->basic_publish($msg, '', $queue);
        }

        $channel->close();
        $connection->close();
    }

    /**
     * @throws \Exception
     */
    public static function listen(array $queues): void
    {
        $connection = self::connection();
        $channel = $connection->channel();

        foreach ($queues as $queue) {
            $channel->queue_declare($queue, false, true, false, false);

            $channel->basic_consume($queue, '', false, true, false, false, function ($msg) use ($queue) {
                $payload = json_decode($msg->body, true);
                if (!$payload) {
                    Log::warning("Invalid message on queue {$queue}: " . $msg->body);
                    return;
                }

                $type = $payload['type'] ?? null;
                $data = $payload['data'] ?? [];

                if ($type) {
                    $eventClass = self::resolveEventClass($type);
                    if (class_exists($eventClass)) {
                        Log::info("Dispatching event {$eventClass} from queue {$queue}");
                        Event::dispatch(new $eventClass($data));
                    } else {
                        Log::warning("No event class found for type '{$type}' on queue {$queue}");
                    }
                }
            });
        }

        echo " [*] Listening on queues: " . implode(', ', $queues) . "\n";

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    protected static function resolveEventClass(string $type): string
    {
        // Example: "order.created" → App\Events\OrderCreatedEvent
        $parts = explode('.', $type);
        $className = collect($parts)
                ->map(fn($p) => ucfirst($p))
                ->implode('') . 'Event';

        return "App\\Events\\{$className}";
    }
}
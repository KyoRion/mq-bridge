<?php

namespace Kyorion\MqBridge\Publishers;

use Illuminate\Support\Facades\Config;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessagePublisher
{
    /**
     * @throws \Exception
     */
    protected static function connection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            Config::get('mq_bridge.connection.host'),
            Config::get('mq_bridge.connection.port'),
            Config::get('mq_bridge.connection.user'),
            Config::get('mq_bridge.connection.password'),
            Config::get('mq_bridge.connection.vhost', '/')
        );
    }

    /**
     * Publish message to RabbitMQ
     *
     * @param string $exchange Exchange name
     * @param string $routingKey Routing key (or queue name for default)
     * @param array $data Payload
     * @param string $exchangeType direct|topic|fanout|headers|default
     * @param array $headers Custom headers for headers exchange
     *
     * @throws \Exception
     */
    public static function publish(
        string $exchange,
        string $routingKey,
        array $data,
        string $exchangeType = 'default',
        array $headers = []
    ): void {
        $connection = self::connection();
        $channel = $connection->channel();

        // Create message
        $properties = [
            'content_type'  => 'application/json',
            'delivery_mode' => 2,
        ];

        // Add headers (for headers exchange)
        if ($exchangeType === 'headers' && !empty($headers)) {
            $properties['application_headers'] = new \PhpAmqpLib\Wire\AMQPTable($headers);
        }

        $msg = new AMQPMessage(json_encode($data), $properties);

        // Declare exchange except default exchange
        if ($exchangeType !== 'default') {
            $channel->exchange_declare(
                $exchange,
                $exchangeType,
                false,  // passive
                true,   // durable
                false   // auto_delete
            );
        }

        // Default exchange publish (routing to queue directly)
        if ($exchangeType === 'default') {
            $channel->queue_declare($routingKey, false, true, false, false);
            $channel->basic_publish($msg, '', $routingKey);
        } else {
            $channel->basic_publish($msg, $exchange, $routingKey);
        }

        $channel->close();
        $connection->close();
    }
}
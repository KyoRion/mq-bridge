<?php

namespace Kyorion\MqBridge\Publishers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessagePublisher
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
        $msg = new AMQPMessage(json_encode($data), ['content_type' => 'application/json', 'delivery_mode' => 2]);

        $channel->basic_publish($msg, '', $queue);

        $channel->close();
        $connection->close();
    }
}
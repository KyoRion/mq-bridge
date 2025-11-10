<?php

namespace Kyorion\MqBridge\Subscribers;

use Illuminate\Support\Facades\Log;
use Kyorion\MqBridge\Security\MessageVerifier;

class MessageSubscriber
{
    public static function handle(array $message, \Closure $callback): void
    {
        try {
            $verified = MessageVerifier::verify($message);

            $payload = $verified['payload'];
            $meta = $verified['meta'];
            $user = $verified['user'];

            $callback($payload, $meta, $user);
        } catch (\Throwable $e) {
            Log::error('❌ Message verification failed', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }
}
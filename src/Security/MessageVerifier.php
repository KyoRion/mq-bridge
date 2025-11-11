<?php

namespace Kyorion\MqBridge\Security;

use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MessageVerifier
{
    /**
     * @throws Exception
     */
    public static function verify(array $message): array
    {
        $provided = $message['signature'] ?? null;
        unset($message['signature']);

        $expected = hash_hmac(
            'sha256',
            json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            Config::get('mq_bridge.hmac_secret')
        );

        if ($provided !== $expected) {
            throw new \RuntimeException('Invalid message signature');
        }

        return $message;
    }
}
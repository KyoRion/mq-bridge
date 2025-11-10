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
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode([
                'meta' => $message['meta'],
                'payload' => $message['payload'],
                'user' => $message['user'] ?? [],
            ]),
            Config::get('mq_bridge.hmac_secret')
        );

        if (!hash_equals($expectedSignature, $message['signature'] ?? '')) {
            throw new Exception('Invalid message signature');
        }

        $user = $message['user'] ?? [];
        $decodedJwt = null;

        if (!empty($user['jwt'])) {
            try {
                $decodedJwt = JWT::decode(
                    $user['jwt'],
                    new Key(Config::get('mq_bridge.jwt_secret'), 'HS256')
                );
            } catch (ExpiredException $e) {
                Log::warning('⚠️ Expired JWT accepted (soft verify)', [
                    'user_id' => $user['id'] ?? null,
                ]);
                $decodedJwt = JWT::jsonDecode(
                    JWT::urlsafeB64Decode(explode('.', $user['jwt'])[1])
                );
            }
        }

        return [
            'payload' => $message['payload'],
            'meta' => $message['meta'],
            'user' => array_merge($user, ['decoded' => (array) $decodedJwt]),
        ];
    }
}
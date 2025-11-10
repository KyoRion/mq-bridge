<?php

namespace Kyorion\MqBridge\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;

class JwtHelper
{
    public static function generate(array $claims, int $ttlSeconds = 300): string
    {
        $claims = array_merge($claims, [
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
        ]);

        return JWT::encode($claims, Config::get('mq_bridge.jwt_secret'), 'HS256');
    }
}
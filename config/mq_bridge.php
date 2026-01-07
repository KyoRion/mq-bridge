<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ connection
    |--------------------------------------------------------------------------
    */
    'connection' => [
        'host' => env('MQ_HOST', 'rabbitmq'),
        'port' => env('MQ_PORT', 5672),
        'user' => env('MQ_USER', 'guest'),
        'password' => env('MQ_PASSWORD', 'guest'),
        'vhost' => env('MQ_VHOST', '/'),
    ],

    'metrics' => [
        'enabled' => env('MQ_BRIDGE_METRICS_ENABLED', false),

        'path' => env('MQ_BRIDGE_METRICS_PATH', '/metrics'),

        'middleware' => [
            // 'auth.basic'
        ],
    ],

    'heartbeat' => [
        // TTL cho heartbeat key (giây)
        'ttl' => env('MQ_BRIDGE_HEARTBEAT_TTL', 60),
    ],


    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'hmac_secret' => env('MQ_HMAC_SECRET', 'changeme'),
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme'),

    /*
    |--------------------------------------------------------------------------
    | Service definitions
    |--------------------------------------------------------------------------
    | Định nghĩa danh sách các service đích mà core-api có thể gửi message tới
    */
    'services' => [
        'demo' => [
            'exchange' => 'demo.exchange',
            'routing_key' => 'demo.key',
            //'subscriber' => \App\Subscribers\DemoSubscriber::class,
        ]
    ]
];
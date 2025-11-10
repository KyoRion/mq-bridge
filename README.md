# 🔗 kyorion/mq-bridge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kyorion/mq-bridge.svg)](https://packagist.org/packages/kyorion/mq-bridge)
[![Total Downloads](https://img.shields.io/packagist/dt/kyorion/mq-bridge.svg)](https://packagist.org/packages/kyorion/mq-bridge)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Laravel Message Bridge** — A secure and verified internal communication layer for Laravel microservices using **RabbitMQ**, featuring **HMAC message signing**, **JWT-based user context**, and a clean **publish/subscribe** API.

---

## 🌐 English Version

### 🚀 Overview
`kyorion/mq-bridge` is a lightweight Laravel package that simplifies and secures message exchange between microservices.  
It integrates seamlessly with **RabbitMQ**, using **HMAC SHA-256** for message integrity and **JWT tokens** for user context propagation.  
Perfect for distributed and event-driven systems where services must trust each other.

---

### ⚙️ Installation
composer require kyorion/mq-bridge

---

### 🧩 Publish Configuration
php artisan vendor:publish --tag=mq-bridge-config

This will generate a configuration file:
config/mq_bridge.php

---

### ⚙️ Example Configuration

```php
return [
    'connection' => [
        'host' => env('MQ_HOST', 'rabbitmq'),
        'port' => env('MQ_PORT', 5672),
        'user' => env('MQ_USER', 'guest'),
        'password' => env('MQ_PASSWORD', 'guest'),
        'vhost' => env('MQ_VHOST', '/'),
    ],
    'hmac_secret' => env('MQ_HMAC_SECRET', 'changeme'),
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme'),
    'services' => [
        'prescription' => [
            'exchange' => 'prescription.exchange',
            'routing_key' => 'prescription.key',
        ],
        'billing' => [
            'exchange' => 'billing.exchange',
            'routing_key' => 'billing.key',
        ],
        'notification' => [
            'exchange' => 'notification.exchange',
            'routing_key' => 'notification.key',
        ],
    ],
];
```

---

### 📨 Publishing a Message

```php
use MqBridge\Publishers\MessagePublisher;

MessagePublisher::publish('billing', 'invoice.created', [
    'invoice_id' => 123,
    'amount' => 200000,
], [
    'jwt' => 'user-jwt-token'
]);
```

---

### 📥 Consuming a Message

```php
use MqBridge\Subscribers\MessageSubscriber;

MessageSubscriber::handle($message, function ($payload, $meta, $user) {
    Log::info('✅ Verified message received', [
        'event' => $meta['event'],
        'payload' => $payload,
        'user' => $user['decoded'] ?? null,
    ]);
});
```

---

### 🔐 Security
Each message sent through `mq-bridge` is:
- Signed with HMAC SHA-256  
- Optionally includes JWT user token  
- Automatically verified upon receipt  
- Rejected if tampered or invalid  

Even if JWTs expire during queue delay, the system supports **soft verification** to ensure data consistency.

---

### 🧩 Multi-Service Example

```php
MessagePublisher::publish('prescription', 'created', [...]);
MessagePublisher::publish('inventory', 'stock.updated', [...]);
MessagePublisher::publish('notification', 'user.alert', [...]);
```

Each service’s configuration is defined in config/mq_bridge.php.

---

### 📄 License
This package is licensed under the **MIT License**.  
You are free to use, modify, and distribute this package for **commercial or open-source** purposes, provided that attribution to the author is included.

---

### 👨‍💻 Author
**KyoRion**  
GitHub: https://github.com/KyoRion  
Packagist: https://packagist.org/packages/kyorion/mq-bridge  

---

### ⭐ Support
If you find this package useful, please ⭐ star it on GitHub and feel free to contribute via Pull Requests.

---

## 🇻🇳 Phiên bản Tiếng Việt

### 🚀 Giới thiệu
`kyorion/mq-bridge` là package Laravel giúp **giao tiếp an toàn giữa các microservice** thông qua RabbitMQ.  
Package này tự động **ký message bằng HMAC SHA-256**, đính kèm **JWT để xác thực người dùng**, và hỗ trợ **publish/subscribe** tiện lợi.  
Phù hợp cho các hệ thống **microservice** hoặc **event-driven architecture** cần xác thực và tin cậy giữa các service.

---

### ⚙️ Cài đặt
``composer require kyorion/mq-bridge``

---

### 🧩 Xuất file cấu hình
``php artisan vendor:publish --tag=mq-bridge-config``

File cấu hình sẽ nằm tại:  ``config/mq_bridge.php``

---

### ⚙️ Cấu hình mẫu

```php
return [
    'connection' => [
        'host' => env('MQ_HOST', 'rabbitmq'),
        'port' => env('MQ_PORT', 5672),
        'user' => env('MQ_USER', 'guest'),
        'password' => env('MQ_PASSWORD', 'guest'),
        'vhost' => env('MQ_VHOST', '/'),
    ],
    'hmac_secret' => env('MQ_HMAC_SECRET', 'changeme'),
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme'),
    'services' => [
        'prescription' => [
            'exchange' => 'prescription.exchange',
            'routing_key' => 'prescription.key',
        ],
        'billing' => [
            'exchange' => 'billing.exchange',
            'routing_key' => 'billing.key',
        ],
        'notification' => [
            'exchange' => 'notification.exchange',
            'routing_key' => 'notification.key',
        ],
    ],
];
```

---

### 📨 Gửi message

```php
use MqBridge\Publishers\MessagePublisher;

MessagePublisher::publish('billing', 'invoice.created', [
    'invoice_id' => 123,
    'amount' => 200000,
], [
    'jwt' => 'user-jwt-token'
]);
```

---

### 📥 Nhận message

```php
use MqBridge\Subscribers\MessageSubscriber;

MessageSubscriber::handle($message, function ($payload, $meta, $user) {
    Log::info('✅ Nhận message thành công và đã xác thực', [
        'sự kiện' => $meta['event'],
        'dữ liệu' => $payload,
        'người dùng' => $user['decoded'] ?? null,
    ]);
});
```

---

### 🔐 Bảo mật
Mỗi message gửi qua `mq-bridge` đều:
- Được ký bằng HMAC-SHA256  
- Có thể kèm theo JWT chứa thông tin người dùng  
- Tự động xác thực chữ ký khi nhận  
- Bị từ chối nếu phát hiện thay đổi hoặc không hợp lệ  

Ngay cả khi JWT hết hạn, hệ thống vẫn hỗ trợ **soft verification** để đảm bảo xử lý message không bị mất dữ liệu.

---

### 🧩 Ví dụ nhiều service

```php
MessagePublisher::publish('prescription', 'created', [...]);
MessagePublisher::publish('inventory', 'stock.updated', [...]);
MessagePublisher::publish('notification', 'user.alert', [...]);
```

Mỗi service có thể cấu hình riêng trong config/mq_bridge.php.

---

### 📄 Giấy phép
Package này được phát hành theo **giấy phép MIT License**.  
Bạn có thể sử dụng cho mục đích thương mại hoặc mã nguồn mở, miễn là ghi rõ tác giả gốc.

---

### 👨‍💻 Tác giả
**KyoRion**  
GitHub: https://github.com/KyoRion  
Packagist: https://packagist.org/packages/kyorion/mq-bridge  

---

### ⭐ Hỗ trợ
Nếu bạn thấy package này hữu ích, hãy ⭐ Star trên GitHub và đóng góp bằng Pull Request!

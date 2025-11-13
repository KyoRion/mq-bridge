# 🔗 kyorion/mq-bridge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kyorion/mq-bridge.svg)](https://packagist.org/packages/kyorion/mq-bridge)
[![Total Downloads](https://img.shields.io/packagist/dt/kyorion/mq-bridge.svg)](https://packagist.org/packages/kyorion/mq-bridge)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Laravel Message Bridge** — A secure and verified internal communication layer for Laravel microservices using **RabbitMQ**, featuring **HMAC message signing**, **JWT-based user context**, and a clean **publish/subscribe** API.

---

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
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme')
];
```

---

### 📨 Gửi message

```php
use MqBridge\Publishers\MessagePublisher;

MessagePublisher::publish(
        exchange: 'foo.result.direct',
        routingKey: 'foo.result.success.core',
        data: [
            'status' => 'success'
        ],
        exchangeType: 'direct'
    );
```

---

### 📥 Nhận message

Khi bạn cần lắng nghe hàng đợi tin nhắn (message queue), hãy tạo một consumer class.

Tạo một Consumer class: ``php artisan mq:make-consumer {name}``

```php
<?php

namespace App\Consumers;

use Kyorion\MqBridge\Consumers\MessageConsumer;

class FooConsumer extends MessageConsumer
{
    public static function exchanges(): array
    {
        return [
             ['name' => 'foo.result.direct', 'type' => 'direct'],
        ];
    }

    public static function queue(): string
    {
        return 'core.foo.result';
    }

    public static function bindings(): array
    {
        return [
            'foo.result.success.core',
            'foo.result.fail.core'
        ];
    }

    public function handle(array $payload): void
    {
        logger()->info('MQ message received:', $payload);
    }
}

```

Sử dụng lệnh: ``php artisan mq:consume {consumer-class-name}``
Ví dụ: ``php artisan mq:consume FooConsumer``

Bạn có thể thêm tag '--debug' dùng để monitor trực tiếp phản hồi message từ consumer.

Bạn có thể cấu hình với Supervisor để tự động khởi động quá trình lắng nghe (auto start listen).
Sau đó, chỉ cần publish event của bạn, job sẽ tự động được bắt và xử lý.

---

### 🔐 Bảo mật
Mỗi message gửi qua `mq-bridge` đều:
- Được ký bằng HMAC-SHA256  
- Có thể kèm theo JWT chứa thông tin người dùng  
- Tự động xác thực chữ ký khi nhận  
- Bị từ chối nếu phát hiện thay đổi hoặc không hợp lệ  

Ngay cả khi JWT hết hạn, hệ thống vẫn hỗ trợ **soft verification** để đảm bảo xử lý message không bị mất dữ liệu.

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

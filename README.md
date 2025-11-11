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
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme')
];
```

---

### 📨 Publishing a Message

```php
use MqBridge\Publishers\MessagePublisher;

MessagePublisher::publish('queue-name', [
    'type' => 'queue.action',
    'data' => [
        'foo' => 'bar'
    ]
]);
```

---

### 📥 Consuming a Message

When you need to listen the message queue make a event & listener for handle custom logic by event.

Create a Event matching by format 'type' when you publish
``type: queue.action`` => then Event class name will be QueueActionEvent

After that create listener and declared it into the EventServiceProvider

```php
protected $listen = [
    QueueActionEvent::class => [
        // Make any listener for handling your data.
        CaptureQueueAction::class,
    ]
];
```

Using command: ``php artisan mq:listen {queues-name}``
Example: ``php artisan mq:listen queue-name``

You can config with supervisor for auto start listen after that just publish your event then the job automatic caught.

---

### 🔐 Security
Each message sent through `mq-bridge` is:
- Signed with HMAC SHA-256  
- Optionally includes JWT user token  
- Automatically verified upon receipt  
- Rejected if tampered or invalid  

Even if JWTs expire during queue delay, the system supports **soft verification** to ensure data consistency.

---

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
    'jwt_secret'  => env('MQ_JWT_SECRET', 'changeme')
];
```

---

### 📨 Gửi message

```php
use MqBridge\Publishers\MessagePublisher;

MessagePublisher::publish('queue-name', [
    'type' => 'queue.action',
    'data' => [
        'foo' => 'bar'
    ]
]);
```

---

### 📥 Nhận message

Khi bạn cần lắng nghe hàng đợi tin nhắn (message queue), hãy tạo Event và Listener để xử lý logic tùy chỉnh thông qua event.

Tạo một Event khớp với định dạng 'type' mà bạn sử dụng khi publish:
``type: queue.action`` => Khi đó tên class Event sẽ là QueueActionEvent

Sau đó, tạo Listener và khai báo nó trong EventServiceProvider:

```php
protected $listen = [
    QueueActionEvent::class => [
        // Make any listener for handling your data.
        CaptureQueueAction::class,
    ]
];
```

Sử dụng lệnh: ``php artisan mq:listen {queues-name}``
Ví dụ: ``php artisan mq:listen queue-name``

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

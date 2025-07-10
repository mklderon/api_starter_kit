<?php

return [
    'app' => [
        'name' => env('APP_NAME', 'Starter Kit'),
        'version' => '1.0.0',
        'debug' => env('DEBUG', false),
        'timezone' => env('TIMEZONE', 'UTC'),
        'url' => env('APP_URL', 'http://localhost'),
        'db_enable' => env('DB_ENABLE', false)
    ],
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'name' => env('DB_NAME', 'db_starter_kit'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'port' => env('DB_PORT', 3306)
    ],
    'jwt' => [
        'secret' => env('JWT_SECRET', 'your-super-secret-jwt-key-change-this'),
        'algorithm' => 'HS256',
        'expiration' => env('JWT_EXPIRATION', 3600)
    ],
    'cors' => [
        'origins' => env('CORS_ORIGINS', '*'),
        'methods' => 'GET,POST,PUT,DELETE,OPTIONS',
        'headers' => 'Content-Type,Authorization,X-Requested-With'
    ]
];
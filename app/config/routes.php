<?php

use Core\Framework;

$app = Framework::app();

// Middlewares global
$app->addMiddleware('CorsMiddleware');
$app->addMiddleware('LoggingMiddleware');

// Ruta check
$app->get('/', function () {
    response()->success([
        'status' => 'OK',
        'timestamp' => date('Y-m-d H:i:s'),
        'memory_usage' => memory_get_usage(true)
    ]);
});
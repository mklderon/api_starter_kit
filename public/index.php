<?php

spl_autoload_register(function ($class) {
    $prefix = 'Core\\';
    $base_dir = __DIR__ . '/../core/';
    
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    $prefixes = [
        'Core\\' => __DIR__ . '/../core/',
        'App\\Middleware\\' => __DIR__ . '/../app/middleware/',
        'App\\Controllers\\' => __DIR__ . '/../app/controllers/',
        'App\\Models\\' => __DIR__ . '/../app/models/'
    ];
    
    foreach ($prefixes as $prefix => $base_dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $relative_class = substr($class, strlen($prefix));
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Cargar helpers
$helpersFile = __DIR__ . '/../app/helpers/functions.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
} else {
    throw new \Exception("Helpers file not found: $helpersFile");
}

// Inicializar y ejecutar la aplicación
use Core\Framework;
use Core\Logger;
use Core\Response;

try {
    $app = new Framework();
    $app->run();
} catch (\Exception $e) {
    $logger = new Logger();
    $logger->error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    if (env('DEBUG', false)) {
        Response::error($e->getMessage(), $e->getCode() ?: 500);
    } else {
        Response::error('Internal server error', 500);
    }
}
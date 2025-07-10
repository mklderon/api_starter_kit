<?php

namespace App\Middleware;

use Core\Middleware;

class CorsMiddleware extends Middleware {
    public function handle() {
        // Este middleware solo configura headers CORS y siempre permite continuar
        // Los headers CORS ya se configuran en Framework::run(), pero podemos reforzarlos aquí
        
        $corsConfig = config('cors');
        
        if ($corsConfig) {
            header('Access-Control-Allow-Origin: ' . $corsConfig['origins']);
            header('Access-Control-Allow-Methods: ' . $corsConfig['methods']);
            header('Access-Control-Allow-Headers: ' . $corsConfig['headers']);
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Para requests OPTIONS, responder inmediatamente
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        debug("CorsMiddleware: CORS headers set successfully");
        return true; // Siempre permitir continuar
    }
}
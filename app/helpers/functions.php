<?php

if (!function_exists('app')) {
    function app() {
        return Core\Framework::app();
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        return app()->getConfig($key) ?? $default;
    }
}

if (!function_exists('database')) {
    function database() {
        return app()->getDatabase();
    }
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $var) {
            var_dump($var);
        }
        die();
    }
}

if (!function_exists('debug')) {
    function debug($data) {
        if (env('APP_ENV', 'production') === 'development') {
            error_log('DEBUG: ' . print_r($data, true));
        }
    }
}

if (!function_exists('response')) {
    function response() {
        return new Core\Response();
    }
}

if (!function_exists('jwt')) {
    function jwt() {
        Core\JWT::init(config('jwt'));
        return new class {
            public function encode($payload) {
                return Core\JWT::encode($payload);
            }
            
            public function decode($token) {
                return Core\JWT::decode($token);
            }
            
            public function getToken() {
                return Core\JWT::getTokenFromHeader();
            }
        };
    }
}

// Nuevas funciones para manejo de fechas con timezone
if (!function_exists('now')) {
    function now($format = 'Y-m-d H:i:s') {
        return date($format);
    }
}

if (!function_exists('today')) {
    function today($format = 'Y-m-d') {
        return date($format);
    }
}

if (!function_exists('timezone')) {
    function timezone() {
        return app()->getTimezone();
    }
}

if (!function_exists('carbon')) {
    function carbon($date = null) {
        $timezone = app()->getTimezone();
        if ($date) {
            return new DateTime($date, new DateTimeZone($timezone));
        }
        return new DateTime('now', new DateTimeZone($timezone));
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'Y-m-d H:i:s') {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        return $date->format($format);
    }
}
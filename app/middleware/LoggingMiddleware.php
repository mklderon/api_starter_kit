<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Logger;

class LoggingMiddleware extends Middleware
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function handle()
    {
        // Log request details
        $this->logger->info('Request', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Register error handler
        set_error_handler(function ($severity, $message, $file, $line) {
            $this->logger->error("PHP Error: {$message}", [
                'file' => $file,
                'line' => $line,
                'severity' => $this->getSeverityLabel($severity)
            ]);
        });

        // Register exception handler
        set_exception_handler(function ($exception) {
            $this->logger->error("Uncaught Exception: {$exception->getMessage()}", [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        });

        return true;
    }

    private function getSeverityLabel($severity)
    {
        $labels = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE => 'NOTICE',
            E_STRICT => 'STRICT',
            E_DEPRECATED => 'DEPRECATED'
        ];
        return $labels[$severity] ?? 'UNKNOWN';
    }
}
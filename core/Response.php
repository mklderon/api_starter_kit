<?php

namespace Core;

/**
 * Class Response
 * 
 * Handles HTTP responses with JSON formatting and CORS headers.
 */
class Response
{
    /**
     * Send a JSON response.
     *
     * @param mixed $data Response data
     * @param int $code HTTP status code
     */
    public static function json($data, int $code = 200): void
    {
        $code = is_numeric($code) && $code >= 100 && $code <= 599 ? $code : 500;
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        Logger::outputBrowserLogs();
        exit;
    }

    /**
     * Send a successful JSON response.
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     */
    public static function success($data = [], string $message = 'Success', int $code = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message Error message
     * @param int|string $code HTTP status code or PDO error code
     * @param array $data Additional error data
     */
    public static function error($message = 'Error', $code = 400, array $data = []): void
    {
        if (!is_numeric($code)) {
            $code = $code === '23000' ? 422 : 500;
        }
        
        self::json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Set CORS headers.
     *
     * @param array $corsConfig CORS configuration
     */
    public static function setCorsHeaders(array $corsConfig): void
    {
        header('Access-Control-Allow-Origin: ' . $corsConfig['origins']);
        header('Access-Control-Allow-Methods: ' . $corsConfig['methods']);
        header('Access-Control-Allow-Headers: ' . $corsConfig['headers']);
        header('Access-Control-Allow-Credentials: true');
    }
}
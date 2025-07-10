<?php

namespace Core;

/**
 * Class JWT
 * 
 * Handles JSON Web Token (JWT) encoding and decoding.
 */
class JWT
{
    /** @var string Secret key for signing tokens */
    private static $secret;

    /** @var string Signing algorithm */
    private static $algorithm = 'HS256';

    /** @var int Token expiration time in seconds */
    private static $expiration = 3600;

    /** @var Logger Logger instance for logging JWT events */
    private static $logger;

    /**
     * Initialize JWT configuration.
     *
     * @param array $config JWT configuration
     */
    public static function init(array $config): void
    {
        self::$secret = $config['secret'];
        self::$algorithm = $config['algorithm'] ?? 'HS256';
        self::$expiration = $config['expiration'] ?? 3600;
        self::$logger = new Logger();
    }

    /**
     * Encode a payload into a JWT.
     *
     * @param array $payload Payload data
     * @return string The encoded JWT
     */
    public static function encode(array $payload): string
    {
        try {
            $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
            $payload['iat'] = time();
            $payload['exp'] = time() + self::$expiration;
            $payload = json_encode($payload);
            
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret, true);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
            self::$logger->info("JWT encoded successfully", ['payload' => $payload]);
            return $jwt;
        } catch (\Exception $e) {
            self::$logger->error("JWT encoding failed: " . $e->getMessage(), ['payload' => $payload]);
            throw $e;
        }
    }

    /**
     * Decode a JWT and validate it.
     *
     * @param string $jwt JWT string
     * @return array Decoded payload
     * @throws \Exception If token is invalid or expired
     */
    public static function decode(string $jwt): array
    {
        try {
            if (!$jwt || !is_string($jwt)) {
                throw new \Exception('Invalid or missing token');
            }
            
            $parts = explode('.', $jwt);
            
            if (count($parts) !== 3) {
                throw new \Exception('Invalid token structure');
            }
            
            [$header, $payload, $signature] = $parts;
            
            $decodedHeader = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $header)), true);
            $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            
            if (!$decodedHeader || !$decodedPayload) {
                throw new \Exception('Invalid token encoding');
            }
            
            $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], 
                base64_encode(hash_hmac('sha256', $header . "." . $payload, self::$secret, true))
            );
            
            if (!hash_equals($signature, $validSignature)) {
                throw new \Exception('Invalid token signature');
            }
            
            if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
                throw new \Exception('Token has expired');
            }
            
            self::$logger->info("JWT decoded successfully", ['payload' => $decodedPayload]);
            return $decodedPayload;
        } catch (\Exception $e) {
            self::$logger->error("JWT decoding failed: " . $e->getMessage(), ['jwt' => $jwt]);
            throw $e;
        }
    }

    /**
     * Get JWT from Authorization header.
     *
     * @return string|null The JWT token or null if not found
     */
    public static function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            self::$logger->warning("No JWT token found in Authorization header");
            return null;
        }
        
        return $matches[1];
    }
}
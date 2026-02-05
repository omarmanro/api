<?php

declare(strict_types=1);

namespace App\Middleware;

class CorsMiddleware
{
    public static function handle(): void
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins) || ($_ENV['APP_ENV'] ?? '') === 'development') {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
    }
}

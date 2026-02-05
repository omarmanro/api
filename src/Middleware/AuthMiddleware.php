<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class AuthMiddleware
{
    public static function handle(): ?array
    {
        $token = self::getBearerToken();

        if (!$token) {
            return null;
        }

        try {
            $secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            
            return [
                'id' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'role' => $decoded->role ?? 'consulta',
                'plantelId' => $decoded->plantelId ?? null,
                'name' => $decoded->name ?? null
            ];
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function getBearerToken(): ?string
    {
        $headers = self::getAuthorizationHeader();
        
        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function getAuthorizationHeader(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }
}

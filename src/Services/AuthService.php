<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private UserRepository $userRepository;
    private string $jwtSecret;
    private int $jwtExpiration;
    private int $refreshExpiration;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-me';
        $this->jwtExpiration = (int) ($_ENV['JWT_EXPIRATION'] ?? 3600);
        $this->refreshExpiration = (int) ($_ENV['JWT_REFRESH_EXPIRATION'] ?? 604800);
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->userRepository->findActiveByEmail($email);
        
        if (!$user || !$user->verifyPassword($password)) {
            return null;
        }

        $this->userRepository->updateLastLogin($user->getId());

        return $this->generateTokens($user);
    }

    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->jwtSecret, 'HS256'));
            
            if ($decoded->type !== 'refresh') {
                return null;
            }

            $user = $this->userRepository->find($decoded->user_id);
            
            if (!$user || !$user->isActive()) {
                return null;
            }

            return $this->generateTokens($user);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if ($decoded->type !== 'access') {
                return null;
            }

            return [
                'id' => $decoded->user_id,
                'email' => $decoded->email,
                'role' => $decoded->role,
                'plantelId' => $decoded->plantel_id ?? null,
                'name' => $decoded->name
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function generateTokens(User $user): array
    {
        $now = time();

        $accessPayload = [
            'iss' => $_ENV['APP_URL'] ?? 'api',
            'iat' => $now,
            'exp' => $now + $this->jwtExpiration,
            'type' => 'access',
            'user_id' => $user->getId(),
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'plantel_id' => $user->plantel_id
        ];

        $refreshPayload = [
            'iss' => $_ENV['APP_URL'] ?? 'api',
            'iat' => $now,
            'exp' => $now + $this->refreshExpiration,
            'type' => 'refresh',
            'user_id' => $user->getId()
        ];

        return [
            'access_token' => JWT::encode($accessPayload, $this->jwtSecret, 'HS256'),
            'refresh_token' => JWT::encode($refreshPayload, $this->jwtSecret, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtExpiration,
            'user' => $user->toArray()
        ];
    }

    public function getUser(int $userId): ?User
    {
        return $this->userRepository->find($userId);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);
        
        if (!$user || !$user->verifyPassword($currentPassword)) {
            return false;
        }

        return $this->userRepository->changePassword($userId, $newPassword);
    }

    public function resetPassword(int $userId, string $newPassword): bool
    {
        return $this->userRepository->changePassword($userId, $newPassword);
    }
}

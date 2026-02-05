<?php

declare(strict_types=1);

namespace App\Middleware;

class RoleMiddleware
{
    public static function handle(array $user, array $allowedRoles): bool
    {
        $userRole = $user['role'] ?? 'consulta';
        
        // Admin has access to everything
        if ($userRole === 'admin') {
            return true;
        }

        return in_array($userRole, $allowedRoles);
    }
}

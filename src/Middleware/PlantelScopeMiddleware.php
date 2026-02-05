<?php

declare(strict_types=1);

namespace App\Middleware;

class PlantelScopeMiddleware
{
    private static ?int $currentPlantelId = null;
    private static bool $isAdmin = false;

    public static function handle(array $user): void
    {
        self::$isAdmin = ($user['role'] ?? '') === 'admin';
        
        // Check if plantelId is provided in query params (for admin filtering)
        $queryPlantelId = $_GET['plantelId'] ?? null;
        
        if (self::$isAdmin && $queryPlantelId !== null) {
            // Admin can filter by any plantel
            self::$currentPlantelId = (int) $queryPlantelId;
        } elseif (!self::$isAdmin) {
            // Non-admin users are scoped to their plantel
            self::$currentPlantelId = $user['plantelId'] ?? null;
        } else {
            // Admin without filter sees all
            self::$currentPlantelId = null;
        }
    }

    public static function getPlantelId(): ?int
    {
        return self::$currentPlantelId;
    }

    public static function isAdmin(): bool
    {
        return self::$isAdmin;
    }

    public static function getScopeCondition(string $alias = ''): string
    {
        if (self::$isAdmin && self::$currentPlantelId === null) {
            return '1=1'; // No filter for admin viewing all
        }

        $column = $alias ? "{$alias}.plantel_id" : 'plantel_id';
        return "{$column} = " . (self::$currentPlantelId ?? 0);
    }

    public static function addScopeToQuery(string $sql, string $alias = ''): string
    {
        $condition = self::getScopeCondition($alias);
        
        if (stripos($sql, 'WHERE') !== false) {
            return preg_replace('/WHERE/i', "WHERE {$condition} AND ", $sql, 1);
        }
        
        // Add WHERE before ORDER BY, GROUP BY, LIMIT, or at the end
        if (preg_match('/(ORDER BY|GROUP BY|LIMIT|$)/i', $sql, $matches, PREG_OFFSET_MATCH)) {
            $position = $matches[0][1];
            return substr($sql, 0, $position) . " WHERE {$condition} " . substr($sql, $position);
        }

        return $sql . " WHERE {$condition}";
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;

class AuditService
{
    private AuditLogRepository $auditRepository;
    private static ?int $currentUserId = null;
    private static ?int $currentPlantelId = null;

    public function __construct()
    {
        $this->auditRepository = new AuditLogRepository();
    }

    public static function setContext(?int $userId, ?int $plantelId): void
    {
        self::$currentUserId = $userId;
        self::$currentPlantelId = $plantelId;
    }

    public function logCreate(string $table, int $recordId, array $newValues): void
    {
        $this->auditRepository->log(
            $table,
            $recordId,
            'create',
            null,
            $this->sanitizeValues($newValues),
            self::$currentUserId,
            self::$currentPlantelId
        );
    }

    public function logUpdate(string $table, int $recordId, array $oldValues, array $newValues): void
    {
        // Solo registrar si hay cambios reales
        $changes = $this->getChanges($oldValues, $newValues);
        if (empty($changes['old']) && empty($changes['new'])) {
            return;
        }

        $this->auditRepository->log(
            $table,
            $recordId,
            'update',
            $this->sanitizeValues($changes['old']),
            $this->sanitizeValues($changes['new']),
            self::$currentUserId,
            self::$currentPlantelId
        );
    }

    public function logDelete(string $table, int $recordId, array $oldValues): void
    {
        $this->auditRepository->log(
            $table,
            $recordId,
            'delete',
            $this->sanitizeValues($oldValues),
            null,
            self::$currentUserId,
            self::$currentPlantelId
        );
    }

    private function getChanges(array $old, array $new): array
    {
        $oldChanges = [];
        $newChanges = [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                if ($key !== 'updated_at') {
                    $oldChanges[$key] = $old[$key] ?? null;
                    $newChanges[$key] = $value;
                }
            }
        }

        return ['old' => $oldChanges, 'new' => $newChanges];
    }

    private function sanitizeValues(array $values): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'api_key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '********';
            }
        }

        return $values;
    }

    public function getHistory(string $table, int $recordId): array
    {
        return $this->auditRepository->getRecordHistory($table, $recordId);
    }

    public function getRecentActivity(?int $plantelId = null, int $limit = 50): array
    {
        return $this->auditRepository->getRecentActivity($plantelId, $limit);
    }
}

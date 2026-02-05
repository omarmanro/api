<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;

class AuditLogRepository extends BaseRepository
{
    protected string $model = AuditLog::class;

    public function log(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?int $plantelId = null
    ): AuditLog {
        return $this->create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id' => $userId,
            'plantel_id' => $plantelId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    public function findByTable(string $tableName, ?int $recordId = null): array
    {
        $conditions = ['table_name' => $tableName];
        if ($recordId !== null) {
            $conditions['record_id'] = $recordId;
        }
        return $this->all($conditions, ['created_at' => 'DESC']);
    }

    public function findByUser(int $userId, ?int $limit = 100): array
    {
        return $this->all(['user_id' => $userId], ['created_at' => 'DESC'], $limit);
    }

    public function findByPlantel(int $plantelId, ?int $limit = 100): array
    {
        return $this->all(['plantel_id' => $plantelId], ['created_at' => 'DESC'], $limit);
    }

    public function getRecordHistory(string $tableName, int $recordId): array
    {
        return $this->all(
            ['table_name' => $tableName, 'record_id' => $recordId],
            ['created_at' => 'ASC']
        );
    }

    public function getRecentActivity(?int $plantelId = null, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.name as user_name, u.email as user_email
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id";
        
        $params = [];

        if ($plantelId !== null) {
            $sql .= " WHERE al.plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT {$limit}";

        return $this->db->fetchAll($sql, $params);
    }
}

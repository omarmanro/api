<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditService;

class AuditController extends BaseController
{
    private AuditService $auditService;

    public function __construct()
    {
        $this->auditService = new AuditService();
    }

    public function index(array $query): array
    {
        $plantelId = $this->getPlantelId();
        $limit = min(100, (int) ($query['limit'] ?? 50));

        $logs = $this->auditService->getRecentActivity($plantelId, $limit);

        return $this->success($logs);
    }

    public function byTable(string $table, array $query): array
    {
        $recordId = isset($query['record_id']) ? (int) $query['record_id'] : null;
        
        $logs = $this->auditService->getHistory($table, $recordId);

        return $this->success($logs);
    }

    public function byRecord(string $table, int $recordId): array
    {
        $logs = $this->auditService->getHistory($table, $recordId);

        return $this->success($logs);
    }
}

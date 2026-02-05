<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MonthlyFee;

class MonthlyFeeRepository extends BaseRepository
{
    protected string $model = MonthlyFee::class;

    public function findByPlantelAndCycle(int $plantelId, int $cycleId): array
    {
        return $this->all([
            'plantel_id' => $plantelId,
            'cycle_id' => $cycleId,
            'status' => MonthlyFee::STATUS_ACTIVE
        ], ['name' => 'ASC']);
    }

    public function findByCareer(int $careerId, int $cycleId): array
    {
        return $this->all([
            'career_id' => $careerId,
            'cycle_id' => $cycleId,
            'status' => MonthlyFee::STATUS_ACTIVE
        ], ['name' => 'ASC']);
    }

    public function getActiveForPlantel(int $plantelId): array
    {
        $sql = "SELECT mf.*, c.name as career_name, sc.name as cycle_name
                FROM {$this->table} mf
                LEFT JOIN careers c ON mf.career_id = c.id
                LEFT JOIN school_cycles sc ON mf.cycle_id = sc.id
                WHERE mf.plantel_id = :plantel_id
                AND mf.status = :status
                AND sc.status = :cycle_status
                ORDER BY c.name ASC, mf.name ASC";
        
        return $this->db->fetchAll($sql, [
            'plantel_id' => $plantelId,
            'status' => MonthlyFee::STATUS_ACTIVE,
            'cycle_status' => 'active'
        ]);
    }
}

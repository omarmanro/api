<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EnrollmentFee;

class EnrollmentFeeRepository extends BaseRepository
{
    protected string $model = EnrollmentFee::class;

    public function findByPlantelAndCycle(int $plantelId, int $cycleId): array
    {
        return $this->all([
            'plantel_id' => $plantelId,
            'cycle_id' => $cycleId,
            'status' => EnrollmentFee::STATUS_ACTIVE
        ], ['name' => 'ASC']);
    }

    public function findByType(string $feeType, int $plantelId, int $cycleId): array
    {
        return $this->all([
            'fee_type' => $feeType,
            'plantel_id' => $plantelId,
            'cycle_id' => $cycleId,
            'status' => EnrollmentFee::STATUS_ACTIVE
        ], ['name' => 'ASC']);
    }

    public function getActiveForPlantel(int $plantelId): array
    {
        $sql = "SELECT ef.*, c.name as career_name, sc.name as cycle_name
                FROM {$this->table} ef
                LEFT JOIN careers c ON ef.career_id = c.id
                LEFT JOIN school_cycles sc ON ef.cycle_id = sc.id
                WHERE ef.plantel_id = :plantel_id
                AND ef.status = :status
                AND sc.status = :cycle_status
                ORDER BY c.name ASC, ef.fee_type ASC, ef.name ASC";
        
        return $this->db->fetchAll($sql, [
            'plantel_id' => $plantelId,
            'status' => EnrollmentFee::STATUS_ACTIVE,
            'cycle_status' => 'active'
        ]);
    }
}
